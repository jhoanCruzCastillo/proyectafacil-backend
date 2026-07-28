<?php

namespace App\Libraries;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Config\Cloudinary as CloudinaryConfig;
use RuntimeException;

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
     * (como llega desde ExcelCatalogModal.vue, que ya lee el archivo con FileReader.readAsDataURL)
     * un data URI base64 completo — Cloudinary lo sube igual sin que el backend tenga que decodificarlo.
     *
     * @return string URL segura (https) del archivo subido
     */
    public function subirExcel(string $fuente, string $nombreOriginal): string
    {
        $resultado = $this->uploadApi->upload($fuente, [
            'resource_type'      => 'raw',
            'folder'             => 'proyecta-facil/plantillas',
            'use_filename'       => true,
            'unique_filename'    => true,
            'filename_override'  => $nombreOriginal,
        ]);

        return (string) $resultado['secure_url'];
    }
}
