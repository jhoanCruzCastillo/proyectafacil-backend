<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Indica si la copia de Excel del ejemplo está al día con sus valores JSON.
// false = hubo ediciones (o nunca se insertó) y hace falta "Insertar"; true = Excel sincronizado.
// No guarda historial ni autor — solo el bit de estado para el indicador del header.
class AddExcelActualizadoToEjemplos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('ejemplos', [
            'excel_actualizado' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'after'      => 'estado',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('ejemplos', 'excel_actualizado');
    }
}
