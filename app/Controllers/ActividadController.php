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
            'mensaje'    => (string) ($dto['mensaje'] ?? ''),
            'color'      => (string) ($dto['color'] ?? 'gray'),
            'created_at' => date('Y-m-d H:i:s'),
        ], true);

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    private function toDto(array $fila): array
    {
        return [
            'id'      => (string) $fila['id'],
            'mensaje' => $fila['mensaje'],
            'fecha'   => $this->tiempoRelativo($fila['created_at']),
            'color'   => $fila['color'],
        ];
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
