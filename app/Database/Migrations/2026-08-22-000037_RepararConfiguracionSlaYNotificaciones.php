<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Repara el estado que dejó un `down()` de 2026-08-04-000024_AddMatchmakingASolicitudAsesoria que
// se interrumpió a medio camino (encontrado en vivo: POST /asesoria/solicitudes devolvía 500 porque
// "configuracion_sla" no existía). Ese down() alcanzó a (1) borrar configuracion_sla y
// solicitud_notificaciones y (2) quitar el CHECK constraint viejo de estado, pero se cayó antes de
// terminar de reinstalarlo — la tabla quedó sin NINGÚN constraint de estado y con datos mezclando
// vocabulario viejo ('aceptada', 'finalizada') y nuevo ('completado', 'en_espera', etc.).
//
// Migración aditiva y segura de re-correr: solo crea lo que falte y nunca actualiza filas
// existentes — la limpieza de las filas con vocabulario viejo queda deliberadamente fuera (no hace
// falta para que la app funcione: el código actual ya no ESCRIBE esos valores, solo pueden seguir
// existiendo en filas históricas).
class RepararConfiguracionSlaYNotificaciones extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        if (! $this->db->tableExists('solicitud_notificaciones')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'solicitud_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'asesor_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('solicitud_id', 'solicitudes_asesoria', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('asesor_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('solicitud_notificaciones');
        }

        if (! $this->db->tableExists('configuracion_sla')) {
            $this->forge->addField([
                'id'                              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tiempo_espera_chat_horas'        => ['type' => 'INT', 'constraint' => 11, 'default' => 24],
                'tiempo_aceptacion_video_minutos' => ['type' => 'INT', 'constraint' => 11, 'default' => 20],
                'tiempo_extra_conexion_minutos'   => ['type' => 'INT', 'constraint' => 11, 'default' => 15],
                'vigencia_horario_dias'           => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('configuracion_sla');
        }
        if ($this->db->table('configuracion_sla')->countAllResults() === 0) {
            $this->db->table('configuracion_sla')->insert([
                'tiempo_espera_chat_horas'        => 24,
                'tiempo_aceptacion_video_minutos' => 20,
                'tiempo_extra_conexion_minutos'   => 15,
                'vigencia_horario_dias'           => 1,
            ]);
        }

        // El CHECK de estado quedó eliminado a medio camino (ver arriba). Se repone con la lista
        // ANCHA (vocabulario viejo + nuevo) para no rechazar ninguna fila ya existente — try/catch en
        // vez de una consulta a catálogos del motor (pg_constraint no existe en MariaDB, que es el
        // motor de producción; ver PortableEnumTrait) porque "ya existe" es justamente el único caso
        // que nos interesa ignorar.
        try {
            $this->addEnumCheck('solicitudes_asesoria', 'estado', [
                'pendiente', 'aceptada', 'rechazada', 'finalizada', 'cancelado',
                'asignado', 'agendado', 'completado', 'en_espera',
            ]);
        } catch (\Throwable $e) {
            // Constraint ya existe (u otro motor con nombre de constraint duplicado) — nada que hacer.
        }
    }

    public function down()
    {
        // Migración de reparación — no hay nada sensato que revertir (el down() de la migración
        // original ya causó el problema que esto arregla).
    }
}
