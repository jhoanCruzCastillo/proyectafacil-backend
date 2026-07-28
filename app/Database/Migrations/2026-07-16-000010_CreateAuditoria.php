<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

class CreateAuditoria extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        // actividad_reciente — feed global del dashboard
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'mensaje' => ['type' => 'VARCHAR', 'constraint' => 255],
            'color' => $this->enumField(['blue', 'green', 'orange', 'gray', 'red']),
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('actividad_reciente');
        $this->addEnumCheck('actividad_reciente', 'color', ['blue', 'green', 'orange', 'gray', 'red']);

        // historial_cambios — una fila por cada "Guardar" con cambios reales en una ficha
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ejemplo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fecha' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ejemplo_id', 'ejemplos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('historial_cambios');

        // historial_cambio_campos — descompone CambioFicha.campos[]
        $this->forge->addField([
            'historial_cambio_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'identificador' => ['type' => 'VARCHAR', 'constraint' => 30],
            'etiqueta' => ['type' => 'VARCHAR', 'constraint' => 200],
            'valor_anterior' => ['type' => 'TEXT'],
            'valor_nuevo' => ['type' => 'TEXT'],
        ]);
        $this->forge->addPrimaryKey(['historial_cambio_id', 'identificador']);
        $this->forge->addForeignKey('historial_cambio_id', 'historial_cambios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('historial_cambio_campos');
    }

    public function down()
    {
        $this->forge->dropTable('historial_cambio_campos');
        $this->forge->dropTable('historial_cambios');
        $this->forge->dropTable('actividad_reciente');
    }
}
