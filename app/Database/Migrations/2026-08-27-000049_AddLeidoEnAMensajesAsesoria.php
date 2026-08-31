<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Palomitas de "visto" en el chat de asesoría (pedido del cliente, estilo WhatsApp: una palomita
// = enviado, dos = visto). `leido_en` queda NULL hasta que la OTRA parte abre/consulta el chat —
// ver AsesoriaController::mensajes(), que marca como leídos los mensajes ajenos cada vez que el
// visor (`visorId`) hace polling de la conversación.
class AddLeidoEnAMensajesAsesoria extends Migration
{
    public function up()
    {
        $this->forge->addColumn('mensajes_asesoria', [
            'leido_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'adjunto_tipo'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('mensajes_asesoria', ['leido_en']);
    }
}
