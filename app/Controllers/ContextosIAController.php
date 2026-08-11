<?php

namespace App\Controllers;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\HTTP\ResponseInterface;

// Contexto que consume la IA al ayudar a llenar una ficha. Lo redacta el administrador desde el
// panel "Contextos IA" del editor de plantillas; el cliente nunca lo ve ni lo edita, solo se
// beneficia de él cuando pregunta en el chat (ver AsistenteIAController).
//
// Tres niveles:
//  - SECCIÓN: un markdown por (plantilla, sección). Solo aplica a esa sección de esa ficha.
//  - GENERAL: un catálogo de markdowns propio de UNA ficha (aplica a toda ella, no se comparte con
//    otras fichas ni sectores).
//  - GLOBAL: un catálogo de markdowns reutilizable por CUALQUIER ficha de CUALQUIER sector.
//
// El markdown en sí NO se guarda en la fila: se sube como archivo .md a Cloudinary (mismo patrón
// que Excel e imágenes, ver CloudinaryUploader) y la fila solo guarda la URL resultante. El
// navegador descarga y cachea ese .md por su cuenta (ver frontend/src/lib/markdownCache.ts); este
// controlador nunca necesita leer el contenido, solo moverlo hacia/desde Cloudinary.
class ContextosIAController extends BaseController
{
    /** Todo lo que el panel necesita para una plantilla: contextos por sección + generales + catálogo global. */
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
                'url'       => $s['url'] !== null ? (string) $s['url'] : null,
                'globales'  => $asociaciones[(int) $s['id']] ?? [],
                'actualizadoEn' => $s['updated_at'] !== null ? str_replace(' ', 'T', (string) $s['updated_at']) . 'Z' : null,
            ], $secciones),
            'generales' => $this->generales($plantillaId),
            'globales'  => $this->globales(),
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
        $url   = $this->subirMarkdown($markdown, "seccion-{$plantillaId}-{$seccionId}.md");

        $existente = $db->table('contextos_ia_seccion')
            ->where('plantilla_id', $plantillaId)
            ->where('seccion_id', $seccionId)
            ->get()->getRowArray();

        if ($existente === null) {
            $db->table('contextos_ia_seccion')->insert([
                'plantilla_id' => $plantillaId,
                'seccion_id'   => $seccionId,
                'url'          => $url,
                'created_at'   => $ahora,
                'updated_at'   => $ahora,
            ]);
            $contextoId = (int) $db->insertID();
        } else {
            $contextoId = (int) $existente['id'];
            $db->table('contextos_ia_seccion')->where('id', $contextoId)->update([
                'url'        => $url,
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

    // --- Generales (propios de una ficha) ---

    public function indexGenerales($plantillaId = null): ResponseInterface
    {
        return $this->response->setJSON($this->generales((int) $plantillaId));
    }

    public function guardarGeneral($plantillaId = null, $id = null): ResponseInterface
    {
        $plantillaId = (int) $plantillaId;
        $dto         = $this->request->getJSON(true) ?? [];
        $nombre      = trim((string) ($dto['nombre'] ?? ''));
        $markdown    = (string) ($dto['markdown'] ?? '');
        if ($nombre === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'El nombre es obligatorio']);
        }

        $db    = db_connect();
        $ahora = date('Y-m-d H:i:s');
        $url   = $this->subirMarkdown($markdown, "general-{$plantillaId}-{$nombre}.md");
        $datos = ['plantilla_id' => $plantillaId, 'nombre' => $nombre, 'url' => $url, 'updated_at' => $ahora];

        if ($id === null || (int) $id === 0) {
            $db->table('contextos_ia_general')->insert($datos + ['created_at' => $ahora]);
        } else {
            $db->table('contextos_ia_general')->where('id', (int) $id)->where('plantilla_id', $plantillaId)->update($datos);
        }

        return $this->response->setJSON($this->generales($plantillaId));
    }

    public function eliminarGeneral($plantillaId = null, $id = null): ResponseInterface
    {
        db_connect()->table('contextos_ia_general')
            ->where('id', (int) $id)
            ->where('plantilla_id', (int) $plantillaId)
            ->delete();

        return $this->response->setJSON($this->generales((int) $plantillaId));
    }

    private function generales(int $plantillaId): array
    {
        $filas = db_connect()->table('contextos_ia_general')
            ->where('plantilla_id', $plantillaId)
            ->orderBy('nombre', 'ASC')
            ->get()->getResultArray();

        return array_map(static fn (array $g) => [
            'id'     => (string) $g['id'],
            'nombre' => $g['nombre'],
            'url'    => $g['url'] !== null ? (string) $g['url'] : null,
            'actualizadoEn' => $g['updated_at'] !== null ? str_replace(' ', 'T', (string) $g['updated_at']) . 'Z' : null,
        ], $filas);
    }

    // --- Globales (compartidos por cualquier ficha/sector) ---

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

        // `nombre` es único en la tabla — validarlo antes de insertar en vez de dejar que reviente
        // la restricción de la BD con un error de SQL crudo.
        $duplicado = $db->table('contextos_ia_globales')->where('nombre', $nombre);
        if ($id !== null && (int) $id !== 0) {
            $duplicado->where('id !=', (int) $id);
        }
        if ($duplicado->countAllResults() > 0) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Ya existe un contexto global con ese nombre']);
        }

        $url   = $this->subirMarkdown($markdown, "global-{$nombre}.md");
        $datos = ['nombre' => $nombre, 'icono' => $icono, 'url' => $url, 'updated_at' => $ahora];

        if ($id === null || (int) $id === 0) {
            $db->table('contextos_ia_globales')->insert($datos + ['created_at' => $ahora]);
        } else {
            $db->table('contextos_ia_globales')->where('id', (int) $id)->update($datos);
        }

        return $this->response->setJSON($this->globales());
    }

    public function eliminarGlobal($id = null): ResponseInterface
    {
        // Sin limpieza manual de asociaciones: la FK de contexto_seccion_globales tiene ON DELETE
        // CASCADE (ver migración CreateContextosIA), así que se borran solas con esta fila.
        db_connect()->table('contextos_ia_globales')->where('id', (int) $id)->delete();

        return $this->response->setJSON($this->globales());
    }

    private function globales(): array
    {
        $filas = db_connect()->table('contextos_ia_globales g')
            ->select('g.*, (SELECT COUNT(*) FROM contexto_seccion_globales sg WHERE sg.contexto_global_id = g.id) AS usos', false)
            ->orderBy('g.nombre', 'ASC')
            ->get()->getResultArray();

        return array_map(static fn (array $g) => [
            'id'     => (string) $g['id'],
            'nombre' => $g['nombre'],
            'icono'  => $g['icono'],
            'url'    => $g['url'] !== null ? (string) $g['url'] : null,
            'usos'   => (int) $g['usos'],
            'actualizadoEn' => $g['updated_at'] !== null ? str_replace(' ', 'T', (string) $g['updated_at']) . 'Z' : null,
        ], $filas);
    }

    /** Sube el markdown a Cloudinary; string vacío no se sube (ahorra una llamada de red al crear algo sin contenido aún). */
    private function subirMarkdown(string $markdown, string $nombreOriginal): ?string
    {
        if (trim($markdown) === '') {
            return null;
        }

        return (new CloudinaryUploader())->subirMarkdown($markdown, $nombreOriginal);
    }
}
