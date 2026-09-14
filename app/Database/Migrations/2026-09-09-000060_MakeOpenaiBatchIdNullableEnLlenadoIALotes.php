<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// El llenado automático de ficha completa migró de la Batches API de OpenAI a Kimi (2026-09-09, ver
// Config\Ia) — Kimi no tiene equivalente a esa API, así que enviarLoteFicha() ahora resuelve todo el
// lote EN PARALELO (curl_multi) dentro del mismo request en vez de crear un job asíncrono, y nunca
// tiene un `openai_batch_id` real que guardar. La columna era NOT NULL (obligatoria en el INSERT
// original de enviarLoteFicha) — se vuelve NULLABLE para que el insert directo a estado='completado'
// no falle. No se elimina la columna: las filas viejas (batches reales enviados a OpenAI antes de
// este cambio) la conservan como registro histórico.
class MakeOpenaiBatchIdNullableEnLlenadoIALotes extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('llenado_ia_lotes', [
            'openai_batch_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('llenado_ia_lotes', [
            'openai_batch_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
        ]);
    }
}
