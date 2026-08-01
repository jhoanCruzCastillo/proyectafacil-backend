<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Segundo nivel de las especialidades del asesor: cada sector MEF ("tema de especialidad") agrupa
// varios "subtemas" más específicos — p. ej. dentro de Formatos Generales: "Liquidación por
// administración directa", "Liquidación por contrata", etc.
//
// `asesor_subtemas` es independiente de `asesor_especialidades` (no la reemplaza): el asesor sigue
// eligiendo sectores completos, y opcionalmente afina qué subtemas dentro de esos sectores atiende.
// Un asesor sin subtemas marcados en un sector se interpreta como "atiende todo el sector".
class CreateSubtemasEspecialidad extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'sector_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 160],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // Un mismo nombre de subtema puede repetirse entre sectores distintos, pero no dentro del
        // mismo sector — esto es lo que hace idempotente al seeder.
        $this->forge->addUniqueKey(['sector_id', 'nombre']);
        $this->forge->addForeignKey('sector_id', 'sectores', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('subtemas_especialidad');

        $this->forge->addField([
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'subtema_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['usuario_id', 'subtema_id']);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('subtema_id', 'subtemas_especialidad', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('asesor_subtemas');
    }

    public function down()
    {
        $this->forge->dropTable('asesor_subtemas');
        $this->forge->dropTable('subtemas_especialidad');
    }
}
