<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMentorias extends Migration
{
    public function up()
    {
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

        // mentoria_inscripciones — descompone SesionMentoria.inscritos[]
        $this->forge->addField([
            'sesion_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fecha_inscripcion' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey(['sesion_id', 'usuario_id']);
        $this->forge->addForeignKey('sesion_id', 'sesiones_mentoria', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('mentoria_inscripciones');

        // preguntas_mentoria
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

    public function down()
    {
        $this->forge->dropTable('preguntas_mentoria');
        $this->forge->dropTable('mentoria_inscripciones');
        $this->forge->dropTable('sesiones_mentoria');
    }
}
