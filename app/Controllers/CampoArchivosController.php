<?php

namespace App\Controllers;

use App\Libraries\ExcelStorage;
use App\Libraries\S3ObjectStore;
use App\Libraries\StreamProxy;
use App\Models\ArchivoModel;
use Config\Encryption;
use CodeIgniter\HTTP\ResponseInterface;
use GuzzleHttp\Client;
use Throwable;

// Campos/columnas tipo `archivo` (PDF/Excel/Word/TXT adjuntos a una ficha) — pedido explícito del
// cliente de subirlos al bucket S3 de Railway, no a Cloudinary como las imágenes. Se guardan en la
// misma tabla `archivos` que ya usan los Excel de plantilla (`propietario_tipo = 'campo'`, sin
// dueño — ver migración AllowCampoEnArchivosPropietario), reutilizando `ExcelStorage` tal cual.
//
// `descargarPublico()` es la pieza nueva: un hipervínculo de Excel es un link plano — al hacer
// clic, el sistema abre esa URL SIN el header Authorization que usa el resto de la API (el Bearer
// vive en localStorage, no en una cookie), así que la ruta protegida de `ArchivosController` nunca
// podría abrirse desde un Excel exportado. Acá el archivo sigue en el bucket PRIVADO de Railway,
// pero la URL de descarga lleva un token firmado (HMAC con el mismo secreto de Config\Encryption
// que ya usa AuthToken) que no depende de sesión — cualquiera con el link lo abre, igual que ya
// pasa hoy con las imágenes públicas de Cloudinary. Lo que se hace público es ESE link puntual, no
// el bucket.
class CampoArchivosController extends BaseController
{
    private const EXTENSIONES_PERMITIDAS = ['pdf', 'xls', 'xlsx', 'doc', 'docx', 'txt'];
    private const TAMANO_MAXIMO = 15 * 1024 * 1024; // 15 MB

    public function subir(): ResponseInterface
    {
        $dto     = $this->request->getJSON(true) ?? [];
        $nombre  = trim((string) ($dto['nombre'] ?? ''));
        $dataUrl = (string) ($dto['dataUrl'] ?? '');
        $ext     = strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION));

        if ($nombre === '' || $dataUrl === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta el nombre o el archivo']);
        }
        if (! in_array($ext, self::EXTENSIONES_PERMITIDAS, true)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Solo se admiten archivos PDF, Excel, Word o TXT']);
        }
        if ($this->tamanoDeDataUrl($dataUrl) > self::TAMANO_MAXIMO) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'El archivo supera los 15 MB permitidos']);
        }

        try {
            $url = (new ExcelStorage())->subirDesdeFuente($dataUrl, $nombre, 'proyecta-facil/campos');
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => ExcelStorage::mensajeErrorAmigable($e)]);
        }

        $id = (new ArchivoModel())->insert([
            'propietario_tipo' => 'campo',
            'nombre'           => $nombre,
            'url'              => $url,
            'fecha_subida'     => date('Y-m-d H:i:s'),
        ], true);

        // `n` (nombre original) va en la URL para que el campo, cuyo valor guardado es solo ESTE
        // string, pueda mostrar/descargar con el nombre correcto sin necesitar un campo aparte en
        // el JSON — el backend lo ignora al servir el binario (solo valida `t`).
        return $this->response->setJSON([
            'id'     => (string) $id,
            'nombre' => $nombre,
            'url'    => '/api/archivos-campo/' . $id . '/contenido?t=' . $this->tokenPara((int) $id) . '&n=' . rawurlencode($nombre),
        ]);
    }

    /** Pública, sin filtro `auth` — ver comentario de clase. Gateada por el token firmado, no por sesión. */
    public function descargarPublico($archivoId = null): ResponseInterface
    {
        $archivo = (new ArchivoModel())
            ->where('propietario_tipo', 'campo')
            ->find($archivoId);
        if (! $archivo || ($archivo['url'] ?? '') === '') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Archivo no encontrado']);
        }

        $tokenRecibido = (string) $this->request->getGet('t');
        if ($tokenRecibido === '' || ! hash_equals($this->tokenPara((int) $archivoId), $tokenRecibido)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Link inválido o vencido']);
        }

        $nombre = (string) ($archivo['nombre'] ?? 'archivo');
        $stored = (string) $archivo['url'];
        $mime   = $this->mimeDeNombre($nombre);

        try {
            if (S3ObjectStore::esStoredS3($stored)) {
                $psr = (new S3ObjectStore())->getObjectPsrResponse(S3ObjectStore::claveDe($stored), true);
                $len = $psr->getHeaderLine('Content-Length');
                StreamProxy::pipe($psr->getBody(), $mime, $nombre, $len !== '' ? (int) $len : null);
            }

            if (preg_match('#^https?://#i', $stored)) {
                $remote = (new Client(['http_errors' => false, 'timeout' => 600]))->get($stored, ['stream' => true]);
                if ($remote->getStatusCode() < 200 || $remote->getStatusCode() >= 300) {
                    return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo obtener el archivo remoto']);
                }
                $len = $remote->getHeaderLine('Content-Length');
                StreamProxy::pipe($remote->getBody(), $mime, $nombre, $len !== '' ? (int) $len : null);
            }
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => ExcelStorage::mensajeErrorAmigable($e)]);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'URL de archivo no reconocida']);
    }

    private function tokenPara(int $archivoId): string
    {
        return hash_hmac('sha256', 'archivo-campo:' . $archivoId, $this->secreto());
    }

    private function secreto(): string
    {
        $key = config(Encryption::class)->key;

        return $key !== '' ? $key : 'proyectafacil-dev-auth-secret';
    }

    private function tamanoDeDataUrl(string $dataUrl): int
    {
        $coma = strpos($dataUrl, ',');
        if ($coma === false) {
            return 0;
        }

        // base64 infla ~33% — calcular el tamaño real sin decodificar todo el binario a memoria.
        $b64 = substr($dataUrl, $coma + 1);

        return (int) floor(strlen($b64) * 3 / 4);
    }

    private function mimeDeNombre(string $nombre): string
    {
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf'  => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls'  => 'application/vnd.ms-excel',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc'  => 'application/msword',
            'txt'  => 'text/plain',
            default => 'application/octet-stream',
        };
    }
}
