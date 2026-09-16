<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Reemplaza el catálogo anterior de temas/subtemas de especialidad (PIN/EJO/PLN/…) por la lista
// oficial ILPIIE IVE del Excel. Necesario en BDs ya sembradas: TemasEspecialidadSeeder usa
// ignore(true) y no actualiza filas existentes. Borra pivotes de asesor y preferencias de cliente
// ligadas a subtemas viejos; las solicitudes quedan con subtema_id NULL (FK ON DELETE SET NULL).
//
// Uso (pedido explícito del usuario — no corre solo):
//   php spark db:seed SyncTemasIlpiieSeeder
class SyncTemasIlpiieSeeder extends Seeder
{
    public function run(): void
    {
        $db = $this->db;

        // Preferencias de registro / especialidades de asesor apuntan a IDs viejos — se limpian
        // antes de borrar el catálogo para no dejar huérfanos si alguna FK no cascadea.
        if ($db->tableExists('cliente_subtemas')) {
            $db->table('cliente_subtemas')->emptyTable();
        }
        $db->table('asesor_subtemas')->emptyTable();
        $db->table('asesor_temas_especialidad')->emptyTable();

        // Subtemas primero (FK desde asesor_subtemas / solicitudes / cliente_subtemas).
        $db->table('subtemas_especialidad')->emptyTable();
        $db->table('temas_especialidad')->emptyTable();

        $this->call(TemasEspecialidadSeeder::class);
        $this->call(SubtemasEspecialidadSeeder::class);

        // Reasigna temas demo a asesor1 con los códigos nuevos (LFO/IPD/…), para que Mis
        // especialidades no quede vacío tras el sync.
        $pedro = $db->table('usuarios')->where('usuario', 'asesor1')->get()->getRowArray();
        if ($pedro !== null) {
            $pedroId = (int) $pedro['id'];
            $codigos = ['LFO', 'IPD', 'ETO', 'CON'];
            foreach ($codigos as $codigo) {
                $tema = $db->table('temas_especialidad')->where('codigo', $codigo)->get()->getRowArray();
                if ($tema === null) {
                    continue;
                }
                $db->table('asesor_temas_especialidad')->ignore(true)->insert([
                    'usuario_id' => $pedroId,
                    'tema_id'    => (int) $tema['id'],
                ]);
            }
            // Marca de nuevo los 2 primeros subtemas de cada tema reasignado.
            $this->call(SubtemasEspecialidadSeeder::class);
        }

        echo "Listo — catálogo de temas/subtemas reemplazado por la lista ILPIIE IVE.\n";
    }
}
