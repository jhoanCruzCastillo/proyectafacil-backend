<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Link de la grabación de la videollamada (Google Drive, cuenta sistema jcruz@arkha.tech) — ver
// GoogleMeetService::grabacionLista(). Queda NULL hasta que Google termina de procesar el archivo
// (puede tardar minutos u horas después de que la llamada terminó), así que un comando programado
// lo va completando después — no se llena en el momento de aceptar/cerrar la solicitud.
class AddLinkGrabacionASolicitudAsesoria extends Migration
{
    public function up()
    {
        $this->forge->addColumn('solicitudes_asesoria', [
            'link_grabacion' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'link_reunion'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('solicitudes_asesoria', ['link_grabacion']);
    }
}
