<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Catálogo de "temas de especialidad" del asesor (Proyectos de Inversión, Contrataciones con el
// Estado, Ejecución de Obras...) — SEPARADO de `sectores` (los 13 sectores MEF que categorizan las
// fichas/plantillas de "Proyectos de Inversión con IA"). Antes (2026-09-13, mismo día) esto se había
// modelado reutilizando `sectores`, pero son dos conceptos distintos con dueños distintos: sectores
// clasifica FICHAS, temas_especialidad clasifica en qué puede ASESORAR un docente — pedido explícito
// del usuario tras notar que mezclarlos rompía la categorización de las fichas ya creadas. Ver
// migración siguiente (RepointSubtemasYAsesorEspecialidadATemas) para el repunte de
// subtemas_especialidad/asesor_especialidades hacia esta tabla nueva.
class CreateTemasEspecialidad extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'codigo'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'nombre'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'icono'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'color_accent' => ['type' => 'VARCHAR', 'constraint' => 20],
            'descripcion'  => ['type' => 'TEXT', 'null' => true],
            'activo'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codigo');
        $this->forge->createTable('temas_especialidad');
    }

    public function down()
    {
        $this->forge->dropTable('temas_especialidad');
    }
}
