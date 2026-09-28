<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Completa la preparación de las secciones "E1. EVALUACIÓN (B-C)" y "E2. COV INTERURBANO" de
// FTE-CARRETERAS — evaluación social por el método Beneficio-Costo (B/C), aplica cuando el IMDA
// con proyecto en el año base es > 200 veh/día (ítem 1.16). Estructura idéntica en Alternativa 1
// (11.01-11.07) y Alternativa 2 (11.08-11.14), generada por bucle salvo los pocos campos con
// tipo real distinto entre alternativas (ver hallazgo abajo).
//
// E1 es la sección MÁS calculada de toda la ficha: el Costo de Operación Vehicular (COV) por tipo
// de vehículo es un parámetro OFICIAL publicado por el MTC (hoja "E2. COV Interurbano", que esta
// misma ficha ya trae) según región/orografía/superficie/estado de la vía — nunca un dato que el
// cliente aporte. Las únicas entradas reales de toda la evaluación B/C son: T.C. (parámetro de la
// metodología COV), el recorrido reducido por tráfico desviado, y los 4 factores de conversión a
// precios sociales (mismos que en D1).
//
// HALLAZGO DE ESTRUCTURA:
//  1. "Longitud Total proyecto" y "Horizonte de evaluación" son tipo=calculado en Alternativa 1
//     (11.01.01/11.01.02) pero tipo=decimal en Alternativa 2 (11.08.01/11.08.02) — inconsistencia
//     de tipo entre alternativas idénticas, no se corrige (requeriría verificar contra el .xlsm
//     real). Se documenta cada uno según su tipo real.
//  2. La tabla "Costos Incrementales" y el campo calculado "Actualizado - Tasa" compartían el mismo
//     identificador (11.07.01/11.14.01) — corregido por
//     CorregirDuplicadosCostosIncrementalesFTECarreterasSeeder (pedido explícito del usuario,
//     2026-09-28): "Actualizado - Tasa" pasó a 11.07.02/11.14.02 y los campos siguientes
//     (Actualizado Inversión/Mantenimiento/Beneficios, VAN, B/C, TIR) se corrieron un lugar cada
//     uno. Este archivo ya refleja los identificadores corregidos.
//
// Idempotente. Uso: php spark db:seed PrepararIAFTECarreterasE1E2Seeder
class PrepararIAFTECarreterasE1E2Seeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

    private const DESC_CALCULADO_COV = 'Tabla enteramente calculada — Costo de Operación Vehicular '
        . '(COV) por tipo de vehículo, tomado de la sección E2 (COV Interurbano) según la región, '
        . 'orografía, superficie y estado de la vía. No propongas valores para ninguna de sus '
        . 'columnas: el COV es un parámetro oficial del MTC, no un dato del proyecto.';

    private const DESC_CALCULADO_SITUACION = 'Tabla enteramente calculada — clasifica la vía por '
        . 'región, orografía, tipo de superficie y estado, tomada de otras secciones ya '
        . 'registradas (ubicación, diagnóstico de la UP). No propongas valores para ninguna de sus '
        . 'columnas.';

    private const DESC_CALCULADO_BENEFICIOS = 'Tabla enteramente calculada — beneficios por ahorro '
        . 'de Costo de Operación Vehicular, resultado de multiplicar el tráfico proyectado (sección '
        . 'A2) por la diferencia de COV sin/con proyecto. No propongas valores para ninguna de sus '
        . 'columnas.';

    private const DESC_CALCULADO_COSTOS_MERCADO = 'Tabla enteramente calculada — costos de '
        . 'inversión y mantenimiento a precios de mercado, tomados de la sección C (Costos) de '
        . 'esta alternativa. No propongas valores para ninguna de sus columnas.';

    private const DESC_CALCULADO_COSTOS_INCREMENTALES = 'Tabla enteramente calculada — costos '
        . 'incrementales (inversión + mantenimiento) y beneficios incrementales, cuyo flujo neto '
        . 'alimenta el VAN, B/C y TIR de esta alternativa. No propongas valores para ninguna de sus '
        . 'columnas.';

    /** Pares [Alternativa 1, Alternativa 2] de identificadores con el MISMO tipo real. */
    private const PARES_TABLAS = [
        ['11.01.04', '11.08.04', self::DESC_CALCULADO_SITUACION],
        ['11.01.05', '11.08.05', self::DESC_CALCULADO_COV],
        ['11.02.02', '11.09.02', self::DESC_CALCULADO_SITUACION],
        ['11.02.03', '11.09.03', self::DESC_CALCULADO_COV],
        ['11.03.01', '11.10.01', self::DESC_CALCULADO_BENEFICIOS],
        ['11.03.02', '11.10.02', self::DESC_CALCULADO_BENEFICIOS],
        ['11.03.03', '11.10.03', self::DESC_CALCULADO_BENEFICIOS],
        ['11.05.01', '11.12.01', self::DESC_CALCULADO_COSTOS_MERCADO],
        ['11.06.01', '11.13.01', self::DESC_CALCULADO_COSTOS_MERCADO],
        ['11.07.01', '11.14.01', self::DESC_CALCULADO_COSTOS_INCREMENTALES],
    ];

    private const PARES_FACTORES = [
        ['11.04.01', '11.11.01', 'Tasa Social de Descuento (TSD) — valor oficial vigente según el '
            . 'Anexo N.° 11 (Parámetros de Evaluación Social) de la Directiva General del SNPMGI: '
            . '0.08 (8%). No cambies este valor salvo que el MEF haya publicado una tasa distinta '
            . 'vigente.'],
        ['11.04.02', '11.11.02', 'Factor de conversión a precios sociales para costos de '
            . 'INVERSIÓN, según el Anexo N.° 11 del SNPMGI — valor oficial vigente (referencialmente '
            . '0.79). No lo inventes ni lo calcules.'],
        ['11.04.03', '11.11.03', 'Porcentaje de valor residual según el tipo de pavimento de esta '
            . 'alternativa (Tabla 8 del instructivo): 35% concreto, 25% asfalto, 10% estabilizado/'
            . 'afirmado — coherente con el tipo de pavimento de la sección P1 (ítem 4.01.06).'],
        ['11.04.04', '11.11.04', 'Factor de conversión a precios sociales para costos de '
            . 'MANTENIMIENTO Y OPERACIÓN, según el Anexo N.° 11 del SNPMGI — valor oficial vigente '
            . '(referencialmente 0.75). No lo inventes ni lo calcules.'],
    ];

    private const PARES_RESULTADOS = [
        ['11.07.02', '11.14.02', 'Costos incrementales de esta alternativa actualizados a la Tasa '
            . 'Social de Descuento (traídos a valor presente) — se autocalcula a partir de la tabla '
            . 'anterior (11.07.01/11.14.01). No lo llenes manualmente.'],
        ['11.07.03', '11.14.03', 'Costo de inversión actualizado (traído a valor presente con la '
            . 'Tasa Social de Descuento) — se autocalcula. No lo llenes manualmente.'],
        ['11.07.04', '11.14.04', 'Costo de mantenimiento actualizado — se autocalcula. No lo llenes '
            . 'manualmente.'],
        ['11.07.05', '11.14.05', 'Beneficios actualizados — se autocalcula. No lo llenes '
            . 'manualmente.'],
        ['11.07.06', '11.14.06', 'Valor Actual Neto Social (VANS) de esta alternativa — se '
            . 'autocalcula como beneficios actualizados menos costos actualizados. No lo llenes '
            . 'manualmente.'],
        ['11.07.07', '11.14.07', 'Ratio Beneficio-Costo (B/C) de esta alternativa — se autocalcula. '
            . 'Un B/C mayor a 1 indica que el proyecto es socialmente rentable. No lo llenes '
            . 'manualmente.'],
        ['11.07.08', '11.14.08', 'Tasa Interna de Retorno Social (TIRS) de esta alternativa — se '
            . 'autocalcula. No lo llenes manualmente.'],
    ];

    private const DESCRIPCIONES_INDIVIDUALES = [
        // Longitud Total / Horizonte: calculado en Alt.1, decimal en Alt.2 (ver hallazgo).
        '11.01.01' => 'Se autocompleta con la longitud total del proyecto (ítem 1.05.03) — no lo '
            . 'llenes manualmente, es un campo calculado.',
        '11.01.02' => 'Se autocompleta con el horizonte de evaluación del proyecto (ítem 1.12) — no '
            . 'lo llenes manualmente, es un campo calculado.',
        '11.08.01' => 'Longitud total del proyecto en kilómetros — debe coincidir con la registrada '
            . 'en el diagnóstico de la UP (ítem 1.05.03).',
        '11.08.02' => 'Horizonte de evaluación en años — debe coincidir con el registrado en el '
            . 'ítem 1.12.',

        '11.01.03' => 'Parámetro "T.C." de la metodología de Costo de Operación Vehicular (COV) — '
            . 'revisa el manual de la metodología COV vigente del MTC para el valor exacto que '
            . 'corresponde a esta alternativa; no lo inventes ni asumas un valor por defecto.',
        '11.08.03' => 'Parámetro "T.C." de la metodología de Costo de Operación Vehicular (COV) — '
            . 'revisa el manual de la metodología COV vigente del MTC para el valor exacto que '
            . 'corresponde a esta alternativa; no lo inventes ni asumas un valor por defecto.',

        '11.02.01' => 'Recorrido (Km) que se reduce por el tráfico que se desvía hacia esta vía una '
            . 'vez mejorada — debe ser coherente con "¿Existe vía alterna?" y el tráfico desviado '
            . 'ya estimado en la sección A2 (ítem 3.05.05). Déjalo en 0 si no existe vía alterna.',
        '11.09.01' => 'Recorrido (Km) que se reduce por el tráfico que se desvía hacia esta vía una '
            . 'vez mejorada — debe ser coherente con "¿Existe vía alterna?" y el tráfico desviado '
            . 'ya estimado en la sección A2 (ítem 3.05.05). Déjalo en 0 si no existe vía alterna.',
    ];

    private const DESCRIPCION_E2 = 'Tabla de referencia OFICIAL y enteramente calculada — Costo de '
        . 'Operación Vehicular (COV) por tipo de vehículo, según región natural (Costa/Sierra/'
        . 'Selva), orografía, tipo de superficie y estado de la vía, tal como la publica el MTC. '
        . 'Nunca propongas ni modifiques valores en esta tabla — es la fuente de la que se leen '
        . 'automáticamente los COV de la sección E1 (Evaluación B-C).';

    public function run(): void
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null || empty($plantilla['asignado_archivo_id'])) {
            echo 'No existe ' . self::CODIGO_PLANTILLA . ' con archivo asignado — nada que preparar.' . PHP_EOL;

            return;
        }

        $archivo = $this->db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        if ($archivo === null || empty($archivo['contenido_json'])) {
            echo 'La plantilla no tiene contenido_json — nada que preparar.' . PHP_EOL;

            return;
        }

        $descripciones = self::DESCRIPCIONES_INDIVIDUALES + ['12.01.01' => self::DESCRIPCION_E2];
        foreach ([self::PARES_TABLAS, self::PARES_FACTORES, self::PARES_RESULTADOS] as $grupo) {
            foreach ($grupo as [$id1, $id2, $desc]) {
                $descripciones[$id1] = $desc;
                $descripciones[$id2] = $desc;
            }
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
                    $nueva = $descripciones[$id] ?? null;
                    if ($nueva !== null && trim((string) ($campo['descripcion'] ?? '')) === '') {
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
            echo 'Ya estaba todo preparado — nada que escribir.' . PHP_EOL;

            return;
        }

        $this->db->table('archivos')->where('id', $archivo['id'])->update([
            'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
        ]);

        echo sprintf('FTE-CARRETERAS preparada (E1/E2): %d descripciones.' . PHP_EOL, $c);
    }
}
