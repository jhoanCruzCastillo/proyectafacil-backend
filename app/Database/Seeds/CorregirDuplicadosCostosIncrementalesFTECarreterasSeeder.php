<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Corrige un defecto real de estructura de FTE-CARRETERAS (encontrado al preparar las
// descripciones de IA — ver comentarios de cabecera de PrepararIAFTECarreterasD1D2Seeder y
// PrepararIAFTECarreterasE1E2Seeder): en las 4 subsecciones "D) Costos Incrementales" (Alternativa
// 1 y 2, tanto en D1. EVALUACIÓN (C-E) como en E1. EVALUACIÓN (B-C)), la tabla "Costos
// Incrementales" y el PRIMER campo calculado que le sigue comparten el mismo identificador — dos
// objetos de campo distintos (id interno UUID distinto) con el mismo identificador de texto.
//
// Pedido explícito del usuario (2026-09-28): no deben repetirse los identificadores — el segundo
// repetido pasa al siguiente número, y todos los campos que le siguen en esa misma subsección se
// corren un lugar para mantener el orden secuencial. Ejemplo: en "Alternativa Nº1 - D) Costos
// Incrementales" (código 9.04), la tabla se queda en 9.04.01, "Valor Actual de los Costos (VAC)"
// pasa de 9.04.01 a 9.04.02, "Ratio C-E" pasa de 9.04.02 a 9.04.03, y así sucesivamente.
//
// Implementación: renumera TODOS los campos de cada una de las 4 subsecciones afectadas de forma
// puramente secuencial según su orden real en el array `campos` (que ya refleja el orden de
// lectura correcto: tabla, luego cada campo calculado en cascada) — no se reordenan campos, solo
// se corrige el identificador de texto para que sea único y consecutivo.
//
// Idempotente: si ya no hay duplicados en una subsección, no la toca.
// Uso: php spark db:seed CorregirDuplicadosCostosIncrementalesFTECarreterasSeeder
class CorregirDuplicadosCostosIncrementalesFTECarreterasSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

    /** Nombres exactos de las 4 subsecciones afectadas (ver hallazgo en la cabecera). */
    private const SUBSECCIONES_AFECTADAS = [
        'Alternativa Nº1 - D) Costos Incrementales',
        'Alternativa Nº2 - D) Costos Incrementales',
        'Alternativa 1 - D) Costos Incrementales',
        'Alternativa 2 - D) Costos Incrementales',
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
        $renumerados = 0;
        $subseccionesTocadas = 0;

        $contenido['secciones'] ??= [];
        foreach ($contenido['secciones'] as &$seccion) {
            $seccion['subsecciones'] ??= [];
            foreach ($seccion['subsecciones'] as &$sub) {
                if (! in_array($sub['nombre'] ?? '', self::SUBSECCIONES_AFECTADAS, true)) {
                    continue;
                }
                $sub['campos'] ??= [];
                $identificadores = array_column($sub['campos'], 'identificador');
                if (count($identificadores) === count(array_unique($identificadores))) {
                    continue; // ya está corregida — idempotencia.
                }

                $prefijo = (string) ($sub['codigo'] ?? '');
                if ($prefijo === '') {
                    echo "  · Subsección «{$sub['nombre']}» no tiene código — se omite por seguridad.\n";

                    continue;
                }

                foreach ($sub['campos'] as $i => &$campo) {
                    $nuevoId = sprintf('%s.%02d', $prefijo, $i + 1);
                    if (($campo['identificador'] ?? '') !== $nuevoId) {
                        $campo['identificador'] = $nuevoId;
                        $renumerados++;
                    }
                }
                unset($campo);
                $subseccionesTocadas++;
            }
            unset($sub);
        }
        unset($seccion);

        if ($renumerados === 0) {
            echo 'Ya estaba corregido — nada que escribir.' . PHP_EOL;

            return;
        }

        $this->db->table('archivos')->where('id', $archivo['id'])->update([
            'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
        ]);

        echo sprintf(
            'FTE-CARRETERAS corregida: %d identificador(es) renumerado(s) en %d subsección(es).' . PHP_EOL,
            $renumerados,
            $subseccionesTocadas
        );
    }
}
