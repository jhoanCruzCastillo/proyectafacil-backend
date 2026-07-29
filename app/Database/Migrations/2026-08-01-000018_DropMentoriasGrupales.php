<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Se elimina por completo la feature de Mentorías grupales (indicación explícita del usuario, no
// convive con el nuevo módulo de Asesorías 1:1 — ver docs/proyectafacil-asesorias.md). Las claves
// de permiso 'mentorias.acceder'/'mentorias.preguntas_respuestas' se borran de permisos_catalogo;
// el FK de usuario_permisos/roles_permisos_base a permisos_catalogo.clave tiene ON DELETE CASCADE,
// así que cualquier override que las usara desaparece solo.
class DropMentoriasGrupales extends Migration
{
    public function up()
    {
        $this->forge->dropTable('preguntas_mentoria');
        $this->forge->dropTable('mentoria_inscripciones');
        $this->forge->dropTable('sesiones_mentoria');

        $this->db->table('permisos_catalogo')->whereIn('clave', ['mentorias.acceder', 'mentorias.preguntas_respuestas'])->delete();
    }

    public function down()
    {
        $this->db->table('permisos_catalogo')->insert(['clave' => 'mentorias.acceder']);
        $this->db->table('permisos_catalogo')->insert(['clave' => 'mentorias.preguntas_respuestas']);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tema' => ['type' => 'VARCHAR', 'constraint' => 200],
            'mentor' => ['type' => 'VARCHAR', 'constraint' => 150],
            'fecha' => ['type' => 'DATETIME'],
            'cupos_totales' => ['type' => 'SMALLINT', 'unsigned' => true],
            'link_reunion' => ['type' => 'VARCHAR', 'constraint' => 255],
            'grabacion_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sesiones_mentoria');

        $this->forge->addField([
            'sesion_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fecha_inscripcion' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey(['sesion_id', 'usuario_id']);
        $this->forge->addForeignKey('sesion_id', 'sesiones_mentoria', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('mentoria_inscripciones');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'sesion_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'pregunta' => ['type' => 'TEXT'],
            'fecha_pregunta' => ['type' => 'DATETIME'],
            'respuesta' => ['type' => 'TEXT', 'null' => true],
            'fecha_respuesta' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sesion_id', 'sesiones_mentoria', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('preguntas_mentoria');
    }
}
