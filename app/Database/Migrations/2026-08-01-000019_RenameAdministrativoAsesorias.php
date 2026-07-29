<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// El rol 'administrativo_mentorias' en realidad gobierna el módulo de Asesorías 1:1 (tickets,
// cobertura de horarios, matchmaking) — se renombra a 'administrativo_asesorias' para que el
// nombre interno deje de decir "mentorias" (ver docs/proyectafacil-asesorias.md, nomenclatura
// obligatoria). Las claves de permiso correspondientes se renombran igual; el UPDATE sobre
// permisos_catalogo.clave (PK) cascada solo por FK ON UPDATE CASCADE hacia usuario_permisos y
// roles_permisos_base (ver 2026-07-16-000004_CreateTiposUsuarioYPermisos.php).
class RenameAdministrativoAsesorias extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        $this->addEnumCheck('usuarios', 'rol', [
            'superusuario', 'administrador', 'cliente', 'administrativo_mentorias', 'administrativo_asesorias', 'asesor',
        ]);

        $this->db->query("UPDATE usuarios SET rol = 'administrativo_asesorias' WHERE rol = 'administrativo_mentorias'");
        // Coincide con usuario/password nuevos que espera UsuariosSeeder.php — evita que un re-seed
        // cree un duplicado, y que la contraseña de acceso quede desincronizada de la seedeada.
        $hash = password_hash('Asesorias#2026', PASSWORD_DEFAULT);
        $this->db->query("UPDATE usuarios SET usuario = 'coord.asesorias', password_hash = ? WHERE usuario = 'coord.mentorias'", [$hash]);

        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        $this->addEnumCheck('usuarios', 'rol', [
            'superusuario', 'administrador', 'cliente', 'administrativo_asesorias', 'asesor',
        ]);

        $this->db->query("UPDATE roles_permisos_base SET rol = 'administrativo_asesorias' WHERE rol = 'administrativo_mentorias'");

        $this->db->query("UPDATE permisos_catalogo SET clave = 'asesoria.tickets_gestionar' WHERE clave = 'mentorias.tickets_gestionar'");
        $this->db->query("UPDATE permisos_catalogo SET clave = 'asesoria.cobertura_horarios' WHERE clave = 'mentorias.cobertura_horarios'");
        $this->db->query("UPDATE permisos_catalogo SET clave = 'asesoria.matchmaking' WHERE clave = 'mentorias.matchmaking'");
    }

    public function down()
    {
        $this->db->query("UPDATE permisos_catalogo SET clave = 'mentorias.tickets_gestionar' WHERE clave = 'asesoria.tickets_gestionar'");
        $this->db->query("UPDATE permisos_catalogo SET clave = 'mentorias.cobertura_horarios' WHERE clave = 'asesoria.cobertura_horarios'");
        $this->db->query("UPDATE permisos_catalogo SET clave = 'mentorias.matchmaking' WHERE clave = 'asesoria.matchmaking'");

        $this->db->query("UPDATE roles_permisos_base SET rol = 'administrativo_mentorias' WHERE rol = 'administrativo_asesorias'");

        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        $this->addEnumCheck('usuarios', 'rol', [
            'superusuario', 'administrador', 'cliente', 'administrativo_mentorias', 'administrativo_asesorias', 'asesor',
        ]);

        $this->db->query("UPDATE usuarios SET usuario = 'coord.mentorias' WHERE usuario = 'coord.asesorias'");
        $this->db->query("UPDATE usuarios SET rol = 'administrativo_mentorias' WHERE rol = 'administrativo_asesorias'");

        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        $this->addEnumCheck('usuarios', 'rol', [
            'superusuario', 'administrador', 'cliente', 'administrativo_mentorias', 'asesor',
        ]);
    }
}
