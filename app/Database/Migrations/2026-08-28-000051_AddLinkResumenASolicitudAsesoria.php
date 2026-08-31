<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Link del resumen/recap generado por Gemini ("Take notes for me", incluido en Business Standard)
// — mismo criterio que link_grabacion: queda NULL hasta que Google termina de generarlo, un
// comando programado lo va completando después. Ver GoogleMeetService::resumenListo().
class AddLinkResumenASolicitudAsesoria extends Migration
{
    public function up()
    {
        $this->forge->addColumn('solicitudes_asesoria', [
            'link_resumen' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'link_grabacion'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('solicitudes_asesoria', ['link_resumen']);
    }
}
