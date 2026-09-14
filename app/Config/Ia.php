<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Acceso a las APIs de IA (Gemini, Anthropic/Claude, OpenAI/ChatGPT y Kimi/Moonshot). Desde el
// 2026-09-09 el proveedor ACTIVO para ambos flujos (AsistenteIAController — chat del cliente — y
// LlenadoIAController — llenado automático de ficha, incluido el lote de "Llenar toda la ficha") es
// Kimi (`kimiApiKey`/`kimiEndpoint`). Gemini, Anthropic y OpenAI quedan configurados pero sin uso
// activo — se conservan por si hay que volver a cambiar de proveedor (ver llamarModeloCrudo() /
// llamarClaudeCrudo() / llamarOpenAICrudo() en LlenadoIAController y llamarOpenAI()/llamarOpenAIJson()
// en AsistenteIAController, todas intactas). La API de Kimi es compatible con el formato de OpenAI
// Chat Completions (mismo `max_completion_tokens`, `response_format: json_object`, forma de
// `messages`) — por eso el llenado automático síncrono migra casi 1:1; la única pieza que NO existe
// en Kimi es la Batches API de OpenAI (subir archivo + job asíncrono + poll), así que el lote de
// "Llenar toda la ficha" (enviarLoteFicha) se reescribió para mandar todas las solicitudes en
// PARALELO (curl_multi) dentro del mismo request síncrono en vez de un job aparte — ver
// ejecutarLoteKimiEnParalelo(). El código de la Batches API de OpenAI (subirArchivoLoteOpenAI /
// esperarArchivoListoOpenAI / crearLoteOpenAI / consultarLoteOpenAI / descargarArchivoOpenAI) queda
// dormido, sin llamadas, por el mismo criterio de reversibilidad.
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

    /** Clave de la API de OpenAI — usada por el asesor de IA (chat) y, desde el 2026-08-19, también por el llenado automático. */
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

    /** Clave de la API de Kimi (Moonshot AI, platform.kimi.ai / api.moonshot.ai) — proveedor ACTIVO desde el 2026-09-09. */
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
}
