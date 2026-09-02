<?php

namespace App\Libraries;

use Config\Google as GoogleConfig;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolution;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\EntryPoint;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Docs;
use Google\Service\Docs\Paragraph;
use Google\Service\Drive;
use Google\Service\Drive\Permission;
use Google\Service\Meet;
use Google\Service\Meet\ArtifactConfig;
use Google\Service\Meet\EndActiveConferenceRequest;
use Google\Service\Meet\Recording;
use Google\Service\Meet\RecordingConfig;
use Google\Service\Meet\SmartNote;
use Google\Service\Meet\SmartNotesConfig;
use Google\Service\Meet\Space;
use Google\Service\Meet\SpaceConfig;
use RuntimeException;
use Throwable;

// Genera links de Google Meet reales para las solicitudes de asesoría en videollamada, con
// domain-wide delegation (Service Account "meet-generator" de arkha.tech, autorizada en
// admin.google.com para los scopes Calendar, Meet (lectura + escritura, ver
// MEETINGS_SPACE_CREATED abajo) y Drive (solo para compartir grabación/resumen, ver
// compartirGrabacion). El espacio de Meet se crea DIRECTO por la Meet API (no vía
// conferenceData.createRequest de Calendar) y luego se adjunta al evento — ver crearLinkReunion()
// para el porqué. Todo queda en el calendario/Drive de `google.meetImpersonateEmail` — una sola
// cuenta "sistema", no una por asesor: los asesores de la app no son usuarios reales de
// Workspace. El link de Meet funciona igual para cualquiera que lo reciba, sin importar de qué
// calendario salió; la grabación/resumen, en cambio, siempre quedan en el Drive de esa cuenta
// sistema (así funciona Meet, sin importar quién los inició) — por eso hace falta compartirlos a
// mano.
class GoogleMeetService
{
    private Client $client;
    private Calendar $calendar;
    private Meet $meet;
    private Drive $drive;
    private Docs $docs;

    public function __construct()
    {
        $config = config(GoogleConfig::class);

        if ($config->meetImpersonateEmail === '') {
            throw new RuntimeException('Google Meet no está configurado — falta google.meetImpersonateEmail en backend/.env');
        }

        $client = new Client();

        // Railway (y plataformas similares) no tienen el JSON de la Service Account en el
        // filesystem del contenedor — solo vive en git localmente, gitignoreado por ser un
        // secreto real. meetServiceAccountKeyBase64 evita depender de un archivo: se decodifica
        // acá mismo y se pasa el array ya parseado, que Client::setAuthConfig() acepta igual que
        // una ruta de archivo.
        if ($config->meetServiceAccountKeyBase64 !== '') {
            $json          = base64_decode($config->meetServiceAccountKeyBase64, true);
            $credenciales  = $json !== false ? json_decode($json, true) : null;
            if (! is_array($credenciales)) {
                throw new RuntimeException('google.meetServiceAccountKeyBase64 no es un JSON de Service Account válido (¿está bien codificado en base64?).');
            }
            $client->setAuthConfig($credenciales);
        } elseif ($config->meetServiceAccountKeyPath !== '') {
            $rutaClave = rtrim(ROOTPATH, '/\\') . '/' . ltrim($config->meetServiceAccountKeyPath, '/\\');
            if (! is_file($rutaClave)) {
                throw new RuntimeException("No se encontró la clave de la Service Account en {$rutaClave}");
            }
            $client->setAuthConfig($rutaClave);
        } else {
            throw new RuntimeException(
                'Google Meet no está configurado — completa google.meetServiceAccountKeyBase64 (o meetServiceAccountKeyPath) en backend/.env',
            );
        }

        $client->addScope(Calendar::CALENDAR);
        $client->addScope(Meet::MEETINGS_SPACE_CREATED);
        $client->addScope(Meet::MEETINGS_SPACE_READONLY);
        $client->addScope(Drive::DRIVE);
        $client->addScope(Docs::DOCUMENTS_READONLY);
        $client->setSubject($config->meetImpersonateEmail);

        $this->client   = $client;
        $this->calendar = new Calendar($client);
        $this->meet     = new Meet($client);
        $this->drive    = new Drive($client);
        $this->docs     = new Docs($client);
    }

    /**
     * Crea el espacio de Meet y el evento de Calendar, y devuelve solo el link de video.
     * `$horarioFecha` en formato "Y-m-d", `$horaInicio`/`$horaFin` en "H:i:s" — tal como se
     * guardan en solicitudes_asesoria.horario_fecha/horario_hora_inicio/horario_hora_fin.
     *
     * El espacio se crea DIRECTO por la Meet API (`spaces.create`) y recién después se adjunta al
     * evento de Calendar a mano (`conferenceData.conferenceId`/`entryPoints`, sin `createRequest`)
     * — a propósito, y no por gusto: un espacio que Calendar crea solo (vía `createRequest`) queda
     * de LECTURA únicamente para nosotros del lado de la Meet API — `spaces.get()` funciona, pero
     * cualquier `spaces.patch()` (moderación, smart notes) devuelve 403 "Permission denied",
     * confirmado en vivo. Creándolo nosotros primero, el espacio queda gestionable de verdad.
     *
     * `$attendeeEmails` son los correos del cliente y el asesor reales (no cuentas de Workspace) —
     * Meet los deja entrar sin "pedir unirse" siempre que abran el link logueados con ese mismo
     * correo en Google. Ninguno de los dos necesita ser usuario de arkha.tech: Calendar API acepta
     * cualquier email como invitado. `usuario_id`s sin correo registrado simplemente no se agregan
     * (ver llamadores) — no es un error, solo pierden el acceso directo para esa persona.
     *
     * `$coHostEmail` (el correo del asesor) recibe rol de co-anfitrión en el espacio de Meet, para
     * poder admitir gente desde la sala de espera y grabar sin ser usuario de arkha.tech — ver
     * asignarCoHost() (confirmado funcionando en vivo). Es un paso adicional al link/evento (que ya
     * funciona sin esto) y se trata como no crítico: si por algún motivo falla, la reunión igual se
     * crea y se devuelve el link, solo sin la promoción automática a co-host. Esto también es lo
     * que dispara la grabación automática de abajo: Meet solo graba sola cuando se une alguien con
     * privilegio de grabar, y ese privilegio lo da el rol de co-anfitrión.
     *
     * `$tipoAcceso` viene de configuracion_videoconferencia (ver
     * SolicitudAsesoriaHelpersTrait::tipoAccesoVideollamada(), configurable desde "Configuración de
     * videollamadas" en el Administrativo) — 'abierta' = cualquiera con el link entra directo, sin
     * tocar la puerta ni necesitar estar logueado con el correo exacto invitado; 'invitados' = solo
     * los invitados directos entran sin tocar la puerta, el resto queda en la sala de espera hasta
     * que el asesor lo admita.
     */
    public function crearLinkReunion(string $titulo, string $horarioFecha, string $horaInicio, string $horaFin, array $attendeeEmails = [], ?string $coHostEmail = null, string $tipoAcceso = 'abierta'): string
    {
        // El resumen de Gemini y la grabación se piden ya en la creación del espacio — no hace
        // falta un patch aparte después (que tampoco funcionaría si el espacio fuera de Calendar,
        // ver arriba). autoRecordingGeneration=ON graba en cuanto se une alguien con privilegio de
        // grabar (el co-anfitrión, ver asignarCoHost() más abajo) — antes de esto, había que
        // activarla a mano desde el panel de Meet en cada reunión.
        $accessType = $tipoAcceso === 'invitados' ? SpaceConfig::ACCESS_TYPE_TRUSTED : SpaceConfig::ACCESS_TYPE_OPEN;
        $space = $this->meet->spaces->create(new Space([
            'config' => new SpaceConfig([
                'accessType' => $accessType,
                'artifactConfig' => new ArtifactConfig([
                    'smartNotesConfig' => new SmartNotesConfig([
                        'autoSmartNotesGeneration' => SmartNotesConfig::AUTO_SMART_NOTES_GENERATION_ON,
                    ]),
                    'recordingConfig' => new RecordingConfig([
                        'autoRecordingGeneration' => RecordingConfig::AUTO_RECORDING_GENERATION_ON,
                    ]),
                ]),
            ]),
        ]));

        $event = new Event([
            'summary' => $titulo,
            'start'   => new EventDateTime([
                'dateTime' => "{$horarioFecha}T{$horaInicio}",
                'timeZone' => 'America/Lima',
            ]),
            'end' => new EventDateTime([
                'dateTime' => "{$horarioFecha}T{$horaFin}",
                'timeZone' => 'America/Lima',
            ]),
            'attendees' => array_map(static fn (string $correo) => new EventAttendee(['email' => $correo]), $attendeeEmails),
            'conferenceData' => new ConferenceData([
                'conferenceId'       => $space->getMeetingCode(),
                'conferenceSolution' => new ConferenceSolution([
                    'key' => new ConferenceSolutionKey(['type' => 'hangoutsMeet']),
                ]),
                'entryPoints' => [
                    new EntryPoint(['entryPointType' => 'video', 'uri' => $space->getMeetingUri()]),
                ],
            ]),
        ]);

        // sendUpdates=none: no se manda correo de invitación — el cliente/asesor ya ven el link
        // dentro de la app. Agregarlos como attendee alcanza para el acceso directo a Meet, sin
        // necesidad de que les llegue un email de por medio.
        $this->calendar->events->insert('primary', $event, ['conferenceDataVersion' => 1, 'sendUpdates' => 'none']);

        $linkMeet = $space->getMeetingUri();

        if ($coHostEmail !== null && $coHostEmail !== '') {
            try {
                $this->asignarCoHost($space->getName(), $coHostEmail);
            } catch (Throwable $e) {
                log_message('warning', 'GoogleMeetService: no se pudo asignar co-host ({correo}) al espacio de {link}: {msg}', [
                    'correo' => $coHostEmail,
                    'link'   => $linkMeet,
                    'msg'    => $e->getMessage(),
                ]);
            }
        }

        return $linkMeet;
    }

    /**
     * Activa moderación en el espacio de Meet y asigna a `$correo` como COHOST — puede admitir
     * gente desde la sala de espera y grabar, sin ser usuario de arkha.tech (el rol de miembro es
     * independiente del dominio de su correo). Solo funciona sobre espacios creados DIRECTO por la
     * Meet API, ver crearLinkReunion() — un espacio creado por Calendar (createRequest) queda de
     * solo lectura para nosotros y esta llamada fallaría con 403.
     *
     * La asignación de miembros (`spaces.members`) todavía vive en `v2beta` de la Meet API — no
     * está en la versión estable `v2` que trae el cliente PHP generado (`google/apiclient-
     * services`), así que se llama a mano con el mismo cliente HTTP ya autenticado por
     * `Client::authorize()` en vez de agregar una dependencia nueva solo para esto.
     */
    private function asignarCoHost(string $spaceName, string $correo): void
    {
        // El miembro se asigna ANTES de activar moderación, a propósito: si esta llamada falla, la
        // excepción corta acá y moderation nunca se enciende. Activar moderación sin lograr asignar
        // un co-host dejaría a todos esperando en la sala, sin nadie que los deje entrar — peor que
        // no tener moderación (esto pasó de verdad: ver la nota abajo sobre por qué el check de
        // status code es obligatorio acá).
        //
        // El cliente que devuelve Client::authorize() tiene `http_errors` en false (config que
        // arrastra de Client::getHttpClient() — ver Guzzle6AuthHandler::attachCredentials, que
        // clona esa config tal cual). Eso significa que un 4xx/5xx NO lanza excepción, solo
        // devuelve un Response con ese código — hay que revisarlo a mano. Sin este chequeo, una
        // respuesta 404 "Method not found" (spaces.members todavía no está disponible para este
        // proyecto) pasaba como "éxito" y moderation se encendía igual, sin que nadie tuviera
        // realmente el rol de co-host — confirmado en vivo: la persona quedaba trabada en la sala
        // de espera pese a que el código nunca reportó ningún error.
        $resp = $this->client->authorize()->post("https://meet.googleapis.com/v2beta/{$spaceName}/members", [
            'json'         => ['email' => $correo, 'role' => 'COHOST'],
            'http_errors'  => false,
        ]);
        if ($resp->getStatusCode() >= 300) {
            throw new RuntimeException("spaces.members respondió {$resp->getStatusCode()}: " . $resp->getBody());
        }

        $this->meet->spaces->patch(
            $spaceName,
            new Space(['config' => new SpaceConfig(['moderation' => SpaceConfig::MODERATION_ON])]),
            ['updateMask' => 'config.moderation'],
        );
    }

    /**
     * Historial real de entrada/salida de cada participante que estuvo en la videollamada,
     * agrupado por nombre — vía Meet API (conferenceRecords → participants →
     * participantSessions). `$meetingCode` es el código de la URL (ej. "igu-zady-sue" de
     * https://meet.google.com/igu-zady-sue). Devuelve `[]` si la llamada nunca ocurrió (nadie
     * entró todavía, o el link es de antes de tener Meet real) — no es un error, solo no hay nada
     * que mostrar aún.
     *
     * @return array<int, array{nombre: string, sesiones: array<int, array{entrada: ?string, salida: ?string}>}>
     */
    public function historialConexion(string $meetingCode): array
    {
        $registros = $this->meet->conferenceRecords->listConferenceRecords([
            'filter' => sprintf('space.meeting_code = "%s"', $meetingCode),
        ])->getConferenceRecords() ?? [];

        $sesionesPorNombre = [];
        foreach ($registros as $registro) {
            $participantes = $this->meet->conferenceRecords_participants
                ->listConferenceRecordsParticipants($registro->getName())
                ->getParticipants() ?? [];

            foreach ($participantes as $participante) {
                $nombre = $participante->getSignedinUser()?->getDisplayName()
                    ?? $participante->getAnonymousUser()?->getDisplayName()
                    ?? $participante->getPhoneUser()?->getDisplayName()
                    ?? 'Desconocido';

                $sesiones = $this->meet->conferenceRecords_participants_participantSessions
                    ->listConferenceRecordsParticipantsParticipantSessions($participante->getName())
                    ->getParticipantSessions() ?? [];

                $sesionesPorNombre[$nombre] ??= [];
                foreach ($sesiones as $sesion) {
                    $sesionesPorNombre[$nombre][] = ['entrada' => $sesion->getStartTime(), 'salida' => $sesion->getEndTime()];
                }
            }
        }

        $resultado = [];
        foreach ($sesionesPorNombre as $nombre => $sesiones) {
            usort($sesiones, static fn (array $a, array $b) => strcmp((string) $a['entrada'], (string) $b['entrada']));
            $resultado[] = ['nombre' => $nombre, 'sesiones' => $sesiones];
        }

        return $resultado;
    }

    /**
     * Busca la grabación de la videollamada y devuelve el link para reproducirla + el `fileId` de
     * Drive (necesario para compartirla, ver compartirGrabacion). Devuelve `null` si todavía no
     * hay grabación, o si Google aún no terminó de procesar el archivo (`state` distinto de
     * `FILE_GENERATED` — puede tardar minutos u horas después de que la llamada terminó) — no es
     * un error, hay que volver a intentarlo más tarde (ver el comando programado que llama a esto).
     *
     * @return array{url: string, fileId: string}|null
     */
    public function grabacionLista(string $meetingCode): ?array
    {
        $registros = $this->meet->conferenceRecords->listConferenceRecords([
            'filter' => sprintf('space.meeting_code = "%s"', $meetingCode),
        ])->getConferenceRecords() ?? [];

        foreach ($registros as $registro) {
            $grabaciones = $this->meet->conferenceRecords_recordings
                ->listConferenceRecordsRecordings($registro->getName())
                ->getRecordings() ?? [];

            foreach ($grabaciones as $grabacion) {
                $destino = $grabacion->getDriveDestination();
                if ($grabacion->getState() === Recording::STATE_FILE_GENERATED && $destino !== null) {
                    return ['url' => $destino->getExportUri(), 'fileId' => $destino->getFile()];
                }
            }
        }

        return null;
    }

    /**
     * Todas las grabaciones asociadas al link (no solo la primera lista) — un mismo link de Meet
     * puede terminar con más de una si, por ejemplo, la reunión se cortó y se reanudó, generando
     * más de un `conferenceRecord`, o si hubo más de una sesión de grabación dentro de la misma
     * llamada. A diferencia de grabacionLista(), esto incluye también las que todavía se están
     * procesando (`state` distinto de `FILE_GENERATED`) — el llamador decide qué mostrar mientras
     * tanto, en vez de que queden invisibles hasta que Google termine.
     *
     * @return array<int, array{url: ?string, fileId: ?string, estado: string, inicio: ?string, fin: ?string}>
     */
    public function grabacionesListadas(string $meetingCode): array
    {
        $registros = $this->meet->conferenceRecords->listConferenceRecords([
            'filter' => sprintf('space.meeting_code = "%s"', $meetingCode),
        ])->getConferenceRecords() ?? [];

        $resultado = [];
        foreach ($registros as $registro) {
            $grabaciones = $this->meet->conferenceRecords_recordings
                ->listConferenceRecordsRecordings($registro->getName())
                ->getRecordings() ?? [];

            foreach ($grabaciones as $grabacion) {
                $destino = $grabacion->getDriveDestination();
                $resultado[] = [
                    'url'     => $destino?->getExportUri(),
                    'fileId'  => $destino?->getFile(),
                    'estado'  => (string) $grabacion->getState(),
                    'inicio'  => $grabacion->getStartTime(),
                    'fin'     => $grabacion->getEndTime(),
                ];
            }
        }

        usort($resultado, static fn (array $a, array $b) => strcmp((string) $a['inicio'], (string) $b['inicio']));

        return $resultado;
    }

    /**
     * Busca el resumen/recap generado por Gemini ("Take notes for me") y devuelve el link del
     * Google Doc + su `fileId` (para compartirlo, ver compartirGrabacion — comparte cualquier
     * archivo de Drive, no solo grabaciones). Mismo criterio que grabacionLista(): `null` si
     * todavía no existe o no terminó de generarse.
     *
     * @return array{url: string, fileId: string}|null
     */
    public function resumenListo(string $meetingCode): ?array
    {
        $registros = $this->meet->conferenceRecords->listConferenceRecords([
            'filter' => sprintf('space.meeting_code = "%s"', $meetingCode),
        ])->getConferenceRecords() ?? [];

        foreach ($registros as $registro) {
            $resumenes = $this->meet->conferenceRecords_smartNotes
                ->listConferenceRecordsSmartNotes($registro->getName())
                ->getSmartNotes() ?? [];

            foreach ($resumenes as $resumen) {
                $destino = $resumen->getDocsDestination();
                if ($resumen->getState() === SmartNote::STATE_FILE_GENERATED && $destino !== null) {
                    return ['url' => $destino->getExportUri(), 'fileId' => $destino->getDocument()];
                }
            }
        }

        return null;
    }

    /**
     * Extrae solo el texto de la sección "Summary" del Google Doc que genera Gemini (ver
     * resumenListo) — el resto (Details/Decisions/Next Steps) se descarta a propósito, no lo
     * mostramos en el panel. Devuelve `null` si no se encuentra una sección con ese nombre.
     *
     * ADVERTENCIA: el parseo busca un encabezado cuyo texto contenga "summary" o "resumen" — no
     * verificado todavía contra un documento REAL generado por Gemini (ninguna asesoría con
     * grabación real completada existe aún). Revisar/ajustar apenas exista el primer caso real.
     */
    public function resumenTexto(string $docId): ?string
    {
        $documento = $this->docs->documents->get($docId);
        $elementos = $documento->getBody()?->getContent() ?? [];

        $capturando = false;
        $parrafos   = [];
        foreach ($elementos as $elemento) {
            $parrafo = $elemento->getParagraph();
            if ($parrafo === null) {
                continue;
            }

            $texto        = $this->textoDeParrafo($parrafo);
            $esEncabezado = str_starts_with((string) $parrafo->getParagraphStyle()?->getNamedStyleType(), 'HEADING');

            if ($esEncabezado) {
                if ($capturando) {
                    break;
                }
                $textoNormalizado = mb_strtolower(trim($texto));
                $capturando       = str_contains($textoNormalizado, 'summary') || str_contains($textoNormalizado, 'resumen');

                continue;
            }

            if ($capturando && trim($texto) !== '') {
                $parrafos[] = trim($texto);
            }
        }

        return $parrafos === [] ? null : implode("\n\n", $parrafos);
    }

    private function textoDeParrafo(Paragraph $parrafo): string
    {
        $texto = '';
        foreach ($parrafo->getElements() ?? [] as $elemento) {
            $texto .= $elemento->getTextRun()?->getContent() ?? '';
        }

        return $texto;
    }

    /**
     * Comparte un archivo de Drive de la cuenta sistema (grabación o resumen de Gemini, ambos
     * viven ahí igual) como lector con los correos reales del cliente y el asesor, para que
     * puedan abrirlo sin pedir acceso. `sendNotificationEmail: false` — mismo criterio que el
     * resto del servicio: no se manda correo, la app ya les muestra el link.
     */
    public function compartirGrabacion(string $fileId, array $correos): void
    {
        foreach ($correos as $correo) {
            $this->drive->permissions->create(
                $fileId,
                new Permission(['type' => 'user', 'role' => 'reader', 'emailAddress' => $correo]),
                ['sendNotificationEmail' => false],
            );
        }
    }

    /**
     * Comparte un archivo de Drive de la cuenta sistema como lector con "cualquiera que tenga el
     * link" — sin login de Google. Temporal, mientras no existe una sección de "Seguridad" en el
     * panel para configurar esto (ver `google.compartirGrabacionPublica`): un link filtrado por
     * accidente alcanza para que cualquiera lo vea, a cambio de no depender de que la persona esté
     * logueada con el correo exacto al que se compartió.
     */
    public function compartirPublico(string $fileId): void
    {
        $this->drive->permissions->create(
            $fileId,
            new Permission(['type' => 'anyone', 'role' => 'reader']),
            ['sendNotificationEmail' => false],
        );
    }

    /**
     * Red de seguridad para cuando el host se olvida de cortar la llamada (y con ella, la
     * grabación) — corta la conferencia activa del espacio si todavía sigue en curso. No existe un
     * "detener solo la grabación" en la Meet API (el recurso de grabaciones es de solo lectura);
     * cortar la conferencia completa es lo único disponible, y como efecto secundario también
     * finaliza cualquier grabación en curso. No hace nada (ni falla) si ya no hay conferencia
     * activa — ver el comando programado que llama a esto pasado el horario + margen.
     */
    public function terminarLlamadaSiActiva(string $linkMeet): void
    {
        $meetingCode = trim((string) parse_url($linkMeet, PHP_URL_PATH), '/');
        if ($meetingCode === '') {
            throw new RuntimeException("No se pudo extraer el meetingCode de {$linkMeet}");
        }

        $space = $this->meet->spaces->get("spaces/{$meetingCode}");
        if ($space->getActiveConference() === null) {
            return;
        }

        $this->meet->spaces->endActiveConference($space->getName(), new EndActiveConferenceRequest());
    }
}
