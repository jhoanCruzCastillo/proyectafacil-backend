<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Catálogo de "temas de especialidad" / temas de asesoría ILPIIE (Liquidación Financiera,
// Expedientes Técnicos..., ver TemasEspecialidadSeeder) — SEPARADO de SectoresController (los
// 13 sectores MEF de las fichas/plantillas). Solo lectura por ahora: el catálogo lo mantiene el
// equipo vía seeder, no hay pantalla de admin para editarlo (a diferencia de "Sectores").
class TemasEspecialidadController extends BaseController
{
    public function index(): ResponseInterface
    {
        return $this->response->setJSON($this->temasPlanos());
    }

    // Público (sin sesión): catálogo anidado tema → subtemas para el paso 2 del registro.
    // Misma info no sensible que index()+subtemas, empaquetada para no hacer 2 requests anónimos.
    public function publico(): ResponseInterface
    {
        $db = db_connect();
        $temas = $db->table('temas_especialidad')
            ->where('activo', 1)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $subtemas = $db->table('subtemas_especialidad')
            ->select('id, tema_id, nombre')
            ->where('activo', 1)
            ->orderBy('tema_id', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $porTema = [];
        foreach ($subtemas as $s) {
            $porTema[(int) $s['tema_id']][] = [
                'id'     => (string) $s['id'],
                'nombre' => $s['nombre'],
            ];
        }

        return $this->response->setJSON(array_map(static fn (array $f) => [
            'id'          => (string) $f['id'],
            'nombre'      => $f['nombre'],
            'codigo'      => $f['codigo'],
            'icono'       => $f['icono'],
            'colorAccent' => $f['color_accent'],
            'descripcion' => $f['descripcion'],
            'subtemas'    => $porTema[(int) $f['id']] ?? [],
        ], $temas));
    }

    /** @return list<array{id: string, nombre: string, codigo: string, icono: string, colorAccent: string, descripcion: ?string, activo: bool}> */
    private function temasPlanos(): array
    {
        $filas = db_connect()->table('temas_especialidad')
            ->where('activo', 1)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return array_map(static fn (array $f) => [
            'id'          => (string) $f['id'],
            'nombre'      => $f['nombre'],
            'codigo'      => $f['codigo'],
            'icono'       => $f['icono'],
            'colorAccent' => $f['color_accent'],
            'descripcion' => $f['descripcion'],
            'activo'      => (bool) $f['activo'],
        ], $filas);
    }
}
