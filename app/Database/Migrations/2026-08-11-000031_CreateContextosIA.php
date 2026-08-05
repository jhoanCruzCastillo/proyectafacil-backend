<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Contexto que se le entrega a la IA cuando ayuda a llenar una ficha.
//
// Dos niveles, tal como los usa el panel de Contextos IA:
//  - LOCAL: un markdown por (plantilla, sección). Solo aplica a esa sección de esa ficha.
//  - GLOBAL: un markdown reutilizable ("Finanzas", "Invierte.pe") que cualquier sección puede
//    asociar. Vive aparte porque se comparte entre fichas y se edita una sola vez.
//
// `seccion_id` es el id de sección DENTRO del JSON de la plantilla (un string tipo "1", "3"), no una
// FK: las secciones no son tabla propia, viven dentro de la estructura de la plantilla.
class CreateContextosIA extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 120],
            'icono' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'markdown' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nombre');
        $this->forge->createTable('contextos_ia_globales');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'plantilla_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'seccion_id' => ['type' => 'VARCHAR', 'constraint' => 40],
            'markdown' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // Una sección de una plantilla tiene como mucho un contexto: guardar es un upsert.
        $this->forge->addUniqueKey(['plantilla_id', 'seccion_id']);
        $this->forge->addForeignKey('plantilla_id', 'plantillas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('contextos_ia_seccion');

        $this->forge->addField([
            'contexto_seccion_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'contexto_global_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['contexto_seccion_id', 'contexto_global_id']);
        $this->forge->addForeignKey('contexto_seccion_id', 'contextos_ia_seccion', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('contexto_global_id', 'contextos_ia_globales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('contexto_seccion_globales');
    }

    public function down()
    {
        $this->forge->dropTable('contexto_seccion_globales');
        $this->forge->dropTable('contextos_ia_seccion');
        $this->forge->dropTable('contextos_ia_globales');
    }
}
