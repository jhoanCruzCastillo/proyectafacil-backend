<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Completa la preparación de la sección "A1. TRÁFICO" de FTE-CARRETERAS (Anexo N.° 01: Pautas
// para el Estudio de Tráfico, del instructivo MTC) — 21 subsecciones prácticamente idénticas: 3
// estaciones principales de conteo × 7 días de la semana. Se genera de forma programática (bucle)
// en vez de escribir 21 bloques calcados a mano — misma descripción para el mismo tipo de campo en
// las 21 repeticiones, identificadores 2.01.01 a 2.21.13.
//
// Cada bloque de 7 días de una estación es: 9 campos de cabecera (N°, nombre, sentido, ubicación,
// código, día/mes/año) + 1 tabla de conteo y clasificación vehicular por hora. Solo la Estación 3
// (identificadores 2.15 a 2.21) agrega 3 tablas de resumen — enteramente CALCULADAS a partir del
// conteo horario, no se llenan con IA.
//
// Clasificación vehicular de la tabla de conteo: la estándar del Reglamento Nacional de Vehículos
// (D.S. N.° 058-2003-MTC) que usa el propio formato "A.5 Formato de Conteo" del aplicativo —
// Auto/Station Wagon/Pick Up/Panel/Rural Combi/Micro (vehículos ligeros), Bus 2E/3E/4E, Camión
// 2E/3E/4E, Semitrayler (2S1-2S2/2S3/3S1-3S2/>=3S3) y Tráiler (2T2/2T3/3T2/>=3T3) — vehículos
// pesados por número de ejes y configuración.
//
// Idempotente: solo escribe lo que falta. Uso: php spark db:seed PrepararIAFTECarreterasA1TraficoSeeder
class PrepararIAFTECarreterasA1TraficoSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

    private const DIAS = ['LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO', 'DOMINGO'];

    /** Descripción de campo por SUFIJO de identificador (.01 a .09) — igual en las 21 repeticiones. */
    private const DESCRIPCIONES_CABECERA = [
        '01' => 'Número correlativo de la estación principal de conteo (1, 2 o 3) — ver el Anexo '
            . 'N.° 01 del instructivo (Pautas para el Estudio de Tráfico). Debe coincidir con el '
            . 'número real de la estación en el estudio de tráfico del proyecto.',
        '02' => 'Nombre o referencia de la estación principal de conteo (ej. cruce, progresiva o '
            . 'localidad donde se ubicó el punto de conteo), tal como figura en el estudio de '
            . 'tráfico.',
        '03' => 'Sentido del conteo en esta estación (ej. "Ida y vuelta", o el par de localidades '
            . 'entre las que circula el tráfico contado), según el estudio de tráfico.',
        '04' => 'Ubicación de la estación de conteo (progresiva Km, o descripción del punto exacto '
            . 'sobre la vía), según el estudio de tráfico.',
        '05' => 'Nombre/código con el que el estudio de tráfico identifica esta estación (puede '
            . 'repetir el dato de "nombre" si el estudio no distingue ambos campos).',
        '06' => 'Código interno o correlativo con el que el estudio de tráfico identifica esta '
            . 'estación de conteo.',
        '07' => 'Día del mes en que se realizó el conteo de este día de la semana (solo el número, '
            . 'ej. "15").',
        '08' => 'Mes en que se realizó el conteo de este día de la semana (ej. "Marzo").',
        '09' => 'Año en que se realizó el conteo de este día de la semana (ej. "2026").',
    ];

    /** Nota por columna de vehículo — misma clasificación MTC (D.S. 058-2003-MTC) en las 21 tablas. */
    private const NOTAS_CONTEO_VEHICULAR = [
        'AUTO' => 'Cantidad de automóviles contados en esta hora y sentido — vehículo ligero de '
            . 'hasta 5 asientos.',
        'STATION WAGON' => 'Cantidad de station wagon contados en esta hora y sentido — vehículo '
            . 'ligero tipo familiar/rural de hasta 5 asientos.',
        'PICK UP' => 'Cantidad de camionetas pick up contadas en esta hora y sentido.',
        'PANEL' => 'Cantidad de camionetas panel (furgonetas cerradas de carga ligera) contadas en '
            . 'esta hora y sentido.',
        'RURAL Combi' => 'Cantidad de combis rurales (vehículo de pasajeros de tamaño intermedio) '
            . 'contadas en esta hora y sentido.',
        'MICRO' => 'Cantidad de microbuses contados en esta hora y sentido.',
        // Los dos pares "2 E"/"3 E"/"4 E" (bus_2e..camion_4e) comparten nombre visible porque son
        // columnas de grupos distintos en el Excel (Bus y Camión) — la nota los distingue.
        'bus_2e' => 'Cantidad de ÓMNIBUS (bus) de 2 ejes contados en esta hora y sentido — no '
            . 'confundir con camión de 2 ejes (columna aparte, grupo "Camión").',
        'bus_3e' => 'Cantidad de ÓMNIBUS (bus) de 3 ejes contados en esta hora y sentido.',
        'bus_4e' => 'Cantidad de ÓMNIBUS (bus) de 4 ejes contados en esta hora y sentido.',
        'camion_2e' => 'Cantidad de CAMIONES de 2 ejes contados en esta hora y sentido — no '
            . 'confundir con bus de 2 ejes (columna aparte, grupo "Bus").',
        'camion_3e' => 'Cantidad de CAMIONES de 3 ejes contados en esta hora y sentido.',
        'camion_4e' => 'Cantidad de CAMIONES de 4 ejes contados en esta hora y sentido.',
        '2S1/2S2' => 'Cantidad de semitráyler (camión + semirremolque) configuración 2S1 o 2S2 '
            . 'contados en esta hora y sentido.',
        '2S3' => 'Cantidad de semitráyler configuración 2S3 contados en esta hora y sentido.',
        '3S1/3S2' => 'Cantidad de semitráyler configuración 3S1 o 3S2 contados en esta hora y '
            . 'sentido.',
        '>= 3S3' => 'Cantidad de semitráyler configuración 3S3 o mayor contados en esta hora y '
            . 'sentido.',
        '2T2' => 'Cantidad de tráiler (camión + remolque) configuración 2T2 contados en esta hora y '
            . 'sentido.',
        '2T3' => 'Cantidad de tráiler configuración 2T3 contados en esta hora y sentido.',
        '3T2' => 'Cantidad de tráiler configuración 3T2 contados en esta hora y sentido.',
        '>=3T3' => 'Cantidad de tráiler configuración 3T3 o mayor contados en esta hora y sentido.',
    ];

    private const DESCRIPCION_TABLA_CONTEO = 'Conteo vehicular clasificado por tipo de vehículo, '
        . 'una fila por cada hora del día (24 horas) — según la clasificación del Reglamento '
        . 'Nacional de Vehículos (D.S. N.° 058-2003-MTC). "Hora" y "Sentido" ya vienen precargados '
        . '(calculados); completa únicamente las cantidades de cada tipo de vehículo, tal como las '
        . 'reporta el estudio de tráfico real del proyecto — nunca inventes ni repitas un patrón '
        . 'genérico si no hay evidencia para una hora puntual (déjala en 0 solo si el estudio '
        . 'confirma que no hubo tránsito, o vacía si no hay dato).';

    private const DESCRIPCION_RESUMEN_CALCULADO = 'Tabla enteramente calculada a partir del conteo '
        . 'horario de esta misma estación y día — no propongas valores para ninguna de sus '
        . 'columnas.';

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

        // Arma DESCRIPCIONES/NOTAS_COLUMNAS para las 21 subsecciones (3 estaciones × 7 días).
        $descripciones = [];
        $notasTabla    = [];
        for ($idx = 1; $idx <= 21; $idx++) {
            $prefijo = sprintf('2.%02d', $idx);
            foreach (self::DESCRIPCIONES_CABECERA as $sufijo => $desc) {
                $descripciones["{$prefijo}.{$sufijo}"] = $desc;
            }
            $descripciones["{$prefijo}.10"] = self::DESCRIPCION_TABLA_CONTEO;
            $notasTabla["{$prefijo}.10"]    = self::NOTAS_CONTEO_VEHICULAR;

            if ($idx >= 15) { // Estación 3 (2.15 a 2.21): 3 tablas resumen extra, calculadas.
                $descripciones["{$prefijo}.11"] = self::DESCRIPCION_RESUMEN_CALCULADO;
                $descripciones["{$prefijo}.12"] = self::DESCRIPCION_RESUMEN_CALCULADO;
                $descripciones["{$prefijo}.13"] = self::DESCRIPCION_RESUMEN_CALCULADO;
            }
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

                    $nueva = $descripciones[$id] ?? null;
                    if ($nueva !== null && trim((string) ($campo['descripcion'] ?? '')) === '') {
                        $campo['descripcion'] = $nueva;
                        $c['desc']++;
                    }

                    $notas = $notasTabla[$id] ?? null;
                    if ($notas !== null && ! empty($campo['configTabla']['columnas'])) {
                        foreach ($campo['configTabla']['columnas'] as &$col) {
                            // Bus/Camión 2E-4E comparten `nombre` visible ("2 E"/"3 E"/"4 E") — se
                            // distinguen por `id` de columna (bus_2e vs camion_2e), no por nombre.
                            $clave = in_array($col['id'] ?? '', ['bus_2e', 'bus_3e', 'bus_4e', 'camion_2e', 'camion_3e', 'camion_4e'], true)
                                ? $col['id']
                                : ($col['nombre'] ?? '');
                            $nota = $notas[$clave] ?? null;
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
            'FTE-CARRETERAS preparada (sección A1. TRÁFICO, 21 estaciones-día): %d descripciones, %d notas de columna.' . PHP_EOL,
            $c['desc'],
            $c['nota']
        );
    }
}
