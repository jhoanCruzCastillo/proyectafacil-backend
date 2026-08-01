<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Fecha real en que la asesoría se cerró como completada. Hasta ahora los reportes de liquidación
// usaban `updated_at` como proxy, pero esa columna se mueve con CUALQUIER cambio posterior de la
// fila (una calificación del alumno, una autorización de pago), así que los totales por periodo
// cambiaban solos con el tiempo. En una pantalla de dinero eso no es aceptable: se necesita una
// fecha que se escriba una sola vez y no vuelva a moverse.
//
// Backfill: para las filas ya completadas se copia `updated_at`, que es la mejor aproximación
// disponible de forma retroactiva.
class AddCompletadoEnASolicitudes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('solicitudes_asesoria', [
            'completado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->db->table('solicitudes_asesoria')
            ->where('estado', 'completado')
            ->where('completado_en IS NULL', null, false)
            ->update(['completado_en' => new \CodeIgniter\Database\RawSql('updated_at')]);
    }

    public function down()
    {
        $this->forge->dropColumn('solicitudes_asesoria', 'completado_en');
    }
}
