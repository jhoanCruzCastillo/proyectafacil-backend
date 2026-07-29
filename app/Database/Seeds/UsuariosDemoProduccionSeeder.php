<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

// Pedido explícito del usuario: sembrar en producción el mismo roster de usuarios de muestra que
// existe en local (UsuariosSeeder), uno por rol, para tener un demo público con login listo para
// usar — justificado porque este ambiente es una demo, no tiene datos reales de clientes todavía.
//
// Deliberadamente separado de DeployProduccionSeeder (que solo siembra datos reales): correr esto
// es una decisión explícita y aparte, no algo que pase automáticamente en cada deploy limpio.
//
// Usa el username 'administrador' (no 'admin') para el rol Administrador porque 'admin' suele ser
// el username elegido para SuperusuarioProduccionSeeder — evita el choque. El resto de usernames
// son idénticos a UsuariosSeeder (local) a propósito, para que el mismo desplegable de "acceso
// rápido" del login funcione igual en ambos ambientes.
//
// Idempotente: no duplica si ya corrió antes.
//
// Uso: php spark db:seed UsuariosDemoProduccionSeeder
class UsuariosDemoProduccionSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['nombre' => 'Carlos Núñez', 'usuario' => 'superuser', 'password' => 'Super#2026', 'rol' => 'superusuario', 'origen' => null],
            ['nombre' => 'María Quispe', 'usuario' => 'administrador', 'password' => 'Admin#2026', 'rol' => 'administrador', 'origen' => null],
            ['nombre' => 'Roberto Salas', 'usuario' => 'coord.asesorias', 'password' => 'Asesorias#2026', 'rol' => 'administrativo_asesorias', 'origen' => null],
            ['nombre' => 'Juan Pérez', 'usuario' => 'cliente', 'password' => 'Cliente#2026', 'rol' => 'cliente', 'origen' => 'alumno'],
            ['nombre' => 'Ana Gómez', 'usuario' => 'cliente2', 'password' => 'Cliente#2026', 'rol' => 'cliente', 'origen' => 'externo'],
            ['nombre' => 'Pedro Ríos', 'usuario' => 'asesor1', 'password' => 'Asesor#2026', 'rol' => 'asesor', 'origen' => null],
        ];

        $ahora = date('Y-m-d H:i:s');

        foreach ($usuarios as $u) {
            $existente = $this->db->table('usuarios')->where('usuario', $u['usuario'])->get()->getRowArray();

            if ($existente !== null) {
                CLI::write("  ya existe: {$u['usuario']}", 'yellow');
                continue;
            }

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
            CLI::write("  creado: {$u['usuario']} ({$u['rol']})", 'green');
        }

        CLI::write('Listo — usuarios de demo sembrados. Estas son credenciales de demo públicas, no reales.', 'green');
    }
}
