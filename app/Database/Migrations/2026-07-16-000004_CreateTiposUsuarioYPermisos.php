<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

class CreateTiposUsuarioYPermisos extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        // tipos_usuario — etiquetas de rol personalizadas (ej. "Soporte Técnico"), heredan un nivel base
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 80],
            'nivel_base' => $this->enumField(['administrador', 'cliente']),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nombre');
        $this->forge->createTable('tipos_usuario');
        $this->addEnumCheck('tipos_usuario', 'nivel_base', ['administrador', 'cliente']);

        // Ahora que tipos_usuario existe, se agrega el puntero opcional desde usuarios
        $this->forge->addColumn('usuarios', [
            'tipo_usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'estado'],
        ]);
        $this->forge->addForeignKey('tipo_usuario_id', 'tipos_usuario', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('usuarios');

        // permisos_catalogo — catálogo estático de PermisoId (clave de negocio como PK: son códigos
        // estables tipo enum, no datos que cambien de identidad, así que no necesitan surrogate key)
        $this->forge->addField([
            'clave' => ['type' => 'VARCHAR', 'constraint' => 50],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('clave');
        $this->forge->createTable('permisos_catalogo');

        // usuario_permisos — overrides explícitos por usuario (N:M)
        $this->forge->addField([
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'permiso_clave' => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addPrimaryKey(['usuario_id', 'permiso_clave']);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permiso_clave', 'permisos_catalogo', 'clave', 'CASCADE', 'CASCADE');
        $this->forge->createTable('usuario_permisos');
    }

    public function down()
    {
        $this->forge->dropTable('usuario_permisos');
        $this->forge->dropTable('permisos_catalogo');
        $this->forge->dropForeignKey('usuarios', 'usuarios_tipo_usuario_id_foreign');
        $this->forge->dropColumn('usuarios', 'tipo_usuario_id');
        $this->forge->dropTable('tipos_usuario');
    }
}
