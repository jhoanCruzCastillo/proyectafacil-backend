<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

class CreateUsuariosTable extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        // tipo_usuario_id se agrega por ALTER en la migración 000004, después de crear tipos_usuario
        // (evita la referencia circular: usuarios <-> tipos_usuario no se necesitan mutuamente aquí).
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 150],
            'usuario' => ['type' => 'VARCHAR', 'constraint' => 50],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'rol' => $this->enumField(['superusuario', 'administrador', 'cliente']),
            'apodo' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tema' => $this->enumField(['claro', 'oscuro', 'sistema'], 'sistema'),
            'estado' => $this->enumField(['activo', 'inactivo'], 'activo'),
            'cuenta_cliente_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('usuario');
        // Auto-referencia: colaborador -> titular. SET NULL si se borra el titular por error antes
        // de reasignar sus colaboradores (evita que el borrado falle en cascada sobre otros usuarios).
        $this->forge->addForeignKey('cuenta_cliente_id', 'usuarios', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('usuarios');
        $this->addEnumCheck('usuarios', 'rol', ['superusuario', 'administrador', 'cliente']);
        $this->addEnumCheck('usuarios', 'tema', ['claro', 'oscuro', 'sistema']);
        $this->addEnumCheck('usuarios', 'estado', ['activo', 'inactivo']);
    }

    public function down()
    {
        $this->forge->dropTable('usuarios');
    }
}
