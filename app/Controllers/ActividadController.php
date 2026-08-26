<?php

namespace App\Controllers;

use App\Models\ActividadModel;
use CodeIgniter\HTTP\ResponseInterface;

// Espejo de `ActividadReciente` en frontend/src/types/index.ts. `fecha` se devuelve ya formateada
// como texto relativo ("hace 5 min") — espejo de frontend/src/lib/tiempoRelativo.ts — porque
// ActivityFeed.vue muestra `item.fecha` tal cual, sin volver a formatearla.
class ActividadController extends BaseController
{
    public function index(): ResponseInterface
    {
        $filas = (new ActividadModel())->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll(20);

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function push(): ResponseInterface
    {
        $dto   = $this->request->getJSON(true) ?? [];
        $model = new ActividadModel();
        $id    = $model->insert([
            'mensaje'     => (string) ($dto['mensaje'] ?? ''),
            'color'       => (string) ($dto['color'] ?? 'gray'),
            'categoria'   => trim((string) ($dto['categoria'] ?? '')) ?: null,
            // El autor siempre se toma del token, nunca de lo que mande el cliente.
            'actor_id'    => session()->get('usuario_id'),
            'objetivo_id' => isset($dto['objetivoId']) ? (int) $dto['objetivoId'] : null,
            'created_at'  => date('Y-m-d H:i:s'),
        ], true);

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    // Actividad de un usuario puntual (tab "Actividad" del panel de detalles) — paginado y con
    // ventana de tiempo, a diferencia del feed global de index() (siempre las últimas 20, sin filtro).
    public function porActor($actorId = null): ResponseInterface
    {
        $dias      = (int) ($this->request->getGet('dias') ?? 30);
        $pagina    = max(1, (int) ($this->request->getGet('pagina') ?? 1));
        $porPagina = max(1, (int) ($this->request->getGet('porPagina') ?? 10));
        $desde     = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

        $builder = (new ActividadModel())
            ->where('actor_id', (int) $actorId)
            ->where('created_at >=', $desde);

        $total = $builder->countAllResults(false);
        $filas = $builder->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')
            ->findAll($porPagina, ($pagina - 1) * $porPagina);

        return $this->response->setJSON([
            'items' => array_map([$this, 'toDto'], $filas),
            'total' => $total,
        ]);
    }

    // "Últimas modificaciones del perfil" (tab Información) — lo último que le pasó a ESTE usuario
    // (objetivo_id), sin importar quién lo hizo. Separado de porActor() (lo que ESTE usuario hizo).
    public function ultimaModificacionPerfil($usuarioId = null): ResponseInterface
    {
        $fila = (new ActividadModel())
            ->where('objetivo_id', (int) $usuarioId)
            ->where('categoria', 'Perfil')
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        return $this->response->setJSON($fila ? $this->toDto($fila) : null);
    }

    private function toDto(array $fila): array
    {
        return [
            'id'        => (string) $fila['id'],
            'mensaje'   => $fila['mensaje'],
            'fecha'     => $this->tiempoRelativo($fila['created_at']),
            'creadoEn'  => $this->aIso($fila['created_at']),
            'color'     => $fila['color'],
            'categoria' => $fila['categoria'] ?? null,
            'actorId'   => $fila['actor_id'] !== null ? (string) $fila['actor_id'] : null,
        ];
    }

    private function aIso(string $fechaMysql): string
    {
        return date(DATE_ATOM, strtotime($fechaMysql));
    }

    private function tiempoRelativo(string $fechaMysql): string
    {
        $ts      = strtotime($fechaMysql);
        $diffMin = (int) floor((time() - $ts) / 60);

        if ($diffMin < 1) {
            return 'hace un momento';
        }
        if ($diffMin < 60) {
            return "hace {$diffMin} min";
        }

        $diffHoras = (int) floor($diffMin / 60);
        if ($diffHoras < 24) {
            return "hace {$diffHoras} h";
        }

        $diffDias = (int) floor($diffHoras / 24);
        if ($diffDias === 1) {
            return 'hace 1 día';
        }
        if ($diffDias < 30) {
            return "hace {$diffDias} días";
        }

        return date('d/m/Y', $ts);
    }
}
