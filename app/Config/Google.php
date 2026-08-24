<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Credenciales de la Service Account de Google usada para generar links de Meet reales al
// aceptar una solicitud de asesoría en videollamada (ver GoogleMeetService). La cuenta tiene
// domain-wide delegation autorizada en admin.google.com (arkha.tech) solo para el scope de
// Calendar — puede crear eventos "como si fuera" cualquier usuario del dominio, pero
// `meetImpersonateEmail` fija cuál usar (una sola cuenta "sistema", no una por asesor: los
// asesores de la app no son usuarios reales de Workspace).
//
// En el .env del backend:
//   google.meetServiceAccountKeyPath = writable/credentials/proyectafacil-meet-....json
//   google.meetImpersonateEmail = jcruz@arkha.tech
class Google extends BaseConfig
{
    /** Ruta al JSON de la Service Account, relativa a la raíz del proyecto backend. */
    public string $meetServiceAccountKeyPath = '';

    /** Cuenta de arkha.tech que "posee" los eventos de Calendar creados — ver nota arriba. */
    public string $meetImpersonateEmail = '';
}
