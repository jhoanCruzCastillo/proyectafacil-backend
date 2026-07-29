<?php

namespace App\Controllers;

use App\Models\HistorialCambioModel;
use CodeIgniter\HTTP\ResponseInterface;

// Espejo de `CambioFicha`/`CampoCambio` en frontend/src/types/index.ts. El cliente arma la entrada
// completa (incluye `fecha`, generada al momento de guardar) — `registrar()` la persiste tal cual;
// `campos[]` se descompone en historial_cambio_campos (misma lógica que ejemplo_tipologia_ioarr).
class HistorialCambiosController extends BaseController
{
    public function index(): ResponseInterface
    {
        $filas = (new HistorialCambioModel())->orderBy('fecha', 'DESC')->orderBy('id', 'DESC')->findAll();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function listByEjemplo($ejemploId = null): ResponseInterface
    {
        $filas = (new HistorialCambioModel())
            ->where('ejemplo_id', (int) $ejemploId)
            ->orderBy('fecha', 'DESC')->orderBy('id', 'DESC')
            ->findAll();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function registrar(): ResponseInterface
    {
        $dto   = $this->request->getJSON(true) ?? [];
        $model = new HistorialCambioModel();
        $id    = $model->insert([
            'ejemplo_id' => (int) ($dto['ejemploId'] ?? 0),
            'usuario_id' => (int) ($dto['usuarioId'] ?? 0),
            'fecha'      => array_key_exists('fecha', $dto) ? $this->isoAMysql((string) $dto['fecha']) : date('Y-m-d H:i:s'),
        ], true);

        $db = db_connect();
        foreach ((array) ($dto['campos'] ?? []) as $campo) {
            $db->table('historial_cambio_campos')->insert([
                'historial_cambio_id' => $id,
                'identificador'       => (string) ($campo['identificador'] ?? ''),
                'etiqueta'            => (string) ($campo['etiqueta'] ?? ''),
                'valor_anterior'      => (string) ($campo['valorAnterior'] ?? ''),
                'valor_nuevo'         => (string) ($campo['valorNuevo'] ?? ''),
            ]);
        }

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    private function toDto(array $fila): array
    {
        $campos = db_connect()->table('historial_cambio_campos')
            ->select('identificador, etiqueta, valor_anterior, valor_nuevo')
            ->where('historial_cambio_id', $fila['id'])
            ->get()->getResultArray();

        return [
            'id'        => (string) $fila['id'],
            'ejemploId' => (string) $fila['ejemplo_id'],
            'usuarioId' => (string) $fila['usuario_id'],
            'fecha'     => str_replace(' ', 'T', $fila['fecha']) . 'Z',
            'campos'    => array_map(static fn (array $c) => [
                'identificador' => $c['identificador'],
                'etiqueta'      => $c['etiqueta'],
                'valorAnterior' => $c['valor_anterior'],
                'valorNuevo'    => $c['valor_nuevo'],
            ], $campos),
        ];
    }

    private function isoAMysql(string $valor): string
    {
        $ts = strtotime($valor);

        return $ts === false ? $valor : date('Y-m-d H:i:s', $ts);
    }
}
