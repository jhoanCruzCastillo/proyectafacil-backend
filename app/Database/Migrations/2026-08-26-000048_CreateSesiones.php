<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Sesiones reales por login — el auth seguía siendo un JWT sin estado (nadie podía "cerrar sesión"
// de otro dispositivo; el token quedaba válido hasta expirar solo). Cada login ahora crea una fila
// acá, y el JWT lleva su id (ver AuthToken::emitir()) — AuthFilter valida que la fila siga sin
// revocar en cada request, así "Cerrar sesión" desde el panel realmente invalida el token.
class CreateSesiones extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'usuario_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'dispositivo'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'navegador'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ip'               => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            // Sin geolocalización real integrada — valor fijo, mismo criterio que la zona horaria
            // estática del perfil (toda la operación es en Perú).
            'ubicacion'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'revocada'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'revocada_en'      => ['type' => 'DATETIME', 'null' => true],
            'ultima_actividad' => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sesiones');
    }

    public function down()
    {
        $this->forge->dropTable('sesiones');
    }
}
