<?php

namespace App\Controllers;

use App\Models\SesionModel;
use CodeIgniter\HTTP\ResponseInterface;

// Tab "Sesiones" del panel de detalles de usuario — sesión actual + historial, con revocación
// real (ver AuthToken::hidratarSesionSiHay(), que rechaza cualquier token cuya fila esté
// revocada). Cada quien solo ve/gestiona SUS PROPIAS sesiones — nunca las de otro usuario, ni
// siquiera un superusuario, por eso todo acá se filtra por session()->get('usuario_id'), no por
// un id de la URL.
class SesionesController extends BaseController
{
    public function misSesiones(): ResponseInterface
    {
        $usuarioId    = (int) session()->get('usuario_id');
        $sesionActual = (string) session()->get('sesion_id');

        $filas = (new SesionModel())
            ->where('usuario_id', $usuarioId)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->response->setJSON(array_map(fn (array $f) => $this->toDto($f, $sesionActual), $filas));
    }

    public function cerrar($id = null): ResponseInterface
    {
        $usuarioId = (int) session()->get('usuario_id');
        $model     = new SesionModel();
        $fila      = $model->find((int) $id);

        if (! $fila || (int) $fila['usuario_id'] !== $usuarioId) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Sesión no encontrada']);
        }

        $model->update((int) $id, ['revocada' => 1, 'revocada_en' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON(['cerrada' => true]);
    }

    private function toDto(array $fila, string $sesionActualId): array
    {
        $esActual = (string) $fila['id'] === $sesionActualId;
        $revocada = (int) $fila['revocada'] === 1;

        return [
            'id'              => (string) $fila['id'],
            'dispositivo'     => $fila['dispositivo'] ?? 'Desconocido',
            'navegador'       => $fila['navegador'] ?? 'Desconocido',
            'ip'              => $fila['ip'] ?? null,
            'ubicacion'       => $fila['ubicacion'] ?? null,
            'esActual'        => $esActual,
            'activa'          => ! $revocada,
            'iniciadaEn'      => $this->aIso($fila['created_at']),
            'ultimaActividad' => $fila['ultima_actividad'] !== null ? $this->aIso($fila['ultima_actividad']) : null,
            'revocadaEn'      => $fila['revocada_en'] !== null ? $this->aIso($fila['revocada_en']) : null,
        ];
    }

    private function aIso(string $fechaMysql): string
    {
        return date(DATE_ATOM, strtotime($fechaMysql));
    }
}
