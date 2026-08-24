<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Redefine el estado que antes era 'incompleta' (ambas partes se conectaron pero el criterio no
// se cumplió) — pedido explícito del usuario: pasa a llamarse 'observado' y el criterio cambia de
// "±5 min de tolerancia en los bordes del horario acordado" a una comparación pura de duración
// (tiempo conectado en simultáneo vs. duración pactada, ver
// SolicitudAsesoriaHelpersTrait::evaluarAsistenciaReal). Se migran las filas existentes para que
// no queden con un valor que el nuevo CHECK ya no permite.
class RenombrarIncompletaAObservado extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_estado');
        $this->db->query("UPDATE solicitudes_asesoria SET estado = 'observado' WHERE estado = 'incompleta'");
        $this->addEnumCheck('solicitudes_asesoria', 'estado', [
            'pendiente', 'aceptada', 'rechazada', 'finalizada', 'cancelado',
            'asignado', 'agendado', 'completado', 'en_espera',
            'observado', 'vencido',
        ]);
    }

    public function down()
    {
        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_estado');
        $this->db->query("UPDATE solicitudes_asesoria SET estado = 'incompleta' WHERE estado = 'observado'");
        $this->addEnumCheck('solicitudes_asesoria', 'estado', [
            'pendiente', 'aceptada', 'rechazada', 'finalizada', 'cancelado',
            'asignado', 'agendado', 'completado', 'en_espera',
            'incompleta', 'vencido',
        ]);
    }
}
