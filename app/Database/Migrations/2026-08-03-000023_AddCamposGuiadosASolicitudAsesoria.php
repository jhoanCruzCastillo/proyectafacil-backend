<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Módulo 2 (docs/proyectafacil-asesorias.md §4 Fase 1): la solicitud guiada ya no elige un docente
// específico al crearse — se crea "Pendiente" sin asignar (el matchmaking del Módulo 3 la asigna
// después), y captura sector/tipo de documento/horario elegidos en el chatbot de 3 pasos. Se agrega
// 'cancelado' al estado para la cancelación propia del alumno (libera el ticket reservado).
class AddCamposGuiadosASolicitudAsesoria extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->forge->modifyColumn('solicitudes_asesoria', [
            'docente_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);

        $this->forge->addColumn('solicitudes_asesoria', [
            'sector_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'ejemplo_id'],
            'tipo_documento' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'sector_id'],
            'horario_fecha' => ['type' => 'DATE', 'null' => true, 'after' => 'mensaje_inicial'],
            'horario_hora_inicio' => ['type' => 'TIME', 'null' => true, 'after' => 'horario_fecha'],
            'horario_hora_fin' => ['type' => 'TIME', 'null' => true, 'after' => 'horario_hora_inicio'],
        ]);
        $this->db->query(
            'ALTER TABLE solicitudes_asesoria ADD CONSTRAINT fk_solicitudes_asesoria_sector_id '
            . 'FOREIGN KEY (sector_id) REFERENCES sectores(id) ON DELETE SET NULL ON UPDATE CASCADE',
        );

        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_estado');
        $this->addEnumCheck('solicitudes_asesoria', 'estado', ['pendiente', 'aceptada', 'rechazada', 'finalizada', 'cancelado']);
    }

    public function down()
    {
        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_estado');
        $this->addEnumCheck('solicitudes_asesoria', 'estado', ['pendiente', 'aceptada', 'rechazada', 'finalizada']);

        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT fk_solicitudes_asesoria_sector_id');
        $this->forge->dropColumn('solicitudes_asesoria', ['sector_id', 'tipo_documento', 'horario_fecha', 'horario_hora_inicio', 'horario_hora_fin']);

        $this->forge->modifyColumn('solicitudes_asesoria', [
            'docente_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
        ]);
    }
}
