<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Completa la preparación de la sección "C. COSTOS" de FTE-CARRETERAS — costo de inversión y
// costos de operación/mantenimiento referenciales, por cada una de las 2 alternativas técnicas.
// Estructura idéntica en Alternativa 1 (8.01.xx) y Alternativa 2 (8.02.xx), generada por bucle.
//
// La mayoría de los campos son SUBTOTALES/TOTALES calculados (A, B, C, Total, IGV) — los únicos
// datos de entrada reales son: naturaleza de la acción y cantidades/precio unitario por partida
// (tabla 8.0X.03), gastos generales/utilidad/supervisión/expediente técnico/gestión/liquidación
// (montos o % reales del proyecto) y el precio de O&M referencial (tabla 8.0X.16).
//
// Idempotente. Uso: php spark db:seed PrepararIAFTECarreterasCCostosSeeder
class PrepararIAFTECarreterasCCostosSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

    /** Descripción por SUFIJO de identificador (.01 a .16) — igual en Alternativa 1 y 2. */
    private const DESCRIPCIONES_SUFIJO = [
        '01' => 'Se autocompleta con el nombre del proyecto (enunciado del ítem 1.01.02) — no lo '
            . 'llenes manualmente, es un campo calculado.',
        '02' => 'Se autocompleta con la región geográfica de la ubicación del proyecto (ítem 1.04) '
            . '— no lo llenes manualmente, es un campo calculado.',
        '04' => 'Se autocalcula como la suma de todos los costos de la tabla de inversión por tramo '
            . '(columna "Costo total") — no lo llenes manualmente.',
        '05' => 'Porcentaje de Gastos Generales sobre el costo directo, según el sistema de '
            . 'ejecución real del proyecto (contrata/administración indirecta) — en administración '
            . 'directa suele ser menor o 0%. No uses un porcentaje "típico" si la fuente de la '
            . 'verdad no lo sustenta.',
        '06' => 'Porcentaje de Utilidad sobre el costo directo — DEBE ser 0% en administración '
            . 'directa; en administración indirecta (por contrata), el % real del contrato.',
        '07' => 'Se autocalcula aplicando el IGV (18% salvo que la fuente de la verdad indique otro '
            . 'régimen) sobre gastos generales y utilidad — no lo llenes manualmente.',
        '08' => 'Se autocalcula como la suma de Gastos Generales + Utilidad + IGV — no lo llenes '
            . 'manualmente.',
        '09' => 'Monto o % real de supervisión de obra, según el contrato/presupuesto del proyecto '
            . '— no un porcentaje genérico.',
        '10' => 'Costo real del expediente técnico o documento equivalente del proyecto.',
        '11' => 'Costo real de gestión del proyecto (administración del expediente/obra a cargo de '
            . 'la UEI).',
        '12' => 'Costo real de liquidación del proyecto.',
        '13' => 'Se autocalcula como la suma de Supervisión + Expediente Técnico + Gestión + '
            . 'Liquidación — no lo llenes manualmente.',
        '14' => 'Se autocalcula como Costo Directo (A) + Costos Indirectos (B) + Otros Costos (C) — '
            . 'debe coincidir con el costo de inversión ya declarado en el ítem 15.1. No lo llenes '
            . 'manualmente.',
        '16' => 'Costos de operación y mantenimiento REFERENCIALES por km (Precios de Mercado S./'
            . 'Km) — una fila por cada política de mantenimiento (rutinario/periódico, sin/con '
            . 'proyecto), con su frecuencia ya predefinida en la columna "Políticas de Operación". '
            . 'Completa solo el precio real por km, según el presupuesto del proyecto.',
    ];

    /** `15` (CONTROL CONCURRENTE) cambia de tipo entre Alternativa 1 (calculado) y 2 (decimal) — '
     * defecto de estructura real de la plantilla, no se corrige acá (requiere verificar contra el
     * .xlsm real antes de unificar el tipo). Se documenta cada uno según su tipo real. */
    private const DESC_CONTROL_CONCURRENTE_CALCULADO = 'Se autocalcula como hasta 2% del costo '
        . 'total de la inversión (ítem .14) — no lo llenes manualmente, es un campo calculado.';
    private const DESC_CONTROL_CONCURRENTE_DECIMAL = 'Monto de control concurrente del proyecto — '
        . 'según el instructivo, puede llegar hasta el 2% del costo total de la inversión (ítem '
        . '.14). No superes ese límite salvo sustento explícito.';

    private const NOTA_TABLA_TRAMO_COSTO_DIRECTO = 'Tabla mixta: filas de catálogo de activos '
        . 'estratégicos, con "Activos" y "Tipo de factor producción" ya predefinidos — completa '
        . 'por cada fila: Naturaleza de la acción (construcción/reforzamiento estructural/'
        . 'remodelación/reparación integral/reparación parcial, ver contexto global "Invierte.pe"), '
        . 'Unidad de medida física, Cantidad física, Cantidad (dimensión) y Precio unitario (soles/'
        . 'UM) — todos según el presupuesto real del proyecto. "Costo total" es calculado (cantidad '
        . '× precio unitario), no lo llenes.';

    private const NOTAS_TABLA_INVERSION = [
        'Naturaleza de acción' => 'EXACTAMENTE una de: construcción, reforzamiento estructural, '
            . 'remodelación, reparación integral o reparación parcial (ver "naturaleza de la '
            . 'acción" en el contexto global "Invierte.pe") — nunca texto libre.',
        'Unidad de medida (física)' => 'Unidad de medida real de la cantidad física de este activo '
            . '(ej. m2, m3, Km, Und) — no confundir con la "unidad de medida (dimensión)", que es '
            . 'calculada.',
        'Cantidad (física)' => 'Cantidad física real de este activo, según el dimensionamiento del '
            . 'proyecto (Anexo N.° 03) — nunca inventada.',
        'Cantidad (dimensión)' => 'Cantidad en la unidad de dimensión (calculada) de este activo — '
            . 'solo complétala si no se deriva automáticamente de la cantidad física.',
        'Precio unitario (soles/UM)' => 'Precio unitario real en soles por unidad de medida, según '
            . 'el presupuesto del proyecto — nunca un precio "de mercado típico" sin sustento.',
    ];

    private const NOTA_PRECIO_OM = 'Precio de mercado en soles por kilómetro (S./Km) para esta '
        . 'política de mantenimiento — según el presupuesto real del proyecto, no un valor típico.';

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

        $descripciones = [];
        $notasTabla    = [];
        foreach (['8.01', '8.02'] as $prefijo) {
            foreach (self::DESCRIPCIONES_SUFIJO as $sufijo => $desc) {
                $descripciones["{$prefijo}.{$sufijo}"] = $desc;
            }
            $descripciones["{$prefijo}.03"] = self::NOTA_TABLA_TRAMO_COSTO_DIRECTO;
            $notasTabla["{$prefijo}.03"]    = self::NOTAS_TABLA_INVERSION;
            $notasTabla["{$prefijo}.16"]    = ['Precios de Mercado S. /Km' => self::NOTA_PRECIO_OM];
        }
        // 15: tipo real distinto entre alternativas (ver comentario de cabecera).
        $descripciones['8.01.15'] = self::DESC_CONTROL_CONCURRENTE_CALCULADO;
        $descripciones['8.02.15'] = self::DESC_CONTROL_CONCURRENTE_DECIMAL;

        $contenido = json_decode((string) $archivo['contenido_json'], true);
        $c = ['desc' => 0, 'nota' => 0];

        $contenido['secciones'] ??= [];
        foreach ($contenido['secciones'] as &$seccion) {
            $seccion['subsecciones'] ??= [];
            foreach ($seccion['subsecciones'] as &$sub) {
                $sub['campos'] ??= [];
                foreach ($sub['campos'] as &$campo) {
                    $id = (string) ($campo['identificador'] ?? '');
                    if ($id === '') {
                        continue;
                    }

                    $nueva = $descripciones[$id] ?? null;
                    if ($nueva !== null && trim((string) ($campo['descripcion'] ?? '')) === '') {
                        $campo['descripcion'] = $nueva;
                        $c['desc']++;
                    }

                    $notas = $notasTabla[$id] ?? null;
                    if ($notas !== null && ! empty($campo['configTabla']['columnas'])) {
                        foreach ($campo['configTabla']['columnas'] as &$col) {
                            $nota = $notas[$col['nombre'] ?? ''] ?? null;
                            if ($nota !== null && trim((string) ($col['nota'] ?? '')) === '') {
                                $col['nota'] = $nota;
                                $c['nota']++;
                            }
                        }
                        unset($col);
                    }
                }
                unset($campo);
            }
            unset($sub);
        }
        unset($seccion);

        if (array_sum($c) === 0) {
            echo 'Ya estaba todo preparado — nada que escribir.' . PHP_EOL;

            return;
        }

        $this->db->table('archivos')->where('id', $archivo['id'])->update([
            'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
        ]);

        echo sprintf(
            'FTE-CARRETERAS preparada (sección C. COSTOS): %d descripciones, %d notas de columna.' . PHP_EOL,
            $c['desc'],
            $c['nota']
        );
    }
}
