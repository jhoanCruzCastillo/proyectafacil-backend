<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Sincroniza el `contenido_json` de una plantilla (secciones/campos/descripciones/config) desde un
 * snapshot JSON versionado en el repo hacia la fila real de `archivos` en ESTE entorno.
 *
 * Existe porque "Ver JSON" / "Importar estructura desde JSON" del editor usan el esquema OFICIAL
 * de exportación a Excel (tipo_nodo/captura/config — ver `schemaExport.ts`/`schemaImport.ts` del
 * frontend), que nunca cargó la "Descripción / ayuda" por campo (es metadata del editor para el
 * llenado con IA, no algo que vaya al Excel) — por eso ese ida y vuelta no sirve para clonar una
 * plantilla completa de un entorno a otro (ej. de dev a producción). Este comando sí copia el
 * `contenido_json` entero tal cual, sin pasar por ese esquema.
 *
 * Uso: php spark ia:sincronizar-plantilla FTE-EBR-V03
 *      php spark ia:sincronizar-plantilla FTE-EBR-V03 /ruta/a/otro.json
 */
class SincronizarPlantilla extends BaseCommand
{
    protected $group       = 'IA';
    protected $name        = 'ia:sincronizar-plantilla';
    protected $description = 'Sobrescribe el contenido_json de una plantilla con un snapshot JSON versionado en el repo.';
    protected $usage       = 'ia:sincronizar-plantilla <codigoPlantilla> [rutaArchivo]';

    public function run(array $params)
    {
        $codigo = $params[0] ?? null;
        if ($codigo === null) {
            CLI::error('Uso: ' . $this->usage);

            return EXIT_ERROR;
        }

        $rutaDefault = ROOTPATH . 'app/Database/Seeds/data/' . strtolower(str_replace('-', '_', $codigo)) . '_contenido_actual.json';
        $ruta        = $params[1] ?? $rutaDefault;
        if (! is_file($ruta)) {
            CLI::error("No existe el archivo: {$ruta}");

            return EXIT_ERROR;
        }

        $contenido    = file_get_contents($ruta);
        $decodificado = json_decode((string) $contenido, true);
        if (! is_array($decodificado) || ! isset($decodificado['secciones'])) {
            CLI::error('El archivo no es un contenido_json válido (falta la clave "secciones").');

            return EXIT_ERROR;
        }

        $db        = db_connect();
        $plantilla = $db->table('plantillas')->where('codigo', $codigo)->get()->getRowArray();
        if ($plantilla === null) {
            CLI::error("No existe ninguna plantilla con código {$codigo} en este entorno.");

            return EXIT_ERROR;
        }
        if (empty($plantilla['asignado_archivo_id'])) {
            CLI::error("La plantilla {$codigo} no tiene un archivo asignado en este entorno.");

            return EXIT_ERROR;
        }

        $antes         = $db->table('archivos')->select('contenido_json')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        $camposAntes   = self::contarCampos(json_decode((string) ($antes['contenido_json'] ?? '{}'), true) ?? []);
        $camposDespues = self::contarCampos($decodificado);

        $db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->update([
            'contenido_json' => $contenido,
        ]);

        CLI::write(sprintf(
            'Listo. %s: %d campos (%d con descripción) -> %d campos (%d con descripción).',
            $codigo,
            $camposAntes['total'],
            $camposAntes['conDescripcion'],
            $camposDespues['total'],
            $camposDespues['conDescripcion'],
        ), 'green');

        return EXIT_SUCCESS;
    }

    /** @return array{total: int, conDescripcion: int} */
    private static function contarCampos(array $contenido): array
    {
        $total          = 0;
        $conDescripcion = 0;
        foreach ($contenido['secciones'] ?? [] as $s) {
            foreach ($s['subsecciones'] ?? [] as $sub) {
                foreach ($sub['campos'] ?? [] as $c) {
                    $total++;
                    if (trim((string) ($c['descripcion'] ?? '')) !== '') {
                        $conDescripcion++;
                    }
                }
            }
        }

        return ['total' => $total, 'conDescripcion' => $conDescripcion];
    }
}
