<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Excepciones puntuales sobre el horario recurrente del docente (horarios_docente) — pedido
// explícito del usuario: poder marcar una FECHA real específica como "ocupado" aunque ese día de
// la semana normalmente esté disponible (ej. "todos los sábados 9-12 disponible, pero el sábado
// 15 de agosto en particular no"). A diferencia de horarios_docente (recurrente, por día de
// semana), esta tabla es por fecha puntual — no se repite.
class CreateHorarioExcepcionesDocente extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'usuario_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fecha'       => ['type' => 'DATE'],
            'hora_inicio' => ['type' => 'TIME'],
            'hora_fin'    => ['type' => 'TIME'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('horario_excepciones_docente');
    }

    public function down()
    {
        $this->forge->dropTable('horario_excepciones_docente');
    }
}
