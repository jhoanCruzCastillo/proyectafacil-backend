<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Una solicitud de asesoría puede cubrir varios subtemas ILPIIE (el alumno marca temas y
// subtemas juntos en un solo paso). `solicitudes_asesoria.subtema_id` se conserva como el
// primero elegido, para no romper joins/listados viejos; la lista completa vive aquí.
//
// Mismo patrón que `cliente_subtemas` / `asesor_subtemas`: PK compuesta, FKs CASCADE.
class CreateSolicitudSubtemas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'solicitud_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'subtema_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['solicitud_id', 'subtema_id']);
        $this->forge->addForeignKey('solicitud_id', 'solicitudes_asesoria', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('subtema_id', 'subtemas_especialidad', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('solicitud_subtemas');

        $this->db->query(
            'INSERT INTO solicitud_subtemas (solicitud_id, subtema_id)
             SELECT id, subtema_id FROM solicitudes_asesoria WHERE subtema_id IS NOT NULL'
        );
    }

    public function down()
    {
        $this->forge->dropTable('solicitud_subtemas');
    }
}
