<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// El backend nunca guardó el cupo de consultas por plan (frontend/src/data/planes.ts sí lo tiene
// como limiteConsultasBase, pero solo se usaba client-side) — el módulo de Asesorías necesita esta
// cifra server-side para emitir los tickets de consulta al activar/renovar un plan.
class AddLimiteConsultasAPlanes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('planes', [
            'limite_consultas_base' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0, 'after' => 'limite_fichas_base'],
        ]);

        $this->db->query('UPDATE planes SET limite_consultas_base = 1 WHERE numero_nivel = 0');
        $this->db->query('UPDATE planes SET limite_consultas_base = 3 WHERE numero_nivel = 1');
        $this->db->query('UPDATE planes SET limite_consultas_base = 6 WHERE numero_nivel = 2');
    }

    public function down()
    {
        $this->forge->dropColumn('planes', 'limite_consultas_base');
    }
}
