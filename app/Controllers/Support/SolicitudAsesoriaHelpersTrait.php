<?php

namespace App\Controllers\Support;

use App\Libraries\GoogleMeetService;
use App\Libraries\HorarioRecurrencia;
use App\Models\ActividadModel;
use DateTime;
use DateTimeZone;
use Throwable;

// Compartido entre AsesoriaController (lado alumno/asesor) y TicketsAsesoriaController (lado
// Administrativo de Asesorías, Módulo 4) — el DTO de una solicitud, el broadcast a asesores
// elegibles y notificaciones son idénticos desde ambos lados, solo cambia quién los invoca.
trait SolicitudAsesoriaHelpersTrait
{
    private function fila(int $id): ?array
    {
        return db_connect()->table('solicitudes_asesoria')->where('id', $id)->get()->getRowArray();
    }

    // Correos reales del cliente y el asesor para invitarlos al evento de Calendar/Meet (ver
    // GoogleMeetService) — no todos los usuarios tienen `correo` cargado (ej. varios docentes de
    // seed), así que se filtran los nulos en vez de fallar: esa persona simplemente entra a Meet
    // "pidiendo unirse" en vez de directo.
    private function correosParaInvitar(int $clienteId, int $asesorId): array
    {
        $filas = db_connect()->table('usuarios')
            ->select('correo')
            ->whereIn('id', [$clienteId, $asesorId])
            ->get()->getResultArray();

        return array_values(array_filter(array_map(static fn (array $u) => $u['correo'] ?? null, $filas)));
    }

    // Correo del asesor solo (independiente de correosParaInvitar) porque GoogleMeetService lo
    // necesita aparte, como coHostEmail — no se puede distinguir cuál es el asesor dentro del
    // array combinado de arriba.
    private function correoAsesor(int $asesorId): ?string
    {
        return db_connect()->table('usuarios')->select('correo')->where('id', $asesorId)->get()->getRowArray()['correo'] ?? null;
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

        // Por contención, no igualdad exacta: el cliente elige una cápsula de 1 hora (ver
        // SolicitarAsesoriaModal.vue) que puede ser un sub-rango de un bloque más largo que el
        // docente marcó como disponible (ej. docente disponible 09:00-12:00, cliente elige la
        // cápsula 10:00-11:00) — con igualdad exacta ningún docente calzaría nunca.
        $filas = db_connect()->table('usuarios u')
            ->select('u.id, hd.fecha_inicio, hd.tipo_repeticion')
            ->join('horarios_docente hd', 'hd.usuario_id = u.id')
            ->where('u.rol', 'asesor')
            ->where('u.estado', 'activo')
            ->where('u.disponible', 1)
            ->where('hd.hora_inicio <=', $horaInicio)
            ->where('hd.hora_fin >=', $horaFin)
            ->get()->getResultArray();

        $filas = array_filter($filas, static fn (array $f) => HorarioRecurrencia::ocurreEnFecha($f, $fecha));

        return array_values(array_unique(array_map(static fn (array $f) => (int) $f['id'], $filas)));
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
            'linkGrabacion'  => $s['link_grabacion'] ?? null,
            'resumenIaTexto' => $s['resumen_ia_texto'] ?? null,
            'calificacion'   => $s['calificacion'] !== null ? (int) $s['calificacion'] : null,
            'calificacionComentario' => $s['calificacion_comentario'] ?? null,
            'creadoEn'       => $this->datetimeAIso($s['created_at']),
            'actualizadoEn'  => $s['updated_at'] !== null ? $this->datetimeAIso($s['updated_at']) : null,
            'completadoEn'   => ($s['completado_en'] ?? null) !== null ? $this->datetimeAIso($s['completado_en']) : null,
        ];
    }

    // Mismo margen que MARGEN_SALIDA_MIN en frontend/src/lib/consultaAsesorUI.ts — mientras la
    // ventana de "puede seguir conectado" sigue abierta, no tiene sentido evaluar asistencia
    // todavía (la llamada bien podría seguir en curso).
    private const MARGEN_SALIDA_MIN = 10;

    // Resuelve automáticamente si una solicitud de video 'agendada' pasa a Completado/Observado/
    // Vencido, según la asistencia real registrada en Meet. Evaluación "bajo demanda": se llama
    // desde los mismos endpoints de listado/detalle que ya se consultan normalmente — la primera
    // vez que alguien pide esta solicitud después de que terminó su horario + margen de salida,
    // se calcula y se guarda; después de eso el estado ya cambió y no se vuelve a evaluar. No hace
    // nada (devuelve la fila tal cual) si no aplica o si no se pudo verificar todavía.
    //
    // Criterio puramente de duración (pedido explícito del usuario, sin tolerancia sobre los
    // bordes del horario): se compara el total de tiempo en que AMBOS estuvieron conectados a la
    // vez contra la duración pactada (fin - inicio acordado).
    // - Completado: el tiempo conectado en simultáneo alcanzó o superó la duración pactada — no
    //   importa si se pasaron, "si se pasa no hay problemas".
    // - Observado: hubo algo de tiempo simultáneo real, pero fue menor a la duración pactada
    //   (aunque sea por 1 minuto).
    // - Vencido: nunca coincidieron conectados al mismo tiempo (incluye que ninguno haya entrado).
    private function resolverAsistenciaSiCorresponde(array $solicitud): array
    {
        $vencimiento = $this->vencimientoAcordado($solicitud);
        if ($vencimiento === null) {
            return $solicitud;
        }
        [$inicioAcordado, $finAcordado] = $vencimiento;

        if (time() < $finAcordado + self::MARGEN_SALIDA_MIN * 60) {
            return $solicitud; // todavía puede estar en curso (dentro del margen de salida)
        }

        return $this->evaluarAsistenciaReal($solicitud, $inicioAcordado, $finAcordado);
    }

    // Cierre manual explícito (botón "Completar" del asesor, ver AsesoriaController::completarVideo)
    // — "doble seguridad" pedida por el usuario: el asesor puede cerrar apenas termina el horario
    // acordado, sin esperar el margen de salida completo; si no lo hace, resolverAsistenciaSiCorresponde
    // (arriba) igual lo resuelve sola una vez pasado ese margen.
    private function resolverAsistenciaManual(array $solicitud): array
    {
        $vencimiento = $this->vencimientoAcordado($solicitud);
        if ($vencimiento === null) {
            return $solicitud;
        }
        [$inicioAcordado, $finAcordado] = $vencimiento;

        if (time() < $finAcordado) {
            return $solicitud; // el horario acordado todavía no termina
        }

        return $this->evaluarAsistenciaReal($solicitud, $inicioAcordado, $finAcordado);
    }

    /** @return array{0: int, 1: int}|null [inicioAcordado, finAcordado] en timestamp, o null si no aplica evaluar. */
    private function vencimientoAcordado(array $solicitud): ?array
    {
        if ($solicitud['tipo'] !== 'video' || $solicitud['estado'] !== 'agendado') {
            return null;
        }
        if (empty($solicitud['horario_fecha']) || empty($solicitud['horario_hora_inicio']) || empty($solicitud['horario_hora_fin'])) {
            return null;
        }

        $zonaLima = new DateTimeZone('America/Lima');
        $inicioAcordado = (new DateTime("{$solicitud['horario_fecha']} {$solicitud['horario_hora_inicio']}", $zonaLima))->getTimestamp();
        $finAcordado    = (new DateTime("{$solicitud['horario_fecha']} {$solicitud['horario_hora_fin']}", $zonaLima))->getTimestamp();

        return [$inicioAcordado, $finAcordado];
    }

    private function evaluarAsistenciaReal(array $solicitud, int $inicioAcordado, int $finAcordado): array
    {
        if (empty($solicitud['link_reunion']) || ! preg_match('#meet\.google\.com/([a-z]+-[a-z]+-[a-z]+)#', (string) $solicitud['link_reunion'], $m)) {
            // Nunca tuvo un link real de Meet (dato viejo/simulado) — no hay forma de verificar
            // asistencia, se deja tal cual en vez de asumir "vencido" sin evidencia real.
            return $solicitud;
        }

        $db      = db_connect();
        $cliente = $db->table('usuarios')->select('nombre')->where('id', $solicitud['cliente_id'])->get()->getRowArray();
        $asesor  = $solicitud['docente_id'] !== null ? $db->table('usuarios')->select('nombre')->where('id', $solicitud['docente_id'])->get()->getRowArray() : null;
        if (! $cliente || ! $asesor) {
            return $solicitud;
        }

        try {
            $porNombre = (new GoogleMeetService())->historialConexion($m[1]);
        } catch (Throwable $e) {
            log_message('error', 'resolverAsistenciaSiCorresponde: Meet API falló para la solicitud {id}: {msg}', ['id' => $solicitud['id'], 'msg' => $e->getMessage()]);

            return $solicitud; // no se pudo verificar — se reintentará la próxima vez que alguien lo consulte
        }

        $normalizar      = static fn (string $s) => mb_strtolower(trim($s));
        $sesionesCliente = [];
        $sesionesAsesor  = [];
        foreach ($porNombre as $p) {
            if ($normalizar($p['nombre']) === $normalizar($cliente['nombre'])) {
                $sesionesCliente = array_merge($sesionesCliente, $p['sesiones']);
            } elseif ($normalizar($p['nombre']) === $normalizar($asesor['nombre'])) {
                $sesionesAsesor = array_merge($sesionesAsesor, $p['sesiones']);
            }
        }

        if ($sesionesCliente === [] || $sesionesAsesor === []) {
            // El nombre de la cuenta real de Google que entró no calzó con "nombre" del cliente o
            // del asesor en la plataforma — pasa seguido (apodos, cuenta personal distinta al
            // nombre registrado, o alguien probando con su propia cuenta). Google solo conoce el
            // nombre de la cuenta que entró, no su rol dentro de esta app. En vez de asumir "nadie
            // llegó" solo porque el nombre no calzó, se cae a comparar a las dos personas reales
            // que sí se conectaron — lo que de verdad define "Completado" es que dos personas
            // distintas coincidieron conectadas en la franja acordada, no cuál nombre tenía cada una.
            [$sesionesCliente, $sesionesAsesor] = $this->dosParticipantesConMasSuperposicion($porNombre);
        }

        $superposicion      = $this->calcularSuperposicion($sesionesCliente, $sesionesAsesor);
        $duracionPactadaSeg = $finAcordado - $inicioAcordado;

        if ($superposicion['segundos'] === 0) {
            $nuevoEstado = 'vencido';
        } elseif ($superposicion['segundos'] >= $duracionPactadaSeg) {
            $nuevoEstado = 'completado';
        } else {
            $nuevoEstado = 'observado';
        }

        $ahora   = date('Y-m-d H:i:s');
        $cambios = ['estado' => $nuevoEstado, 'updated_at' => $ahora];
        if ($nuevoEstado === 'completado') {
            $cambios['completado_en'] = $ahora;
        }
        $db->table('solicitudes_asesoria')->where('id', $solicitud['id'])->update($cambios);

        if ($nuevoEstado === 'completado') {
            $this->consumirTicket((int) $solicitud['id']);
            $this->notificar((int) $solicitud['cliente_id'], 'solicitud_completada', 'Tu asesoría fue marcada como completada — cuéntanos cómo estuvo', (int) $solicitud['id']);
            // Resuelto "bajo demanda" (ver comentario de la clase) — puede correr en la request de
            // cualquiera que consulte la lista después (cliente, docente o el admin de Módulo 4), así
            // que el actor se toma de la propia solicitud, nunca de session().
            (new ActividadModel())->insert([
                'mensaje'    => 'Asistió a una sesión de asesoría',
                'color'      => 'green',
                'categoria'  => 'Sesiones',
                'actor_id'   => (int) $solicitud['cliente_id'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Observada o vencida: la asesoría no ocurrió como debía — se libera el ticket de
            // consulta para que el alumno pueda volver a intentarlo, igual que en una cancelación.
            $this->liberarTicket((int) $solicitud['id']);
            $mensaje = $nuevoEstado === 'observado'
                ? 'Tu videollamada quedó marcada como observada — el tiempo conectado en simultáneo no alcanzó la duración acordada.'
                : 'Nadie ingresó a tu videollamada agendada.';
            $this->notificar((int) $solicitud['cliente_id'], 'solicitud_' . $nuevoEstado, $mensaje, (int) $solicitud['id']);
        }

        return array_merge($solicitud, $cambios);
    }

    /**
     * Fallback de evaluarAsistenciaReal() cuando ningún nombre de Google calzó con el cliente o el
     * asesor de la app. Prueba todas las combinaciones de a pares entre los participantes reales y
     * devuelve el par con mayor superposición real — con un link privado de solo 2 invitados, lo
     * normal es que haya como mucho 2 participantes distintos, así que esto en la práctica es
     * "compara a los dos que entraron". Sin al menos 2 participantes distintos no hay par que
     * comparar (nadie más entró aparte de, como mucho, una persona).
     *
     * @param array<int, array{nombre: string, sesiones: array}> $porNombre
     * @return array{0: array, 1: array}
     */
    private function dosParticipantesConMasSuperposicion(array $porNombre): array
    {
        if (count($porNombre) < 2) {
            return [[], []];
        }

        $mejorSegundos  = -1;
        $mejorA         = [];
        $mejorB         = [];
        foreach ($porNombre as $i => $a) {
            foreach ($porNombre as $j => $b) {
                if ($j <= $i) {
                    continue;
                }
                $sup = $this->calcularSuperposicion($a['sesiones'], $b['sesiones']);
                if ($sup['segundos'] > $mejorSegundos) {
                    $mejorSegundos = $sup['segundos'];
                    $mejorA        = $a['sesiones'];
                    $mejorB        = $b['sesiones'];
                }
            }
        }

        return [$mejorA, $mejorB];
    }

    // Aplana dos listas de sesiones {entrada, salida} (ISO, pueden venir en cualquier orden y con
    // huecos) y calcula: segundos totales en que AMBAS listas tuvieron una sesión activa al mismo
    // tiempo, más el inicio de la primera superposición y el fin de la última — usado para la
    // tolerancia de ±5 min contra el horario acordado. Sesiones sin `salida` (llamada que quedó
    // activa / dato incompleto) se ignoran, igual que sesiones sin `entrada`.
    private function calcularSuperposicion(array $sesionesA, array $sesionesB): array
    {
        $segundosTotales = 0;
        $inicioMin       = null;
        $finMax          = null;

        foreach ($sesionesA as $a) {
            if (empty($a['entrada']) || empty($a['salida'])) {
                continue;
            }
            $aIni = strtotime($a['entrada']);
            $aFin = strtotime($a['salida']);

            foreach ($sesionesB as $b) {
                if (empty($b['entrada']) || empty($b['salida'])) {
                    continue;
                }
                $ini = max($aIni, strtotime($b['entrada']));
                $fin = min($aFin, strtotime($b['salida']));
                if ($fin <= $ini) {
                    continue;
                }

                $segundosTotales += $fin - $ini;
                $inicioMin        = $inicioMin === null ? $ini : min($inicioMin, $ini);
                $finMax           = $finMax === null ? $fin : max($finMax, $fin);
            }
        }

        return ['segundos' => $segundosTotales, 'inicio' => $inicioMin, 'fin' => $finMax];
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
