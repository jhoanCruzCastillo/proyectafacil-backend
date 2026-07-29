<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

// Comando de exportación (solo lectura) — vuelca el contenido REAL que hoy vive en la BD local
// (construido a mano en el editor, sesión tras sesión) a un JSON versionable, para que
// EstructuraPlantillasSeeder pueda reproducirlo en cualquier otro ambiente (ej. Railway) sin
// depender de Cloudinary ni de que alguien vuelva a construir las secciones a mano.
//
// Exporta:
// - `plantillas`: cada plantilla con asignado_archivo_id (su archivo de estructura) -> contenido_json.
// - `ejemplos`: SOLO los de referencia (propietario_usuario_id IS NULL — autorados por el admin,
//   no fichas de un cliente real) junto con su propio archivo 1:1 si lo tiene.
//
// Uso: php spark plantillas:export-estructura
class ExportPlantillasEstructuraCommand extends BaseCommand
{
    protected $group       = 'app';
    protected $name        = 'plantillas:export-estructura';
    protected $description = 'Exporta a JSON el contenido_json de plantillas/ejemplos actualmente en la BD (solo lectura).';

    public function run(array $params)
    {
        $db = db_connect();

        $plantillasFilas = $db->table('plantillas p')
            ->select('p.codigo, a.nombre as archivo_nombre, a.url as archivo_url, a.contenido_json')
            ->join('archivos a', 'a.id = p.asignado_archivo_id')
            ->where('p.asignado_archivo_id IS NOT NULL', null, false)
            ->orderBy('p.codigo', 'ASC')
            ->get()->getResultArray();

        $plantillas = array_map(static fn (array $f) => [
            'codigo'        => $f['codigo'],
            'archivoNombre' => $f['archivo_nombre'],
            'archivoUrl'    => $f['archivo_url'],
            'contenidoJson' => $f['contenido_json'] !== null ? json_decode($f['contenido_json']) : null,
        ], $plantillasFilas);

        $ejemplosFilas = $db->table('ejemplos e')
            ->select('e.id, e.nombre, e.subtitulo, e.detalle, e.activo, e.compartida, e.estado, p.codigo as plantilla_codigo, a.contenido_json')
            ->join('plantillas p', 'p.id = e.plantilla_id')
            ->join('archivos a', 'a.ejemplo_id = e.id', 'left')
            ->where('e.propietario_usuario_id IS NULL', null, false)
            ->orderBy('p.codigo', 'ASC')
            ->orderBy('e.id', 'ASC')
            ->get()->getResultArray();

        $tipologiasPorEjemplo = [];
        foreach ($db->table('ejemplo_tipologia_ioarr')->get()->getResultArray() as $t) {
            $tipologiasPorEjemplo[(int) $t['ejemplo_id']][] = $t['tipologia'];
        }

        $ejemplos = array_map(static function (array $f) use ($tipologiasPorEjemplo) {
            return [
                'plantillaCodigo' => $f['plantilla_codigo'],
                'nombre'          => $f['nombre'],
                'subtitulo'       => $f['subtitulo'],
                'detalle'         => $f['detalle'],
                'activo'          => (bool) $f['activo'],
                'compartida'      => (bool) $f['compartida'],
                'estado'          => $f['estado'],
                'tipologiasIoarr' => $tipologiasPorEjemplo[(int) $f['id']] ?? [],
                'contenidoJson'   => $f['contenido_json'] !== null ? json_decode($f['contenido_json']) : null,
            ];
        }, $ejemplosFilas);

        $ruta = APPPATH . 'Database/Seeds/data/plantillas_estructura.json';
        file_put_contents($ruta, json_encode([
            'plantillas' => $plantillas,
            'ejemplos'   => $ejemplos,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        CLI::write('Exportadas ' . count($plantillas) . ' estructuras de plantilla y ' . count($ejemplos) . ' ejemplos de referencia.', 'green');
        CLI::write("-> {$ruta}", 'green');
    }
}
