<?php

namespace App\Libraries;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Config\Cloudinary as CloudinaryConfig;
use RuntimeException;
use Throwable;

// Sube archivos binarios (Excel) a Cloudinary como resource_type=raw — no son imágenes, no necesitan
// transformación. El JSON de estructura NO pasa por aquí: vive en archivos.contenido_json (ver
// docs/database-design.md, "Por qué url va a Cloudinary pero contenido_json no").
class CloudinaryUploader
{
    private UploadApi $uploadApi;

    public function __construct()
    {
        $config = config(CloudinaryConfig::class);

        if ($config->cloudName === '' || $config->apiKey === '' || $config->apiSecret === '' || $config->apiSecret === 'tu-api-secret') {
            throw new RuntimeException(
                'Cloudinary no está configurado — completa cloudinary.cloudName/apiKey/apiSecret en backend/.env',
            );
        }

        Configuration::instance([
            'cloud' => [
                'cloud_name' => $config->cloudName,
                'api_key'    => $config->apiKey,
                'api_secret' => $config->apiSecret,
            ],
            'url' => ['secure' => true],
        ]);

        $this->uploadApi = new UploadApi();
    }

    /**
     * $fuente acepta lo que Cloudinary soporte como origen de upload: ruta local, URL remota, o
     * data URI base64. Preferir ruta local / URL (sin inflar ~33 % como base64).
     *
     * @return string URL segura (https) del archivo subido
     */
    public function subirExcel(string $fuente, string $nombreOriginal): string
    {
        return $this->subirRaw($fuente, $nombreOriginal, 'proyecta-facil/plantillas');
    }

    /**
     * Subida desde archivo temporal del servidor (multipart). Usa chunks si pesa > 5 MB
     * (necesario en planes pagos para raw grandes; el tope sigue siendo el del plan Cloudinary).
     */
    public function subirExcelDesdeRuta(string $rutaLocal, string $nombreOriginal): string
    {
        if (! is_file($rutaLocal)) {
            throw new RuntimeException('Archivo temporal no encontrado para subir a Cloudinary');
        }

        return $this->subirRaw($rutaLocal, $nombreOriginal, 'proyecta-facil/plantillas', true);
    }

    /**
     * @param bool $chunkSiGrande activa chunk_size cuando el origen es ruta local y el tamaño lo amerita
     */
    private function subirRaw(string $fuente, string $nombreOriginal, string $folder, bool $chunkSiGrande = false): string
    {
        $opts = [
            'resource_type'     => 'raw',
            'folder'            => $folder,
            'use_filename'      => true,
            'unique_filename'   => true,
            'filename_override' => $nombreOriginal,
        ];

        if ($chunkSiGrande && is_file($fuente)) {
            $bytes = filesize($fuente);
            if ($bytes !== false && $bytes > 5_000_000) {
                $opts['chunk_size'] = 6_000_000;
            }
        }

        $resultado = $this->uploadApi->upload($fuente, $opts);

        return (string) $resultado['secure_url'];
    }

    /**
     * Traduce errores típicos de Cloudinary a mensaje usable en la UI.
     */
    public static function mensajeErrorAmigable(Throwable $e): string
    {
        $msg = $e->getMessage();
        if (preg_match('/File size too large.*?Got\s+(\d+).*?Maximum is\s+(\d+)/is', $msg, $m)) {
            $gotMb = round(((int) $m[1]) / 1_048_576, 1);
            $maxMb = round(((int) $m[2]) / 1_048_576, 1);

            return "El archivo (~{$gotMb} MB) supera el límite de tu plan Cloudinary ({$maxMb} MB). "
                . 'Free permite 10 MB; Plus 20 MB; Advanced 40 MB. Sube de plan o reduce el Excel.';
        }

        return 'No se pudo subir a Cloudinary: ' . $msg;
    }

    /**
     * Sube una imagen (resource_type=image, a diferencia de subirExcel): así Cloudinary la sirve
     * como imagen real y quedan disponibles sus transformaciones de entrega (ancho, formato, etc.).
     * Acepta las mismas fuentes que subirExcel — en la práctica llega un data URI, porque el
     * frontend extrae el binario del .xlsx y no tiene un archivo en disco que ofrecer.
     *
     * @return string URL segura (https) de la imagen subida
     */
    public function subirImagen(string $fuente, string $nombreOriginal): string
    {
        $resultado = $this->uploadApi->upload($fuente, [
            'resource_type'     => 'image',
            'folder'            => 'proyecta-facil/imagenes',
            'use_filename'      => true,
            'unique_filename'   => true,
            'filename_override' => $nombreOriginal,
        ]);

        return (string) $resultado['secure_url'];
    }

    /**
     * Sube texto plano (Markdown) como archivo .md — mismo patrón que subirExcel/subirImagen, pero
     * la fuente no llega desde el navegador como data URI: se arma acá a partir del string.
     * Usado por los contextos IA (por sección, generales de una ficha, y globales).
     *
     * @return string URL segura (https) del archivo subido
     */
    public function subirMarkdown(string $markdown, string $nombreOriginal): string
    {
        $fuente    = 'data:text/markdown;base64,' . base64_encode($markdown);
        $resultado = $this->uploadApi->upload($fuente, [
            'resource_type'      => 'raw',
            'folder'             => 'proyecta-facil/contextos-ia',
            'use_filename'       => true,
            'unique_filename'    => true,
            'filename_override'  => $nombreOriginal,
        ]);

        return (string) $resultado['secure_url'];
    }

    /**
     * Sube un documento de la "fuente de la verdad" (PDF/TXT/MD que el cliente carga con información
     * de su proyecto). Llega como data URI desde el navegador, igual que subirExcel.
     *
     * @return string URL segura (https) del archivo subido
     */
    public function subirDocumento(string $fuente, string $nombreOriginal): string
    {
        $resultado = $this->uploadApi->upload($fuente, [
            'resource_type'      => 'raw',
            'folder'             => 'proyecta-facil/fuente-verdad',
            'use_filename'       => true,
            'unique_filename'    => true,
            'filename_override'  => $nombreOriginal,
        ]);

        return (string) $resultado['secure_url'];
    }
}
