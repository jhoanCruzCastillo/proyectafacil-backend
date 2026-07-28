<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Espejo de frontend/src/data/sectores.ts, extraído a data/sectores.json (vía tsx, ver
// scripts/extract usado en sesión) — catálogo oficial de 13 sectores del demo.
class SectoresSeeder extends Seeder
{
    public function run(): void
    {
        $sectores = json_decode(file_get_contents(__DIR__ . '/data/sectores.json'), true);

        foreach ($sectores as $s) {
            $this->db->table('sectores')->ignore(true)->insert([
                'codigo'       => $s['codigo'],
                'nombre'       => $s['nombre'],
                'icono'        => $s['icono'],
                'color_accent' => $s['colorAccent'],
                'descripcion'  => $s['descripcion'] ?? null,
                'tipo_sector'  => $s['tipoSector'],
                'activo'       => $s['activo'] ? 1 : 0,
            ]);
        }
    }
}
