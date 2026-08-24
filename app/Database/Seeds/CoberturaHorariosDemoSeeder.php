<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Amplía la disponibilidad semanal de los asesores demo (pedido explícito del usuario: el mapa de
// calor de Cobertura de horarios se veía casi vacío) — complementa, no reemplaza, los bloques que
// ya sembró AsesoriasDemoSeeder. Idempotente por bloque exacto (usuario+día de semana+hora_inicio),
// no por "el docente ya tiene algo" — así se puede correr para sumar más franjas sin duplicar las
// que ya existen. El chequeo de "ya existe" compara el día de semana de `fecha_inicio` en PHP, no
// la fecha exacta — `fecha_inicio` es solo el ancla de una regla semanal y varía según qué día se
// corra el seeder, así que comparar fechas exactas duplicaría filas si se corre otro día distinto.
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

            $existentes = $this->db->table('horarios_docente')
                ->select('fecha_inicio, hora_inicio')
                ->where('usuario_id', $docente['id'])
                ->where('tipo_repeticion', 'semanal')
                ->get()->getResultArray();

            foreach ($filas as [$diaSemana, $horaInicio, $horaFin]) {
                $horaInicioConSegundos = "{$horaInicio}:00";
                $yaExiste = false;
                foreach ($existentes as $e) {
                    if ($e['hora_inicio'] === $horaInicioConSegundos && $this->diaSemanaDe((string) $e['fecha_inicio']) === $diaSemana) {
                        $yaExiste = true;
                        break;
                    }
                }
                if ($yaExiste) {
                    continue;
                }

                $this->db->table('horarios_docente')->insert([
                    'usuario_id'      => $docente['id'],
                    'fecha_inicio'    => $this->fechaRecienteConDiaSemana($diaSemana),
                    'hora_inicio'     => $horaInicioConSegundos,
                    'hora_fin'        => "{$horaFin}:00",
                    'todo_el_dia'     => 0,
                    'tipo_repeticion' => 'semanal',
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /** Fecha más reciente <= hoy cuyo día de semana (1=lunes..7=domingo) coincide con $diaSemana. */
    private function fechaRecienteConDiaSemana(int $diaSemana): string
    {
        $hoy    = new \DateTimeImmutable('today');
        $hoyDia = $this->diaSemanaDe($hoy->format('Y-m-d'));
        $atras  = ($hoyDia - $diaSemana + 7) % 7;

        return $hoy->modify("-{$atras} days")->format('Y-m-d');
    }

    /** 1=lunes..7=domingo, a partir de una fecha "YYYY-MM-DD". */
    private function diaSemanaDe(string $fechaIso): int
    {
        $js = (int) date('w', strtotime($fechaIso));

        return $js === 0 ? 7 : $js;
    }
}
