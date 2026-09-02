<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Evita reenviar el correo "tu videollamada empieza en 5 minutos" en cada corrida del cron (ver
// EnviarRecordatoriosVideollamadaCommand) — se marca la primera vez que se envía y no se vuelve a
// tocar.
class AddRecordatorioEnviadoASolicitudAsesoria extends Migration
{
    public function up()
    {
        $this->forge->addColumn('solicitudes_asesoria', [
            'recordatorio_enviado' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false, 'after' => 'sla_vence_en'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('solicitudes_asesoria', 'recordatorio_enviado');
    }
}
