<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Semilla de "Gestionar roles y permisos base" — espejo exacto de los defaults en
// frontend/src/lib/permisosCatalogo.ts (PERMISOS_ADMINISTRADOR, PERMISOS_DOCENTE, etc.).
// Idempotente: no duplica si un rol ya tiene filas guardadas (respeta ediciones previas del admin).
class RolesPermisosBaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'administrador' => [
                'sectores.ver', 'sectores.gestionar',
                'plantillas.ver', 'plantillas.gestionar', 'plantillas.importar_json',
                'estructura.editar', 'ejemplos.gestionar', 'excel.asignar',
                'usuarios.gestionar', 'roles.gestionar',
            ],
            'administrativo_asesorias' => [
                'asesoria.tickets_gestionar', 'asesoria.cobertura_horarios', 'asesoria.matchmaking',
                'asesoria.autorizar_pagos', 'asesoria.configurar_sla',
            ],
            'cliente' => ['fichas.crear', 'facturacion.gestionar', 'asesoria.solicitar'],
            'asesor' => ['asesoria.atender_chat', 'asesoria.atender_video', 'asesoria.marcar_disponibilidad'],
        ];

        foreach ($defaults as $rol => $claves) {
            $yaTiene = $this->db->table('roles_permisos_base')->where('rol', $rol)->countAllResults() > 0;
            if ($yaTiene) {
                continue;
            }
            foreach ($claves as $clave) {
                $this->db->table('roles_permisos_base')->insert(['rol' => $rol, 'permiso_clave' => $clave]);
            }
        }
    }
}
