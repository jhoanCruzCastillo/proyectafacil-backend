<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Datos de prueba para la pantalla "No atendidas / reasignadas" del asesor demo (Pedro Ríos /
// asesor1). Cubre los cuatro escenarios que la pantalla distingue:
//   Tab 1 "No aceptadas a tiempo"  → tomada_por_otro / vencida_sin_respuesta / cancelada_por_alumno
//   Tab 2 "Agendadas no atendidas" → cita suya con hora ya pasada que nunca se cerró
//
// Idempotente: se salta por completo si ya se sembró antes (marcador en mensaje_inicial).
//
// Uso: php spark db:seed NoAtendidasDemoAsesor1Seeder
class NoAtendidasDemoAsesor1Seeder extends Seeder
{
    private const MARCADOR = '(demo no atendidas asesor1)';

    public function run(): void
    {
        $yaExiste = $this->db->table('solicitudes_asesoria')->like('mensaje_inicial', self::MARCADOR)->countAllResults() > 0;
        if ($yaExiste) {
            echo "Ya sembrado antes — no se hace nada.\n";

            return;
        }

        $pedro = $this->db->table('usuarios')->where('usuario', 'asesor1')->get()->getRowArray();
        if ($pedro === null) {
            echo "No existe el usuario 'asesor1' — corre UsuariosSeeder primero.\n";

            return;
        }
        $pedroId = (int) $pedro['id'];

        $otrosAsesores = $this->otrosAsesores($pedroId);
        $alumnos = $this->alumnosDisponibles();
        if ($otrosAsesores === [] || count($alumnos) < 4) {
            echo "Faltan otros asesores o alumnos demo — corre AsesoriasDemoSeeder primero.\n";

            return;
        }

        $sectorId = $this->sectorIdsPorCodigo();

        $solicitudes = [
            // --- Tab 1: se las ganó otro asesor ---
            [
                'alumno' => $alumnos[0], 'tipo' => 'chat', 'estado' => 'asignado',
                'docenteId' => $otrosAsesores[0], 'sector' => 'EDU',
                'mensaje' => 'Ayuda con el árbol de problemas ' . self::MARCADOR,
                'creadoEn' => '-2 days', 'sla' => '-2 days +4 hours',
            ],
            [
                'alumno' => $alumnos[1], 'tipo' => 'video', 'estado' => 'completado',
                'docenteId' => $otrosAsesores[count($otrosAsesores) > 1 ? 1 : 0], 'sector' => 'SAL',
                'mensaje' => 'Videollamada sobre la brecha de servicio ' . self::MARCADOR,
                'creadoEn' => '-5 days', 'sla' => '-5 days +2 hours',
                'horarioFecha' => date('Y-m-d', strtotime('-4 days')), 'horarioInicio' => '10:00:00', 'horarioFin' => '10:30:00',
                'linkReunion' => true,
            ],
            [
                'alumno' => $alumnos[2], 'tipo' => 'chat', 'estado' => 'completado',
                'docenteId' => $otrosAsesores[0], 'sector' => 'VYS',
                'mensaje' => 'Consulta sobre saneamiento rural ' . self::MARCADOR,
                'creadoEn' => '-8 days', 'sla' => '-8 days +3 hours',
            ],
            // --- Tab 1: venció sin que nadie respondiera ---
            [
                'alumno' => $alumnos[3], 'tipo' => 'chat', 'estado' => 'en_espera',
                'docenteId' => null, 'sector' => 'DIS',
                'mensaje' => 'Duda sobre el marco lógico ' . self::MARCADOR,
                'creadoEn' => '-3 days', 'sla' => '-2 days',
            ],
            [
                'alumno' => $alumnos[0], 'tipo' => 'video', 'estado' => 'pendiente',
                'docenteId' => null, 'sector' => 'EDU',
                'mensaje' => 'Videollamada para revisar costos del proyecto ' . self::MARCADOR,
                'creadoEn' => '-6 days', 'sla' => '-5 days',
                'horarioFecha' => date('Y-m-d', strtotime('-4 days')), 'horarioInicio' => '16:00:00', 'horarioFin' => '16:30:00',
            ],
            // --- Tab 1: el alumno la canceló mientras esperaba ---
            [
                'alumno' => $alumnos[1], 'tipo' => 'chat', 'estado' => 'cancelado',
                'docenteId' => null, 'sector' => 'SAL',
                'mensaje' => 'Consulta sobre equipamiento biomédico ' . self::MARCADOR,
                'creadoEn' => '-4 days', 'sla' => '-3 days',
            ],
            // --- Tab 2: eran suyas, con hora fijada, y la hora ya pasó sin cerrarse ---
            [
                'alumno' => $alumnos[2], 'tipo' => 'video', 'estado' => 'agendado',
                'docenteId' => $pedroId, 'sector' => 'VYS', 'notificar' => false,
                'mensaje' => 'Videollamada sobre PTAR ' . self::MARCADOR,
                'creadoEn' => '-10 days',
                'horarioFecha' => date('Y-m-d', strtotime('-7 days')), 'horarioInicio' => '09:00:00', 'horarioFin' => '09:30:00',
                'linkReunion' => true,
            ],
            [
                'alumno' => $alumnos[3], 'tipo' => 'video', 'estado' => 'agendado',
                'docenteId' => $pedroId, 'sector' => 'DIS', 'notificar' => false,
                'mensaje' => 'Videollamada sobre población beneficiaria ' . self::MARCADOR,
                'creadoEn' => '-6 days',
                'horarioFecha' => date('Y-m-d', strtotime('-3 days')), 'horarioInicio' => '17:00:00', 'horarioFin' => '17:30:00',
                'linkReunion' => true,
            ],
        ];

        foreach ($solicitudes as $s) {
            $creadoEn = date('Y-m-d H:i:s', strtotime($s['creadoEn']));

            $this->db->table('solicitudes_asesoria')->insert([
                'cliente_id'          => $s['alumno'],
                'docente_id'          => $s['docenteId'],
                'ejemplo_id'          => null,
                'sector_id'           => $sectorId[$s['sector']] ?? null,
                'tipo_documento'      => 'ficha_tecnica',
                'tipo'                => $s['tipo'],
                'estado'              => $s['estado'],
                'mensaje_inicial'     => $s['mensaje'],
                'horario_fecha'       => $s['horarioFecha'] ?? null,
                'horario_hora_inicio' => $s['horarioInicio'] ?? null,
                'horario_hora_fin'    => $s['horarioFin'] ?? null,
                'sla_vence_en'        => isset($s['sla']) ? date('Y-m-d H:i:s', strtotime($s['sla'])) : null,
                'link_reunion'        => ! empty($s['linkReunion']) ? $this->linkSimulado() : null,
                'created_at'          => $creadoEn,
                'updated_at'          => $creadoEn,
            ]);
            $solicitudId = $this->db->insertID();

            // Lo que hace que aparezcan en el Tab 1 es justamente que a Pedro SÍ le llegaron por
            // broadcast — sin esta fila la solicitud no es "suya perdida", es ajena.
            if ($s['notificar'] ?? true) {
                $this->db->table('solicitud_notificaciones')->insert([
                    'solicitud_id' => $solicitudId,
                    'asesor_id'    => $pedroId,
                    'created_at'   => $creadoEn,
                ]);
            }
        }

        echo "Listo — 8 solicitudes demo para 'No atendidas / reasignadas' de asesor1: 3 tomadas por otro, 2 vencidas, 1 cancelada, 2 agendadas no atendidas.\n";
    }

    private function otrosAsesores(int $exceptoId): array
    {
        $filas = $this->db->table('usuarios')
            ->select('id')
            ->where('rol', 'asesor')
            ->where('id !=', $exceptoId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return array_map(static fn (array $f) => (int) $f['id'], $filas);
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

    private function alumnosDisponibles(): array
    {
        $usuarios = ['cliente', 'cliente2', 'alumno.rosa', 'alumno.mateo', 'cliente.lucia', 'cliente.diego'];
        $ids = [];
        foreach ($usuarios as $u) {
            $fila = $this->db->table('usuarios')->where('usuario', $u)->get()->getRowArray();
            if ($fila !== null) {
                $ids[] = (int) $fila['id'];
            }
        }

        return $ids;
    }

    private function linkSimulado(): string
    {
        return 'https://proyectafacil.app/reunion/demo-' . strtolower(bin2hex(random_bytes(4)));
    }
}
