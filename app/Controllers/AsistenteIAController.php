<?php

namespace App\Controllers;

use App\Models\EjemploModel;
use CodeIgniter\HTTP\ResponseInterface;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

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

    // Pedido explícito del usuario: "ayúdame a llenar/verificar el campo X" — a diferencia de
    // consultar() (charla libre, responde texto/markdown), acá la IA responde SIEMPRE en JSON con una
    // explicación y una lista corta de valores recomendados, que el frontend convierte en botones
    // seleccionables (ver AsesorIAChat.vue). El campo en sí lo busca y desambigua el FRONTEND (ya
    // tiene toda la plantilla cargada) — acá solo llega el descriptor del campo ya resuelto, para no
    // duplicar esa búsqueda del lado servidor.
    //
    // Dos modos (botón "?" del campo, ver FieldCard.vue):
    //  - "llenar" (campo vacío): recomienda valores nuevos.
    //  - "verificar" (campo con valor): evalúa si el valor actual es correcto y solo da alternativas
    //    si conviene mejorarlo.
    //
    // Dos niveles de contexto, escalando solo si hace falta (pedido explícito del usuario — no
    // siempre mandar los PDFs de "Contexto general", son pesados y la mayoría de campos ya se
    // resuelven con la fuente de la verdad + guías): 1) guías del admin + fuente de la verdad del
    // cliente + ejemplo de referencia + valores ya confirmados de esta sección; 2) si con eso la IA
    // reporta que no tiene información concreta ("suficiente":false), se reintenta agregando el texto
    // de los PDF de "Contexto general" de la plantilla. Si ni así alcanza, se le ofrece al usuario la
    // lista de esos PDFs para que los revise él mismo (ver sinInformacionSuficiente en la respuesta).
    public function ayudaCampo(): ResponseInterface
    {
        $dto            = $this->request->getJSON(true) ?? [];
        $plantillaId    = (int) ($dto['plantillaId'] ?? 0);
        $seccionId      = (string) ($dto['seccionId'] ?? '');
        $ejemploId      = (int) ($dto['ejemploId'] ?? 0);
        $campo          = (array) ($dto['campo'] ?? []);
        $etiqueta       = trim((string) ($campo['etiqueta'] ?? ''));
        $contextoSeccion = (array) ($dto['contextoSeccion'] ?? []);
        $modo           = (string) ($dto['modo'] ?? 'llenar');
        if ($modo !== 'verificar' || trim((string) ($campo['valorActual'] ?? '')) === '') {
            $modo = 'llenar'; // defensivo: verificar sin valor actual no tiene sentido
        }

        if ($etiqueta === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta el campo a ayudar']);
        }

        $config = config('Ia');
        if ($config->openaiApiKey === '') {
            return $this->response->setStatusCode(503)->setJSON([
                'error' => 'El asesor de IA todavía no está configurado en el servidor.',
            ]);
        }

        $sistemaBase = $this->construirSistema($plantillaId, $seccionId);

        // Fuente de la verdad DE ESTA FICHA (documentos/texto que el cliente cargó para su propio
        // proyecto) — encontrado en vivo (2026-09-06): sin esto, la IA solo tenía guías genéricas del
        // admin y nunca los datos reales del proyecto, así que "Responsable de la UF" respondía con un
        // placeholder ("nombre del jefe de la UF según resolución...") en vez del nombre real que el
        // cliente ya había subido en su PDF. No se mete en construirSistema() porque esa función
        // también la usa consultar() (charla libre), que nunca mandó ejemploId y no debe empezar a
        // exigirlo.
        if ($ejemploId > 0) {
            $ejemplo      = (new EjemploModel())->find($ejemploId);
            $fuenteVerdad = $ejemplo ? $this->fuenteDeLaVerdad($ejemploId, $ejemplo['fuente_verdad_texto'] ?? '') : '';
            if (trim($fuenteVerdad) !== '') {
                $sistemaBase .= "\n\nFuente de la verdad (información real de ESTE proyecto, cargada por el cliente — "
                    . "usa estos datos concretos cuando existan, en vez de una descripción genérica):\n{$fuenteVerdad}";
            }
        }

        // Ejemplo de otro proyecto ya resuelto (a lo más uno por plantilla, ver
        // EjemplosController::marcarReferenciaIA) — mismo criterio few-shot que usa el llenado
        // automático (LlenadoIAController::valoresEjemploReferencia), acotado a los campos de ESTA
        // sección para no inflar el prompt con las otras ~170 filas de la ficha.
        $identificadoresRelevantes = array_merge(array_keys($contextoSeccion), [(string) ($campo['identificador'] ?? '')]);
        $referencia = array_intersect_key($this->valoresEjemploReferencia($plantillaId), array_flip($identificadoresRelevantes));
        if ($referencia !== []) {
            $lineas = [];
            foreach ($referencia as $id => $valor) {
                $lineas[] = "{$id}: {$valor}";
            }
            $sistemaBase .= "\n\nEjemplo real de OTRO proyecto ya resuelto en esta misma ficha (úsalo de apoyo/inspiración de "
                . "estilo y contenido — nunca lo copies literalmente salvo que realmente aplique a este proyecto):\n" . implode("\n", $lineas);
        }

        // Valores que el usuario ya confirmó en esta misma sección — le da a la IA contexto
        // situacional (ej. el distrito ya indicado en un campo anterior) sin tener que repetirlo.
        $contextoSeccionTexto = array_filter(array_map(static fn ($v) => trim((string) $v), $contextoSeccion), static fn (string $v) => $v !== '');
        if ($contextoSeccionTexto !== []) {
            $lineas = [];
            foreach ($contextoSeccionTexto as $id => $valor) {
                $lineas[] = "{$id}: {$valor}";
            }
            $sistemaBase .= "\n\nValores que el usuario ya confirmó en OTROS campos de esta misma sección (contexto situacional):\n" . implode("\n", $lineas);
        }

        $usuario = $this->construirPromptCampo($campo, $modo);

        $sistema1  = $sistemaBase . "\n\n" . $this->reglasRespuestaCampo($modo);
        $resultado = $this->llamarOpenAIJson($config, $sistema1, $usuario);
        if ($resultado === null) {
            return $this->response->setStatusCode(502)->setJSON([
                'error' => 'No se pudo consultar al asesor de IA en este momento. Inténtalo de nuevo.',
            ]);
        }

        if ($resultado['suficiente']) {
            return $this->response->setJSON($this->salidaAyudaCampo($resultado));
        }

        // Segundo intento: se le agregan los PDF de "Contexto general" de la plantilla (nunca se
        // mandan de entrada — son pesados y la mayoría de campos no los necesita).
        $archivos = $this->archivosContextoGeneral($plantillaId);
        $conTexto = array_filter($archivos, static fn (array $a) => trim((string) $a['contenido_texto']) !== '');
        if ($conTexto === []) {
            return $this->response->setJSON($this->salidaSinInformacion($resultado, $archivos));
        }

        $bloquePdfs = [];
        foreach ($conTexto as $a) {
            $bloquePdfs[] = "--- {$a['nombre']} ---\n{$a['contenido_texto']}";
        }
        $sistema2 = $sistemaBase
            . "\n\nMás información de referencia sobre esta ficha técnica (documentos oficiales cargados por el administrador — "
            . "revísalos con atención, aquí puede estar el dato concreto que te faltaba):\n" . implode("\n\n", $bloquePdfs)
            . "\n\n" . $this->reglasRespuestaCampo($modo);
        $resultado2 = $this->llamarOpenAIJson($config, $sistema2, $usuario);
        if ($resultado2 === null || ! $resultado2['suficiente']) {
            return $this->response->setJSON($this->salidaSinInformacion($resultado2 ?? $resultado, $archivos));
        }

        return $this->response->setJSON($this->salidaAyudaCampo($resultado2));
    }

    /** @param array{explicacion:string,opciones:list<string>,correcto:?bool} $resultado */
    private function salidaAyudaCampo(array $resultado): array
    {
        return [
            'explicacion' => $resultado['explicacion'],
            'opciones'    => $resultado['opciones'],
            'correcto'    => $resultado['correcto'],
        ];
    }

    /**
     * @param array{explicacion:string,opciones:list<string>,correcto:?bool} $resultado
     * @param list<array{id:string,nombre:string,url:string,contenido_texto:string}> $archivos
     */
    private function salidaSinInformacion(array $resultado, array $archivos): array
    {
        return [
            'explicacion' => $resultado['explicacion'] !== ''
                ? $resultado['explicacion']
                : 'No encontré información concreta de este proyecto para este campo — revisa los documentos de referencia de la ficha, o consúltalo con tu asesor humano.',
            'opciones'                 => [],
            'correcto'                 => null,
            'sinInformacionSuficiente' => true,
            'archivosContexto'         => array_map(static fn (array $a) => [
                'id' => $a['id'], 'nombre' => $a['nombre'], 'url' => $a['url'],
            ], $archivos),
        ];
    }

    /** @param array{identificador?:string,etiqueta?:string,tipo?:string,opciones?:array,valorActual?:string} $campo */
    private function construirPromptCampo(array $campo, string $modo): string
    {
        $etiqueta = (string) ($campo['etiqueta'] ?? '');
        $tipo     = (string) ($campo['tipo'] ?? 'texto_corto');
        $valorActual = trim((string) ($campo['valorActual'] ?? ''));
        $lineas   = [
            $modo === 'verificar'
                ? "El usuario pidió verificar el valor que ya escribió en el campo \"{$etiqueta}\" (identificador {$campo['identificador']}, tipo {$tipo})."
                : "El usuario pidió ayuda para llenar el campo \"{$etiqueta}\" (identificador {$campo['identificador']}, tipo {$tipo}).",
        ];

        $opciones = $campo['opciones'] ?? null;
        if (is_array($opciones) && $opciones !== []) {
            $lista = implode(' | ', array_map('strval', $opciones));
            $lineas[] = "Este campo solo acepta EXACTAMENTE una de estas opciones (catálogo cerrado): {$lista}. "
                . 'Tus "opciones" recomendadas deben ser un subconjunto literal de esta lista — nunca inventar otras ni modificar su texto.';
        }

        if ($modo === 'verificar') {
            $lineas[] = "Valor ACTUAL del campo, a evaluar: \"{$valorActual}\"";
            $lineas[] = 'Evalúa si ese valor es correcto y está bien fundamentado para este proyecto, usando el contexto de la ficha ya provisto.';
            $lineas[] = 'Explica en 2-4 líneas tu evaluación. Si el valor ya está bien, dilo claramente y deja "opciones" vacío ("opciones":[]).';
            $lineas[] = 'Si el valor debe corregirse o mejorarse, da entre 1 y 4 valores alternativos CONCRETOS (nunca genéricos), basados en ese mismo contexto.';
        } else {
            if ($valorActual !== '') {
                $lineas[] = "Valor actual del campo, por si el usuario ya escribió algo (puede estar incompleto o mal): \"{$valorActual}\"";
            }
            $lineas[] = 'Explica en 2-4 líneas de qué trata este campo y qué se espera que contenga, usando el contexto de la ficha ya provisto.';
            $lineas[] = 'Luego da entre 1 y 4 valores recomendados y CONCRETOS para este campo específico (nunca genéricos ni de relleno), basados en ese mismo contexto.';
        }

        return implode("\n", $lineas);
    }

    private function reglasRespuestaCampo(string $modo): string
    {
        $forma = $modo === 'verificar'
            ? '{"explicacion":"<texto breve en español, 2-4 líneas>","correcto":<true o false>,"opciones":["<alternativa 1>", "..."],"suficiente":<true o false>}'
            : '{"explicacion":"<texto breve en español, 2-4 líneas>","opciones":["<valor recomendado 1>","<valor recomendado 2>"],"suficiente":<true o false>}';

        return 'Para esta respuesta en particular, ignora cualquier instrucción previa sobre el formato de salida y responde '
            . 'SIEMPRE con un único objeto JSON válido, sin markdown y sin texto fuera del JSON, con esta forma exacta: '
            . "{$forma}. "
            . 'El arreglo "opciones" tiene entre 1 y 4 elementos cuando corresponde recomendar algo, y puede ir vacío ([]) '
            . 'cuando modo="verificar" y el valor actual ya está bien. Cada elemento es un valor literal y usable tal cual para llenar el '
            . 'campo (nunca una descripción de la opción). Si el campo tiene catálogo cerrado, usa exactamente el texto de esas opciones. '
            . '"suficiente" es tu autoevaluación honesta: true SOLO si tus opciones/explicación se basan en datos CONCRETOS de este '
            . 'proyecto (fuente de la verdad, ejemplo de referencia o guías con datos reales) — false si estás adivinando o dando algo '
            . 'genérico por falta de información real. Nunca marques "suficiente":true solo para cumplir el formato.';
    }

    /**
     * Igual que llamarOpenAI() pero en JSON mode y devolviendo ya el array parseado — usado solo por
     * ayudaCampo(). Se mantiene aparte de llamarOpenAI() porque el contrato de salida es distinto
     * (objeto estructurado, no texto libre) y mezclar ambos con un parámetro opcional complicaba más
     * de lo que ahorraba.
     *
     * @return array{explicacion:string,opciones:list<string>,correcto:?bool,suficiente:bool}|null
     */
    // gpt-5 es un modelo "de razonamiento": max_completion_tokens se reparte entre tokens de
    // razonamiento (ocultos) y la respuesta visible — encontrado en vivo probando este endpoint:
    // con $config->maxTokens (800, pensado para la charla libre de llamarOpenAI()) el modelo gastó
    // los 800 enteros en razonar y devolvió contenido vacío (finish_reason=length,
    // reasoning_tokens=800). Un tope generoso no encarece nada por sí solo — el costo real sigue
    // siendo por tokens efectivamente usados, esto solo evita el corte en seco.
    private const MAX_TOKENS_AYUDA_CAMPO = 4000;

    private function llamarOpenAIJson(object $config, string $sistema, string $usuario): ?array
    {
        $ch = curl_init($config->openaiEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                // gpt-5-mini en vez de gpt-5 (config->openaiModelo): "ayúdame a llenar/verificar el
                // campo X" es una tarea acotada contra un schema JSON fijo, igual que el llenado
                // automático de LlenadoIAController (mismo criterio ya usado ahí: un modelo más chico
                // rinde igual por mucho menos costo y latencia — ver openaiModeloLlenado en Config\Ia).
                'model'                 => $config->openaiModeloLlenado,
                'max_completion_tokens' => self::MAX_TOKENS_AYUDA_CAMPO,
                // "low": recorta el razonamiento oculto del modelo — encontrado en vivo (2026-09-07)
                // que sin esto una sola respuesta de "ayúdame a verificar el campo X" tardaba >10s.
                // Ver contenidoDeUrl() para la otra mitad del arreglo de latencia (caché de los .md).
                'reasoning_effort'      => 'low',
                'response_format'       => ['type' => 'json_object'],
                'messages'              => [
                    ['role' => 'system', 'content' => $sistema],
                    ['role' => 'user', 'content' => $usuario],
                ],
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
            log_message('error', '[asistente-ia] OpenAI (ayuda-campo) respondió {estado}: {cuerpo} {curl}', [
                'estado' => $estado,
                'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : '(sin cuerpo)',
                'curl'   => $errorCurl,
            ]);

            return null;
        }

        $json      = json_decode((string) $cuerpo, true);
        $contenido = trim((string) ($json['choices'][0]['message']['content'] ?? ''));
        $parsed    = json_decode($contenido, true);

        $explicacion = trim((string) ($parsed['explicacion'] ?? ''));
        $opcionesRaw = is_array($parsed['opciones'] ?? null) ? $parsed['opciones'] : [];
        $opciones    = array_values(array_filter(
            array_map(static fn ($o) => trim((string) $o), $opcionesRaw),
            static fn (string $o) => $o !== '',
        ));
        $correcto   = array_key_exists('correcto', (array) $parsed) ? (bool) $parsed['correcto'] : null;
        // Ausente = se asume true (no se ve una señal de que le falte información) — evita que un
        // modelo que olvide el campo dispare de más el segundo intento con los PDF.
        $suficiente = array_key_exists('suficiente', (array) $parsed) ? (bool) $parsed['suficiente'] : true;

        if ($explicacion === '') {
            log_message('warning', '[asistente-ia] ayuda-campo: respuesta sin explicacion utilizable: {contenido}', [
                'contenido' => substr($contenido, 0, 300),
            ]);

            return null;
        }

        return [
            'explicacion' => $explicacion,
            'opciones'    => array_slice($opciones, 0, 4),
            'correcto'    => $correcto,
            'suficiente'  => $suficiente,
        ];
    }

    /** Id del ejemplo marcado como "referencia para IA" en esta plantilla, o null si no hay ninguno
     * (a lo más uno por plantilla, ver EjemplosController::marcarReferenciaIA). Mismo criterio que
     * LlenadoIAController::ejemploReferenciaId() — duplicado a propósito, ver comentario de
     * fuenteDeLaVerdad() más abajo sobre por qué estos controladores no comparten helpers. */
    private function ejemploReferenciaId(int $plantillaId): ?int
    {
        $ref = db_connect()->table('ejemplos')->where('plantilla_id', $plantillaId)->where('es_referencia_ia', 1)->get()->getRowArray();

        return $ref !== null ? (int) $ref['id'] : null;
    }

    /** Mismo criterio que LlenadoIAController::valoresEjemploReferencia() — valores (identificador =>
     * texto plano) del ejemplo marcado como referencia, si hay uno y tiene algo llenado.
     *
     * @return array<string,string>
     */
    private function valoresEjemploReferencia(int $plantillaId): array
    {
        $refId = $this->ejemploReferenciaId($plantillaId);
        if ($refId === null) {
            return [];
        }
        $archivo = db_connect()->table('archivos')->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $refId)->get()->getRowArray();
        if (! $archivo || empty($archivo['contenido_json'])) {
            return [];
        }
        $contenido = json_decode((string) $archivo['contenido_json'], true);
        $valores   = is_array($contenido['valores'] ?? null) ? $contenido['valores'] : [];

        return array_filter(
            array_map(static fn ($v) => is_string($v) ? $v : null, $valores),
            static fn ($v) => $v !== null && trim($v) !== '',
        );
    }

    /**
     * PDF de "Contexto general" de la plantilla (ver ContextosIAController) — último recurso cuando
     * ni la fuente de la verdad ni las guías bastan. El texto se extrae al subir el archivo
     * (ContextosIAController::subirArchivoGeneral); esto solo hace de respaldo perezoso para los que
     * se subieron antes de que existiera esa extracción, cacheando el resultado para la próxima vez.
     *
     * @return list<array{id:string,nombre:string,url:string,contenido_texto:string}>
     */
    private function archivosContextoGeneral(int $plantillaId): array
    {
        $db    = db_connect();
        $filas = $db->table('contextos_ia_archivos')->where('plantilla_id', $plantillaId)->get()->getResultArray();

        $resultado = [];
        foreach ($filas as $a) {
            $texto = trim((string) ($a['contenido_texto'] ?? ''));
            if ($texto === '' && trim((string) ($a['url'] ?? '')) !== '') {
                $texto = $this->extraerTextoPdfDeUrl($a['url']);
                if ($texto !== '') {
                    $db->table('contextos_ia_archivos')->where('id', (int) $a['id'])->update(['contenido_texto' => $texto]);
                }
            }
            $resultado[] = [
                'id'              => (string) $a['id'],
                'nombre'          => $a['nombre'],
                'url'             => $a['url'],
                'contenido_texto' => $texto,
            ];
        }

        return $resultado;
    }

    private function extraerTextoPdfDeUrl(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
        $binario = curl_exec($ch);
        $estado  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($binario === false || $estado < 200 || $estado >= 300) {
            return '';
        }

        try {
            return trim((new PdfParser())->parseContent((string) $binario)->getText());
        } catch (Throwable $e) {
            log_message('error', '[asistente-ia] No se pudo extraer texto del PDF de contexto general "{url}": {msg}', ['url' => $url, 'msg' => $e->getMessage()]);

            return '';
        }
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

    /** Mismo criterio que LlenadoIAController::fuenteDeLaVerdad() — el texto ya extraído de los
     * archivos que el cliente subió para ESTE ejemplo, más las notas de texto libre. */
    private function fuenteDeLaVerdad(int $ejemploId, string $textoAdicional): string
    {
        $archivos = db_connect()->table('fuente_verdad_archivos')->where('ejemplo_id', $ejemploId)->get()->getResultArray();
        $partes   = [];
        foreach ($archivos as $a) {
            $texto = trim((string) ($a['contenido_texto'] ?? ''));
            if ($texto !== '') {
                $partes[] = "--- {$a['nombre']} ---\n{$texto}";
            }
        }
        if (trim($textoAdicional) !== '') {
            $partes[] = "--- Notas adicionales del cliente ---\n" . trim($textoAdicional);
        }

        return implode("\n\n", $partes);
    }

    /**
     * Descarga el .md de Cloudinary; '' si no hay URL o la descarga falla (nunca revienta el prompt
     * por esto). Cacheado por URL — cada guardado desde ContextosIAController sube el markdown como
     * archivo NUEVO (`unique_filename: true` en CloudinaryUploader.php), así que una URL nunca cambia
     * de contenido: cachearla no puede servir una versión vieja. Encontrado en vivo (2026-09-07)
     * midiendo la latencia real de "ayúdame a llenar/verificar el campo X": construirSistema()
     * descargaba en serie 3-6 .md por CADA mensaje del chat (contexto de sección + globales +
     * generales), siempre los mismos durante toda la sesión — puro tiempo de red desperdiciado.
     */
    private function contenidoDeUrl(?string $url): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }

        $cache    = service('cache');
        $cacheKey = 'contexto_ia_md_' . md5($url);
        $cacheado = $cache->get($cacheKey);
        if (is_string($cacheado)) {
            return $cacheado;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $cuerpo = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $texto = ($cuerpo !== false && $estado >= 200 && $estado < 300) ? trim((string) $cuerpo) : '';
        if ($texto !== '') {
            $cache->save($cacheKey, $texto, 3600);
        }

        return $texto;
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
                // gpt-5 es un modelo de razonamiento: por defecto gasta tokens ocultos "pensando"
                // antes de responder, lo que se siente como lentitud en un chat conversacional corto.
                // "low" recorta ese razonamiento oculto sin degradar una respuesta de 2-4 líneas —
                // ver https://developers.openai.com/api/docs/guides/reasoning (reasoning_effort:
                // low|medium|high, soportado en /v1/chat/completions para la familia gpt-5).
                'reasoning_effort'      => 'low',
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
