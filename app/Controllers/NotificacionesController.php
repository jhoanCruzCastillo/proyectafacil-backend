<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Inbox simple por usuario (leído vía polling desde la campanita del Sidebar) — hoy solo lo llena
// AsesoriaController al crear/aceptar/rechazar una solicitud o al llegar un mensaje nuevo.
class NotificacionesController extends BaseController
{
    public function index(): ResponseInterface
    {
        $usuarioId = (int) ($this->request->getGet('usuarioId') ?? 0);

        $filas = db_connect()->table('notificaciones')
            ->where('usuario_id', $usuarioId)
            ->orderBy('created_at', 'DESC')
            ->get(30)->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function marcarLeida($id = null): ResponseInterface
    {
        db_connect()->table('notificaciones')->where('id', (int) $id)->update([
            'leida_en' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['ok' => true]);
    }

    private function toDto(array $n): array
    {
        return [
            'id'             => (string) $n['id'],
            'usuarioId'      => (string) $n['usuario_id'],
            'tipo'           => $n['tipo'],
            'mensaje'        => $n['mensaje'],
            'referenciaTipo' => $n['referencia_tipo'],
            'referenciaId'   => $n['referencia_id'] !== null ? (string) $n['referencia_id'] : null,
            'leidaEn'        => $n['leida_en'] !== null ? $this->datetimeAIso($n['leida_en']) : null,
            'creadoEn'       => $this->datetimeAIso($n['created_at']),
        ];
    }

    // Ver AsesoriaController/Support/SolicitudAsesoriaHelpersTrait::datetimeAIso — el sufijo 'Z' es
    // necesario para que el navegador interprete estas fechas como UTC y no como hora local.
    private function datetimeAIso(string $valor): string
    {
        return str_replace(' ', 'T', $valor) . 'Z';
    }
}
