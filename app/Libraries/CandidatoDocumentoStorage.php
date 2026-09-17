<?php

namespace App\Libraries;

/**
 * CV de postulantes al equipo de especialistas (candidatos.cv_url) — PDF/DOC/DOCX, casi siempre
 * `resource_type=raw` en Cloudinary. Mismo patrón exacto que AdjuntoChatStorage (ver ese archivo
 * para el porqué): Cloudinary bloquea por defecto la entrega pública de raw PDF/ZIP (401 "deny or
 * ACL failure"), así que si hay S3/Railway configurado el CV va ahí — bucket privado, servido
 * siempre por nuestro proxy admin (`CandidatosController::cv`), nunca por una URL pública directa.
 * Sin S3 configurado cae al mismo Cloudinary raw de siempre.
 */
class CandidatoDocumentoStorage
{
    private const CARPETA = 'proyecta-facil/candidatos-cv';

    public function usarS3(): bool
    {
        return S3ObjectStore::estaConfigurado();
    }

    /** Sube desde un archivo ya en disco (multipart). Devuelve el valor a guardar en `candidatos.cv_url`. */
    public function subirDesdeRuta(string $rutaLocal, string $nombreOriginal, string $mimeTipo): string
    {
        if (! $this->usarS3()) {
            return (new CloudinaryUploader())->subirArchivoContexto($rutaLocal, $nombreOriginal);
        }

        return (new S3ObjectStore())->subirDesdeRuta($rutaLocal, $nombreOriginal, self::CARPETA, $mimeTipo);
    }
}
