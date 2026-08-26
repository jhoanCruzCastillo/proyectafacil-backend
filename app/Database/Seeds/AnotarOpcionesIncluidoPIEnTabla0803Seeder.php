<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Bug real encontrado en vivo (2026-08-25): la tabla 08.03.1 (Análisis de Tecnología) de
// FTE-CUIDADO-DIURNO tiene una columna "¿Se incluye como parte del PI?" cuyo desplegable real del
// Excel solo acepta 2 frases ("Se incluye en el PI" / "No se incluye en el PI"), pero el JSON de
// estructura la tenía como texto_corto SIN `opciones` — la IA proponía "Sí"/"No" (razonable para
// una pregunta que suena sí/no, pero inválido para el VLOOKUP real del Excel).
//
// El llenado SÍNCRONO de una sola tabla (LlenadoIAController::llenarTabla(), botón "Llenar con IA"
// de una tabla) ya no dependía de esto: el frontend resuelve las opciones EN VIVO contra el Excel
// cargado (ver opcionesEstaticasTabla.ts) y las manda al backend. Pero el LOTE de toda la ficha
// (enviarLoteFicha(), el modal "Procesamiento con IA") corre 100% server-side sin navegador — no
// hay Excel vivo que resolver ahí, así que esa ruta SOLO conoce columnas con `opciones` en el JSON
// mismo (ver el comentario "Sin opcionesPorColumna/contextoAdicional del frontend" en
// enviarLoteFicha()). Para esta columna, `catalogoPorColumnaDe()` no tenía nada con qué validar en
// el lote — el catálogo real nunca se aplicaba ahí, sin importar qué tan reforzado estuviera el
// prompt ni qué tan estricto el validador (ver LlenadoIAController::compararForma()).
//
// La solución correcta y general: la lista NO depende de otra celda (no es un INDIRECT como los de
// la Sección 5) — es estática, así que corresponde que viva en el JSON de estructura como
// `opciones`, no que cada ruta de llenado tenga que resolverla aparte. Con esto en el JSON:
//   1. El lote server-side ya valida esta columna igual que el botón individual.
//   2. construirPromptTabla() ya muestra las opciones reales al modelo desde el primer intento
//      (antes dependía de que el frontend se las inyectara).
//   3. opcionesEstaticasTabla.ts sigue funcionando igual (su override gana si trae algo, pero ahora
//      es redundante para esta columna específica — no hace daño dejarlo).
//
// Mismo patrón que MarcarColumnasUbigeoCalculadasSeeder: actualiza la BD viva (EstructuraPlantillasSeeder
// se salta cuando la plantilla ya tiene asignado_archivo_id) y también data/plantillas_estructura.json
// para entornos sembrados desde cero.
//
// Idempotente: si la columna ya tiene estas opciones, no la vuelve a tocar.
// Uso: php spark db:seed AnotarOpcionesIncluidoPIEnTabla0803Seeder
class AnotarOpcionesIncluidoPIEnTabla0803Seeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CUIDADO-DIURNO';
    private const IDENTIFICADOR_TABLA = '08.03.1';
    private const ID_COLUMNA = 'f2a6788b-a18e-445e-8e2d-faac7001640e';
    private const OPCIONES = ['Se incluye en el PI', 'No se incluye en el PI'];

    public function run(): void
    {
        $this->actualizarBaseDeDatos();
        $this->actualizarArchivoSeed();
    }

    private function actualizarBaseDeDatos(): void
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null || ! $plantilla['asignado_archivo_id']) {
            echo 'No existe ' . self::CODIGO_PLANTILLA . ' con archivo asignado — nada que anotar en la BD.' . PHP_EOL;

            return;
        }

        $archivo = $this->db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        if ($archivo === null || empty($archivo['contenido_json'])) {
            echo 'La plantilla no tiene contenido_json — nada que anotar en la BD.' . PHP_EOL;

            return;
        }

        $contenido = json_decode((string) $archivo['contenido_json'], true);
        $tocados   = 0;
        $this->recorrer($contenido, $tocados);

        if ($tocados === 0) {
            echo 'BD: la columna ya tenía las opciones correctas (o no se encontró) — nada que hacer.' . PHP_EOL;

            return;
        }

        $this->db->table('archivos')->where('id', $archivo['id'])->update([
            'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
        ]);
        echo "BD: columna anotada con las {$tocados} opciones reales del Excel." . PHP_EOL;
    }

    private function actualizarArchivoSeed(): void
    {
        $ruta = ROOTPATH . 'app/Database/Seeds/data/plantillas_estructura.json';
        if (! is_file($ruta)) {
            echo 'No existe data/plantillas_estructura.json — nada que anotar ahí.' . PHP_EOL;

            return;
        }

        $contenido = json_decode((string) file_get_contents($ruta), true);
        if (! is_array($contenido)) {
            echo 'data/plantillas_estructura.json no decodifica a JSON válido — se deja intacto.' . PHP_EOL;

            return;
        }

        $tocados = 0;
        if (is_array($contenido['plantillas'] ?? null)) {
            // OJO: `foreach ($contenido['plantillas'] ?? [] as &$plantilla)` NO sirve acá — el `??`
            // convierte la expresión en un valor temporal, así que `&$plantilla` referencia esa copia
            // y nunca al array real dentro de `$contenido`; las mutaciones se pierden en silencio
            // (bug real encontrado en vivo: tocados>0 en cada corrida, pero el archivo nunca cambiaba).
            // Hace falta guardar primero que la clave existe y sea array, y recién ahí iterar la
            // clave real por referencia.
            foreach ($contenido['plantillas'] as &$plantilla) {
                if (($plantilla['codigo'] ?? null) !== self::CODIGO_PLANTILLA) {
                    continue;
                }
                $contenidoJson = $plantilla['contenidoJson'] ?? null;
                $this->recorrer($contenidoJson, $tocados);
                if ($contenidoJson !== null) {
                    $plantilla['contenidoJson'] = $contenidoJson;
                }
            }
            unset($plantilla);
        }

        if ($tocados === 0) {
            echo 'Archivo seed: la columna ya tenía las opciones correctas (o no se encontró) — nada que hacer.' . PHP_EOL;

            return;
        }

        file_put_contents($ruta, json_encode($contenido, JSON_UNESCAPED_UNICODE));
        echo "Archivo seed: columna anotada con las {$tocados} opciones reales del Excel." . PHP_EOL;
    }

    private function recorrer(mixed &$nodo, int &$tocados): void
    {
        if (! is_array($nodo)) {
            return;
        }

        if (($nodo['identificador'] ?? null) === self::IDENTIFICADOR_TABLA) {
            $columnas = &$nodo['configTabla']['columnas'];
            if (is_array($columnas)) {
                foreach ($columnas as &$col) {
                    if (($col['id'] ?? null) === self::ID_COLUMNA && ($col['opciones'] ?? null) !== self::OPCIONES) {
                        $col['opciones'] = self::OPCIONES;
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
