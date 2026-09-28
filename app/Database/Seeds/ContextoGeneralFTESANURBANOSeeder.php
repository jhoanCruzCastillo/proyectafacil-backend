<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Contexto general (pilar 2 del tab "Contexto IA") de la FTE de los servicios de saneamiento en el
// ámbito urbano, destilado del instructivo del MVCS (58 páginas): qué formula la ficha, los cuatro
// servicios/sistemas que abarca, la cadena funcional fija, las cuatro naturalezas de intervención y
// qué brecha cierra cada una, la lista CERRADA de cinco indicadores con su aritmética de aporte
// (contribución <= déficit), el alcance territorial de "una UP por servicio", los 20 años de
// funcionamiento, dónde está la evidencia por tipo de dato y los anexos que la ficha exige.
//
// Por qué destilado y no el PDF entero: el instructivo son ~149.000 caracteres (~37.000 tokens) y se
// reenviaría en CADA llamada del llenado — esta ficha tiene 18 secciones y 106 tablas.
//
// NO contiene instrucciones campo por campo: eso vive en las guías por sección
// (ContextosIAFTESANURBANOSeeder) y en la "Descripción / ayuda" de cada campo
// (PrepararIAFTESANURBANOSeeder).
//
// Se guarda como una fila de `contextos_ia_general` con el nombre exacto que busca el panel del
// editor (`NOMBRE_CONTEXTO_GENERAL` en frontend/src/features/editor/contextosIaNombres.ts). El
// markdown se sube a Cloudinary y en la BD solo queda la URL.
//
// Idempotente: actualiza la fila si ya existe.
//
// Uso: php spark db:seed ContextoGeneralFTESANURBANOSeeder
class ContextoGeneralFTESANURBANOSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-SAN-URBANO';

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

        $markdown = (string) file_get_contents(__DIR__ . '/content/fte-san-urbano-contexto-general.md');
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
