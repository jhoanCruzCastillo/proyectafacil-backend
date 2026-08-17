<?php

namespace App\Libraries;

use App\Models\ArchivoModel;
use RuntimeException;
use Throwable;

/**
 * Fachada para Excel: si hay Railway/S3 configurado, sube ahí; si no, Cloudinary.
 * En BD: URL https de Cloudinary o `s3:{key}`. Al SPA, S3 se expone como
 * `/api/archivos/{id}/contenido` (proxy con Bearer) para evitar CORS del bucket.
 */
class ExcelStorage
{
    public function usarS3(): bool
    {
        return S3ObjectStore::estaConfigurado();
    }

    public function subirDesdeRuta(string $rutaLocal, string $nombreOriginal): string
    {
        if ($this->usarS3()) {
            return (new S3ObjectStore())->subirDesdeRuta($rutaLocal, $nombreOriginal);
        }

        return (new CloudinaryUploader())->subirExcelDesdeRuta($rutaLocal, $nombreOriginal);
    }

    /**
     * Acepta ruta local, data URI, URL https, o proxy `/api/archivos/{id}/contenido`.
     */
    public function subirDesdeFuente(string $fuente, string $nombreOriginal): string
    {
        if ($this->usarS3()) {
            $tmp = $this->materializarATemp($fuente, $nombreOriginal);
            try {
                return (new S3ObjectStore())->subirDesdeRuta($tmp, $nombreOriginal);
            } finally {
                @unlink($tmp);
            }
        }

        // Sin S3: si la fuente es nuestro proxy, resolver desde BD / Cloudinary URL.
        if (preg_match('#(?:^|/)api/archivos/(\d+)/contenido#', $fuente, $m)) {
            $fila = (new ArchivoModel())->find((int) $m[1]);
            $stored = (string) ($fila['url'] ?? '');
            if ($stored === '') {
                throw new RuntimeException('Archivo de origen no encontrado');
            }
            $fuente = $stored;
        }

        return (new CloudinaryUploader())->subirExcel($fuente, $nombreOriginal);
    }

    /**
     * Valor de `archivos.url` (+ id) → URL que el frontend puede fetch-ear.
     * S3 → proxy API; Cloudinary → https directo.
     */
    public function urlParaCliente(string $storedUrl, int $archivoId): string
    {
        if ($storedUrl === '') {
            return '';
        }
        if (S3ObjectStore::esStoredS3($storedUrl)) {
            return '/api/archivos/' . $archivoId . '/contenido';
        }

        return $storedUrl;
    }

    /** Binario + nombre para el endpoint de contenido. */
    public function leerContenido(array $archivo): array
    {
        $stored = (string) ($archivo['url'] ?? '');
        $nombre = (string) ($archivo['nombre'] ?? 'archivo.xlsx');
        if ($stored === '') {
            throw new RuntimeException('Archivo sin URL');
        }

        if (S3ObjectStore::esStoredS3($stored)) {
            $body = (new S3ObjectStore())->getObjectBody(S3ObjectStore::claveDe($stored));
        } elseif (preg_match('#^https?://#i', $stored)) {
            $body = @file_get_contents($stored);
            if ($body === false) {
                throw new RuntimeException('No se pudo descargar el Excel desde su URL');
            }
        } else {
            throw new RuntimeException('URL de archivo no reconocida');
        }

        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlsm'  => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xls'   => 'application/vnd.ms-excel',
            default => 'application/octet-stream',
        };

        return ['body' => $body, 'nombre' => $nombre, 'mime' => $mime];
    }

    public static function mensajeErrorAmigable(Throwable $e): string
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'File size too large') || str_contains($msg, 'Cloudinary')) {
            return CloudinaryUploader::mensajeErrorAmigable($e);
        }

        return S3ObjectStore::mensajeErrorAmigable($e);
    }

    private function materializarATemp(string $fuente, string $nombreOriginal): string
    {
        $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION) ?: 'xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'pf_xlsx_');
        if ($tmp === false) {
            throw new RuntimeException('No se pudo crear archivo temporal');
        }
        $ruta = $tmp . '.' . $ext;
        @unlink($tmp);

        // Proxy del propio API → leer clave/URL real desde BD (evita 401 sin Bearer en file_get_contents).
        if (preg_match('#(?:^|/)api/archivos/(\d+)/contenido#', $fuente, $m)) {
            $fila = (new ArchivoModel())->find((int) $m[1]);
            $stored = (string) ($fila['url'] ?? '');
            if ($stored === '') {
                throw new RuntimeException('Archivo de origen no encontrado');
            }
            if (S3ObjectStore::esStoredS3($stored)) {
                return (new S3ObjectStore())->descargarATemp($stored, $ext);
            }
            $fuente = $stored;
        }

        if (is_file($fuente)) {
            if (! @copy($fuente, $ruta)) {
                throw new RuntimeException('No se pudo copiar el Excel a temporal');
            }

            return $ruta;
        }

        if (str_starts_with($fuente, 'data:')) {
            $comma = strpos($fuente, ',');
            if ($comma === false) {
                throw new RuntimeException('data URI de Excel inválida');
            }
            $raw = base64_decode(substr($fuente, $comma + 1), true);
            if ($raw === false) {
                throw new RuntimeException('No se pudo decodificar el Excel (base64)');
            }
            if (file_put_contents($ruta, $raw) === false) {
                throw new RuntimeException('No se pudo escribir el Excel temporal');
            }

            return $ruta;
        }

        if (S3ObjectStore::esStoredS3($fuente)) {
            return (new S3ObjectStore())->descargarATemp($fuente, $ext);
        }

        if (preg_match('#^https?://#i', $fuente)) {
            $bin = @file_get_contents($fuente);
            if ($bin === false) {
                throw new RuntimeException('No se pudo descargar el Excel de origen para copiarlo');
            }
            if (file_put_contents($ruta, $bin) === false) {
                throw new RuntimeException('No se pudo escribir el Excel temporal');
            }

            return $ruta;
        }

        throw new RuntimeException('Fuente de Excel no reconocida (se esperaba ruta, data URI, URL https o /api/archivos/…/contenido)');
    }
}
