<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Bug real encontrado en vivo (2026-08-25), misma familia que 08.03.1 (ver
// AnotarOpcionesIncluidoPIEnTabla0803Seeder) y los #VALUE!/#DIV0! de Costos: en la tabla 15.02.1
// (Gestión integral de los riesgos, hoja Sostenibilidad) las columnas "Probabilidad de ocurrencia"
// e "Impacto" tienen desplegable real en el Excel (validación de datos por lista, contra los
// rangos con nombre `Probabilidad` = Listas!$E$3:$E$7 = {0.9, 0.7, 0.5, 0.3, 0.1} e `Impacto` =
// Listas!$D$3:$D$7 = {0.05, 0.1, 0.2, 0.4, 0.8}), pero el JSON de estructura las tenía como
// texto_corto SIN `opciones` — la IA (y quien llenó a mano) escribía etiquetas de texto ("Muy
// Baja", "Media") en vez de los números reales que exige el Excel.
//
// Además, "Estimación de riesgo" (Sostenibilidad!L17 = +K17*J17, Probabilidad×Impacto) es una
// fórmula real del Excel — estaba tipada texto_corto en vez de `calculado`, así que también se
// llenaba con una etiqueta de texto inventada en vez de dejar que el Excel la calcule.
//
// Corrige la estructura (BD viva + archivo semilla) y limpia los datos ya guardados: convierte
// las etiquetas de texto de Probabilidad/Impacto a su número real (misma escala de 5 niveles que
// ya usaba la IA, solo le faltaba el valor numérico correcto) y vacía "Estimación de riesgo" (ya
// no corresponde guardar nada ahí, la protección de columna calculado se encarga).
//
// Idempotente: si una columna ya tiene el tipo correcto, o un valor ya está en la escala numérica
// real, no se toca.
// Uso: php spark db:seed CorregirRiesgosSostenibilidadSeeder
class CorregirRiesgosSostenibilidadSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CUIDADO-DIURNO';
    private const IDENTIFICADOR_TABLA = '15.02.1';

    private const ID_PROBABILIDAD = '8c00726b-955d-4300-8b5c-e1aaba26245d';
    private const ID_IMPACTO = '1e79a02a-bf1a-424e-b8f9-90558d97cb93';
    private const ID_ESTIMACION = 'cdd01523-795f-4616-897c-72f6148ec733';

    private const OPCIONES_PROBABILIDAD = ['0.90', '0.70', '0.50', '0.30', '0.10'];
    private const OPCIONES_IMPACTO = ['0.05', '0.10', '0.20', '0.40', '0.80'];

    /** Etiqueta de texto (tal como la escribía la IA) => número real de la escala del Excel. Misma
     * escala de 5 niveles, solo le faltaba el valor numérico — no se pierde la intención original. */
    private const MAPA_PROBABILIDAD = [
        'muy baja' => '0.10',
        'baja'     => '0.30',
        'media'    => '0.50',
        'alta'     => '0.70',
        'muy alta' => '0.90',
    ];
    private const MAPA_IMPACTO = [
        'muy bajo' => '0.05',
        'bajo'     => '0.10',
        'medio'    => '0.20',
        'alto'     => '0.40',
        'muy alto' => '0.80',
    ];

    public function run(): void
    {
        $tocadosEstructura = $this->actualizarEstructura();
        $tocadosDatos       = $this->limpiarDatosEjemplos();
        echo "Estructura: {$tocadosEstructura} columnas corregidas. Datos: {$tocadosDatos} celdas corregidas." . PHP_EOL;
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

        if (($nodo['identificador'] ?? null) === self::IDENTIFICADOR_TABLA) {
            $columnas = &$nodo['configTabla']['columnas'];
            if (is_array($columnas)) {
                foreach ($columnas as &$col) {
                    $colId = $col['id'] ?? null;
                    if ($colId === self::ID_PROBABILIDAD && ($col['opciones'] ?? null) !== self::OPCIONES_PROBABILIDAD) {
                        $col['opciones'] = self::OPCIONES_PROBABILIDAD;
                        $tocados++;
                    }
                    if ($colId === self::ID_IMPACTO && ($col['opciones'] ?? null) !== self::OPCIONES_IMPACTO) {
                        $col['opciones'] = self::OPCIONES_IMPACTO;
                        $tocados++;
                    }
                    if ($colId === self::ID_ESTIMACION && ($col['tipo'] ?? null) !== 'calculado') {
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
            $raw       = $valores[self::IDENTIFICADOR_TABLA] ?? null;
            if (! is_string($raw) || $raw === '') {
                continue;
            }
            $filas = json_decode($raw, true);
            if (! is_array($filas)) {
                continue;
            }

            $t = 0;
            foreach ($filas as &$fila) {
                if (! is_array($fila)) {
                    continue;
                }
                $this->corregirCelda($fila, self::ID_PROBABILIDAD, self::MAPA_PROBABILIDAD, $t);
                $this->corregirCelda($fila, self::ID_IMPACTO, self::MAPA_IMPACTO, $t);
                if (array_key_exists(self::ID_ESTIMACION, $fila) && $fila[self::ID_ESTIMACION] !== '') {
                    $fila[self::ID_ESTIMACION] = '';
                    $t++;
                }
            }
            unset($fila);

            if ($t > 0) {
                $valores[self::IDENTIFICADOR_TABLA] = json_encode($filas, JSON_UNESCAPED_UNICODE);
                $contenido['valores'] = $valores;
                $this->db->table('archivos')->where('id', $archivo['id'])->update([
                    'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
                ]);
            }
            $tocados += $t;
        }

        return $tocados;
    }

    /** @param array<string,string> $mapa etiqueta en minúsculas => número real */
    private function corregirCelda(array &$fila, string $colId, array $mapa, int &$tocados): void
    {
        if (! array_key_exists($colId, $fila) || ! is_string($fila[$colId]) || $fila[$colId] === '') {
            return;
        }
        $numero = $mapa[mb_strtolower(trim($fila[$colId]))] ?? null;
        if ($numero !== null && $fila[$colId] !== $numero) {
            $fila[$colId] = $numero;
            $tocados++;
        }
    }
}
