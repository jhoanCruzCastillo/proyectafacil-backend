<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Los contextos IA dejan de guardar el markdown como TEXT en la fila: ahora se sube como archivo
// .md a Cloudinary y la fila solo guarda la URL (mismo patrón que ya usan Excel e imágenes — ver
// CloudinaryUploader). También agrega el tercer nivel de la jerarquía: "Contextos Generales", que
// aplican a TODA una ficha (a diferencia de "por sección") pero no se comparten con otras fichas
// (a diferencia de "globales").
class ContextosIACloudinary extends Migration
{
    public function up()
    {
        $this->forge->addColumn('contextos_ia_globales', [
            'url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'icono'],
        ]);
        $this->forge->dropColumn('contextos_ia_globales', 'markdown');

        $this->forge->addColumn('contextos_ia_seccion', [
            'url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'seccion_id'],
        ]);
        $this->forge->dropColumn('contextos_ia_seccion', 'markdown');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'plantilla_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 120],
            'url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('plantilla_id', 'plantillas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('contextos_ia_general');
    }

    public function down()
    {
        $this->forge->dropTable('contextos_ia_general');

        $this->forge->addColumn('contextos_ia_seccion', [
            'markdown' => ['type' => 'TEXT', 'null' => true, 'after' => 'seccion_id'],
        ]);
        $this->forge->dropColumn('contextos_ia_seccion', 'url');

        $this->forge->addColumn('contextos_ia_globales', [
            'markdown' => ['type' => 'TEXT', 'null' => true, 'after' => 'icono'],
        ]);
        $this->forge->dropColumn('contextos_ia_globales', 'url');
    }
}
