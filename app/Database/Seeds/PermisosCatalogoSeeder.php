<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Espejo exacto de PermisoId en frontend/src/types/index.ts — usuario_permisos.permiso_clave
// tiene FK a esta tabla, así que debe existir antes de poder guardar cualquier override de permisos.
class PermisosCatalogoSeeder extends Seeder
{
    public function run()
    {
        $claves = [
            'sectores.ver', 'sectores.gestionar',
            'plantillas.ver', 'plantillas.gestionar', 'plantillas.importar_json',
            'estructura.editar',
            'ejemplos.gestionar', 'excel.asignar', 'json.ver',
            'usuarios.gestionar_clientes', 'usuarios.gestionar_administradores', 'usuarios.gestionar_superusuarios',
            'fichas.crear', 'fichas.compartir', 'fichas.ver_historial',
            'colaboradores.gestionar',
            'mentorias.acceder', 'mentorias.preguntas_respuestas',
            'ia.mejora_texto', 'ia.asesor',
            'facturacion.gestionar',
            'asesoria.solicitar', 'asesoria.atender',
        ];

        foreach ($claves as $clave) {
            $this->db->table('permisos_catalogo')->ignore(true)->insert(['clave' => $clave]);
        }
    }
}
