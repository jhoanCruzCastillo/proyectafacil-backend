<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Sobre qué subtema concreto trató la asesoría. Hasta ahora la solicitud solo sabía su sector
// ("Salud"), pero las pantallas de liquidación muestran el detalle ("Equipamiento biomédico"),
// que es lo que le permite al asesor reconocer de qué fue cada consulta.
//
// Nullable a propósito: el flujo del chatbot que crea solicitudes todavía NO pide el subtema, así
// que las solicitudes reales seguirán llegando sin él hasta que se agregue ese paso. Toda la UI
// tiene que tolerar que venga vacío.
class AddSubtemaASolicitudes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('solicitudes_asesoria', [
            'subtema_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->db->query('ALTER TABLE solicitudes_asesoria ADD CONSTRAINT solicitudes_asesoria_subtema_id_foreign FOREIGN KEY (subtema_id) REFERENCES subtemas_especialidad(id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT solicitudes_asesoria_subtema_id_foreign');
        $this->forge->dropColumn('solicitudes_asesoria', 'subtema_id');
    }
}
