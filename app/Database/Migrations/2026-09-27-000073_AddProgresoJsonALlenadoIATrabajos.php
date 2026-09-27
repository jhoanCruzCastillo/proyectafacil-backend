<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Progreso por SECCIÓN de un llenado con IA en curso, para el modal que el cliente ve mientras el
// worker corre (barra de %, lista de secciones con completada/en proceso/pendiente, contador de
// campos). Hasta ahora lo único que había era `progreso_texto`, un VARCHAR(255) de texto libre — no
// alcanza para dibujar una lista por sección, y además llegaba tarde: el reporte de progreso se
// emitía DESPUÉS de que el lote entero terminara (un solo call site de $reportar, fuera de
// ejecutarLoteEnParalelo), así que el cliente veía "Enviando N solicitudes…" congelado varios
// minutos y después el contador saltaba de 0 a N de golpe.
//
// Ahora el progreso se emite desde adentro del lote, cuando cada solicitud aterriza de verdad (ver
// ejecutarTandaEnParalelo + curl_multi_info_read). `progreso_texto` se mantiene tal cual para no
// romper a nadie que ya lo lea; esta columna es la que tiene la forma completa.
//
// TEXT y no JSONB a propósito: es el mismo criterio que `seccion_ids` y `resultado_json` de esta
// misma tabla — nunca se consulta por dentro desde SQL, solo se escribe entero y se lee entero.
class AddProgresoJsonALlenadoIATrabajos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('llenado_ia_trabajos', [
            'progreso_json' => ['type' => 'TEXT', 'null' => true, 'after' => 'progreso_texto'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('llenado_ia_trabajos', 'progreso_json');
    }
}
