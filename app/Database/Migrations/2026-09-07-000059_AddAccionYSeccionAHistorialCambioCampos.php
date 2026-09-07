<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Rediseño del "Historial de cambios" pedido por el usuario: tabla filtrable/ordenable con columnas
// Acción (Editó/Autocompletó/Eliminó) y Campo/Sección — hoy historial_cambio_campos solo guardaba
// identificador+etiqueta+valores, sin saber de qué sección venía el campo ni si el cambio lo hizo
// una persona o el llenado con IA.
class AddAccionYSeccionAHistorialCambioCampos extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->forge->addColumn('historial_cambio_campos', [
            // 'editado' por defecto: los ~cambios ya guardados antes de este campo no distinguían
            // origen, y la inmensa mayoría de cambios reales son ediciones manuales.
            'accion'         => $this->enumField(['editado', 'autocompletado', 'eliminado'], 'editado'),
            'seccion_numero' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'seccion_nombre' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
        ]);
        $this->addEnumCheck('historial_cambio_campos', 'accion', ['editado', 'autocompletado', 'eliminado']);
    }

    public function down()
    {
        $this->forge->dropColumn('historial_cambio_campos', ['accion', 'seccion_numero', 'seccion_nombre']);
    }
}
