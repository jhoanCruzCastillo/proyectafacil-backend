<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Se retira 'docente' como rol de primera clase — 'asesor' ya cubre exactamente la misma
// funcionalidad (mismos permisos base: atender_chat, atender_video, marcar_disponibilidad, ver
// RolesPermisosBaseSeeder). Los usuarios con rol 'docente' se eliminan (cascada limpia
// horarios_docente/solicitudes_asesoria/mensajes_asesoria/notificaciones donde eran protagonistas).
class RemoveRolDocente extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->db->table('roles_permisos_base')->where('rol', 'docente')->delete();
        $this->db->table('usuarios')->where('rol', 'docente')->delete();

        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        $this->addEnumCheck('usuarios', 'rol', [
            'superusuario', 'administrador', 'cliente', 'administrativo_mentorias', 'asesor',
        ]);
    }

    public function down()
    {
        $this->db->query('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        $this->addEnumCheck('usuarios', 'rol', [
            'superusuario', 'administrador', 'cliente', 'docente', 'administrativo_mentorias', 'asesor',
        ]);
    }
}
