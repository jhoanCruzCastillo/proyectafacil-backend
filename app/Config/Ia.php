<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Acceso a las APIs de IA (Gemini, Anthropic/Claude, OpenAI/ChatGPT y Kimi/Moonshot). Desde el
// 2026-09-23 QUÉ proveedor se usa ya no se decide editando código: lo decide `ia.proveedor` en el
// .env ('kimi' por defecto, 'openai' para volver a ChatGPT) — ver el flag $proveedor y los helpers
// apiKeyActiva()/endpointActivo()/modeloActivo()/modeloLlenadoActivo() al final de esta clase.
// Aplica a los dos flujos: AsistenteIAController (chat del cliente) y LlenadoIAController (llenado
// automático de ficha, incluido el lote de "Llenar toda la ficha").
//
// Historial: se usó Kimi desde el 2026-09-09, se volvió a OpenAI el 2026-09-17 ("ahora usaremos
// OpenAI") y se volvió a Kimi el 2026-09-23. Ese ir y venir es la razón del flag: los dos caminos
// de código quedan intactos y el cambio cuesta una línea. Gemini y Anthropic siguen configurados
// pero sin uso activo — se conservan igual (ver
// llamarModeloCrudo() / llamarClaudeCrudo() / llamarKimiCrudo() en LlenadoIAController y
// llamarKimi()/llamarKimiJson() en AsistenteIAController, todas intactas).
//
// La API de Kimi es compatible con el formato de OpenAI Chat Completions (mismo
// `max_completion_tokens`, `response_format: json_object`, forma de `messages`), así que el swap de
// vuelta a OpenAI fue igual de mecánico que el cambio a Kimi en su momento. IMPORTANTE: el lote de
// "Llenar toda la ficha" (enviarLoteFicha) SIGUE mandando todas las solicitudes en PARALELO
// (curl_multi, ver ejecutarLoteEnParalelo(), que ahora usa el endpoint/key del proveedor activo)
// en vez de volver a la Batches API real de OpenAI (subir archivo + job asíncrono + poll) — se
// decidió mantener el mecanismo síncrono para no reintroducir el polling del lado del cliente que
// esa API exige, aunque eso signifique no aprovechar el descuento de precio de Batches. El código de
// la Batches API (subirArchivoLoteOpenAI / esperarArchivoListoOpenAI / crearLoteOpenAI /
// consultarLoteOpenAI / descargarArchivoOpenAI) sigue dormido, sin llamadas, por si en algún momento
// se decide migrar el lote a ese flujo async.
//
// Las API keys se leen SIEMPRE del entorno del servidor — nunca se escriben aquí ni llegan al
// navegador. Ponerlas en el frontend no serviría de nada: Vite hornea las variables VITE_* dentro
// del bundle que se descarga el usuario, así que cualquiera podría extraerlas y gastar la cuenta.
//
// En el .env del backend:
//   ia.geminiApiKey = "AIza..."
//   ia.anthropicApiKey = "sk-ant-..."
//   ia.openaiApiKey = "sk-proj-..."
//   ia.kimiApiKey = "sk-..."
//   ia.proveedor = kimi            # o: openai
class Ia extends BaseConfig
{
    /** Clave de la API de Gemini (Google AI Studio). Vacía = el llenado con IA responde que no está configurado. */
    public string $geminiApiKey = '';

    /** Modelo a usar. */
    public string $geminiModelo = 'gemini-2.5-flash';

    /** La key va como query param `?key=`, no como header — se arma en el controlador. */
    public string $geminiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models';

    /** Clave de la API de Anthropic. Vacía = sin uso activo (ver nota arriba). */
    public string $anthropicApiKey = '';

    /** Modelo del asesor de IA (chat conversacional) — respuestas más matizadas, vale la pena el costo. */
    public string $modelo = 'claude-sonnet-5';

    /**
     * Modelo del llenado automático de fichas (LlenadoIAController) — extracción determinista contra
     * un schema fijo, no conversación matizada, así que un modelo más barato rinde igual de bien por
     * mucho menos costo. Con cache_control activo (ver llamarClaudeCrudo), el costo por llamada baja
     * de ~$0.17 (Sonnet 5 sin caché) a ~$0.022 (Sonnet 5 con caché) a, proyectado, ~$0.01-0.012 con
     * Haiku 4.5 + caché (ver PRECIOS_CLAUDE_POR_MTOK en el controlador).
     */
    public string $modeloLlenado = 'claude-haiku-4-5';

    /** Tope de tokens de la respuesta — el asesor responde párrafos, no documentos. */
    public int $maxTokens = 800;

    public string $endpoint = 'https://api.anthropic.com/v1/messages';

    public string $anthropicVersion = '2023-06-01';

    /** Clave de la API de OpenAI/ChatGPT. Se usa solo si `ia.proveedor = openai` (ver $proveedor). */
    public string $openaiApiKey = '';

    /** Modelo del asesor de IA (chat) y de las tablas con catálogo en cascada del llenado automático (ver openaiModeloLlenado abajo) — respuestas más matizadas/instrucciones más densas, vale la pena el costo mayor. */
    public string $openaiModelo = 'gpt-5';

    /**
     * Modelo del llenado automático de fichas (LlenadoIAController) — extracción determinista contra
     * un schema fijo, no conversación matizada, así que un modelo más barato rinde igual de bien por
     * mucho menos costo. Mismo criterio que `modeloLlenado` (Claude, dormido) — ver
     * PRECIOS_OPENAI_POR_MTOK en el controlador para el detalle de precio.
     */
    public string $openaiModeloLlenado = 'gpt-5-mini';

    public string $openaiEndpoint = 'https://api.openai.com/v1/chat/completions';

    /** Clave de la API de Kimi (Moonshot AI, platform.kimi.ai / api.moonshot.ai). Proveedor por defecto (ver $proveedor). */
    public string $kimiApiKey = '';

    /** Modelo del asesor de IA (chat) y de las tablas con catálogo en cascada del llenado automático (ver kimiModeloLlenado abajo) — modelo insignia, respuestas más matizadas, vale la pena el costo mayor. */
    public string $kimiModelo = 'kimi-k3';

    /**
     * Modelo del llenado automático de fichas (LlenadoIAController) — extracción determinista contra
     * un schema fijo, no conversación matizada, así que un modelo más barato rinde igual de bien por
     * mucho menos costo. Mismo criterio que `openaiModeloLlenado` (dormido) — ver
     * PRECIOS_KIMI_POR_MTOK en el controlador para el detalle de precio.
     */
    public string $kimiModeloLlenado = 'kimi-k2.6';

    /** API de Kimi: compatible con el formato de OpenAI Chat Completions (mismos campos de request/response). */
    public string $kimiEndpoint = 'https://api.moonshot.ai/v1/chat/completions';

    /**
     * Proveedor ACTIVO para el chat del asesor y el llenado automático: 'kimi' u 'openai'.
     *
     * Hasta ahora cada cambio de proveedor se hacía a mano, editando los puntos de llamada y
     * renombrando funciones (ejecutarLoteKimiEnParalelo -> ejecutarLoteOpenAIEnParalelo, etc.).
     * Con este flag el cambio es UNA línea del .env y los dos caminos de código quedan intactos,
     * que es justo lo que hacía falta para poder ir y volver sin romper nada.
     *
     * En el .env del backend:  ia.proveedor = kimi   (o: openai)
     *
     * Solo cubre el par Kimi/OpenAI, que comparten el formato Chat Completions. Gemini y Anthropic
     * siguen teniendo sus propias funciones (llamarModeloCrudo / llamarClaudeCrudo), sin uso activo.
     */
    public string $proveedor = 'kimi';

    /** True si el proveedor activo es Kimi. Cualquier valor distinto de 'openai' se trata como Kimi. */
    public function usaKimi(): bool
    {
        return strtolower(trim($this->proveedor)) !== 'openai';
    }

    /** Clave del proveedor activo — la que hay que mirar para decidir si la IA está configurada. */
    public function apiKeyActiva(): string
    {
        return $this->usaKimi() ? $this->kimiApiKey : $this->openaiApiKey;
    }

    /** Endpoint Chat Completions del proveedor activo. */
    public function endpointActivo(): string
    {
        return $this->usaKimi() ? $this->kimiEndpoint : $this->openaiEndpoint;
    }

    /** Modelo insignia del proveedor activo (chat del asesor y tablas con catálogo en cascada). */
    public function modeloActivo(): string
    {
        return $this->usaKimi() ? $this->kimiModelo : $this->openaiModelo;
    }

    /** Modelo barato del proveedor activo (llenado automático). */
    public function modeloLlenadoActivo(): string
    {
        return $this->usaKimi() ? $this->kimiModeloLlenado : $this->openaiModeloLlenado;
    }
}
