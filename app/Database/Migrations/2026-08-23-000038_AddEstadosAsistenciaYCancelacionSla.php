<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Dos piezas del mismo pedido del usuario ("estados y comportamiento de tickets de
// videoconferencia"):
// 1. Dos estados nuevos para solicitudes de video, resueltos automáticamente contra la asistencia
//    real en Meet (ver SolicitudAsesoriaHelpersTrait::resolverAsistenciaSiCorresponde):
//    'incompleta' (hubo algo de tiempo simultáneo real pero no cubrió el horario acordado) y
//    'vencido' (nunca coincidieron conectados). Reutiliza el nombre "vencido" que ya existe como
//    etiqueta de SLA de aceptación (antes de agendarse) — contextos distintos, decisión explícita
//    del usuario, no una colisión accidental.
// 2. Ventana configurable de cancelación propia del alumno en configuracion_sla: NULL = puede
//    cancelar en cualquier momento ("permanentemente" habilitado), un número = solo dentro de esos
//    minutos desde que se creó la solicitud.
class AddEstadosAsistenciaYCancelacionSla extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_estado');
        $this->addEnumCheck('solicitudes_asesoria', 'estado', [
            'pendiente', 'aceptada', 'rechazada', 'finalizada', 'cancelado',
            'asignado', 'agendado', 'completado', 'en_espera',
            'incompleta', 'vencido',
        ]);

        $this->forge->addColumn('configuracion_sla', [
            'cancelacion_limite_minutos' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => 60],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('configuracion_sla', 'cancelacion_limite_minutos');

        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_estado');
        $this->addEnumCheck('solicitudes_asesoria', 'estado', [
            'pendiente', 'aceptada', 'rechazada', 'finalizada', 'cancelado',
            'asignado', 'agendado', 'completado', 'en_espera',
        ]);
    }
}
