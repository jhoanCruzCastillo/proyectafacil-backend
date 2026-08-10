<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Contexto que consume la IA al ayudar a llenar una ficha. Lo redacta el administrador desde el
// panel "Contextos IA" del editor de plantillas; el cliente nunca lo ve ni lo edita, solo se
// beneficia de él cuando pregunta en el chat (ver AsesorIAController).
class ContextosIAController extends BaseController
{
    /** Todo lo que el panel necesita para una plantilla: contextos por sección + catálogo global. */
    public function index($plantillaId = null): ResponseInterface
    {
        $plantillaId = (int) $plantillaId;
        $db = db_connect();

        $secciones = $db->table('contextos_ia_seccion')
            ->where('plantilla_id', $plantillaId)
            ->get()->getResultArray();

        $asociaciones = [];
        if ($secciones !== []) {
            $filas = $db->table('contexto_seccion_globales')
                ->whereIn('contexto_seccion_id', array_column($secciones, 'id'))
                ->get()->getResultArray();
            foreach ($filas as $f) {
                $asociaciones[(int) $f['contexto_seccion_id']][] = (string) $f['contexto_global_id'];
            }
        }

        return $this->response->setJSON([
            'secciones' => array_map(static fn (array $s) => [
                'seccionId' => (string) $s['seccion_id'],
                'markdown'  => (string) ($s['markdown'] ?? ''),
                'globales'  => $asociaciones[(int) $s['id']] ?? [],
                'actualizadoEn' => $s['updated_at'] !== null ? str_replace(' ', 'T', (string) $s['updated_at']) . 'Z' : null,
            ], $secciones),
            'globales' => $this->globales(),
        ]);
    }

    /** Upsert del contexto de una sección + sus contextos globales asociados. */
    public function guardar($plantillaId = null, $seccionId = null): ResponseInterface
    {
        $plantillaId = (int) $plantillaId;
        $seccionId   = (string) $seccionId;
        $dto         = $this->request->getJSON(true) ?? [];
        $markdown    = (string) ($dto['markdown'] ?? '');
        $globales    = array_map('intval', (array) ($dto['globales'] ?? []));

        $db    = db_connect();
        $ahora = date('Y-m-d H:i:s');

        $existente = $db->table('contextos_ia_seccion')
            ->where('plantilla_id', $plantillaId)
            ->where('seccion_id', $seccionId)
            ->get()->getRowArray();

        if ($existente === null) {
            $db->table('contextos_ia_seccion')->insert([
                'plantilla_id' => $plantillaId,
                'seccion_id'   => $seccionId,
                'markdown'     => $markdown,
                'created_at'   => $ahora,
                'updated_at'   => $ahora,
            ]);
            $contextoId = (int) $db->insertID();
        } else {
            $contextoId = (int) $existente['id'];
            $db->table('contextos_ia_seccion')->where('id', $contextoId)->update([
                'markdown'   => $markdown,
                'updated_at' => $ahora,
            ]);
        }

        // Reemplazo total, igual criterio que especialidades/horarios: es una lista corta y así no
        // hay que calcular diferencias ni arrastrar asociaciones huérfanas.
        $db->table('contexto_seccion_globales')->where('contexto_seccion_id', $contextoId)->delete();
        foreach (array_unique($globales) as $globalId) {
            $db->table('contexto_seccion_globales')->insert([
                'contexto_seccion_id' => $contextoId,
                'contexto_global_id'  => $globalId,
            ]);
        }

        return $this->index($plantillaId);
    }

    public function eliminar($plantillaId = null, $seccionId = null): ResponseInterface
    {
        db_connect()->table('contextos_ia_seccion')
            ->where('plantilla_id', (int) $plantillaId)
            ->where('seccion_id', (string) $seccionId)
            ->delete();

        return $this->index((int) $plantillaId);
    }

    public function indexGlobales(): ResponseInterface
    {
        return $this->response->setJSON($this->globales());
    }

    public function guardarGlobal($id = null): ResponseInterface
    {
        $dto      = $this->request->getJSON(true) ?? [];
        $nombre   = trim((string) ($dto['nombre'] ?? ''));
        $markdown = (string) ($dto['markdown'] ?? '');
        $icono    = $dto['icono'] ?? null;
        if ($nombre === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'El nombre es obligatorio']);
        }

        $db    = db_connect();
        $ahora = date('Y-m-d H:i:s');
        $datos = ['nombre' => $nombre, 'icono' => $icono, 'markdown' => $markdown, 'updated_at' => $ahora];

        if ($id === null || (int) $id === 0) {
            $db->table('contextos_ia_globales')->insert($datos + ['created_at' => $ahora]);
        } else {
            $db->table('contextos_ia_globales')->where('id', (int) $id)->update($datos);
        }

        return $this->response->setJSON($this->globales());
    }

    private function globales(): array
    {
        $filas = db_connect()->table('contextos_ia_globales g')
            ->select('g.*, (SELECT COUNT(*) FROM contexto_seccion_globales sg WHERE sg.contexto_global_id = g.id) AS usos', false)
            ->orderBy('g.nombre', 'ASC')
            ->get()->getResultArray();

        return array_map(static fn (array $g) => [
            'id'       => (string) $g['id'],
            'nombre'   => $g['nombre'],
            'icono'    => $g['icono'],
            'markdown' => (string) ($g['markdown'] ?? ''),
            'usos'     => (int) $g['usos'],
            'actualizadoEn' => $g['updated_at'] !== null ? str_replace(' ', 'T', (string) $g['updated_at']) . 'Z' : null,
        ], $filas);
    }
}
