<?php

namespace App\Commands;

use App\Libraries\SincronizadorContenidoPlantillas;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

// Trae a la BD actual el contenido de plantillas de data/plantillas_estructura.json SIN borrar
// usuarios ni fichas de clientes (ver SincronizadorContenidoPlantillas).
//
// Por defecto es una SIMULACIÓN: no escribe nada, solo informa qué estructuras cambiarían y
// cuántas fichas de clientes se verían afectadas. Con --aplicar escribe.
//
// Uso: php spark plantillas:sincronizar-contenido            (simulación)
//      php spark plantillas:sincronizar-contenido --aplicar  (escribe)
class SincronizarContenidoPlantillasCommand extends BaseCommand
{
    protected $group       = 'app';
    protected $name        = 'plantillas:sincronizar-contenido';
    protected $description = 'Actualiza estructuras y ejemplos de referencia de plantillas desde el JSON versionado, sin tocar usuarios ni fichas de clientes. Simulación salvo --aplicar.';
    protected $usage       = 'plantillas:sincronizar-contenido [--aplicar]';
    protected $options     = ['--aplicar' => 'Escribe los cambios (sin esta opción solo simula).'];

    public function run(array $params)
    {
        $aplicar = array_key_exists('aplicar', $params) || CLI::getOption('aplicar') !== null;

        CLI::write($aplicar ? 'MODO APLICAR — se escribirá en la BD.' : 'MODO SIMULACIÓN — no se escribe nada (usa --aplicar para escribir).', $aplicar ? 'yellow' : 'cyan');

        $resumen = (new SincronizadorContenidoPlantillas(db_connect(), $aplicar, static fn (string $l) => CLI::write($l)))->ejecutar();

        CLI::write('');
        foreach ($resumen as $clave => $n) {
            CLI::write(sprintf('  %-28s %d', str_replace('_', ' ', $clave), $n));
        }
    }
}
