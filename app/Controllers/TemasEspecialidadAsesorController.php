<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Temas de especialidad que el asesor marca como propios (tabla `asesor_temas_especialidad`,
// usuario_id + tema_id → temas_especialidad) — SEPARADO de EspecialidadesAsesorController
// (`asesor_especialidades`, sectores MEF, sigue siendo la que usa el aviso automático por sector).
// Mismo patrón: catálogo global, selección del asesor con reemplazo total (borra e inserta).
class TemasEspecialidadAsesorController extends BaseController
{
    public function index($usuarioId = null): ResponseInterface
    {
        return $this->response->setJSON($this->temaIds((int) $usuarioId));
    }

    public function guardar($usuarioId = null): ResponseInterface
    {
        $usuarioId = (int) $usuarioId;
        $dto = $this->request->getJSON(true) ?? [];
        $temaIds = array_map('intval', (array) ($dto['temaIds'] ?? []));

        $db = db_connect();
        $db->table('asesor_temas_especialidad')->where('usuario_id', $usuarioId)->delete();
        foreach (array_unique($temaIds) as $temaId) {
            $db->table('asesor_temas_especialidad')->insert(['usuario_id' => $usuarioId, 'tema_id' => $temaId]);
        }

        return $this->response->setJSON($this->temaIds($usuarioId));
    }

    private function temaIds(int $usuarioId): array
    {
        $filas = db_connect()->table('asesor_temas_especialidad')
            ->select('tema_id')
            ->where('usuario_id', $usuarioId)
            ->get()->getResultArray();

        return array_map(static fn (array $f) => (string) $f['tema_id'], $filas);
    }
}
