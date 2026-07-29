<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Mismas credenciales que frontend/src/data/usuarios.ts (seed del modo mock) — permite probar el
// login real con los mismos usuarios de demostración que ya se usan en VITE_MOCK_AUTH=true.
// Idempotente: seguro de re-ejecutar, solo inserta los usuarios que todavía no existen (por
// `usuario`) y completa `origen` si a alguno le falta.
class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        // El usuario admin/administrador de muestra pasó de usuario 'admin' a 'administrador' para
        // no chocar con el username 'admin' que suelen usar los superusuarios reales en producción
        // (ver SuperusuarioProduccionSeeder). Si ya corrió esta seeder antes con el nombre viejo,
        // renombrar la fila existente en vez de crear un duplicado.
        $viejoAdmin = $this->db->table('usuarios')->where('usuario', 'admin')->where('rol', 'administrador')->get()->getRowArray();
        if ($viejoAdmin !== null) {
            $this->db->table('usuarios')->where('id', $viejoAdmin['id'])->update(['usuario' => 'administrador']);
        }

        $usuarios = [
            ['nombre' => 'Carlos Núñez', 'usuario' => 'superuser', 'password' => 'Super#2026', 'rol' => 'superusuario', 'origen' => null],
            ['nombre' => 'María Quispe', 'usuario' => 'administrador', 'password' => 'Admin#2026', 'rol' => 'administrador', 'origen' => null],
            ['nombre' => 'Roberto Salas', 'usuario' => 'coord.asesorias', 'password' => 'Asesorias#2026', 'rol' => 'administrativo_asesorias', 'origen' => null],
            ['nombre' => 'Juan Pérez', 'usuario' => 'cliente', 'password' => 'Cliente#2026', 'rol' => 'cliente', 'origen' => 'alumno'],
            ['nombre' => 'Ana Gómez', 'usuario' => 'cliente2', 'password' => 'Cliente#2026', 'rol' => 'cliente', 'origen' => 'externo'],
            ['nombre' => 'Pedro Ríos', 'usuario' => 'asesor1', 'password' => 'Asesor#2026', 'rol' => 'asesor', 'origen' => null],
            ['nombre' => 'Laura Medina', 'usuario' => 'asesor2', 'password' => 'Asesor#2026', 'rol' => 'asesor', 'origen' => null],
        ];

        $ahora = date('Y-m-d H:i:s');

        foreach ($usuarios as $u) {
            $existente = $this->db->table('usuarios')->where('usuario', $u['usuario'])->get()->getRowArray();

            if ($existente === null) {
                $this->db->table('usuarios')->insert([
                    'nombre'        => $u['nombre'],
                    'usuario'       => $u['usuario'],
                    'password_hash' => password_hash($u['password'], PASSWORD_DEFAULT),
                    'rol'           => $u['rol'],
                    'origen'        => $u['origen'],
                    'estado'        => 'activo',
                    'created_at'    => $ahora,
                    'updated_at'    => $ahora,
                ]);
                continue;
            }

            if ($u['origen'] !== null && $existente['origen'] === null) {
                $this->db->table('usuarios')->where('id', $existente['id'])->update(['origen' => $u['origen']]);
            }
        }
    }
}
