<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Pedido explícito del usuario: separar los tickets de consulta en dos "fichas" independientes
// según modalidad (chat / videoconferencia) — antes cualquier ticket disponible servía para
// cualquier modalidad. Cada ficha ahora nace con su propia duración de consulta (minutos), fija
// según su modalidad al momento de emitirse.
class AddModalidadYDuracionATicketsConsulta extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->forge->addColumn('tickets_consulta', [
            'modalidad' => $this->enumField(['chat', 'video'], 'chat') + ['after' => 'origen'],
            'duracion_minutos' => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'default' => 30, 'after' => 'modalidad'],
        ]);
        $this->addEnumCheck('tickets_consulta', 'modalidad', ['chat', 'video']);
    }

    public function down()
    {
        $this->db->query('ALTER TABLE tickets_consulta DROP CONSTRAINT chk_tickets_consulta_modalidad');
        $this->forge->dropColumn('tickets_consulta', 'duracion_minutos');
        $this->forge->dropColumn('tickets_consulta', 'modalidad');
    }
}
