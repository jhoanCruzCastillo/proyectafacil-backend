<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Pantalla "Gestionar roles y permisos base" (Ruta B): el set de permisos por defecto de cada rol
// (distinto de `usuario_permisos`, que son los overrides puntuales de UN usuario — ver Ruta D).
// Superusuario no tiene filas acá: siempre tiene TODOS_LOS_PERMISOS, no es editable.
class CreateRolesPermisosBase extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'rol' => ['type' => 'VARCHAR', 'constraint' => 30],
            'permiso_clave' => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addPrimaryKey(['rol', 'permiso_clave']);
        $this->forge->addForeignKey('permiso_clave', 'permisos_catalogo', 'clave', 'CASCADE', 'CASCADE');
        $this->forge->createTable('roles_permisos_base');
    }

    public function down()
    {
        $this->forge->dropTable('roles_permisos_base');
    }
}
