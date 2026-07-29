<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Módulo 6 (docs/proyectafacil-asesorias.md §4 Fase 5): calificación del alumno al completar y
// autorización de pago del honorario al asesor. `pago_autorizado_en` vive en la solicitud (no en
// una tabla de liquidaciones aparte) porque el honorario es fijo ($550) y se autoriza en bloque por
// asesor+periodo — no hace falta más estructura que "cuándo se autorizó este ticket".
class AddCierreASolicitudAsesoria extends Migration
{
    public function up()
    {
        $this->forge->addColumn('solicitudes_asesoria', [
            'calificacion' => ['type' => 'SMALLINT', 'null' => true, 'after' => 'link_reunion'],
            'calificacion_comentario' => ['type' => 'TEXT', 'null' => true, 'after' => 'calificacion'],
            'pago_autorizado_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'calificacion_comentario'],
        ]);
        $this->db->query(
            'ALTER TABLE solicitudes_asesoria ADD CONSTRAINT chk_solicitudes_asesoria_calificacion '
            . 'CHECK (calificacion IS NULL OR (calificacion >= 1 AND calificacion <= 5))',
        );
    }

    public function down()
    {
        $this->db->query('ALTER TABLE solicitudes_asesoria DROP CONSTRAINT chk_solicitudes_asesoria_calificacion');
        $this->forge->dropColumn('solicitudes_asesoria', ['calificacion', 'calificacion_comentario', 'pago_autorizado_en']);
    }
}
