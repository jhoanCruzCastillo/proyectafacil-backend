<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Catálogo OFICIAL de "temas de especialidad" / temas de asesoría ILPIIE IVE (Liquidación
// Financiera, Expedientes Técnicos, TDRs...), tal como figura en el Excel del cliente —
// SEPARADO de SectoresSeeder (los 13 sectores MEF que categorizan las fichas/plantillas de
// "Proyectos de Inversión con IA"). Ver SyncTemasIlpiieSeeder si hay que reemplazar un catálogo
// viejo ya sembrado (este seeder usa ignore y no actualiza filas existentes).
class TemasEspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        $temas = json_decode(file_get_contents(__DIR__ . '/data/temas_especialidad.json'), true);

        foreach ($temas as $t) {
            $this->db->table('temas_especialidad')->ignore(true)->insert([
                'codigo'       => $t['codigo'],
                'nombre'       => $t['nombre'],
                'icono'        => $t['icono'],
                'color_accent' => $t['colorAccent'],
                'descripcion'  => $t['descripcion'] ?? null,
                'activo'       => $t['activo'] ? 1 : 0,
            ]);
        }
    }
}
