<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// "Fuente de la verdad" de una ficha de cliente: documentos (PDF/TXT/MD) y texto libre que describen
// el proyecto real, usados como contexto principal para que la IA llene la ficha automáticamente
// (ver ContextosIAController para el contexto de CÓMO llenar cada sección — esto es el QUÉ llenar).
//
// No reutiliza la tabla `archivos`: ese `ejemplo_id` es único (1 fila = la copia de Excel del
// ejemplo), y acá se necesitan varios archivos por ejemplo.
class CreateFuenteVerdad extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ejemplo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 200],
            // 'pdf' | 'txt' | 'md' — se valida en el controlador, no como ENUM de BD (portabilidad
            // Postgres/MariaDB, ver docs).
            'extension' => ['type' => 'VARCHAR', 'constraint' => 10],
            'url' => ['type' => 'VARCHAR', 'constraint' => 500],
            // Texto ya extraído del archivo (tal cual para .txt/.md, parseado para .pdf) — así el
            // llenado con IA no tiene que descargar y volver a parsear el archivo en cada corrida.
            'contenido_texto' => ['type' => 'TEXT', 'null' => true],
            'tamano_bytes' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ejemplo_id', 'ejemplos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('fuente_verdad_archivos');

        // Texto adicional libre ("contexto sobre el proyecto") — uno por ejemplo, vive directo en la
        // fila igual que otros campos de texto de `ejemplos`, no amerita tabla aparte.
        $this->forge->addColumn('ejemplos', [
            'fuente_verdad_texto' => ['type' => 'TEXT', 'null' => true, 'after' => 'compartida'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('ejemplos', 'fuente_verdad_texto');
        $this->forge->dropTable('fuente_verdad_archivos');
    }
}
