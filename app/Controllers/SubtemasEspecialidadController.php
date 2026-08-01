<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Segundo nivel de las especialidades del asesor: dentro de cada sector MEF ("tema"), qué subtemas
// específicos atiende — p. ej. dentro de Formatos Generales: "Liquidación por contrata".
// Mismo patrón que EspecialidadesAsesorController: el catálogo es global y la selección del asesor
// se guarda con reemplazo total (borra e inserta), no con diff incremental.
class SubtemasEspecialidadController extends BaseController
{
    /** Catálogo completo de subtemas activos, agrupable por sector en el frontend. */
    public function index(): ResponseInterface
    {
        $filas = db_connect()->table('subtemas_especialidad')
            ->select('id, sector_id, nombre')
            ->where('activo', 1)
            ->orderBy('sector_id', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map(static fn (array $f) => [
            'id'       => (string) $f['id'],
            'sectorId' => (string) $f['sector_id'],
            'nombre'   => $f['nombre'],
        ], $filas));
    }

    public function delAsesor($usuarioId = null): ResponseInterface
    {
        return $this->response->setJSON($this->subtemaIds((int) $usuarioId));
    }

    public function guardarDelAsesor($usuarioId = null): ResponseInterface
    {
        $usuarioId = (int) $usuarioId;
        $dto = $this->request->getJSON(true) ?? [];
        $subtemaIds = array_map('intval', (array) ($dto['subtemaIds'] ?? []));

        $db = db_connect();
        $db->table('asesor_subtemas')->where('usuario_id', $usuarioId)->delete();
        foreach (array_unique($subtemaIds) as $subtemaId) {
            $db->table('asesor_subtemas')->insert(['usuario_id' => $usuarioId, 'subtema_id' => $subtemaId]);
        }

        return $this->response->setJSON($this->subtemaIds($usuarioId));
    }

    private function subtemaIds(int $usuarioId): array
    {
        $filas = db_connect()->table('asesor_subtemas')
            ->select('subtema_id')
            ->where('usuario_id', $usuarioId)
            ->get()->getResultArray();

        return array_map(static fn (array $f) => (string) $f['subtema_id'], $filas);
    }
}
