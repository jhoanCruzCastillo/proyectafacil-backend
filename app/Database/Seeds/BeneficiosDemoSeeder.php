<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Beneficio de ejemplo para probar de punta a punta el flujo de compra con Stripe (ver
// PagosController/BeneficiosController) antes de decidir qué beneficios reales se van a vender ni
// qué funcionalidad va a proteger cada uno — eso es un paso posterior y separado.
// `stripe_price_id` queda vacío a propósito: se completa a mano una vez creado el Producto/Price
// correspondiente en el Dashboard de Stripe (modo prueba). Mientras esté vacío, el botón "Comprar"
// se muestra deshabilitado en el frontend.
//
// Idempotente — se puede correr de nuevo sin duplicar filas.
// Uso: php spark db:seed BeneficiosDemoSeeder
class BeneficiosDemoSeeder extends Seeder
{
    private const BENEFICIOS = [
        [
            'slug'            => 'ia-avanzada',
            'nombre'          => 'Acceso a IA avanzada',
            'descripcion'     => 'Desbloquea las funciones de llenado y sugerencias con IA en todas tus fichas.',
            'precio'          => 9.99,
            'recurrente'      => 1,
            // Producto+Price de PRUEBA ya creados en la cuenta de Stripe del usuario
            // (prod_V89dW5TxBMKTBA) — sin esto el botón "Comprar" queda deshabilitado.
            'stripe_price_id' => 'price_1U7tKDGzUEUBDTFMH2UoqHio',
        ],
    ];

    public function run(): void
    {
        $ahora = date('Y-m-d H:i:s');

        foreach (self::BENEFICIOS as $b) {
            $existe = $this->db->table('beneficios')->where('slug', $b['slug'])->get()->getRowArray();
            if ($existe) {
                // Idempotente pero no ciego: si el price_id de Stripe cambió (ej. se recreó el
                // producto), se actualiza en vez de dejar la fila vieja sin comprar nunca.
                if ($existe['stripe_price_id'] !== $b['stripe_price_id']) {
                    $this->db->table('beneficios')->where('id', $existe['id'])->update([
                        'stripe_price_id' => $b['stripe_price_id'],
                        'updated_at'      => $ahora,
                    ]);
                }
                continue;
            }

            $this->db->table('beneficios')->insert([
                'slug'            => $b['slug'],
                'nombre'          => $b['nombre'],
                'descripcion'     => $b['descripcion'],
                'precio'          => $b['precio'],
                'recurrente'      => $b['recurrente'],
                'stripe_price_id' => $b['stripe_price_id'],
                'activo'          => 1,
                'created_at'      => $ahora,
                'updated_at'      => $ahora,
            ]);
        }

        echo "Listo — beneficios de ejemplo sembrados.\n";
    }
}
