<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Espejo de frontend/src/data/mentorias.ts — sesiones de muestra (prototipo sin integración real
// de video/calendario). 'usr-3' del mock corresponde al cliente sembrado por UsuariosSeeder
// (usuario='cliente', id=3).
class MentoriasSeeder extends Seeder
{
    public function run(): void
    {
        $cliente = $this->db->table('usuarios')->where('usuario', 'cliente')->get()->getRow('id');

        $sesiones = [
            [
                'tema'          => 'Introducción a la formulación con el Formato 6A',
                'mentor'        => 'Ing. Rocío Salazar',
                'fecha'         => '2026-07-10 18:00:00',
                'cupos_totales' => 12,
                'link_reunion'  => 'https://zoom.us/j/8123456788',
                'grabacion_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'inscritos'     => [$cliente],
                'preguntas'     => [
                    [
                        'usuario_id'      => $cliente,
                        'pregunta'        => '¿Esto aplica también para proyectos de agua y saneamiento?',
                        'fecha_pregunta'  => '2026-07-10 19:10:00',
                        'respuesta'       => 'Sí, la misma lógica de brecha aplica — solo cambia el indicador de producto según el sector.',
                        'fecha_respuesta' => '2026-07-11 09:00:00',
                    ],
                ],
            ],
            [
                'tema'          => 'Cómo armar el árbol de causas-efectos',
                'mentor'        => 'Ing. Rocío Salazar',
                'fecha'         => '2026-07-17 18:00:00',
                'cupos_totales' => 12,
                'link_reunion'  => 'https://zoom.us/j/8123456789',
                'grabacion_url' => null,
                'inscritos'     => [],
                'preguntas'     => [],
            ],
            [
                'tema'          => 'Brecha de servicio: demanda, oferta y proyección',
                'mentor'        => 'Ing. Rocío Salazar',
                'fecha'         => '2026-07-21 19:00:00',
                'cupos_totales' => 15,
                'link_reunion'  => 'https://meet.google.com/abc-defg-hij',
                'grabacion_url' => null,
                'inscritos'     => [],
                'preguntas'     => [],
            ],
            [
                'tema'          => 'Costos y cronograma en el Excel del Formato 6A',
                'mentor'        => 'Econ. Luis Farfán',
                'fecha'         => '2026-07-24 18:30:00',
                'cupos_totales' => 10,
                'link_reunion'  => 'https://zoom.us/j/8123456790',
                'grabacion_url' => null,
                'inscritos'     => [],
                'preguntas'     => [],
            ],
            [
                'tema'          => 'Preguntas y respuestas: dudas generales de formulación',
                'mentor'        => 'Ing. Rocío Salazar',
                'fecha'         => '2026-07-29 18:00:00',
                'cupos_totales' => 20,
                'link_reunion'  => 'https://meet.google.com/xyz-uvwq-rst',
                'grabacion_url' => null,
                'inscritos'     => [],
                'preguntas'     => [],
            ],
        ];

        foreach ($sesiones as $sesion) {
            $inscritos = $sesion['inscritos'];
            $preguntas = $sesion['preguntas'];
            unset($sesion['inscritos'], $sesion['preguntas']);

            $this->db->table('sesiones_mentoria')->insert($sesion);
            $sesionId = $this->db->insertID();

            foreach ($inscritos as $usuarioId) {
                if (! $usuarioId) {
                    continue;
                }
                $this->db->table('mentoria_inscripciones')->insert([
                    'sesion_id'         => $sesionId,
                    'usuario_id'        => $usuarioId,
                    'fecha_inscripcion' => $sesion['fecha'],
                ]);
            }

            foreach ($preguntas as $p) {
                if (! $p['usuario_id']) {
                    continue;
                }
                $p['sesion_id'] = $sesionId;
                $this->db->table('preguntas_mentoria')->insert($p);
            }
        }
    }
}
