<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Módulo 3 (docs/proyectafacil-asesorias.md §4 Fase 2): matchmaking por broadcast — al crear una
// solicitud se notifica a TODOS los asesores elegibles (solicitud_notificaciones registra a quién,
// también usado por el detalle de ticket del Módulo 4) y el primero en aceptar gana. Vocabulario de
// estado final del documento: Pendiente → Asignado(chat)/Agendado(video) → Completado | Cancelado |
// En espera (sin cobertura para reasignar) — reemplaza 'aceptada'/'rechazada'/'finalizada', que ya
// no tienen sentido en un modelo de broadcast (no hay un solo docente al que rechazarle).
class AddMatchmakingASolicitudAsesoria extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->forge->addColumn('solicitudes_asesoria', [
            'sla_vence_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'horario_hora_fin'],
        ]);

        // Migrar filas existentes al nuevo vocabulario ANTES de angostar el CHECK (mismo orden que
        // 2026-08-03-000023: ensanchar primero, mover datos, angostar después).
        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_estado');
        $this->addEnumCheck('solicitudes_asesoria', 'estado', [
            'pendiente', 'aceptada', 'rechazada', 'finalizada', 'cancelado',
            'asignado', 'agendado', 'completado', 'en_espera',
        ]);
        $this->db->query("UPDATE solicitudes_asesoria SET estado = 'asignado' WHERE estado = 'aceptada' AND tipo = 'chat'");
        $this->db->query("UPDATE solicitudes_asesoria SET estado = 'agendado' WHERE estado = 'aceptada' AND tipo = 'video'");
        $this->db->query("UPDATE solicitudes_asesoria SET estado = 'completado' WHERE estado = 'finalizada'");
        $this->db->query("UPDATE solicitudes_asesoria SET estado = 'cancelado' WHERE estado = 'rechazada'");
        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_estado');
        $this->addEnumCheck('solicitudes_asesoria', 'estado', [
            'pendiente', 'asignado', 'agendado', 'completado', 'cancelado', 'en_espera',
        ]);

        // solicitud_notificaciones — a quién se le hizo broadcast (docs §4 Fase 2 y detalle de
        // ticket del Módulo 4 "docentes notificados"). El primer UPDATE en aceptar() gana la carrera.
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'solicitud_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'asesor_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('solicitud_id', 'solicitudes_asesoria', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('asesor_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('solicitud_notificaciones');

        // configuracion_sla — fila única (id=1). La pantalla de edición (Módulo 4) es futura; por
        // ahora solo se necesitan los valores para calcular sla_vence_en al crear una solicitud.
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tiempo_espera_chat_horas' => ['type' => 'INT', 'constraint' => 11, 'default' => 24],
            'tiempo_aceptacion_video_minutos' => ['type' => 'INT', 'constraint' => 11, 'default' => 20],
            'tiempo_extra_conexion_minutos' => ['type' => 'INT', 'constraint' => 11, 'default' => 15],
            'vigencia_horario_dias' => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('configuracion_sla');
        $this->db->table('configuracion_sla')->insert([
            'tiempo_espera_chat_horas' => 24,
            'tiempo_aceptacion_video_minutos' => 20,
            'tiempo_extra_conexion_minutos' => 15,
            'vigencia_horario_dias' => 1,
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('configuracion_sla');
        $this->forge->dropTable('solicitud_notificaciones');

        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_estado');
        $this->addEnumCheck('solicitudes_asesoria', 'estado', [
            'pendiente', 'aceptada', 'rechazada', 'finalizada', 'cancelado',
            'asignado', 'agendado', 'completado', 'en_espera',
        ]);
        $this->db->query("UPDATE solicitudes_asesoria SET estado = 'aceptada' WHERE estado IN ('asignado', 'agendado')");
        $this->db->query("UPDATE solicitudes_asesoria SET estado = 'finalizada' WHERE estado = 'completado'");
        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_estado');
        $this->addEnumCheck('solicitudes_asesoria', 'estado', ['pendiente', 'aceptada', 'rechazada', 'finalizada', 'cancelado']);

        $this->forge->dropColumn('solicitudes_asesoria', 'sla_vence_en');
    }
}
