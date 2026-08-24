<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Sistema de "beneficios": entidades compradas de forma abstracta (vía Stripe, ver
// PagosController), que en un paso posterior y separado se asocian a funcionalidades concretas de
// la plataforma — si la cuenta no tiene el beneficio, no tiene acceso a lo que ese beneficio
// proteja. A diferencia de tickets_consulta (créditos consumibles: se gastan de a uno), un
// beneficio es una propiedad binaria — se tiene o no se tiene, no se consume.
//
// `beneficios` es el catálogo (nombre, precio, si es recurrente, el price_id de Stripe una vez
// creado en el Dashboard). `cuenta_beneficios` es quién posee qué — una fila por cada vez que una
// cuenta obtuvo un beneficio, con su estado real (activo/cancelado/expirado) y las referencias de
// Stripe para poder correlacionar con webhooks futuros (ej. cancelación de una suscripción).
class CreateBeneficios extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 60],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 120],
            'descripcion' => ['type' => 'TEXT', 'null' => true],
            'precio' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'recurrente' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'stripe_price_id' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('beneficios');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'cuenta_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'beneficio_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'estado' => $this->enumField(['activo', 'cancelado', 'expirado'], 'activo'),
            'stripe_checkout_session_id' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'stripe_subscription_id' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'fecha_inicio' => ['type' => 'DATETIME', 'null' => true],
            'fecha_fin' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('cuenta_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('beneficio_id', 'beneficios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cuenta_beneficios');
        $this->addEnumCheck('cuenta_beneficios', 'estado', ['activo', 'cancelado', 'expirado']);
    }

    public function down()
    {
        $this->forge->dropTable('cuenta_beneficios');
        $this->forge->dropTable('beneficios');
    }
}
