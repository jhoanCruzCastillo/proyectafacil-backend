<?php

namespace App\Controllers;

use App\Libraries\CloudinaryUploader;
use App\Models\ArchivoModel;
use App\Models\EjemploModel;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

// Espejo de ExcelEjemplosApi en frontend/src/types/index.ts — la copia propia de Excel (1:1) de un
// ejemplo/ficha. Es la misma tabla `archivos` que usa ArchivosController para las plantillas, pero
// con propietario_tipo='ejemplo'; `contenido_json` (los valores del ejemplo) se deja intacto acá,
// solo se toca desde EjemplosController::update.
class ExcelEjemplosController extends BaseController
{
    public function get($ejemploId = null): ResponseInterface
    {
        $archivo = (new ArchivoModel())->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $ejemploId)->first();
        if (! $archivo) {
            return $this->response->setJSON(null);
        }

        return $this->response->setJSON($this->toDto($archivo));
    }

    public function set($ejemploId = null): ResponseInterface
    {
        $ejemploId = (int) $ejemploId;
        if (! (new EjemploModel())->find($ejemploId)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ejemplo no encontrado']);
        }

        $dto = $this->request->getJSON(true) ?? [];
        $nombre = (string) ($dto['nombre'] ?? 'archivo.xlsx');
        // dataUrl acá puede ser una URL de Cloudinary ya existente (copia inicial del Excel de la
        // plantilla) o un data URI base64 nuevo (resultado de insertarValoresEnExcel) — Cloudinary
        // acepta ambos como origen de upload, ver CloudinaryUploader::subirExcel.
        $dataUrl = (string) ($dto['dataUrl'] ?? '');
        if ($dataUrl === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta el archivo (dataUrl)']);
        }

        try {
            $url = (new CloudinaryUploader())->subirExcel($dataUrl, $nombre);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo subir a Cloudinary: ' . $e->getMessage()]);
        }

        $archivoModel = new ArchivoModel();
        $existente = $archivoModel->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $ejemploId)->first();

        if ($existente) {
            $archivoModel->update($existente['id'], [
                'nombre'       => $nombre,
                'url'          => $url,
                'fecha_subida' => date('Y-m-d H:i:s'),
            ]);
            $id = $existente['id'];
        } else {
            $id = $archivoModel->insert([
                'propietario_tipo' => 'ejemplo',
                'ejemplo_id'       => $ejemploId,
                'nombre'           => $nombre,
                'url'              => $url,
                'fecha_subida'     => date('Y-m-d H:i:s'),
            ], true);
        }

        return $this->response->setJSON($this->toDto($archivoModel->find($id)));
    }

    private function toDto(array $archivo): array
    {
        return [
            'id'          => (string) $archivo['id'],
            'nombre'      => $archivo['nombre'],
            'dataUrl'     => $archivo['url'],
            'fechaSubida' => $archivo['fecha_subida'],
        ];
    }
}
