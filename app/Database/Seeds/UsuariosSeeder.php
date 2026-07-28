<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Mismas credenciales que frontend/src/data/usuarios.ts (seed del modo mock) — permite probar el
// login real con los mismos usuarios de demostración que ya se usan en VITE_MOCK_AUTH=true.
class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['nombre' => 'Carlos Núñez', 'usuario' => 'superuser', 'password' => 'Super#2026', 'rol' => 'superusuario'],
            ['nombre' => 'María Quispe', 'usuario' => 'admin', 'password' => 'Admin#2026', 'rol' => 'administrador'],
            ['nombre' => 'Juan Pérez', 'usuario' => 'cliente', 'password' => 'Cliente#2026', 'rol' => 'cliente'],
        ];

        $ahora = date('Y-m-d H:i:s');

        foreach ($usuarios as $u) {
            $this->db->table('usuarios')->insert([
                'nombre'        => $u['nombre'],
                'usuario'       => $u['usuario'],
                'password_hash' => password_hash($u['password'], PASSWORD_DEFAULT),
                'rol'           => $u['rol'],
                'estado'        => 'activo',
                'created_at'    => $ahora,
                'updated_at'    => $ahora,
            ]);
        }
    }
}
