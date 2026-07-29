<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Modal "Nuevo usuario" (Ruta A): agrega correo electrónico (todos los roles) y, para clientes con
// origen 'alumno', la fecha hasta la que conserva ese acceso ("Vigencia como alumno hasta").
class AddCorreoYVigenciaUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addColumn('usuarios', [
            'correo' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'vigencia_alumno_hasta' => ['type' => 'DATE', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('usuarios', ['correo', 'vigencia_alumno_hasta']);
    }
}
