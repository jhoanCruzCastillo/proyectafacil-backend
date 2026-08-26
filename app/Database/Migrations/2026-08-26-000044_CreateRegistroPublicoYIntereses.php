<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Registro público (pedido del cliente de ATIENDO, ver plan de implementación): una cuenta
// self-service necesita quedar en un estado intermedio hasta que confirme su correo — se agrega
// 'pendiente_verificacion' al enum de `usuarios.estado` (mismo patrón portable de
// PortableEnumTrait ya usado en el resto del proyecto: VARCHAR + CHECK, no ENUM nativo). El campo
// original era VARCHAR(20) (max(20, strlen('inactivo'))) — 'pendiente_verificacion' (22
// caracteres) no entra, así que también se ensancha la columna, mismo patrón que
// AddRolesYOrigenUsuarios hizo con `rol`.
//
// `token_verificacion`/`token_verificacion_expira`: token de un solo uso para el link de
// verificación por correo. `preferencia_registro`: la respuesta libre a "¿cuál de estas te
// describe mejor?" del formulario de registro — puramente informativa, sin ningún efecto en
// permisos (eso lo sigue decidiendo `origen`, que el registro público siempre deja en 'externo').
//
// `cliente_intereses`: mismo patrón exacto que `asesor_especialidades`
// (2026-08-02-000022_CreateAsesorEspecialidades.php) — PK compuesta (usuario_id, sector_id), FKs
// CASCADE/CASCADE, sin id propio ni timestamps.
class CreateRegistroPublicoYIntereses extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_estado');
        $this->forge->modifyColumn('usuarios', [
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30],
        ]);
        $this->addEnumCheck('usuarios', 'estado', ['activo', 'inactivo', 'pendiente_verificacion']);

        $this->forge->addColumn('usuarios', [
            'token_verificacion'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'estado'],
            'token_verificacion_expira' => ['type' => 'DATETIME', 'null' => true, 'after' => 'token_verificacion'],
            'preferencia_registro'      => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'origen'],
        ]);

        $this->forge->addField([
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'sector_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['usuario_id', 'sector_id']);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('sector_id', 'sectores', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cliente_intereses');
    }

    public function down()
    {
        $this->forge->dropTable('cliente_intereses');

        $this->forge->dropColumn('usuarios', ['token_verificacion', 'token_verificacion_expira', 'preferencia_registro']);

        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_estado');
        $this->db->query("UPDATE usuarios SET estado = 'inactivo' WHERE estado = 'pendiente_verificacion'");
        $this->addEnumCheck('usuarios', 'estado', ['activo', 'inactivo']);
        $this->forge->modifyColumn('usuarios', [
            'estado' => ['type' => 'VARCHAR', 'constraint' => 20],
        ]);
    }
}
