<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Estado de publicación, independiente entre una plantilla y cada uno de sus ejemplos: una
// plantilla archivada puede tener ejemplos publicados y viceversa. Por defecto ambos nacen
// 'archivado' — el admin decide explícitamente cuándo publicarlos.
class AddEstadoToPlantillasYEjemplos extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->forge->addColumn('plantillas', [
            'estado' => $this->enumField(['publicado', 'archivado'], 'archivado') + ['after' => 'disponible_nivel0'],
        ]);
        $this->addEnumCheck('plantillas', 'estado', ['publicado', 'archivado']);

        $this->forge->addColumn('ejemplos', [
            'estado' => $this->enumField(['publicado', 'archivado'], 'archivado') + ['after' => 'compartida'],
        ]);
        $this->addEnumCheck('ejemplos', 'estado', ['publicado', 'archivado']);
    }

    public function down()
    {
        $this->db->query('ALTER TABLE ejemplos DROP CONSTRAINT chk_ejemplos_estado');
        $this->forge->dropColumn('ejemplos', 'estado');
        $this->db->query('ALTER TABLE plantillas DROP CONSTRAINT chk_plantillas_estado');
        $this->forge->dropColumn('plantillas', 'estado');
    }
}
