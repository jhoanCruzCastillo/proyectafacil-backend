<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// candidato_notas — notas internas del equipo sobre una postulación (panel "Ver" de
// Especialistas › Candidatos), para que el Administrativo de Asesorías deje contexto de
// seguimiento (llamadas hechas, impresiones de la entrevista, etc.) visible para todo el equipo.
// Nada que ver con `historial_cambio_campos` (auditoría automática de ediciones) — esto es texto
// libre escrito a mano, por eso va en su propia tabla simple, sin campo "acción" ni "sección".
class CreateCandidatoNotas extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('candidato_notas')) {
            return;
        }

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'candidato_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'usuario_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nota'         => ['type' => 'TEXT'],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('candidato_id', 'candidatos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('candidato_notas');
    }

    public function down()
    {
        $this->forge->dropTable('candidato_notas', true);
    }
}
