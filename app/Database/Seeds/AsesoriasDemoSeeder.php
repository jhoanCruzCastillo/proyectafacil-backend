<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Datos de demostración para el Módulo de Asesorías (pedido explícito del usuario): fotos de
// perfil vía DiceBear para todos los usuarios existentes + nuevos alumnos/docentes con horarios,
// especialidades, tickets de consulta y un lote de solicitudes de asesoría en distintos estados,
// para que el dashboard/Cobertura de horarios/Liquidaciones/Tickets no se vean vacíos en una demo.
// Idempotente: las personas se insertan por `usuario` (check-then-insert), las fotos solo se
// completan donde faltan, y el lote de solicitudes se salta por completo si ya se sembró antes
// (se detecta por la presencia de solicitudes del primer alumno nuevo).
class AsesoriasDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->backfillFotos();

        $sectorId = $this->sectorIdsPorCodigo();
        $planId   = $this->planIdsPorNivel();

        $alumnos  = $this->crearAlumnosDemo();
        $asesores = $this->crearAsesoresDemo($sectorId);

        foreach ($alumnos as $slug => $a) {
            $this->asegurarFacturacion($a['id'], $planId[$a['nivel']]);
        }

        $loteYaExiste = $this->db->table('solicitudes_asesoria')
            ->where('cliente_id', $alumnos['rosa']['id'])
            ->countAllResults() > 0;

        if (! $loteYaExiste) {
            $this->crearSolicitudesDemo($alumnos, $asesores, $sectorId);
        }
    }

    // DiceBear (api.dicebear.com) — gratuita, sin API key, avatares ilustrados (no fotos de
    // personas reales) para no aparentar representar a alguien real. Seed = `usuario` para que el
    // avatar de cada quien sea estable entre corridas.
    private function backfillFotos(): void
    {
        $filas = $this->db->table('usuarios')->select('id, usuario')->where('foto_url', null)->get()->getResultArray();

        foreach ($filas as $f) {
            $this->db->table('usuarios')->where('id', $f['id'])->update([
                'foto_url' => 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($f['usuario']),
            ]);
        }
    }

    private function sectorIdsPorCodigo(): array
    {
        $filas = $this->db->table('sectores')->select('id, codigo')->get()->getResultArray();
        $porCodigo = [];
        foreach ($filas as $f) {
            $porCodigo[$f['codigo']] = (int) $f['id'];
        }

        return $porCodigo;
    }

    private function planIdsPorNivel(): array
    {
        $filas = $this->db->table('planes')->select('id, numero_nivel')->get()->getResultArray();
        $porNivel = [];
        foreach ($filas as $f) {
            $porNivel[(int) $f['numero_nivel']] = (int) $f['id'];
        }

        return $porNivel;
    }

    private function upsertUsuario(array $u): int
    {
        $existente = $this->db->table('usuarios')->where('usuario', $u['usuario'])->get()->getRowArray();
        if ($existente !== null) {
            return (int) $existente['id'];
        }

        $ahora = date('Y-m-d H:i:s');
        $this->db->table('usuarios')->insert([
            'nombre'        => $u['nombre'],
            'usuario'       => $u['usuario'],
            'password_hash' => password_hash($u['password'], PASSWORD_DEFAULT),
            'rol'           => $u['rol'],
            'origen'        => $u['origen'] ?? null,
            'correo'        => $u['correo'] ?? null,
            'estado'        => 'activo',
            'disponible'    => $u['disponible'] ?? 1,
            'foto_url'      => 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($u['usuario']),
            'created_at'    => $ahora,
            'updated_at'    => $ahora,
        ]);

        return (int) $this->db->insertID();
    }

    private function crearAlumnosDemo(): array
    {
        $def = [
            'rosa'  => ['nombre' => 'Rosa Delgado', 'usuario' => 'alumno.rosa', 'password' => 'Alumno#2026', 'rol' => 'cliente', 'origen' => 'alumno', 'correo' => 'rosa.delgado@example.com', 'nivel' => 1],
            'mateo' => ['nombre' => 'Mateo Vargas', 'usuario' => 'alumno.mateo', 'password' => 'Alumno#2026', 'rol' => 'cliente', 'origen' => 'alumno', 'correo' => 'mateo.vargas@example.com', 'nivel' => 2],
            'lucia' => ['nombre' => 'Lucía Fernández', 'usuario' => 'cliente.lucia', 'password' => 'Cliente#2026', 'rol' => 'cliente', 'origen' => 'externo', 'correo' => 'lucia.fernandez@example.com', 'nivel' => 1],
            'diego' => ['nombre' => 'Diego Torres', 'usuario' => 'cliente.diego', 'password' => 'Cliente#2026', 'rol' => 'cliente', 'origen' => null, 'correo' => 'diego.torres@example.com', 'nivel' => 0],
        ];

        // Juan (cliente) y Ana (cliente2) ya existen desde UsuariosSeeder — se agregan acá solo para
        // que participen del lote de solicitudes y de la asignación de plan/tickets con el resto.
        $juan = $this->db->table('usuarios')->where('usuario', 'cliente')->get()->getRowArray();
        $ana  = $this->db->table('usuarios')->where('usuario', 'cliente2')->get()->getRowArray();

        $alumnos = [];
        foreach ($def as $slug => $u) {
            $alumnos[$slug] = ['id' => $this->upsertUsuario($u), 'nivel' => $u['nivel']];
        }
        $alumnos['juan'] = ['id' => (int) $juan['id'], 'nivel' => 1];
        $alumnos['ana']  = ['id' => (int) $ana['id'], 'nivel' => 2];

        return $alumnos;
    }

    private function crearAsesoresDemo(array $sectorId): array
    {
        $def = [
            'elena' => [
                'nombre' => 'Elena Castro', 'usuario' => 'asesor3', 'password' => 'Asesor#2026', 'rol' => 'asesor',
                'correo' => 'elena.castro@example.com', 'disponible' => 1,
                'especialidades' => ['AGR', 'DIS'],
                'horario' => [[1, '09:00', '12:00'], [3, '09:00', '12:00'], [5, '14:00', '17:00']],
            ],
            'jorge' => [
                'nombre' => 'Jorge Paredes', 'usuario' => 'asesor4', 'password' => 'Asesor#2026', 'rol' => 'asesor',
                'correo' => 'jorge.paredes@example.com', 'disponible' => 0,
                'especialidades' => ['TYC', 'VYS'],
                'horario' => [[2, '10:00', '13:00'], [4, '10:00', '13:00']],
            ],
            'sofia' => [
                'nombre' => 'Sofía Ramírez', 'usuario' => 'asesor5', 'password' => 'Asesor#2026', 'rol' => 'asesor',
                'correo' => 'sofia.ramirez@example.com', 'disponible' => 1,
                'especialidades' => ['AMB', 'SAL'],
                'horario' => [[1, '14:00', '17:00'], [3, '14:00', '17:00'], [5, '09:00', '12:00']],
            ],
        ];

        $asesores = [];
        foreach ($def as $slug => $a) {
            $id = $this->upsertUsuario($a);
            $asesores[$slug] = ['id' => $id];

            $tieneEspecialidades = $this->db->table('asesor_especialidades')->where('usuario_id', $id)->countAllResults() > 0;
            if (! $tieneEspecialidades) {
                foreach ($a['especialidades'] as $codigo) {
                    $this->db->table('asesor_especialidades')->ignore(true)->insert([
                        'usuario_id' => $id,
                        'sector_id'  => $sectorId[$codigo],
                    ]);
                }
            }

            $tieneHorario = $this->db->table('horarios_docente')->where('usuario_id', $id)->countAllResults() > 0;
            if (! $tieneHorario) {
                foreach ($a['horario'] as [$dia, $inicio, $fin]) {
                    $this->db->table('horarios_docente')->insert([
                        'usuario_id'      => $id,
                        'fecha_inicio'    => $this->fechaRecienteConDiaSemana($dia),
                        'hora_inicio'     => "{$inicio}:00",
                        'hora_fin'        => "{$fin}:00",
                        'todo_el_dia'     => 0,
                        'tipo_repeticion' => 'semanal',
                        'created_at'      => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        return $asesores;
    }

    /** Fecha más reciente <= hoy cuyo día de semana (1=lunes..7=domingo) coincide con $diaSemana. */
    private function fechaRecienteConDiaSemana(int $diaSemana): string
    {
        $hoy   = new \DateTimeImmutable('today');
        $jsHoy = (int) $hoy->format('w');
        $hoyDia = $jsHoy === 0 ? 7 : $jsHoy;
        $atras  = ($hoyDia - $diaSemana + 7) % 7;

        return $hoy->modify("-{$atras} days")->format('Y-m-d');
    }

    // Réplica de FacturacionController::crearDefault() + TicketsConsultaController::emitirTicketsDePlan()
    // — no se llama a los controllers desde un seeder (regla del proyecto: cambios de BD solo por
    // seeders, sin depender de HTTP), así que la lógica de emisión de tickets se repite acá.
    private function asegurarFacturacion(int $usuarioId, int $planId): void
    {
        $tiene = $this->db->table('facturaciones')->where('usuario_id', $usuarioId)->countAllResults() > 0;
        if (! $tiene) {
            $this->db->table('facturaciones')->insert([
                'usuario_id'        => $usuarioId,
                'plan_id'           => $planId,
                'cancelada'         => 0,
                'fecha_renovacion'  => date('Y-m-d', strtotime('+1 month')),
                'fecha_inicio_plan' => date('Y-m-d H:i:s'),
                'metodo_pago'       => 'tarjeta',
                'tarjeta_marca'     => 'Visa',
                'tarjeta_ultimos4'  => '4242',
            ]);
        }

        $yaTieneTickets = $this->db->table('tickets_consulta')
            ->where('usuario_id', $usuarioId)->where('origen', 'plan')->countAllResults() > 0;
        if ($yaTieneTickets) {
            return;
        }

        $cupo      = (int) ($this->db->table('planes')->where('id', $planId)->get()->getRow('limite_consultas_base') ?? 0);
        $cupoChat  = (int) ceil($cupo / 2);
        $cupoVideo = $cupo - $cupoChat;
        $ahora     = date('Y-m-d H:i:s');

        foreach ([['chat', 30, $cupoChat], ['video', 45, $cupoVideo]] as [$modalidad, $duracion, $cantidad]) {
            for ($i = 0; $i < $cantidad; $i++) {
                $this->db->table('tickets_consulta')->insert([
                    'usuario_id'       => $usuarioId,
                    'origen'           => 'plan',
                    'estado'           => 'disponible',
                    'modalidad'        => $modalidad,
                    'duracion_minutos' => $duracion,
                    'created_at'       => $ahora,
                    'updated_at'       => $ahora,
                ]);
            }
        }
    }

    // Prioridad plan > add-on, más antiguo primero, filtrado por modalidad — mismo criterio que
    // AsesoriaController::reservarTicket(), repetido acá por la misma razón que asegurarFacturacion().
    private function reservarTicket(int $usuarioId, string $modalidad): ?array
    {
        $comun = fn ($q) => $q->where('usuario_id', $usuarioId)->where('estado', 'disponible')->where('modalidad', $modalidad);

        $ticket = $comun($this->db->table('tickets_consulta')->where('origen', 'plan'))->orderBy('id', 'ASC')->get()->getRowArray();

        return $ticket ?? $comun($this->db->table('tickets_consulta')->where('origen', 'addon'))->orderBy('id', 'ASC')->get()->getRowArray();
    }

    private function crearSolicitudesDemo(array $alumnos, array $asesores, array $sectorId): void
    {
        $pedro = $this->db->table('usuarios')->where('usuario', 'asesor1')->get()->getRow('id');
        $laura = $this->db->table('usuarios')->where('usuario', 'asesor2')->get()->getRow('id');

        $proximoLunes = date('Y-m-d', strtotime('next monday'));
        $haceDias     = static fn (int $dias, int $horas = 0) => date('Y-m-d H:i:s', strtotime("-{$dias} days -{$horas} hours"));

        $solicitudes = [
            // pendiente sin asignar (chat) — dispara broadcast por especialidad
            [
                'clienteSlug' => 'rosa', 'tipo' => 'chat', 'estado' => 'pendiente', 'docenteId' => null,
                'sector' => 'EDU', 'tipoDocumento' => 'ficha_tecnica', 'mensaje' => 'Necesito ayuda con la brecha de servicio de mi PI de educación.',
                'creadoEn' => $haceDias(0, 2), 'sla' => date('Y-m-d H:i:s', strtotime('+22 hours')),
                'notificarA' => ['elena'], 'ticketEstadoFinal' => 'reservado',
            ],
            // pendiente sin asignar (video) — horario calzado con el de Sofía para probar cobertura
            [
                'clienteSlug' => 'mateo', 'tipo' => 'video', 'estado' => 'pendiente', 'docenteId' => null,
                'sector' => 'SAL', 'tipoDocumento' => 'ficha_tecnica', 'mensaje' => 'Quisiera una videollamada para revisar el análisis técnico.',
                'horarioFecha' => $proximoLunes, 'horarioInicio' => '14:00:00', 'horarioFin' => '17:00:00',
                'creadoEn' => $haceDias(0, 0), 'sla' => date('Y-m-d H:i:s', strtotime('+18 minutes')),
                'notificarA' => ['sofia'], 'ticketEstadoFinal' => 'reservado',
            ],
            // en_espera — sin cobertura, SLA ya vencido (para Cobertura de horarios / Tickets en espera)
            [
                'clienteSlug' => 'lucia', 'tipo' => 'chat', 'estado' => 'en_espera', 'docenteId' => null,
                'sector' => 'TYC', 'tipoDocumento' => 'ficha_tecnica', 'mensaje' => 'Duda sobre el análisis de alternativas de mi proyecto vial.',
                'creadoEn' => $haceDias(3), 'sla' => $haceDias(2),
                'notificarA' => [], 'ticketEstadoFinal' => 'reservado',
            ],
            // asignado (chat en curso)
            [
                'clienteSlug' => 'juan', 'tipo' => 'chat', 'estado' => 'asignado', 'docenteId' => (int) $pedro,
                'sector' => 'EDU', 'tipoDocumento' => 'ficha_tecnica', 'mensaje' => 'Ayuda con la formulación de objetivos.',
                'creadoEn' => $haceDias(5), 'sla' => null,
                'notificarA' => [], 'notificarAsesorId' => (int) $pedro, 'ticketEstadoFinal' => 'reservado',
            ],
            // agendado (video con link ya generado)
            [
                'clienteSlug' => 'ana', 'tipo' => 'video', 'estado' => 'agendado', 'docenteId' => (int) $laura,
                'sector' => 'SAL', 'tipoDocumento' => 'ficha_tecnica', 'mensaje' => 'Videollamada para revisar costos del proyecto.',
                'horarioFecha' => date('Y-m-d', strtotime('+2 days')), 'horarioInicio' => '10:00:00', 'horarioFin' => '10:30:00',
                'creadoEn' => $haceDias(4), 'sla' => null,
                'notificarA' => [], 'notificarAsesorId' => (int) $laura, 'ticketEstadoFinal' => 'reservado',
                'linkReunion' => true,
            ],
            // completado + calificado + pago autorizado
            [
                'clienteSlug' => 'juan', 'tipo' => 'chat', 'estado' => 'completado', 'docenteId' => (int) $pedro,
                'sector' => 'EDU', 'tipoDocumento' => 'ficha_tecnica', 'mensaje' => 'Revisión final de la ficha técnica.',
                'creadoEn' => $haceDias(20), 'actualizadoEn' => $haceDias(19), 'sla' => null,
                'notificarA' => [], 'notificarAsesorId' => (int) $pedro, 'ticketEstadoFinal' => 'consumido',
                'calificacion' => 5, 'comentario' => 'Excelente asesoría, muy claro explicando.', 'pagoAutorizado' => true,
            ],
            // completado, calificado, pago pendiente de autorizar
            [
                'clienteSlug' => 'diego', 'tipo' => 'video', 'estado' => 'completado', 'docenteId' => (int) $asesores['elena']['id'],
                'sector' => 'AGR', 'tipoDocumento' => 'ficha_tecnica', 'mensaje' => 'Videollamada sobre brecha de servicio agrícola.',
                'horarioFecha' => $haceDias(10), 'horarioInicio' => '09:00:00', 'horarioFin' => '09:30:00',
                'creadoEn' => $haceDias(10), 'actualizadoEn' => $haceDias(10), 'sla' => null,
                'notificarA' => [], 'notificarAsesorId' => (int) $asesores['elena']['id'], 'ticketEstadoFinal' => 'consumido',
                'calificacion' => 4, 'comentario' => null, 'pagoAutorizado' => false, 'linkReunion' => true,
            ],
            // completado, sin calificar todavía, pago pendiente
            [
                'clienteSlug' => 'ana', 'tipo' => 'chat', 'estado' => 'completado', 'docenteId' => (int) $laura,
                'sector' => 'VYS', 'tipoDocumento' => 'ficha_tecnica', 'mensaje' => 'Duda sobre el diagnóstico de la unidad productora.',
                'creadoEn' => $haceDias(6), 'actualizadoEn' => $haceDias(6), 'sla' => null,
                'notificarA' => [], 'notificarAsesorId' => (int) $laura, 'ticketEstadoFinal' => 'consumido',
                'calificacion' => null, 'comentario' => null, 'pagoAutorizado' => false,
            ],
            // cancelado por el alumno (ticket liberado de vuelta a disponible)
            [
                'clienteSlug' => 'rosa', 'tipo' => 'chat', 'estado' => 'cancelado', 'docenteId' => null,
                'sector' => 'INT', 'tipoDocumento' => 'ficha_tecnica', 'mensaje' => 'Consulta sobre seguridad ciudadana (cancelada por el alumno).',
                'creadoEn' => $haceDias(8), 'actualizadoEn' => $haceDias(8), 'sla' => $haceDias(7),
                'notificarA' => [], 'ticketEstadoFinal' => 'disponible',
            ],
            // completado, calificado y pagado
            [
                'clienteSlug' => 'mateo', 'tipo' => 'video', 'estado' => 'completado', 'docenteId' => (int) $asesores['sofia']['id'],
                'sector' => 'AMB', 'tipoDocumento' => 'ficha_tecnica', 'mensaje' => 'Videollamada sobre impacto ambiental del proyecto.',
                'horarioFecha' => $haceDias(15), 'horarioInicio' => '14:00:00', 'horarioFin' => '14:30:00',
                'creadoEn' => $haceDias(15), 'actualizadoEn' => $haceDias(14), 'sla' => null,
                'notificarA' => [], 'notificarAsesorId' => (int) $asesores['sofia']['id'], 'ticketEstadoFinal' => 'consumido',
                'calificacion' => 5, 'comentario' => 'Muy puntual y resolvió todas mis dudas.', 'pagoAutorizado' => true, 'linkReunion' => true,
            ],
        ];

        foreach ($solicitudes as $s) {
            $clienteId = $alumnos[$s['clienteSlug']]['id'];
            $ticket    = $this->reservarTicket($clienteId, $s['tipo']);

            $creadoEn     = $s['creadoEn'];
            $actualizadoEn = $s['actualizadoEn'] ?? $creadoEn;

            $this->db->table('solicitudes_asesoria')->insert([
                'cliente_id'          => $clienteId,
                'docente_id'          => $s['docenteId'],
                'ejemplo_id'          => null,
                'sector_id'           => $sectorId[$s['sector']],
                'tipo_documento'      => $s['tipoDocumento'],
                'tipo'                => $s['tipo'],
                'estado'              => $s['estado'],
                'mensaje_inicial'     => $s['mensaje'],
                'horario_fecha'       => $s['horarioFecha'] ?? null,
                'horario_hora_inicio' => $s['horarioInicio'] ?? null,
                'horario_hora_fin'    => $s['horarioFin'] ?? null,
                'sla_vence_en'        => $s['sla'],
                'link_reunion'        => ! empty($s['linkReunion']) ? $this->linkSimulado() : null,
                'calificacion'        => $s['calificacion'] ?? null,
                'calificacion_comentario' => $s['comentario'] ?? null,
                'pago_autorizado_en'  => ! empty($s['pagoAutorizado']) ? $actualizadoEn : null,
                'created_at'          => $creadoEn,
                'updated_at'          => $actualizadoEn,
            ]);
            $solicitudId = $this->db->insertID();

            if ($ticket !== null) {
                $this->db->table('tickets_consulta')->where('id', $ticket['id'])->update([
                    'estado'                => $s['ticketEstadoFinal'],
                    'solicitud_asesoria_id' => $s['ticketEstadoFinal'] === 'disponible' ? null : $solicitudId,
                    'updated_at'            => $actualizadoEn,
                ]);
            }

            foreach ($s['notificarA'] ?? [] as $slug) {
                $this->db->table('solicitud_notificaciones')->insert([
                    'solicitud_id' => $solicitudId,
                    'asesor_id'    => $asesores[$slug]['id'],
                    'created_at'   => $creadoEn,
                ]);
            }
            if (! empty($s['notificarAsesorId'])) {
                $this->db->table('solicitud_notificaciones')->insert([
                    'solicitud_id' => $solicitudId,
                    'asesor_id'    => $s['notificarAsesorId'],
                    'created_at'   => $creadoEn,
                ]);
            }
        }
    }

    // Mismo formato que SolicitudAsesoriaHelpersTrait::generarLinkSimulado() — dominio propio del
    // producto, nunca zoom.us/meet.google.com, para no aparentar una integración real.
    private function linkSimulado(): string
    {
        return 'https://proyectafacil.app/reunion/demo-' . strtolower(bin2hex(random_bytes(4)));
    }
}
