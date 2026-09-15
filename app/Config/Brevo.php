<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Brevo (correo transaccional vía API HTTP) — mismo motivo que se evaluó con Resend: Railway
// bloquea el puerto SMTP saliente en producción, así que el envío va por HTTPS (443). Se optó por
// Brevo como proveedor final. Docs: https://developers.brevo.com/reference/sendtransacemail
//
// En el .env del backend:
//   brevo.apiKey = xkeysib-...
//   brevo.fromEmail = notificaciones@mail.arkha.cloud   (debe ser de un dominio autenticado en
//     Brevo — Configuración > Remitentes, dominio, IP > Dominios)
//   brevo.fromName = Proyecta Fácil
class Brevo extends BaseConfig
{
    public string $apiKey = '';
    public string $fromEmail = '';
    public string $fromName = 'Proyecta Fácil';
}
