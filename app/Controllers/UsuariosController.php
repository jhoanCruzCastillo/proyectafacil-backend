<?php

namespace App\Controllers;

use App\Libraries\CorreoService;
use App\Models\ActividadModel;
use App\Models\PlanModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\ResponseInterface;
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
    public function index(): ResponseInterface
    {
        $filas = (new UsuarioModel())->orderBy('id')->findAll();

        // DEBUG TEMPORAL — quitar cuando se resuelva el issue de producción devolviendo datos mock.
        error_log('[DEBUG usuarios.index] host=' . ($_SERVER['HTTP_HOST'] ?? '?') . ' filas_en_bd=' . count($filas) . ' usuario_id_en_sesion=' . (session()->get('usuario_id') ?? 'null'));

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
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

    private function toDto(array $fila): array
    {
        $db = db_connect();
        $permisos = $db->table('usuario_permisos')
            ->select('permiso_clave')
            ->where('usuario_id', $fila['id'])
            ->get()
            ->getResultArray();

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
