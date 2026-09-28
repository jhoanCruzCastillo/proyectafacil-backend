<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Prompt del sistema (pilar 1 del tab "Contexto IA") para la FTE de los servicios de saneamiento en
// el ámbito urbano (MVCS). Define el COMPORTAMIENTO de la IA en el llenado asistido: rol, orden de
// entradas, reglas de oro, y qué hacer ante evidencia ausente o contradictoria.
//
// Parte del prompt de Salud, que ya estaba probado, y le cambia lo sectorial (entidades y fuentes:
// EPS/ATM/SUNASS/ANA en vez de DIRESA/RENIPRESS) más una sección 12 con las reglas propias de esta
// ficha: la cadena funcional FIJA (18/040/0088), los cinco indicadores de brecha cerrados, el tope
// "contribución <= déficit", la fórmula del nombre y los 20 años de fase de funcionamiento.
//
// Se guarda como una fila de `contextos_ia_general` con el nombre reservado 'Prompt del sistema'
// (contextosGeneralesDe() lo excluye del bloque de generales justamente porque va aparte, como
// prompt). El markdown se sube a Cloudinary y en la BD solo queda la URL.
//
// Idempotente: actualiza la fila si ya existe.
//
// Uso: php spark db:seed PromptSistemaFTESANURBANOSeeder
class PromptSistemaFTESANURBANOSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-SAN-URBANO';

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

        $markdown = (string) file_get_contents(__DIR__ . '/content/fte-san-urbano-prompt-sistema.md');
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
