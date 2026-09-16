<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Espejo exacto de frontend/src/data/planes.ts — catálogo estático de planes y add-ons.
// `Plan.id`/`AddOn.id` (slugs 'nivel-N', 'consultoria-1a1', etc.) no se guardan como PK aquí:
// planes se referencia por `numero_nivel` y add_ons por `nombre` (ver FacturacionController).
// `stripe_price_id`: Producto+Price de PRUEBA ya creados en la cuenta de Stripe del usuario (ver
// PagosController::checkoutPlan/checkoutAddon) — sin esto, comprar ese plan/add-on no es posible.
class PlanesYAddOnsSeeder extends Seeder
{
    private const STRIPE_PRICE_IDS_PLANES = [
        0 => 'price_1U7twFGzUEUBDTFM5cXH56vp',
        1 => 'price_1U7twGGzUEUBDTFMjOZCraKZ',
        2 => 'price_1U7twGGzUEUBDTFMnZZzQCpz',
    ];
    private const STRIPE_PRICE_IDS_ADDONS = [
        'Consultoría 1 a 1'   => 'price_1U7twHGzUEUBDTFM9KQolr2k',
        'Usuario adicional'   => 'price_1U7twIGzUEUBDTFMCxxDwDuC',
        'Plantilla adicional' => 'price_1U7twIGzUEUBDTFMRZCi98jT',
    ];

    public function run(): void
    {
        $planes = [
            [
                'numero_nivel' => 0, 'nombre' => 'Profesional', 'precio' => 400, 'periodicidad' => 'Mensual',
                'limite_fichas_base' => 3, 'limite_consultas_base' => 2, 'limite_usuarios_base' => 1,
                'features' => [
                    '2 sesiones de 1 hora',
                    'Chat ilimitado',
                    'Acceso a plantillas ILPIIE',
                    'Descuento 15% en sesiones adicionales',
                ],
            ],
            [
                'numero_nivel' => 1, 'nombre' => 'Consultora / Empresa', 'precio' => 1200, 'periodicidad' => 'Mensual',
                'limite_fichas_base' => 10, 'limite_consultas_base' => 4, 'limite_usuarios_base' => 3,
                'features' => [
                    '4 sesiones de 1 hora',
                    '1 sesión grupal (hasta 5 personas)',
                    'Chat prioritario',
                    'Acceso a IA asistente avanzada',
                    'Reporte mensual de consultas',
                ],
            ],
            [
                'numero_nivel' => 2, 'nombre' => 'Gobierno Regional / Local', 'precio' => 3500, 'periodicidad' => 'Mensual',
                'limite_fichas_base' => 20, 'limite_consultas_base' => 10, 'limite_usuarios_base' => 5,
                'features' => [
                    '10 sesiones de 1 hora',
                    'Acompañamiento en trámite específico',
                    'Reporte mensual ejecutivo',
                    'Facturación a entidad con orden de servicio',
                ],
            ],
        ];

        foreach ($planes as $plan) {
            $features = $plan['features'];
            unset($plan['features']);

            $existente = $this->db->table('planes')->where('numero_nivel', $plan['numero_nivel'])->get()->getRowArray();
            if ($existente) {
                $this->db->table('planes')->where('id', $existente['id'])->update($plan);
                $planId = $existente['id'];
            } else {
                $this->db->table('planes')->insert($plan);
                $planId = $this->db->table('planes')->where('numero_nivel', $plan['numero_nivel'])->get()->getRow('id');
            }
            $this->db->table('planes')->where('id', $planId)->update([
                'stripe_price_id' => self::STRIPE_PRICE_IDS_PLANES[$plan['numero_nivel']],
            ]);

            $this->db->table('plan_features')->where('plan_id', $planId)->delete();
            foreach ($features as $orden => $texto) {
                $this->db->table('plan_features')->insert([
                    'plan_id'       => $planId,
                    'orden'         => $orden,
                    'feature_texto' => $texto,
                ]);
            }
        }

        $addOns = [
            [
                'nombre' => 'Consultoría 1 a 1',
                'descripcion' => 'Consultoría 1 a 1 con consultor experto desde cualquier nivel.',
                'precio' => 550, 'recurrente' => 0, 'niveles' => [],
            ],
            [
                'nombre' => 'Usuario adicional',
                'descripcion' => 'Usuarios adicionales para cualquier nivel de membresía',
                'precio' => 45, 'recurrente' => 1, 'niveles' => [0, 1, 2],
            ],
            [
                'nombre' => 'Plantilla adicional',
                'descripcion' => 'Plantillas simultáneas adicionales para cualquier nivel de membresía',
                'precio' => 15, 'recurrente' => 1, 'niveles' => [0, 1, 2],
            ],
        ];

        foreach ($addOns as $addOn) {
            $niveles = $addOn['niveles'];
            unset($addOn['niveles']);

            $existente = $this->db->table('add_ons')->where('nombre', $addOn['nombre'])->get()->getRowArray();
            if ($existente) {
                $this->db->table('add_ons')->where('id', $existente['id'])->update($addOn);
                $addOnId = $existente['id'];
            } else {
                $this->db->table('add_ons')->insert($addOn);
                $addOnId = $this->db->table('add_ons')->where('nombre', $addOn['nombre'])->get()->getRow('id');
            }
            $this->db->table('add_ons')->where('id', $addOnId)->update([
                'stripe_price_id' => self::STRIPE_PRICE_IDS_ADDONS[$addOn['nombre']],
            ]);

            $this->db->table('add_on_niveles_disponibles')->where('add_on_id', $addOnId)->delete();
            foreach ($niveles as $nivel) {
                $this->db->table('add_on_niveles_disponibles')->insert([
                    'add_on_id'    => $addOnId,
                    'numero_nivel' => $nivel,
                ]);
            }
        }
    }
}
