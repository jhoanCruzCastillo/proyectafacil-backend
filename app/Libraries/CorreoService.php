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

    /**
     * Aviso de que "Llenar toda la ficha" (worker asíncrono, ver LlenadoIAController) terminó — el
     * cliente pudo cerrar la pestaña o la sesión apenas lo disparó, así que este correo es la única
     * forma en que se entera de que ya puede volver a revisar los resultados.
     *
     * Pedido explícito del usuario (2026-09-27): nada de costo en USD acá — es un detalle técnico
     * interno (ver `llenado_ia_trabajos.costo_usd`, que se queda solo en BD/logs), el cliente solo
     * necesita saber QUÉ secciones se llenaron. Tampoco un link genérico al listado de fichas: el
     * link va directo a ESTA ficha (`/mis-fichas/{ejemploId}`).
     *
     * @param  list<string> $seccionesLlenadas Nombres de las secciones con al menos un campo
     *                                          completado — ya filtradas por el llamador.
     * @throws \RuntimeException si el correo no se pudo enviar
     */
    public function enviarLlenadoIACompletado(string $correo, string $nombre, string $nombreFicha, array $seccionesLlenadas, string $urlFicha): void
    {
        $asunto = 'Tu ficha terminó de llenarse con IA — Proyecta Fácil';

        // `$seccionesLlenadas` vacío no debería pasar (ver IaEjecutarLlenado::avisar, que junta texto
        // + tablas), pero si el llamador algún día manda una lista vacía por un caso no contemplado,
        // esto cae a una frase genérica en vez de un cuadro verde vacío (bug real encontrado en vivo
        // 2026-09-28: una ficha llenada solo con tablas no aparecía en `secciones`, así que la lista
        // llegaba vacía y el correo mostraba el cuadro sin nada adentro).
        $intro = $seccionesLlenadas === []
            ? 'Se completaron varios campos.'
            : 'Se completaron campos en estas secciones:';

        $listaTexto = $seccionesLlenadas === []
            ? ''
            : "\n\n" . implode("\n", array_map(static fn (string $s): string => "  • {$s}", $seccionesLlenadas));
        $cuerpo = "Hola {$nombre},\n\n"
            . "El llenado automático con IA de tu ficha \"{$nombreFicha}\" ya terminó. {$intro}{$listaTexto}\n\n"
            . "Revísala aquí:\n{$urlFicha}\n";

        $listaHtml = $seccionesLlenadas === [] ? '' : (
            '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;margin:0 0 24px;">'
            . implode('', array_map(
                static fn (string $s): string => '<tr><td style="padding:4px 0;color:#0f172a;font-size:14px;line-height:1.5;">'
                    . '<span style="color:#16a34a;font-weight:700;margin-right:8px;">&#10003;</span>' . htmlspecialchars($s, ENT_QUOTES) . '</td></tr>',
                $seccionesLlenadas,
            ))
            . '</table>'
        );
        $html = $this->plantillaHtml(
            '¡Tu ficha ya está lista para revisar!',
            '<p style="margin:0 0 16px;color:#334155;font-size:15px;line-height:1.6;">'
                . "Hola {$nombre},</p>"
            . '<p style="margin:0 0 20px;color:#334155;font-size:15px;line-height:1.6;">'
                . 'El llenado automático con IA de tu ficha <strong>"' . htmlspecialchars($nombreFicha, ENT_QUOTES) . '"</strong> ya terminó. '
                . htmlspecialchars($intro, ENT_QUOTES) . '</p>'
            . $listaHtml
            . '<table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="border-radius:8px;background:#22c55e;">'
                . '<a href="' . htmlspecialchars($urlFicha, ENT_QUOTES) . '" style="display:inline-block;padding:12px 28px;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;border-radius:8px;">Revisar mi ficha</a>'
            . '</td></tr></table>',
        );

        $this->enviar($correo, $asunto, $cuerpo, $html);
    }

    /**
     * Cabecera (logo + wordmark + lema, igual al sidebar de la app) y pie comunes a cualquier correo
     * HTML — para que un futuro segundo correo con diseño (hoy todos los demás son texto plano) no
     * tenga que rearmarlos desde cero.
     */
    private function plantillaHtml(string $titulo, string $cuerpoHtml): string
    {
        // URL hosteada, NO embebido en base64: se probó embebido (ver historial) porque en local
        // Gmail no puede llegar a `http://localhost:8080/...`, pero el logo salió roto IGUAL en un
        // envío real — Brevo (el proveedor de correo, ver arriba) descarta/no soporta imágenes
        // `data:` embebidas en el HTML que reenvía. Una URL pública de verdad es el único camino que
        // funciona con este proveedor. Esto SOLO se puede ver bien una vez desplegado: `app.baseURL`
        // tiene que estar seteado en Railway al dominio público real del backend (por defecto es
        // 'http://localhost:8080/', ver Config\App.php) — si se deja sin setear, el logo sale roto
        // en producción también, no por Brevo sino porque la URL en sí no es alcanzable.
        $logoUrl = rtrim(config(\Config\App::class)->baseURL, '/') . '/assets/logo-email.png';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <body style="margin:0;padding:32px 16px;background:#f8fafc;font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center">
              <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px;width:100%;">
                <tr><td align="center" style="padding-bottom:24px;">
                  <img src="{$logoUrl}" width="48" height="48" alt="Proyecta Fácil" style="display:block;border-radius:12px;margin-bottom:10px;">
                  <div style="font-size:20px;font-weight:700;">
                    <span style="color:#0f172a;">Proyecta</span><span style="color:#22c55e;">Fácil</span>
                  </div>
                  <div style="font-size:12px;color:#64748b;margin-top:2px;">Proyectos de Inversión y Asesorías -by ILPIIE</div>
                </td></tr>
                <tr><td style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;">
                  <h1 style="margin:0 0 16px;color:#0f172a;font-size:19px;font-weight:700;">{$titulo}</h1>
                  {$cuerpoHtml}
                </td></tr>
                <tr><td align="center" style="padding-top:20px;color:#94a3b8;font-size:12px;">
                  © Proyecta Fácil — by ILPIIE
                </td></tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;
    }

    protected function enviar(string $correo, string $asunto, string $cuerpo, ?string $html = null): void
    {
        $json = [
            'sender'      => ['name' => $this->fromName, 'email' => $this->fromEmail],
            'to'          => [['email' => $correo]],
            'subject'     => $asunto,
            'textContent' => $cuerpo,
        ];
        if ($html !== null) {
            $json['htmlContent'] = $html;
        }

        $response = $this->http->post('https://api.brevo.com/v3/smtp/email', [
            'headers' => [
                'api-key'      => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'json'        => $json,
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
