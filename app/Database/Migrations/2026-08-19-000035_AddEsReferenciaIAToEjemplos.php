<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Marca el ejemplo que la IA usa como few-shot al llenar (ver LlenadoIAController::
// valoresEjemploReferencia): un caso ya resuelto correctamente, del que se extrae — por
// identificador de campo — la FORMA/estilo esperado, no solo reglas en prosa. A lo más uno por
// plantilla (EjemplosController::marcarReferenciaIA limpia cualquier otro antes de marcar este).
class AddEsReferenciaIAToEjemplos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('ejemplos', [
            'es_referencia_ia' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'after'      => 'excel_actualizado',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('ejemplos', 'es_referencia_ia');
    }
}
