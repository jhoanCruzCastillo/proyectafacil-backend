<?php

namespace App\Controllers;

use App\Controllers\Support\SolicitudAsesoriaHelpersTrait;
use App\Libraries\GoogleMeetService;
use App\Libraries\HorarioRecurrencia;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

// Lado Administrativo de Asesorías (Módulo 4, docs/proyectafacil-asesorias.md §5 "administrador"):
// dashboard con KPIs, tabla de todos los tickets, detalle con línea de tiempo + docentes
// notificados, e intervención manual cuando el matchmaking automático (AsesoriaController) no
// resuelve un ticket a tiempo. Comparte helpers con AsesoriaController vía
// SolicitudAsesoriaHelpersTrait.
class TicketsAsesoriaController extends BaseController
{
    use SolicitudAsesoriaHelpersTrait;

    public function dashboard(): ResponseInterface
    {
        $db  = db_connect();
        $hoy = date('Y-m-d') . ' 00:00:00';

        $pendientes      = $db->table('solicitudes_asesoria')->where('estado', 'pendiente')->countAllResults();
        $completadosHoy  = $db->table('solicitudes_asesoria')->where('estado', 'completado')->where('updated_at >=', $hoy)->countAllResults();
        $completadosTotal = $db->table('solicitudes_asesoria')->where('estado', 'completado')->countAllResults();

        return $this->response->setJSON([
            'pendientes'       => $pendientes,
            'completadosHoy'   => $completadosHoy,
            'completadosTotal' => $completadosTotal,
        ]);
    }

    public function index(): ResponseInterface
    {
        $filas = db_connect()->table('solicitudes_asesoria sa')
            ->select('sa.*, c.nombre as cliente_nombre, c.foto_url as cliente_foto_url, d.nombre as docente_nombre, d.foto_url as docente_foto_url, s.nombre as sector_nombre')
            ->join('usuarios c', 'c.id = sa.cliente_id')
            ->join('usuarios d', 'd.id = sa.docente_id', 'left')
            ->join('sectores s', 's.id = sa.sector_id', 'left')
            ->orderBy('sa.created_at', 'DESC')
            ->get()->getResultArray();
        $filas = array_map([$this, 'resolverAsistenciaSiCorresponde'], $filas);

        return $this->response->setJSON(array_map([$this, 'toDtoSolicitud'], $filas));
    }

    public function detalle($id = null): ResponseInterface
    {
        $id   = (int) $id;
        $fila = db_connect()->table('solicitudes_asesoria sa')
            ->select('sa.*, c.nombre as cliente_nombre, c.correo as cliente_correo, c.foto_url as cliente_foto_url, d.nombre as docente_nombre, d.foto_url as docente_foto_url, s.nombre as sector_nombre')
            ->join('usuarios c', 'c.id = sa.cliente_id')
            ->join('usuarios d', 'd.id = sa.docente_id', 'left')
            ->join('sectores s', 's.id = sa.sector_id', 'left')
            ->where('sa.id', $id)
            ->get()->getRowArray();

        if (! $fila) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ticket no encontrado']);
        }

        $fila = $this->resolverAsistenciaSiCorresponde($fila);

        $notificados = db_connect()->table('solicitud_notificaciones sn')
            ->select('sn.asesor_id, u.nombre, u.foto_url, sn.created_at')
            ->join('usuarios u', 'u.id = sn.asesor_id')
            ->where('sn.solicitud_id', $id)
            ->orderBy('u.nombre', 'ASC')
            ->get()->getResultArray();

        $dto                        = $this->toDtoSolicitud($fila);
        $dto['clienteCorreo']       = $fila['cliente_correo'] ?? null;
        $dto['docentesNotificados'] = array_map(static fn (array $n) => [
            'id'           => (string) $n['asesor_id'],
            'nombre'       => $n['nombre'],
            'fotoUrl'      => $n['foto_url'] ?? null,
            'notificadoEn' => str_replace(' ', 'T', (string) $n['created_at']) . 'Z',
        ], $notificados);

        return $this->response->setJSON($dto);
    }

    // Historial real de conexión a la videollamada (Meet API) — quién entró y cuándo, para el
    // ticket ya completado. Sin tabla propia: se consulta a Google en el momento, usando el
    // código de reunión que ya vive en `link_reunion`. Devuelve una lista vacía (nunca un error
    // visible al admin) si el ticket no es de video, si el link no es de Meet, o si Google
    // todavía no tiene ningún registro — cualquiera de esos casos simplemente significa "nada que
    // mostrar aún".
    public function historialConexion($id = null): ResponseInterface
    {
        $id     = (int) $id;
        $vacio  = ['participantes' => [], 'tiempoCoincidenteSegundos' => 0];
        $fila   = db_connect()->table('solicitudes_asesoria sa')
            ->select('sa.tipo, sa.link_reunion, c.nombre as cliente_nombre, c.correo as cliente_correo, c.foto_url as cliente_foto_url, d.nombre as docente_nombre, d.correo as docente_correo, d.foto_url as docente_foto_url')
            ->join('usuarios c', 'c.id = sa.cliente_id')
            ->join('usuarios d', 'd.id = sa.docente_id', 'left')
            ->where('sa.id', $id)
            ->get()->getRowArray();

        if (! $fila || $fila['tipo'] !== 'video' || empty($fila['link_reunion'])) {
            return $this->response->setJSON($vacio);
        }

        if (! preg_match('#meet\.google\.com/([a-z]+-[a-z]+-[a-z]+)#', (string) $fila['link_reunion'], $m)) {
            return $this->response->setJSON($vacio);
        }

        try {
            $porNombre = (new GoogleMeetService())->historialConexion($m[1]);
        } catch (Throwable $e) {
            log_message('error', 'GoogleMeetService::historialConexion falló para la solicitud {id}: {msg}', ['id' => $id, 'msg' => $e->getMessage()]);

            return $this->response->setJSON($vacio);
        }

        // El nombre que da Meet es el de la cuenta de Google real que entró (puede ser cualquier
        // cosa: apodo, cuenta personal distinta al nombre registrado) — nunca se muestra tal cual.
        // Se empareja por nombre contra el cliente/asesor de este ticket cuando calza; si no calza
        // con uno de los dos (o ninguno), se cae al mismo criterio que evaluarAsistenciaReal: la
        // pareja de participantes reales con mayor superposición entre sí. En cualquier caso, lo
        // que se muestra siempre es la identidad conocida en la plataforma (nombre + correo
        // configurado), no el nombre que reportó Google.
        $normalizar      = static fn (string $s) => mb_strtolower(trim($s));
        $sesionesCliente = [];
        $sesionesAsesor  = [];
        foreach ($porNombre as $p) {
            if ($normalizar($p['nombre']) === $normalizar($fila['cliente_nombre'])) {
                $sesionesCliente = array_merge($sesionesCliente, $p['sesiones']);
            } elseif ($fila['docente_nombre'] && $normalizar($p['nombre']) === $normalizar($fila['docente_nombre'])) {
                $sesionesAsesor = array_merge($sesionesAsesor, $p['sesiones']);
            }
        }
        if ($sesionesCliente === [] || $sesionesAsesor === []) {
            [$sesionesCliente, $sesionesAsesor] = $this->dosParticipantesConMasSuperposicion($porNombre);
        }

        $participantes = [];
        if ($sesionesCliente !== []) {
            $participantes[] = [
                'nombre'   => $fila['cliente_nombre'],
                'correo'   => $fila['cliente_correo'] ?? null,
                'rol'      => 'Alumno',
                'fotoUrl'  => $fila['cliente_foto_url'] ?? null,
                'sesiones' => $sesionesCliente,
            ];
        }
        if ($sesionesAsesor !== [] && $fila['docente_nombre']) {
            $participantes[] = [
                'nombre'   => $fila['docente_nombre'],
                'correo'   => $fila['docente_correo'] ?? null,
                'rol'      => 'Docente',
                'fotoUrl'  => $fila['docente_foto_url'] ?? null,
                'sesiones' => $sesionesAsesor,
            ];
        }

        return $this->response->setJSON([
            'participantes'             => $participantes,
            'tiempoCoincidenteSegundos' => $this->calcularSuperposicion($sesionesCliente, $sesionesAsesor)['segundos'],
        ]);
    }

    // Elegibles AHORA (no al momento del broadcast — la disponibilidad pudo cambiar) para la
    // lista "Docentes disponibles ahora" del modal de Intervención manual.
    public function docentesDisponibles($id = null): ResponseInterface
    {
        $id  = (int) $id;
        $sol = $this->fila($id);
        if (! $sol) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ticket no encontrado']);
        }

        $ids = $sol['tipo'] === 'video'
            ? $this->asesoresPorHorario($sol['horario_fecha'], $sol['horario_hora_inicio'], $sol['horario_hora_fin'])
            : $this->asesoresPorSector($sol['sector_id'] !== null ? (int) $sol['sector_id'] : null);

        if ($ids === []) {
            return $this->response->setJSON([]);
        }

        $db    = db_connect();
        $filas = $db->table('usuarios')->select('id, nombre, foto_url')->whereIn('id', $ids)->orderBy('nombre', 'ASC')->get()->getResultArray();

        // Una especialidad "representativa" por docente (la primera alfabéticamente) para la
        // tarjeta del modal de intervención manual — el docente puede tener varias.
        $especialidades = $db->table('asesor_especialidades ae')
            ->select('ae.usuario_id, s.nombre as sector_nombre')
            ->join('sectores s', 's.id = ae.sector_id')
            ->whereIn('ae.usuario_id', $ids)
            ->orderBy('s.nombre', 'ASC')
            ->get()->getResultArray();
        $especialidadPorDocente = [];
        foreach ($especialidades as $e) {
            $uid = (int) $e['usuario_id'];
            $especialidadPorDocente[$uid] ??= $e['sector_nombre'];
        }

        // Calificación promedio y total de consultas completadas de siempre (no solo del mes) —
        // credencial de confianza para que el Administrativo elija a quién asignar manualmente.
        $stats = $db->table('solicitudes_asesoria')
            ->select('docente_id, COUNT(*) as total, AVG(calificacion) as promedio')
            ->where('estado', 'completado')
            ->whereIn('docente_id', $ids)
            ->groupBy('docente_id')
            ->get()->getResultArray();
        $statsPorDocente = [];
        foreach ($stats as $s) {
            $statsPorDocente[(int) $s['docente_id']] = [
                'total'    => (int) $s['total'],
                'promedio' => $s['promedio'] !== null ? round((float) $s['promedio'], 1) : null,
            ];
        }

        return $this->response->setJSON(array_map(static function (array $f) use ($especialidadPorDocente, $statsPorDocente) {
            $uid   = (int) $f['id'];
            $stat  = $statsPorDocente[$uid] ?? ['total' => 0, 'promedio' => null];

            return [
                'id'                   => (string) $f['id'],
                'nombre'               => $f['nombre'],
                'fotoUrl'              => $f['foto_url'] ?? null,
                'especialidad'         => $especialidadPorDocente[$uid] ?? null,
                'calificacionPromedio' => $stat['promedio'],
                'consultasAtendidas'   => $stat['total'],
            ];
        }, $filas));
    }

    // Asignación manual del Administrativo — a diferencia de AsesoriaController::aceptar()
    // (asesor, atómico condicionado a 'pendiente'), esta también permite reasignar un ticket que
    // ya cayó en 'en_espera'.
    public function asignar($id = null): ResponseInterface
    {
        $id       = (int) $id;
        $dto      = $this->request->getJSON(true) ?? [];
        $asesorId = (int) ($dto['asesorId'] ?? 0);
        $db       = db_connect();

        $solicitud = $this->fila($id);
        if (! $solicitud || ! in_array($solicitud['estado'], ['pendiente', 'en_espera'], true)) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Este ticket ya no se puede asignar']);
        }

        $esVideo     = $solicitud['tipo'] === 'video';
        $nuevoEstado = $esVideo ? 'agendado' : 'asignado';

        $linkReunion = null;
        if ($esVideo) {
            try {
                $linkReunion = (new GoogleMeetService())->crearLinkReunion(
                    "Asesoría Proyecta Fácil #{$id}",
                    (string) $solicitud['horario_fecha'],
                    (string) $solicitud['horario_hora_inicio'],
                    (string) $solicitud['horario_hora_fin'],
                    $this->correosParaInvitar((int) $solicitud['cliente_id'], $asesorId),
                );
            } catch (Throwable $e) {
                log_message('error', 'GoogleMeetService: no se pudo generar el link de Meet para la solicitud {id}: {msg}', ['id' => $id, 'msg' => $e->getMessage()]);

                return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo generar el link de la videollamada. Intenta de nuevo en unos minutos.']);
            }
        }

        $db->table('solicitudes_asesoria')->where('id', $id)->update([
            'docente_id'   => $asesorId,
            'estado'       => $nuevoEstado,
            'link_reunion' => $linkReunion,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->notificar($asesorId, 'nueva_solicitud_asesoria', 'Un administrativo te asignó una solicitud de asesoría', $id);
        $this->notificar((int) $solicitud['cliente_id'], 'solicitud_aceptada', 'Tu solicitud de asesoría fue aceptada', $id);

        return $this->response->setJSON($this->toDtoSolicitud($this->fila($id)));
    }

    // Sin docentes disponibles para reasignar (docs §4 Fase 2) — no libera ni consume el ticket de
    // consulta, espera indefinidamente hasta que haya cobertura.
    public function marcarEnEspera($id = null): ResponseInterface
    {
        $id = (int) $id;
        db_connect()->table('solicitudes_asesoria')->where('id', $id)->update([
            'estado'     => 'en_espera',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON($this->toDtoSolicitud($this->fila($id)));
    }

    // Solo aplica a video: limpia el horario elegido y vuelve a 'pendiente' con un SLA nuevo, para
    // que el alumno elija otro horario desde su pantalla.
    public function reabrirHorario($id = null): ResponseInterface
    {
        $id        = (int) $id;
        $solicitud = $this->fila($id);
        if (! $solicitud || $solicitud['tipo'] !== 'video') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Solo aplica a solicitudes de videollamada']);
        }

        db_connect()->table('solicitudes_asesoria')->where('id', $id)->update([
            'estado'              => 'pendiente',
            'horario_fecha'       => null,
            'horario_hora_inicio' => null,
            'horario_hora_fin'    => null,
            'sla_vence_en'        => $this->calcularSlaVenceEn('video'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
        $this->notificar((int) $solicitud['cliente_id'], 'reabrir_horario', 'Elige un nuevo horario para tu videollamada', $id);

        return $this->response->setJSON($this->toDtoSolicitud($this->fila($id)));
    }

    // Cancelación por el Administrativo (docs §Fase 4) — el frontend pide el texto de seguridad
    // "CANCELAR-[ID]" antes de llamar a este endpoint; aquí solo se ejecuta el efecto.
    public function cancelar($id = null): ResponseInterface
    {
        $id        = (int) $id;
        $solicitud = $this->fila($id);

        db_connect()->table('solicitudes_asesoria')->where('id', $id)->update([
            'estado'     => 'cancelado',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->liberarTicket($id);

        if ($solicitud) {
            $this->notificar((int) $solicitud['cliente_id'], 'solicitud_cancelada', 'Tu solicitud de asesoría fue cancelada por un administrativo', $id);
        }

        return $this->response->setJSON($this->toDtoSolicitud($this->fila($id)));
    }

    // Mapa de calor de cobertura (Módulo 5): grilla semanal (lunes-domingo) de franjas de 30 min
    // entre 08:00-20:00, con la cantidad de asesores disponibles AHORA MISMO (expandiendo cada
    // regla de horarios_docente a sus ocurrencias reales en la semana visible, ver
    // HorarioRecurrencia) por franja, y una marca cuando esa franja+fecha exacta tiene un ticket
    // de video pendiente/en espera sin resolver.
    public function coberturaHorarios(): ResponseInterface
    {
        $fechaParam  = (string) ($this->request->getGet('fecha') ?? date('Y-m-d'));
        $sectorParam = $this->request->getGet('sectorId');
        $sectorId    = ($sectorParam !== null && $sectorParam !== '') ? (int) $sectorParam : null;

        $ts             = strtotime($fechaParam) ?: time();
        $diaSemanaInput = (int) date('N', $ts);
        $lunesTs        = $ts - ($diaSemanaInput - 1) * 86400;
        $lunes          = date('Y-m-d', $lunesTs);
        $domingo        = date('Y-m-d', $lunesTs + 6 * 86400);

        $dias = [];
        for ($i = 0; $i < 7; $i++) {
            $dias[] = ['fecha' => date('Y-m-d', $lunesTs + $i * 86400)];
        }

        $franjas = [];
        for ($h = 8; $h < 20; $h++) {
            $franjas[] = sprintf('%02d:00', $h);
            $franjas[] = sprintf('%02d:30', $h);
        }

        $db      = db_connect();
        $builder = $db->table('horarios_docente hd')
            ->select('hd.fecha_inicio, hd.tipo_repeticion, hd.hora_inicio, hd.hora_fin, u.id as usuario_id, u.nombre, u.foto_url')
            ->join('usuarios u', 'u.id = hd.usuario_id')
            ->where('u.rol', 'asesor')
            ->where('u.estado', 'activo')
            ->where('u.disponible', 1);
        if ($sectorId !== null) {
            $builder->join('asesor_especialidades ae', 'ae.usuario_id = u.id')->where('ae.sector_id', $sectorId);
        }
        $reglas = $builder->get()->getResultArray();

        // Un bloque "09:00-12:00" cubre TODAS las franjas de 30 min dentro de ese rango
        // (09:00, 09:30, ..., 11:30) — antes solo se marcaba el minuto exacto de inicio, dejando el
        // resto del bloque como "sin cobertura" en el mapa de calor.
        $porFranja = [];
        foreach ($dias as $dia) {
            foreach ($reglas as $f) {
                if (! HorarioRecurrencia::ocurreEnFecha($f, $dia['fecha'])) {
                    continue;
                }
                $docente   = ['id' => (string) $f['usuario_id'], 'nombre' => $f['nombre'], 'fotoUrl' => $f['foto_url'] ?? null];
                [$hI, $mI] = array_map('intval', explode(':', substr((string) $f['hora_inicio'], 0, 5)));
                [$hF, $mF] = array_map('intval', explode(':', substr((string) $f['hora_fin'], 0, 5)));
                for ($t = $hI * 60 + $mI; $t < $hF * 60 + $mF; $t += 30) {
                    $hora                                     = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
                    $porFranja[$dia['fecha']][$hora][]         = $docente;
                }
            }
        }

        $pendientesSet = [];
        $pendientes    = $db->table('solicitudes_asesoria')
            ->select('horario_fecha, horario_hora_inicio')
            ->where('tipo', 'video')
            ->whereIn('estado', ['pendiente', 'en_espera'])
            ->where('horario_fecha >=', $lunes)
            ->where('horario_fecha <=', $domingo)
            ->get()->getResultArray();
        foreach ($pendientes as $p) {
            $pendientesSet[$p['horario_fecha'] . '|' . substr((string) $p['horario_hora_inicio'], 0, 5)] = true;
        }

        $celdas = [];
        foreach ($dias as $dia) {
            foreach ($franjas as $hora) {
                $celdas[] = [
                    'fecha'      => $dia['fecha'],
                    'horaInicio' => $hora,
                    'docentes'   => $porFranja[$dia['fecha']][$hora] ?? [],
                    'pendiente'  => isset($pendientesSet[$dia['fecha'] . '|' . $hora]),
                ];
            }
        }

        return $this->response->setJSON([
            'lunes'   => $lunes,
            'domingo' => $domingo,
            'dias'    => array_map(static fn (array $d) => $d['fecha'], $dias),
            'franjas' => $franjas,
            'celdas'  => $celdas,
        ]);
    }

    // Caso especial (docs §4 Fase 2): varios alumnos esperando el mismo horario de video sin
    // docente asignado, ordenados por urgencia de SLA (el que vence antes, primero).
    public function mismoHorario(): ResponseInterface
    {
        $fecha      = (string) ($this->request->getGet('fecha') ?? '');
        $horaInicio = $this->conSegundos((string) ($this->request->getGet('horaInicio') ?? ''));
        $horaFin    = $this->conSegundos((string) ($this->request->getGet('horaFin') ?? ''));

        $filas = db_connect()->table('solicitudes_asesoria sa')
            ->select('sa.*, c.nombre as cliente_nombre, c.foto_url as cliente_foto_url, s.nombre as sector_nombre')
            ->join('usuarios c', 'c.id = sa.cliente_id')
            ->join('sectores s', 's.id = sa.sector_id', 'left')
            ->where('sa.tipo', 'video')
            ->whereIn('sa.estado', ['pendiente', 'en_espera'])
            ->where('sa.horario_fecha', $fecha)
            ->where('sa.horario_hora_inicio', $horaInicio)
            ->where('sa.horario_hora_fin', $horaFin)
            ->orderBy('sa.sla_vence_en', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDtoSolicitud'], $filas));
    }

    // Honorario = valor fijo del ticket × N.º de tickets completados en el periodo, sin importar
    // si el ticket vino de plan o add-on (docs §4 Fase 5 punto 3). Se agrupa por asesor porque la
    // autorización de pago es en bloque, no ticket por ticket.
    // El valor vive en Config\Asesoria porque la pantalla "Mi Liquidación" del asesor tiene que
    // mostrar exactamente el mismo número que calcula acá el administrativo.
    private function honorarioPorTicket(): int
    {
        return config('Asesoria')->honorarioPorTicket;
    }

    public function liquidaciones(): ResponseInterface
    {
        $periodo = (string) ($this->request->getGet('periodo') ?? date('Y-m'));
        [$inicio, $fin] = $this->rangoDelPeriodo($periodo);
        $honorario = $this->honorarioPorTicket();

        // COALESCE: `completado_en` es la fecha real de cierre, pero las filas anteriores a esa
        // columna podrían no tenerla — se cae a updated_at como antes para no perderlas.
        $filas = db_connect()->table('solicitudes_asesoria sa')
            ->select('sa.docente_id, u.nombre as docente_nombre, u.foto_url as docente_foto_url, sa.pago_autorizado_en')
            ->join('usuarios u', 'u.id = sa.docente_id')
            ->where('sa.estado', 'completado')
            ->where('COALESCE(sa.completado_en, sa.updated_at) >=', $inicio)
            ->where('COALESCE(sa.completado_en, sa.updated_at) <', $fin)
            ->get()->getResultArray();

        $porAsesor = [];
        foreach ($filas as $f) {
            $id = (int) $f['docente_id'];
            if (! isset($porAsesor[$id])) {
                $porAsesor[$id] = ['id' => $id, 'nombre' => $f['docente_nombre'], 'fotoUrl' => $f['docente_foto_url'] ?? null, 'completados' => 0, 'pagados' => 0];
            }
            $porAsesor[$id]['completados']++;
            if ($f['pago_autorizado_en'] !== null) {
                $porAsesor[$id]['pagados']++;
            }
        }

        $asesores = array_map(static function (array $a) use ($honorario) {
            $pendientes = $a['completados'] - $a['pagados'];

            return [
                'asesorId'           => (string) $a['id'],
                'asesorNombre'       => $a['nombre'],
                'asesorFotoUrl'      => $a['fotoUrl'],
                'ticketsCompletados' => $a['completados'],
                'ticketsPendientes'  => $pendientes,
                'honorarioTotal'     => $a['completados'] * $honorario,
                'honorarioPendiente' => $pendientes * $honorario,
                'todoPagado'         => $pendientes === 0,
            ];
        }, array_values($porAsesor));

        usort($asesores, static fn (array $a, array $b) => strcmp($a['asesorNombre'], $b['asesorNombre']));

        return $this->response->setJSON([
            'periodo'           => $periodo,
            'honorarioPorTicket' => $honorario,
            'asesores'          => $asesores,
        ]);
    }

    public function autorizarPago(): ResponseInterface
    {
        $dto       = $this->request->getJSON(true) ?? [];
        $asesorIds = array_map('intval', (array) ($dto['asesorIds'] ?? []));
        $periodo   = (string) ($dto['periodo'] ?? date('Y-m'));

        if ($asesorIds === []) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Selecciona al menos un asesor']);
        }

        [$inicio, $fin] = $this->rangoDelPeriodo($periodo);

        db_connect()->table('solicitudes_asesoria')
            ->whereIn('docente_id', $asesorIds)
            ->where('estado', 'completado')
            ->where('updated_at >=', $inicio)
            ->where('updated_at <', $fin)
            ->where('pago_autorizado_en IS NULL')
            ->update(['pago_autorizado_en' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON(['ok' => true]);
    }

    /** @return array{0: string, 1: string} [inicio, fin) del mes "YYYY-MM" en formato datetime */
    private function rangoDelPeriodo(string $periodo): array
    {
        [$anio, $mes] = array_map('intval', explode('-', $periodo) + [1 => (int) date('n')]);
        $inicio = sprintf('%04d-%02d-01 00:00:00', $anio ?: (int) date('Y'), $mes);

        return [$inicio, date('Y-m-d H:i:s', strtotime($inicio . ' +1 month'))];
    }

    public function configuracionSla(): ResponseInterface
    {
        $fila = db_connect()->table('configuracion_sla')->get()->getRowArray();

        return $this->response->setJSON($this->configuracionSlaADto($fila));
    }

    // Solo tiempoEsperaChatHoras y tiempoAceptacionVideoMinutos alimentan hoy calcularSlaVenceEn()
    // (SolicitudAsesoriaHelpersTrait). Los otros dos campos existen en la tabla desde
    // 2026-08-04-000024_AddMatchmakingASolicitudAsesoria pero ninguna lógica los lee todavía — se
    // exponen igual porque son parte del mismo registro de configuración, y el frontend avisa que
    // aún no tienen efecto.
    public function actualizarConfiguracionSla(): ResponseInterface
    {
        $dto = $this->request->getJSON(true) ?? [];

        $campos = [
            'tiempoEsperaChatHoras'        => 'tiempo_espera_chat_horas',
            'tiempoAceptacionVideoMinutos' => 'tiempo_aceptacion_video_minutos',
            'tiempoExtraConexionMinutos'   => 'tiempo_extra_conexion_minutos',
            'vigenciaHorarioDias'          => 'vigencia_horario_dias',
        ];

        $cambios = [];
        foreach ($campos as $campoDto => $columna) {
            if (! array_key_exists($campoDto, $dto)) {
                continue;
            }
            $valor = (int) $dto[$campoDto];
            if ($valor <= 0) {
                return $this->response->setStatusCode(422)->setJSON(['error' => "El valor de \"{$campoDto}\" debe ser mayor que cero"]);
            }
            $cambios[$columna] = $valor;
        }

        // null = el alumno puede cancelar su solicitud en cualquier momento ("permanentemente"
        // habilitado) — a diferencia de los campos de arriba, null es un valor válido acá, no un
        // error.
        if (array_key_exists('cancelacionLimiteMinutos', $dto)) {
            $valor = $dto['cancelacionLimiteMinutos'];
            if ($valor !== null && (! is_numeric($valor) || (int) $valor <= 0)) {
                return $this->response->setStatusCode(422)->setJSON(['error' => 'El límite de cancelación debe ser un número mayor que cero, o null para "sin límite"']);
            }
            $cambios['cancelacion_limite_minutos'] = $valor === null ? null : (int) $valor;
        }

        $db   = db_connect();
        $fila = $db->table('configuracion_sla')->get()->getRowArray();
        if ($cambios !== []) {
            $db->table('configuracion_sla')->where('id', $fila['id'])->update($cambios);
            $fila = $db->table('configuracion_sla')->get()->getRowArray();
        }

        return $this->response->setJSON($this->configuracionSlaADto($fila));
    }

    private function configuracionSlaADto(array $fila): array
    {
        return [
            'tiempoEsperaChatHoras'        => (int) $fila['tiempo_espera_chat_horas'],
            'tiempoAceptacionVideoMinutos' => (int) $fila['tiempo_aceptacion_video_minutos'],
            'tiempoExtraConexionMinutos'   => (int) $fila['tiempo_extra_conexion_minutos'],
            'vigenciaHorarioDias'          => (int) $fila['vigencia_horario_dias'],
            'cancelacionLimiteMinutos'     => $fila['cancelacion_limite_minutos'] !== null ? (int) $fila['cancelacion_limite_minutos'] : null,
        ];
    }
}
