<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Pedido explícito del usuario: más citas de video sembradas para el asesor demo (Pedro Ríos /
// asesor1), repartidas en distintos días/horas de la semana actual y la siguiente, para poder
// probar la pantalla "Cronograma" con datos reales — variedad de estados (agendado/completado) y
// horarios que no calzan con los bloques de disponibilidad, para confirmar que los rectángulos de
// cita se ubican libres sobre la grilla (no solo dentro de los bloques marcados como disponibles).
//
// Complementa a AsesoriasDemoAsesor1Seeder sin tocarlo. Idempotente: se salta si ya se sembró antes.
//
// Uso: php spark db:seed CronogramaDemoAsesor1Seeder
class CronogramaDemoAsesor1Seeder extends Seeder
{
    private const MARCADOR = '(demo cronograma asesor1)';

    public function run(): void
    {
        $yaExiste = $this->db->table('solicitudes_asesoria')->like('mensaje_inicial', self::MARCADOR)->countAllResults() > 0;
        if ($yaExiste) {
            echo "Ya sembrado antes — no se hace nada.\n";

            return;
        }

        $pedro = $this->db->table('usuarios')->where('usuario', 'asesor1')->get()->getRowArray();
        if ($pedro === null) {
            echo "No existe el usuario 'asesor1' — corre UsuariosSeeder o UsuariosDemoProduccionSeeder primero.\n";

            return;
        }
        $pedroId = (int) $pedro['id'];

        $sectorId = $this->sectorIdsPorCodigo();
        $alumnos = $this->alumnosDisponibles();
        if (count($alumnos) < 6) {
            echo "No hay suficientes alumnos demo (cliente/cliente2/alumno.*) — corre AsesoriasDemoSeeder primero.\n";

            return;
        }

        $lunesEstaSemana = date('Y-m-d', strtotime('monday this week'));

        $solicitudes = [
            // Lunes 8:30-9:00 — dentro del bloque disponible (8am-11am), agendado.
            ['alumno' => $alumnos[0], 'dia' => 0, 'inicio' => '08:30:00', 'fin' => '09:00:00', 'estado' => 'agendado', 'mensaje' => 'Revisión de la brecha de servicio ' . self::MARCADOR],
            // Martes 9:15-9:45 — dentro del bloque disponible, ya atendida.
            ['alumno' => $alumnos[1], 'dia' => 1, 'inicio' => '09:15:00', 'fin' => '09:45:00', 'estado' => 'completado', 'mensaje' => 'Consulta sobre el análisis técnico ' . self::MARCADOR],
            // Miércoles 16:00-16:30 — dentro del bloque disponible (3pm-6pm), agendado.
            ['alumno' => $alumnos[2], 'dia' => 2, 'inicio' => '16:00:00', 'fin' => '16:30:00', 'estado' => 'agendado', 'mensaje' => 'Duda sobre la evaluación social ' . self::MARCADOR],
            // Jueves 8:00-8:45 — dentro del bloque disponible, ya atendida.
            ['alumno' => $alumnos[3], 'dia' => 3, 'inicio' => '08:00:00', 'fin' => '08:45:00', 'estado' => 'completado', 'mensaje' => 'Revisión de sostenibilidad del proyecto ' . self::MARCADOR],
            // Viernes 13:00-13:30 — fuera de cualquier bloque disponible (prueba de posicionamiento libre).
            ['alumno' => $alumnos[4], 'dia' => 4, 'inicio' => '13:00:00', 'fin' => '13:30:00', 'estado' => 'agendado', 'mensaje' => 'Consulta sobre gestión del proyecto ' . self::MARCADOR],
            // Sábado 11:00-11:30 — fuera de cualquier bloque disponible, ya atendida.
            ['alumno' => $alumnos[5], 'dia' => 5, 'inicio' => '11:00:00', 'fin' => '11:30:00', 'estado' => 'completado', 'mensaje' => 'Revisión final del formato 6A ' . self::MARCADOR],
        ];

        foreach ($solicitudes as $s) {
            $fecha = date('Y-m-d', strtotime($lunesEstaSemana . " +{$s['dia']} days"));

            $this->db->table('solicitudes_asesoria')->insert([
                'cliente_id'          => $s['alumno'],
                'docente_id'          => $pedroId,
                'ejemplo_id'          => null,
                'sector_id'           => $sectorId['EDU'] ?? null,
                'tipo_documento'      => 'ficha_tecnica',
                'tipo'                => 'video',
                'estado'              => $s['estado'],
                'mensaje_inicial'     => $s['mensaje'],
                'horario_fecha'       => $fecha,
                'horario_hora_inicio' => $s['inicio'],
                'horario_hora_fin'    => $s['fin'],
                'sla_vence_en'        => null,
                'link_reunion'        => $this->linkSimulado(),
                'created_at'          => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at'          => date('Y-m-d H:i:s', strtotime('-1 day')),
            ]);
        }

        echo "Listo — 6 citas de video demo nuevas para asesor1 (Pedro Ríos): 3 agendadas, 3 atendidas, repartidas en la semana.\n";
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
