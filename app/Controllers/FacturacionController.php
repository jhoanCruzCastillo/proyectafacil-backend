<?php

namespace App\Controllers;

use App\Models\AddOnModel;
use App\Models\FacturacionModel;
use App\Models\PlanModel;
use CodeIgniter\HTTP\ResponseInterface;

// Espejo de `FacturacionMock` en frontend/src/types/index.ts. `planId`/`addons` usan los mismos
// slugs que el catálogo estático frontend/src/data/planes.ts ('nivel-N', 'consultoria-1a1', etc.)
// — se resuelven contra `planes.numero_nivel` y `add_ons.nombre` (ver ADDON_SLUGS) en vez de
// guardar esos slugs como PK real, porque `planes.id`/`add_ons.id` son auto_increment.
class FacturacionController extends BaseController
{
    private const ADDON_SLUGS = [
        'consultoria-1a1'     => 'Consultoría 1 a 1',
        'usuario-adicional'   => 'Usuario adicional',
        'plantilla-adicional' => 'Plantilla adicional',
    ];

    public function get($usuarioId = null): ResponseInterface
    {
        $usuarioId = (int) $usuarioId;
        if (! (new FacturacionModel())->find($usuarioId)) {
            $this->crearDefault($usuarioId);
        }

        return $this->response->setJSON($this->toDto($usuarioId));
    }

    public function update($usuarioId = null): ResponseInterface
    {
        $usuarioId = (int) $usuarioId;
        $model      = new FacturacionModel();
        if (! $model->find($usuarioId)) {
            $this->crearDefault($usuarioId);
        }

        $dto     = $this->request->getJSON(true) ?? [];
        $cambios = [];

        if (array_key_exists('planId', $dto)) {
            $plan = (new PlanModel())->where('numero_nivel', $this->planIdANumeroNivel((string) $dto['planId']))->first();
            if ($plan) {
                $cambios['plan_id'] = $plan['id'];
            }
        }
        if (array_key_exists('cancelada', $dto)) {
            $cambios['cancelada'] = $dto['cancelada'] ? 1 : 0;
        }
        if (array_key_exists('fechaRenovacion', $dto) && $dto['fechaRenovacion'] !== null) {
            $cambios['fecha_renovacion'] = $this->fechaClienteAIso((string) $dto['fechaRenovacion']);
        }
        if (array_key_exists('fechaInicioPlan', $dto) && $dto['fechaInicioPlan'] !== null) {
            $cambios['fecha_inicio_plan'] = $this->datetimeClienteAMysql((string) $dto['fechaInicioPlan']);
        }
        if (array_key_exists('metodoPago', $dto)) {
            $cambios['metodo_pago'] = $dto['metodoPago'];
        }
        if (array_key_exists('tarjetaMarca', $dto)) {
            $cambios['tarjeta_marca'] = $dto['tarjetaMarca'];
        }
        if (array_key_exists('tarjetaUltimos4', $dto)) {
            $cambios['tarjeta_ultimos4'] = $dto['tarjetaUltimos4'];
        }
        if (array_key_exists('telefonoPago', $dto)) {
            $cambios['telefono_pago'] = $dto['telefonoPago'];
        }

        if ($cambios !== []) {
            $model->update($usuarioId, $cambios);
        }

        if (array_key_exists('addons', $dto)) {
            $this->sincronizarAddons($usuarioId, (array) $dto['addons']);
        }
        if (array_key_exists('facturas', $dto)) {
            $this->sincronizarFacturas($usuarioId, (array) $dto['facturas']);
        }

        return $this->response->setJSON($this->toDto($usuarioId));
    }

    private function crearDefault(int $usuarioId): void
    {
        $planBase = (new PlanModel())->where('numero_nivel', 1)->first();
        (new FacturacionModel())->insert([
            'usuario_id'        => $usuarioId,
            'plan_id'           => $planBase['id'],
            'cancelada'         => 0,
            'fecha_renovacion'  => date('Y-m-d', strtotime('+1 month')),
            'fecha_inicio_plan' => date('Y-m-d H:i:s'),
            'metodo_pago'       => 'tarjeta',
            'tarjeta_marca'     => 'Visa',
            'tarjeta_ultimos4'  => '4242',
        ]);
    }

    private function toDto(int $usuarioId): ?array
    {
        $fila = (new FacturacionModel())->find($usuarioId);
        if (! $fila) {
            return null;
        }

        $plan = (new PlanModel())->find($fila['plan_id']);
        $db   = db_connect();

        $facturas = $db->table('facturas')
            ->select('id, fecha, total, estado')
            ->where('facturacion_usuario_id', $usuarioId)
            ->orderBy('fecha', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        $addonsFilas = $db->table('facturacion_addons')
            ->select('add_on_id, cantidad')
            ->where('facturacion_usuario_id', $usuarioId)
            ->get()->getResultArray();

        $nombrePorId = [];
        foreach ((new AddOnModel())->findAll() as $a) {
            $nombrePorId[(int) $a['id']] = $a['nombre'];
        }

        $addons = [];
        foreach ($addonsFilas as $a) {
            $slug = array_search($nombrePorId[(int) $a['add_on_id']] ?? null, self::ADDON_SLUGS, true);
            if ($slug !== false) {
                $addons[$slug] = (int) $a['cantidad'];
            }
        }

        return [
            'planId'          => 'nivel-' . $plan['numero_nivel'],
            'plan'            => 'Nivel ' . $plan['numero_nivel'] . ' — ' . $plan['nombre'],
            'precio'          => $this->formatMonto((float) $plan['precio']),
            'periodicidad'    => $plan['periodicidad'],
            'cancelada'       => (bool) $fila['cancelada'],
            'fechaRenovacion' => $this->fechaIsoACliente($fila['fecha_renovacion']),
            'fechaInicioPlan' => $fila['fecha_inicio_plan'],
            'metodoPago'      => $fila['metodo_pago'],
            'tarjetaMarca'    => $fila['tarjeta_marca'],
            'tarjetaUltimos4' => $fila['tarjeta_ultimos4'],
            'telefonoPago'    => $fila['telefono_pago'],
            'facturas'        => array_map(static fn (array $f) => [
                'id'     => (string) $f['id'],
                'fecha'  => $f['fecha'],
                'total'  => '$' . number_format((float) $f['total'], 2),
                'estado' => $f['estado'],
            ], $facturas),
            'addons' => (object) $addons,
        ];
    }

    private function sincronizarAddons(int $usuarioId, array $addons): void
    {
        $db = db_connect();
        $db->table('facturacion_addons')->where('facturacion_usuario_id', $usuarioId)->delete();

        foreach ($addons as $slug => $cantidad) {
            if ((int) $cantidad <= 0) {
                continue;
            }
            $nombre = self::ADDON_SLUGS[$slug] ?? null;
            if ($nombre === null) {
                continue;
            }
            $addOn = (new AddOnModel())->where('nombre', $nombre)->first();
            if (! $addOn) {
                continue;
            }
            $db->table('facturacion_addons')->insert([
                'facturacion_usuario_id' => $usuarioId,
                'add_on_id'              => $addOn['id'],
                'cantidad'               => (int) $cantidad,
            ]);
        }
    }

    private function sincronizarFacturas(int $usuarioId, array $facturas): void
    {
        $db         = db_connect();
        $existentes = $db->table('facturas')->select('id')->where('facturacion_usuario_id', $usuarioId)->get()->getResultArray();
        $idsExistentes = array_map(static fn (array $f) => (string) $f['id'], $existentes);

        foreach ($facturas as $f) {
            $id = (string) ($f['id'] ?? '');
            if (in_array($id, $idsExistentes, true)) {
                continue;
            }
            $db->table('facturas')->insert([
                'facturacion_usuario_id' => $usuarioId,
                'fecha'                  => $this->fechaClienteAIso((string) $f['fecha']),
                'total'                  => $this->parseMonto($f['total']),
                'estado'                 => $f['estado'],
            ]);
        }
    }

    private function planIdANumeroNivel(string $planId): int
    {
        return (int) str_replace('nivel-', '', $planId);
    }

    private function parseMonto($valor): float
    {
        if (is_numeric($valor)) {
            return (float) $valor;
        }

        return (float) preg_replace('/[^0-9.\-]/', '', (string) $valor);
    }

    private function formatMonto(float $valor): string
    {
        return $valor === floor($valor) ? '$' . (int) $valor : '$' . number_format($valor, 2);
    }

    // Acepta 'd/m/Y' (Date.toLocaleDateString('es-PE') del frontend) o 'Y-m-d' ya ISO.
    private function fechaClienteAIso(string $valor): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $valor)) {
            return substr($valor, 0, 10);
        }
        $partes = explode('/', $valor);
        if (count($partes) === 3) {
            [$d, $m, $y] = $partes;

            return sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
        }

        return $valor;
    }

    private function fechaIsoACliente(?string $iso): ?string
    {
        if ($iso === null || $iso === '') {
            return null;
        }
        $ts = strtotime($iso);

        return $ts === false ? $iso : ((int) date('j', $ts) . '/' . (int) date('n', $ts) . '/' . date('Y', $ts));
    }

    private function datetimeClienteAMysql(string $valor): string
    {
        $ts = strtotime($valor);

        return $ts === false ? $valor : date('Y-m-d H:i:s', $ts);
    }
}
