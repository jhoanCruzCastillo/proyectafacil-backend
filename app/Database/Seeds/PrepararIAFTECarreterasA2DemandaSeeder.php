<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Completa la preparación de la sección "A2. DEMANDA" de FTE-CARRETERAS — deriva el IMDA actual y
// proyectado a partir del conteo de tráfico (sección A1) y las tasas de crecimiento (sección A4).
// La mayoría de sus tablas son enteramente CALCULADAS (encadenan A1 -> A2 -> A4 -> A2); solo
// "Tráfico Desviado por Tipo de Vehículo" (3.05.05) tiene columnas de año realmente editables — el
// resto de campos sueltos son datos puntuales (ubicación, mes/año del conteo, factores de A3, tipo
// de intervención, % de tráfico normal, existencia de vía alterna).
//
// Idempotente. Uso: php spark db:seed PrepararIAFTECarreterasA2DemandaSeeder
class PrepararIAFTECarreterasA2DemandaSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

    private const NOTA_ANIO_DESVIADO = 'Tráfico desviado estimado para este año del horizonte '
        . '(vehículos/día), tal como resulte de la matriz Origen-Destino (encuestas O/D, ver Anexo '
        . 'N.° 01) — no lo calcules con una fórmula propia, cópialo del resultado de esa matriz. '
        . 'Déjalo en 0 si no existe vía alterna (ver 2.3 ¿Existe vía alterna?).';

    private const DESCRIPCIONES = [
        // --- 1. Generalidades -------------------------------------------------------------------
        '3.01.01' => 'Se autocompleta con el departamento de la ubicación geográfica de la vía '
            . '(ítem 1.04) — no lo llenes manualmente, es un campo calculado.',
        '3.01.02' => 'Provincia donde se ubica la vía — debe coincidir con la registrada en la '
            . 'ubicación geográfica (ítem 1.04).',
        '3.01.03' => 'Distrito donde se ubica la vía — debe coincidir con el registrado en la '
            . 'ubicación geográfica (ítem 1.04).',
        '3.01.04' => 'Se autocompleta con el horizonte de evaluación del proyecto (ítem 1.12) — no '
            . 'lo llenes manualmente, es un campo calculado.',

        // --- 1.1 Determinación del tráfico actual -------------------------------------------------
        '3.02.01' => 'Mes en que se realizó el estudio de tráfico (conteo de 7 días de la sección '
            . 'A1) — debe coincidir con las fechas registradas ahí.',
        '3.02.02' => 'Año en que se realizó el estudio de tráfico — debe coincidir con las fechas '
            . 'registradas en la sección A1.',
        '3.02.03' => 'Tabla enteramente calculada — resume el conteo horario de la sección A1 '
            . '(Tráfico) en un total por día y tipo de vehículo. No propongas valores para ninguna '
            . 'de sus columnas.',
        '3.02.04' => 'Factor de Corrección Estacional (F.C.E.) para vehículos ligeros, según la '
            . 'sección A3 (Factor de Corrección) de esta misma ficha — no inventes el valor, '
            . 'cópialo de esa hoja.',
        '3.02.05' => 'Factor de Corrección Estacional (F.C.E.) para vehículos pesados, según la '
            . 'sección A3 (Factor de Corrección) de esta misma ficha — no inventes el valor, '
            . 'cópialo de esa hoja.',

        // --- 2.1 Demanda Actual --------------------------------------------------------------------
        '3.03.01' => 'Tabla enteramente calculada — IMD/IMDA actual por tipo de vehículo, derivado '
            . 'del conteo de tráfico (3.02.03) y los factores de corrección (3.02.04/3.02.05). No '
            . 'propongas valores para ninguna de sus columnas.',
        '3.03.02' => 'Tabla enteramente calculada — resumen del IMD actual y su distribución '
            . 'porcentual por tipo de vehículo. No propongas valores para ninguna de sus columnas.',

        // --- 2.2 Demanda Proyectada ------------------------------------------------------------------
        '3.04.01' => 'Se autocompleta con la tasa de crecimiento vehicular de pasajeros de la '
            . 'región del proyecto (sección A4, Tasa de Crecimiento) — no lo llenes manualmente.',
        '3.04.02' => 'Se autocompleta con la tasa de crecimiento vehicular de carga de la región '
            . 'del proyecto (sección A4, Tasa de Crecimiento) — no lo llenes manualmente.',
        '3.04.03' => 'Tabla enteramente calculada — proyecta el tráfico normal (situación sin '
            . 'proyecto) a 20 años aplicando las tasas de crecimiento (3.04.01/3.04.02) al tráfico '
            . 'actual (2.1). No propongas valores para ninguna de sus columnas.',

        // --- 2.3 Demanda Proyectada Con Proyecto ------------------------------------------------------
        '3.05.01' => 'Se autocompleta con la naturaleza de intervención del proyecto (ítem 1.01.01) '
            . '— no lo llenes manualmente, es un campo calculado.',
        '3.05.02' => '% que representa el tráfico generado o inducido respecto al tráfico normal — '
            . 'según el instructivo, el orden de magnitud sugerido es alrededor de 10%, sin superar '
            . '30% salvo que la fuente de la verdad sustente explícitamente un valor mayor.',
        '3.05.03' => 'Responder "Sí" o "No" según si existe una vía alterna que hoy capta tráfico '
            . 'que podría desviarse hacia la vía del proyecto una vez mejorada. Si es "No", el '
            . 'tráfico desviado (2.3, tabla siguiente) debe quedar en 0.',
        '3.05.04' => 'Tabla enteramente calculada — suma tráfico normal + generado + desviado por '
            . 'tipo de vehículo y año. No propongas valores para ninguna de sus columnas.',
        '3.05.05' => 'Única tabla de esta sección con columnas realmente editables (por año, 0 a '
            . '20) — tráfico desviado estimado hacia la vía del proyecto, resultado de la matriz '
            . 'Origen-Destino (encuestas O/D, ver Anexo N.° 01). Déjalo en 0 en todos los años si '
            . '"¿Existe vía alterna?" (2.3) fue "No".',
        '3.05.06' => 'Tabla enteramente calculada — IMD total (normal + generado + desviado) por '
            . 'año. No propongas valores para ninguna de sus columnas.',
        '3.05.07' => 'Tabla enteramente calculada — tráfico total por tipo de vehículo y año. No '
            . 'propongas valores para ninguna de sus columnas.',

        // --- 2.4 Proyección de la población del área de influencia -------------------------------------
        '3.06.01' => 'Tabla enteramente calculada — proyección de la población beneficiaria (ítem '
            . '1.06.01) a 20 años. No propongas valores para ninguna de sus columnas.',
        '3.06.02' => 'Se autocompleta como el promedio de la proyección poblacional — no lo llenes '
            . 'manualmente, es un campo calculado.',
    ];

    private const NOTAS_COLUMNAS = [
        '3.05.05' => [
            'y0' => self::NOTA_ANIO_DESVIADO, 'y1' => self::NOTA_ANIO_DESVIADO, 'y2' => self::NOTA_ANIO_DESVIADO,
            'y3' => self::NOTA_ANIO_DESVIADO, 'y4' => self::NOTA_ANIO_DESVIADO, 'y5' => self::NOTA_ANIO_DESVIADO,
            'y6' => self::NOTA_ANIO_DESVIADO, 'y7' => self::NOTA_ANIO_DESVIADO, 'y8' => self::NOTA_ANIO_DESVIADO,
            'y9' => self::NOTA_ANIO_DESVIADO, 'y10' => self::NOTA_ANIO_DESVIADO, 'y11' => self::NOTA_ANIO_DESVIADO,
            'y12' => self::NOTA_ANIO_DESVIADO, 'y13' => self::NOTA_ANIO_DESVIADO, 'y14' => self::NOTA_ANIO_DESVIADO,
            'y15' => self::NOTA_ANIO_DESVIADO, 'y16' => self::NOTA_ANIO_DESVIADO, 'y17' => self::NOTA_ANIO_DESVIADO,
            'y18' => self::NOTA_ANIO_DESVIADO, 'y19' => self::NOTA_ANIO_DESVIADO, 'y20' => self::NOTA_ANIO_DESVIADO,
        ],
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

                    $nueva = self::DESCRIPCIONES[$id] ?? null;
                    if ($nueva !== null && trim((string) ($campo['descripcion'] ?? '')) === '') {
                        $campo['descripcion'] = $nueva;
                        $c['desc']++;
                    }

                    $notas = self::NOTAS_COLUMNAS[$id] ?? null;
                    if ($notas !== null && ! empty($campo['configTabla']['columnas'])) {
                        foreach ($campo['configTabla']['columnas'] as &$col) {
                            $nota = $notas[$col['id'] ?? ''] ?? null;
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
            'FTE-CARRETERAS preparada (sección A2. DEMANDA): %d descripciones, %d notas de columna.' . PHP_EOL,
            $c['desc'],
            $c['nota']
        );
    }
}
