<?php

namespace App\Libraries;

use Config\Google as GoogleConfig;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Meet;
use RuntimeException;

// Genera links de Google Meet reales para las solicitudes de asesoría en videollamada, vía la
// Calendar API con domain-wide delegation (Service Account "meet-generator" de arkha.tech,
// autorizada en admin.google.com para los scopes Calendar y Meet — este último solo lectura).
// El evento se crea en el calendario de `google.meetImpersonateEmail` — una sola cuenta
// "sistema", no una por asesor: los asesores de la app no son usuarios reales de Workspace. El
// link de Meet funciona igual para cualquiera que lo reciba, sin importar de qué calendario salió.
class GoogleMeetService
{
    private Calendar $calendar;
    private Meet $meet;

    public function __construct()
    {
        $config = config(GoogleConfig::class);

        if ($config->meetServiceAccountKeyPath === '' || $config->meetImpersonateEmail === '') {
            throw new RuntimeException(
                'Google Meet no está configurado — completa google.meetServiceAccountKeyPath/meetImpersonateEmail en backend/.env',
            );
        }

        $rutaClave = rtrim(ROOTPATH, '/\\') . '/' . ltrim($config->meetServiceAccountKeyPath, '/\\');
        if (! is_file($rutaClave)) {
            throw new RuntimeException("No se encontró la clave de la Service Account en {$rutaClave}");
        }

        $client = new Client();
        $client->setAuthConfig($rutaClave);
        $client->addScope(Calendar::CALENDAR);
        $client->addScope(Meet::MEETINGS_SPACE_READONLY);
        $client->setSubject($config->meetImpersonateEmail);

        $this->calendar = new Calendar($client);
        $this->meet     = new Meet($client);
    }

    /**
     * Crea el evento de Calendar con conferencia de Meet y devuelve solo el link de video.
     * `$horarioFecha` en formato "Y-m-d", `$horaInicio`/`$horaFin` en "H:i:s" — tal como se
     * guardan en solicitudes_asesoria.horario_fecha/horario_hora_inicio/horario_hora_fin.
     *
     * `$attendeeEmails` son los correos del cliente y el asesor reales (no cuentas de Workspace) —
     * Meet los deja entrar sin "pedir unirse" siempre que abran el link logueados con ese mismo
     * correo en Google. Ninguno de los dos necesita ser usuario de arkha.tech: Calendar API acepta
     * cualquier email como invitado. `usuario_id`s sin correo registrado simplemente no se agregan
     * (ver llamadores) — no es un error, solo pierden el acceso directo para esa persona.
     */
    public function crearLinkReunion(string $titulo, string $horarioFecha, string $horaInicio, string $horaFin, array $attendeeEmails = []): string
    {
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
                'createRequest' => new CreateConferenceRequest([
                    'requestId'             => bin2hex(random_bytes(8)),
                    'conferenceSolutionKey' => new ConferenceSolutionKey(['type' => 'hangoutsMeet']),
                ]),
            ]),
        ]);

        // sendUpdates=none: no se manda correo de invitación — el cliente/asesor ya ven el link
        // dentro de la app. Agregarlos como attendee alcanza para el acceso directo a Meet, sin
        // necesidad de que les llegue un email de por medio.
        $creado = $this->calendar->events->insert('primary', $event, ['conferenceDataVersion' => 1, 'sendUpdates' => 'none']);

        foreach ($creado->getConferenceData()?->getEntryPoints() ?? [] as $entryPoint) {
            if ($entryPoint->getEntryPointType() === 'video') {
                return $entryPoint->getUri();
            }
        }

        throw new RuntimeException('Google Calendar no devolvió un link de Meet en la respuesta.');
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
}
