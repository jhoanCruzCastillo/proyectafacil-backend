<?php

namespace App\Controllers;

use App\Models\CursoModel;
use CodeIgniter\HTTP\ResponseInterface;

// Cursos a los que pertenece un cliente/alumno (pestaña "Clientes - Alumnos" de Usuarios y
// permisos: chips de filtro + botón "Crear curso"). Espejo de `Curso` en
// frontend/src/types/index.ts — mismo patrón que SectoresController (colorAccent, cantidad
// calculada con COUNT(), nunca columna, ver 3FN en docs/database-design.md).
class CursosController extends BaseController
{
    public function index(): ResponseInterface
    {
        $model = new CursoModel();
        $filas = $model->orderBy('nombre', 'ASC')->findAll();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function create(): ResponseInterface
    {
        if ($gate = $this->exigirAdmin()) {
            return $gate;
        }

        $dto = $this->request->getJSON(true) ?? [];
        $nombre = trim((string) ($dto['nombre'] ?? ''));
        if ($nombre === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'El nombre del curso es obligatorio.']);
        }

        $model = new CursoModel();
        if ($model->where('nombre', $nombre)->countAllResults() > 0) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Ya existe un curso con ese nombre.']);
        }

        $id = $model->insert([
            'nombre'       => $nombre,
            'color_accent' => trim((string) ($dto['colorAccent'] ?? '')) ?: '#2563eb',
        ], true);

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    private function exigirAdmin(): ?ResponseInterface
    {
        $rol = session()->get('usuario_rol');
        if (! in_array($rol, ['administrador', 'superusuario'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'No tienes permiso para esta acción']);
        }

        return null;
    }

    private function toDto(array $fila): array
    {
        $cantidadAlumnos = db_connect()->table('usuarios')->where('curso_id', $fila['id'])->countAllResults();

        return [
            'id'              => (string) $fila['id'],
            'nombre'          => $fila['nombre'],
            'colorAccent'     => $fila['color_accent'],
            'cantidadAlumnos' => $cantidadAlumnos,
        ];
    }
}
