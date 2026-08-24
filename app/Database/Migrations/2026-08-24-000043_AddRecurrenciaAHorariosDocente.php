<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Pedido explícito del usuario: "Mi disponibilidad" deja de ser una grilla clicleable por día de
// semana — pasa a ser reglas con fecha ancla + tipo de repetición (diaria, lunes a viernes,
// semanal, mensual, anual) y un flag "todo el día", agregadas solo desde el modal "Agregar
// horario disponible". `dia_semana` queda redundante frente a `fecha_inicio` + `tipo_repeticion`
// (la expansión a ocurrencias concretas vive en el frontend, ver horarioRecurrencia.ts) — se
// elimina.
//
// Backfill fila por fila en PHP (no SQL crudo de fechas — portable Postgres/MariaDB, y el
// dataset es chico): cada fila existente conserva su comportamiento recurrente exacto,
// anclada en la fecha más reciente <= hoy cuyo día de semana coincide con su `dia_semana`.
class AddRecurrenciaAHorariosDocente extends Migration
{
    public function up()
    {
        $this->forge->addColumn('horarios_docente', [
            'fecha_inicio'    => ['type' => 'DATE', 'null' => true, 'after' => 'dia_semana'],
            'todo_el_dia'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'hora_fin'],
            'tipo_repeticion' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'semanal', 'after' => 'todo_el_dia'],
        ]);

        $filas = $this->db->table('horarios_docente')->select('id, dia_semana')->get()->getResultArray();
        foreach ($filas as $fila) {
            $this->db->table('horarios_docente')->where('id', $fila['id'])->update([
                'fecha_inicio'    => $this->fechaRecienteConDiaSemana((int) $fila['dia_semana']),
                'todo_el_dia'     => 0,
                'tipo_repeticion' => 'semanal',
            ]);
        }

        $this->forge->dropColumn('horarios_docente', 'dia_semana');
    }

    public function down()
    {
        $this->forge->addColumn('horarios_docente', [
            'dia_semana' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'after' => 'usuario_id'],
        ]);

        $filas = $this->db->table('horarios_docente')->select('id, fecha_inicio')->get()->getResultArray();
        foreach ($filas as $fila) {
            $jsDia = (int) date('w', strtotime((string) $fila['fecha_inicio']));
            $diaSemana = $jsDia === 0 ? 7 : $jsDia;
            $this->db->table('horarios_docente')->where('id', $fila['id'])->update(['dia_semana' => $diaSemana]);
        }

        $this->forge->dropColumn('horarios_docente', ['fecha_inicio', 'todo_el_dia', 'tipo_repeticion']);
    }

    /** Fecha más reciente <= hoy cuyo día de semana (1=lunes..7=domingo) coincide con $diaSemana. */
    private function fechaRecienteConDiaSemana(int $diaSemana): string
    {
        $hoy      = new \DateTimeImmutable('today');
        $jsHoy    = (int) $hoy->format('w');
        $hoyDia   = $jsHoy === 0 ? 7 : $jsHoy;
        $atras    = ($hoyDia - $diaSemana + 7) % 7;

        return $hoy->modify("-{$atras} days")->format('Y-m-d');
    }
}
