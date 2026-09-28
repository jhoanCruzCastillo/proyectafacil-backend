<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Repara las tablas que quedaron con `valorEjemplo` vacío aunque su `configTabla.captura.filasBase`
// dice cuántas filas tiene la tabla en el Excel.
//
// Por qué importa: el llenado con IA valida la respuesta del modelo contra el valor ACTUAL de la
// tabla (LlenadoIAController::compararForma()), y esa comparación exige la MISMA cantidad de filas.
// Si el valor actual es `[]`, cualquier respuesta con contenido se rechaza con
// «En "valor": se esperaban 0 elementos, la IA devolvió N» — la tabla es literalmente imposible de
// llenar, por buena que sea la respuesta.
//
// Encontrado en vivo el 2026-09-28 probando el Formato 07-C (trabajo 27): el modelo devolvió los 4
// activos correctos para la tabla 3.02.01 — la que decide el tipo de IOARR y por lo tanto todo el
// resto del formato — y se descartaron enteros. Al revisar el resto del catálogo aparecieron 49
// tablas en 7 plantillas con el mismo defecto de importación.
//
// La reparación es neutra: se crean `filasBase` filas con TODAS las columnas en cadena vacía. No
// inventa contenido — solo le da a la tabla la forma que el Excel ya declara, que es justo lo que
// compararForma() necesita para dejar entrar la respuesta.
//
// Idempotente: solo toca tablas cuyo `valorEjemplo` está vacío o ausente. Correrlo dos veces no
// pisa nada, y una tabla que el usuario ya llenó nunca se toca.
//
// Uso: php spark db:seed RellenarFilasBaseTablasSeeder
class RellenarFilasBaseTablasSeeder extends Seeder
{
    public function run(): void
    {
        $archivos = $this->db->table('archivos a')
            ->select('a.id, a.contenido_json, p.codigo')
            ->join('plantillas p', 'p.asignado_archivo_id = a.id')
            ->where('a.contenido_json IS NOT NULL')
            ->get()->getResultArray();

        $totalTablas   = 0;
        $totalArchivos = 0;
        $saltadas      = [];

        foreach ($archivos as $archivo) {
            $contenido = json_decode((string) $archivo['contenido_json'], true);
            if (! is_array($contenido)) {
                continue;
            }

            $reparadas = [];

            $contenido['secciones'] ??= [];
            foreach ($contenido['secciones'] as &$seccion) {
                $seccion['subsecciones'] ??= [];
                foreach ($seccion['subsecciones'] as &$sub) {
                    $sub['campos'] ??= [];
                    foreach ($sub['campos'] as &$campo) {
                        $nuevo = $this->filasBaseFaltantes($campo, $archivo['codigo'], $saltadas);
                        if ($nuevo === null) {
                            continue;
                        }
                        $campo['valorEjemplo'] = json_encode($nuevo, JSON_UNESCAPED_UNICODE);
                        $reparadas[]           = (string) $campo['identificador'] . '(' . count($nuevo) . ')';
                    }
                    unset($campo);
                }
                unset($sub);
            }
            unset($seccion);

            if ($reparadas === []) {
                continue;
            }

            $this->db->table('archivos')->where('id', $archivo['id'])->update([
                'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
            ]);
            $totalArchivos++;
            $totalTablas += count($reparadas);
            echo sprintf('  %-22s %d tabla(s): %s' . PHP_EOL, $archivo['codigo'], count($reparadas), implode(', ', $reparadas));
        }

        foreach ($saltadas as $aviso) {
            echo '  AVISO — ' . $aviso . PHP_EOL;
        }

        echo $totalTablas === 0
            ? 'No había tablas sin filas base — nada que reparar.' . PHP_EOL
            : sprintf('Listo — %d tabla(s) reparadas en %d plantilla(s).' . PHP_EOL, $totalTablas, $totalArchivos);
    }

    /**
     * Devuelve las filas vacías que le faltan a este campo, o null si no hay nada que hacer.
     *
     * @param list<string> $saltadas se le añaden los casos que esta reparación no cubre
     *
     * @return list<array<string,string>>|null
     */
    private function filasBaseFaltantes(array $campo, string $codigo, array &$saltadas): ?array
    {
        if (($campo['tipo'] ?? '') !== 'tabla') {
            // Las jerárquicas guardan un árbol, no una lista de filas: darles `filasBase` filas planas
            // las rompería. Si alguna aparece con el mismo defecto, se avisa para tratarla aparte.
            if (($campo['tipo'] ?? '') === 'tabla_jerarquica' && $this->esTablaSinFilas($campo)) {
                $saltadas[] = "{$codigo} {$campo['identificador']}: es jerárquica, se salta (hay que reconstruir el árbol a mano).";
            }

            return null;
        }
        if (! $this->esTablaSinFilas($campo)) {
            return null;
        }

        $config    = $campo['configTabla'] ?? [];
        $filasBase = (int) ($config['captura']['filasBase'] ?? 0);
        $columnas  = $config['columnas'] ?? [];
        if ($columnas === []) {
            $saltadas[] = "{$codigo} {$campo['identificador']}: declara filasBase={$filasBase} pero no tiene columnas.";

            return null;
        }

        $filaVacia = [];
        foreach ($columnas as $col) {
            $id = (string) ($col['id'] ?? '');
            if ($id !== '') {
                $filaVacia[$id] = '';
            }
        }

        return array_fill(0, $filasBase, $filaVacia);
    }

    /** Declara filas en el Excel (`filasBase`) pero su valor guardado está vacío. */
    private function esTablaSinFilas(array $campo): bool
    {
        $filasBase = (int) (($campo['configTabla']['captura']['filasBase'] ?? 0));
        if ($filasBase <= 0) {
            return false;
        }
        $valor = $campo['valorEjemplo'] ?? null;
        if ($valor === null || trim((string) $valor) === '') {
            return true;
        }
        $decodificado = json_decode((string) $valor, true);

        return is_array($decodificado) && $decodificado === [];
    }
}
