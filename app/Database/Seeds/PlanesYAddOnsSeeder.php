<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Espejo exacto de frontend/src/data/planes.ts — catálogo estático de planes y add-ons.
// `Plan.id`/`AddOn.id` (slugs 'nivel-N', 'consultoria-1a1', etc.) no se guardan como PK aquí:
// planes se referencia por `numero_nivel` y add_ons por `nombre` (ver FacturacionController).
class PlanesYAddOnsSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            [
                'numero_nivel' => 0, 'nombre' => 'Pedagógico', 'precio' => 50, 'periodicidad' => 'Único',
                'limite_fichas_base' => 2, 'limite_usuarios_base' => 1,
                'features' => [
                    'Uso para entrenar el llenado de las plantillas',
                    'Limitado a pruebas concretas y ejercicios ya diseñados',
                    'Permite experimentar con la herramienta hasta 2 ejercicios simultáneos',
                    '4 semanas activo (semanas finales)',
                ],
            ],
            [
                'numero_nivel' => 1, 'nombre' => 'Profesional', 'precio' => 150, 'periodicidad' => 'Mensual',
                'limite_fichas_base' => 3, 'limite_usuarios_base' => 1,
                'features' => [
                    'Llenado de plantillas con proyectos reales',
                    'Ayuda de la inteligencia artificial para mejorar títulos y textos',
                    'Asistencia de dónde encontrar referencias de llenado en el curso',
                    'Asesor de IA 24/7 para llenado de las plantillas',
                    'Límite de 1 usuario y hasta 3 plantillas simultáneas',
                    'Acceso a mentorías grupales en línea',
                    'Incluye todos los formatos',
                ],
            ],
            [
                'numero_nivel' => 2, 'nombre' => 'Premium', 'precio' => 250, 'periodicidad' => 'Mensual',
                'limite_fichas_base' => 10, 'limite_usuarios_base' => 3,
                'features' => [
                    'Hasta 3 usuarios colaborativos',
                    'Hasta 10 plantillas simultáneas',
                    'Histórico de cambio en las plantillas llenadas',
                    'Sugerencias inteligentes de IA',
                    'Acceso a sección de preguntas y respuestas en las mentorías grupales',
                ],
            ],
        ];

        foreach ($planes as $plan) {
            $features = $plan['features'];
            unset($plan['features']);

            $this->db->table('planes')->ignore(true)->insert($plan);
            $planId = $this->db->table('planes')->where('numero_nivel', $plan['numero_nivel'])->get()->getRow('id');

            foreach ($features as $orden => $texto) {
                $this->db->table('plan_features')->ignore(true)->insert([
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
                'descripcion' => 'Usuarios adicionales para los niveles 1 y 2',
                'precio' => 45, 'recurrente' => 1, 'niveles' => [1, 2],
            ],
            [
                'nombre' => 'Plantilla adicional',
                'descripcion' => 'Plantillas simultáneas adicionales para los niveles 1 y 2',
                'precio' => 15, 'recurrente' => 1, 'niveles' => [1, 2],
            ],
        ];

        foreach ($addOns as $addOn) {
            $niveles = $addOn['niveles'];
            unset($addOn['niveles']);

            $this->db->table('add_ons')->ignore(true)->insert($addOn);
            $addOnId = $this->db->table('add_ons')->where('nombre', $addOn['nombre'])->get()->getRow('id');

            foreach ($niveles as $nivel) {
                $this->db->table('add_on_niveles_disponibles')->ignore(true)->insert([
                    'add_on_id'    => $addOnId,
                    'numero_nivel' => $nivel,
                ]);
            }
        }
    }
}
