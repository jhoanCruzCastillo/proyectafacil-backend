<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Especialidades del asesor (docs/proyectafacil-asesorias.md §3.3): uno o más de los 13 sectores
// MEF, elegidos por el propio asesor desde su pantalla — el Administrador solo los ve (tabla
// "Docentes", de solo lectura). También agrega el toggle "Disponible" del asesor (recibe
// solicitudes de chat solo si está activo) usado en el matchmaking por broadcast.
class CreateAsesorEspecialidades extends Migration
{
    public function up()
    {
        $this->forge->addColumn('usuarios', [
            'disponible' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => false],
        ]);

        $this->forge->addField([
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'sector_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['usuario_id', 'sector_id']);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('sector_id', 'sectores', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('asesor_especialidades');
    }

    public function down()
    {
        $this->forge->dropTable('asesor_especialidades');
        $this->forge->dropColumn('usuarios', 'disponible');
    }
}
