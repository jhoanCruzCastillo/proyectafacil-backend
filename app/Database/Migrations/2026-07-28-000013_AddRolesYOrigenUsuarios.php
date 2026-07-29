<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Pantalla "Usuarios y permisos" (rediseño): amplía el catálogo de roles con
// 'administrativo_mentorias' y 'asesor', y agrega 'origen' (alumno/externo) para distinguir de
// dónde viene una cuenta Cliente — se muestra como columna/badge en la tabla y como filtro.
class AddRolesYOrigenUsuarios extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        // 'administrativo_mentorias' (24) no cabe en el VARCHAR(20) original — se ensancha la columna.
        $this->forge->modifyColumn('usuarios', [
            'rol' => ['type' => 'VARCHAR', 'constraint' => 30],
        ]);
        $this->addEnumCheck('usuarios', 'rol', [
            'superusuario', 'administrador', 'cliente', 'docente', 'administrativo_mentorias', 'asesor',
        ]);

        $this->forge->addColumn('usuarios', [
            'origen' => $this->enumField(['alumno', 'externo'], null, true),
        ]);
        $this->addEnumCheck('usuarios', 'origen', ['alumno', 'externo']);
    }

    public function down()
    {
        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_origen');
        $this->forge->dropColumn('usuarios', 'origen');

        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        $this->addEnumCheck('usuarios', 'rol', ['superusuario', 'administrador', 'cliente', 'docente']);
    }
}
