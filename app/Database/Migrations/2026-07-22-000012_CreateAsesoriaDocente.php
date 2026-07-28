<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Asesoría 1:1 cliente↔docente: nuevo rol 'docente', su horario semanal de referencia, las
// solicitudes de asesoría (chat o videollamada por link externo, igual que Mentorías), los mensajes
// de cada solicitud (chat por polling — no hay WebSockets en el proyecto) y un inbox de
// notificaciones por usuario (no existía nada persistente/por-usuario antes de esto, solo el feed
// global de actividad_reciente).
class CreateAsesoriaDocente extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        $this->addEnumCheck('usuarios', 'rol', ['superusuario', 'administrador', 'cliente', 'docente']);

        // horarios_docente — bloques semanales recurrentes (dia_semana 1=lunes .. 7=domingo), solo de
        // referencia para que el cliente sepa cuándo suele estar disponible el docente; no es un
        // calendario de citas (la solicitud siempre es "ahora").
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'dia_semana' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true],
            'hora_inicio' => ['type' => 'TIME'],
            'hora_fin' => ['type' => 'TIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('horarios_docente');

        // solicitudes_asesoria
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'docente_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'ejemplo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'tipo' => $this->enumField(['chat', 'video']),
            'estado' => $this->enumField(['pendiente', 'aceptada', 'rechazada', 'finalizada'], 'pendiente'),
            'mensaje_inicial' => ['type' => 'TEXT', 'null' => true],
            'link_reunion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('cliente_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('docente_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ejemplo_id', 'ejemplos', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('solicitudes_asesoria');
        $this->addEnumCheck('solicitudes_asesoria', 'tipo', ['chat', 'video']);
        $this->addEnumCheck('solicitudes_asesoria', 'estado', ['pendiente', 'aceptada', 'rechazada', 'finalizada']);

        // mensajes_asesoria
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'solicitud_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'autor_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'texto' => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('solicitud_id', 'solicitudes_asesoria', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('autor_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('mensajes_asesoria');

        // notificaciones — inbox simple por usuario (polling), referencia_tipo/referencia_id apunta
        // a la entidad relacionada (ej. 'solicitud_asesoria' + id) para poder navegar al hacer clic.
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 50],
            'mensaje' => ['type' => 'VARCHAR', 'constraint' => 255],
            'referencia_tipo' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'referencia_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'leida_en' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('notificaciones');
    }

    public function down()
    {
        $this->forge->dropTable('notificaciones');
        $this->forge->dropTable('mensajes_asesoria');
        $this->forge->dropTable('solicitudes_asesoria');
        $this->forge->dropTable('horarios_docente');

        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        $this->addEnumCheck('usuarios', 'rol', ['superusuario', 'administrador', 'cliente']);
    }
}
