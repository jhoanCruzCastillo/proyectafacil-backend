<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Completa la preparación de FTE-EBR-V03 para el llenado con IA usando el mecanismo de
// "Descripción / ayuda" por campo (el que se ve en el panel de propiedades del editor de
// plantillas), en vez de un markdown aparte como hace FTE-CUIDADO-DIURNO con su contexto general
// "Guía de llenado campo por campo".
//
// Esta ficha ya venía con 101 de 122 campos descritos. Acá se cierra lo que faltaba, y de paso dos
// defectos de estructura que el llenado con IA habría arrastrado:
//
//  1. ETIQUETA EQUIVOCADA — 06.02.1 decía "A) FACTORES DE CONVERSIÓN A PRECIOS SOCIALES" pero su
//     captura es I17 con columnas Año / Sin Proyecto / Inversión / Mantenimiento: en el Excel real
//     ("Anexo 5. Evaluación Eco.") eso es "B) COSTOS DE INVERSIÓN Y MANTENIMIENTO A PRECIOS DE
//     MERCADO" de la Alternativa 2. Con la etiqueta vieja, el prompt le pedía al modelo factores de
//     conversión y le mostraba columnas de costos por año.
//  2. CAMPOS FALTANTES — la Alternativa 2 no tenía los tres campos que sí tiene la Alternativa 1
//     antes de la tabla de costos. En el Excel el bloque de Alt 2 es el de Alt 1 corrido 7 columnas
//     a la derecha (B->I, C->J, D->K, E->L, F->M):
//        A) Factores de conversión  C8:D9  -> J8:K9
//        Tasa Social de Descuento   F7     -> M7
//        Valor Residual             F8     -> M8
//
// También siembra `nota` en las columnas de las tablas de costos de la Sección 06, que es la clave
// que lee construirPromptTabla() para las columnas (no `descripcion`, esa es de campo).
//
// Idempotente: solo escribe lo que falta; correrlo dos veces no duplica ni pisa texto existente.
// Uso: php spark db:seed PrepararIAFTEEBRSeeder
class PrepararIAFTEEBRSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-EBR-V03';

    /** identificador => descripción de campo (la que el prompt manda como ayuda). */
    private const DESCRIPCIONES = [
        // --- Sección 01, 8.2.2 Informe técnico preliminar de mecánica de suelos -------------
        '01.08.10' => 'Responder "Si" o "No" según se adjunte o no el informe técnico preliminar de '
            . 'mecánica de suelos (o documento similar) en los anexos del Diagnóstico.',
        '01.08.11' => 'Registrar la capacidad portante del suelo en kg/cm2, tal como la reporta el '
            . 'informe técnico preliminar de mecánica de suelos. Solo el valor numérico.',
        '01.08.12' => 'Registrar la profundidad del nivel freático en metros (m), según el informe '
            . 'técnico preliminar de mecánica de suelos. Un nivel freático superficial es un riesgo '
            . 'para la ejecución y debe quedar declarado aquí.',
        '01.08.13' => 'Marcar con "X" el tipo de suelo de cada terreno evaluado. Los tipos que admite '
            . 'la FTE son: grava y suelos gravosos, arena y suelos arenosos, limos y arcilla (baja '
            . 'plasticidad), limos y arcilla (alta plasticidad), y suelos altamente orgánicos. Marca '
            . 'una sola opción por terreno.',
        '01.08.14' => 'Nombre completo y número de colegiatura (CIP) del profesional responsable del '
            . 'informe técnico preliminar de mecánica de suelos.',
        // Las dos partes son la misma tabla partida en el Excel: mismo criterio de llenado.
        '01.08.15' => 'Describir de forma RESUMIDA, por especialidad, las condiciones de la '
            . 'infraestructura existente del local educativo, según el informe de diagnóstico suscrito '
            . 'por un arquitecto y/o ingeniero civil que va en anexos. Por especialidad: Arquitectura '
            . '(número y tipo de edificaciones y activos, material predominante, si requieren demolición), '
            . 'Estructuras (si se requiere intervención estructural, reparación o reforzamiento), '
            . 'Instalaciones Eléctricas (si hay suministro, estado de conservación y operatividad) e '
            . 'Instalaciones Sanitarias (agua y alcantarillado o sistema alternativo, estado y '
            . 'operatividad). Si el local tiene más de una institución educativa, detallar la situación '
            . 'de cada una.',
        '01.08.16' => 'Describir de forma RESUMIDA, por especialidad, las condiciones de la '
            . 'infraestructura existente del local educativo, según el informe de diagnóstico suscrito '
            . 'por un arquitecto y/o ingeniero civil que va en anexos. Por especialidad: Arquitectura '
            . '(número y tipo de edificaciones y activos, material predominante, si requieren demolición), '
            . 'Estructuras (si se requiere intervención estructural, reparación o reforzamiento), '
            . 'Instalaciones Eléctricas (si hay suministro, estado de conservación y operatividad) e '
            . 'Instalaciones Sanitarias (agua y alcantarillado o sistema alternativo, estado y '
            . 'operatividad). Si el local tiene más de una institución educativa, detallar la situación '
            . 'de cada una.',

        // --- Sección 06, Anexo 05: Evaluación económica -------------------------------------
        '06.01.1' => 'Factores de conversión que llevan los costos de precios de mercado a precios '
            . 'sociales. La FTE los trae predeterminados: Inversión 0.79 y Mantenimiento y Operación '
            . '0.75. No los cambies salvo que el Invierte.pe haya publicado otros vigentes.',
        '06.01.2' => 'Tasa Social de Descuento del Invierte.pe, expresada en tanto por uno. El valor '
            . 'vigente que trae la FTE es 0.08 (8%).',
        '06.01.5' => 'Costos a precios sociales de la Alternativa 1. Se obtienen multiplicando cada '
            . 'costo a precios de mercado de la tabla B) por su factor de conversión de la tabla A) '
            . '(Inversión × 0.79, Mantenimiento × 0.75). El Excel ya trae la fórmula: no reescribas '
            . 'estas celdas a mano.',
        '06.01.6' => null, // ya tiene descripción; se deja como está

        '06.02.1' => 'Costos de inversión y mantenimiento de la Alternativa 2 a precios de MERCADO, '
            . 'año por año del horizonte de evaluación (10 años). A diferencia de la Alternativa 1 '
            . '—que se completa sola desde los Anexos 01, 02 y 03— acá el presupuesto estimado se '
            . 'registra manualmente. Incluye los costos de operación y mantenimiento con y sin '
            . 'proyecto (ítem 16.4).',
        '06.02.2' => 'Costos a precios sociales de la Alternativa 2. Se obtienen multiplicando cada '
            . 'costo a precios de mercado de la tabla B) por su factor de conversión de la tabla A) '
            . '(Inversión × 0.79, Mantenimiento × 0.75). El Excel ya trae la fórmula: no reescribas '
            . 'estas celdas a mano.',
        '06.02.3' => 'Se calcula automáticamente como la diferencia entre los costos con proyecto y '
            . 'sin proyecto (situación optimizada) de la Alternativa 2, tanto a precios de mercado '
            . 'como sociales.',
    ];

    /** 06.02.1 venía con la etiqueta de otra tabla — ver el comentario de cabecera. */
    private const ETIQUETA_CORREGIDA = [
        '06.02.1' => 'B) COSTOS DE INVERSIÓN Y MANTENIMIENTO A PRECIOS DE MERCADO',
    ];

    /** `nota` por columna (lo que lee construirPromptTabla), donde la unidad no se deduce del nombre. */
    private const NOTA_SOLES = 'Monto en SOLES (S/) del año indicado en la columna "Año", no un '
        . 'porcentaje ni un acumulado. Escribe el número sin separador de miles.';

    private const NOTAS_COLUMNAS = [
        '06.01.4' => ['Sin Proyecto' => self::NOTA_SOLES, 'Inversión' => self::NOTA_SOLES, 'Mantenimiento' => self::NOTA_SOLES],
        '06.01.5' => ['Sin Proyecto' => self::NOTA_SOLES, 'Inversión' => self::NOTA_SOLES, 'Mantenimiento' => self::NOTA_SOLES],
        '06.01.6' => ['Inversión' => self::NOTA_SOLES, 'Mantenimiento' => self::NOTA_SOLES],
        '06.02.1' => ['Sin Proyecto' => self::NOTA_SOLES, 'Inversión' => self::NOTA_SOLES, 'Mantenimiento' => self::NOTA_SOLES],
        '06.02.2' => ['Sin Proyecto' => self::NOTA_SOLES, 'Inversión' => self::NOTA_SOLES, 'Mantenimiento' => self::NOTA_SOLES],
        '06.02.3' => ['Inversión' => self::NOTA_SOLES, 'Mantenimiento*' => self::NOTA_SOLES],
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
        $c = ['desc' => 0, 'etiq' => 0, 'nota' => 0, 'nuevos' => 0];

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

                    $etiqueta = self::ETIQUETA_CORREGIDA[$id] ?? null;
                    if ($etiqueta !== null && ($campo['etiqueta'] ?? '') !== $etiqueta) {
                        $campo['etiqueta'] = $etiqueta;
                        $c['etiq']++;
                    }

                    if ($id === '06.02.5' && ($campo['tipo'] ?? '') !== 'texto_corto') {
                        $campo['tipo'] = 'texto_corto';
                        $c['etiq']++;
                    }

                    $notas = self::NOTAS_COLUMNAS[$id] ?? null;
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

                // La Alternativa 2 no tenía los tres campos previos a la tabla de costos.
                if (($sub['codigo'] ?? '') === '06.02') {
                    $c['nuevos'] += $this->completarAlternativa2($sub);
                }
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
            'FTE-EBR-V03 preparada: %d descripciones, %d etiqueta(s) corregida(s), %d notas de columna, %d campos nuevos en Alternativa 2.' . PHP_EOL,
            $c['desc'],
            $c['etiq'],
            $c['nota'],
            $c['nuevos']
        );
    }

    /**
     * Agrega a la subsección 06.02 los tres campos que sí existen en 06.01 y faltaban acá.
     * Posiciones verificadas en "Anexo 5. Evaluación Eco." del .xlsx real.
     */
    private function completarAlternativa2(array &$sub): int
    {
        $existentes = array_column($sub['campos'] ?? [], 'identificador');
        $nuevos     = [];

        if (! in_array('06.02.0', $existentes, true)) {
            $nuevos[] = [
                'id'            => $this->uuid(),
                'identificador' => '06.02.0',
                'etiqueta'      => 'A) FACTORES DE CONVERSIÓN A PRECIOS SOCIALES',
                'tipo'          => 'tabla',
                'editable'      => true,
                'descripcion'   => self::DESCRIPCIONES['06.01.1'],
                'valorEjemplo'  => '',
                'configTabla'   => [
                    'subtipo'    => 'filas_dinamicas',
                    'agrupador'  => false,
                    'abarcaFilas' => 1,
                    'captura'    => ['columnaInicial' => 'J', 'filaInicial' => 8, 'filasBase' => 2],
                    'columnas'   => [
                        ['id' => $this->uuid(), 'nombre' => 'Obras',  'tipo' => 'texto_corto', 'columnaExcel' => 'J', 'abarcaColumnasExcel' => 1],
                        ['id' => $this->uuid(), 'nombre' => 'Factor', 'tipo' => 'texto_corto', 'columnaExcel' => 'K', 'abarcaColumnasExcel' => 1],
                    ],
                ],
            ];
        }
        if (! in_array('06.02.4', $existentes, true)) {
            $nuevos[] = [
                'id'            => $this->uuid(),
                'identificador' => '06.02.4',
                'etiqueta'      => 'Tasa Social de Descuento',
                'tipo'          => 'decimal',
                'editable'      => true,
                'descripcion'   => self::DESCRIPCIONES['06.01.2'],
                'valorEjemplo'  => '',
                'captura'       => ['columna' => 'M', 'fila' => 7, 'abarcaColumnas' => 1, 'abarcaFilas' => 1],
            ];
        }
        if (! in_array('06.02.5', $existentes, true)) {
            $nuevos[] = [
                'id'            => $this->uuid(),
                'identificador' => '06.02.5',
                'etiqueta'      => 'Valor Residual',
                // Mismo tipo que su gemelo de Alternativa 1 (06.01.3), para que el par se comporte
                // igual. Ambos serían más correctos como 'decimal', pero cambiar el que ya existe y
                // funciona es decisión de producto, no de este seeder.
                'tipo'          => 'texto_corto',
                'editable'      => true,
                'descripcion'   => 'Valor de los activos de la Alternativa 2 que, al finalizar el '
                    . 'horizonte de evaluación (10 años), aún conservan vida útil remanente y por tanto '
                    . 'valor económico.',
                'valorEjemplo'  => '',
                'captura'       => ['columna' => 'M', 'fila' => 8, 'abarcaColumnas' => 1, 'abarcaFilas' => 1],
            ];
        }

        if ($nuevos === []) {
            return 0;
        }

        // Los factores de conversión van primero (fila 8 del Excel), los otros dos al final.
        $sub['campos'] = array_merge($nuevos, $sub['campos'] ?? []);

        return count($nuevos);
    }

    private function uuid(): string
    {
        $d    = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0F) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
