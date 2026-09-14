<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Catálogo de "temas de especialidad" del asesor (Proyectos de Inversión, Contrataciones con el
// Estado, Ejecución de Obras..., ver TemasEspecialidadSeeder) — SEPARADO de SectoresController (los
// 13 sectores MEF de las fichas/plantillas). Solo lectura por ahora: el catálogo lo mantiene el
// equipo vía seeder, no hay pantalla de admin para editarlo (a diferencia de "Sectores").
class TemasEspecialidadController extends BaseController
{
    public function index(): ResponseInterface
    {
        $filas = db_connect()->table('temas_especialidad')
            ->where('activo', 1)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map(static fn (array $f) => [
            'id'          => (string) $f['id'],
            'nombre'      => $f['nombre'],
            'codigo'      => $f['codigo'],
            'icono'       => $f['icono'],
            'colorAccent' => $f['color_accent'],
            'descripcion' => $f['descripcion'],
            'activo'      => (bool) $f['activo'],
        ], $filas));
    }
}
