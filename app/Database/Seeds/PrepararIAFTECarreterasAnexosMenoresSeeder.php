<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Completa la preparación de 4 secciones pequeñas de FTE-CARRETERAS que anteceden a la evaluación
// económica: "P1. EJES EQUIVALENTES", "A3. FACTOR DE CORRECCIÓN", "A4. TASA DE CRECIMIENTO" y "B.
// SITUACIÓN DE LA UNIDAD PRODUCTORA". Las tres primeras son casi enteramente CALCULADAS — A3/A4 son
// tablas de referencia oficial (factores de corrección por peaje, tasas de crecimiento por
// departamento) que trae el propio aplicativo Excel, no algo que el cliente o la IA completen; P1
// deriva ejes equivalentes del tráfico ya proyectado en A2. Solo "B" tiene una tabla con columnas
// realmente editables (comparación sin/con proyecto).
//
// Idempotente. Uso: php spark db:seed PrepararIAFTECarreterasAnexosMenoresSeeder
class PrepararIAFTECarreterasAnexosMenoresSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

    private const DESCRIPCIONES = [
        // --- P1. EJES EQUIVALENTES ---------------------------------------------------------------
        '4.01.01' => 'Número de calzadas de la vía: 1 para calzada única, 2 si el proyecto '
            . 'construye una segunda calzada (naturaleza Ampliación, ver ítem 1.01.01).',
        '4.01.02' => 'Número de sentidos de circulación de la vía (1 o 2).',
        '4.01.03' => 'Número de carriles por sentido de circulación.',
        '4.01.04' => 'Factor Direccional (FD) — se autocompleta según el número de sentidos '
            . 'registrado; no lo llenes manualmente, es un campo calculado.',
        '4.01.05' => 'Factor de Carril (FC) — se autocompleta según el número de carriles por '
            . 'sentido registrado; no lo llenes manualmente, es un campo calculado.',
        '4.01.06' => 'Tipo de pavimento de la alternativa técnica (ej. afirmado, estabilizado, '
            . 'pavimento asfáltico, pavimento de concreto hidráulico) — determina la vida útil y el '
            . 'horizonte de evaluación del proyecto (ítem 1.12).',
        '4.02.01' => 'Tabla enteramente calculada — tráfico total proyectado a 20 años por tipo de '
            . 'vehículo, tomado de la sección A2 (Demanda). No propongas valores para ninguna de sus '
            . 'columnas.',
        '4.03.01' => 'Tabla enteramente calculada — ejes equivalentes por tipo de vehículo, '
            . 'derivados del tráfico total (4.02.01) y los factores FC/FD/presión de neumático. No '
            . 'propongas valores para ninguna de sus columnas.',
        '4.03.02' => 'Tabla enteramente calculada — total de ejes equivalentes acumulados. No '
            . 'propongas valores para ninguna de sus columnas.',

        // --- A3. FACTOR DE CORRECCIÓN -------------------------------------------------------------
        '5.01.01' => 'Tabla de referencia OFICIAL y enteramente calculada (factores de corrección '
            . 'mensuales de vehículos ligeros por unidad de peaje, promedio 2010-2020, ya '
            . 'incorporados por el propio aplicativo de la FTE) — nunca propongas ni modifiques '
            . 'valores en esta tabla.',
        '5.01.02' => 'Tabla de referencia OFICIAL y enteramente calculada (factores de corrección '
            . 'mensuales de vehículos pesados por unidad de peaje, promedio 2010-2020, ya '
            . 'incorporados por el propio aplicativo de la FTE) — nunca propongas ni modifiques '
            . 'valores en esta tabla.',

        // --- A4. TASA DE CRECIMIENTO --------------------------------------------------------------
        '6.01.01' => 'Tabla de referencia OFICIAL y enteramente calculada (tasa de crecimiento '
            . 'anual de vehículos ligeros por departamento, propuesta por la propia FTE del Sector '
            . 'Transportes) — nunca propongas ni modifiques valores en esta tabla.',
        '6.01.02' => 'Tabla de referencia OFICIAL y enteramente calculada (tasa de crecimiento '
            . 'anual de vehículos pesados por departamento, ligada al crecimiento del PBI) — nunca '
            . 'propongas ni modifiques valores en esta tabla.',

        // --- B. SITUACIÓN DE LA UNIDAD PRODUCTORA -------------------------------------------------
        '7.01.01' => 'Descripción del estado actual de la vía (calzada, obras de arte, drenaje, '
            . 'señalización) según el diagnóstico de campo — mismo criterio que el diagnóstico de '
            . 'la UP (ítem 1.05), en mayor detalle técnico.',
        '7.01.02' => 'Velocidad de diseño de la vía (km/h), según el Manual de Carreteras: Diseño '
            . 'Geométrico DG-2018, acorde a la clasificación y orografía de la vía.',
        '7.02.01' => 'Comparación de características técnicas de la vía SIN proyecto (situación '
            . 'actual, diagnóstico) y CON proyecto (situación de diseño de la alternativa '
            . 'seleccionada) — cada fila es una característica distinta (ej. ancho de calzada, tipo '
            . 'de superficie, estado de conservación); completa ambas columnas con la evidencia real '
            . 'del proyecto, nunca copies el mismo valor en ambas si el proyecto sí implica un '
            . 'cambio.',
    ];

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

        echo sprintf(
            'FTE-CARRETERAS preparada (P1/A3/A4/B): %d descripciones.' . PHP_EOL,
            $c
        );
    }
}
