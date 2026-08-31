<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Adjuntos de chat de asesoría que NO son imagen (PDF, Word, ZIP, etc.) — las imágenes siguen
 * subiendo a Cloudinary sin cambios (CloudinaryUploader::subirAdjuntoChat), no las toca esta clase.
 *
 * Por qué: desde 2025 Cloudinary restringe por defecto la entrega pública de PDF/ZIP subidos como
 * resource_type=raw (antiabuso — hosting de malware/phishing), y responde 401 "deny or ACL failure"
 * aunque el archivo se haya subido bien. Encontrado en vivo (2026-08-31) con un PDF de fuente de la
 * verdad adjuntado en un chat de asesoría. Fix: si hay Railway/S3 configurado, estos archivos van
 * ahí — el bucket es privado igual, pero SIEMPRE se sirve por nuestro proxy con Bearer
 * (`/api/asesorias/mensajes/{id}/adjunto`), nunca por URL pública directa, así que la restricción de
 * Cloudinary ni entra en juego. Sin S3 configurado, cae al mismo Cloudinary raw de siempre (sin
 * regresión respecto a hoy, pero con el mismo problema si el archivo es PDF/ZIP).
 */
class AdjuntoChatStorage
{
    private const CARPETA = 'proyecta-facil/asesoria-adjuntos';

    public function usarS3(): bool
    {
        return S3ObjectStore::estaConfigurado();
    }

    /**
     * Sube desde un archivo ya en disco (multipart — preferido, no infla ~33 % como base64 ni
     * obliga a tener el archivo entero en memoria de PHP para decodificarlo).
     * Devuelve el valor a guardar en `mensajes_asesoria.adjunto_url`.
     */
    public function subirDesdeRuta(string $rutaLocal, string $nombreOriginal, string $mimeTipo): string
    {
        if (! $this->usarS3()) {
            return (new CloudinaryUploader())->subirAdjuntoChat($rutaLocal, $nombreOriginal, $mimeTipo);
        }

        return (new S3ObjectStore())->subirDesdeRuta($rutaLocal, $nombreOriginal, self::CARPETA, $mimeTipo);
    }

    /**
     * Compat: sube desde data URI (JSON, sin multipart) — decodifica a un temporal primero.
     * Devuelve el valor a guardar en `mensajes_asesoria.adjunto_url`.
     */
    public function subirDesdeDataUrl(string $dataUrl, string $nombreOriginal, string $mimeTipo): string
    {
        $tmp = $this->materializarATemp($dataUrl, $nombreOriginal);
        try {
            return $this->subirDesdeRuta($tmp, $nombreOriginal, $mimeTipo);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Valor de `mensajes_asesoria.adjunto_url` → URL que el frontend puede usar.
     * S3 → proxy API (necesita el id del mensaje ya guardado); Cloudinary → https directo (compat
     * con adjuntos ya enviados antes de este cambio).
     */
    public function urlParaCliente(string $stored, string $mensajeId): string
    {
        if ($stored === '') {
            return '';
        }
        if (S3ObjectStore::esStoredS3($stored)) {
            return '/api/asesoria/mensajes/' . $mensajeId . '/adjunto';
        }

        return $stored;
    }

    private function materializarATemp(string $dataUrl, string $nombreOriginal): string
    {
        $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION) ?: 'bin';
        $tmp = tempnam(sys_get_temp_dir(), 'pf_adj_');
        if ($tmp === false) {
            throw new RuntimeException('No se pudo crear archivo temporal');
        }
        $ruta = $tmp . '.' . $ext;
        @unlink($tmp);

        if (! str_starts_with($dataUrl, 'data:')) {
            throw new RuntimeException('Se esperaba una data URI para el adjunto');
        }
        $comma = strpos($dataUrl, ',');
        if ($comma === false) {
            throw new RuntimeException('data URI de adjunto inválida');
        }
        $raw = base64_decode(substr($dataUrl, $comma + 1), true);
        if ($raw === false) {
            throw new RuntimeException('No se pudo decodificar el adjunto (base64)');
        }
        if (file_put_contents($ruta, $raw) === false) {
            throw new RuntimeException('No se pudo escribir el adjunto temporal');
        }

        return $ruta;
    }
}
