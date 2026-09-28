<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Corrige la `descripcion` de los 4 campos calculados que, tras renumerar los identificadores
// duplicados (ver CorregirDuplicadosCostosIncrementalesFTECarreterasSeeder), heredaron el texto
// pensado para la TABLA "Costos Incrementales" con la que antes compartían identificador — ya no
// aplica, cada uno tiene su propio identificador único y necesita su propia descripción.
//
// A diferencia de los demás seeders de este grupo (que solo escriben si `descripcion` está vacío),
// este SOBRESCRIBE a propósito estos 4 campos puntuales — ya tienen texto, pero es el texto
// equivocado.
//
// Uso: php spark db:seed CorregirDescripcionesCostosIncrementalesFTECarreterasSeeder
class CorregirDescripcionesCostosIncrementalesFTECarreterasSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

    private const DESCRIPCIONES = [
        '9.04.02' => 'Valor Actual de los Costos Sociales (VACS) de esta alternativa — se '
            . 'autocalcula descontando los costos incrementales de la tabla anterior (9.04.01) a '
            . 'la Tasa Social de Descuento. No lo llenes manualmente.',
        '9.08.02' => 'Valor Actual de los Costos Sociales (VACS) de esta alternativa — se '
            . 'autocalcula descontando los costos incrementales de la tabla anterior (9.08.01) a '
            . 'la Tasa Social de Descuento. No lo llenes manualmente.',
        '11.07.02' => 'Costos incrementales de esta alternativa actualizados a la Tasa Social de '
            . 'Descuento (traídos a valor presente) — se autocalcula a partir de la tabla anterior '
            . '(11.07.01). No lo llenes manualmente.',
        '11.14.02' => 'Costos incrementales de esta alternativa actualizados a la Tasa Social de '
            . 'Descuento (traídos a valor presente) — se autocalcula a partir de la tabla anterior '
            . '(11.14.01). No lo llenes manualmente.',
    ];

    public function run(): void
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null || empty($plantilla['asignado_archivo_id'])) {
            echo 'No existe ' . self::CODIGO_PLANTILLA . ' con archivo asignado — nada que corregir.' . PHP_EOL;

            return;
        }

        $archivo = $this->db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        if ($archivo === null || empty($archivo['contenido_json'])) {
            echo 'La plantilla no tiene contenido_json — nada que corregir.' . PHP_EOL;

            return;
        }

        $contenido = json_decode((string) $archivo['contenido_json'], true);
        $c = 0;

        $contenido['secciones'] ??= [];
        foreach ($contenido['secciones'] as &$seccion) {
            $seccion['subsecciones'] ??= [];
            foreach ($seccion['subsecciones'] as &$sub) {
                $sub['campos'] ??= [];
                foreach ($sub['campos'] as &$campo) {
                    $id = (string) ($campo['identificador'] ?? '');
                    $nueva = self::DESCRIPCIONES[$id] ?? null;
                    // Sobrescribe SOLO si el campo es el calculado (no la tabla) — ambos ya
                    // deberían tener identificadores distintos, pero se valida el tipo por
                    // seguridad antes de tocar nada.
                    if ($nueva !== null && ($campo['tipo'] ?? '') === 'calculado' && ($campo['descripcion'] ?? '') !== $nueva) {
                        $campo['descripcion'] = $nueva;
                        $c++;
                    }
                }
                unset($campo);
            }
            unset($sub);
        }
        unset($seccion);

        if ($c === 0) {
            echo 'Ya estaba corregido — nada que escribir.' . PHP_EOL;

            return;
        }

        $this->db->table('archivos')->where('id', $archivo['id'])->update([
            'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
        ]);

        echo sprintf('FTE-CARRETERAS: %d descripción(es) corregida(s).' . PHP_EOL, $c);
    }
}
