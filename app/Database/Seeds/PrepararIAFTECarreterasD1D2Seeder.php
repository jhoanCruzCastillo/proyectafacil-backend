<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Completa la preparación de las secciones "D1. EVALUACIÓN (C-E)" y "D2. RATIO C-E" de
// FTE-CARRETERAS — evaluación social por el método Costo-Eficiencia (aplica cuando el IMDA con
// proyecto en el año base es ≤ 200 veh/día, ver ítem 1.16). Estructura idéntica en Alternativa 1
// (9.01-9.04) y Alternativa 2 (9.05-9.08), pareada explícitamente por no compartir el mismo sufijo
// de subsección (offset de 4). D2 es una tabla de referencia oficial (líneas de corte), sin
// entradas editables.
//
// La subsección "D) Costos Incrementales" de cada alternativa tenía originalmente un defecto de
// estructura: la tabla "Costos Incrementales" y el campo calculado "Valor Actual de los Costos
// (VAC)" compartían el mismo identificador (9.04.01/9.08.01). Corregido por
// CorregirDuplicadosCostosIncrementalesFTECarreterasSeeder (pedido explícito del usuario,
// 2026-09-28): el VAC pasó a 9.04.02/9.08.02 y los campos siguientes (Ratio C-E, CAE, VAC/Km) se
// corrieron un lugar cada uno. Este archivo ya refleja los identificadores corregidos.
//
// Idempotente. Uso: php spark db:seed PrepararIAFTECarreterasD1D2Seeder
class PrepararIAFTECarreterasD1D2Seeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

    /** [id Alternativa 1, id Alternativa 2, descripción] — mismo criterio en ambas alternativas. */
    private const PARES = [
        ['9.01.01', '9.05.01', 'Tasa Social de Descuento (TSD) — valor oficial vigente según el '
            . 'Anexo N.° 11 (Parámetros de Evaluación Social) de la Directiva General del SNPMGI: '
            . '0.08 (8%). No cambies este valor salvo que el MEF haya publicado una tasa distinta '
            . 'vigente.'],
        ['9.01.02', '9.05.02', 'Factor de conversión a precios sociales para costos de INVERSIÓN, '
            . 'según el Anexo N.° 11 del SNPMGI — valor oficial vigente (referencialmente 0.79). No '
            . 'lo inventes ni lo calcules; usa el parámetro vigente.'],
        ['9.01.03', '9.05.03', 'Porcentaje de valor residual según el tipo de pavimento de esta '
            . 'alternativa (Tabla 8 del instructivo): 35% para pavimento de concreto, 25% para '
            . 'pavimento de asfalto, 10% para vías estabilizadas o afirmadas. Debe ser coherente '
            . 'con el tipo de pavimento registrado en la sección P1 (Ejes Equivalentes, ítem '
            . '4.01.06).'],
        ['9.01.04', '9.05.04', 'Factor de conversión a precios sociales para costos de '
            . 'MANTENIMIENTO Y OPERACIÓN, según el Anexo N.° 11 del SNPMGI — valor oficial vigente '
            . '(referencialmente 0.75). No lo inventes ni lo calcules.'],
        ['9.02.01', '9.06.01', 'Tabla enteramente calculada — costos de inversión y mantenimiento '
            . 'a precios de MERCADO, tomados de la sección C (Costos) de esta alternativa. No '
            . 'propongas valores para ninguna de sus columnas.'],
        ['9.03.01', '9.07.01', 'Tabla enteramente calculada — aplica los factores de conversión '
            . '(A) a los costos a precios de mercado (B) para obtener los costos a precios '
            . 'SOCIALES. No propongas valores para ninguna de sus columnas.'],
        ['9.04.01', '9.08.01', 'Tabla enteramente calculada — costos incrementales (diferencia '
            . 'entre costos con y sin proyecto, a precios sociales) de esta alternativa, insumo del '
            . 'Valor Actual de Costos y el Ratio Costo-Eficiencia. No propongas valores para '
            . 'ninguna de sus columnas.'],
        ['9.04.02', '9.08.02', 'Valor Actual de los Costos Sociales (VACS) de esta alternativa — se '
            . 'autocalcula descontando los costos incrementales de la tabla anterior a la Tasa '
            . 'Social de Descuento. No lo llenes manualmente.'],
        ['9.04.03', '9.08.03', 'Ratio Costo-Eficiencia de esta alternativa (VACS ÷ kilómetros '
            . 'intervenidos) — se autocalcula. No lo llenes manualmente; compáralo con la línea de '
            . 'corte de la sección D2 solo como referencia de lectura, no como algo que debas '
            . 'escribir tú.'],
        ['9.04.04', '9.08.04', 'Costo Anual Equivalente (CAE) de esta alternativa — se autocalcula. '
            . 'No lo llenes manualmente.'],
        ['9.04.05', '9.08.05', 'Valor Actual de Costos por Kilómetro (VAC/Km) de esta alternativa '
            . '— se autocalcula. Es el valor que se compara contra la línea de corte de la sección '
            . 'D2.'],
    ];

    private const DESCRIPCION_D2 = 'Tabla de referencia OFICIAL y enteramente calculada — líneas '
        . 'de corte de Costo-Eficiencia por kilómetro (a precios sociales), según el rango de IMDA, '
        . 'la solución técnica y la región natural (Costa/Sierra/Selva), tal como las publica el '
        . 'MTC (Informe N.° 179-2020-MTC/21.GE/EATS). Nunca propongas ni modifiques valores en esta '
        . 'tabla — solo se usa para comparar (fuera de este campo) contra el Ratio C-E de cada '
        . 'alternativa (ítems 9.04.02/9.08.02).';

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

        $descripciones = ['10.01.01' => self::DESCRIPCION_D2];
        foreach (self::PARES as [$id1, $id2, $desc]) {
            $descripciones[$id1] = $desc;
            $descripciones[$id2] = $desc;
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

        echo sprintf('FTE-CARRETERAS preparada (D1/D2): %d descripciones.' . PHP_EOL, $c);
    }
}
