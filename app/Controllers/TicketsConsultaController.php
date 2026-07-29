<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// El "crédito" real detrás de una solicitud de asesoría (docs/proyectafacil-asesorias.md §3.1).
// La emisión (emitirTicketsDePlan/emitirTicketsDeAddon) la dispara FacturacionController al
// activar un plan o comprar el add-on "Consultoría 1 a 1" — este controller solo expone la
// consulta del saldo. Reserva/consumo/liberación se agregan en el módulo de solicitud guiada.
class TicketsConsultaController extends BaseController
{
    // Duración fija por modalidad al momento de emitir una ficha — placeholder razonable hasta que
    // el negocio defina valores reales por plan (docs/proyectafacil-asesorias.md §2 no los detalla).
    private const DURACION_CHAT_MIN  = 30;
    private const DURACION_VIDEO_MIN = 45;

    public function index($usuarioId = null): ResponseInterface
    {
        $filas = db_connect()->table('tickets_consulta')
            ->where('usuario_id', (int) $usuarioId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    // Emite tickets de origen 'plan' la primera vez que un usuario tiene facturación con ese plan —
    // no reemite si ya existe al menos uno (evita duplicar en cada guardado de Facturación). El
    // reseteo por ciclo de renovación (no acumulable) queda pendiente de un disparador de
    // renovación real; por ahora la emisión es de una sola vez por activación.
    public static function emitirTicketsDePlan(int $usuarioId, int $planId): void
    {
        $db = db_connect();

        $yaTiene = $db->table('tickets_consulta')
            ->where('usuario_id', $usuarioId)
            ->where('origen', 'plan')
            ->countAllResults() > 0;
        if ($yaTiene) {
            return;
        }

        $plan = $db->table('planes')->where('id', $planId)->get()->getRowArray();
        $cupo = (int) ($plan['limite_consultas_base'] ?? 0);
        if ($cupo <= 0) {
            return;
        }

        self::insertarTickets($usuarioId, 'plan', $cupo);
    }

    // Emite la diferencia entre la cantidad de add-on ya comprada (tickets ya emitidos) y la nueva
    // cantidad — perpetuos, sin fecha_expira. Nunca resta si la cantidad baja (ya emitidos quedan).
    public static function emitirTicketsDeAddon(int $usuarioId, int $cantidadNueva): void
    {
        $db = db_connect();

        $yaEmitidos = $db->table('tickets_consulta')
            ->where('usuario_id', $usuarioId)
            ->where('origen', 'addon')
            ->countAllResults();

        $faltantes = $cantidadNueva - $yaEmitidos;
        if ($faltantes <= 0) {
            return;
        }

        self::insertarTickets($usuarioId, 'addon', $faltantes);
    }

    // Reparte la cantidad emitida entre las dos fichas (chat/video) — mitad y mitad, la unidad
    // impar (cupos de plan de número impar) va a chat. Sin una regla de negocio distinta definida
    // todavía por plan/add-on (docs/proyectafacil-asesorias.md §2 solo da un total), es el reparto
    // más neutral posible.
    private static function insertarTickets(int $usuarioId, string $origen, int $cantidad): void
    {
        $cantidadChat  = (int) ceil($cantidad / 2);
        $cantidadVideo = $cantidad - $cantidadChat;

        self::insertarTicketsDeModalidad($usuarioId, $origen, 'chat', self::DURACION_CHAT_MIN, $cantidadChat);
        self::insertarTicketsDeModalidad($usuarioId, $origen, 'video', self::DURACION_VIDEO_MIN, $cantidadVideo);
    }

    private static function insertarTicketsDeModalidad(int $usuarioId, string $origen, string $modalidad, int $duracionMinutos, int $cantidad): void
    {
        $db    = db_connect();
        $ahora = date('Y-m-d H:i:s');

        for ($i = 0; $i < $cantidad; $i++) {
            $db->table('tickets_consulta')->insert([
                'usuario_id'       => $usuarioId,
                'origen'           => $origen,
                'estado'           => 'disponible',
                'modalidad'        => $modalidad,
                'duracion_minutos' => $duracionMinutos,
                'created_at'       => $ahora,
                'updated_at'       => $ahora,
            ]);
        }
    }

    private function toDto(array $fila): array
    {
        return [
            'id'                  => (string) $fila['id'],
            'usuarioId'           => (string) $fila['usuario_id'],
            'origen'              => $fila['origen'],
            'estado'              => $fila['estado'],
            'modalidad'           => $fila['modalidad'],
            'duracionMinutos'     => (int) $fila['duracion_minutos'],
            'fechaExpira'         => $fila['fecha_expira'],
            'solicitudAsesoriaId' => $fila['solicitud_asesoria_id'] !== null ? (string) $fila['solicitud_asesoria_id'] : null,
            'creadoEn'            => str_replace(' ', 'T', (string) $fila['created_at']) . 'Z',
        ];
    }
}
