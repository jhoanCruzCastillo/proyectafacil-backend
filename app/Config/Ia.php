<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Acceso a la API de Claude (Anthropic).
//
// La API key se lee SIEMPRE del entorno del servidor — nunca se escribe aquí ni llega al navegador.
// Ponerla en el frontend no serviría de nada: Vite hornea las variables VITE_* dentro del bundle
// que se descarga el usuario, así que cualquiera podría extraerla y gastar la cuenta.
//
// En el .env del backend:
//   ia.anthropicApiKey = "sk-ant-..."
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
}
