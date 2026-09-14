<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Especialidad del asesor por TEMA (Proyectos de Inversión, Ejecución de Obras...) — separada de
// `asesor_especialidades` (sectores MEF, sigue intacta y es la que usa el aviso automático por
// sector, ver SolicitudAsesoriaHelpersTrait::asesoresPorSector). El asesor marca AMBAS cosas desde
// "Temas de especialidad": "Sectores que atiendes" (asesor_especialidades) y esta, informativa por
// ahora — mismo patrón exacto que CreateAsesorEspecialidades (2026-08-02), solo que apunta a
// `temas_especialidad` en vez de `sectores`.
class CreateAsesorTemasEspecialidad extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tema_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['usuario_id', 'tema_id']);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('tema_id', 'temas_especialidad', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('asesor_temas_especialidad');
    }

    public function down()
    {
        $this->forge->dropTable('asesor_temas_especialidad');
    }
}
