<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// cursos — categoriza a los usuarios cliente/alumno por el curso al que pertenecen (pedido del
// cliente para la pestaña "Clientes - Alumnos" de Usuarios y permisos: chips de filtro + botón
// "Crear curso"). Tabla independiente en vez de un enum en `usuarios` porque el admin necesita
// poder crear cursos nuevos desde la UI sin tocar código — mismo criterio que `sectores`
// (color_accent para el chip, nombre único).
class CreateCursos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nombre'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'color_accent' => ['type' => 'VARCHAR', 'constraint' => 20],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nombre');
        $this->forge->createTable('cursos');

        // Puntero opcional desde usuarios — solo tiene sentido para rol='cliente'/origen='alumno',
        // pero no se restringe a nivel de columna (igual que tipo_usuario_id): la UI es la que
        // decide cuándo mostrarlo/editarlo.
        $this->forge->addColumn('usuarios', [
            'curso_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'origen'],
        ]);
        $this->forge->addForeignKey('curso_id', 'cursos', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('usuarios');
    }

    public function down()
    {
        $this->forge->dropForeignKey('usuarios', 'usuarios_curso_id_foreign');
        $this->forge->dropColumn('usuarios', 'curso_id');
        $this->forge->dropTable('cursos');
    }
}
