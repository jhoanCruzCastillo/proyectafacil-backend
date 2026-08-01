<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Pedido explícito del usuario: más variedad de estados para el asesor demo (Pedro Ríos / asesor1)
// en su pantalla "Mis consultas", para poder ver las 4 pestañas (Por Agendar / Agendadas /
// Reprogramadas / Atendidas) con datos reales — Pedro solo tenía 2 solicitudes sembradas por
// AsesoriasDemoSeeder (1 asignado, 1 completado), nada en estado "pendiente" ni "agendado".
//
// Complementa a AsesoriasDemoSeeder sin tocarlo — ese seeder ya corrió y no hay que re-ejecutarlo.
// Idempotente: se salta por completo si ya se sembró antes (detectado por un mensaje_inicial único).
//
// Uso: php spark db:seed AsesoriasDemoAsesor1Seeder
class AsesoriasDemoAsesor1Seeder extends Seeder
{
    private const MARCADOR = 'Necesito ayuda con el diagnóstico de la unidad productora (demo asesor1).';

    /** Sectores MEF que atiende el asesor demo — es lo que abre marcado en "Temas de especialidad". */
    private const SECTORES_PROPIOS = ['SAL', 'VYS', 'DIS'];

    public function run(): void
    {
        $pedro = $this->db->table('usuarios')->where('usuario', 'asesor1')->get()->getRowArray();
        if ($pedro === null) {
            echo "No existe el usuario 'asesor1' — corre UsuariosSeeder o UsuariosDemoProduccionSeeder primero.\n";

            return;
        }
        $pedroId = (int) $pedro['id'];

        $sectorId = $this->sectorIdsPorCodigo();

        // Va ANTES del corte de idempotencia: AsesoriasDemoSeeder solo le da especialidades a los
        // docentes demo (usuarios 12-14), no a asesor1, así que sin esto su pantalla "Temas de
        // especialidad" arranca vacía en una BD recién sembrada — y sin sectores tampoco hay
        // subtemas que marcarle después.
        $this->asignarEspecialidades($pedroId, $sectorId);

        $yaExiste = $this->db->table('solicitudes_asesoria')->where('mensaje_inicial', self::MARCADOR)->countAllResults() > 0;
        if ($yaExiste) {
            echo "Solicitudes ya sembradas antes — solo se verificaron las especialidades.\n";

            return;
        }

        $alumnos = $this->alumnosDisponibles();
        if (count($alumnos) < 4) {
            echo "No hay suficientes alumnos demo (cliente/cliente2/alumno.*) — corre AsesoriasDemoSeeder primero.\n";

            return;
        }

        $proximoLunes = date('Y-m-d', strtotime('next monday'));

        $solicitudes = [
            // Por Agendar (chat, sin asignar aún — notificado a Pedro entre otros)
            [
                'clienteId' => $alumnos[0], 'tipo' => 'chat', 'estado' => 'pendiente', 'docenteId' => null,
                'sector' => 'EDU', 'mensaje' => self::MARCADOR,
                'creadoEn' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'sla' => date('Y-m-d H:i:s', strtotime('+20 hours')),
                'notificarPedro' => true,
            ],
            // Por Agendar (video, sin asignar aún — notificado a Pedro entre otros)
            [
                'clienteId' => $alumnos[1], 'tipo' => 'video', 'estado' => 'pendiente', 'docenteId' => null,
                'sector' => 'EDU', 'mensaje' => 'Quisiera una videollamada para revisar el análisis de alternativas (demo asesor1).',
                'horarioFecha' => $proximoLunes, 'horarioInicio' => '09:00:00', 'horarioFin' => '09:30:00',
                'creadoEn' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'sla' => date('Y-m-d H:i:s', strtotime('+15 hours')),
                'notificarPedro' => true,
            ],
            // Agendada (video, ya asignada a Pedro, aún sin atender)
            [
                'clienteId' => $alumnos[2], 'tipo' => 'video', 'estado' => 'agendado', 'docenteId' => $pedroId,
                'sector' => 'EDU', 'mensaje' => 'Videollamada para revisar el cronograma de ejecución (demo asesor1).',
                'horarioFecha' => date('Y-m-d', strtotime('+3 days')), 'horarioInicio' => '15:00:00', 'horarioFin' => '15:30:00',
                'creadoEn' => date('Y-m-d H:i:s', strtotime('-2 days')), 'sla' => null, 'linkReunion' => true,
            ],
            // Agendada (chat, ya asignado a Pedro, conversación en curso)
            [
                'clienteId' => $alumnos[3], 'tipo' => 'chat', 'estado' => 'asignado', 'docenteId' => $pedroId,
                'sector' => 'EDU', 'mensaje' => 'Ayuda con la formulación del problema central (demo asesor1).',
                'creadoEn' => date('Y-m-d H:i:s', strtotime('-4 hours')), 'sla' => null,
            ],
        ];

        foreach ($solicitudes as $s) {
            $this->db->table('solicitudes_asesoria')->insert([
                'cliente_id'          => $s['clienteId'],
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
                'sla_vence_en'        => $s['sla'],
                'link_reunion'        => ! empty($s['linkReunion']) ? $this->linkSimulado() : null,
                'created_at'          => $s['creadoEn'],
                'updated_at'          => $s['creadoEn'],
            ]);
            $solicitudId = $this->db->insertID();

            if (! empty($s['notificarPedro'])) {
                $this->db->table('solicitud_notificaciones')->insert([
                    'solicitud_id' => $solicitudId,
                    'asesor_id'    => $pedroId,
                    'created_at'   => $s['creadoEn'],
                ]);
            }
        }

        echo "Listo — 4 solicitudes demo nuevas para asesor1 (Pedro Ríos): 2 por agendar, 2 agendadas.\n";
    }

    private function asignarEspecialidades(int $pedroId, array $sectorId): void
    {
        $nuevas = 0;
        foreach (self::SECTORES_PROPIOS as $codigo) {
            if (! isset($sectorId[$codigo])) {
                continue;
            }
            $yaTiene = $this->db->table('asesor_especialidades')
                ->where('usuario_id', $pedroId)
                ->where('sector_id', $sectorId[$codigo])
                ->countAllResults() > 0;
            if ($yaTiene) {
                continue;
            }
            $this->db->table('asesor_especialidades')->insert(['usuario_id' => $pedroId, 'sector_id' => $sectorId[$codigo]]);
            $nuevas++;
        }
        if ($nuevas > 0) {
            echo "Listo — {$nuevas} especialidades asignadas a asesor1 (Pedro Ríos).\n";
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

    // Mismo formato que SolicitudAsesoriaHelpersTrait::generarLinkSimulado() — dominio propio del
    // producto, nunca zoom.us/meet.google.com, para no aparentar una integración real.
    private function linkSimulado(): string
    {
        return 'https://proyectafacil.app/reunion/demo-' . strtolower(bin2hex(random_bytes(4)));
    }
}
