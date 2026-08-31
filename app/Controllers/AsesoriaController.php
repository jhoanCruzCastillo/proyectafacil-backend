<?php

namespace App\Controllers;

use App\Controllers\Support\SolicitudAsesoriaHelpersTrait;
use App\Libraries\AdjuntoChatStorage;
use App\Libraries\CloudinaryUploader;
use App\Libraries\GoogleMeetService;
use App\Libraries\S3ObjectStore;
use App\Libraries\StreamProxy;
use App\Models\ActividadModel;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

// Solicitudes de asesoría 1:1 cliente↔docente (chat o videollamada por link externo) y sus
// mensajes. Desde el Módulo 2 el cliente ya no elige un docente al crear la solicitud: el chatbot
// guiado reserva un ticket de consulta y crea la solicitud "Pendiente" sin asignar. Desde el
// Módulo 3 (docs/proyectafacil-asesorias.md §4 Fase 2), crear() hace broadcast a todos los asesores
// elegibles (solicitud_notificaciones) y aceptar() resuelve la carrera con un UPDATE condicionado
// a estado='pendiente' — el primero en llegar gana, a los demás se les informa que ya fue tomada.
// Una vez asignada/agendada, ambos pueden intercambiar mensajes vía polling — no hay WebSockets en
// el proyecto. Cada transición relevante deja una notificación para la otra parte (ver
// NotificacionesController). El lado Administrativo de Asesorías (dashboard, intervención manual)
// vive en TicketsAsesoriaController — comparten helpers vía SolicitudAsesoriaHelpersTrait.
class AsesoriaController extends BaseController
{
    use SolicitudAsesoriaHelpersTrait;

    public function misSolicitudes(): ResponseInterface
    {
        $usuarioId = (int) ($this->request->getGet('usuarioId') ?? 0);
        $rol       = (string) ($this->request->getGet('rol') ?? 'cliente');
        $db        = db_connect();

        $builder = $db->table('solicitudes_asesoria sa')
            ->select('sa.*, c.nombre as cliente_nombre, c.foto_url as cliente_foto_url, d.nombre as docente_nombre, d.foto_url as docente_foto_url, s.nombre as sector_nombre')
            ->join('usuarios c', 'c.id = sa.cliente_id')
            ->join('usuarios d', 'd.id = sa.docente_id', 'left')
            ->join('sectores s', 's.id = sa.sector_id', 'left');

        if ($rol === 'cliente') {
            $builder->where('sa.cliente_id', $usuarioId);
        } else {
            // Mías (ya asignadas/agendadas a mí) + las pendientes que me llegaron por broadcast.
            $notificadas = $db->table('solicitud_notificaciones')->select('solicitud_id')->where('asesor_id', $usuarioId)->get()->getResultArray();
            $ids         = array_map(static fn (array $n) => (int) $n['solicitud_id'], $notificadas);

            $builder->groupStart()
                ->where('sa.docente_id', $usuarioId)
                ->orGroupStart()
                    ->where('sa.estado', 'pendiente')
                    ->whereIn('sa.id', $ids === [] ? [0] : $ids)
                ->groupEnd()
            ->groupEnd();
        }

        $filas = $builder->orderBy('sa.created_at', 'DESC')->get()->getResultArray();
        $filas = array_map([$this, 'resolverAsistenciaSiCorresponde'], $filas);

        return $this->response->setJSON(array_map([$this, 'toDtoSolicitud'], $filas));
    }

    // Pantalla "No atendidas / reasignadas" del asesor — dos listas que le muestran lo que dejó
    // pasar, para que dimensione cuánto trabajo está perdiendo:
    //  - noAceptadas: le llegaron por broadcast pero terminaron en manos de otro asesor, vencieron
    //    sin que nadie respondiera, o el alumno las canceló mientras esperaba.
    //  - agendadasNoAtendidas: eran suyas y con hora fijada, pero la hora ya pasó y nunca se
    //    cerraron como completadas.
    public function noAtendidas(): ResponseInterface
    {
        $usuarioId = (int) ($this->request->getGet('usuarioId') ?? 0);
        $db        = db_connect();

        $notificadas = $db->table('solicitud_notificaciones')->select('solicitud_id')->where('asesor_id', $usuarioId)->get()->getResultArray();
        $ids         = array_map(static fn (array $n) => (int) $n['solicitud_id'], $notificadas);

        $noAceptadas = [];
        if ($ids !== []) {
            $filas = $this->builderSolicitudes($db)
                ->whereIn('sa.id', $ids)
                ->groupStart()
                    ->where('sa.docente_id IS NULL', null, false)
                    ->orWhere('sa.docente_id !=', $usuarioId)
                ->groupEnd()
                ->orderBy('sa.created_at', 'DESC')
                ->get()->getResultArray();

            $noAceptadas = array_map(function (array $s): array {
                $dto = $this->toDtoSolicitud($s);
                $dto['motivo'] = $this->motivoNoAceptada($s);

                return $dto;
            }, $filas);
        }

        // El filtro "ya pasó la hora" se hace en PHP y no en SQL a propósito: sumar fecha + hora
        // en la consulta obliga a sintaxis distinta por motor (Postgres/MySQL) y el volumen por
        // asesor es chico.
        $agendadas = $this->builderSolicitudes($db)
            ->where('sa.docente_id', $usuarioId)
            ->where('sa.estado', 'agendado')
            ->where('sa.horario_fecha IS NOT NULL', null, false)
            ->orderBy('sa.horario_fecha', 'DESC')
            ->get()->getResultArray();

        $ahora = time();
        $agendadasNoAtendidas = [];
        foreach ($agendadas as $s) {
            $fin = strtotime($s['horario_fecha'] . ' ' . ($s['horario_hora_fin'] ?? '23:59:59'));
            if ($fin !== false && $fin < $ahora) {
                $agendadasNoAtendidas[] = $this->toDtoSolicitud($s);
            }
        }

        return $this->response->setJSON([
            'noAceptadas'          => $noAceptadas,
            'agendadasNoAtendidas' => $agendadasNoAtendidas,
        ]);
    }

    private function builderSolicitudes(\CodeIgniter\Database\BaseConnection $db)
    {
        return $db->table('solicitudes_asesoria sa')
            ->select('sa.*, c.nombre as cliente_nombre, c.foto_url as cliente_foto_url, d.nombre as docente_nombre, d.foto_url as docente_foto_url, s.nombre as sector_nombre')
            ->join('usuarios c', 'c.id = sa.cliente_id')
            ->join('usuarios d', 'd.id = sa.docente_id', 'left')
            ->join('sectores s', 's.id = sa.sector_id', 'left');
    }

    private function motivoNoAceptada(array $s): string
    {
        if ($s['docente_id'] !== null) {
            return 'tomada_por_otro';
        }
        if ($s['estado'] === 'cancelado') {
            return 'cancelada_por_alumno';
        }

        return 'vencida_sin_respuesta';
    }

    // Bloques de video ya agendados (con docente asignado) dentro de un rango de fechas — usado
    // por el selector de horario del cliente para no ofrecer un bloque que YA está tomado por el
    // único docente que lo cubre. Si otro docente distinto ofrece ese mismo horario recurrente y
    // sigue libre esa fecha, el bloque se sigue mostrando (el cruce lo hace el frontend contra
    // disponibilidadAgregada, comparando por docenteId).
    public function agendadosPorRango(): ResponseInterface
    {
        $desde = (string) ($this->request->getGet('desde') ?? '');
        $hasta = (string) ($this->request->getGet('hasta') ?? '');

        $filas = db_connect()->table('solicitudes_asesoria')
            ->select('docente_id, horario_fecha, horario_hora_inicio, horario_hora_fin')
            ->where('tipo', 'video')
            ->where('estado', 'agendado')
            ->where('horario_fecha >=', $desde)
            ->where('horario_fecha <=', $hasta)
            ->get()->getResultArray();

        return $this->response->setJSON(array_map(static fn (array $f) => [
            'docenteId'  => (string) $f['docente_id'],
            'fecha'      => $f['horario_fecha'],
            'horaInicio' => substr((string) $f['horario_hora_inicio'], 0, 5),
            'horaFin'    => substr((string) $f['horario_hora_fin'], 0, 5),
        ], $filas));
    }

    public function crear(): ResponseInterface
    {
        $dto               = $this->request->getJSON(true) ?? [];
        $db                = db_connect();
        $clienteId         = (int) ($dto['clienteId'] ?? 0);
        $cuentaId          = $this->idCuentaDe($clienteId);
        $tipo              = (string) ($dto['tipo'] ?? 'chat');
        $sectorId          = ! empty($dto['sectorId']) ? (int) $dto['sectorId'] : null;
        $horarioFecha      = $dto['horarioFecha'] ?? null;
        $horarioHoraInicio = ! empty($dto['horarioHoraInicio']) ? $this->conSegundos((string) $dto['horarioHoraInicio']) : null;
        $horarioHoraFin    = ! empty($dto['horarioHoraFin']) ? $this->conSegundos((string) $dto['horarioHoraFin']) : null;

        $ticket = $this->reservarTicket($cuentaId, $tipo);
        if (! $ticket) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'No tienes fichas disponibles para esta modalidad']);
        }

        $db->table('solicitudes_asesoria')->insert([
            'cliente_id'          => $clienteId,
            'docente_id'          => null,
            'ejemplo_id'          => ! empty($dto['ejemploId']) ? (int) $dto['ejemploId'] : null,
            'sector_id'           => $sectorId,
            'tipo_documento'      => $dto['tipoDocumento'] ?? null,
            'tipo'                => $tipo,
            'estado'              => 'pendiente',
            'mensaje_inicial'     => $dto['mensajeInicial'] ?? null,
            'horario_fecha'       => $horarioFecha,
            'horario_hora_inicio' => $horarioHoraInicio,
            'horario_hora_fin'    => $horarioHoraFin,
            'sla_vence_en'        => $this->calcularSlaVenceEn($tipo),
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
        $id = $db->insertID();

        $db->table('tickets_consulta')->where('id', $ticket['id'])->update([
            'estado'                => 'reservado',
            'solicitud_asesoria_id' => $id,
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);

        $this->broadcast((int) $id, $tipo, $sectorId, $horarioFecha, $horarioHoraInicio, $horarioHoraFin);

        (new ActividadModel())->insert([
            'mensaje'    => 'Reservó una sesión de asesoría',
            'color'      => 'blue',
            'categoria'  => 'Sesiones',
            'actor_id'   => $clienteId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // La respuesta alimenta el modal de confirmación del alumno (muestra la categoría elegida),
        // así que a diferencia de fila() acá sí hace falta el join a sectores.
        $fila = $db->table('solicitudes_asesoria sa')
            ->select('sa.*, s.nombre as sector_nombre')
            ->join('sectores s', 's.id = sa.sector_id', 'left')
            ->where('sa.id', $id)
            ->get()->getRowArray();

        return $this->response->setJSON($this->toDtoSolicitud($fila));
    }

    public function aceptar($id = null): ResponseInterface
    {
        $id       = (int) $id;
        $dto      = $this->request->getJSON(true) ?? [];
        $asesorId = (int) ($dto['asesorId'] ?? 0);
        $db       = db_connect();

        $solicitudPrevia = $this->fila($id);
        if (! $solicitudPrevia) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Solicitud no encontrada']);
        }

        $fueNotificado = $db->table('solicitud_notificaciones')
            ->where('solicitud_id', $id)
            ->where('asesor_id', $asesorId)
            ->countAllResults() > 0;
        if (! $fueNotificado) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'No fuiste notificado de esta solicitud']);
        }

        $esVideo     = $solicitudPrevia['tipo'] === 'video';
        $nuevoEstado = $esVideo ? 'agendado' : 'asignado';
        // El link ya no se pide a mano — se genera automáticamente al confirmar, con un evento real
        // de Google Calendar (Meet) vía GoogleMeetService. Si Google falla, se prefiere devolver el
        // error ahora (en vez de guardar un link simulado en silencio) — estamos verificando que la
        // integración real funcione, un fallo silencioso solo escondería el problema.
        $linkReunion = null;
        if ($esVideo) {
            try {
                $linkReunion = (new GoogleMeetService())->crearLinkReunion(
                    "Asesoría Proyecta Fácil #{$id}",
                    (string) $solicitudPrevia['horario_fecha'],
                    (string) $solicitudPrevia['horario_hora_inicio'],
                    (string) $solicitudPrevia['horario_hora_fin'],
                    $this->correosParaInvitar((int) $solicitudPrevia['cliente_id'], $asesorId),
                    $this->correoAsesor($asesorId),
                );
            } catch (Throwable $e) {
                log_message('error', 'GoogleMeetService: no se pudo generar el link de Meet para la solicitud {id}: {msg}', ['id' => $id, 'msg' => $e->getMessage()]);

                return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo generar el link de la videollamada. Intenta de nuevo en unos minutos.']);
            }
        }

        // Carrera resuelta de forma atómica: el WHERE estado='pendiente' hace que solo el primer
        // UPDATE que llegue afecte una fila — los siguientes intentos (mismo id) afectan 0 filas.
        $db->table('solicitudes_asesoria')
            ->where('id', $id)
            ->where('estado', 'pendiente')
            ->update([
                'docente_id'   => $asesorId,
                'estado'       => $nuevoEstado,
                'link_reunion' => $linkReunion,
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

        if ($db->affectedRows() === 0) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Esta consulta ya fue tomada por otro asesor']);
        }

        $solicitud = $this->fila($id);
        $this->notificar((int) $solicitud['cliente_id'], 'solicitud_aceptada', 'Tu solicitud de asesoría fue aceptada', $id);

        return $this->response->setJSON($this->toDtoSolicitud($solicitud));
    }

    // Cierre manual explícito de una videollamada 'agendada' ya terminada — atajo para el asesor,
    // que no tiene por qué esperar a que la pantalla vuelva a hacer polling (misSolicitudes ya
    // resuelve esto solo, cada 15s, una vez pasado el horario + margen de salida). Reusa la misma
    // verificación real de asistencia que la resolución automática — a diferencia de finalizar()
    // (incondicional, pero solo válido para chat, que no tiene horario que verificar), este nunca
    // marca "completado" a ciegas por el solo hecho de que alguien apretó el botón.
    public function completarVideo($id = null): ResponseInterface
    {
        $id        = (int) $id;
        $solicitud = $this->fila($id);
        if (! $solicitud || $solicitud['tipo'] !== 'video' || $solicitud['estado'] !== 'agendado') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Esta solicitud no se puede completar']);
        }

        $resultado = $this->resolverAsistenciaManual($solicitud);
        if ($resultado['estado'] === 'agendado') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Todavía no se puede completar esta asesoría. Espera a que termine el horario agendado (con su margen de salida) e inténtalo de nuevo.']);
        }

        return $this->response->setJSON($this->toDtoSolicitud($resultado));
    }

    public function finalizar($id = null): ResponseInterface
    {
        $id    = (int) $id;
        $ahora = date('Y-m-d H:i:s');
        // `completado_en` se escribe una sola vez y no se vuelve a tocar — es la fecha sobre la que
        // se calculan las liquidaciones, y no puede moverse por ediciones posteriores de la fila
        // (calificación del alumno, autorización de pago) como sí le pasa a `updated_at`.
        db_connect()->table('solicitudes_asesoria')->where('id', $id)->update([
            'estado'        => 'completado',
            'completado_en' => $ahora,
            'updated_at'    => $ahora,
        ]);
        $this->consumirTicket($id);

        $solicitud = $this->fila($id);
        if ($solicitud) {
            $this->notificar((int) $solicitud['cliente_id'], 'solicitud_completada', 'Tu asesoría fue marcada como completada — cuéntanos cómo estuvo', $id);
        }

        return $this->response->setJSON($this->toDtoSolicitud($solicitud));
    }

    // Cancelación propia del alumno (docs §Fase 4): libera el ticket reservado de vuelta a
    // 'disponible' — solo aplica antes de que un docente acepte.
    public function cancelarPropia($id = null): ResponseInterface
    {
        $id        = (int) $id;
        $solicitud = $this->fila($id);
        if (! $solicitud) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Solicitud no encontrada']);
        }

        // null en configuracion_sla.cancelacion_limite_minutos = el alumno puede cancelar en
        // cualquier momento ("permanentemente" habilitado, ver Configuración de SLA).
        $limiteMin = db_connect()->table('configuracion_sla')->get()->getRowArray()['cancelacion_limite_minutos'] ?? null;
        if ($limiteMin !== null) {
            $creadoEn = strtotime($solicitud['created_at'] . ' UTC');
            if (time() > $creadoEn + (int) $limiteMin * 60) {
                return $this->response->setStatusCode(422)->setJSON(['error' => "Ya pasó el tiempo permitido para cancelar esta solicitud ({$limiteMin} min desde que la enviaste)."]);
            }
        }

        db_connect()->table('solicitudes_asesoria')->where('id', $id)->update([
            'estado'     => 'cancelado',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->liberarTicket($id);

        return $this->response->setJSON($this->toDtoSolicitud($this->fila($id)));
    }

    // Calificación del alumno (docs §4 Fase 5 punto 2) — 5 estrellas + comentario opcional, solo
    // sobre un ticket ya completado y solo una vez (no se sobreescribe).
    public function calificar($id = null): ResponseInterface
    {
        $id  = (int) $id;
        $dto = $this->request->getJSON(true) ?? [];

        $solicitud = $this->fila($id);
        if (! $solicitud || $solicitud['estado'] !== 'completado') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Solo se puede calificar un ticket completado']);
        }
        if ($solicitud['calificacion'] !== null) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Este ticket ya fue calificado']);
        }

        $estrellas = (int) ($dto['estrellas'] ?? 0);
        if ($estrellas < 1 || $estrellas > 5) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'La calificación debe estar entre 1 y 5']);
        }

        db_connect()->table('solicitudes_asesoria')->where('id', $id)->update([
            'calificacion'            => $estrellas,
            'calificacion_comentario' => trim((string) ($dto['comentario'] ?? '')) ?: null,
            'updated_at'              => date('Y-m-d H:i:s'),
        ]);

        (new ActividadModel())->insert([
            'mensaje'    => "Evaluó una sesión de asesoría con {$estrellas} estrellas",
            'color'      => 'orange',
            'categoria'  => 'Evaluación',
            'actor_id'   => (int) $solicitud['cliente_id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON($this->toDtoSolicitud($this->fila($id)));
    }

    // `visorId` (opcional) = quién está consultando este chat ahora mismo — si viene, marca como
    // leídos (para el remitente, la segunda palomita) todos los mensajes ajenos que seguían sin
    // leer. Sin `visorId` (ej. algún caller viejo) simplemente no marca nada, solo lista.
    public function mensajes($solicitudId = null): ResponseInterface
    {
        $solicitudId = (int) $solicitudId;
        $visorId     = $this->request->getGet('visorId');

        if ($visorId !== null && $visorId !== '') {
            db_connect()->table('mensajes_asesoria')
                ->where('solicitud_id', $solicitudId)
                ->where('autor_id !=', (int) $visorId)
                ->where('leido_en', null)
                ->update(['leido_en' => date('Y-m-d H:i:s')]);
        }

        $filas = db_connect()->table('mensajes_asesoria')
            ->where('solicitud_id', $solicitudId)
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDtoMensaje'], $filas));
    }

    public function enviarMensaje($solicitudId = null): ResponseInterface
    {
        $solicitudId = (int) $solicitudId;
        $dto         = $this->request->getJSON(true) ?? [];
        $autorId     = (int) ($dto['autorId'] ?? 0);
        $texto       = (string) ($dto['texto'] ?? '');
        $adjuntoUrl  = trim((string) ($dto['adjuntoUrl'] ?? ''));
        $db          = db_connect();

        if ($texto === '' && $adjuntoUrl === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'El mensaje está vacío']);
        }

        $solicitud = $this->fila($solicitudId);
        // "Inició una conversación" (Actividad del cliente): su primer mensaje propio en esta
        // solicitud — se decide ANTES de insertar el suyo, para no contarlo a sí mismo.
        $esPrimerMensajeDelCliente = $solicitud
            && $autorId === (int) $solicitud['cliente_id']
            && $db->table('mensajes_asesoria')->where('solicitud_id', $solicitudId)->where('autor_id', $autorId)->countAllResults() === 0;

        $db->table('mensajes_asesoria')->insert([
            'solicitud_id'   => $solicitudId,
            'autor_id'       => $autorId,
            'texto'          => $texto,
            'adjunto_url'    => $adjuntoUrl !== '' ? $adjuntoUrl : null,
            'adjunto_nombre' => $adjuntoUrl !== '' ? trim((string) ($dto['adjuntoNombre'] ?? '')) ?: null : null,
            'adjunto_tipo'   => $adjuntoUrl !== '' ? trim((string) ($dto['adjuntoTipo'] ?? '')) ?: null : null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        if ($esPrimerMensajeDelCliente) {
            (new ActividadModel())->insert([
                'mensaje'    => 'Inició una conversación con su asesor',
                'color'      => 'gray',
                'categoria'  => 'Asesoría 1:1',
                'actor_id'   => $autorId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $destinoId = $autorId === (int) $solicitud['cliente_id'] ? (int) $solicitud['docente_id'] : (int) $solicitud['cliente_id'];
        $this->notificar($destinoId, 'nuevo_mensaje', 'Tienes un nuevo mensaje de asesoría', $solicitudId);

        $filas = $db->table('mensajes_asesoria')->where('solicitud_id', $solicitudId)->orderBy('created_at', 'ASC')->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDtoMensaje'], $filas));
    }

    // Sube un adjunto de chat (cualquier tipo de archivo) y devuelve su URL — el frontend la usa
    // después en el POST de enviarMensaje(). Separado en dos pasos (subir, luego enviar) para poder
    // mostrar progreso de subida antes de que el mensaje exista.
    //
    // Imágenes siguen yendo a Cloudinary (resource_type=image, sin restricción de entrega — se
    // muestran inline con <img> directo). Todo lo demás (PDF, Word, ZIP…) va a AdjuntoChatStorage:
    // Cloudinary bloquea por defecto la entrega pública de raw PDF/ZIP desde 2025 (ver el comentario
    // en esa clase) — el valor que devuelve acá puede ser `s3:{key}` (no es una URL usable todavía;
    // toDtoMensaje() la traduce recién cuando ya existe el id del mensaje, ver urlParaCliente()).
    //
    // Preferido: multipart binario (campo "archivo") — encontrado en vivo (2026-08-31) que un PDF
    // real mandado como data URI en JSON (~33 % más pesado que el archivo + todo bufferizado en
    // memoria de PHP de una sola vez) terminaba en net::ERR_HTTP2_PROTOCOL_ERROR en el navegador.
    // Compat: JSON con dataUrl se mantiene por si algo más lo sigue usando.
    public function subirAdjunto(): ResponseInterface
    {
        try {
            $file = $this->request->getFile('archivo');
            if ($file && $file->isValid() && ! $file->hasMoved()) {
                $nombre = $file->getClientName() ?: 'archivo';
                $tipo   = $file->getClientMimeType() ?: 'application/octet-stream';
                $url    = str_starts_with($tipo, 'image/')
                    ? (new CloudinaryUploader())->subirAdjuntoChat($file->getTempName(), $nombre, $tipo)
                    : (new AdjuntoChatStorage())->subirDesdeRuta($file->getTempName(), $nombre, $tipo);

                return $this->response->setJSON(['url' => $url]);
            }

            $dto     = $this->request->getJSON(true) ?? [];
            $dataUrl = (string) ($dto['dataUrl'] ?? '');
            $nombre  = (string) ($dto['nombre'] ?? 'archivo');
            $tipo    = (string) ($dto['tipo'] ?? 'application/octet-stream');
            if ($dataUrl === '') {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta el archivo (campo multipart "archivo" o dataUrl)']);
            }
            $url = str_starts_with($tipo, 'image/')
                ? (new CloudinaryUploader())->subirAdjuntoChat($dataUrl, $nombre, $tipo)
                : (new AdjuntoChatStorage())->subirDesdeDataUrl($dataUrl, $nombre, $tipo);

            return $this->response->setJSON(['url' => $url]);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo subir el archivo: ' . $e->getMessage()]);
        }
    }

    /** Proxy con Bearer del adjunto de un mensaje — ver el comentario en AdjuntoChatStorage. */
    public function adjuntoMensaje($mensajeId = null): ResponseInterface
    {
        $m = db_connect()->table('mensajes_asesoria')->where('id', (int) $mensajeId)->get()->getRowArray();
        $stored = (string) ($m['adjunto_url'] ?? '');
        if ($m === null || $stored === '') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Adjunto no encontrado']);
        }

        $nombre = (string) ($m['adjunto_nombre'] ?? 'archivo');
        $mime   = (string) ($m['adjunto_tipo'] ?? 'application/octet-stream');

        if (! S3ObjectStore::esStoredS3($stored)) {
            // Mensaje viejo con URL https de Cloudinary — no debería llegar acá (toDtoMensaje() ya
            // devuelve la URL directa para ese caso), pero por las dudas se redirige en vez de fallar.
            return $this->response->redirect($stored);
        }

        try {
            $psr = (new S3ObjectStore())->getObjectPsrResponse(S3ObjectStore::claveDe($stored), true);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo obtener el adjunto: ' . $e->getMessage()]);
        }
        $len = $psr->getHeaderLine('Content-Length');
        StreamProxy::pipe($psr->getBody(), $mime, $nombre, $len !== '' ? (int) $len : null);
    }

    // Notifica (solicitud_notificaciones + inbox) a todos los asesores elegibles al crear.
    private function broadcast(int $solicitudId, string $tipo, ?int $sectorId, ?string $horarioFecha, ?string $horaInicio, ?string $horaFin): void
    {
        $db       = db_connect();
        $asesores = $tipo === 'video'
            ? $this->asesoresPorHorario($horarioFecha, $horaInicio, $horaFin)
            : $this->asesoresPorSector($sectorId);

        $ahora = date('Y-m-d H:i:s');
        foreach (array_unique($asesores) as $asesorId) {
            $db->table('solicitud_notificaciones')->insert([
                'solicitud_id' => $solicitudId,
                'asesor_id'    => $asesorId,
                'created_at'   => $ahora,
            ]);
            $this->notificar($asesorId, 'nueva_solicitud_asesoria', 'Tienes una nueva solicitud de asesoría', $solicitudId);
        }
    }

    // Prioridad plan > add-on, más antiguo primero (docs §2 "Orden de consumo"). Filtra por
    // modalidad — una ficha de chat nunca reserva una videollamada ni viceversa (pedido explícito
    // del usuario: son dos tipos de ficha separados, cada uno con su propia duración).
    private function reservarTicket(int $usuarioId, string $modalidad): ?array
    {
        $db    = db_connect();
        $comun = static fn ($q) => $q->where('usuario_id', $usuarioId)->where('estado', 'disponible')->where('modalidad', $modalidad);

        $ticket = $comun($db->table('tickets_consulta')->where('origen', 'plan'))->orderBy('id', 'ASC')->get()->getRowArray();

        return $ticket ?? $comun($db->table('tickets_consulta')->where('origen', 'addon'))->orderBy('id', 'ASC')->get()->getRowArray();
    }

    private function idCuentaDe(int $usuarioId): int
    {
        $u = db_connect()->table('usuarios')->select('cuenta_cliente_id')->where('id', $usuarioId)->get()->getRowArray();

        return $u && $u['cuenta_cliente_id'] !== null ? (int) $u['cuenta_cliente_id'] : $usuarioId;
    }

    private function toDtoMensaje(array $m): array
    {
        $adjuntoUrl = $m['adjunto_url'] ?? null;

        return [
            'id'            => (string) $m['id'],
            'solicitudId'   => (string) $m['solicitud_id'],
            'autorId'       => (string) $m['autor_id'],
            'texto'         => $m['texto'],
            'adjuntoUrl'    => $adjuntoUrl !== null
                ? (new AdjuntoChatStorage())->urlParaCliente((string) $adjuntoUrl, (string) $m['id'])
                : null,
            'adjuntoNombre' => $m['adjunto_nombre'] ?? null,
            'adjuntoTipo'   => $m['adjunto_tipo'] ?? null,
            'creadoEn'      => $this->datetimeAIso($m['created_at']),
            'leidoEn'       => $m['leido_en'] !== null ? $this->datetimeAIso($m['leido_en']) : null,
        ];
    }
}
