<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

class CreateSectoresTable extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'codigo' => ['type' => 'VARCHAR', 'constraint' => 20],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 150],
            'icono' => ['type' => 'VARCHAR', 'constraint' => 50],
            'color_accent' => ['type' => 'VARCHAR', 'constraint' => 20],
            'descripcion' => ['type' => 'TEXT', 'null' => true],
            'tipo_sector' => $this->enumField(['Sectorial', 'General']),
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codigo');
        $this->forge->createTable('sectores');
        $this->addEnumCheck('sectores', 'tipo_sector', ['Sectorial', 'General']);
    }

    public function down()
    {
        $this->forge->dropTable('sectores');
    }
}
