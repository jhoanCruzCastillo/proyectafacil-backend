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
// En el .env del backend (local, con el archivo de la Service Account en disco):
//   google.meetServiceAccountKeyPath = writable/credentials/proyectafacil-meet-....json
//   google.meetImpersonateEmail = jcruz@arkha.tech
//
// En Railway (u otra plataforma sin filesystem persistente para subir el JSON aparte del repo):
//   google_meetServiceAccountKeyBase64 = <contenido del JSON de la Service Account, en base64>
//   google_meetImpersonateEmail = jcruz@arkha.tech
// Si meetServiceAccountKeyBase64 está seteada, GoogleMeetService la usa en vez de buscar el
// archivo — no hace falta que el JSON exista en el filesystem del contenedor.
class Google extends BaseConfig
{
    /** Ruta al JSON de la Service Account, relativa a la raíz del proyecto backend. */
    public string $meetServiceAccountKeyPath = '';

    /** JSON completo de la Service Account en base64 — alternativa a meetServiceAccountKeyPath
     * para plataformas donde no se puede subir el archivo de credenciales aparte del repo. */
    public string $meetServiceAccountKeyBase64 = '';

    /** Cuenta de arkha.tech que "posee" los eventos de Calendar creados — ver nota arriba. */
    public string $meetImpersonateEmail = '';
}
