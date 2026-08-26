<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Base para el panel "Detalles del usuario" (Usuarios y permisos): datos de perfil que faltaban
// (teléfono, último acceso) y auditoría real de actividad — `actividad_reciente` hoy es un feed
// global sin autor; se le agrega quién hizo la acción (actor_id) y, para el caso puntual de
// "se editó el perfil de alguien", a quién le pasó (objetivo_id) — permite separar "lo que ESTE
// usuario hizo" (tab Actividad) de "lo que le pasó a ESTE usuario" (tab Información, "últimas
// modificaciones del perfil").
class AddAuditoriaAUsuariosYActividad extends Migration
{
    public function up()
    {
        $this->forge->addColumn('usuarios', [
            'telefono'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'correo'],
            'ultimo_acceso' => ['type' => 'DATETIME', 'null' => true, 'after' => 'telefono'],
        ]);

        $this->forge->addColumn('actividad_reciente', [
            'actor_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'id'],
            'objetivo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'actor_id'],
            'categoria'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'color'],
        ]);
        $this->db->query('ALTER TABLE actividad_reciente ADD CONSTRAINT fk_actividad_actor FOREIGN KEY (actor_id) REFERENCES usuarios(id) ON DELETE SET NULL');
        $this->db->query('ALTER TABLE actividad_reciente ADD CONSTRAINT fk_actividad_objetivo FOREIGN KEY (objetivo_id) REFERENCES usuarios(id) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE actividad_reciente DROP CONSTRAINT fk_actividad_actor');
        $this->db->query('ALTER TABLE actividad_reciente DROP CONSTRAINT fk_actividad_objetivo');
        $this->forge->dropColumn('actividad_reciente', ['actor_id', 'objetivo_id', 'categoria']);
        $this->forge->dropColumn('usuarios', ['telefono', 'ultimo_acceso']);
    }
}
