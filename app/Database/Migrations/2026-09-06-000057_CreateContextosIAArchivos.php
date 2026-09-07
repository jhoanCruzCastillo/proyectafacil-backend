<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Archivos PDF de referencia adjuntos a "Contexto general" de una ficha (pedido explícito del
// usuario) — tabla completamente separada de contextos_ia_general a propósito: estos archivos NO
// son "insumos" del prompt (nunca deben poder asignarse a un paso en la pestaña Estructura ni
// entrar al llenado automático de la ficha con IA), se usan para otra cosa fuera de este flujo.
class CreateContextosIAArchivos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'plantilla_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'url'          => ['type' => 'VARCHAR', 'constraint' => 500],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('plantilla_id', 'plantillas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('contextos_ia_archivos');
    }

    public function down()
    {
        $this->forge->dropTable('contextos_ia_archivos');
    }
}
