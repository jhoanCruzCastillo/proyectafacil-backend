<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Adjuntos en el chat de asesoría (imágenes y archivos) — pedido del cliente: "que el sistema
// permita enviar cualquier tipo de archivos, como un chat real". Un mensaje puede llevar texto,
// adjunto, o ambos — `texto` sigue NOT NULL pero puede llegar como cadena vacía cuando el mensaje
// es solo un archivo.
class AddAdjuntoAMensajesAsesoria extends Migration
{
    public function up()
    {
        $this->forge->addColumn('mensajes_asesoria', [
            'adjunto_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'texto'],
            'adjunto_nombre' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'adjunto_url'],
            'adjunto_tipo'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'adjunto_nombre'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('mensajes_asesoria', ['adjunto_url', 'adjunto_nombre', 'adjunto_tipo']);
    }
}
