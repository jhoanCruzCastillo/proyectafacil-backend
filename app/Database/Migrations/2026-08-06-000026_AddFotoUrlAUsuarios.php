<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Foto de perfil de docentes/alumnos (pedido explícito del usuario) — avatar generado vía DiceBear
// (api.dicebear.com, gratuita, sin API key, ilustrada — no fotos de personas reales, para no
// aparentar representar a alguien real) desde el seeder, nunca subida por el usuario final todavía.
class AddFotoUrlAUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addColumn('usuarios', [
            'foto_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'correo'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('usuarios', 'foto_url');
    }
}
