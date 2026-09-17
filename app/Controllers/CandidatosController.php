<?php

namespace App\Controllers;

use App\Libraries\CandidatoDocumentoStorage;
use App\Libraries\S3ObjectStore;
use App\Libraries\StreamProxy;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Encryption;
use Config\Stripe as StripeConfig;
use GuzzleHttp\Client;
use Throwable;

// Postulaciones públicas al equipo de especialistas ILPIIE Live (formulario
// /registro-especialista, ver RegistroEspecialistaPage.vue) — sección "Especialistas › Candidatos"
// del panel admin. Un candidato NO es una cuenta `usuarios`: `postular()` es la única acción
// pública de este controlador (sin filtro 'auth', ver Routes.php); todo lo demás requiere
// administrativo_asesorias/superusuario. El detalle ("Ver", CandidatoDetalleModal.vue), las notas
// internas y el cambio de estado (ver TRANSICIONES) ya son funcionales. Promover un candidato
// aprobado a una cuenta de asesor real sigue fuera de alcance — no especificado todavía.
class CandidatosController extends BaseController
{
    private const ESTADOS = ['registrado', 'en_evaluacion', 'para_entrevista', 'aprobado', 'desaprobado'];

    /** Público: recibe el wizard completo de /registro-especialista. */
    public function postular(): ResponseInterface
    {
        $dto = json_decode((string) ($this->request->getPost('datos') ?? '{}'), true) ?? [];

        $nombre    = trim((string) ($dto['nombre'] ?? ''));
        $dni       = trim((string) ($dto['dni'] ?? ''));
        $correo    = trim((string) ($dto['correo'] ?? ''));
        $telefono  = trim((string) ($dto['telefono'] ?? ''));
        $password  = (string) ($dto['password'] ?? '');
        $profesion = trim((string) ($dto['profesion'] ?? ''));
        $nivelAcademico = trim((string) ($dto['nivelAcademico'] ?? ''));
        $colegiatura    = trim((string) ($dto['colegiatura'] ?? ''));
        $experiencia    = trim((string) ($dto['experiencia'] ?? ''));
        $nivelEspecialidad = trim((string) ($dto['nivelEspecialidad'] ?? ''));
        $otrosTemas  = trim((string) ($dto['otrosTemas'] ?? ''));
        $linkedin    = trim((string) ($dto['linkedin'] ?? ''));
        $otrasRedes  = trim((string) ($dto['otrasRedes'] ?? ''));
        $comentarios = trim((string) ($dto['comentarios'] ?? ''));
        $actividades = is_array($dto['actividades'] ?? null) ? array_values(array_map('strval', $dto['actividades'])) : [];
        $temaIds     = is_array($dto['temaIds'] ?? null) ? array_map('intval', $dto['temaIds']) : [];
        $disponibilidad = is_array($dto['disponibilidad'] ?? null) ? $dto['disponibilidad'] : [];

        if ($nombre === '' || mb_strlen($nombre) < 4 || $dni === '' || mb_strlen($dni) < 8
            || ! filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($telefono) < 6
            || mb_strlen($password) < 8 || $profesion === '' || $nivelAcademico === ''
            || $experiencia === '' || ! in_array($nivelEspecialidad, ['Especialista', 'Senior', 'Altamente especializado'], true)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Completa todos los campos obligatorios del perfil.']);
        }
        if ($temaIds === [] || $actividades === [] || $disponibilidad === []) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Selecciona al menos un tema, una actividad y un bloque de disponibilidad.']);
        }

        $db = db_connect();
        if ($this->correoExisteEn('usuarios', $correo) || $this->correoExisteEn('candidatos', $correo)) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Correo ya registrado']);
        }
        if ($db->table('candidatos')->where('dni', $dni)->countAllResults() > 0) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Ya existe una postulación con ese DNI.']);
        }

        $file = $this->request->getFile('archivo');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta adjuntar el CV.']);
        }

        try {
            $cvUrl = (new CandidatoDocumentoStorage())->subirDesdeRuta(
                $file->getTempName(),
                $file->getClientName() ?: 'cv.pdf',
                $file->getClientMimeType() ?: 'application/octet-stream',
            );
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo subir el CV. Intenta de nuevo.']);
        }

        $temasValidos = $temaIds === [] ? [] : $db->table('temas_especialidad')
            ->whereIn('id', $temaIds)
            ->where('activo', 1)
            ->get()->getResultArray();

        $db->transStart();
        $db->table('candidatos')->insert([
            'nombre'             => $nombre,
            'dni'                => $dni,
            'correo'             => $correo,
            'telefono'           => $telefono,
            'password_hash'      => password_hash($password, PASSWORD_DEFAULT),
            'profesion'          => $profesion,
            'nivel_academico'    => $nivelAcademico,
            'colegiatura'        => $colegiatura !== '' ? $colegiatura : null,
            'anios_experiencia'  => $experiencia,
            'nivel_especialidad' => $nivelEspecialidad,
            'otros_temas'        => $otrosTemas !== '' ? $otrosTemas : null,
            'actividades'        => json_encode($actividades),
            'cv_url'             => $cvUrl,
            'cv_nombre_original' => $file->getClientName() ?: 'cv.pdf',
            'linkedin'           => $linkedin !== '' ? $linkedin : null,
            'otras_redes'        => $otrasRedes !== '' ? $otrasRedes : null,
            'comentarios'        => $comentarios !== '' ? $comentarios : null,
            'estado'             => 'registrado',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
        $id = $db->insertID();

        foreach ($temasValidos as $t) {
            $db->table('candidato_temas_especialidad')->ignore(true)->insert([
                'candidato_id' => $id,
                'tema_id'      => $t['id'],
            ]);
        }
        foreach ($disponibilidad as $b) {
            $dia  = (int) ($b['dia'] ?? 0);
            $hora = (int) ($b['hora'] ?? -1);
            // Mismo rango/regla que el wizard público: lunes(1)..sábado(6), 8:00-20:00, sábado
            // tarde (>=13h) bloqueado — se re-valida acá por si alguien manda el POST a mano.
            if ($dia < 1 || $dia > 6 || $hora < 8 || $hora > 20 || ($dia === 6 && $hora >= 13)) {
                continue;
            }
            $db->table('candidato_disponibilidad')->ignore(true)->insert([
                'candidato_id' => $id,
                'dia_semana'   => $dia,
                'hora_inicio'  => sprintf('%02d:00:00', $hora),
            ]);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'No se pudo registrar la postulación.']);
        }

        return $this->response->setStatusCode(201)->setJSON(['id' => (string) $id]);
    }

    /**
     * Admin: lista completa (sin paginar en el servidor) — mismo patrón que
     * TicketsAsesoriaPage.vue/useTicketsAsesoriaQuery: trae todo una vez, tabs/búsqueda/paginado
     * los resuelve el frontend. A esta escala (decenas/pocos cientos de candidatos) es más simple
     * que paginar en dos capas.
     */
    public function index(): ResponseInterface
    {
        if ($gate = $this->exigirAdminAsesorias()) {
            return $gate;
        }

        $db = db_connect();
        $filas = $db->table('candidatos')
            ->select('id, nombre, dni, correo, telefono, profesion, nivel_academico, anios_experiencia, estado, created_at')
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        $ids = array_map(static fn (array $f) => (int) $f['id'], $filas);
        $temasPorCandidato = [];
        if ($ids !== []) {
            $temas = $db->table('candidato_temas_especialidad cte')
                ->select('cte.candidato_id, t.id as tema_id, t.nombre as tema_nombre')
                ->join('temas_especialidad t', 't.id = cte.tema_id')
                ->whereIn('cte.candidato_id', $ids)
                ->get()->getResultArray();
            foreach ($temas as $t) {
                $temasPorCandidato[(int) $t['candidato_id']][] = ['id' => (string) $t['tema_id'], 'nombre' => $t['tema_nombre']];
            }
        }

        return $this->response->setJSON(
            array_map(fn (array $c) => $this->toDto($c, $temasPorCandidato[(int) $c['id']] ?? []), $filas),
        );
    }

    // El CV en el bucket (S3/Cloudinary) queda huérfano — igual que el resto del proyecto no borra
    // archivos del bucket al borrar el registro que los referencia (ver ArchivoModel/ExcelStorage),
    // no vale la pena la complejidad de un borrado remoto para este caso.
    /** Admin: elimina una postulación (candidato_temas_especialidad/disponibilidad/notas caen por CASCADE). */
    public function eliminar($id = null): ResponseInterface
    {
        if ($gate = $this->exigirAdminAsesorias()) {
            return $gate;
        }

        $db = db_connect();
        $candidato = $db->table('candidatos')->where('id', (int) $id)->get()->getRowArray();
        if (! $candidato) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Candidato no encontrado']);
        }

        $db->table('candidatos')->where('id', (int) $id)->delete();

        // 204 no debe llevar body — con setBody('') el proxy de Vite en dev lo trata como
        // respuesta malformada y lo convierte en 502 (probado en vivo). apiFetch ya maneja 204
        // sin parsear el body (ver _shared.ts), así que basta con el status code.
        return $this->response->setStatusCode(204);
    }

    /** Admin: detalle completo de un candidato — panel "Ver" (CandidatoDetalleModal.vue). */
    public function detalle($id = null): ResponseInterface
    {
        if ($gate = $this->exigirAdminAsesorias()) {
            return $gate;
        }

        $db = db_connect();
        $candidato = $db->table('candidatos')->where('id', (int) $id)->get()->getRowArray();
        if (! $candidato) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Candidato no encontrado']);
        }

        $temas = $db->table('candidato_temas_especialidad cte')
            ->select('t.id, t.nombre')
            ->join('temas_especialidad t', 't.id = cte.tema_id')
            ->where('cte.candidato_id', $candidato['id'])
            ->orderBy('t.id', 'ASC')
            ->get()->getResultArray();

        $bloques = $db->table('candidato_disponibilidad')
            ->select('dia_semana, hora_inicio')
            ->where('candidato_id', $candidato['id'])
            ->orderBy('dia_semana', 'ASC')
            ->orderBy('hora_inicio', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'id'               => (string) $candidato['id'],
            'nombre'           => $candidato['nombre'],
            'dni'              => $candidato['dni'],
            'correo'           => $candidato['correo'],
            'telefono'         => $candidato['telefono'],
            'profesion'        => $candidato['profesion'],
            'nivelAcademico'   => $candidato['nivel_academico'],
            'colegiatura'      => $candidato['colegiatura'],
            'aniosExperiencia' => $candidato['anios_experiencia'],
            'nivelEspecialidad' => $candidato['nivel_especialidad'],
            'otrosTemas'       => $candidato['otros_temas'],
            'actividades'      => json_decode((string) $candidato['actividades'], true) ?? [],
            'temas'            => array_map(static fn (array $t) => ['id' => (string) $t['id'], 'nombre' => $t['nombre']], $temas),
            'disponibilidad'   => array_map(static fn (array $b) => [
                'dia'  => (int) $b['dia_semana'],
                'hora' => (int) substr((string) $b['hora_inicio'], 0, 2),
            ], $bloques),
            'cvNombreOriginal' => $candidato['cv_nombre_original'],
            'linkedin'         => $candidato['linkedin'],
            'otrasRedes'       => $candidato['otras_redes'],
            'comentarios'      => $candidato['comentarios'],
            'estado'           => $candidato['estado'],
            'fechaRegistro'    => $candidato['created_at'],
            'actualizadoEn'    => $candidato['updated_at'],
        ]);
    }

    // Flujo lineal pedido por el cliente: registrado → en_evaluacion → para_entrevista →
    // (aprobado | desaprobado). No se permite saltar pasos ni "desaprobar" antes de la entrevista —
    // "Desaprobar" ni siquiera se muestra en el modal hasta llegar a para_entrevista. Se valida acá
    // también (no solo en el frontend) para que una llamada manual a la API no pueda saltarse el
    // orden.
    private const TRANSICIONES = [
        'registrado'      => ['en_evaluacion'],
        'en_evaluacion'   => ['para_entrevista'],
        'para_entrevista' => ['aprobado', 'desaprobado'],
    ];

    /** Admin: avanza el estado de la postulación (un paso a la vez, ver TRANSICIONES). */
    public function cambiarEstado($id = null): ResponseInterface
    {
        if ($gate = $this->exigirAdminAsesorias()) {
            return $gate;
        }

        $db = db_connect();
        $candidato = $db->table('candidatos')->where('id', (int) $id)->get()->getRowArray();
        if (! $candidato) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Candidato no encontrado']);
        }

        $dto = $this->request->getJSON(true) ?? [];
        $nuevoEstado = (string) ($dto['estado'] ?? '');
        $permitidos  = self::TRANSICIONES[$candidato['estado']] ?? [];
        if (! in_array($nuevoEstado, $permitidos, true)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Esa transición de estado no está permitida.']);
        }

        $db->table('candidatos')->where('id', (int) $id)->update([
            'estado'     => $nuevoEstado,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->detalle($id);
    }

    /** Admin: notas internas del equipo sobre esta postulación. */
    public function notas($id = null): ResponseInterface
    {
        if ($gate = $this->exigirAdminAsesorias()) {
            return $gate;
        }

        $filas = db_connect()->table('candidato_notas cn')
            ->select('cn.id, cn.nota, cn.created_at, u.nombre as autor_nombre')
            ->join('usuarios u', 'u.id = cn.usuario_id')
            ->where('cn.candidato_id', (int) $id)
            ->orderBy('cn.created_at', 'DESC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map(static fn (array $n) => [
            'id'         => (string) $n['id'],
            'texto'      => $n['nota'],
            'autorNombre' => $n['autor_nombre'],
            'creadoEn'   => $n['created_at'],
        ], $filas));
    }

    /** Admin: agrega una nota interna nueva. */
    public function agregarNota($id = null): ResponseInterface
    {
        if ($gate = $this->exigirAdminAsesorias()) {
            return $gate;
        }

        $db = db_connect();
        $candidato = $db->table('candidatos')->where('id', (int) $id)->get()->getRowArray();
        if (! $candidato) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Candidato no encontrado']);
        }

        $dto  = $this->request->getJSON(true) ?? [];
        $nota = trim((string) ($dto['texto'] ?? ''));
        if ($nota === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'La nota no puede estar vacía.']);
        }

        $db->table('candidato_notas')->insert([
            'candidato_id' => (int) $id,
            'usuario_id'   => (int) session()->get('usuario_id'),
            'nota'         => $nota,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return $this->notas($id);
    }

    /** Admin: conteos por estado — alimenta las tarjetas y los contadores de las pestañas. */
    public function resumen(): ResponseInterface
    {
        if ($gate = $this->exigirAdminAsesorias()) {
            return $gate;
        }

        $db = db_connect();
        $filas = $db->table('candidatos')->select('estado, COUNT(*) as total')->groupBy('estado')->get()->getResultArray();
        $porEstado = array_fill_keys(self::ESTADOS, 0);
        foreach ($filas as $f) {
            $porEstado[$f['estado']] = (int) $f['total'];
        }

        return $this->response->setJSON([
            'total'    => array_sum($porEstado),
            'porEstado' => $porEstado,
        ]);
    }

    /** Admin: descarga del CV en streaming (S3/Cloudinary → cliente), nunca URL pública directa. */
    public function cv($id = null): ResponseInterface
    {
        if ($gate = $this->exigirAdminAsesorias()) {
            return $gate;
        }

        $candidato = db_connect()->table('candidatos')->where('id', (int) $id)->get()->getRowArray();
        if (! $candidato || ($candidato['cv_url'] ?? '') === '') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'CV no encontrado']);
        }

        return $this->servirCv($candidato);
    }

    /**
     * Pública, sin filtro 'auth' — el hipervínculo de la columna "CV" del Excel exportado es un
     * link plano: al abrirlo no manda el header Authorization (el Bearer vive en localStorage, no
     * en una cookie), así que la ruta admin `cv()` de arriba nunca podría abrirse desde Excel.
     * Mismo patrón exacto que CampoArchivosController::descargarPublico: el archivo sigue en el
     * bucket privado, pero el link lleva un token firmado (HMAC, sin expiración — igual de vida
     * útil que cualquier link ya exportado a un Excel que alguien puede reabrir cuando sea).
     */
    public function cvPublico($id = null): ResponseInterface
    {
        $candidato = db_connect()->table('candidatos')->where('id', (int) $id)->get()->getRowArray();
        if (! $candidato || ($candidato['cv_url'] ?? '') === '') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'CV no encontrado']);
        }

        $token = (string) $this->request->getGet('t');
        if ($token === '' || ! hash_equals($this->tokenParaCv((int) $id), $token)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Link inválido']);
        }

        return $this->servirCv($candidato);
    }

    private function servirCv(array $candidato): ResponseInterface
    {
        $stored = (string) $candidato['cv_url'];
        $nombre = (string) $candidato['cv_nombre_original'];
        $mime   = $this->mimeDeNombre($nombre);

        try {
            if (S3ObjectStore::esStoredS3($stored)) {
                $psr = (new S3ObjectStore())->getObjectPsrResponse(S3ObjectStore::claveDe($stored), true);
                $len = $psr->getHeaderLine('Content-Length');
                StreamProxy::pipe($psr->getBody(), $mime, $nombre, $len !== '' ? (int) $len : null, 'attachment');
            }

            if (preg_match('#^https?://#i', $stored)) {
                $remote = (new Client(['http_errors' => false, 'timeout' => 60]))->get($stored, ['stream' => true]);
                if ($remote->getStatusCode() < 200 || $remote->getStatusCode() >= 300) {
                    return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo obtener el CV']);
                }
                $len = $remote->getHeaderLine('Content-Length');
                StreamProxy::pipe($remote->getBody(), $mime, $nombre, $len !== '' ? (int) $len : null, 'attachment');
            }
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo obtener el CV']);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'URL de CV no reconocida']);
    }

    private function tokenParaCv(int $candidatoId): string
    {
        return hash_hmac('sha256', 'candidato-cv:' . $candidatoId, $this->secretoLinks());
    }

    private function secretoLinks(): string
    {
        $key = config(Encryption::class)->key;

        return $key !== '' ? $key : 'proyectafacil-dev-auth-secret';
    }

    // Todas las columnas reales de `candidatos` (perfil completo, no solo lo que ya se ve en la
    // tabla) — pedido explícito del cliente tras ver que el primer export solo traía 9 columnas.
    // La columna "CV" no lleva el archivo (sigue en el bucket, nunca se descarga server-side para
    // el Excel) — lleva un hipervínculo a cvPublico() (link firmado, ver ese método).
    private const EXCEL_ENCABEZADOS = [
        'Nombre', 'DNI', 'Correo', 'Teléfono', 'Profesión', 'Nivel académico', 'Colegiatura / CIP',
        'Experiencia', 'Nivel de especialidad', 'Temas de asesoría', 'Otros temas', 'Actividades',
        'Bloques de disponibilidad', 'LinkedIn', 'Otras redes', 'Comentarios', 'CV', 'Estado', 'Fecha de registro',
    ];
    private const EXCEL_ULTIMA_COLUMNA = 'S';

    /** Admin: exporta la lista (mismos filtros que index()) a un .xlsx real, con todos los campos. */
    public function exportarExcel(): ResponseInterface
    {
        if ($gate = $this->exigirAdminAsesorias()) {
            return $gate;
        }

        $db = db_connect();
        $estado = trim((string) $this->request->getGet('estado'));
        $q      = trim((string) $this->request->getGet('q'));

        $base = $db->table('candidatos c');
        if ($estado !== '' && $estado !== 'todos' && in_array($estado, self::ESTADOS, true)) {
            $base->where('c.estado', $estado);
        }
        if ($q !== '') {
            $base->groupStart()->like('c.nombre', $q)->orLike('c.dni', $q)->orLike('c.correo', $q)->groupEnd();
        }
        $filas = $base->select(
            'c.id, c.nombre, c.dni, c.correo, c.telefono, c.profesion, c.nivel_academico, c.colegiatura, '
            . 'c.anios_experiencia, c.nivel_especialidad, c.otros_temas, c.actividades, c.linkedin, '
            . 'c.otras_redes, c.comentarios, c.cv_url, c.cv_nombre_original, c.estado, c.created_at',
        )->orderBy('c.created_at', 'DESC')->get()->getResultArray();

        $ids = array_map(static fn (array $f) => (int) $f['id'], $filas);
        $temasPorCandidato = [];
        $bloquesPorCandidato = [];
        if ($ids !== []) {
            $temas = $db->table('candidato_temas_especialidad cte')
                ->select('cte.candidato_id, t.nombre as tema_nombre')
                ->join('temas_especialidad t', 't.id = cte.tema_id')
                ->whereIn('cte.candidato_id', $ids)
                ->get()->getResultArray();
            foreach ($temas as $t) {
                $temasPorCandidato[(int) $t['candidato_id']][] = $t['tema_nombre'];
            }
            $bloques = $db->table('candidato_disponibilidad')->select('candidato_id')->whereIn('candidato_id', $ids)->get()->getResultArray();
            foreach ($bloques as $b) {
                $cid = (int) $b['candidato_id'];
                $bloquesPorCandidato[$cid] = ($bloquesPorCandidato[$cid] ?? 0) + 1;
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Candidatos');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $rangoEncabezado = 'A1:' . self::EXCEL_ULTIMA_COLUMNA . '1';
        $sheet->fromArray(self::EXCEL_ENCABEZADOS, null, 'A1');
        $sheet->getStyle($rangoEncabezado)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($rangoEncabezado)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('16A34A');
        $sheet->getStyle($rangoEncabezado)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->setAutoFilter($rangoEncabezado);
        $sheet->freezePane('A2');

        $fila = 2;
        foreach ($filas as $c) {
            $temas       = implode(', ', $temasPorCandidato[(int) $c['id']] ?? []);
            $actividades = implode(', ', json_decode((string) $c['actividades'], true) ?? []);
            $bloques     = $bloquesPorCandidato[(int) $c['id']] ?? 0;

            $sheet->fromArray([
                $c['nombre'], $c['dni'], $c['correo'], $c['telefono'], $c['profesion'],
                $c['nivel_academico'], $c['colegiatura'] ?: '—', $c['anios_experiencia'], $c['nivel_especialidad'],
                $temas ?: '—', $c['otros_temas'] ?: '—', $actividades ?: '—', $bloques,
                $c['linkedin'] ?: '—', $c['otras_redes'] ?: '—', $c['comentarios'] ?: '—',
                '', // CV: se completa abajo como hipervínculo real, no como texto plano.
                self::etiquetaEstado($c['estado']), $c['created_at'],
            ], null, 'A' . $fila);

            if (($c['cv_url'] ?? '') !== '') {
                $celdaCv = 'Q' . $fila;
                $urlCv   = rtrim(config(StripeConfig::class)->frontendBaseUrl, '/')
                    . '/api/candidatos/' . $c['id'] . '/cv-publico?t=' . $this->tokenParaCv((int) $c['id']);
                $sheet->setCellValue($celdaCv, $c['cv_nombre_original'] ?: 'Descargar CV');
                $sheet->getCell($celdaCv)->getHyperlink()->setUrl($urlCv);
                $sheet->getStyle($celdaCv)->getFont()->setUnderline(true)->getColor()->setRGB('2563EB');
            }

            $fila++;
        }
        $ultimaFila = $fila - 1;

        if ($ultimaFila >= 2) {
            $rangoDatos = 'A2:' . self::EXCEL_ULTIMA_COLUMNA . $ultimaFila;
            $sheet->getStyle($rangoDatos)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP)->setWrapText(true);
            $sheet->getStyle('A1:' . self::EXCEL_ULTIMA_COLUMNA . $ultimaFila)
                ->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                ->getColor()->setRGB('E5E7EB');
            for ($i = 2; $i <= $ultimaFila; $i++) {
                if ($i % 2 === 0) {
                    $sheet->getStyle('A' . $i . ':' . self::EXCEL_ULTIMA_COLUMNA . $i)
                        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
                }
            }
        }
        foreach (range('A', self::EXCEL_ULTIMA_COLUMNA) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Temas/otros temas/actividades/comentarios pueden ser largos — autosize los dejaría
        // kilométricos, mejor un ancho fijo generoso con wrap (ya activado arriba).
        foreach (['J', 'K', 'L', 'P'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth(40);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'candidatos_') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tmp);

        $contenido = file_get_contents($tmp);
        @unlink($tmp);

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="candidatos.xlsx"')
            ->setBody($contenido);
    }

    private function toDto(array $c, array $temas): array
    {
        return [
            'id'               => (string) $c['id'],
            'nombre'           => $c['nombre'],
            'dni'              => $c['dni'],
            'correo'           => $c['correo'],
            'telefono'         => $c['telefono'],
            'profesion'        => $c['profesion'],
            'nivelAcademico'   => $c['nivel_academico'],
            'aniosExperiencia' => $c['anios_experiencia'],
            'temas'            => $temas,
            'estado'           => $c['estado'],
            'fechaRegistro'    => $c['created_at'],
        ];
    }

    private static function etiquetaEstado(string $estado): string
    {
        return match ($estado) {
            'registrado'       => 'Registrado',
            'en_evaluacion'    => 'En evaluación',
            'para_entrevista'  => 'Para entrevista',
            'aprobado'         => 'Aprobado',
            'desaprobado'      => 'Desaprobado',
            default            => $estado,
        };
    }

    private function mimeDeNombre(string $nombre): string
    {
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf'   => 'application/pdf',
            'doc'   => 'application/msword',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }

    private function exigirAdminAsesorias(): ?ResponseInterface
    {
        $rol = session()->get('usuario_rol');
        if (! in_array($rol, ['administrativo_asesorias', 'superusuario'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'No tienes permiso para esta acción']);
        }

        return null;
    }

    // Comparación sin importar mayúsculas — un alumno/admin/asesor con "Ana@x.com" no debe
    // poder postular como "ana@x.com". Tablas permitidas: usuarios | candidatos.
    private function correoExisteEn(string $tabla, string $correo): bool
    {
        if (! in_array($tabla, ['usuarios', 'candidatos'], true)) {
            return false;
        }

        $db = db_connect();
        $fila = $db->query(
            'SELECT 1 FROM ' . $tabla . ' WHERE correo IS NOT NULL AND LOWER(correo) = ' . $db->escape(strtolower($correo)) . ' LIMIT 1',
        )->getRowArray();

        return $fila !== null;
    }
}
