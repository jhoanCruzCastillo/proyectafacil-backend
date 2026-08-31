<?php

namespace App\Libraries;

use Config\Storage as StorageConfig;
use GuzzleHttp\Client;
use RuntimeException;
use Throwable;

/**
 * Cliente S3 mínimo (PutObject + GetObject) vía Guzzle — sin aws-sdk.
 * Railway Buckets son privados y sin CORS para el SPA: el binario se sirve por proxy API
 * (`/api/archivos/{id}/contenido`). En BD: `s3:{key}`.
 */
class S3ObjectStore
{
    public const STORED_PREFIX = 's3:';

    private StorageConfig $config;
    private Client $http;

    public function __construct(?StorageConfig $config = null)
    {
        $this->config = $config ?? config(StorageConfig::class);
        if (! self::estaConfigurado($this->config)) {
            throw new RuntimeException(
                'Storage S3 no configurado — completa storage.endpoint/region/bucket/accessKey/secretKey en backend/.env',
            );
        }
        $this->http = new Client(['http_errors' => false, 'timeout' => 600]);
    }

    public static function estaConfigurado(?StorageConfig $config = null): bool
    {
        $c = $config ?? config(StorageConfig::class);

        return $c->endpoint !== ''
            && $c->bucket !== ''
            && $c->accessKey !== ''
            && $c->secretKey !== ''
            && $c->secretKey !== 'tu-secret-key';
    }

    public static function esStoredS3(string $urlOClave): bool
    {
        return str_starts_with($urlOClave, self::STORED_PREFIX);
    }

    public static function claveDe(string $stored): string
    {
        return substr($stored, strlen(self::STORED_PREFIX));
    }

    /**
     * Sube un archivo local y devuelve el valor a guardar en `archivos.url` (`s3:key`).
     *
     * @param string $mimeOverride Content-Type real (ej. del navegador) — para archivos que no son
     *                             Excel, mimeDeNombre() solo conoce xlsx/xlsm/xls y cae a
     *                             octet-stream. Vacío = comportamiento de siempre (adivinar por extensión).
     */
    public function subirDesdeRuta(string $rutaLocal, string $nombreOriginal, string $carpeta = 'proyecta-facil/excel', string $mimeOverride = ''): string
    {
        if (! is_file($rutaLocal)) {
            throw new RuntimeException('Archivo temporal no encontrado para subir a S3');
        }

        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $nombreOriginal) ?: 'archivo.xlsx';
        $key  = trim($carpeta, '/') . '/' . date('Ymd') . '_' . bin2hex(random_bytes(6)) . '_' . $safe;

        $this->putObjectFromFile($key, $rutaLocal, $mimeOverride !== '' ? $mimeOverride : $this->mimeDeNombre($nombreOriginal));

        return self::STORED_PREFIX . $key;
    }

    /** Descarga el objeto a un archivo temporal y devuelve su ruta. */
    public function descargarATemp(string $stored, string $extension = 'xlsx'): string
    {
        if (! self::esStoredS3($stored)) {
            throw new RuntimeException('No es una clave S3');
        }

        $body = $this->getObjectBody(self::claveDe($stored));
        $tmp  = tempnam(sys_get_temp_dir(), 'pf_s3_');
        if ($tmp === false) {
            throw new RuntimeException('No se pudo crear temporal para S3');
        }
        $ruta = $tmp . '.' . ltrim($extension, '.');
        @unlink($tmp);
        if (file_put_contents($ruta, $body) === false) {
            throw new RuntimeException('No se pudo guardar el Excel descargado de S3');
        }

        return $ruta;
    }

    /** Cuerpo completo en memoria (copias internas / materializar). */
    public function getObjectBody(string $key): string
    {
        return (string) $this->getObjectPsrResponse($key, false)->getBody();
    }

    /**
     * GET firmado a S3. Con $stream=true el body se lee a demanda (proxy al navegador con progreso real).
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getObjectPsrResponse(string $key, bool $stream = true)
    {
        $amzDate     = gmdate('Ymd\THis\Z');
        $dateStamp   = gmdate('Ymd');
        $region      = $this->config->region !== '' ? $this->config->region : 'auto';
        $service     = 's3';
        $payloadHash = 'UNSIGNED-PAYLOAD';

        [$host, $canonicalUri, $url] = $this->resolverHostYUri($key);

        $headers = [
            'host'                 => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $amzDate,
        ];
        ksort($headers);

        $signedHeaderNames = implode(';', array_keys($headers));
        $canonicalHeaders  = '';
        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name . ':' . trim($value) . "\n";
        }

        $canonicalRequest = implode("\n", [
            'GET',
            $canonicalUri,
            '',
            $canonicalHeaders,
            $signedHeaderNames,
            $payloadHash,
        ]);

        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";
        $stringToSign    = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);
        $signature       = $this->firmar($this->config->secretKey, $dateStamp, $region, $service, $stringToSign);
        $authorization   = 'AWS4-HMAC-SHA256 Credential=' . $this->config->accessKey . '/' . $credentialScope
            . ', SignedHeaders=' . $signedHeaderNames
            . ', Signature=' . $signature;

        $response = $this->http->get($url, [
            'stream'  => $stream,
            'headers' => [
                'Host'                 => $host,
                'x-amz-content-sha256' => $payloadHash,
                'x-amz-date'           => $amzDate,
                'Authorization'        => $authorization,
            ],
        ]);

        $code = $response->getStatusCode();
        if ($code < 200 || $code >= 300) {
            $err = (string) $response->getBody();
            throw new RuntimeException('S3 GetObject falló (' . $code . '): ' . mb_substr($err, 0, 400));
        }

        return $response;
    }

    private function putObjectFromFile(string $key, string $rutaLocal, string $contentType): void
    {
        $payloadHash = hash_file('sha256', $rutaLocal);
        if ($payloadHash === false) {
            throw new RuntimeException('No se pudo leer el archivo para subir a S3');
        }

        $amzDate   = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $region    = $this->config->region !== '' ? $this->config->region : 'auto';
        $service   = 's3';

        [$host, $canonicalUri, $url] = $this->resolverHostYUri($key);

        $headers = [
            'content-type'         => $contentType,
            'host'                 => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $amzDate,
        ];
        ksort($headers);

        $signedHeaderNames = implode(';', array_keys($headers));
        $canonicalHeaders  = '';
        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name . ':' . trim($value) . "\n";
        }

        $canonicalRequest = implode("\n", [
            'PUT',
            $canonicalUri,
            '',
            $canonicalHeaders,
            $signedHeaderNames,
            $payloadHash,
        ]);

        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";
        $stringToSign    = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);
        $signature = $this->firmar($this->config->secretKey, $dateStamp, $region, $service, $stringToSign);
        $authorization = 'AWS4-HMAC-SHA256 Credential=' . $this->config->accessKey . '/' . $credentialScope
            . ', SignedHeaders=' . $signedHeaderNames
            . ', Signature=' . $signature;

        $stream = fopen($rutaLocal, 'rb');
        if ($stream === false) {
            throw new RuntimeException('No se pudo abrir el archivo para subir a S3');
        }

        try {
            $response = $this->http->put($url, [
                'body'    => $stream,
                'headers' => [
                    'Content-Type'         => $contentType,
                    'Host'                 => $host,
                    'x-amz-content-sha256' => $payloadHash,
                    'x-amz-date'           => $amzDate,
                    'Authorization'        => $authorization,
                ],
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $code = $response->getStatusCode();
        if ($code < 200 || $code >= 300) {
            $body = (string) $response->getBody();
            throw new RuntimeException('S3 PutObject falló (' . $code . '): ' . mb_substr($body, 0, 400));
        }
    }

    /**
     * @return array{0: string, 1: string, 2: string} host, canonicalUri, fullUrl
     */
    private function resolverHostYUri(string $key): array
    {
        $encodedKey = $this->encodeKeyPath($key);

        if ($this->config->forcePathStyle) {
            $host = parse_url($this->config->endpoint, PHP_URL_HOST) ?: 'storage.railway.app';
            $canonicalUri = '/' . rawurlencode($this->config->bucket) . $encodedKey;
            $url = rtrim($this->config->endpoint, '/') . '/' . rawurlencode($this->config->bucket) . $encodedKey;

            return [$host, $canonicalUri, $url];
        }

        $endpointHost = parse_url($this->config->endpoint, PHP_URL_HOST) ?: 'storage.railway.app';
        $host         = $this->config->bucket . '.' . $endpointHost;
        $canonicalUri = $encodedKey === '' ? '/' : $encodedKey;
        $url          = $this->virtualHostBase() . $encodedKey;

        return [$host, $canonicalUri, $url];
    }

    private function virtualHostBase(): string
    {
        $scheme = parse_url($this->config->endpoint, PHP_URL_SCHEME) ?: 'https';
        $endpointHost = parse_url($this->config->endpoint, PHP_URL_HOST) ?: 'storage.railway.app';

        return $scheme . '://' . $this->config->bucket . '.' . $endpointHost;
    }

    /** /a/b → /a/b con cada segmento urlencoded */
    private function encodeKeyPath(string $key): string
    {
        $key = ltrim(str_replace('\\', '/', $key), '/');
        if ($key === '') {
            return '/';
        }
        $parts = array_map('rawurlencode', explode('/', $key));

        return '/' . implode('/', $parts);
    }

    private function firmar(string $secret, string $dateStamp, string $region, string $service, string $stringToSign): string
    {
        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $secret, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        return hash_hmac('sha256', $stringToSign, $kSigning);
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

    public static function mensajeErrorAmigable(Throwable $e): string
    {
        return 'No se pudo subir al storage S3: ' . $e->getMessage();
    }
}
