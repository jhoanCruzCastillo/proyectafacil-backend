<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// El "crédito" real detrás de una solicitud de asesoría (docs/proyectafacil-asesorias.md §3.1).
// Reemplaza el COUNT(*) sobre solicitudes_asesoria que se usaba antes para calcular consultas
// disponibles — ahora es una entidad con estado real: Disponible → Reservado → Consumido |
// Liberado (vuelve a Disponible) | Expirado (solo origen='plan', nunca 'addon'). Se emite al
// activarse/renovarse un plan (origen='plan', con fecha_expira) y al comprar el add-on
// "Consultoría 1 a 1" (origen='addon', perpetuo, fecha_expira null).
class CreateTicketsConsulta extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'origen' => $this->enumField(['plan', 'addon']),
            'estado' => $this->enumField(['disponible', 'reservado', 'consumido', 'liberado', 'expirado'], 'disponible'),
            'fecha_expira' => ['type' => 'DATE', 'null' => true],
            'solicitud_asesoria_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('solicitud_asesoria_id', 'solicitudes_asesoria', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('tickets_consulta');
        $this->addEnumCheck('tickets_consulta', 'origen', ['plan', 'addon']);
        $this->addEnumCheck('tickets_consulta', 'estado', ['disponible', 'reservado', 'consumido', 'liberado', 'expirado']);
    }

    public function down()
    {
        $this->forge->dropTable('tickets_consulta');
    }
}
