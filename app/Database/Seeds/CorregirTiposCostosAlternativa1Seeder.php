<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Bug real encontrado en vivo (2026-08-25): en la tabla 09.01.6 (Costos indirectos, Alternativa 1)
// la columna "% respecto al costo directo" mostraba #VALUE! para todas las filas. Causa raíz, en 2
// partes:
//
// 1. "Costos a precios de mercado" (09.01.1 a 09.01.6, Alternativa 1) está tipada `texto_corto` en
//    vez de `decimal` — comparado contra las tablas GEMELAS de Alternativa 2 y 3 (10.01.x/11.01.x,
//    mismas columnas, mismo Excel), que SÍ están en `decimal`. Con `texto_corto` nadie le pide a la
//    IA "sin separador de miles" (ver construirPromptTabla()), así que escribía "S/ 107,500" tal
//    cual — texto, no número. La fórmula real del Excel (`Análisis Técnico`... perdón,
//    `CostosAlt1!G70 = IF($M$64>0,+E70/$M$64,"")`) divide esa celda: en Excel de verdad "S/ 107,500"
//    como texto en una celda numérica YA rompe cualquier fórmula que la sume/divida — no es un bug
//    de nuestro motor de fórmulas en vivo, el Excel real haría lo mismo.
// 2. "% respecto al costo directo" (mismas 3 tablas) es la propia fórmula de arriba — un valor
//    CALCULADO por Excel, no algo que la IA deba llenar — pero estaba tipada `decimal`/`texto_corto`
//    en las 3 alternativas, sin la protección `calculado` que ya existe en el sistema (mismo patrón
//    que MarcarColumnasUbigeoCalculadasSeeder: prompt no se lo pide a la IA, compararForma() fuerza
//    el valor original, excelWriter.ts nunca pisa la fórmula real al exportar).
//
// Corrige la estructura (Alternativa 1 para que combine con sus gemelas 2/3; las 3 para el punto 2)
// y, aparte, limpia los datos YA guardados en cada ejemplo de esta plantilla (quita "S/"/espacios/
// comas de lo que ya se escribió como texto, y vacía el % calculado viejo que ya no corresponde
// guardar).
//
// Idempotente: si una columna ya tiene el tipo correcto, o un valor ya está limpio, no se toca.
// Uso: php spark db:seed CorregirTiposCostosAlternativa1Seeder
class CorregirTiposCostosAlternativa1Seeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CUIDADO-DIURNO';

    /** identificador de tabla => [ids de columna que deben pasar a `decimal`] */
    private const A_DECIMAL = [
        '09.01.1' => ['ef994295-781c-467e-9b34-0707503382b5', 'b6cda42e-28e2-4672-a050-8c8b5ea5f947', 'fa3c6abc-78fa-4fc3-a630-23b57428723d', 'bb186ad8-9f08-4b60-995b-9ff3b9e067cc'],
        '09.01.2' => ['ef994295-781c-467e-9b34-0707503382b5', 'b6cda42e-28e2-4672-a050-8c8b5ea5f947', 'fa3c6abc-78fa-4fc3-a630-23b57428723d', 'bb186ad8-9f08-4b60-995b-9ff3b9e067cc'],
        '09.01.3' => ['ef994295-781c-467e-9b34-0707503382b5', 'bb186ad8-9f08-4b60-995b-9ff3b9e067cc'],
        '09.01.4' => ['ef994295-781c-467e-9b34-0707503382b5', 'bb186ad8-9f08-4b60-995b-9ff3b9e067cc'],
        '09.01.5' => ['ef994295-781c-467e-9b34-0707503382b5', 'bb186ad8-9f08-4b60-995b-9ff3b9e067cc'],
        '09.01.6' => ['ad5b811c-34c8-4b85-8e8a-4c87894de596'],
    ];

    /** identificador de tabla => id de columna que en realidad es una fórmula del Excel */
    private const A_CALCULADO = [
        '09.01.6' => '734fd8bc-a306-4532-bfb4-d401f8866507',
        '10.01.7' => 'pct_directo',
        '11.01.7' => 'pct_directo',
    ];

    public function run(): void
    {
        $tocadosEstructura = $this->actualizarEstructura();
        $tocadosDatos       = $this->limpiarDatosEjemplos();
        echo "Estructura: {$tocadosEstructura} columnas corregidas. Datos: {$tocadosDatos} celdas limpiadas." . PHP_EOL;
    }

    private function actualizarEstructura(): int
    {
        $tocados = 0;

        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla !== null && $plantilla['asignado_archivo_id']) {
            $archivo = $this->db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
            if ($archivo !== null && ! empty($archivo['contenido_json'])) {
                $contenido = json_decode((string) $archivo['contenido_json'], true);
                $t = 0;
                $this->recorrerEstructura($contenido, $t);
                if ($t > 0) {
                    $this->db->table('archivos')->where('id', $archivo['id'])->update([
                        'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
                    ]);
                }
                $tocados += $t;
            }
        }

        $ruta = ROOTPATH . 'app/Database/Seeds/data/plantillas_estructura.json';
        if (is_file($ruta)) {
            $contenido = json_decode((string) file_get_contents($ruta), true);
            if (is_array($contenido) && is_array($contenido['plantillas'] ?? null)) {
                $t = 0;
                foreach ($contenido['plantillas'] as &$plantillaSeed) {
                    if (($plantillaSeed['codigo'] ?? null) !== self::CODIGO_PLANTILLA) {
                        continue;
                    }
                    $contenidoJson = $plantillaSeed['contenidoJson'] ?? null;
                    $this->recorrerEstructura($contenidoJson, $t);
                    if ($contenidoJson !== null) {
                        $plantillaSeed['contenidoJson'] = $contenidoJson;
                    }
                }
                unset($plantillaSeed);
                if ($t > 0) {
                    file_put_contents($ruta, json_encode($contenido, JSON_UNESCAPED_UNICODE));
                }
                $tocados += $t;
            }
        }

        return $tocados;
    }

    private function recorrerEstructura(mixed &$nodo, int &$tocados): void
    {
        if (! is_array($nodo)) {
            return;
        }

        $identificador = $nodo['identificador'] ?? null;
        if (is_string($identificador)) {
            $columnas = &$nodo['configTabla']['columnas'];
            if (is_array($columnas)) {
                $idsADecimal = self::A_DECIMAL[$identificador] ?? [];
                $idCalculado = self::A_CALCULADO[$identificador] ?? null;
                foreach ($columnas as &$col) {
                    $colId = $col['id'] ?? null;
                    if (in_array($colId, $idsADecimal, true) && ($col['tipo'] ?? null) !== 'decimal') {
                        $col['tipo'] = 'decimal';
                        $tocados++;
                    }
                    if ($colId === $idCalculado && ($col['tipo'] ?? null) !== 'calculado') {
                        $col['tipo'] = 'calculado';
                        $tocados++;
                    }
                }
                unset($col);
            }
            unset($columnas);
        }

        foreach ($nodo as &$hijo) {
            $this->recorrerEstructura($hijo, $tocados);
        }
        unset($hijo);
    }

    private function limpiarDatosEjemplos(): int
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null) {
            return 0;
        }

        $tocados  = 0;
        $ejemplos = $this->db->table('ejemplos')->where('plantilla_id', $plantilla['id'])->get()->getResultArray();
        foreach ($ejemplos as $ejemplo) {
            $archivo = $this->db->table('archivos')
                ->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $ejemplo['id'])
                ->get()->getRowArray();
            if ($archivo === null || empty($archivo['contenido_json'])) {
                continue;
            }

            $contenido = json_decode((string) $archivo['contenido_json'], true);
            $valores   = $contenido['valores'] ?? [];
            $t         = 0;

            foreach (self::A_DECIMAL as $identificador => $idsColumna) {
                $this->limpiarTablaValores($valores, $identificador, $idsColumna, false, $t);
            }
            foreach (self::A_CALCULADO as $identificador => $idColumna) {
                $this->limpiarTablaValores($valores, $identificador, [$idColumna], true, $t);
            }

            if ($t > 0) {
                $contenido['valores'] = $valores;
                $this->db->table('archivos')->where('id', $archivo['id'])->update([
                    'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
                ]);
            }
            $tocados += $t;
        }

        return $tocados;
    }

    /** @param list<string> $idsColumna */
    private function limpiarTablaValores(array &$valores, string $identificador, array $idsColumna, bool $vaciar, int &$tocados): void
    {
        $raw = $valores[$identificador] ?? null;
        if (! is_string($raw) || $raw === '') {
            return;
        }
        $filas = json_decode($raw, true);
        if (! is_array($filas)) {
            return;
        }

        $cambiado = false;
        foreach ($filas as &$fila) {
            if (! is_array($fila)) {
                continue;
            }
            foreach ($idsColumna as $colId) {
                if (! array_key_exists($colId, $fila) || ! is_string($fila[$colId])) {
                    continue;
                }
                if ($vaciar) {
                    if ($fila[$colId] !== '') {
                        $fila[$colId] = '';
                        $cambiado      = true;
                        $tocados++;
                    }
                    continue;
                }
                $limpio = $this->limpiarNumero($fila[$colId]);
                if ($limpio !== $fila[$colId]) {
                    $fila[$colId] = $limpio;
                    $cambiado      = true;
                    $tocados++;
                }
            }
        }
        unset($fila);

        if ($cambiado) {
            $valores[$identificador] = json_encode($filas, JSON_UNESCAPED_UNICODE);
        }
    }

    /** "S/ 107,500" -> "107500". Deja intacto cualquier valor que no tenga pinta de moneda con
     * separador de miles (ej. ya limpio, vacío, o texto genuino que no debería estar ahí). */
    private function limpiarNumero(string $v): string
    {
        $t = trim($v);
        if ($t === '' || ! preg_match('/^S\/\.?\s*[\d,]+(\.\d+)?$/u', $t)) {
            return $v;
        }

        return str_replace([',', ' '], '', preg_replace('/^S\/\.?\s*/u', '', $t));
    }
}
