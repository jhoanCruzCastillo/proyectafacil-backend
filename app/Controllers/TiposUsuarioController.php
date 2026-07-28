<?php

namespace App\Controllers;

use App\Models\TipoUsuarioModel;
use CodeIgniter\HTTP\ResponseInterface;

// Espejo de `TipoUsuario` en frontend/src/types/index.ts.
class TiposUsuarioController extends BaseController
{
    public function index(): ResponseInterface
    {
        $filas = (new TipoUsuarioModel())->orderBy('id')->findAll();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function create(): ResponseInterface
    {
        $dto = $this->request->getJSON(true) ?? [];
        $model = new TipoUsuarioModel();
        $id = $model->insert([
            'nombre'     => $dto['nombre'] ?? null,
            'nivel_base' => $dto['nivelBase'] ?? null,
        ], true);

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    public function update($id = null): ResponseInterface
    {
        $model = new TipoUsuarioModel();
        if (! $model->find($id)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Tipo de usuario no encontrado']);
        }

        $dto = $this->request->getJSON(true) ?? [];
        $cambios = [];
        if (array_key_exists('nombre', $dto)) {
            $cambios['nombre'] = $dto['nombre'];
        }
        if (array_key_exists('nivelBase', $dto)) {
            $cambios['nivel_base'] = $dto['nivelBase'];
        }
        if ($cambios !== []) {
            $model->update($id, $cambios);
        }

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    public function delete($id = null): ResponseInterface
    {
        (new TipoUsuarioModel())->delete($id);

        return $this->response->setJSON((object) []);
    }

    private function toDto(array $fila): array
    {
        return [
            'id'        => (string) $fila['id'],
            'nombre'    => $fila['nombre'],
            'nivelBase' => $fila['nivel_base'],
        ];
    }
}
