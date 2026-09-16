<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Preferencias del paso 2 del registro público ("Temas de interés"): el cliente elige subtemas
// del catálogo ILPIIE (`subtemas_especialidad`), no sectores MEF. `cliente_intereses` (sector_id)
// queda para usos futuros / datos legacy; el registro nuevo guarda aquí.
//
// Mismo patrón que `asesor_subtemas`: PK compuesta (usuario_id, subtema_id), FKs CASCADE/CASCADE,
// sin id propio ni timestamps.
class CreateClienteSubtemas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'subtema_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['usuario_id', 'subtema_id']);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('subtema_id', 'subtemas_especialidad', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cliente_subtemas');
    }

    public function down()
    {
        $this->forge->dropTable('cliente_subtemas');
    }
}
