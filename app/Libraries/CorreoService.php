<?php

namespace App\Libraries;

use Config\Brevo as BrevoConfig;
use Config\Stripe as StripeConfig;
use GuzzleHttp\Client;

// Primer y único punto de envío de correo del proyecto — antes no existía ninguno (ver
// UsuariosController, que hasta ahora devolvía la contraseña temporal en la respuesta HTTP para
// que el admin la copie a mano). Envía vía la API HTTP de Brevo en vez de SMTP: en producción
// (Railway) el puerto saliente 587 hacia smtp.gmail.com daba timeout de conexión — la plataforma
// bloquea SMTP saliente, no era un problema de credenciales — así que se abandonó CodeIgniter
// Email/SMTP por completo a favor de una API sobre HTTPS (443), que nunca se bloquea. Se probó
// también el servicio propio del cliente ("avisomail") pero el servidor (174.136.38.42) da
// connection timeout tanto por HTTP como por HTTPS — probablemente un firewall con lista blanca de
// IPs — así que se volvió a Brevo mientras se resuelve el acceso a ese servidor.
class CorreoService
{
    private Client $http;
    private string $apiKey;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $config          = config(BrevoConfig::class);
        $this->http      = new Client();
        $this->apiKey    = $config->apiKey;
        $this->fromEmail = $config->fromEmail;
        $this->fromName  = $config->fromName !== '' ? $config->fromName : 'Proyecta Fácil';
    }

    /** @throws \RuntimeException si el correo no se pudo enviar (API key sin configurar, dominio no autenticado, etc.) */
    public function enviarVerificacion(string $correo, string $nombre, string $urlVerificacion): void
    {
        $asunto = 'Confirma tu correo — Proyecta Fácil';
        $cuerpo = "Hola {$nombre},\n\n"
            . "Gracias por registrarte en Proyecta Fácil. Confirma tu correo entrando a este enlace "
            . "(válido por 24 horas):\n\n{$urlVerificacion}\n\n"
            . "Si no fuiste vos quien se registró, podés ignorar este correo.\n";

        $this->enviar($correo, $asunto, $cuerpo);
    }

    /** @throws \RuntimeException si el correo no se pudo enviar */
    public function enviarAccesos(string $correo, string $nombre, string $usuario, string $passwordTemporal): void
    {
        $urlLogin = rtrim(config(StripeConfig::class)->frontendBaseUrl, '/') . '/login';
        $asunto   = 'Tus accesos a Proyecta Fácil';
        $cuerpo   = "Hola {$nombre},\n\n"
            . "Se creó (o se renovó el acceso a) tu cuenta en Proyecta Fácil. Estos son tus datos "
            . "de ingreso:\n\n"
            . "Usuario: {$usuario}\n"
            . "Contraseña: {$passwordTemporal}\n\n"
            . "Inicia sesión aquí:\n{$urlLogin}\n\n"
            . "Te recomendamos cambiar la contraseña apenas ingreses.\n";

        $this->enviar($correo, $asunto, $cuerpo);
    }

    /** @throws \RuntimeException si el correo no se pudo enviar */
    public function enviarRecordatorioVideollamada(string $correo, string $nombre, string $horaInicio, string $linkReunion): void
    {
        $asunto = 'Tu videollamada empieza en 5 minutos — Proyecta Fácil';
        $cuerpo = "Hola {$nombre},\n\n"
            . "Tu asesoría por videollamada está por empezar, a las {$horaInicio}.\n\n"
            . "Únete desde este enlace:\n{$linkReunion}\n";

        $this->enviar($correo, $asunto, $cuerpo);
    }

    private function enviar(string $correo, string $asunto, string $cuerpo): void
    {
        $response = $this->http->post('https://api.brevo.com/v3/smtp/email', [
            'headers' => [
                'api-key'      => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'json' => [
                'sender'      => ['name' => $this->fromName, 'email' => $this->fromEmail],
                'to'          => [['email' => $correo]],
                'subject'     => $asunto,
                'textContent' => $cuerpo,
            ],
            'http_errors' => false,
        ]);

        if ($response->getStatusCode() >= 300) {
            log_message('error', '[correo] Falló el envío a {correo}: HTTP {status} — {body}', [
                'correo' => $correo,
                'status' => $response->getStatusCode(),
                'body'   => (string) $response->getBody(),
            ]);
            throw new \RuntimeException('No se pudo enviar el correo.');
        }
    }
}
