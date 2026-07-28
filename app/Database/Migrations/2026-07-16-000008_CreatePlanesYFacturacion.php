<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

class CreatePlanesYFacturacion extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        // planes
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'numero_nivel' => ['type' => 'TINYINT', 'unsigned' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 80],
            'precio' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'periodicidad' => ['type' => 'VARCHAR', 'constraint' => 20],
            'limite_fichas_base' => ['type' => 'SMALLINT', 'unsigned' => true],
            'limite_usuarios_base' => ['type' => 'TINYINT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('numero_nivel');
        $this->forge->createTable('planes');

        // plan_features — descompone Plan.features[]
        $this->forge->addField([
            'plan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'orden' => ['type' => 'TINYINT', 'unsigned' => true],
            'feature_texto' => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addPrimaryKey(['plan_id', 'orden']);
        $this->forge->addForeignKey('plan_id', 'planes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('plan_features');

        // add_ons
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 100],
            'descripcion' => ['type' => 'TEXT'],
            'precio' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'recurrente' => ['type' => 'TINYINT', 'constraint' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('add_ons');

        // add_on_niveles_disponibles — descompone AddOn.nivelesDisponibles[]
        $this->forge->addField([
            'add_on_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'numero_nivel' => ['type' => 'TINYINT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['add_on_id', 'numero_nivel']);
        $this->forge->addForeignKey('add_on_id', 'add_ons', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('add_on_niveles_disponibles');

        // facturaciones — 1:1 con usuarios (solo titulares). Sin columnas plan/precio: se leen por
        // JOIN a planes (ver 3FN en docs/database-design.md — el prototipo las duplicaba).
        $this->forge->addField([
            'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'plan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'cancelada' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'fecha_renovacion' => ['type' => 'DATE', 'null' => true],
            'fecha_inicio_plan' => ['type' => 'DATETIME', 'null' => true],
            'metodo_pago' => $this->enumField(['tarjeta', 'yape', 'plin', 'mercado_pago', '360pay']),
            'tarjeta_marca' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'tarjeta_ultimos4' => ['type' => 'CHAR', 'constraint' => 4, 'null' => true],
            'telefono_pago' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('usuario_id');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('plan_id', 'planes', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('facturaciones');
        $this->addEnumCheck('facturaciones', 'metodo_pago', ['tarjeta', 'yape', 'plin', 'mercado_pago', '360pay']);

        // facturas
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'facturacion_usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fecha' => ['type' => 'DATE'],
            'total' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'estado' => $this->enumField(['Pagado', 'Pendiente']),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('facturacion_usuario_id', 'facturaciones', 'usuario_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('facturas');
        $this->addEnumCheck('facturas', 'estado', ['Pagado', 'Pendiente']);

        // facturacion_addons — descompone FacturacionMock.addons: Record<addonId, cantidad>
        $this->forge->addField([
            'facturacion_usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'add_on_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'cantidad' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey(['facturacion_usuario_id', 'add_on_id']);
        $this->forge->addForeignKey('facturacion_usuario_id', 'facturaciones', 'usuario_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('add_on_id', 'add_ons', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('facturacion_addons');
    }

    public function down()
    {
        $this->forge->dropTable('facturacion_addons');
        $this->forge->dropTable('facturas');
        $this->forge->dropTable('facturaciones');
        $this->forge->dropTable('add_on_niveles_disponibles');
        $this->forge->dropTable('add_ons');
        $this->forge->dropTable('plan_features');
        $this->forge->dropTable('planes');
    }
}
