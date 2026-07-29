<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Pedido explícito del usuario: que Juan Pérez (usuario 'cliente') tenga chats de asesoría ya
// ACEPTADOS (estado='asignado') con una conversación real, para poder probar visualmente el botón
// "Unirse a la conversación" + AsesoriaChatPanel. Juan ya tenía varias solicitudes 'asignado' de
// pruebas manuales anteriores del módulo de matchmaking, pero todas sin mensajes — este seeder NO
// crea solicitudes nuevas (evita duplicar), solo rellena con una conversación de ejemplo las que
// todavía tienen 0 mensajes (hasta 2, para no saturar "Mis consultas").
class ChatsActivosPruebaJuanSeeder extends Seeder
{
    public function run(): void
    {
        $juan = $this->db->table('usuarios')->where('usuario', 'cliente')->get()->getRowArray();
        if ($juan === null) {
            return;
        }
        $juanId = (int) $juan['id'];

        $activos = $this->db->table('solicitudes_asesoria')
            ->select('id, docente_id')
            ->where('cliente_id', $juanId)
            ->where('tipo', 'chat')
            ->where('estado', 'asignado')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $conversaciones = [
            [
                ['docente', '¡Hola Juan! Cuéntame, ¿en qué parte de tu ficha técnica te puedo ayudar?'],
                ['juan', 'Hola, tengo dudas sobre cómo redactar los medios fundamentales de mi proyecto educativo.'],
                ['docente', 'Claro, cuéntame primero cuál es el problema central que identificaste.'],
                ['juan', 'Es la baja calidad del servicio educativo en la institución.'],
                ['docente', 'Perfecto, entonces los medios fundamentales deberían atacar directamente las causas de eso. ¿Qué causas directas tienes listadas?'],
            ],
            [
                ['juan', 'Buenas, necesito ayuda para calcular la brecha de servicio de mi ficha de salud.'],
                ['docente', 'Con gusto. ¿Ya tienes los datos de demanda y oferta actual del servicio?'],
                ['juan', 'Tengo la demanda pero no estoy seguro de cómo calcular la oferta optimizada.'],
                ['docente', 'Sin problema, revisemos juntos la fórmula — compárteme primero el dato de oferta actual.'],
            ],
        ];

        $rellenadas = 0;
        foreach ($activos as $sol) {
            if ($rellenadas >= count($conversaciones)) {
                break;
            }

            $yaTieneMensajes = $this->db->table('mensajes_asesoria')->where('solicitud_id', $sol['id'])->countAllResults() > 0;
            if ($yaTieneMensajes) {
                continue;
            }

            $docenteId = (int) $sol['docente_id'];
            $ahora     = time();
            foreach ($conversaciones[$rellenadas] as $i => [$autor, $texto]) {
                $this->db->table('mensajes_asesoria')->insert([
                    'solicitud_id' => $sol['id'],
                    'autor_id'     => $autor === 'juan' ? $juanId : $docenteId,
                    'texto'        => $texto,
                    'created_at'   => date('Y-m-d H:i:s', $ahora + $i * 60),
                ]);
            }
            $rellenadas++;
        }
    }
}
