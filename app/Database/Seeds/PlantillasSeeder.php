<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Espejo de frontend/src/data/plantillas.ts, extraído a data/plantillas.json — catálogo oficial de
// 34 plantillas del demo. Solo metadata (codigo/nombre/sector/instrumento/tipologías); `secciones`
// se sube aparte (script UploadExcelsSeeder-style, ver conversación) porque vive en
// archivos.contenido_json, no en esta tabla. Requiere que SectoresSeeder ya haya corrido.
class PlantillasSeeder extends Seeder
{
    private array $tipologiasValidas = ['optimizacion', 'ampliacion_marginal', 'reposicion', 'rehabilitacion'];

    public function run(): void
    {
        $sectoresJson = json_decode(file_get_contents(__DIR__ . '/data/sectores.json'), true);
        $plantillas   = json_decode(file_get_contents(__DIR__ . '/data/plantillas.json'), true);

        // mock sectorId ('sec-general') -> codigo ('GEN')
        $codigoPorMockId = [];
        foreach ($sectoresJson as $s) {
            $codigoPorMockId[$s['id']] = $s['codigo'];
        }

        // codigo -> id real ya sembrado por SectoresSeeder
        $idPorCodigo = [];
        foreach ($this->db->table('sectores')->select('id, codigo')->get()->getResultArray() as $row) {
            $idPorCodigo[$row['codigo']] = $row['id'];
        }

        foreach ($plantillas as $p) {
            $codigoSector = $codigoPorMockId[$p['sectorId']] ?? null;
            $sectorId     = $codigoSector !== null ? ($idPorCodigo[$codigoSector] ?? null) : null;
            if ($sectorId === null) {
                continue;
            }

            $this->db->table('plantillas')->insert([
                'sector_id'           => $sectorId,
                'codigo'              => $p['codigo'],
                'nombre'              => $p['nombre'],
                'descripcion'         => $p['descripcion'] ?? null,
                'instrumento'         => $p['instrumento'],
                'fecha_actualizacion' => $this->normalizarFecha($p['fechaActualizacion'] ?? null),
                'archivo_default_url' => $p['archivoDefaultUrl'] ?? null,
                'disponible_nivel0'   => ! empty($p['disponibleNivel0']) ? 1 : 0,
            ]);
            $plantillaId = $this->db->insertID();

            foreach (array_intersect($p['tipologiasIoarr'] ?? [], $this->tipologiasValidas) as $tipologia) {
                $this->db->table('plantilla_tipologia_ioarr')->insert([
                    'plantilla_id' => $plantillaId,
                    'tipologia'    => $tipologia,
                ]);
            }
        }
    }

    // frontend usa 'd/m/Y'; la columna es DATE ('Y-m-d').
    private function normalizarFecha(?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return date('Y-m-d');
        }
        $partes = explode('/', $valor);
        if (count($partes) === 3) {
            [$d, $m, $y] = $partes;

            return sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
        }

        return $valor;
    }
}
