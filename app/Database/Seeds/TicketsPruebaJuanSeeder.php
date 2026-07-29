<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Pedido explícito del usuario para probar visualmente la separación de fichas por modalidad
// (docs/proyectafacil-asesorias.md): deja a Juan Pérez (usuario 'cliente') con exactamente 15
// fichas de chat y 17 de videoconferencia, todas disponibles. Reemplaza sus tickets_consulta
// existentes por completo (no los acumula) para que el conteo de "testeo" sea exacto y
// predecible en cada corrida — las solicitudes de asesoría ya sembradas por AsesoriasDemoSeeder
// no se tocan, solo pierden su referencia al ticket original si la tenían.
class TicketsPruebaJuanSeeder extends Seeder
{
    public function run(): void
    {
        $juan = $this->db->table('usuarios')->where('usuario', 'cliente')->get()->getRowArray();
        if ($juan === null) {
            return;
        }
        $juanId = (int) $juan['id'];

        $this->db->table('tickets_consulta')->where('usuario_id', $juanId)->delete();

        $ahora = date('Y-m-d H:i:s');
        foreach ([['chat', 30, 15], ['video', 45, 17]] as [$modalidad, $duracion, $cantidad]) {
            for ($i = 0; $i < $cantidad; $i++) {
                $this->db->table('tickets_consulta')->insert([
                    'usuario_id'       => $juanId,
                    'origen'           => 'plan',
                    'estado'           => 'disponible',
                    'modalidad'        => $modalidad,
                    'duracion_minutos' => $duracion,
                    'created_at'       => $ahora,
                    'updated_at'       => $ahora,
                ]);
            }
        }
    }
}
