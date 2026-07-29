<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Amplía la disponibilidad semanal de los asesores demo (pedido explícito del usuario: el mapa de
// calor de Cobertura de horarios se veía casi vacío) — complementa, no reemplaza, los bloques que
// ya sembró AsesoriasDemoSeeder. Idempotente por bloque exacto (usuario+día+hora_inicio), no por
// "el docente ya tiene algo" — así se puede correr para sumar más franjas sin duplicar las que ya
// existen.
class CoberturaHorariosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $bloques = [
            // Pedro Ríos (asesor1)
            'asesor1' => [
                [1, '08:00', '11:00'], [2, '08:00', '11:00'], [3, '15:00', '18:00'],
                [4, '08:00', '11:00'], [5, '08:00', '11:00'],
            ],
            // Laura Medina (asesor2)
            'asesor2' => [
                [1, '09:00', '12:00'], [2, '14:00', '17:00'],
                [4, '09:00', '12:00'], [5, '14:00', '17:00'],
            ],
            // Elena Castro (asesor3)
            'asesor3' => [
                [2, '09:00', '11:00'], [4, '09:00', '11:00'],
            ],
            // Jorge Paredes (asesor4)
            'asesor4' => [
                [1, '10:00', '13:00'], [3, '08:00', '10:00'], [5, '10:00', '12:00'],
            ],
            // Sofía Ramírez (asesor5)
            'asesor5' => [
                [2, '14:00', '17:00'], [4, '14:00', '16:00'],
            ],
        ];

        foreach ($bloques as $usuario => $filas) {
            $docente = $this->db->table('usuarios')->where('usuario', $usuario)->get()->getRowArray();
            if ($docente === null) {
                continue;
            }

            foreach ($filas as [$diaSemana, $horaInicio, $horaFin]) {
                $yaExiste = $this->db->table('horarios_docente')
                    ->where('usuario_id', $docente['id'])
                    ->where('dia_semana', $diaSemana)
                    ->where('hora_inicio', "{$horaInicio}:00")
                    ->countAllResults() > 0;
                if ($yaExiste) {
                    continue;
                }

                $this->db->table('horarios_docente')->insert([
                    'usuario_id'  => $docente['id'],
                    'dia_semana'  => $diaSemana,
                    'hora_inicio' => "{$horaInicio}:00",
                    'hora_fin'    => "{$horaFin}:00",
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
