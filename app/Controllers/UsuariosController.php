<?php

namespace App\Controllers;

use App\Libraries\CorreoService;
use App\Models\ActividadModel;
use App\Models\PlanModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

// Espejo de `Usuario` en frontend/src/types/index.ts. `password` nunca se lee del cliente en el
// GET (siempre viaja como '' — la UI tampoco la usa para mostrar) y nunca se guarda en texto
// plano — se hashea con password_hash() antes de persistir. Única excepción: la respuesta de
// `create()` cuando no se mandó `password` — el modal "Nuevo usuario" ya no pide contraseña, así
// que el backend genera una temporal y la devuelve en texto plano UNA sola vez, para que el admin
// se la copie y se la pase al usuario (no hay servicio de email en el proyecto).
// `permisos` es el override explícito en usuario_permisos; si el usuario no tiene overrides, se
// manda `null` y el frontend calcula el default por rol (ver lib/permisosCatalogo.ts, permisosDe()).
class UsuariosController extends BaseController
{
    /**
     * Columnas del Excel de alumnos, EN ORDEN. Una sola fuente para la plantilla que se descarga y
     * para la lista que el modal muestra, así no pueden desincronizarse.
     */
    private const COLUMNAS_ALUMNOS = ['Nombre', 'Correo', 'Teléfono', 'Vigencia hasta', 'Curso', 'Beneficio'];

    /** Hoja oculta que alimenta los desplegables de Curso y Beneficio de la plantilla. */
    private const HOJA_LISTAS = 'Listas';

    public function index(): ResponseInterface
    {
        $filas = (new UsuarioModel())->orderBy('id')->findAll();

        // DEBUG TEMPORAL — quitar cuando se resuelva el issue de producción devolviendo datos mock.
        error_log('[DEBUG usuarios.index] host=' . ($_SERVER['HTTP_HOST'] ?? '?') . ' filas_en_bd=' . count($filas) . ' usuario_id_en_sesion=' . (session()->get('usuario_id') ?? 'null'));

        $cursos = db_connect()->table('cursos')->select('id, nombre, color_accent')->get()->getResultArray();
        $cursosPorId = [];
        foreach ($cursos as $c) {
            $cursosPorId[(int) $c['id']] = $c;
        }

        return $this->response->setJSON(array_map(fn (array $f) => $this->toDto($f, $cursosPorId), $filas));
    }

    public function create(): ResponseInterface
    {
        $dto = $this->request->getJSON(true) ?? [];
        $model = new UsuarioModel();

        $passwordProvista = trim((string) ($dto['password'] ?? ''));
        $passwordTemporal = $passwordProvista === '' ? $this->generarPasswordTemporal() : null;

        // soloProvistos: true — omitir del INSERT las columnas ausentes del payload en vez de
        // forzar NULL, para que la BD aplique sus DEFAULT (tema='sistema', estado='activo').
        $fila = $this->fromDto($dto, soloProvistos: true);
        $fila['password_hash'] = password_hash($passwordTemporal ?? $passwordProvista, PASSWORD_DEFAULT);
        $id = $model->insert($fila, true);

        if (array_key_exists('permisos', $dto)) {
            $this->sincronizarPermisos((int) $id, $dto['permisos']);
        }

        $resultado = $this->toDto($model->find($id));
        if ($passwordTemporal !== null) {
            $resultado['password'] = $passwordTemporal;
        }

        return $this->response->setJSON($resultado);
    }

    public function update($id = null): ResponseInterface
    {
        $model = new UsuarioModel();
        $actual = $model->find($id);
        if (! $actual) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Usuario no encontrado']);
        }

        $dto = $this->request->getJSON(true) ?? [];
        $cambios = $this->fromDto($dto, soloProvistos: true);
        if (array_key_exists('password', $dto) && trim((string) $dto['password']) !== '') {
            $cambios['password_hash'] = password_hash((string) $dto['password'], PASSWORD_DEFAULT);
        }

        // El admin cambió el Origen a mano (no un ajuste automático) — se registra quién y cuándo,
        // server-side, para que el cliente no pueda falsear el rastro de auditoría.
        if (array_key_exists('origen', $dto) && $dto['origen'] !== $actual['origen']) {
            $cambios['origen_cambiado_por_id'] = session()->get('usuario_id');
            $cambios['origen_cambiado_en'] = date('Y-m-d H:i:s');
        }

        // Tab "Información" del panel de detalles: cualquier cambio real a los 3 campos editables
        // ahí (nombre/correo/teléfono) deja rastro en "Últimas modificaciones del perfil" — sin
        // importar quién lo edite, el rastro es del perfil (objetivo_id), no del editor.
        $camposPerfil = ['nombre', 'correo', 'telefono'];
        $cambioPerfil = false;
        foreach ($camposPerfil as $campo) {
            if (array_key_exists($campo, $dto) && $dto[$campo] !== ($actual[$campo] ?? null)) {
                $cambioPerfil = true;
                break;
            }
        }

        if ($cambios !== []) {
            $model->update($id, $cambios);
        }

        if ($cambioPerfil) {
            (new ActividadModel())->insert([
                'mensaje'     => 'Actualizó su información de perfil',
                'color'       => 'blue',
                'categoria'   => 'Perfil',
                'actor_id'    => session()->get('usuario_id'),
                'objetivo_id' => (int) $id,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        if (array_key_exists('permisos', $dto)) {
            $this->sincronizarPermisos((int) $id, $dto['permisos']);
        }

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    public function delete($id = null): ResponseInterface
    {
        (new UsuarioModel())->delete($id);

        return $this->response->setJSON((object) []);
    }

    // Usado desde el modal "Editar usuario": no hay forma de recuperar la contraseña original (se
    // guarda cifrada, ver comentario de arriba de la clase) — genera una NUEVA en el momento, la
    // guarda, y la manda por correo. Nadie ve ni guarda la anterior en ningún lado.
    public function enviarAccesos($id = null): ResponseInterface
    {
        $model = new UsuarioModel();
        $usuario = $model->find($id);
        if (! $usuario) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Usuario no encontrado']);
        }
        if (empty($usuario['correo'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Este usuario no tiene un correo cargado.']);
        }

        $passwordTemporal = $this->generarPasswordTemporal();
        $model->update($id, ['password_hash' => password_hash($passwordTemporal, PASSWORD_DEFAULT)]);

        try {
            (new CorreoService())->enviarAccesos($usuario['correo'], $usuario['nombre'], $usuario['usuario'], $passwordTemporal);
        } catch (Throwable $e) {
            log_message('error', '[usuarios] No se pudo enviar accesos a {correo}: {msg}', ['correo' => $usuario['correo'], 'msg' => $e->getMessage()]);

            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo enviar el correo. Intenta de nuevo.']);
        }

        return $this->response->setJSON(['enviado' => true]);
    }

    // Usado desde el modal "Usuario creado" (justo después de create()): la contraseña temporal
    // que el admin ya está viendo en pantalla todavía es válida (recién se guardó) — se manda ESA
    // misma por correo, sin generar una nueva, para no invalidar por sorpresa lo que ya copió.
    public function enviarAccesosDirecto($id = null): ResponseInterface
    {
        $usuario = (new UsuarioModel())->find($id);
        if (! $usuario) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Usuario no encontrado']);
        }
        if (empty($usuario['correo'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Este usuario no tiene un correo cargado.']);
        }

        $password = trim((string) ($this->request->getJSON(true)['password'] ?? ''));
        if ($password === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta la contraseña a enviar.']);
        }

        try {
            (new CorreoService())->enviarAccesos($usuario['correo'], $usuario['nombre'], $usuario['usuario'], $password);
        } catch (Throwable $e) {
            log_message('error', '[usuarios] No se pudo enviar accesos a {correo}: {msg}', ['correo' => $usuario['correo'], 'msg' => $e->getMessage()]);

            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo enviar el correo. Intenta de nuevo.']);
        }

        return $this->response->setJSON(['enviado' => true]);
    }

    // Cupos actuales de un cliente (alumno o externo) para el panel admin "Membresía y pagos".
    // Lee la cuenta titular; no crea facturación (eso era el bug de GET /facturacion).
    public function beneficiosAsignados($id = null): ResponseInterface
    {
        $gate = $this->exigirAdmin();
        if ($gate !== null) {
            return $gate;
        }

        $usuario = (new UsuarioModel())->find($id);
        if (! $usuario) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Usuario no encontrado']);
        }
        if (($usuario['rol'] ?? '') !== 'cliente') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Solo se pueden asignar beneficios a clientes (alumnos o externos).']);
        }

        return $this->response->setJSON($this->dtoBeneficios($this->idCuentaDe($usuario)));
    }

    // Otorga plan y/o cupos sin pasar por Stripe. Fichas de chat/video se SUMAN; plantillas
    // simultáneas se FIJAN al total pedido (el extra sobre la base del plan va al add-on
    // "Plantilla adicional").
    public function asignarBeneficios($id = null): ResponseInterface
    {
        $gate = $this->exigirAdmin();
        if ($gate !== null) {
            return $gate;
        }

        $usuario = (new UsuarioModel())->find($id);
        if (! $usuario) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Usuario no encontrado']);
        }
        if (($usuario['rol'] ?? '') !== 'cliente') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Solo se pueden asignar beneficios a clientes (alumnos o externos).']);
        }

        $cuentaId          = $this->idCuentaDe($usuario);
        $dto               = $this->request->getJSON(true) ?? [];
        $planIdSlug        = trim((string) ($dto['planId'] ?? ''));
        $agregarChat       = max(0, (int) ($dto['agregarFichasChat'] ?? 0));
        $agregarVideo      = max(0, (int) ($dto['agregarFichasVideo'] ?? 0));
        $limitePlantillas  = array_key_exists('limitePlantillas', $dto) && $dto['limitePlantillas'] !== null && $dto['limitePlantillas'] !== ''
            ? max(0, (int) $dto['limitePlantillas'])
            : null;

        if ($planIdSlug === '' && $agregarChat === 0 && $agregarVideo === 0 && $limitePlantillas === null) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Indica un plan, fichas o un cupo de plantillas para asignar.']);
        }

        if ($planIdSlug !== '') {
            $error = $this->asignarPlan($cuentaId, $planIdSlug);
            if ($error !== null) {
                return $error;
            }
        }

        if ($limitePlantillas !== null) {
            $error = $this->asignarLimitePlantillas($cuentaId, $limitePlantillas);
            if ($error !== null) {
                return $error;
            }
        }

        if ($agregarChat > 0 || $agregarVideo > 0) {
            TicketsConsultaController::otorgarFichasModalidad($cuentaId, $agregarChat, $agregarVideo);
        }

        (new ActividadModel())->insert([
            'mensaje'     => 'Se le asignaron beneficios de membresía',
            'color'       => 'green',
            'categoria'   => 'Membresía',
            'actor_id'    => session()->get('usuario_id'),
            'objetivo_id' => (int) $id,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON($this->dtoBeneficios($cuentaId));
    }

    /**
     * Admin: carga masiva de clientes-alumnos desde un Excel — mismo criterio que
     * CandidatosController::importarExcel (crea `usuarios` directo, contraseña aleatoria
     * descartada, "Enviar accesos" queda para después). Filas con un correo ya registrado se
     * omiten (no se actualiza al usuario existente) y se reportan en la respuesta. "Vigencia
     * hasta" es la misma fecha opcional que pide el modal "Crea un nuevo acceso al panel" cuando
     * Origen=Alumno (columna `vigencia_alumno_hasta`) — se lee la celda directamente (no vía
     * toArray) para no depender de cómo Excel formatea la fecha para mostrarla.
     *
     * "Curso" y "Beneficio" van POR FILA en el propio Excel (columnas E y F), elegidos de las listas
     * desplegables que trae la plantilla descargable — así un mismo archivo puede repartir alumnos
     * entre varios cursos o planes. Los dos son opcionales, y un archivo viejo de 4 columnas se
     * sigue importando igual (esas celdas simplemente no existen).
     *
     * Se aceptan por NOMBRE, que es lo que el admin ve y elige, no por id. El beneficio admite tanto
     * "Nivel N — Nombre" (lo que pone la plantilla) como el nombre del plan a secas. El plan se
     * otorga por la misma vía que "Asignar beneficios" del panel de detalle (asignarPlan()), así que
     * un alumno importado queda exactamente igual que uno al que se le asignó a mano.
     */
    public function importarAlumnosExcel(): ResponseInterface
    {
        if ($gate = $this->exigirAdmin()) {
            return $gate;
        }

        $file = $this->request->getFile('archivo');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta el archivo Excel.']);
        }

        $db = db_connect();

        // Diccionarios nombre-normalizado => valor, para resolver las columnas Curso y Beneficio sin
        // pegarle a la BD una vez por fila.
        $cursosPorNombre     = $this->cursosPorNombreNormalizado();
        $beneficiosPorNombre = $this->beneficiosPorNombreNormalizado();

        try {
            $sheet = IOFactory::load($file->getTempName())->getActiveSheet();
            $filas = $sheet->toArray(null, true, true, false);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No se pudo leer el archivo. Verifica que sea un .xlsx válido.']);
        }
        array_shift($filas); // fila 1 = encabezados

        $creados  = 0;
        $omitidos = [];
        $numeroFila = 1;
        foreach ($filas as $f) {
            $numeroFila++;
            $nombre   = trim((string) ($f[0] ?? ''));
            $correo   = trim((string) ($f[1] ?? ''));
            $telefono = trim((string) ($f[2] ?? ''));

            if ($nombre === '' && $correo === '') {
                continue; // fila en blanco, se ignora sin reportar
            }
            if ($nombre === '' || ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $omitidos[] = ['fila' => $numeroFila, 'motivo' => 'Falta el nombre o el correo no es válido'];
                continue;
            }
            if ($this->correoYaRegistrado($correo)) {
                $omitidos[] = ['fila' => $numeroFila, 'motivo' => "Correo ya registrado ({$correo})"];
                continue;
            }

            [$vigencia, $vigenciaValida] = $this->fechaDeCelda($sheet, 'D' . $numeroFila);
            if (! $vigenciaValida) {
                $omitidos[] = ['fila' => $numeroFila, 'motivo' => 'La fecha de "Vigencia hasta" no es válida (usa AAAA-MM-DD)'];
                continue;
            }

            // Curso y Beneficio: vacío es válido (el alumno queda sin curso / sin plan), pero un
            // valor que no está en la lista se rechaza en vez de crear al alumno a medias — si se
            // creara igual, el admin se quedaría con un alumno sin el curso que creía haberle puesto
            // y sin forma fácil de detectarlo entre doscientos.
            $textoCurso = trim((string) ($f[4] ?? ''));
            $cursoId    = null;
            if ($textoCurso !== '') {
                $cursoId = $cursosPorNombre[$this->claveDeNombre($textoCurso)] ?? null;
                if ($cursoId === null) {
                    $omitidos[] = ['fila' => $numeroFila, 'motivo' => "El curso \"{$textoCurso}\" no existe"];
                    continue;
                }
            }

            $textoBeneficio = trim((string) ($f[5] ?? ''));
            $planSlug       = '';
            if ($textoBeneficio !== '') {
                $planSlug = $beneficiosPorNombre[$this->claveDeNombre($textoBeneficio)] ?? '';
                if ($planSlug === '') {
                    $omitidos[] = ['fila' => $numeroFila, 'motivo' => "El beneficio \"{$textoBeneficio}\" no existe"];
                    continue;
                }
            }

            $ahora = date('Y-m-d H:i:s');
            $db->table('usuarios')->insert([
                'nombre'                => $nombre,
                'usuario'               => $this->loginDisponibleDesde($correo, $nombre),
                'password_hash'         => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'rol'                   => 'cliente',
                'origen'                => 'alumno',
                'estado'                => 'activo',
                'correo'                => $correo,
                'telefono'              => $telefono !== '' ? $telefono : null,
                'curso_id'              => $cursoId,
                'vigencia_alumno_hasta' => $vigencia,
                'created_at'            => $ahora,
                'updated_at'            => $ahora,
            ]);
            $creados++;

            // Recién creado y sin `cuenta_cliente_id`: su cuenta es él mismo (ver idCuentaDe()).
            // El slug salió del diccionario de planes existentes, así que asignarPlan() no debería
            // fallar; si lo hace igual, el alumno YA está creado — se reporta para que el admin
            // sepa que a ese le falta el beneficio, en vez de dejarlo pasar en silencio.
            if ($planSlug !== '' && $this->asignarPlan((int) $db->insertID(), $planSlug) !== null) {
                $omitidos[] = ['fila' => $numeroFila, 'motivo' => "El alumno se creó, pero no se pudo asignar el beneficio \"{$textoBeneficio}\": hazlo a mano."];
            }
        }

        return $this->response->setJSON(['creados' => $creados, 'omitidos' => $omitidos]);
    }

    /**
     * Admin: descarga el .xlsx de ejemplo para la carga masiva de alumnos.
     *
     * Se genera en el servidor, y no en el frontend, para que el archivo que el administrador baja
     * y el que importarAlumnosExcel() espera no puedan desincronizarse: las dos cosas salen de la
     * misma constante de columnas. La fila de ejemplo va con "Vigencia hasta" como TEXTO en formato
     * AAAA-MM-DD, que es el que la importación acepta sin ambigüedad de locale.
     */
    public function plantillaAlumnosExcel(): ResponseInterface
    {
        if ($gate = $this->exigirAdmin()) {
            return $gate;
        }

        $cursos     = $this->cursosParaPlantilla();
        $beneficios = $this->beneficiosParaPlantilla();

        $libro = new Spreadsheet();
        $hoja  = $libro->getActiveSheet();
        $hoja->setTitle('Alumnos');

        $hoja->fromArray(self::COLUMNAS_ALUMNOS, null, 'A1');
        $hoja->getStyle('A1:F1')->getFont()->setBold(true);

        $ejemplos = [
            ['Rosa Delgado Ríos', 'rosa.delgado@example.com', '987654321', '2026-12-31', $cursos[0] ?? '', $beneficios[0] ?? ''],
            ['Mateo Vargas Luna', 'mateo.vargas@example.com', '', '', '', ''],
        ];
        foreach ($ejemplos as $i => $ejemplo) {
            $fila = $i + 2;
            $hoja->setCellValue("A{$fila}", $ejemplo[0]);
            $hoja->setCellValue("B{$fila}", $ejemplo[1]);
            // Teléfono y fecha van como TEXTO EXPLÍCITO. Si se dejara que PhpSpreadsheet infiera el
            // tipo, "987654321" se guardaría como número (y un teléfono con "+51" o con cero inicial
            // se rompería al editarlo) y "2026-12-31" se volvería un serial que cada Excel muestra
            // según su locale — justo el formato ambiguo que la importación no acepta.
            $hoja->setCellValueExplicit("C{$fila}", $ejemplo[2], DataType::TYPE_STRING);
            $hoja->setCellValueExplicit("D{$fila}", $ejemplo[3], DataType::TYPE_STRING);
            $hoja->setCellValueExplicit("E{$fila}", $ejemplo[4], DataType::TYPE_STRING);
            $hoja->setCellValueExplicit("F{$fila}", $ejemplo[5], DataType::TYPE_STRING);
        }
        $hoja->getStyle('C2:D3')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $this->agregarListasDesplegables($libro, $hoja, $cursos, $beneficios);

        foreach (range('A', 'F') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        $temporal = tempnam(sys_get_temp_dir(), 'plantilla-alumnos-') . '.xlsx';
        (new Xlsx($libro))->save($temporal);
        $contenido = (string) file_get_contents($temporal);
        @unlink($temporal);
        $libro->disconnectWorksheets();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="formato-alumnos.xlsx"')
            ->setBody($contenido);
    }

    /**
     * Clave para comparar nombres escritos a mano: sin mayúsculas, sin tildes y con los espacios
     * colapsados.
     *
     * Hace falta porque el valor llega de una celda de Excel: aunque la plantilla trae desplegable,
     * el admin puede pegar el texto, escribirlo sin tilde, o dejar un espacio doble al copiar. Se
     * normalizan también los guiones largos, porque la etiqueta de beneficio usa "—" y al reescribir
     * a mano sale un "-" común.
     */
    private function claveDeNombre(string $texto): string
    {
        $texto = strtr($texto, ['—' => '-', '–' => '-']);
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        return (string) preg_replace('/\s+/', ' ', $texto);
    }

    /** @return array<string,int> nombre normalizado => id del curso. */
    private function cursosPorNombreNormalizado(): array
    {
        $mapa = [];
        foreach (db_connect()->table('cursos')->select('id, nombre')->get()->getResultArray() as $c) {
            $mapa[$this->claveDeNombre((string) $c['nombre'])] = (int) $c['id'];
        }

        return $mapa;
    }

    /**
     * @return array<string,string> nombre normalizado => slug `nivel-N`.
     *
     * Se aceptan las dos formas con las que el valor puede llegar: la etiqueta completa que pone la
     * plantilla ("Nivel 1 — Profesional") y el nombre del plan a secas ("Profesional"), que es lo
     * que un admin escribe cuando llena la columna sin usar el desplegable.
     */
    private function beneficiosPorNombreNormalizado(): array
    {
        $mapa = [];
        foreach ((new PlanModel())->findAll() as $p) {
            $slug   = 'nivel-' . (int) $p['numero_nivel'];
            $nombre = (string) $p['nombre'];

            $mapa[$this->claveDeNombre('Nivel ' . (int) $p['numero_nivel'] . ' — ' . $nombre)] = $slug;
            $mapa[$this->claveDeNombre($nombre)] = $slug;
        }

        return $mapa;
    }

    /** Nombres de los cursos existentes, en el mismo orden en que los ve el admin en el panel. */
    private function cursosParaPlantilla(): array
    {
        return array_column(
            db_connect()->table('cursos')->select('nombre')->orderBy('nombre', 'ASC')->get()->getResultArray(),
            'nombre',
        );
    }

    /**
     * Beneficios elegibles, con el formato "Nivel N — Nombre" que también entiende la importación.
     *
     * Se arman desde la tabla `planes` y NO desde un catálogo fijo: lo que se otorga se busca por
     * `numero_nivel` (ver asignarPlan()), así que la etiqueta que el admin elige y el plan que
     * realmente se asigna salen de la misma fuente.
     */
    private function beneficiosParaPlantilla(): array
    {
        $filas = (new PlanModel())->orderBy('numero_nivel', 'ASC')->findAll();

        return array_map(
            static fn (array $p): string => 'Nivel ' . (int) $p['numero_nivel'] . ' — ' . $p['nombre'],
            $filas,
        );
    }

    /**
     * Cuelga las listas desplegables de Curso y Beneficio en la plantilla.
     *
     * Las opciones NO van embebidas en la fórmula de validación (`'"a,b,c"'`): ese formato inline
     * de Excel se corta a 255 caracteres y parte los valores que contienen comas —y un nombre de
     * curso como "Invierte.pe, nivel avanzado" rompería la lista entera—. Por eso van en una hoja
     * auxiliar OCULTA y la validación apunta a ese rango.
     */
    private function agregarListasDesplegables(Spreadsheet $libro, Worksheet $hoja, array $cursos, array $beneficios): void
    {
        if ($cursos === [] && $beneficios === []) {
            return;
        }

        $listas = $libro->createSheet();
        $listas->setTitle(self::HOJA_LISTAS);
        foreach ($cursos as $i => $curso) {
            $listas->setCellValueExplicit('A' . ($i + 1), $curso, DataType::TYPE_STRING);
        }
        foreach ($beneficios as $i => $beneficio) {
            $listas->setCellValueExplicit('B' . ($i + 1), $beneficio, DataType::TYPE_STRING);
        }
        $listas->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        // Hasta la fila 500: es el desplegable en las filas que el admin va a llenar. Pegarlo solo a
        // las 2 de ejemplo obligaría a copiar formato hacia abajo para cada alumno nuevo.
        $rangos = [];
        if ($cursos !== []) {
            $rangos['E'] = sprintf("'%s'!\$A\$1:\$A\$%d", self::HOJA_LISTAS, count($cursos));
        }
        if ($beneficios !== []) {
            $rangos['F'] = sprintf("'%s'!\$B\$1:\$B\$%d", self::HOJA_LISTAS, count($beneficios));
        }

        foreach ($rangos as $columna => $formula) {
            // Suelta, NO vía getCell("E2")->getDataValidation(): esa forma engancha la validación a
            // E2 además del rango, y el archivo termina con la regla declarada dos veces sobre la
            // misma celda.
            $validacion = new DataValidation();
            $validacion->setType(DataValidation::TYPE_LIST);
            // STOP: si el admin escribe algo que no está en la lista, Excel lo rechaza en el momento
            // en vez de dejar que se entere recién al importar.
            $validacion->setErrorStyle(DataValidation::STYLE_STOP);
            $validacion->setAllowBlank(true);
            $validacion->setShowInputMessage(true);
            $validacion->setShowErrorMessage(true);
            $validacion->setShowDropDown(true);
            $validacion->setErrorTitle('Valor no válido');
            $validacion->setError('Elige una de las opciones de la lista, o deja la celda vacía.');
            $validacion->setPromptTitle($columna === 'E' ? 'Curso' : 'Beneficio');
            $validacion->setPrompt('Opcional. Elige de la lista.');
            $validacion->setFormula1($formula);

            $hoja->setDataValidation("{$columna}2:{$columna}500", $validacion);
        }
    }

    /**
     * Lee una celda de fecha admitiendo tanto una celda con formato de fecha real de Excel como
     * texto plano "AAAA-MM-DD" — celda vacía es válida (sin vigencia). Devuelve
     * [fecha ('Y-m-d') | null, esVálida].
     *
     * @return array{0: string|null, 1: bool}
     */
    private function fechaDeCelda(Worksheet $sheet, string $referencia): array
    {
        $celda = $sheet->getCell($referencia);
        $valor = $celda->getValue();
        if ($valor === null || trim((string) $valor) === '') {
            return [null, true];
        }

        if (is_numeric($valor) && ExcelDate::isDateTime($celda)) {
            $fecha = ExcelDate::excelToDateTimeObject($valor);

            return [$fecha->format('Y-m-d'), true];
        }

        $texto = trim((string) $valor);
        $fecha = \DateTime::createFromFormat('Y-m-d', $texto);

        return $fecha && $fecha->format('Y-m-d') === $texto ? [$texto, true] : [null, false];
    }

    private function correoYaRegistrado(string $correo): bool
    {
        $db = db_connect();

        return $db->query(
            'SELECT 1 FROM usuarios WHERE correo IS NOT NULL AND LOWER(correo) = ' . $db->escape(strtolower($correo)) . ' LIMIT 1',
        )->getRowArray() !== null;
    }

    /** Login libre a partir del correo (o del nombre), con sufijo numérico si ya está tomado —
     *  mismo criterio que CandidatosController::loginDisponible. */
    private function loginDisponibleDesde(string $correo, string $nombre): string
    {
        $local = strtok($correo, '@');
        $base  = strtolower((string) preg_replace('/[^A-Za-z0-9._-]/', '', $local === false ? '' : $local));
        if ($base === '') {
            $base = strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', $nombre));
        }
        $base = substr($base !== '' ? $base : 'usuario', 0, 40);

        $db    = db_connect();
        $login = $base;
        $n     = 1;
        while ($db->table('usuarios')->where('usuario', $login)->countAllResults() > 0) {
            $login = $base . ++$n;
        }

        return $login;
    }

    private function exigirAdmin(): ?ResponseInterface
    {
        $rol = session()->get('usuario_rol');
        if (! in_array($rol, ['administrador', 'superusuario'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'No tienes permiso para esta acción']);
        }

        return null;
    }

    private function idCuentaDe(array $usuario): int
    {
        return $usuario['cuenta_cliente_id'] !== null
            ? (int) $usuario['cuenta_cliente_id']
            : (int) $usuario['id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function dtoBeneficios(int $cuentaId): array
    {
        $db            = db_connect();
        $fact          = $db->table('facturaciones')->where('usuario_id', $cuentaId)->get()->getRowArray();
        $planId        = null;
        $planNombre    = null;
        $base          = 0;
        $extra         = 0;

        if ($fact) {
            $plan = (new PlanModel())->find($fact['plan_id']);
            if ($plan) {
                $planId     = 'nivel-' . $plan['numero_nivel'];
                $planNombre = $plan['nombre'];
                $base       = (int) $plan['limite_fichas_base'];
            }
            $addon = $db->table('add_ons')->where('nombre', 'Plantilla adicional')->get()->getRowArray();
            if ($addon) {
                $filaAddon = $db->table('facturacion_addons')
                    ->where('facturacion_usuario_id', $cuentaId)
                    ->where('add_on_id', $addon['id'])
                    ->get()->getRowArray();
                $extra = (int) ($filaAddon['cantidad'] ?? 0);
            }
        }

        $tickets   = $db->table('tickets_consulta')->where('usuario_id', $cuentaId)->get()->getResultArray();
        $chatDisp  = 0;
        $videoDisp = 0;
        foreach ($tickets as $t) {
            if (($t['estado'] ?? '') !== 'disponible') {
                continue;
            }
            if (($t['modalidad'] ?? '') === 'chat') {
                $chatDisp++;
            }
            if (($t['modalidad'] ?? '') === 'video') {
                $videoDisp++;
            }
        }

        return [
            'cuentaId'               => (string) $cuentaId,
            'planId'                 => $planId,
            'planNombre'             => $planNombre,
            'limitePlantillas'       => $base + $extra,
            'limitePlantillasBase'   => $base,
            'fichasChatDisponibles'  => $chatDisp,
            'fichasVideoDisponibles' => $videoDisp,
        ];
    }

    private function asignarPlan(int $cuentaId, string $planIdSlug): ?ResponseInterface
    {
        if (! preg_match('/^nivel-(\d+)$/', $planIdSlug, $m)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Plan no válido']);
        }

        $plan = (new PlanModel())->where('numero_nivel', (int) $m[1])->first();
        if (! $plan) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ese plan no está cargado en el sistema']);
        }

        $db    = db_connect();
        $ahora = date('Y-m-d H:i:s');
        $fila  = $db->table('facturaciones')->where('usuario_id', $cuentaId)->get()->getRowArray();

        if ($fila) {
            $cambios = [
                'plan_id'    => $plan['id'],
                'cancelada'  => 0,
                'updated_at' => $ahora,
            ];
            if (empty($fila['fecha_inicio_plan'])) {
                $cambios['fecha_inicio_plan'] = $ahora;
            }
            // Sin suscripción Stripe: vigencia sin fecha de corte (AuthController::tienePlan trata
            // fecha_renovacion nula como vigente). Con Stripe se deja la fecha que ya puso el webhook.
            if (empty($fila['stripe_subscription_id'])) {
                $cambios['fecha_renovacion'] = null;
            }
            $db->table('facturaciones')->where('usuario_id', $cuentaId)->update($cambios);
        } else {
            $db->table('facturaciones')->insert([
                'usuario_id'        => $cuentaId,
                'plan_id'           => $plan['id'],
                'cancelada'         => 0,
                'fecha_renovacion'  => null,
                'fecha_inicio_plan' => $ahora,
                'metodo_pago'       => 'tarjeta',
                'created_at'        => $ahora,
                'updated_at'        => $ahora,
            ]);
        }

        TicketsConsultaController::emitirTicketsDePlan($cuentaId, (int) $plan['id']);

        return null;
    }

    private function asignarLimitePlantillas(int $cuentaId, int $limiteDeseado): ?ResponseInterface
    {
        $db   = db_connect();
        $fact = $db->table('facturaciones')->where('usuario_id', $cuentaId)->get()->getRowArray();
        if (! $fact) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Asigna un plan para poder definir plantillas simultáneas.']);
        }

        $plan  = (new PlanModel())->find($fact['plan_id']);
        $base  = (int) ($plan['limite_fichas_base'] ?? 0);
        $extra = max(0, $limiteDeseado - $base);

        $addon = $db->table('add_ons')->where('nombre', 'Plantilla adicional')->get()->getRowArray();
        if (! $addon) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'No está configurado el add-on de plantilla adicional.']);
        }

        $filaAddon = $db->table('facturacion_addons')
            ->where('facturacion_usuario_id', $cuentaId)
            ->where('add_on_id', $addon['id'])
            ->get()->getRowArray();

        if ($extra === 0) {
            if ($filaAddon) {
                $db->table('facturacion_addons')
                    ->where('facturacion_usuario_id', $cuentaId)
                    ->where('add_on_id', $addon['id'])
                    ->delete();
            }
        } elseif ($filaAddon) {
            $db->table('facturacion_addons')
                ->where('facturacion_usuario_id', $cuentaId)
                ->where('add_on_id', $addon['id'])
                ->update(['cantidad' => $extra]);
        } else {
            $db->table('facturacion_addons')->insert([
                'facturacion_usuario_id' => $cuentaId,
                'add_on_id'              => $addon['id'],
                'cantidad'               => $extra,
            ]);
        }

        return null;
    }

    /** @param array<int, array{id: mixed, nombre: string, color_accent: string}>|null $cursosPorId */
    private function toDto(array $fila, ?array $cursosPorId = null): array
    {
        $db = db_connect();
        $permisos = $db->table('usuario_permisos')
            ->select('permiso_clave')
            ->where('usuario_id', $fila['id'])
            ->get()
            ->getResultArray();

        $cursoId = $fila['curso_id'] ?? null;
        $curso = null;
        if ($cursoId !== null) {
            $curso = $cursosPorId !== null
                ? ($cursosPorId[(int) $cursoId] ?? null)
                : $db->table('cursos')->where('id', $cursoId)->get()->getRowArray();
        }

        return [
            'id'              => (string) $fila['id'],
            'nombre'          => $fila['nombre'],
            'usuario'         => $fila['usuario'],
            'password'        => '',
            'rol'             => $fila['rol'],
            'apodo'           => $fila['apodo'],
            'tema'            => $fila['tema'],
            'estado'          => $fila['estado'],
            'cuentaClienteId' => $fila['cuenta_cliente_id'] !== null ? (string) $fila['cuenta_cliente_id'] : null,
            'permisos'        => $permisos === [] ? null : array_map(static fn (array $p) => $p['permiso_clave'], $permisos),
            'tipoUsuarioId'   => $fila['tipo_usuario_id'] !== null ? (string) $fila['tipo_usuario_id'] : null,
            'origen'          => $fila['origen'] ?? null,
            'cursoId'         => $cursoId !== null ? (string) $cursoId : null,
            'cursoNombre'     => $curso['nombre'] ?? null,
            'cursoColorAccent' => $curso['color_accent'] ?? null,
            'correo'          => $fila['correo'] ?? null,
            'fotoUrl'         => $fila['foto_url'] ?? null,
            'vigenciaAlumnoHasta' => $fila['vigencia_alumno_hasta'] ?? null,
            'origenCambiadoPorNombre' => $fila['origen_cambiado_por_id'] !== null
                ? ((new UsuarioModel())->find($fila['origen_cambiado_por_id'])['nombre'] ?? null)
                : null,
            'origenCambiadoEn' => $fila['origen_cambiado_en'] ?? null,
            'disponible'      => (bool) ($fila['disponible'] ?? true),
            'chatAnchoPx'     => $fila['chat_ancho_px'] !== null ? (int) $fila['chat_ancho_px'] : null,
            'chatAltoPx'      => $fila['chat_alto_px'] !== null ? (int) $fila['chat_alto_px'] : null,
            'telefono'        => $fila['telefono'] ?? null,
            'fechaRegistro'   => isset($fila['created_at']) ? date(DATE_ATOM, strtotime($fila['created_at'])) : null,
            'ultimoAcceso'    => $fila['ultimo_acceso'] !== null ? date(DATE_ATOM, strtotime($fila['ultimo_acceso'])) : null,
        ];
    }

    private function generarPasswordTemporal(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        return $password;
    }

    /**
     * @return array<string, mixed>
     */
    private function fromDto(array $dto, bool $soloProvistos = false): array
    {
        $mapa = [
            'nombre'          => 'nombre',
            'usuario'         => 'usuario',
            'rol'             => 'rol',
            'apodo'           => 'apodo',
            'tema'            => 'tema',
            'estado'          => 'estado',
            'cuentaClienteId' => 'cuenta_cliente_id',
            'tipoUsuarioId'   => 'tipo_usuario_id',
            'origen'          => 'origen',
            'cursoId'         => 'curso_id',
            'correo'          => 'correo',
            'fotoUrl'         => 'foto_url',
            'vigenciaAlumnoHasta' => 'vigencia_alumno_hasta',
            'chatAnchoPx'     => 'chat_ancho_px',
            'chatAltoPx'      => 'chat_alto_px',
            'telefono'        => 'telefono',
        ];

        $fila = [];
        foreach ($mapa as $claveDto => $columna) {
            if ($soloProvistos && ! array_key_exists($claveDto, $dto)) {
                continue;
            }
            $fila[$columna] = $dto[$claveDto] ?? null;
        }
        if (array_key_exists('disponible', $dto)) {
            $fila['disponible'] = $dto['disponible'] ? 1 : 0;
        }

        return $fila;
    }

    private function sincronizarPermisos(int $usuarioId, ?array $permisos): void
    {
        $db = db_connect();
        $db->table('usuario_permisos')->where('usuario_id', $usuarioId)->delete();

        foreach ($permisos ?? [] as $clave) {
            $db->table('usuario_permisos')->insert([
                'usuario_id'    => $usuarioId,
                'permiso_clave' => $clave,
            ]);
        }
    }
}
