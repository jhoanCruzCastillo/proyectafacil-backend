<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Tamaño del panel de chat de asesoría, recordado por usuario (asesor o cliente, el que sea que
// lo haya redimensionado) — para que no vuelva al tamaño por defecto cada vez que abre un chat.
class AddPreferenciaTamanoChatAUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addColumn('usuarios', [
            'chat_ancho_px' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'after' => 'tema'],
            'chat_alto_px'  => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'after' => 'chat_ancho_px'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('usuarios', ['chat_ancho_px', 'chat_alto_px']);
    }
}
