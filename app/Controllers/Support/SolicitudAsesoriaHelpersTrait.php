<?php

namespace App\Controllers\Support;

// Compartido entre AsesoriaController (lado alumno/asesor) y TicketsAsesoriaController (lado
// Administrativo de Asesorías, Módulo 4) — el DTO de una solicitud, el broadcast a asesores
// elegibles y notificaciones son idénticos desde ambos lados, solo cambia quién los invoca.
trait SolicitudAsesoriaHelpersTrait
{
    private function fila(int $id): ?array
    {
        return db_connect()->table('solicitudes_asesoria')->where('id', $id)->get()->getRowArray();
    }

    private function notificar(int $usuarioId, string $tipo, string $mensaje, int $solicitudId): void
    {
        db_connect()->table('notificaciones')->insert([
            'usuario_id'      => $usuarioId,
            'tipo'            => $tipo,
            'mensaje'         => $mensaje,
            'referencia_tipo' => 'solicitud_asesoria',
            'referencia_id'   => $solicitudId,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    // Chat: por especialidad de sector + toggle 'disponible'. Video: solo quienes marcaron ESE
    // horario exacto como disponible (docs §4 Fase 2).
    private function asesoresPorSector(?int $sectorId): array
    {
        if ($sectorId === null) {
            return [];
        }

        $filas = db_connect()->table('usuarios u')
            ->select('u.id')
            ->join('asesor_especialidades ae', 'ae.usuario_id = u.id')
            ->where('u.rol', 'asesor')
            ->where('u.estado', 'activo')
            ->where('u.disponible', 1)
            ->where('ae.sector_id', $sectorId)
            ->get()->getResultArray();

        return array_map(static fn (array $f) => (int) $f['id'], $filas);
    }

    private function asesoresPorHorario(?string $fecha, ?string $horaInicio, ?string $horaFin): array
    {
        if ($fecha === null || $horaInicio === null || $horaFin === null) {
            return [];
        }

        $diaSemana = (int) date('N', strtotime($fecha)); // 1=lunes..7=domingo, igual que horarios_docente

        $filas = db_connect()->table('usuarios u')
            ->select('u.id')
            ->join('horarios_docente hd', 'hd.usuario_id = u.id')
            ->where('u.rol', 'asesor')
            ->where('u.estado', 'activo')
            ->where('u.disponible', 1)
            ->where('hd.dia_semana', $diaSemana)
            ->where('hd.hora_inicio', $horaInicio)
            ->where('hd.hora_fin', $horaFin)
            ->get()->getResultArray();

        return array_map(static fn (array $f) => (int) $f['id'], $filas);
    }

    private function calcularSlaVenceEn(string $tipo): string
    {
        $config   = db_connect()->table('configuracion_sla')->get()->getRowArray();
        $horas    = (int) ($config['tiempo_espera_chat_horas'] ?? 24);
        $minutos  = (int) ($config['tiempo_aceptacion_video_minutos'] ?? 20);
        $segundos = $tipo === 'video' ? $minutos * 60 : $horas * 3600;

        return date('Y-m-d H:i:s', time() + $segundos);
    }

    private function conSegundos(string $horaHM): string
    {
        return strlen($horaHM) === 5 ? "{$horaHM}:00" : $horaHM;
    }

    // Módulo 7 (bloqueado hasta tener credenciales reales de Zoom/Google Meet, docs §4 Fase 3):
    // simulación explícita a pedido del usuario — genera un link con apariencia de sala de reunión
    // bajo el propio dominio del producto (nunca un dominio de Zoom/Google, para no aparentar ser
    // una integración real) en vez de pedirle al asesor que pegue uno a mano. Reemplazar este
    // método por la llamada real a la API el día que existan las credenciales.
    private function generarLinkSimulado(int $solicitudId): string
    {
        $codigo = strtolower(bin2hex(random_bytes(4)));

        return "https://proyectafacil.app/reunion/{$solicitudId}-{$codigo}";
    }

    // Libera el ticket de consulta reservado de vuelta a 'disponible' (docs §3.1: Reservado →
    // Disponible al cancelar). Usado tanto por la cancelación propia del alumno como por la del
    // Administrativo (Módulo 4).
    private function liberarTicket(int $solicitudId): void
    {
        db_connect()->table('tickets_consulta')
            ->where('solicitud_asesoria_id', $solicitudId)
            ->where('estado', 'reservado')
            ->update(['estado' => 'disponible', 'solicitud_asesoria_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    // Ticket de asesoría → Completado ⇒ Ticket de consulta vinculado → Consumido (docs §4 Fase 5
    // punto 1). Reservado, nunca cualquier otro estado, para no "consumir" uno ya liberado/cancelado.
    private function consumirTicket(int $solicitudId): void
    {
        db_connect()->table('tickets_consulta')
            ->where('solicitud_asesoria_id', $solicitudId)
            ->where('estado', 'reservado')
            ->update(['estado' => 'consumido', 'updated_at' => date('Y-m-d H:i:s')]);
    }

    private function toDtoSolicitud(array $s): array
    {
        return [
            'id'             => (string) $s['id'],
            'clienteId'      => (string) $s['cliente_id'],
            'clienteNombre'  => $s['cliente_nombre'] ?? null,
            'clienteFotoUrl' => $s['cliente_foto_url'] ?? null,
            'docenteId'      => $s['docente_id'] !== null ? (string) $s['docente_id'] : null,
            'docenteNombre'  => $s['docente_nombre'] ?? null,
            'docenteFotoUrl' => $s['docente_foto_url'] ?? null,
            'ejemploId'      => $s['ejemplo_id'] !== null ? (string) $s['ejemplo_id'] : null,
            'sectorId'       => $s['sector_id'] !== null ? (string) $s['sector_id'] : null,
            'sectorNombre'   => $s['sector_nombre'] ?? null,
            'tipoDocumento'  => $s['tipo_documento'] ?? null,
            'tipo'           => $s['tipo'],
            'estado'         => $s['estado'],
            'mensajeInicial' => $s['mensaje_inicial'],
            'horarioFecha'      => $s['horario_fecha'] ?? null,
            'horarioHoraInicio' => $s['horario_hora_inicio'] !== null ? substr((string) $s['horario_hora_inicio'], 0, 5) : null,
            'horarioHoraFin'    => $s['horario_hora_fin'] !== null ? substr((string) $s['horario_hora_fin'], 0, 5) : null,
            'slaVenceEn'     => $s['sla_vence_en'] !== null ? $this->datetimeAIso($s['sla_vence_en']) : null,
            'linkReunion'    => $s['link_reunion'],
            'calificacion'   => $s['calificacion'] !== null ? (int) $s['calificacion'] : null,
            'calificacionComentario' => $s['calificacion_comentario'] ?? null,
            'creadoEn'       => $this->datetimeAIso($s['created_at']),
            'actualizadoEn'  => $s['updated_at'] !== null ? $this->datetimeAIso($s['updated_at']) : null,
        ];
    }

    // Las columnas DATETIME se guardan en UTC (hora del servidor) pero MySQL/Postgres las
    // devuelven sin indicador de zona ("2026-07-29 06:13:03") — sin el sufijo 'Z', `new Date(...)`
    // en el navegador las interpreta como hora LOCAL en vez de UTC, desfasando cualquier cálculo
    // (countdown de SLA, "hace X minutos") por el offset de zona horaria del usuario.
    private function datetimeAIso(string $valor): string
    {
        return str_replace(' ', 'T', $valor) . 'Z';
    }
}
