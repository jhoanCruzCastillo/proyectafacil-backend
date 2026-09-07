<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Texto ya extraído de cada PDF de "Contexto general" (ver CreateContextosIAArchivos) — pedido
// explícito del usuario: estos PDFs pasan a usarse como último recurso en "ayúdame a llenar/
// verificar el campo X" (AsistenteIAController::ayudaCampo) cuando ni la fuente de la verdad del
// cliente ni las guías del admin bastan. Se extrae una vez (al subir, o de forma perezosa la
// primera vez que se necesita) y se cachea acá — mismo motivo que fuente_verdad_archivos.contenido_texto:
// evita re-descargar y re-parsear el PDF en cada consulta del chat.
class AddContenidoTextoAContextosIAArchivos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('contextos_ia_archivos', [
            'contenido_texto' => ['type' => 'TEXT', 'null' => true, 'after' => 'url'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('contextos_ia_archivos', 'contenido_texto');
    }
}
