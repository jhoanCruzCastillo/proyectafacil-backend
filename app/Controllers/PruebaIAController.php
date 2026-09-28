<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Sandbox de prueba (ruta /test del frontend, solo superusuario) para validar si es viable un flujo
 * "estilo ChatGPT Premium": subir PDF/Excel + un mensaje, y que la IA responda con texto Y CON
 * ARCHIVOS GENERADOS de verdad (no solo análisis en texto).
 *
 * Investigado antes de construir esto (2026-09-26): el proveedor activo del resto de la app (ver
 * Config\Ia — hoy Kimi u OpenAI según `ia.proveedor`) usa el endpoint Chat Completions, que NO
 * soporta esto — ni siquiera Kimi con su proveedor activo tiene un equivalente a ejecución de
 * código en sandbox (su API de archivos solo extrae texto). Por eso este controlador llama a
 * OpenAI DIRECTO con su propia key (`config('Ia')->openaiApiKey`, sin pasar por
 * apiKeyActiva()/endpointActivo()), usando el único mecanismo real que sí soporta esto: la
 * Responses API (`POST /v1/responses`) + herramienta `code_interpreter` (un sandbox de Python real
 * del lado de OpenAI que lee los archivos subidos y puede generar archivos de salida).
 *
 * Completamente aislado del resto de la app — no comparte código con LlenadoIAController ni
 * AsistenteIAController, y no depende de qué proveedor esté activo. Efímero a propósito: no
 * persiste nada en BD ni sube nada a S3/Cloudinary — los archivos generados se devuelven en base64
 * en la misma respuesta HTTP. Es un playground para decidir si vale la pena construir esto de
 * verdad en algún flujo real, no una feature de producción.
 */
class PruebaIAController extends BaseController
{
    private const ENDPOINT_RESPONSES = 'https://api.openai.com/v1/responses';
    private const ENDPOINT_FILES     = 'https://api.openai.com/v1/files';
    private const ENDPOINT_CONTAINER_FILE = 'https://api.openai.com/v1/containers/%s/files/%s/content';
    private const MODELO_DEFECTO     = 'gpt-5';

    private const EXTENSIONES_PERMITIDAS = ['pdf', 'xlsx', 'xls', 'csv', 'docx', 'txt', 'json', 'png', 'jpg', 'jpeg'];
    private const TAMANO_MAXIMO_ARCHIVO  = 20 * 1024 * 1024; // 20 MB — generoso para pruebas, sin más criterio que ese.

    /** Mismos precios/modelos ya vetados y en uso real por LlenadoIAController::PRECIOS_OPENAI_POR_MTOK
     * (setiembre 2026) — se duplica acá (en vez de extraerlo a un lugar común) porque este controlador
     * es deliberadamente independiente de LlenadoIAController, sin nada compartido. Solo se ofrecen en
     * el selector los modelos con precio conocido: mostrar un costo inventado sería peor que no
     * mostrar el modelo. */
    private const PRECIOS_POR_MTOK = [
        'gpt-5'      => ['input' => 1.25, 'output' => 10.00],
        'gpt-5-mini' => ['input' => 0.25, 'output' => 2.00],
        'gpt-5-nano' => ['input' => 0.05, 'output' => 0.40],
    ];

    /** Modelos disponibles en el selector del sandbox — ver PruebaIAPage.vue. */
    public function modelos(): ResponseInterface
    {
        if (($resp = $this->exigirSuperusuario()) !== null) {
            return $resp;
        }

        $modelos = [];
        foreach (self::PRECIOS_POR_MTOK as $id => $precios) {
            $modelos[] = [
                'id'                     => $id,
                'precioEntradaUsdPorMtok' => $precios['input'],
                'precioSalidaUsdPorMtok'  => $precios['output'],
            ];
        }

        return $this->response->setJSON($modelos);
    }

    public function chat(): ResponseInterface
    {
        if (($resp = $this->exigirSuperusuario()) !== null) {
            return $resp;
        }

        $apiKey = trim((string) (config('Ia')->openaiApiKey ?? ''));
        if ($apiKey === '') {
            return $this->response->setStatusCode(503)->setJSON(['error' => 'Falta configurar ia.openaiApiKey en el servidor (ver .env del backend).']);
        }

        $body                = $this->request->getJSON(true) ?? [];
        $mensaje             = trim((string) ($body['mensaje'] ?? ''));
        $archivosEntrada     = is_array($body['archivos'] ?? null) ? $body['archivos'] : [];
        $previousResponseId  = trim((string) ($body['previousResponseId'] ?? ''));
        $modeloPedido        = trim((string) ($body['modelo'] ?? ''));
        $modelo              = array_key_exists($modeloPedido, self::PRECIOS_POR_MTOK) ? $modeloPedido : self::MODELO_DEFECTO;

        if ($mensaje === '' && $archivosEntrada === []) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Escribe un mensaje o adjunta al menos un archivo.']);
        }

        $tmpFiles = [];
        $fileIds  = [];

        foreach ($archivosEntrada as $archivo) {
            $nombre  = trim((string) ($archivo['nombre'] ?? ''));
            $dataUrl = (string) ($archivo['dataUrl'] ?? '');
            $ext     = strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION));

            if ($nombre === '' || $dataUrl === '') {
                continue;
            }
            if (! in_array($ext, self::EXTENSIONES_PERMITIDAS, true)) {
                $this->limpiar($tmpFiles);

                return $this->response->setStatusCode(400)->setJSON(['error' => "Extensión no permitida: .{$ext}"]);
            }

            $coma    = strpos($dataUrl, ',');
            $binario = base64_decode(substr($dataUrl, $coma !== false ? $coma + 1 : 0), true);
            if ($binario === false || strlen($binario) > self::TAMANO_MAXIMO_ARCHIVO) {
                $this->limpiar($tmpFiles);

                return $this->response->setStatusCode(400)->setJSON(['error' => "\"{$nombre}\" no es un archivo válido o supera los 20 MB."]);
            }

            $tmp = tempnam(sys_get_temp_dir(), 'prueba_ia_');
            file_put_contents($tmp, $binario);
            $tmpFiles[] = $tmp;

            $fileId = $this->subirArchivoOpenAI($apiKey, $tmp, $nombre);
            if ($fileId === null) {
                $this->limpiar($tmpFiles);

                return $this->response->setStatusCode(502)->setJSON(['error' => "No se pudo subir \"{$nombre}\" a OpenAI."]);
            }
            $fileIds[] = $fileId;
        }

        $this->limpiar($tmpFiles);

        $contenido = [];
        foreach ($fileIds as $fileId) {
            $contenido[] = ['type' => 'input_file', 'file_id' => $fileId];
        }
        $contenido[] = ['type' => 'input_text', 'text' => $mensaje !== '' ? $mensaje : 'Analiza el/los archivo(s) adjunto(s).'];

        $payload = [
            'model' => $modelo,
            'tools' => [
                ['type' => 'code_interpreter', 'container' => ['type' => 'auto']],
            ],
            'input' => [
                ['role' => 'user', 'content' => $contenido],
            ],
            'store' => true,
        ];
        if ($previousResponseId !== '') {
            $payload['previous_response_id'] = $previousResponseId;
        }

        [$estado, $cuerpo, $error] = $this->post(self::ENDPOINT_RESPONSES, $apiKey, $payload, 240);
        if ($cuerpo === false || $estado < 200 || $estado >= 300) {
            log_message('error', '[prueba-ia] Responses API falló ({estado}): {cuerpo} {error}', [
                'estado' => $estado, 'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 800) : '(sin cuerpo)', 'error' => $error,
            ]);

            return $this->response->setStatusCode(502)->setJSON([
                'error'   => 'La IA no pudo procesar la solicitud.',
                'detalle' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : $error,
            ]);
        }

        $json = json_decode((string) $cuerpo, true);

        return $this->response->setJSON($this->normalizarRespuesta(is_array($json) ? $json : [], $apiKey, $modelo));
    }

    private function exigirSuperusuario(): ?ResponseInterface
    {
        if (session()->get('usuario_rol') !== 'superusuario') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'No tienes permiso para esta acción']);
        }

        return null;
    }

    /** @return array{0:int,1:string|false,2:string} [estado HTTP, cuerpo crudo, mensaje de error de curl] */
    private function post(string $url, string $apiKey, array $payload, int $timeoutSegundos): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['content-type: application/json', 'authorization: Bearer ' . $apiKey],
            CURLOPT_TIMEOUT        => $timeoutSegundos,
        ]);
        $cuerpo = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        return [$estado, $cuerpo, $error];
    }

    /** Files API de OpenAI (purpose=user_data — entrada genérica para la Responses API, ver
     * https://developers.openai.com/api/docs/guides/pdf-files). Devuelve el file_id, o null si falló. */
    private function subirArchivoOpenAI(string $apiKey, string $rutaTmp, string $nombreOriginal): ?string
    {
        $ch = curl_init(self::ENDPOINT_FILES);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['authorization: Bearer ' . $apiKey],
            CURLOPT_POSTFIELDS     => [
                'purpose' => 'user_data',
                'file'    => new \CURLFile($rutaTmp, $this->mimeDeNombre($nombreOriginal), $nombreOriginal),
            ],
            CURLOPT_TIMEOUT => 60,
        ]);
        $cuerpo = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($cuerpo === false || $estado < 200 || $estado >= 300) {
            log_message('error', '[prueba-ia] Subida de archivo a OpenAI falló ({estado}): {cuerpo}', [
                'estado' => $estado, 'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : '(sin cuerpo)',
            ]);

            return null;
        }

        $json = json_decode((string) $cuerpo, true);

        return is_array($json) && isset($json['id']) ? (string) $json['id'] : null;
    }

    /**
     * Recorre `output` de la respuesta (ver
     * https://developers.openai.com/api/docs/api-reference/responses/create) juntando el texto de
     * los mensajes y las citas `container_file_citation` (archivos que Code Interpreter generó),
     * y descarga cada una de esas citas para devolverlas ya en base64 al frontend.
     */
    private function normalizarRespuesta(array $json, string $apiKey, string $modelo): array
    {
        $textos      = [];
        $citas       = [];
        foreach ($json['output'] ?? [] as $item) {
            if (($item['type'] ?? '') !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $c) {
                if (($c['type'] ?? '') !== 'output_text') {
                    continue;
                }
                $textos[] = (string) ($c['text'] ?? '');
                foreach ($c['annotations'] ?? [] as $a) {
                    if (($a['type'] ?? '') === 'container_file_citation') {
                        $citas[] = $a;
                    }
                }
            }
        }

        $archivos = [];
        foreach ($citas as $cita) {
            $nombre  = (string) ($cita['filename'] ?? 'archivo');
            $dataUrl = $this->descargarArchivoContainer($apiKey, (string) ($cita['container_id'] ?? ''), (string) ($cita['file_id'] ?? ''), $nombre);
            if ($dataUrl !== null) {
                $archivos[] = ['nombre' => $nombre, 'dataUrl' => $dataUrl];
            }
        }

        $mensajeTexto = trim(implode("\n\n", array_filter($textos)));
        $usage        = is_array($json['usage'] ?? null) ? $json['usage'] : [];

        return [
            'responseId' => (string) ($json['id'] ?? ''),
            'mensaje'    => $mensajeTexto !== '' ? $mensajeTexto : (string) ($json['output_text'] ?? ''),
            'archivos'   => $archivos,
            'modelo'     => $modelo,
            'usage'      => $usage,
            'costoUsd'   => $this->calcularCostoUsd($modelo, $usage),
        ];
    }

    /**
     * Mismo criterio que LlenadoIAController::registrarUsoOpenAI() — adaptado a las claves de la
     * Responses API (`input_tokens`/`output_tokens`/`input_tokens_details.cached_tokens`) en vez de
     * las de Chat Completions (`prompt_tokens`/`completion_tokens`). Los `reasoning_tokens` ya vienen
     * incluidos DENTRO de `output_tokens` (es un desglose, no un cargo aparte) — no se suman de nuevo.
     * OJO: esto NO incluye el costo del contenedor de Code Interpreter en sí (OpenAI lo cobra aparte
     * por tiempo activo, no viene en `usage`) — el costo real de la sesión será algo mayor a este número.
     */
    private function calcularCostoUsd(string $modelo, array $usage): float
    {
        $entrada = (int) ($usage['input_tokens'] ?? 0);
        $salida  = (int) ($usage['output_tokens'] ?? 0);
        $cache   = (int) ($usage['input_tokens_details']['cached_tokens'] ?? 0);

        $entradaNormal = max(0, $entrada - $cache);
        $precios       = self::PRECIOS_POR_MTOK[$modelo] ?? self::PRECIOS_POR_MTOK[self::MODELO_DEFECTO];

        return ($entradaNormal * $precios['input'] + $cache * $precios['input'] * 0.1 + $salida * $precios['output']) / 1_000_000;
    }

    private function descargarArchivoContainer(string $apiKey, string $containerId, string $fileId, string $nombre): ?string
    {
        if ($containerId === '' || $fileId === '') {
            return null;
        }

        $ch = curl_init(sprintf(self::ENDPOINT_CONTAINER_FILE, rawurlencode($containerId), rawurlencode($fileId)));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['authorization: Bearer ' . $apiKey],
            CURLOPT_TIMEOUT        => 60,
        ]);
        $binario = curl_exec($ch);
        $estado  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($binario === false || $estado < 200 || $estado >= 300) {
            log_message('error', '[prueba-ia] No se pudo descargar el archivo generado {nombre} ({estado}).', ['nombre' => $nombre, 'estado' => $estado]);

            return null;
        }

        return 'data:' . $this->mimeDeNombre($nombre) . ';base64,' . base64_encode($binario);
    }

    private function mimeDeNombre(string $nombre): string
    {
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf'          => 'application/pdf',
            'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls'          => 'application/vnd.ms-excel',
            'csv'          => 'text/csv',
            'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc'          => 'application/msword',
            'txt'          => 'text/plain',
            'json'         => 'application/json',
            'png'          => 'image/png',
            'jpg', 'jpeg'  => 'image/jpeg',
            default        => 'application/octet-stream',
        };
    }

    private function limpiar(array $rutas): void
    {
        foreach ($rutas as $ruta) {
            @unlink($ruta);
        }
    }
}
