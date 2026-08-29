<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Texto ya extraído de la sección "Summary" del resumen de Gemini (ver GoogleMeetService::
// resumenTexto) — se guarda aparte de link_resumen para no tener que leer el Google Doc cada vez
// que se abre el ticket; el comando programado lo completa una sola vez, junto con link_resumen.
class AddResumenIaTextoASolicitudAsesoria extends Migration
{
    public function up()
    {
        $this->forge->addColumn('solicitudes_asesoria', [
            'resumen_ia_texto' => ['type' => 'TEXT', 'null' => true, 'after' => 'link_resumen'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('solicitudes_asesoria', ['resumen_ia_texto']);
    }
}
