<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Cuenta cuántas veces estadoLoteFicha() reintentó recrear el batch en OpenAI ante el error
// transitorio "Cannot find file... or organization does not have access to it" — confirmado en vivo
// (2026-08-30): un batch recién creado a veces falla porque el worker que lo procesa todavía no
// puede leer el archivo recién subido, aunque GET /v1/files/{id} ya lo muestre como 'processed'. Sin
// este contador, cada poll (cada 15s desde el frontend) reintentaría para siempre en vez de rendirse
// después de un par de intentos.
class AddReintentosALlenadoIALotes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('llenado_ia_lotes', [
            'reintentos' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0, 'after' => 'openai_file_id'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('llenado_ia_lotes', 'reintentos');
    }
}
