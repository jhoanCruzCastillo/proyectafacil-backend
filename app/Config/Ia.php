<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Acceso a las APIs de IA (Anthropic/Claude y OpenAI/ChatGPT).
//
// Las API keys se leen SIEMPRE del entorno del servidor — nunca se escriben aquí ni llegan al
// navegador. Ponerlas en el frontend no serviría de nada: Vite hornea las variables VITE_* dentro
// del bundle que se descarga el usuario, así que cualquiera podría extraerlas y gastar la cuenta.
//
// En el .env del backend:
//   ia.anthropicApiKey = "sk-ant-..."
//   ia.openaiApiKey = "sk-proj-..."
class Ia extends BaseConfig
{
    /** Clave de la API de Anthropic. Vacía = el asesor IA responde que no está configurado. */
    public string $anthropicApiKey = '';

    /** Modelo a usar. */
    public string $modelo = 'claude-sonnet-5';

    /** Tope de tokens de la respuesta — el asesor responde párrafos, no documentos. */
    public int $maxTokens = 800;

    public string $endpoint = 'https://api.anthropic.com/v1/messages';

    public string $anthropicVersion = '2023-06-01';

    /** Clave de la API de OpenAI. Vacía = todavía no configurada — sin uso funcional aún, solo disponible en el entorno. */
    public string $openaiApiKey = '';

    /** Modelo de OpenAI a usar el día que se conecte un flujo a esta clave. */
    public string $openaiModelo = 'gpt-5';

    public string $openaiEndpoint = 'https://api.openai.com/v1/chat/completions';
}
