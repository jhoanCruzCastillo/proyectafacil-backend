<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Bootstrap MÍNIMO para un ambiente real (producción/staging): crea SOLO un superusuario, con
// usuario/contraseña tomados de variables de entorno (nunca hardcodeados como en UsuariosSeeder,
// que es exclusivamente para desarrollo local). El resto de usuarios (admin, clientes, asesores)
// se crean después desde la propia app, vía "Usuarios y permisos" — este seeder no los siembra.
//
// Requiere las variables de entorno SEED_SUPERUSER_USUARIO y SEED_SUPERUSER_PASSWORD (configúralas
// en Railway → Variables antes de correr este seeder). Si ya existe un usuario con ese `usuario`,
// no hace nada (idempotente) — no pisa una contraseña ya establecida.
class SuperusuarioProduccionSeeder extends Seeder
{
    public function run(): void
    {
        $usuario   = getenv('SEED_SUPERUSER_USUARIO');
        $password  = getenv('SEED_SUPERUSER_PASSWORD');
        $nombre    = getenv('SEED_SUPERUSER_NOMBRE') ?: 'Superusuario';

        if (! $usuario || ! $password) {
            echo "Faltan SEED_SUPERUSER_USUARIO / SEED_SUPERUSER_PASSWORD como variables de entorno — no se creó ningún usuario.\n";

            return;
        }

        $existente = $this->db->table('usuarios')->where('usuario', $usuario)->get()->getRowArray();
        if ($existente !== null) {
            echo "Ya existe un usuario '{$usuario}' — no se modificó.\n";

            return;
        }

        $ahora = date('Y-m-d H:i:s');
        $this->db->table('usuarios')->insert([
            'nombre'        => $nombre,
            'usuario'       => $usuario,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'rol'           => 'superusuario',
            'origen'        => null,
            'estado'        => 'activo',
            'created_at'    => $ahora,
            'updated_at'    => $ahora,
        ]);

        echo "Superusuario '{$usuario}' creado.\n";
    }
}
