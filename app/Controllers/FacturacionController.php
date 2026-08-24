<?php

namespace App\Controllers;

use App\Models\AddOnModel;
use App\Models\FacturacionModel;
use App\Models\PlanModel;
use CodeIgniter\HTTP\ResponseInterface;
use Stripe\StripeClient;
use Throwable;

// Espejo de `FacturacionMock` en frontend/src/types/index.ts. `addons` usa los mismos slugs que
// el catálogo estático frontend/src/data/planes.ts ('consultoria-1a1', etc.) — se resuelven contra
// `add_ons.nombre` (ver ADDON_SLUGS) en vez de guardar esos slugs como PK real, porque
// `add_ons.id` es auto_increment. El cambio de plan, la compra de add-ons, el método de pago, y
// las facturas ya NO se escriben acá — eso ahora es 100% Stripe real (ver PagosController):
// Checkout para la primera compra, ajuste directo de la suscripción para cambios posteriores, y el
// Customer Portal de Stripe para tarjeta/cancelación/historial. Este controller queda para
// lectura (get()) y el único toggle que no depende de un Checkout nuevo: cancelar/reactivar.
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
        $model     = new FacturacionModel();
        if (! $model->find($usuarioId)) {
            $this->crearDefault($usuarioId);
        }

        $dto = $this->request->getJSON(true) ?? [];
        if (array_key_exists('cancelada', $dto)) {
            $this->actualizarCancelacion($usuarioId, (bool) $dto['cancelada']);
        }

        return $this->response->setJSON($this->toDto($usuarioId));
    }

    // Único toggle que sigue viviendo acá en vez de en PagosController: si ya hay una suscripción
    // real de Stripe, la cancela/reactiva de verdad (cancel_at_period_end) antes de guardar la
    // bandera local — igual gestionable desde el Customer Portal, esto es el atajo rápido que ya
    // existía en la UI ("Cancelar plan"/"Volver a suscribirse").
    private function actualizarCancelacion(int $usuarioId, bool $cancelada): void
    {
        $db   = db_connect();
        $fila = $db->table('facturaciones')->where('usuario_id', $usuarioId)->get()->getRowArray();

        if ($fila && ! empty($fila['stripe_subscription_id'])) {
            $config = config('Stripe');
            if ($config->secretKey !== '') {
                try {
                    (new StripeClient($config->secretKey))->subscriptions->update($fila['stripe_subscription_id'], [
                        'cancel_at_period_end' => $cancelada,
                    ]);
                } catch (Throwable $e) {
                    log_message('error', 'FacturacionController::actualizarCancelacion falló contra Stripe: {msg}', ['msg' => $e->getMessage()]);
                }
            }
        }

        $db->table('facturaciones')->where('usuario_id', $usuarioId)->update(['cancelada' => $cancelada ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')]);
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
        TicketsConsultaController::emitirTicketsDePlan($usuarioId, (int) $planBase['id']);
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
            'planId'               => 'nivel-' . $plan['numero_nivel'],
            'plan'                 => 'Nivel ' . $plan['numero_nivel'] . ' — ' . $plan['nombre'],
            'precio'               => $this->formatMonto((float) $plan['precio']),
            'periodicidad'         => $plan['periodicidad'],
            'cancelada'            => (bool) $fila['cancelada'],
            'fechaRenovacion'      => $this->fechaIsoACliente($fila['fecha_renovacion']),
            'fechaInicioPlan'      => $fila['fecha_inicio_plan'],
            'metodoPago'           => $fila['metodo_pago'],
            'tarjetaMarca'         => $fila['tarjeta_marca'],
            'tarjetaUltimos4'      => $fila['tarjeta_ultimos4'],
            'telefonoPago'         => $fila['telefono_pago'],
            'stripeCustomerId'     => $fila['stripe_customer_id'] ?? null,
            'stripeSubscriptionId' => $fila['stripe_subscription_id'] ?? null,
            'facturas'             => array_map(static fn (array $f) => [
                'id'     => (string) $f['id'],
                'fecha'  => $f['fecha'],
                'total'  => '$' . number_format((float) $f['total'], 2),
                'estado' => $f['estado'],
            ], $facturas),
            'addons' => (object) $addons,
        ];
    }

    private function formatMonto(float $valor): string
    {
        return $valor === floor($valor) ? '$' . (int) $valor : '$' . number_format($valor, 2);
    }

    private function fechaIsoACliente(?string $iso): ?string
    {
        if ($iso === null || $iso === '') {
            return null;
        }
        $ts = strtotime($iso);

        return $ts === false ? $iso : ((int) date('j', $ts) . '/' . (int) date('n', $ts) . '/' . date('Y', $ts));
    }
}
