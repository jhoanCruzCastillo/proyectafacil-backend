<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Antes, para armar el prompt de sistema de CUALQUIER ficha, LlenadoIAController resolvía cada paso
// buscando por NOMBRE LITERAL reservado ("Rol del asistente de IA", "Prompt del sistema", "Reglas de
// llenado automático con IA") o trayendo TODOS los generales de la plantilla (contexto general) —
// hardcodeado, sin forma de que el admin decida qué insumo puntual va en cada paso. Esta tabla
// exterioriza esa decisión: por plantilla y por paso (1=rol, 2=prompt del sistema, 4=reglas de
// llenado, 5=contexto general — los únicos 4 pasos "asignables"; 3/6/7/8 quedan fuera de esta tabla
// porque son fijos en código, vienen del cliente, o se editan desde otro pilar), el admin puede
// asociar uno o más insumos reales (una fila de `contextos_ia_general` o de `contextos_ia_globales`).
//
// El ORDEN de armado del prompt (construirSistema()/construirSistemaTabla()) NO cambia con esto —
// sigue fijo en el código. Lo único que se vuelve configurable es QUÉ insumo específico llena cada
// paso. Si una plantilla no tiene ninguna fila acá para un paso dado, el backend cae al
// comportamiento de siempre (buscar por nombre reservado / traer todos los generales) — ver el
// fallback en cada método de LlenadoIAController — así que crear esta tabla vacía no rompe nada de
// lo que ya funciona hoy.
class CreateContextosIAPasos extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'plantilla_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'paso'         => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true],
            'tipo_insumo'  => $this->enumField(['general', 'global']),
            'insumo_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'orden'        => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['plantilla_id', 'paso']);
        $this->forge->addForeignKey('plantilla_id', 'plantillas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('contextos_ia_pasos');
        $this->addEnumCheck('contextos_ia_pasos', 'tipo_insumo', ['general', 'global']);
    }

    public function down()
    {
        $this->forge->dropTable('contextos_ia_pasos');
    }
}
