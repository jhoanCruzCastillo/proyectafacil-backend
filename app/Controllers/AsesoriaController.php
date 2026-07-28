<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Solicitudes de asesoría 1:1 cliente↔docente (chat o videollamada por link externo) y sus
// mensajes. El cliente elige un docente específico (ver DocentesController) y crea la solicitud;
// el docente la acepta/rechaza; una vez aceptada, ambos pueden intercambiar mensajes vía polling —
// no hay WebSockets en el proyecto. Cada transición relevante deja una notificación para la otra
// parte (ver NotificacionesController).
class AsesoriaController extends BaseController
{
    public function misSolicitudes(): ResponseInterface
    {
        $usuarioId = (int) ($this->request->getGet('usuarioId') ?? 0);
        $rol       = (string) ($this->request->getGet('rol') ?? 'cliente');
        $columna   = $rol === 'docente' ? 'sa.docente_id' : 'sa.cliente_id';

        $filas = db_connect()->table('solicitudes_asesoria sa')
            ->select('sa.*, c.nombre as cliente_nombre, d.nombre as docente_nombre')
            ->join('usuarios c', 'c.id = sa.cliente_id')
            ->join('usuarios d', 'd.id = sa.docente_id')
            ->where($columna, $usuarioId)
            ->orderBy('sa.created_at', 'DESC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDtoSolicitud'], $filas));
    }

    public function crear(): ResponseInterface
    {
        $dto = $this->request->getJSON(true) ?? [];
        $db  = db_connect();

        $db->table('solicitudes_asesoria')->insert([
            'cliente_id'      => (int) ($dto['clienteId'] ?? 0),
            'docente_id'      => (int) ($dto['docenteId'] ?? 0),
            'ejemplo_id'      => ! empty($dto['ejemploId']) ? (int) $dto['ejemploId'] : null,
            'tipo'            => (string) ($dto['tipo'] ?? 'chat'),
            'estado'          => 'pendiente',
            'mensaje_inicial' => $dto['mensajeInicial'] ?? null,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        $id = $db->insertID();

        $solicitud = $this->fila((int) $id);
        $this->notificar(
            (int) $solicitud['docente_id'],
            'solicitud_asesoria',
            'Tienes una nueva solicitud de asesoría',
            (int) $id,
        );

        return $this->response->setJSON($this->toDtoSolicitud($solicitud));
    }

    public function aceptar($id = null): ResponseInterface
    {
        $id  = (int) $id;
        $dto = $this->request->getJSON(true) ?? [];
        $db  = db_connect();

        $db->table('solicitudes_asesoria')->where('id', $id)->update([
            'estado'       => 'aceptada',
            'link_reunion' => $dto['linkReunion'] ?? null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $solicitud = $this->fila($id);
        $this->notificar((int) $solicitud['cliente_id'], 'solicitud_aceptada', 'Tu solicitud de asesoría fue aceptada', $id);

        return $this->response->setJSON($this->toDtoSolicitud($solicitud));
    }

    public function rechazar($id = null): ResponseInterface
    {
        $id = (int) $id;
        $db = db_connect();

        $db->table('solicitudes_asesoria')->where('id', $id)->update([
            'estado'     => 'rechazada',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $solicitud = $this->fila($id);
        $this->notificar((int) $solicitud['cliente_id'], 'solicitud_rechazada', 'Tu solicitud de asesoría fue rechazada', $id);

        return $this->response->setJSON($this->toDtoSolicitud($solicitud));
    }

    public function finalizar($id = null): ResponseInterface
    {
        $id = (int) $id;
        db_connect()->table('solicitudes_asesoria')->where('id', $id)->update([
            'estado'     => 'finalizada',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON($this->toDtoSolicitud($this->fila($id)));
    }

    public function mensajes($solicitudId = null): ResponseInterface
    {
        $filas = db_connect()->table('mensajes_asesoria')
            ->where('solicitud_id', (int) $solicitudId)
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDtoMensaje'], $filas));
    }

    public function enviarMensaje($solicitudId = null): ResponseInterface
    {
        $solicitudId = (int) $solicitudId;
        $dto         = $this->request->getJSON(true) ?? [];
        $autorId     = (int) ($dto['autorId'] ?? 0);
        $db          = db_connect();

        $db->table('mensajes_asesoria')->insert([
            'solicitud_id' => $solicitudId,
            'autor_id'     => $autorId,
            'texto'        => (string) ($dto['texto'] ?? ''),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $solicitud   = $this->fila($solicitudId);
        $destinoId   = $autorId === (int) $solicitud['cliente_id'] ? (int) $solicitud['docente_id'] : (int) $solicitud['cliente_id'];
        $this->notificar($destinoId, 'nuevo_mensaje', 'Tienes un nuevo mensaje de asesoría', $solicitudId);

        $filas = $db->table('mensajes_asesoria')->where('solicitud_id', $solicitudId)->orderBy('created_at', 'ASC')->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDtoMensaje'], $filas));
    }

    private function fila(int $id): array
    {
        return db_connect()->table('solicitudes_asesoria')->where('id', $id)->get()->getRowArray();
    }

    private function notificar(int $usuarioId, string $tipo, string $mensaje, int $solicitudId): void
    {
        db_connect()->table('notificaciones')->insert([
            'usuario_id'      => $usuarioId,
            'tipo'            => $tipo,
            'mensaje'         => $mensaje,
            'referencia_tipo' => 'solicitud_asesoria',
            'referencia_id'   => $solicitudId,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    private function toDtoSolicitud(array $s): array
    {
        return [
            'id'             => (string) $s['id'],
            'clienteId'      => (string) $s['cliente_id'],
            'clienteNombre'  => $s['cliente_nombre'] ?? null,
            'docenteId'      => (string) $s['docente_id'],
            'docenteNombre'  => $s['docente_nombre'] ?? null,
            'ejemploId'      => $s['ejemplo_id'] !== null ? (string) $s['ejemplo_id'] : null,
            'tipo'           => $s['tipo'],
            'estado'         => $s['estado'],
            'mensajeInicial' => $s['mensaje_inicial'],
            'linkReunion'    => $s['link_reunion'],
            'creadoEn'       => $this->datetimeAIso($s['created_at']),
            'actualizadoEn'  => $s['updated_at'] !== null ? $this->datetimeAIso($s['updated_at']) : null,
        ];
    }

    private function toDtoMensaje(array $m): array
    {
        return [
            'id'          => (string) $m['id'],
            'solicitudId' => (string) $m['solicitud_id'],
            'autorId'     => (string) $m['autor_id'],
            'texto'       => $m['texto'],
            'creadoEn'    => $this->datetimeAIso($m['created_at']),
        ];
    }

    private function datetimeAIso(string $valor): string
    {
        return str_replace(' ', 'T', $valor);
    }
}
