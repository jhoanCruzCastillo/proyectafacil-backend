<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Las columnas Departamento/Provincia/Distrito de las 3 tablas de ubicación de FTE-CUIDADO-DIURNO
// (2.01.01, 2.02.01, 3.03.01) están marcadas en el JSON como texto_corto independiente, pero en el
// Excel real SON celdas de fórmula: `+IF(C7="","",VLOOKUP(C7,UBIGEO,4,FALSE))` (Territorio!G7,
// verificado desempaquetando el .xlsx real) — se derivan siempre de la celda "Ubigeo" de su misma
// fila, nunca se escriben directo. Marcarlas `calculado` aquí activa 3 protecciones que YA existen
// en el sistema (una sola vez, sin código nuevo):
//   1. construirPromptTabla() ya no le pide a la IA que las llene ("las calcula el Excel, no las
//      llenes").
//   2. compararForma() ya fuerza su valor original sin importar qué proponga la IA.
//   3. excelWriter.ts nunca escribe una celda con valor vacío ('') — como valorEjemplo se queda
//      vacío para estas claves, la fórmula original del Excel de descarga queda intacta.
// Sin este cambio, escribir CUALQUIER texto ahí (manual o por IA) sobrescribe el VLOOKUP real con
// texto estático la primera vez que se exporta — corrompiendo la fórmula para siempre en ese archivo.
//
// De paso, anota la columna "ubigeo" de esas mismas 3 tablas: en vez de pedirle a la IA el código de
// 6 dígitos directo (que demostró producir códigos plausibles pero incorrectos — ver la sesión de
// llenado manual del CIAI Amanecer, 060104 en vez de 060105), la `nota` le pide los 3 NOMBRES
// separados por " | "; LlenadoIAController::llenarTabla() resuelve el código real de forma
// determinista contra el catálogo oficial (App\Libraries\UbigeoResolver) después de la respuesta del
// modelo — ver resolverUbigeoEnTabla().
//
// Mismo patrón que AnotarNotaColumnasCostosSeeder: actualiza la BD viva porque
// EstructuraPlantillasSeeder se salta cuando la plantilla ya tiene asignado_archivo_id. También se
// aplica el mismo cambio a data/plantillas_estructura.json para entornos sembrados desde cero.
//
// Idempotente: si la columna ya es 'calculado' / ya tiene esta nota, no la vuelve a tocar.
// Uso: php spark db:seed MarcarColumnasUbigeoCalculadasSeeder
class MarcarColumnasUbigeoCalculadasSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CUIDADO-DIURNO';

    private const OBJETIVOS = [
        '2.01.01' => ['depto', 'prov', 'dist'],
        '2.02.01' => ['depto', 'prov', 'dist'],
        '3.03.01' => ['departamento', 'provincia', 'distrito'],
    ];

    private const TABLAS_UBIGEO = ['2.01.01', '2.02.01', '3.03.01'];

    private const NOTA_UBIGEO = 'Escribe el Departamento, la Provincia y el Distrito del proyecto '
        . 'separados por " | ", en ese orden — ej. "Ayacucho | Huamanga | Ayacucho". NO escribas el '
        . 'código UBIGEO de 6 dígitos ni inventes uno: el sistema calcula el código real a partir de '
        . 'estos 3 nombres. Si la fuente de la verdad no menciona alguno de los 3 con claridad, deja '
        . 'la celda vacía en vez de adivinar.';

    public function run(): void
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null || ! $plantilla['asignado_archivo_id']) {
            echo 'No existe ' . self::CODIGO_PLANTILLA . ' con archivo asignado — nada que marcar.' . PHP_EOL;

            return;
        }

        $archivo = $this->db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        if ($archivo === null || empty($archivo['contenido_json'])) {
            echo 'La plantilla no tiene contenido_json — nada que marcar.' . PHP_EOL;

            return;
        }

        $contenido = json_decode((string) $archivo['contenido_json'], true);
        $tocados   = 0;
        $this->recorrer($contenido, $tocados);

        if ($tocados === 0) {
            echo 'Ninguna columna nueva que marcar (ya estaban en calculado, o no se encontraron los campos).' . PHP_EOL;

            return;
        }

        $this->db->table('archivos')->where('id', $archivo['id'])->update([
            'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
        ]);
        echo "Columnas marcadas como calculado: {$tocados}." . PHP_EOL;
    }

    private function recorrer(mixed &$nodo, int &$tocados): void
    {
        if (! is_array($nodo)) {
            return;
        }

        $identificador = $nodo['identificador'] ?? null;
        if (is_string($identificador) && isset(self::OBJETIVOS[$identificador])) {
            $columnas = &$nodo['configTabla']['columnas'];
            if (is_array($columnas)) {
                foreach ($columnas as &$col) {
                    if (in_array($col['id'] ?? null, self::OBJETIVOS[$identificador], true) && ($col['tipo'] ?? null) !== 'calculado') {
                        $col['tipo'] = 'calculado';
                        $tocados++;
                    }
                    if (in_array($identificador, self::TABLAS_UBIGEO, true) && ($col['id'] ?? null) === 'ubigeo' && ($col['nota'] ?? null) !== self::NOTA_UBIGEO) {
                        $col['nota'] = self::NOTA_UBIGEO;
                        $tocados++;
                    }
                }
                unset($col);
            }
            unset($columnas);
        }

        foreach ($nodo as &$hijo) {
            $this->recorrer($hijo, $tocados);
        }
        unset($hijo);
    }
}
