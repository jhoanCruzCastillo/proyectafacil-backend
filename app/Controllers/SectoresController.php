<?php

namespace App\Controllers;

use App\Models\SectorModel;
use CodeIgniter\HTTP\ResponseInterface;

// Espejo de `Sector` en frontend/src/types/index.ts. cantidadPlantillas/cantidadEjemplos nunca son
// columnas (ver 3FN en docs/database-design.md) — se calculan con COUNT() en cada respuesta.
class SectoresController extends BaseController
{
    public function index(): ResponseInterface
    {
        $model = new SectorModel();
        $filas = $model->orderBy('id')->findAll();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function show($id = null): ResponseInterface
    {
        $fila = (new SectorModel())->find($id);
        if (! $fila) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Sector no encontrado']);
        }

        return $this->response->setJSON($this->toDto($fila));
    }

    public function create(): ResponseInterface
    {
        $dto = $this->request->getJSON(true) ?? [];
        $model = new SectorModel();
        $id = $model->insert($this->fromDto($dto), true);

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    public function update($id = null): ResponseInterface
    {
        $model = new SectorModel();
        if (! $model->find($id)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Sector no encontrado']);
        }

        $dto = $this->request->getJSON(true) ?? [];
        $cambios = $this->fromDto($dto, soloProvistos: true);
        if ($cambios !== []) {
            $model->update($id, $cambios);
        }

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    public function delete($id = null): ResponseInterface
    {
        (new SectorModel())->delete($id);

        return $this->response->setJSON((object) []);
    }

    private function toDto(array $fila): array
    {
        $db = db_connect();
        $cantidadPlantillas = $db->table('plantillas')->where('sector_id', $fila['id'])->countAllResults();
        $cantidadEjemplos = $db->table('ejemplos')
            ->join('plantillas', 'plantillas.id = ejemplos.plantilla_id')
            ->where('plantillas.sector_id', $fila['id'])
            ->countAllResults();

        return [
            'id'                 => (string) $fila['id'],
            'nombre'             => $fila['nombre'],
            'codigo'             => $fila['codigo'],
            'icono'              => $fila['icono'],
            'colorAccent'        => $fila['color_accent'],
            'descripcion'        => $fila['descripcion'],
            'tipoSector'         => $fila['tipo_sector'],
            'activo'             => (bool) $fila['activo'],
            'cantidadPlantillas' => $cantidadPlantillas,
            'cantidadEjemplos'   => $cantidadEjemplos,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fromDto(array $dto, bool $soloProvistos = false): array
    {
        $mapa = [
            'codigo'      => 'codigo',
            'nombre'      => 'nombre',
            'icono'       => 'icono',
            'colorAccent' => 'color_accent',
            'descripcion' => 'descripcion',
            'tipoSector'  => 'tipo_sector',
            'activo'      => 'activo',
        ];

        $fila = [];
        foreach ($mapa as $claveDto => $columna) {
            if ($soloProvistos && ! array_key_exists($claveDto, $dto)) {
                continue;
            }
            $valor = $dto[$claveDto] ?? null;
            $fila[$columna] = $columna === 'activo' ? (int) ($valor ?? true) : $valor;
        }

        return $fila;
    }
}
