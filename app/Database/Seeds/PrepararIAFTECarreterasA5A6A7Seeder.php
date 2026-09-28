<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Completa la preparación de las 3 secciones "Formato de campo" de FTE-CARRETERAS: "A5. FORMATO
// DE CONTEO", "A6. FORMATO ENCUESTA PASAJE" y "A7. FORMATO ENCUESTA CARGA" — plantillas de
// transcripción de campo (mismo espíritu que la sección A1: datos que un equipo de campo ya
// levantó, no razonamiento). A5 reutiliza la misma clasificación vehicular que A1 (Reglamento
// Nacional de Vehículos, D.S. N.° 058-2003-MTC) pero partida en 3 bloques horarios en vez de 24
// filas; A6/A7 son fichas de encuesta Origen-Destino (pasajeros y carga respectivamente).
//
// Idempotente. Uso: php spark db:seed PrepararIAFTECarreterasA5A6A7Seeder
class PrepararIAFTECarreterasA5A6A7Seeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

    private const DESCRIPCIONES = [
        // --- A5. FORMATO DE CONTEO --------------------------------------------------------------
        '13.01.01' => 'Tramo de la carretera donde se realizó el conteo — debe coincidir con el '
            . 'tramo registrado en el diagnóstico de la UP (ítem 1.05) o en la sección A1.',
        '13.01.02' => 'Sentido del conteo (ej. "Ida y vuelta" o el par de localidades entre las que '
            . 'circula el tráfico contado).',
        '13.01.03' => 'Ubicación de la estación de conteo (progresiva Km o descripción del punto).',
        '13.01.04' => 'Nombre/código con el que el estudio de tráfico identifica esta estación.',
        '13.01.05' => 'Código interno o correlativo de la estación de conteo.',
        '13.01.06' => 'Día de la semana del conteo (ej. "Lunes").',
        '13.01.07' => 'Día del mes en que se realizó el conteo (solo el número).',
        '13.01.08' => 'Mes en que se realizó el conteo.',
        '13.01.09' => 'Año en que se realizó el conteo.',
        '13.02.01' => 'Conteo vehicular clasificado por tipo de vehículo, del bloque horario 00:00 '
            . 'a 08:00 — según la clasificación del Reglamento Nacional de Vehículos (D.S. N.° '
            . '058-2003-MTC). "Hora" y "Sentido" ya vienen precargados (calculados); completa '
            . 'únicamente las cantidades reales del estudio de tráfico, nunca inventadas.',
        '13.02.02' => 'Conteo vehicular clasificado por tipo de vehículo, del bloque horario 08:00 '
            . 'a 16:00 — mismo criterio que el bloque 00-08.',
        '13.02.03' => 'Conteo vehicular clasificado por tipo de vehículo, del bloque horario 16:00 '
            . 'a 24:00 — mismo criterio que el bloque 00-08.',

        // --- A6. FORMATO ENCUESTA PASAJE ---------------------------------------------------------
        '14.01.01' => 'Tramo de la carretera donde se realizó la encuesta de Origen-Destino.',
        '14.01.02' => 'Ubicación de la estación de encuesta (progresiva Km o descripción del '
            . 'punto).',
        '14.01.03' => 'Sentido de la encuesta.',
        '14.01.04' => 'Nombre/código con el que el estudio identifica esta estación de encuesta.',
        '14.01.05' => 'Código interno o correlativo de la estación de encuesta.',
        '14.01.06' => 'Día de la semana de la encuesta (ej. "Lunes").',
        '14.01.07' => 'Día del mes en que se realizó la encuesta.',
        '14.01.08' => 'Mes en que se realizó la encuesta.',
        '14.01.09' => 'Año en que se realizó la encuesta.',
        '14.02.01' => 'Una fila por cada vehículo de pasajeros encuestado — transcribe tal como '
            . 'consta en la encuesta física de campo: hora, placa, tipo de vehículo, marca/modelo/'
            . 'año, tipo de combustible, número de asientos y pasajeros, motivo de viaje (marca con '
            . '"X" solo UNA de las 4 columnas de motivo: Trabajo/comercio, Turismo, Estudio, o '
            . 'Salud) y el par Origen/Destino (lugar, provincia, departamento). No completes filas '
            . 'para encuestas que no existen en la fuente de la verdad.',
        '14.03.01' => 'Nombre del encuestador que levantó la información de campo.',
        '14.03.02' => 'Nombre del Jefe de Brigada responsable del equipo de encuestadores.',
        '14.03.03' => 'Nombre y colegiatura del Ingeniero Responsable del estudio de tráfico.',

        // --- A7. FORMATO ENCUESTA CARGA ----------------------------------------------------------
        '15.01.01' => 'Tramo de la carretera donde se realizó la encuesta de Origen-Destino de '
            . 'carga.',
        '15.01.02' => 'Ubicación de la estación de encuesta (progresiva Km o descripción del '
            . 'punto).',
        '15.01.03' => 'Sentido de la encuesta.',
        '15.01.04' => 'Nombre/código con el que el estudio identifica esta estación de encuesta.',
        '15.01.05' => 'Código interno o correlativo de la estación de encuesta.',
        '15.01.06' => 'Día del mes en que se realizó la encuesta.',
        '15.01.07' => 'Mes en que se realizó la encuesta.',
        '15.01.08' => 'Año en que se realizó la encuesta.',
        '15.02.01' => 'Una fila por cada vehículo de carga encuestado — transcribe tal como consta '
            . 'en la encuesta física de campo: hora, placa, tipo de vehículo, carrocería, embalaje, '
            . 'combustible, producto transportado, el par Origen/Destino (lugar, provincia, '
            . 'departamento), peso de la carga, marca/modelo/año, peso seco y carga útil del '
            . 'vehículo. No completes filas para encuestas que no existen en la fuente de la '
            . 'verdad.',
        '15.03.01' => 'Nombre del encuestador que levantó la información de campo.',
        '15.03.02' => 'Nombre del Jefe de Brigada responsable del equipo de encuestadores.',
        '15.03.03' => 'Nombre y colegiatura del Ingeniero Responsable del estudio de tráfico.',
    ];

    private const NOTA_AUTO = 'Cantidad de automóviles contados en esta hora y sentido.';
    private const NOTA_SW = 'Cantidad de station wagon contados en esta hora y sentido.';
    private const NOTA_PICKUP = 'Cantidad de camionetas pick up contadas en esta hora y sentido.';
    private const NOTA_PANEL = 'Cantidad de camionetas panel contadas en esta hora y sentido.';
    private const NOTA_COMBI = 'Cantidad de combis rurales contadas en esta hora y sentido.';
    private const NOTA_MICRO = 'Cantidad de microbuses contados en esta hora y sentido.';
    private const NOTA_BUS_2E = 'Cantidad de ómnibus (bus) de 2 ejes contados en esta hora y '
        . 'sentido — no confundir con camión de 2 ejes.';
    private const NOTA_BUS_3E = 'Cantidad de ómnibus (bus) de 3 ejes contados en esta hora y '
        . 'sentido.';
    private const NOTA_BUS_4E = 'Cantidad de ómnibus (bus) de 4 ejes contados en esta hora y '
        . 'sentido.';
    private const NOTA_CAMION_2E = 'Cantidad de camiones de 2 ejes contados en esta hora y sentido '
        . '— no confundir con bus de 2 ejes.';
    private const NOTA_CAMION_3E = 'Cantidad de camiones de 3 ejes contados en esta hora y sentido.';
    private const NOTA_CAMION_4E = 'Cantidad de camiones de 4 ejes contados en esta hora y sentido.';
    private const NOTA_SEMI_2S1_2S2 = 'Cantidad de semitráyler configuración 2S1 o 2S2 contados en '
        . 'esta hora y sentido.';
    private const NOTA_SEMI_2S3 = 'Cantidad de semitráyler configuración 2S3 contados en esta hora '
        . 'y sentido.';
    private const NOTA_SEMI_3S1_3S2 = 'Cantidad de semitráyler configuración 3S1 o 3S2 contados en '
        . 'esta hora y sentido.';
    private const NOTA_SEMI_GE_3S3 = 'Cantidad de semitráyler configuración 3S3 o mayor contados en '
        . 'esta hora y sentido.';
    private const NOTA_TR_2T2 = 'Cantidad de tráiler configuración 2T2 contados en esta hora y '
        . 'sentido.';
    private const NOTA_TR_2T3 = 'Cantidad de tráiler configuración 2T3 contados en esta hora y '
        . 'sentido.';
    private const NOTA_TR_3T2 = 'Cantidad de tráiler configuración 3T2 contados en esta hora y '
        . 'sentido.';
    private const NOTA_TR_GE_3T3 = 'Cantidad de tráiler configuración 3T3 o mayor contados en esta '
        . 'hora y sentido.';

    private const NOTAS_CONTEO = [
        'Auto' => self::NOTA_AUTO,
        'Station Wagon' => self::NOTA_SW,
        'Pick Up' => self::NOTA_PICKUP,
        'Panel' => self::NOTA_PANEL,
        'Rural Combi' => self::NOTA_COMBI,
        'Micro' => self::NOTA_MICRO,
        'Bus 2 E' => self::NOTA_BUS_2E,
        'Bus 3 E' => self::NOTA_BUS_3E,
        'Bus 4 E' => self::NOTA_BUS_4E,
        'Camión 2 E' => self::NOTA_CAMION_2E,
        'Camión 3 E' => self::NOTA_CAMION_3E,
        'Camión 4 E' => self::NOTA_CAMION_4E,
        'Semi Trayler 2S1/2S2' => self::NOTA_SEMI_2S1_2S2,
        'Semi Trayler 2S3' => self::NOTA_SEMI_2S3,
        'Semi Trayler 3S1/3S2' => self::NOTA_SEMI_3S1_3S2,
        'Semi Trayler >= 3S3' => self::NOTA_SEMI_GE_3S3,
        'Trayler 2T2' => self::NOTA_TR_2T2,
        'Trayler 2T3' => self::NOTA_TR_2T3,
        'Trayler 3T2' => self::NOTA_TR_3T2,
        'Trayler >=3T3' => self::NOTA_TR_GE_3T3,
    ];

    private const NOTA_MOTIVO = 'Marca "X" en ESTA columna solo si este fue el motivo de viaje real '
        . 'del pasajero encuestado — a lo sumo una de las 4 columnas de motivo debe llevar "X" por '
        . 'fila.';

    private const NOTAS_ENCUESTA_PASAJE = [
        'Motivo de Viaje - T (Trabajo, comercio)' => self::NOTA_MOTIVO,
        'Motivo de Viaje - P (Turismo, paseos, excursiones)' => self::NOTA_MOTIVO,
        'Motivo de Viaje - E (Estudio, seminario, congreso)' => self::NOTA_MOTIVO,
        'Motivo de Viaje - S (Salud, enfermedad)' => self::NOTA_MOTIVO,
        'Origen (valor)' => 'Lugar, provincia y departamento de origen del viaje, tal como lo '
            . 'declaró el pasajero encuestado.',
        'Destino (valor)' => 'Lugar, provincia y departamento de destino del viaje, tal como lo '
            . 'declaró el pasajero encuestado.',
    ];

    private const NOTAS_ENCUESTA_CARGA = [
        'Origen (valor)' => 'Lugar, provincia y departamento de origen del viaje, tal como lo '
            . 'declaró el conductor encuestado.',
        'Destino (valor)' => 'Lugar, provincia y departamento de destino del viaje, tal como lo '
            . 'declaró el conductor encuestado.',
        'Peso Carga' => 'Peso de la carga transportada (toneladas o kg, según el formato de la '
            . 'encuesta), tal como lo declaró el conductor.',
        'Peso Seco' => 'Peso seco (tara) del vehículo, según su ficha técnica.',
        'Carga Util' => 'Capacidad de carga útil del vehículo, según su ficha técnica.',
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

        $notasTabla = [
            '13.02.01' => self::NOTAS_CONTEO,
            '13.02.02' => self::NOTAS_CONTEO,
            '13.02.03' => self::NOTAS_CONTEO,
            '14.02.01' => self::NOTAS_ENCUESTA_PASAJE,
            '15.02.01' => self::NOTAS_ENCUESTA_CARGA,
        ];

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
            'FTE-CARRETERAS preparada (A5/A6/A7): %d descripciones, %d notas de columna.' . PHP_EOL,
            $c['desc'],
            $c['nota']
        );
    }
}
