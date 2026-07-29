<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Especialidades del asesor (docs/proyectafacil-asesorias.md §3.3): uno o más de los 13 sectores
// MEF, autogestionados por el propio asesor desde su pantalla — el Administrador solo los ve
// (tabla "Docentes", de solo lectura, sin poder editarlos).
class EspecialidadesAsesorController extends BaseController
{
    public function index($usuarioId = null): ResponseInterface
    {
        return $this->response->setJSON($this->sectorIds((int) $usuarioId));
    }

    public function guardar($usuarioId = null): ResponseInterface
    {
        $usuarioId = (int) $usuarioId;
        $dto = $this->request->getJSON(true) ?? [];
        $sectorIds = array_map('intval', (array) ($dto['sectorIds'] ?? []));

        $db = db_connect();
        $db->table('asesor_especialidades')->where('usuario_id', $usuarioId)->delete();
        foreach (array_unique($sectorIds) as $sectorId) {
            $db->table('asesor_especialidades')->insert(['usuario_id' => $usuarioId, 'sector_id' => $sectorId]);
        }

        return $this->response->setJSON($this->sectorIds($usuarioId));
    }

    private function sectorIds(int $usuarioId): array
    {
        $filas = db_connect()->table('asesor_especialidades')
            ->select('sector_id')
            ->where('usuario_id', $usuarioId)
            ->get()->getResultArray();

        return array_map(static fn (array $f) => (string) $f['sector_id'], $filas);
    }
}
