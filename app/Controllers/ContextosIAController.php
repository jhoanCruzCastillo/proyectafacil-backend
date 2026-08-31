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
    /**
     * Nombres reservados que LlenadoIAController busca por nombre literal cuando un paso NO tiene
     * ninguna asignación explícita en `contextos_ia_pasos` (ver rolAsistente()/promptDelSistemaDe()/
     * reglasLlenado() en ese controlador). Duplicados a propósito acá (y en
     * frontend/src/features/editor/contextosIaNombres.ts) — este controlador solo necesita el NOMBRE
     * para mostrar en la tab "Estructura" cuál insumo está usando el sistema por defecto, no resuelve
     * el prompt real.
     */
    private const NOMBRE_ROL_ASISTENTE   = 'Rol del asistente de IA';
    private const NOMBRE_PROMPT_SISTEMA  = 'Prompt del sistema';
    private const NOMBRE_REGLAS_LLENADO  = 'Reglas de llenado automático con IA';

    /**
     * Texto base para el pilar "Prompt del sistema" cuando el admin aprieta "Restaurar predeterminado"
     * — antes vivía hardcodeado en el frontend (promptSistemaPredeterminado.ts), duplicando ahí el rol
     * y el contrato JSON que YA están fijos en LlenadoIAController::construirSistema()/
     * construirSistemaTabla() para TODA llamada real a la IA. Vivir en el backend evita ese doble
     * mantenimiento y permite recortar lo que ya está garantizado por código en vez de repetirlo en
     * prosa (ver los comentarios inline de abajo, sección por sección, sobre qué se quitó y por qué).
     */
    // $plantillaId no se usa hoy en el contenido — se mantiene en la firma/ruta por si en el
    // futuro el predeterminado necesita variar por tipo de plantilla. A propósito el texto NO
    // menciona el nombre/sector de la ficha: eso vive en "Contexto general" (que sí es propio de
    // cada ficha) — mezclarlo acá rompía la idea de que "Prompt del sistema" es el mismo texto para
    // cualquier ficha de cualquier sector (ver conversación real: encontrado porque el admin notó
    // que el contenido real de esta plantilla nombraba "FTE — Servicios de Cuidado Diurno" adentro
    // de lo que se supone es genérico).
    public function promptSistemaPredeterminado($plantillaId = null): ResponseInterface
    {
        return $this->response->setJSON(['markdown' => $this->construirPromptPredeterminado()]);
    }

    private function construirPromptPredeterminado(): string
    {
        $partes = [
            'El detalle conceptual de la ficha en curso vive en el **contexto general** (ahí se ' .
                'identifica de qué formato y sector se trata); el detalle campo a campo vive en la ' .
                '**guía de la sección**; la forma exacta de cada campo vive en el **JSON de la ' .
                'sección**. Este documento, en cambio, no cambia entre fichas — son las reglas de ' .
                'comportamiento que aplican sin importar el sector (defensa, agronomía, salud, etc.).',

            "## Reglas específicas de este formato\n\n" .
                // 1 y 2 (hardcodeadas: "no inventes ids", "no_encontrado si no hay evidencia") y el rol
                // ya van SIEMPRE antes de esto — ver construirSistema(). Acá solo lo que no está
                // garantizado por código.
                "1. **No completes \"por completar\".** Mejor un campo vacío/no encontrado que un valor " .
                "plausible sin soporte.\n" .
                "2. **Respeta tipos y opciones del JSON.** Si hay catálogo cerrado o etiquetas booleanas, " .
                "usa exactamente esas cadenas.\n" .
                "3. **No modifiques campos `editable: false` ni `calculado`** — puedes leerlos como " .
                "contexto, nunca sobrescribirlos.\n" .
                "4. **Tablas y jerarquías:** conserva la estructura del schema (filas, columnas, hijos). " .
                "Completa celdas; no agregues ni borres filas de catálogo fijo salvo que la guía lo " .
                "permita explícitamente.\n" .
                "5. **Coordenadas:** si el tipo es `coordenadas`, no lo aplanes a un string salvo que el " .
                "schema lo pida.\n" .
                "6. **Una sección a la vez.** Ignora campos de otras secciones aunque aparezcan en los " .
                "documentos.\n" .
                "7. **Cita evidencia.** Todo valor propuesto debe poder rastrearse a un documento o dato " .
                "del cliente.\n" .
                "8. **Si dos documentos se contradicen**, marca el campo como conflictivo — no elijas un " .
                "lado sin señalarlo.\n" .
                '9. **Verifica la unidad antes de proponer un número** — el nombre del campo no basta ' .
                '("Costo", "Cantidad"); revisa si trae una nota "IMPORTANTE:" (ej. "costo ANUAL, no ' .
                'mensual") y convierte el dato si la fuente lo trae en otra unidad.',

            "## Qué buscar en los documentos del cliente\n\n" .
                "- Datos explícitos: nombres, códigos, montos, fechas, Ubigeo, direcciones.\n" .
                "- Entidades, ubicación (departamento/provincia/distrito), cifras y unidades.\n" .
                "- Condiciones de cumplimiento (sí/no), tablas y listados.\n" .
                "- Referencias a normas o fuentes oficiales.\n\n" .
                'No uses conocimiento genérico del mundo para inventar datos del proyecto concreto ' .
                '(población, montos, nombres de responsables, etc.) — si no está en la fuente de la ' .
                'verdad, no existe para este llenado.',

            "## Qué NO debes hacer\n\n" .
                "- No reescribas el schema ni cambies identificadores de campo.\n" .
                "- No inventes opciones fuera del catálogo del campo.\n" .
                "- No copies valores de una alternativa técnica a otra, ni del ejemplo genérico del " .
                "instructivo como si fueran del proyecto real del cliente.\n" .
                '- No "rellenes" campos calculados (totales, ratios, leyendas autoarmadas).',

            "## Criterio de calidad\n\n" .
                'Un buen resultado deja vacíos los huecos reales, cita evidencia verificable, respeta ' .
                'opciones y tipos, y es coherente con la guía de la sección — el éxito se mide por ' .
                'fidelidad a la evidencia y al schema, no por cuántos campos se llenan.',
        ];

        return implode("\n\n---\n\n", $partes);
    }

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
            'generales'     => $this->generales($plantillaId),
            'globales'      => $this->globales(),
            'pasos'         => $this->pasos($plantillaId),
            'pasosFallback' => $this->pasosFallback($plantillaId),
        ]);
    }

    // --- Pasos (qué insumo va en cada paso del armado del prompt de sistema, tab "Estructura") ---
    // Solo 1/2/4/5 admiten insumos — igual lista que LlenadoIAController::PASOS_ASIGNABLES (duplicada
    // a propósito: ese controlador resuelve el prompt real, este solo persiste la asignación).

    /** Asigna un insumo (general o global) a un paso de esta plantilla — no reemplaza lo que ya
     * hubiera asignado, se suma (ver concatenarInsumos() en LlenadoIAController). */
    public function guardarPaso($plantillaId = null, $paso = null): ResponseInterface
    {
        $plantillaId = (int) $plantillaId;
        $paso        = (int) $paso;
        $dto         = $this->request->getJSON(true) ?? [];
        $tipo        = (string) ($dto['tipo'] ?? '');
        $insumoId    = (int) ($dto['insumoId'] ?? 0);

        if (! in_array($paso, [1, 2, 4, 5], true)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Este paso no admite insumos asignables']);
        }
        if (! in_array($tipo, ['general', 'global'], true) || $insumoId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta tipo o insumoId válido']);
        }

        $db = db_connect();
        $yaAsignado = $db->table('contextos_ia_pasos')
            ->where('plantilla_id', $plantillaId)->where('paso', $paso)
            ->where('tipo_insumo', $tipo)->where('insumo_id', $insumoId)
            ->countAllResults() > 0;
        if (! $yaAsignado) {
            $maxOrden = $db->table('contextos_ia_pasos')
                ->selectMax('orden')->where('plantilla_id', $plantillaId)->where('paso', $paso)
                ->get()->getRowArray()['orden'] ?? 0;
            $db->table('contextos_ia_pasos')->insert([
                'plantilla_id' => $plantillaId,
                'paso'         => $paso,
                'tipo_insumo'  => $tipo,
                'insumo_id'    => $insumoId,
                'orden'        => (int) $maxOrden + 1,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->index($plantillaId);
    }

    public function eliminarPaso($plantillaId = null, $asignacionId = null): ResponseInterface
    {
        db_connect()->table('contextos_ia_pasos')
            ->where('id', (int) $asignacionId)
            ->where('plantilla_id', (int) $plantillaId)
            ->delete();

        return $this->index((int) $plantillaId);
    }

    /** Insumos asignados a cada paso (1/2/4/5), resueltos a su nombre — para pintar la tab
     * "Estructura" sin que el frontend tenga que cruzar `tipo_insumo`+`insumo_id` contra generales y
     * globales por su cuenta. */
    private function pasos(int $plantillaId): array
    {
        $filas = db_connect()->table('contextos_ia_pasos')
            ->where('plantilla_id', $plantillaId)
            ->orderBy('paso', 'ASC')->orderBy('orden', 'ASC')
            ->get()->getResultArray();
        if ($filas === []) {
            return [];
        }

        $idsGeneral = array_column(array_filter($filas, static fn (array $f): bool => $f['tipo_insumo'] === 'general'), 'insumo_id');
        $idsGlobal  = array_column(array_filter($filas, static fn (array $f): bool => $f['tipo_insumo'] === 'global'), 'insumo_id');
        $db         = db_connect();
        $generales  = $idsGeneral !== [] ? $db->table('contextos_ia_general')->select('id, nombre')->whereIn('id', $idsGeneral)->get()->getResultArray() : [];
        $globales   = $idsGlobal !== [] ? $db->table('contextos_ia_globales')->select('id, nombre')->whereIn('id', $idsGlobal)->get()->getResultArray() : [];
        $nombrePorId = ['general' => array_column($generales, 'nombre', 'id'), 'global' => array_column($globales, 'nombre', 'id')];

        $out = [];
        foreach ($filas as $f) {
            $nombre = $nombrePorId[$f['tipo_insumo']][$f['insumo_id']] ?? null;
            if ($nombre === null) {
                continue; // insumo borrado después de asignarlo — fila huérfana, se ignora
            }
            $out[] = [
                'id'       => (string) $f['id'],
                'paso'     => (int) $f['paso'],
                'tipo'     => $f['tipo_insumo'],
                'insumoId' => (string) $f['insumo_id'],
                'nombre'   => $nombre,
            ];
        }

        return $out;
    }

    /**
     * Qué insumo está usando REALMENTE el sistema en cada paso 1/2/4/5 cuando no hay ninguna
     * asignación explícita en `contextos_ia_pasos` (ver pasos() arriba) — mismo criterio exacto que el
     * fallback de LlenadoIAController: paso 1 busca el global reservado por nombre, paso 2 busca el
     * general reservado de ESTA plantilla, paso 4 busca el global reservado, paso 5 trae TODOS los
     * generales de la plantilla menos el de paso 2. Sin esto, la tab "Estructura" mostraba "ningún
     * insumo asignado" para un paso que en realidad SÍ tiene contenido real llenándolo en cada
     * llamada — encontrado en vivo: el admin no tenía forma de saber qué estaba usando el paso 1 si
     * nunca lo había tocado desde acá.
     */
    private function pasosFallback(int $plantillaId): array
    {
        $db  = db_connect();
        $out = [];

        $rol = $db->table('contextos_ia_globales')->select('id, nombre')->where('nombre', self::NOMBRE_ROL_ASISTENTE)->get()->getRowArray();
        if ($rol !== null) {
            $out[] = ['paso' => 1, 'tipo' => 'global', 'insumoId' => (string) $rol['id'], 'nombre' => $rol['nombre']];
        }

        $promptSistema = $db->table('contextos_ia_general')->select('id, nombre')
            ->where('plantilla_id', $plantillaId)->where('nombre', self::NOMBRE_PROMPT_SISTEMA)
            ->get()->getRowArray();
        if ($promptSistema !== null) {
            $out[] = ['paso' => 2, 'tipo' => 'general', 'insumoId' => (string) $promptSistema['id'], 'nombre' => $promptSistema['nombre']];
        }

        $reglas = $db->table('contextos_ia_globales')->select('id, nombre')->where('nombre', self::NOMBRE_REGLAS_LLENADO)->get()->getRowArray();
        if ($reglas !== null) {
            $out[] = ['paso' => 4, 'tipo' => 'global', 'insumoId' => (string) $reglas['id'], 'nombre' => $reglas['nombre']];
        }

        $generales = $db->table('contextos_ia_general')->select('id, nombre')
            ->where('plantilla_id', $plantillaId)->where('nombre !=', self::NOMBRE_PROMPT_SISTEMA)
            ->get()->getResultArray();
        foreach ($generales as $g) {
            $out[] = ['paso' => 5, 'tipo' => 'general', 'insumoId' => (string) $g['id'], 'nombre' => $g['nombre']];
        }

        return $out;
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
