<?php

namespace App\Controllers;

use App\Controllers\Support\SolicitudAsesoriaHelpersTrait;
use CodeIgniter\HTTP\ResponseInterface;

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

        return $this->response->setJSON(array_map([$this, 'toDtoSolicitud'], $filas));
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
        // El link ya no se pide a mano — se genera automáticamente al confirmar (simulado, ver
        // generarLinkSimulado() hasta tener credenciales reales de Zoom/Meet).
        $linkReunion = $esVideo ? $this->generarLinkSimulado($id) : null;

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

    public function finalizar($id = null): ResponseInterface
    {
        $id = (int) $id;
        db_connect()->table('solicitudes_asesoria')->where('id', $id)->update([
            'estado'     => 'completado',
            'updated_at' => date('Y-m-d H:i:s'),
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
        $id = (int) $id;
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

        return $this->response->setJSON($this->toDtoSolicitud($this->fila($id)));
    }

    public function mensajes($solicitudId = null): ResponseInterface
    {
        $filas = db_connect()->table('mensajes_asesoria')
            ->where('solicitud_id', (int) $solicitudId)
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDtoMensaje'], $filas));
    }

    public function enviarMensaje($solicitudId = null): ResponseInterface
    {
        $solicitudId = (int) $solicitudId;
        $dto         = $this->request->getJSON(true) ?? [];
        $autorId     = (int) ($dto['autorId'] ?? 0);
        $db          = db_connect();

        $db->table('mensajes_asesoria')->insert([
            'solicitud_id' => $solicitudId,
            'autor_id'     => $autorId,
            'texto'        => (string) ($dto['texto'] ?? ''),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $solicitud   = $this->fila($solicitudId);
        $destinoId   = $autorId === (int) $solicitud['cliente_id'] ? (int) $solicitud['docente_id'] : (int) $solicitud['cliente_id'];
        $this->notificar($destinoId, 'nuevo_mensaje', 'Tienes un nuevo mensaje de asesoría', $solicitudId);

        $filas = $db->table('mensajes_asesoria')->where('solicitud_id', $solicitudId)->orderBy('created_at', 'ASC')->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDtoMensaje'], $filas));
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
        return [
            'id'          => (string) $m['id'],
            'solicitudId' => (string) $m['solicitud_id'],
            'autorId'     => (string) $m['autor_id'],
            'texto'       => $m['texto'],
            'creadoEn'    => $this->datetimeAIso($m['created_at']),
        ];
    }
}
