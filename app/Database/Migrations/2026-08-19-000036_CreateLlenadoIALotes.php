<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Un lote = una corrida de "Llenar toda la ficha" enviada como Batch API de OpenAI (~50% más barato
// que la llamada síncrona de siempre, a cambio de no tener respuesta inmediata — OpenAI no garantiza
// cuándo termina, aunque en la práctica suele ser bastante antes de las 24h del plazo). El botón
// individual "Llenar con IA" de un campo/tabla suelto sigue yendo por el camino síncrono de siempre
// (llenarFicha/llenarTabla) — un lote no tiene sentido para una sola llamada, y perdería la
// iteración rápida (probar, ver, corregir) que ese botón necesita.
//
// `mapeo_json` guarda, por cada `custom_id` que se mandó en el lote, lo mínimo para poder aplicar su
// resultado cuando el lote termine: de qué sección/tabla es, y qué se necesita para persistirlo o
// validarlo (ver LlenadoIAController::enviarLoteFicha/procesarLoteFicha) — se recalcula el resto
// (columnas, catálogo, etc.) contra la plantilla en ese momento en vez de duplicarlo aquí, para no
// quedar con una copia vieja de la estructura si esta cambió entre que se envió el lote y terminó.
class CreateLlenadoIALotes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ejemplo_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'openai_batch_id'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'openai_file_id'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            // enviado -> procesando -> completado | error | cancelado (estados propios, no calcados
            // 1:1 de los de OpenAI — ver mapearEstadoLoteOpenAI en el controlador).
            'estado'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'enviado'],
            'mapeo_json'       => ['type' => 'TEXT'],
            // Se llena una sola vez, cuando el lote pasa a 'completado' — así un GET repetido del
            // cliente (polling) no vuelve a descargar ni reprocesar el archivo de resultados de
            // OpenAI cada vez, solo lee esto.
            'resultado_json'   => ['type' => 'TEXT', 'null' => true],
            'error'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ejemplo_id');
        $this->forge->createTable('llenado_ia_lotes');
    }

    public function down()
    {
        $this->forge->dropTable('llenado_ia_lotes');
    }
}
