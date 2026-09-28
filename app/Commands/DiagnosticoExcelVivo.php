<?php

namespace App\Commands;

use App\Libraries\ExcelVivoService;
use App\Libraries\S3ObjectStore;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Prueba puntual de ExcelVivoService contra el Excel real de una plantilla — para validar, ANTES de
 * integrarlo al worker de llenado async, que PhpSpreadsheet resuelve INDIRECT/nombres definidos
 * correctamente contra los archivos reales del proyecto (riesgo principal de la Fase 0, ver el plan).
 *
 * Uso: php spark ia:diagnostico-excel-vivo <codigoPlantilla> <hoja> <celda> [valorParaCeldaDependida hoja!celda]
 */
class DiagnosticoExcelVivo extends BaseCommand
{
    protected $group       = 'IA';
    protected $name        = 'ia:diagnostico-excel-vivo';
    protected $description = 'Prueba ExcelVivoService::opcionesDe()/calculado() contra el Excel real de una plantilla.';

    public function run(array $params)
    {
        $codigo = $params[0] ?? null;
        $hoja   = $params[1] ?? null;
        $celda  = $params[2] ?? null;
        if ($codigo === null || $hoja === null || $celda === null) {
            CLI::error('Uso: ia:diagnostico-excel-vivo <codigoPlantilla> <hoja> <celda> [hoja!celda=valor ...]');

            return EXIT_ERROR;
        }

        $db        = db_connect();
        $plantilla = $db->table('plantillas')->where('codigo', $codigo)->get()->getRowArray();
        if ($plantilla === null || empty($plantilla['asignado_archivo_id'])) {
            CLI::error("No existe {$codigo} con archivo asignado.");

            return EXIT_ERROR;
        }
        $archivo = $db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        CLI::write("Archivo: {$archivo['url']}", 'yellow');

        $rutaLocal = null;
        if (S3ObjectStore::esStoredS3($archivo['url'])) {
            $rutaLocal = (new S3ObjectStore())->descargarATemp($archivo['url']);
        } else {
            CLI::error('Este archivo no está en S3 — este diagnóstico solo cubre ese caso por ahora.');

            return EXIT_ERROR;
        }
        CLI::write("Descargado a: {$rutaLocal}", 'yellow');

        $inicio = microtime(true);
        $excel  = new ExcelVivoService($rutaLocal);
        CLI::write('Cargado en ' . round(microtime(true) - $inicio, 2) . 's', 'yellow');

        // Overrides opcionales: "Problema-Objetivo!B44=Educación Inicial"
        foreach (array_slice($params, 3) as $par) {
            if (! str_contains($par, '=')) {
                continue;
            }
            [$ref, $valor] = explode('=', $par, 2);
            if (! str_contains($ref, '!')) {
                continue;
            }
            [$h, $c] = explode('!', $ref, 2);
            $excel->escribirValor($h, $c, $valor);
            CLI::write("Escrito {$h}!{$c} = {$valor}", 'yellow');
        }

        CLI::write("--- opcionesDe({$hoja}, {$celda}) ---", 'green');
        $opciones = $excel->opcionesDe($hoja, $celda);
        if ($opciones === null) {
            CLI::write('null (sin validación de lista, o no se pudo resolver)', 'red');
        } else {
            CLI::write(count($opciones) . ' opciones: ' . implode(' | ', array_slice($opciones, 0, 20)) . (count($opciones) > 20 ? ' …' : ''));
        }

        CLI::write("--- calculado({$hoja}, {$celda}) ---", 'green');
        $calc = $excel->calculado($hoja, $celda);
        CLI::write(is_scalar($calc) ? (string) $calc : json_encode($calc));

        @unlink($rutaLocal);

        return EXIT_SUCCESS;
    }
}
