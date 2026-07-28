<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

class CreatePlantillasTable extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        // plantillas: sin asignado_archivo_id todavía — se agrega por ALTER en 2026-07-16-000007,
        // una vez que la tabla unificada `archivos` existe (necesita a su vez que `ejemplos` ya
        // exista, porque `archivos` referencia tanto plantillas como ejemplos).
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'sector_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'codigo' => ['type' => 'VARCHAR', 'constraint' => 30],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 200],
            'descripcion' => ['type' => 'TEXT'],
            'instrumento' => $this->enumField(['formato', 'ioarr', 'ficha_tecnica', 'perfil']),
            'fecha_actualizacion' => ['type' => 'DATE'],
            'archivo_default_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'disponible_nivel0' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codigo');
        $this->forge->addForeignKey('sector_id', 'sectores', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('plantillas');
        $this->addEnumCheck('plantillas', 'instrumento', ['formato', 'ioarr', 'ficha_tecnica', 'perfil']);

        // plantilla_tipologia_ioarr — descompone Plantilla.tipologiasIoarr[] (1FN)
        $this->forge->addField([
            'plantilla_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipologia' => $this->enumField(['optimizacion', 'ampliacion_marginal', 'reposicion', 'rehabilitacion']),
        ]);
        $this->forge->addPrimaryKey(['plantilla_id', 'tipologia']);
        $this->forge->addForeignKey('plantilla_id', 'plantillas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('plantilla_tipologia_ioarr');
        $this->addEnumCheck('plantilla_tipologia_ioarr', 'tipologia', ['optimizacion', 'ampliacion_marginal', 'reposicion', 'rehabilitacion']);
    }

    public function down()
    {
        $this->forge->dropTable('plantilla_tipologia_ioarr');
        $this->forge->dropTable('plantillas');
    }
}
