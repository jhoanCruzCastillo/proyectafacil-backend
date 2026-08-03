<?php

namespace App\Controllers;

use App\Libraries\CloudinaryUploader;
use App\Libraries\ConversorImagen;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

// Sube una imagen a Cloudinary y devuelve su URL, que es lo que se guarda como `valor` de un campo
// tipo `imagen` en el JSON. Dos orígenes la usan: el volcado desde Excel (que extrae el binario
// incrustado en el .xlsx) y, más adelante, la subida manual desde el editor.
//
// La subida pasa por el backend a propósito: las credenciales de Cloudinary (apiSecret incluido)
// viven en backend/.env y no deben salir de ahí. El frontend nunca las ve.
class ImagenesController extends BaseController
{
    public function subir(): ResponseInterface
    {
        $dto     = $this->request->getJSON(true) ?? [];
        $dataUrl = (string) ($dto['dataUrl'] ?? '');
        $nombre  = (string) ($dto['nombre'] ?? 'imagen');
        $formato = strtolower((string) ($dto['formato'] ?? ''));

        if ($dataUrl === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta la imagen (dataUrl)']);
        }

        // Formatos que no son web (EMF/WMF de las plantillas oficiales) se convierten antes de
        // subir: Cloudinary los rechaza como resource_type=image y el navegador no los pinta.
        if ($formato !== '' && ! ConversorImagen::esFormatoWeb($formato)) {
            $bytes = $this->bytesDeDataUrl($dataUrl);
            if ($bytes === null) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'La imagen no llegó en un data URI válido']);
            }

            $png = ConversorImagen::aPng($bytes, $formato);
            if ($png === null) {
                // No es un error del cliente: es una capacidad que falta en el servidor. Se
                // responde con un código propio para que el volcado lo reporte como "omitida"
                // en vez de romper toda la operación.
                return $this->response->setStatusCode(422)->setJSON([
                    'error'   => ConversorImagen::disponible()
                        ? "No se pudo convertir la imagen {$formato} a PNG"
                        : "El servidor no tiene LibreOffice instalado, necesario para convertir {$formato} a PNG",
                    'motivo'  => 'formato_no_convertible',
                    'formato' => $formato,
                ]);
            }

            $dataUrl = 'data:image/png;base64,' . base64_encode($png);
            $nombre  = preg_replace('/\.[^.]+$/', '', $nombre) . '.png';
        }

        try {
            $url = (new CloudinaryUploader())->subirImagen($dataUrl, $nombre);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo subir a Cloudinary: ' . $e->getMessage()]);
        }

        return $this->response->setJSON(['url' => $url]);
    }

    /** Estado del conversor — lo consulta el frontend para avisar antes de intentar un volcado. */
    public function capacidades(): ResponseInterface
    {
        return $this->response->setJSON(['conversorDisponible' => ConversorImagen::disponible()]);
    }

    private function bytesDeDataUrl(string $dataUrl): ?string
    {
        $coma = strpos($dataUrl, ',');
        if ($coma === false) {
            return null;
        }
        $bytes = base64_decode(substr($dataUrl, $coma + 1), true);

        return $bytes === false ? null : $bytes;
    }
}
