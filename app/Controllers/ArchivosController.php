<?php

namespace App\Controllers;

use App\Libraries\CloudinaryUploader;
use App\Models\ArchivoModel;
use App\Models\PlantillaModel;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

// Espejo de ArchivosExcelApi/CatalogoExcelPlantilla en frontend/src/types/index.ts. El campo
// `dataUrl` de ArchivoExcel es histórico del prototipo (base64 en localStorage) — acá lleva la URL
// real de Cloudinary. Los consumidores del frontend (ExcelViewer, excelWriter, xlsxXmlPatcher) ya
// leen ese campo con `fetch()`, que funciona igual con un data: URI o una https: URL, así que no
// necesitan cambios.
class ArchivosController extends BaseController
{
    public function getCatalogo($plantillaId = null): ResponseInterface
    {
        return $this->response->setJSON($this->catalogoDto((int) $plantillaId));
    }

    public function addArchivo($plantillaId = null): ResponseInterface
    {
        $plantillaId = (int) $plantillaId;
        $plantilla = (new PlantillaModel())->find($plantillaId);
        if (! $plantilla) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Plantilla no encontrada']);
        }

        $dto = $this->request->getJSON(true) ?? [];
        $nombre = (string) ($dto['nombre'] ?? 'archivo.xlsx');
        // ExcelCatalogModal.vue ya lee el archivo con FileReader.readAsDataURL antes de enviarlo —
        // llega como data URI base64 completo, que Cloudinary acepta directo como origen de upload.
        $dataUrl = (string) ($dto['dataUrl'] ?? '');
        if ($dataUrl === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta el archivo (dataUrl)']);
        }

        try {
            $url = (new CloudinaryUploader())->subirExcel($dataUrl, $nombre);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo subir a Cloudinary: ' . $e->getMessage()]);
        }

        $nuevoId = (new ArchivoModel())->insert([
            'propietario_tipo' => 'plantilla',
            'plantilla_id'     => $plantillaId,
            'nombre'           => $nombre,
            'url'              => $url,
            'fecha_subida'     => date('Y-m-d H:i:s'),
        ], true);

        // Primer archivo del catálogo queda asignado automáticamente (mismo comportamiento que el mock).
        if (! $plantilla['asignado_archivo_id']) {
            (new PlantillaModel())->update($plantillaId, ['asignado_archivo_id' => $nuevoId]);
        }

        return $this->response->setJSON($this->catalogoDto($plantillaId));
    }

    public function deleteArchivo($plantillaId = null, $archivoId = null): ResponseInterface
    {
        // plantillas.asignado_archivo_id → archivos.id es ON DELETE SET NULL (ver migración), así
        // que si el archivo borrado era el asignado, la BD ya lo desasigna sola.
        (new ArchivoModel())->delete($archivoId);

        return $this->response->setJSON($this->catalogoDto((int) $plantillaId));
    }

    public function asignarArchivo($plantillaId = null, $archivoId = null): ResponseInterface
    {
        (new PlantillaModel())->update((int) $plantillaId, ['asignado_archivo_id' => (int) $archivoId]);

        return $this->response->setJSON($this->catalogoDto((int) $plantillaId));
    }

    private function catalogoDto(int $plantillaId): array
    {
        $plantilla = (new PlantillaModel())->find($plantillaId);
        $filas = (new ArchivoModel())
            ->where('propietario_tipo', 'plantilla')
            ->where('plantilla_id', $plantillaId)
            ->orderBy('id')
            ->findAll();

        return [
            'archivos' => array_map(static fn (array $f): array => [
                'id'          => (string) $f['id'],
                'nombre'      => $f['nombre'],
                'dataUrl'     => $f['url'],
                'fechaSubida' => $f['fecha_subida'],
            ], $filas),
            'asignadoId' => ($plantilla && $plantilla['asignado_archivo_id']) ? (string) $plantilla['asignado_archivo_id'] : null,
        ];
    }
}
