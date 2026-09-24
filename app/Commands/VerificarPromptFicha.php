<?php

namespace App\Commands;

use App\Controllers\LlenadoIAController;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use ReflectionMethod;

/**
 * Imprime el prompt de usuario que el llenado con IA le mandaría al modelo para un campo o una
 * tabla de una ficha, SIN llamar al modelo.
 *
 * Existe porque la única forma de comprobar que la "Descripción / ayuda" que el admin escribe por
 * campo llega de verdad al prompt era gastar una llamada real (y cuota) y leer el log. Con esto se
 * verifica la parte determinista —que el texto viaja— y se reserva la llamada real para comprobar
 * la calidad de la respuesta.
 *
 * Uso:
 *   php spark ia:verificar-prompt FTE-EBR-V03 06.02.1     (una tabla)
 *   php spark ia:verificar-prompt FTE-EBR-V03 seccion:06  (todos los campos simples de la sección)
 */
class VerificarPromptFicha extends BaseCommand
{
    protected $group       = 'IA';
    protected $name        = 'ia:verificar-prompt';
    protected $description = 'Muestra el prompt de usuario del llenado con IA para un campo/tabla, sin llamar al modelo.';
    protected $usage       = 'ia:verificar-prompt <codigoPlantilla> <identificador|seccion:N>';

    public function run(array $params)
    {
        $codigo = $params[0] ?? null;
        $objeto = $params[1] ?? null;
        if ($codigo === null || $objeto === null) {
            CLI::error('Uso: ' . $this->usage);

            return EXIT_ERROR;
        }

        $db        = db_connect();
        $plantilla = $db->table('plantillas')->where('codigo', $codigo)->get()->getRowArray();
        if ($plantilla === null || empty($plantilla['asignado_archivo_id'])) {
            CLI::error("No existe {$codigo} con archivo asignado.");

            return EXIT_ERROR;
        }
        $archivo   = $db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        $contenido = json_decode((string) $archivo['contenido_json'], true);

        $ctrl = new LlenadoIAController();

        if (str_starts_with($objeto, 'seccion:')) {
            $numero = substr($objeto, 8);
            foreach ($contenido['secciones'] ?? [] as $s) {
                if ((string) ($s['numero'] ?? '') !== $numero) {
                    continue;
                }
                $campos = [];
                foreach ($s['subsecciones'] ?? [] as $sub) {
                    foreach ($sub['campos'] ?? [] as $c) {
                        if (($c['tipo'] ?? '') !== 'tabla' && ($c['tipo'] ?? '') !== 'nota') {
                            $campos[] = $c;
                        }
                    }
                }
                $m = new ReflectionMethod($ctrl, 'construirPromptSeccion');
                $m->setAccessible(true);
                CLI::write($m->invoke($ctrl, (string) $s['id'], (string) $s['nombre'], $campos));

                return EXIT_SUCCESS;
            }
            CLI::error("No se encontró la sección {$numero}.");

            return EXIT_ERROR;
        }

        foreach ($contenido['secciones'] ?? [] as $s) {
            foreach ($s['subsecciones'] ?? [] as $sub) {
                foreach ($sub['campos'] ?? [] as $c) {
                    if ((string) ($c['identificador'] ?? '') !== $objeto) {
                        continue;
                    }
                    if (($c['tipo'] ?? '') !== 'tabla') {
                        CLI::error("{$objeto} no es una tabla; usa seccion:N para ver campos simples.");

                        return EXIT_ERROR;
                    }
                    $cfg = $c['configTabla'];
                    $m   = new ReflectionMethod($ctrl, 'construirPromptTabla');
                    $m->setAccessible(true);
                    CLI::write($m->invoke(
                        $ctrl,
                        $c,
                        (string) ($cfg['subtipo'] ?? 'filas_dinamicas'),
                        (bool) ($cfg['agrupador'] ?? false),
                        $cfg['columnas'] ?? [],
                        []
                    ));

                    return EXIT_SUCCESS;
                }
            }
        }
        CLI::error("No se encontró el campo {$objeto}.");

        return EXIT_ERROR;
    }
}
