<?php

namespace App\Controllers;

use App\Libraries\ExcelStorage;
use App\Libraries\S3ObjectStore;
use App\Models\ArchivoModel;
use App\Models\PlantillaModel;
use CodeIgniter\HTTP\ResponseInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\StreamInterface;
use Throwable;

// Espejo de ArchivosExcelApi/CatalogoExcelPlantilla en frontend/src/types/index.ts. El campo
// `dataUrl` de ArchivoExcel es histórico del prototipo (base64 en localStorage) — acá lleva la URL
// real (Cloudinary https o URL presignada S3). Los consumidores del frontend ya leen con fetch().
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

        $storage = new ExcelStorage();
        $nombre  = 'archivo.xlsx';

        try {
            // Preferido: multipart binario (no infla ~33 % como base64).
            $file = $this->request->getFile('archivo');
            if ($file && $file->isValid() && ! $file->hasMoved()) {
                $nombre = $file->getClientName() ?: $nombre;
                $url    = $storage->subirDesdeRuta($file->getTempName(), $nombre);
            } else {
                // Compat: JSON con dataUrl (URL Cloudinary o data URI).
                $dto     = $this->request->getJSON(true) ?? [];
                $nombre  = (string) ($dto['nombre'] ?? $nombre);
                $dataUrl = (string) ($dto['dataUrl'] ?? '');
                if ($dataUrl === '') {
                    return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta el archivo (campo multipart "archivo" o dataUrl)']);
                }
                $url = $storage->subirDesdeFuente($dataUrl, $nombre);
            }
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => ExcelStorage::mensajeErrorAmigable($e)]);
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

    /** Sirve el binario en streaming (S3/Cloudinary → cliente) para progreso real en el navegador. */
    public function contenido($archivoId = null): ResponseInterface
    {
        $archivo = (new ArchivoModel())->find($archivoId);
        if (! $archivo || ($archivo['url'] ?? '') === '') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Archivo no encontrado']);
        }

        $nombre = (string) ($archivo['nombre'] ?? 'archivo.xlsx');
        $stored = (string) $archivo['url'];
        $mime   = $this->mimeDeNombre($nombre);

        try {
            if (S3ObjectStore::esStoredS3($stored)) {
                $psr = (new S3ObjectStore())->getObjectPsrResponse(S3ObjectStore::claveDe($stored), true);
                $len = $psr->getHeaderLine('Content-Length');

                return $this->pipeStreamAlCliente(
                    $psr->getBody(),
                    $mime,
                    $nombre,
                    $len !== '' ? (int) $len : null,
                );
            }

            if (preg_match('#^https?://#i', $stored)) {
                $remote = (new Client(['http_errors' => false, 'timeout' => 600]))->get($stored, ['stream' => true]);
                if ($remote->getStatusCode() < 200 || $remote->getStatusCode() >= 300) {
                    return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo obtener el Excel remoto']);
                }
                $len = $remote->getHeaderLine('Content-Length');

                return $this->pipeStreamAlCliente(
                    $remote->getBody(),
                    $mime,
                    $nombre,
                    $len !== '' ? (int) $len : null,
                );
            }
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => ExcelStorage::mensajeErrorAmigable($e)]);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'URL de archivo no reconocida']);
    }

    /**
     * Escribe el stream al cliente a trozos (sin cargar el Excel entero en memoria de PHP).
     * Sale del ciclo de Response de CI4 a propósito: si bufferizáramos el body, el % saltaría 0→100.
     */
    private function pipeStreamAlCliente(StreamInterface $stream, string $mime, string $nombre, ?int $length): ResponseInterface
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');

        $safeName = str_replace(['"', "\r", "\n"], '', $nombre);

        header('Content-Type: ' . $mime);
        if ($length !== null && $length > 0) {
            header('Content-Length: ' . $length);
        }
        header('Content-Disposition: inline; filename="' . $safeName . '"');
        header('Cache-Control: private, max-age=300');
        header('X-Accel-Buffering: no');
        header('Connection: close');

        // Fuerza el envío de cabeceras antes del primer trozo (php spark serve / CGI).
        if (function_exists('fastcgi_finish_request') === false) {
            flush();
        }

        $chunk = 64 * 1024;
        while (! $stream->eof()) {
            $data = $stream->read($chunk);
            if ($data === '') {
                break;
            }
            echo $data;
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
        $stream->close();

        exit;
    }

    private function mimeDeNombre(string $nombre): string
    {
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        return match ($ext) {
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlsm'  => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xls'   => 'application/vnd.ms-excel',
            default => 'application/octet-stream',
        };
    }

    private function catalogoDto(int $plantillaId): array
    {
        $plantilla = (new PlantillaModel())->find($plantillaId);
        $filas = (new ArchivoModel())
            ->where('propietario_tipo', 'plantilla')
            ->where('plantilla_id', $plantillaId)
            ->orderBy('id')
            ->findAll();

        $storage = new ExcelStorage();

        return [
            'archivos' => array_map(static fn (array $f): array => [
                'id'          => (string) $f['id'],
                'nombre'      => $f['nombre'],
                'dataUrl'     => $storage->urlParaCliente((string) ($f['url'] ?? ''), (int) $f['id']),
                'fechaSubida' => $f['fecha_subida'],
            ], $filas),
            'asignadoId' => ($plantilla && $plantilla['asignado_archivo_id']) ? (string) $plantilla['asignado_archivo_id'] : null,
        ];
    }
}
