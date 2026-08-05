<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Asesor de llenado del cliente (el chat flotante del editor de fichas).
//
// El navegador NUNCA habla con Anthropic directamente: manda aquí la pregunta y este controlador
// arma el prompt con el contexto que el administrador redactó para esa sección y llama a la API con
// la clave del servidor. Así la clave no sale de la máquina y el contexto — que es propiedad del
// producto — tampoco viaja al cliente.
//
// OJO con el nombre: no puede llamarse "AsesorIAController" porque en Windows el sistema de
// archivos ignora mayúsculas y colisionaría con AsesoriaController (asesorías 1:1 con docentes),
// que es otra cosa completamente distinta.
class AsistenteIAController extends BaseController
{
    public function consultar(): ResponseInterface
    {
        $dto         = $this->request->getJSON(true) ?? [];
        $plantillaId = (int) ($dto['plantillaId'] ?? 0);
        $seccionId   = (string) ($dto['seccionId'] ?? '');
        $pregunta    = trim((string) ($dto['pregunta'] ?? ''));
        $historial   = (array) ($dto['historial'] ?? []);

        if ($pregunta === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Escribe una pregunta']);
        }

        $config = config('Ia');
        if ($config->anthropicApiKey === '') {
            return $this->response->setStatusCode(503)->setJSON([
                'error' => 'El asesor de IA todavía no está configurado en el servidor.',
            ]);
        }

        $mensajes = [];
        // Solo los últimos turnos: el contexto de la sección ya va en el system prompt, y arrastrar
        // todo el historial encarece cada llamada sin aportar nada.
        foreach (array_slice($historial, -6) as $m) {
            $texto = trim((string) ($m['texto'] ?? ''));
            if ($texto === '') {
                continue;
            }
            $mensajes[] = [
                'role'    => ($m['autor'] ?? '') === 'usuario' ? 'user' : 'assistant',
                'content' => $texto,
            ];
        }
        $mensajes[] = ['role' => 'user', 'content' => $pregunta];

        $texto = $this->llamarAnthropic($config, $this->construirSistema($plantillaId, $seccionId), $mensajes);
        if ($texto === null) {
            return $this->response->setStatusCode(502)->setJSON([
                'error' => 'No se pudo consultar al asesor de IA en este momento. Inténtalo de nuevo.',
            ]);
        }

        return $this->response->setJSON(['texto' => $texto]);
    }

    /** System prompt = rol + contexto local de la sección + contextos globales asociados. */
    private function construirSistema(int $plantillaId, string $seccionId): string
    {
        $db = db_connect();
        $partes = [
            'Eres un asesor experto en formulación de proyectos de inversión pública del Perú (Invierte.pe).',
            'Ayudas a un usuario a llenar una ficha técnica oficial del MEF.',
            'Responde en español, breve y concreto. No inventes datos: si falta información, pídesela al usuario.',
        ];

        $contexto = $db->table('contextos_ia_seccion')
            ->where('plantilla_id', $plantillaId)
            ->where('seccion_id', $seccionId)
            ->get()->getRowArray();

        if ($contexto !== null) {
            if (trim((string) $contexto['markdown']) !== '') {
                $partes[] = "Contexto e instrucciones para esta sección:\n" . $contexto['markdown'];
            }

            $globales = $db->table('contexto_seccion_globales sg')
                ->select('g.nombre, g.markdown')
                ->join('contextos_ia_globales g', 'g.id = sg.contexto_global_id')
                ->where('sg.contexto_seccion_id', (int) $contexto['id'])
                ->get()->getResultArray();

            foreach ($globales as $g) {
                if (trim((string) $g['markdown']) !== '') {
                    $partes[] = "Contexto adicional — {$g['nombre']}:\n{$g['markdown']}";
                }
            }
        }

        return implode("\n\n", $partes);
    }

    /** @return string|null texto de la respuesta, o null si la llamada falló */
    private function llamarAnthropic(object $config, string $sistema, array $mensajes): ?string
    {
        $ch = curl_init($config->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'      => $config->modelo,
                'max_tokens' => $config->maxTokens,
                'system'     => $sistema,
                'messages'   => $mensajes,
            ]),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'content-type: application/json',
                'x-api-key: ' . $config->anthropicApiKey,
                'anthropic-version: ' . $config->anthropicVersion,
            ],
        ]);
        $cuerpo    = curl_exec($ch);
        $estado    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($cuerpo === false || $estado < 200 || $estado >= 300) {
            // Se registra el detalle en el log del servidor, pero al cliente solo le llega un
            // mensaje genérico: el cuerpo del error puede traer datos de la cuenta.
            log_message('error', '[asistente-ia] Anthropic respondió {estado}: {cuerpo} {curl}', [
                'estado' => $estado,
                'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : '(sin cuerpo)',
                'curl'   => $errorCurl,
            ]);

            return null;
        }

        // La respuesta trae `content` como lista de bloques; solo interesan los de texto.
        $json  = json_decode((string) $cuerpo, true);
        $texto = '';
        foreach ((array) ($json['content'] ?? []) as $bloque) {
            if (($bloque['type'] ?? '') === 'text') {
                $texto .= $bloque['text'] ?? '';
            }
        }

        return trim($texto) === '' ? null : trim($texto);
    }
}
