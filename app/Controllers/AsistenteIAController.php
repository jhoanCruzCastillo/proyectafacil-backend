<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Asesor de llenado del cliente (el chat flotante del editor de fichas).
//
// El navegador NUNCA habla con OpenAI directamente: manda aquí la pregunta y este controlador arma
// el prompt con el contexto que el administrador redactó para esa sección y llama a la API con la
// clave del servidor. Así la clave no sale de la máquina y el contexto — que es propiedad del
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
        if ($config->openaiApiKey === '') {
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

        $texto = $this->llamarOpenAI($config, $this->construirSistema($plantillaId, $seccionId), $mensajes);
        if ($texto === null) {
            return $this->response->setStatusCode(502)->setJSON([
                'error' => 'No se pudo consultar al asesor de IA en este momento. Inténtalo de nuevo.',
            ]);
        }

        return $this->response->setJSON(['texto' => $texto]);
    }

    /** System prompt = rol + contexto local de la sección + contextos generales de la ficha + globales asociados. */
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
            $texto = $this->contenidoDeUrl($contexto['url'] ?? null);
            if ($texto !== '') {
                $partes[] = "Contexto e instrucciones para esta sección:\n" . $texto;
            }

            $globales = $db->table('contexto_seccion_globales sg')
                ->select('g.nombre, g.url')
                ->join('contextos_ia_globales g', 'g.id = sg.contexto_global_id')
                ->where('sg.contexto_seccion_id', (int) $contexto['id'])
                ->get()->getResultArray();

            foreach ($globales as $g) {
                $texto = $this->contenidoDeUrl($g['url'] ?? null);
                if ($texto !== '') {
                    $partes[] = "Contexto adicional — {$g['nombre']}:\n{$texto}";
                }
            }
        }

        // Generales: propios de esta ficha, aplican a todas sus secciones sin necesidad de asociarlos uno por uno.
        $generales = $db->table('contextos_ia_general')->where('plantilla_id', $plantillaId)->get()->getResultArray();
        foreach ($generales as $g) {
            $texto = $this->contenidoDeUrl($g['url'] ?? null);
            if ($texto !== '') {
                $partes[] = "Contexto general de esta ficha — {$g['nombre']}:\n{$texto}";
            }
        }

        return implode("\n\n", $partes);
    }

    /** Descarga el .md de Cloudinary; '' si no hay URL o la descarga falla (nunca revienta el prompt por esto). */
    private function contenidoDeUrl(?string $url): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $cuerpo = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($cuerpo !== false && $estado >= 200 && $estado < 300) ? trim((string) $cuerpo) : '';
    }

    /** @return string|null texto de la respuesta, o null si la llamada falló */
    private function llamarOpenAI(object $config, string $sistema, array $mensajes): ?string
    {
        // Chat Completions: el system prompt va como un mensaje más, primero en la lista.
        $mensajesConSistema = array_merge([['role' => 'system', 'content' => $sistema]], $mensajes);

        $ch = curl_init($config->openaiEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'                 => $config->openaiModelo,
                'max_completion_tokens' => $config->maxTokens,
                'messages'              => $mensajesConSistema,
            ]),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'content-type: application/json',
                'authorization: Bearer ' . $config->openaiApiKey,
            ],
        ]);
        $cuerpo    = curl_exec($ch);
        $estado    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($cuerpo === false || $estado < 200 || $estado >= 300) {
            // Se registra el detalle en el log del servidor, pero al cliente solo le llega un
            // mensaje genérico: el cuerpo del error puede traer datos de la cuenta.
            log_message('error', '[asistente-ia] OpenAI respondió {estado}: {cuerpo} {curl}', [
                'estado' => $estado,
                'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : '(sin cuerpo)',
                'curl'   => $errorCurl,
            ]);

            return null;
        }

        $json  = json_decode((string) $cuerpo, true);
        $texto = trim((string) ($json['choices'][0]['message']['content'] ?? ''));

        return $texto === '' ? null : $texto;
    }
}
