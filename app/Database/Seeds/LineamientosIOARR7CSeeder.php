<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Contexto general (pilar 2 del tab "Contexto IA") del Formato N.o 07-C, destilado de los
// Lineamientos para la identificacion y registro de las IOARR (MEF, 77 paginas): que es una IOARR,
// los cuatro tipos y sus variantes, los limites que la convierten en Proyecto de Inversion (el 20 %
// sobre la capacidad de diseno, el ano de inoperatividad), fraccionamiento y duplicacion, y el
// umbral de 75 UIT.
//
// A diferencia de las FTE, aca NO se envia la "guia general" de fichas tecnicas: este formato no
// formula ni evalua alternativas, solo registra. Por eso la fila se llama "Lineamientos IOARR".
//
// NO contiene instrucciones campo por campo: eso vive en la guia de seccion
// (ContextosIAIOARR7CSeeder) y en la "Descripcion / ayuda" de cada campo (PrepararIAIOARR7CSeeder).
//
// Se guarda como una fila de `contextos_ia_general` con el nombre exacto que busca el panel del
// editor (`NOMBRE_CONTEXTO_GENERAL` en frontend/src/features/editor/contextosIaNombres.ts). El
// markdown se sube a Cloudinary y en la BD solo queda la URL.
//
// Idempotente: actualiza la fila si ya existe.
//
// Uso: php spark db:seed LineamientosIOARR7CSeeder
class LineamientosIOARR7CSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = '7C';

    /** NO es 'Contexto general': este formato no lleva la guia general de las FTE. Cualquier nombre
     *  distinto del reservado 'Prompt del sistema' se envia igual como contexto general — ver
     *  LlenadoIAController::contextosGeneralesDe(), que solo excluye ese. */
    private const NOMBRE = 'Lineamientos IOARR';

    public function run(): void
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null) {
            echo 'No existe la plantilla ' . self::CODIGO_PLANTILLA . " — corre PlantillasSeeder primero.\n";

            return;
        }
        $plantillaId = (int) $plantilla['id'];

        $markdown = (string) file_get_contents(__DIR__ . '/content/ioarr-7c-lineamientos.md');
        $url      = (new CloudinaryUploader())->subirMarkdown($markdown, "general-{$plantillaId}-lineamientos-ioarr.md");
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
            echo "Lineamientos IOARR creados para " . self::CODIGO_PLANTILLA . ".\n";
        } else {
            $this->db->table('contextos_ia_general')->where('id', $existente['id'])
                ->update(['url' => $url, 'updated_at' => $ahora]);
            echo "Lineamientos IOARR actualizados para " . self::CODIGO_PLANTILLA . ".\n";
        }
    }
}
