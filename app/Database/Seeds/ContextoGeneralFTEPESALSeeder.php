<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Contexto general (pilar 2 del tab "Contexto IA") de la FTE de establecimientos de salud de 12 horas
// con rol Puerta de Entrada, destilado del instructivo de la OPMI Salud (MINSA, 119 páginas): qué es
// la ficha, el orden real de llenado (A -> G -> H/I -> J -> B..F, que NO es el orden visual), las
// cuatro naturalezas admitidas, los valores que el sector deja predeterminados en la Sección C, las
// siglas y las definiciones que cambian el llenado (GDU, PMF, PA, PE).
//
// NO contiene instrucciones campo por campo: eso vive en las guías por sección
// (ContextosIAFTEPESALSeeder) y en la "Descripción / ayuda" de cada campo (PrepararIAFTEPESALSeeder).
//
// Se guarda como una fila de `contextos_ia_general` con el nombre exacto que busca el panel del
// editor (`NOMBRE_CONTEXTO_GENERAL` en frontend/src/features/editor/contextosIaNombres.ts). El
// markdown se sube a Cloudinary y en la BD solo queda la URL.
//
// Idempotente: actualiza la fila si ya existe.
//
// Uso: php spark db:seed ContextoGeneralFTEPESALSeeder
class ContextoGeneralFTEPESALSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-PE-SAL';

    /** Debe coincidir con NOMBRE_CONTEXTO_GENERAL del frontend. */
    private const NOMBRE = 'Contexto general';

    public function run(): void
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null) {
            echo 'No existe la plantilla ' . self::CODIGO_PLANTILLA . " — corre PlantillasSeeder primero.\n";

            return;
        }
        $plantillaId = (int) $plantilla['id'];

        $markdown = (string) file_get_contents(__DIR__ . '/content/fte-pe-sal-contexto-general.md');
        $url      = (new CloudinaryUploader())->subirMarkdown($markdown, "general-{$plantillaId}-contexto-general.md");
        $ahora    = date('Y-m-d H:i:s');

        $existente = $this->db->table('contextos_ia_general')
            ->where('plantilla_id', $plantillaId)
            ->where('nombre', self::NOMBRE)
            ->get()->getRowArray();

        if ($existente === null) {
            $this->db->table('contextos_ia_general')->insert([
                'plantilla_id' => $plantillaId,
                'nombre'       => self::NOMBRE,
                'url'          => $url,
                'created_at'   => $ahora,
                'updated_at'   => $ahora,
            ]);
            echo "Contexto general creado para " . self::CODIGO_PLANTILLA . ".\n";
        } else {
            $this->db->table('contextos_ia_general')->where('id', $existente['id'])
                ->update(['url' => $url, 'updated_at' => $ahora]);
            echo "Contexto general actualizado para " . self::CODIGO_PLANTILLA . ".\n";
        }
    }
}
