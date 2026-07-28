<?php

namespace App\Commands;

use App\Libraries\CloudinaryUploader;
use App\Models\ArchivoModel;
use App\Models\PlantillaModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

// Comando de una sola corrida: sube a Cloudinary los Excel reales del catálogo de fichas oficiales
// (viven en el prototipo hermano book/templates_editor/public/fichas_oficiales/, no en este repo) y
// asigna cada uno a su plantilla vía `archivos` + `plantillas.asignado_archivo_id`. Para la plantilla
// 6A además adjunta la estructura completa de 14 secciones (extraída de
// frontend/src/data/plantillas.ts a Database/Seeds/data/plantillas.json) en `contenido_json`.
class UploadFichasExcelCommand extends BaseCommand
{
    protected $group       = 'app';
    protected $name        = 'fichas:upload-excel';
    protected $description = 'Sube a Cloudinary los Excel del catálogo de fichas oficiales y los asigna a su plantilla.';

    private const BASE_DIR = 'C:/Users/anton/Documents/GitHub/Herrera/book/templates_editor/public';

    public function run(array $params)
    {
        $plantillaModel = new PlantillaModel();
        $archivoModel   = new ArchivoModel();

        try {
            $uploader = new CloudinaryUploader();
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return;
        }

        $plantillas = $plantillaModel
            ->where('archivo_default_url IS NOT NULL', null, false)
            ->orderBy('codigo')
            ->findAll();

        $seccionesPorCodigo = $this->cargarSecciones();

        $subidos  = 0;
        $omitidos = 0;

        foreach ($plantillas as $p) {
            $localPath = self::BASE_DIR . $p['archivo_default_url'];
            if (! is_file($localPath)) {
                CLI::write("SKIP {$p['codigo']}: no existe {$localPath}", 'yellow');
                $omitidos++;

                continue;
            }

            $nombre = basename($localPath);
            CLI::write("Subiendo {$p['codigo']} — {$nombre} ...");

            try {
                $url = $uploader->subirExcel($localPath, $nombre);
            } catch (Throwable $e) {
                CLI::error("  ERROR subiendo {$p['codigo']}: " . $e->getMessage());
                $omitidos++;

                continue;
            }

            $contenidoJson = isset($seccionesPorCodigo[$p['codigo']])
                ? json_encode(['secciones' => $seccionesPorCodigo[$p['codigo']]], JSON_UNESCAPED_UNICODE)
                : null;

            $archivoId = $archivoModel->insert([
                'propietario_tipo' => 'plantilla',
                'plantilla_id'     => $p['id'],
                'nombre'           => $nombre,
                'url'              => $url,
                'contenido_json'   => $contenidoJson,
                'fecha_subida'     => date('Y-m-d H:i:s'),
            ], true);

            $plantillaModel->update($p['id'], ['asignado_archivo_id' => $archivoId]);

            CLI::write("  OK -> archivo #{$archivoId}" . ($contenidoJson ? ' (+ estructura)' : ''), 'green');
            $subidos++;
        }

        CLI::write("Listo. Subidos: {$subidos}, omitidos: {$omitidos}.", 'green');
    }

    /** @return array<string, array> codigo de plantilla -> array de secciones */
    private function cargarSecciones(): array
    {
        $ruta = APPPATH . 'Database/Seeds/data/plantillas.json';
        $data = json_decode(file_get_contents($ruta), true);

        $resultado = [];
        foreach ($data as $p) {
            if (! empty($p['secciones'])) {
                $resultado[$p['codigo']] = $p['secciones'];
            }
        }

        return $resultado;
    }
}
