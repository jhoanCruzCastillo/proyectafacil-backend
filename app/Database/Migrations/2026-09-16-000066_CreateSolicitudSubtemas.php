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
        if (! $this->db->tableExists('solicitud_subtemas')) {
            $this->forge->addField([
                'solicitud_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'subtema_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $this->forge->addPrimaryKey(['solicitud_id', 'subtema_id']);
            $this->forge->addForeignKey('solicitud_id', 'solicitudes_asesoria', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('subtema_id', 'subtemas_especialidad', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('solicitud_subtemas');
        }

        if (
            ! $this->db->tableExists('solicitud_subtemas')
            || ! $this->db->tableExists('solicitudes_asesoria')
            || ! $this->db->fieldExists('subtema_id', 'solicitudes_asesoria')
        ) {
            return;
        }

        // Portable: no ON CONFLICT / INSERT IGNORE. Re-corrida no duplica la PK.
        $this->db->query(
            'INSERT INTO solicitud_subtemas (solicitud_id, subtema_id)
             SELECT sa.id, sa.subtema_id
             FROM solicitudes_asesoria sa
             WHERE sa.subtema_id IS NOT NULL
               AND NOT EXISTS (
                   SELECT 1 FROM solicitud_subtemas ss
                   WHERE ss.solicitud_id = sa.id AND ss.subtema_id = sa.subtema_id
               )'
        );
    }

    public function down()
    {
        $this->forge->dropTable('solicitud_subtemas', true);
    }
}
