<?php

namespace App\Libraries;

use Config\Email as EmailConfig;

// Primer y único punto de envío de correo del proyecto — antes no existía ninguno (ver
// UsuariosController, que hasta ahora devolvía la contraseña temporal en la respuesta HTTP para
// que el admin la copie a mano). Usa el servicio de Email que ya trae CodeIgniter, configurado vía
// backend/.env (local) o variables de entorno de Railway (producción) con el mismo patrón
// underscore que Stripe/Google: `email_protocol`, `email_SMTPHost`, `email_SMTPUser`,
// `email_SMTPPass`, `email_SMTPPort`, `email_fromEmail`, `email_fromName` — ver Config\Email, que
// no necesitó tocarse: BaseConfig ya mapea esas variables solo.
class CorreoService
{
    private \CodeIgniter\Email\Email $mailer;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $config          = config(EmailConfig::class);
        $this->mailer    = service('email');
        $this->fromEmail = $config->fromEmail;
        $this->fromName  = $config->fromName !== '' ? $config->fromName : 'Proyecta Fácil';
    }

    /** @throws \RuntimeException si el correo no se pudo enviar (SMTP sin configurar, credenciales inválidas, etc.) */
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
        $asunto = 'Tus accesos a Proyecta Fácil';
        $cuerpo = "Hola {$nombre},\n\n"
            . "Se creó (o se renovó el acceso a) tu cuenta en Proyecta Fácil. Estos son tus datos "
            . "de ingreso:\n\n"
            . "Usuario: {$usuario}\n"
            . "Contraseña: {$passwordTemporal}\n\n"
            . "Te recomendamos cambiar la contraseña apenas ingreses.\n";

        $this->enviar($correo, $asunto, $cuerpo);
    }

    private function enviar(string $correo, string $asunto, string $cuerpo): void
    {
        $this->mailer->clear(true);
        $this->mailer->setFrom($this->fromEmail, $this->fromName);
        $this->mailer->setTo($correo);
        $this->mailer->setSubject($asunto);
        $this->mailer->setMessage($cuerpo);

        if (! $this->mailer->send()) {
            log_message('error', '[correo] Falló el envío a {correo}: {debug}', [
                'correo' => $correo,
                'debug'  => $this->mailer->printDebugger(['headers']),
            ]);
            throw new \RuntimeException('No se pudo enviar el correo.');
        }
    }
}
