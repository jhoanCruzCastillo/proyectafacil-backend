<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * Equivalente en PHP de `frontend/src/composables/useListasExcel.ts` (tipo `ExcelVivo`) — resuelve
 * listas desplegables (incluidas las que dependen de `INDIRECT`, ver `xlsxListas.ts::resolverFormula`)
 * y valores calculados contra el Excel REAL de una plantilla, para que el worker de llenado con IA
 * pueda ofrecer el mismo catálogo de opciones que ya ve el cliente en el editor, sin necesitar el
 * navegador.
 *
 * A diferencia del frontend (que reimplementa su propio motor de fórmulas en `excelFormulaEval.ts`),
 * acá se delega la evaluación real de fórmulas a `PhpSpreadsheet\Calculation` — ya es dependencia del
 * backend (import/export de Candidatos) y trae soporte nativo de `INDIRECT`/`VLOOKUP`. Lo que SÍ se
 * porta a mano es la lógica de "qué resolver" (literal / INDIRECT / nombre definido / rango directo),
 * calcada de `resolverFormula()` en `xlsxListas.ts`.
 */
class ExcelVivoService
{
    private Spreadsheet $libro;
    private Calculation $calc;

    /** Cache de listas ya resueltas dentro de esta instancia (vive lo que dure un trabajo de llenado). */
    private array $cacheListas = [];

    public function __construct(string $rutaXlsxLocal)
    {
        if (! is_file($rutaXlsxLocal)) {
            throw new RuntimeException("No existe el archivo Excel: {$rutaXlsxLocal}");
        }
        $reader = IOFactory::createReaderForFile($rutaXlsxLocal);
        $reader->setReadDataOnly(false); // hace falta leer fórmulas y validaciones, no solo valores
        $this->libro = $reader->load($rutaXlsxLocal);
        $this->calc  = Calculation::getInstance($this->libro);
    }

    private function hoja(string $nombre): ?Worksheet
    {
        return $this->libro->getSheetByName($nombre);
    }

    /** Escribe un valor crudo en `hoja!ref` — para dejar el libro en el mismo estado que tiene la
     * ficha antes de pedir cálculos u opciones que dependan de otras celdas. */
    public function escribirValor(string $hoja, string $ref, string $valor): void
    {
        $ws = $this->hoja($hoja);
        if ($ws === null) {
            return;
        }
        $ws->getCell($ref)->setValue($valor);
    }

    /** Valor que el Excel calcularía en esa celda (o null si no aplica / la hoja no existe). */
    public function calculado(string $hoja, string $ref): mixed
    {
        $ws = $this->hoja($hoja);
        if ($ws === null) {
            return null;
        }
        try {
            return $ws->getCell($ref)->getCalculatedValue();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Igual que `opcionesDe()`, pero simulando que ciertas celdas tuvieran otro valor (sin dejar el
     * cambio permanente) — mismo contrato que `ExcelVivo.opcionesDeConOverride()` del frontend: sirve
     * para catálogos en cascada de más de un nivel donde todavía no se sabe qué va a elegir la IA en
     * la celda de la que depende (ver `resolverCascada()` en LlenadoIAController).
     *
     * @param array<string,string> $overrides `hoja!ref` => valor hipotético
     * @return string[]|null
     */
    public function opcionesDeConOverride(string $hoja, string $ref, array $overrides): ?array
    {
        $originales = [];
        foreach ($overrides as $clave => $valor) {
            [$h, $r] = explode('!', $clave, 2);
            $ws      = $this->hoja($h);
            if ($ws === null) {
                continue;
            }
            $originales[$clave] = $ws->getCell($r)->getValue();
            $ws->getCell($r)->setValue($valor);
        }

        try {
            return $this->opcionesDe($hoja, $ref);
        } finally {
            foreach ($originales as $clave => $valorOriginal) {
                [$h, $r] = explode('!', $clave, 2);
                $this->hoja($h)?->getCell($r)->setValue($valorOriginal);
            }
        }
    }

    /**
     * Opciones del desplegable de esa celda, o null si no tiene validación de lista o no se pudo
     * resolver — mismo contrato que `ExcelVivo.opcionesDe()` del frontend.
     *
     * @return string[]|null
     */
    public function opcionesDe(string $hoja, string $ref): ?array
    {
        $ws = $this->hoja($hoja);
        if ($ws === null) {
            return null;
        }
        $validacion = $ws->getCell($ref)->getDataValidation();
        $formula1   = trim($validacion->getFormula1());
        if ($formula1 === '') {
            return null;
        }

        return $this->resolverFormula($formula1, $hoja, new \SplObjectStorage());
    }

    /**
     * Puerto de `resolverFormula()` (xlsxListas.ts) — decide qué tipo de expresión es la validación
     * y la resuelve a una lista concreta de opciones. `$visitados` evita ciclos entre nombres
     * definidos que se apunten entre sí.
     *
     * @return string[]|null
     */
    private function resolverFormula(string $texto, string $hojaBase, \SplObjectStorage $visitados, int $profundidad = 0): ?array
    {
        $texto = trim($texto);
        if ($texto === '' || $profundidad > 8) {
            return null;
        }

        // Literal en línea: "Gobierno Nacional,Gobierno Regional,Gobierno Local"
        if (str_starts_with($texto, '"')) {
            $sinComillas = trim($texto, '"');
            $opciones    = array_values(array_filter(array_map('trim', explode(',', $sinComillas)), static fn ($o) => $o !== ''));

            return $opciones !== [] ? $opciones : null;
        }

        // INDIRECT(x) / INDIRECTO(x) [, estiloA1] — se evalúa solo el primer argumento con el motor
        // de cálculo real; el resultado es el nombre/rango de destino, que se vuelve a resolver.
        if (preg_match('/\b(INDIRECT|INDIRECTO)\s*\(/i', $texto, $m, PREG_OFFSET_CAPTURE)) {
            $abre = $m[0][1] + strlen($m[0][0]) - 1;
            $arg  = $this->argumentoDe($texto, $abre);
            if ($arg === null) {
                return null;
            }
            $expr = $this->primerArgumento($arg);
            if ($expr === '') {
                return null;
            }
            try {
                $destino = $this->calc->calculateFormula('=' . $expr, null, $this->hoja($hojaBase)?->getCell('A1'));
            } catch (\Throwable) {
                return null;
            }
            // calculateFormula() puede devolver un array cuando PhpSpreadsheet interpreta el
            // resultado como un rango/celda implícita (ej. una referencia simple "Listas!$D$44") en
            // vez de un escalar — se aplana y se toma el primer valor no vacío, igual que leería una
            // sola celda.
            if (is_array($destino)) {
                $plano   = array_merge(...array_map(static fn ($f) => is_array($f) ? array_values($f) : [$f], $destino));
                $destino = $plano[0] ?? '';
            }
            $destino = is_string($destino) ? trim($destino) : (string) $destino;
            if ($destino === '' || str_starts_with($destino, '#')) {
                return null; // celda de la que depende todavía vacía / #N/A — mismo criterio que el frontend
            }

            return $this->resolverFormula($destino, $hojaBase, $visitados, $profundidad + 1);
        }

        // Nombre definido (rango con nombre, ej. "NivelGobierno" -> Listas!$J$3:$J$5).
        $definido = $this->libro->getDefinedName($texto) ?? $this->libro->getDefinedName($texto, $this->hoja($hojaBase));
        if ($definido instanceof NamedRange) {
            return $this->leerRango($definido->getRange(), $hojaBase);
        }

        // Rango directo: 'Hoja'!$A$3:$A$10 | Listas!$J$3:$J$5 | $A$3:$A$10
        if (preg_match('/^(?:(?:\'([^\']+)\'|([^\'!]+))!)?\$?[A-Z]+\$?\d+(?::\$?[A-Z]+\$?\d+)?$/i', $texto)) {
            return $this->leerRango($texto, $hojaBase);
        }

        return null;
    }

    /** Lee un rango (con o sin hoja explícita) y devuelve los textos no vacíos, en orden. */
    private function leerRango(string $rangoTexto, string $hojaBase): ?array
    {
        $hojaNombre = $hojaBase;
        $celdas     = $rangoTexto;
        if (preg_match('/^(?:\'([^\']+)\'|([^\'!]+))!(.+)$/', $rangoTexto, $m)) {
            $hojaNombre = $m[1] !== '' ? $m[1] : $m[2];
            $celdas     = $m[3];
        }
        $ws = $this->hoja($hojaNombre);
        if ($ws === null) {
            return null;
        }
        $celdas = str_replace('$', '', $celdas);
        $filas  = $ws->rangeToArray($celdas, null, true, false);
        $out    = [];
        foreach ($filas as $fila) {
            foreach ($fila as $valor) {
                $texto = trim((string) $valor);
                if ($texto === '' || str_starts_with($texto, '#')) {
                    continue;
                }
                $out[] = $texto;
                if (count($out) >= 5000) {
                    return $out; // mismo tope de seguridad que MAX_OPCIONES en xlsxListas.ts
                }
            }
        }

        return $out !== [] ? $out : null;
    }

    /** Puerto de `argumentoDe()` — texto del argumento de una llamada, respetando paréntesis anidados. */
    private function argumentoDe(string $texto, int $posAbre): ?string
    {
        $nivel = 0;
        $len   = strlen($texto);
        for ($i = $posAbre; $i < $len; $i++) {
            if ($texto[$i] === '(') {
                $nivel++;
            } elseif ($texto[$i] === ')') {
                $nivel--;
                if ($nivel === 0) {
                    return substr($texto, $posAbre + 1, $i - $posAbre - 1);
                }
            }
        }

        return null;
    }

    /** Puerto de `primerArgumento()` — primer argumento de una lista `a, b, …`, respetando paréntesis y comillas. */
    private function primerArgumento(string $args): string
    {
        $nivel       = 0;
        $enComillas  = false;
        $len         = strlen($args);
        for ($i = 0; $i < $len; $i++) {
            $ch = $args[$i];
            if ($ch === '"' && ($i === 0 || $args[$i - 1] !== '\\')) {
                $enComillas = ! $enComillas;
                continue;
            }
            if ($enComillas) {
                continue;
            }
            if ($ch === '(') {
                $nivel++;
            } elseif ($ch === ')') {
                $nivel--;
            } elseif ($ch === ',' && $nivel === 0) {
                return trim(substr($args, 0, $i));
            }
        }

        return trim($args);
    }
}
