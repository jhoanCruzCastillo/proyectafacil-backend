<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Modal "Editar usuario" (Ruta C): cuando un admin cambia manualmente el Origen de un cliente
// (Alumno/Externo), se registra quién y cuándo — UsuariosController::update() lo detecta
// comparando el valor entrante contra el guardado y setea estas dos columnas server-side.
class AddAuditoriaOrigenUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addColumn('usuarios', [
            'origen_cambiado_por_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'origen_cambiado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addForeignKey('origen_cambiado_por_id', 'usuarios', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('usuarios');
    }

    public function down()
    {
        $this->forge->dropForeignKey('usuarios', 'usuarios_origen_cambiado_por_id_foreign');
        $this->forge->dropColumn('usuarios', ['origen_cambiado_por_id', 'origen_cambiado_en']);
    }
}
