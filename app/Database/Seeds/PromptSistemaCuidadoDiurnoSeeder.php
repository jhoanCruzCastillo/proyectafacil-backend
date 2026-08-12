<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Prompt del sistema (pilar 1 del tab "Contexto IA") para la FTE de Servicios de Cuidado Diurno.
// Define el COMPORTAMIENTO de la IA en el llenado asistido: rol, orden de entradas, reglas de
// oro, estados/evidencia y formato de salida. No incluye glosario de la ficha (contexto general)
// ni instrucciones campo a campo (guías por sección).
//
// Se guarda como fila de `contextos_ia_general` con el nombre fijo `Prompt del sistema`
// (NOMBRE_PROMPT_SISTEMA en el frontend). El markdown va a Cloudinary; en BD solo la URL.
//
// Idempotente. Uso: php spark db:seed PromptSistemaCuidadoDiurnoSeeder
class PromptSistemaCuidadoDiurnoSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CUIDADO-DIURNO';

    /** Debe coincidir con NOMBRE_PROMPT_SISTEMA del frontend. */
    private const NOMBRE = 'Prompt del sistema';

    public function run(): void
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null) {
            echo 'No existe la plantilla ' . self::CODIGO_PLANTILLA . " — corre PlantillasSeeder primero.\n";

            return;
        }
        $plantillaId = (int) $plantilla['id'];

        $markdown = (string) file_get_contents(__DIR__ . '/content/fte-cuidado-diurno-prompt-sistema.md');
        $url      = (new CloudinaryUploader())->subirMarkdown($markdown, "general-{$plantillaId}-prompt-sistema.md");
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
            echo "Prompt del sistema creado para " . self::CODIGO_PLANTILLA . ".\n";
        } else {
            $this->db->table('contextos_ia_general')->where('id', $existente['id'])
                ->update(['url' => $url, 'updated_at' => $ahora]);
            echo "Prompt del sistema actualizado para " . self::CODIGO_PLANTILLA . ".\n";
        }
    }
}
