<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Extiende Stripe real (ya usado para "beneficios", ver 2026-08-24-000040_CreateBeneficios) al
// Plan y los Add-ons — pedido explícito del usuario: "el punto dos no debe estar simulado, debe
// ser funcional". `stripe_price_id` en planes/add_ons resuelve qué cobrar; las columnas nuevas en
// facturaciones/facturacion_addons guardan las referencias reales de Stripe (Customer,
// Subscription, y el ítem de suscripción específico de cada línea, para poder hacer swap de plan
// o ajustar la cantidad de un add-on sin afectar los demás ítems de la misma suscripción).
class AddStripeAFacturacion extends Migration
{
    public function up()
    {
        $this->forge->addColumn('planes', [
            'stripe_price_id' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'periodicidad'],
        ]);
        $this->forge->addColumn('add_ons', [
            'stripe_price_id' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'recurrente'],
        ]);
        $this->forge->addColumn('facturaciones', [
            'stripe_customer_id'          => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'stripe_subscription_id'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'stripe_subscription_item_id' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
        ]);
        $this->forge->addColumn('facturacion_addons', [
            'stripe_subscription_item_id' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('facturacion_addons', 'stripe_subscription_item_id');
        $this->forge->dropColumn('facturaciones', ['stripe_customer_id', 'stripe_subscription_id', 'stripe_subscription_item_id']);
        $this->forge->dropColumn('add_ons', 'stripe_price_id');
        $this->forge->dropColumn('planes', 'stripe_price_id');
    }
}
