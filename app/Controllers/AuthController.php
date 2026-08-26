<?php

namespace App\Controllers;

use App\Libraries\AuthToken;
use App\Libraries\CorreoService;
use App\Libraries\UserAgentParser;
use App\Models\SesionModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Stripe as StripeConfig;
use Throwable;

// Contrato de respuesta: espejo de `Sesion` en frontend/src/types/index.ts (usuarioId, nombre,
// usuario, rol, iniciadaEn). login() además incluye `token` (Bearer JWT) y `tienePlan`. me()
// devuelve Sesion o null. Auth por Authorization: Bearer — no depende de la cookie de sesión PHP.
//
// `tienePlan` viaja SIEMPRE fuera del JWT (no es un claim de identidad, es un estado que cambia
// apenas alguien compra un plan — meterlo en el token lo dejaría desactualizado hasta el próximo
// login) y se recalcula en cada llamada a login()/me() con un chequeo liviano y SIN efectos
// colaterales: a propósito nunca se llama a FacturacionController::crearDefault() desde acá —
// ver el plan de implementación de "registro público" para el porqué (un cliente sin plan debe
// ver únicamente la pantalla de elegir plan, nunca uno de mentira asignado solo por consultar el
// estado de su sesión).
class AuthController extends BaseController
{
    public function login(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];
        $usuario = trim((string) ($data['usuario'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        // El registro público (register()) nunca le muestra a la persona el `usuario` autogenerado
        // — solo pide correo y contraseña — así que el login acepta cualquiera de los dos como
        // identificador, igual que el nombre de usuario elegido por un admin desde el panel.
        $fila = (new UsuarioModel())
            ->groupStart()
                ->where('usuario', $usuario)
                ->orWhere('correo', $usuario)
            ->groupEnd()
            ->first();

        if (! $fila || ! password_verify($password, $fila['password_hash'])) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Credenciales inválidas']);
        }

        if ($fila['estado'] === 'pendiente_verificacion') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Todavía no verificaste tu correo. Revisa tu bandeja de entrada para activar tu cuenta.']);
        }

        if ($fila['estado'] !== 'activo') {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Credenciales inválidas']);
        }

        (new UsuarioModel())->update((int) $fila['id'], ['ultimo_acceso' => date('Y-m-d H:i:s')]);

        $userAgent = (string) $this->request->getUserAgent();
        $sesionId  = (new SesionModel())->insert([
            'usuario_id'       => (int) $fila['id'],
            'dispositivo'      => UserAgentParser::dispositivo($userAgent),
            'navegador'        => UserAgentParser::navegador($userAgent),
            'ip'               => $this->request->getIPAddress(),
            'ubicacion'        => 'Lima, Perú',
            'ultima_actividad' => date('Y-m-d H:i:s'),
            'created_at'       => date('Y-m-d H:i:s'),
        ], true);

        $sesion = [
            'usuarioId'  => (string) $fila['id'],
            'nombre'     => $fila['nombre'],
            'usuario'    => $fila['usuario'],
            'rol'        => $fila['rol'],
            'iniciadaEn' => date(DATE_ATOM),
        ];

        return $this->response->setJSON([
            ...$sesion,
            'token'     => AuthToken::emitir($sesion, (string) $sesionId),
            'tienePlan' => $this->tienePlan($fila['rol'], (int) $fila['id']),
        ]);
    }

    public function me(): ResponseInterface
    {
        $sesion = $this->sesionActual();
        if ($sesion === null) {
            return $this->response->setJSON(null);
        }

        return $this->response->setJSON([
            ...$sesion,
            'tienePlan' => $this->tienePlan($sesion['rol'], (int) $sesion['usuarioId']),
        ]);
    }

    public function logout(): ResponseInterface
    {
        // auth/logout no pasa por el filtro 'auth' (ver Routes.php), así que hay que leer el
        // Bearer a mano acá para revocar la fila en `sesiones` — si no, el token seguiría siendo
        // válido para otras pestañas/dispositivos hasta que expire solo.
        $claims = AuthToken::desdeRequest();
        if ($claims !== null && $claims['sesionId'] !== '') {
            (new SesionModel())->update((int) $claims['sesionId'], ['revocada' => 1, 'revocada_en' => date('Y-m-d H:i:s')]);
        }

        // Limpiamos sesión PHP por si queda residual de versiones anteriores.
        session()->destroy();

        return $this->response->setJSON((object) []);
    }

    // Registro público — pedido del cliente de ATIENDO. A diferencia de UsuariosController::create()
    // (que lo usa un admin ya logueado y genera una contraseña temporal), acá la persona elige su
    // propia contraseña y la cuenta queda 'pendiente_verificacion' hasta confirmar el correo — no
    // se manda ninguna contraseña por correo en este flujo. `origen` siempre queda en 'externo':
    // 'alumno' es un estado que solo asigna un admin (son alumnos reales del cliente) — pedido
    // explícito del usuario. `preferencia_registro` (la respuesta a "¿cuál te describe mejor?") es
    // puramente informativa, sin ningún efecto en permisos.
    public function register(): ResponseInterface
    {
        $dto = $this->request->getJSON(true) ?? [];
        $nombre = trim((string) ($dto['nombre'] ?? ''));
        $correo = trim((string) ($dto['correo'] ?? ''));
        $password = (string) ($dto['password'] ?? '');
        $preferencia = trim((string) ($dto['preferencia'] ?? ''));
        $sectorIds = is_array($dto['sectorIds'] ?? null) ? array_map('intval', $dto['sectorIds']) : [];

        if ($nombre === '' || $correo === '' || mb_strlen($password) < 8) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Completa nombre, correo y una contraseña de al menos 8 caracteres.']);
        }
        if (! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'El correo no es válido.']);
        }

        $model = new UsuarioModel();
        if ($model->where('correo', $correo)->first()) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Ya existe una cuenta con ese correo.']);
        }

        $token = bin2hex(random_bytes(32));
        $id = $model->insert([
            'nombre'                    => $nombre,
            'usuario'                   => $this->usuarioDisponibleDesde($correo),
            'correo'                    => $correo,
            'password_hash'             => password_hash($password, PASSWORD_DEFAULT),
            'rol'                       => 'cliente',
            'origen'                    => 'externo',
            'preferencia_registro'      => $preferencia !== '' ? $preferencia : null,
            'estado'                    => 'pendiente_verificacion',
            'token_verificacion'        => $token,
            'token_verificacion_expira' => date('Y-m-d H:i:s', strtotime('+24 hours')),
        ], true);

        if ($sectorIds !== []) {
            $db = db_connect();
            $sectoresValidos = $db->table('sectores')->whereIn('id', $sectorIds)->get()->getResultArray();
            foreach ($sectoresValidos as $s) {
                $db->table('cliente_intereses')->ignore(true)->insert(['usuario_id' => $id, 'sector_id' => $s['id']]);
            }
        }

        $urlVerificacion = rtrim(config(StripeConfig::class)->frontendBaseUrl, '/') . '/verificar-correo/' . $token;
        try {
            (new CorreoService())->enviarVerificacion($correo, $nombre, $urlVerificacion);
        } catch (Throwable $e) {
            // La cuenta ya quedó creada — no se revierte por un hipo de correo. El admin puede
            // reenviar el acceso a mano desde "Usuarios y permisos" si hiciera falta.
            log_message('error', '[registro] No se pudo enviar el correo de verificación a {correo}: {msg}', ['correo' => $correo, 'msg' => $e->getMessage()]);
        }

        return $this->response->setJSON(['registrado' => true]);
    }

    // Público, sin sesión — el link viaja por correo. Idempotente: si ya estaba activo (alguien
    // hizo doble clic en el link), no falla, solo confirma de nuevo.
    public function verificar($token = null): ResponseInterface
    {
        $model = new UsuarioModel();
        $fila = $model->where('token_verificacion', (string) $token)->first();

        if (! $fila) {
            return $this->response->setStatusCode(410)->setJSON(['error' => 'Este enlace ya no es válido — puede que ya hayas confirmado tu correo, o que haya vencido.']);
        }
        if ($fila['token_verificacion_expira'] !== null && strtotime($fila['token_verificacion_expira']) < time()) {
            return $this->response->setStatusCode(410)->setJSON(['error' => 'Este enlace venció. Contáctanos para que te ayudemos a activar tu cuenta.']);
        }

        $model->update($fila['id'], [
            'estado'                    => 'activo',
            'token_verificacion'        => null,
            'token_verificacion_expira' => null,
        ]);

        return $this->response->setJSON(['verificado' => true]);
    }

    private function tienePlan(string $rol, int $usuarioId): bool
    {
        if ($rol !== 'cliente') {
            return true; // no aplica — no se usa para gatear nada fuera del rol cliente.
        }

        return db_connect()->table('facturaciones')->where('usuario_id', $usuarioId)->countAllResults() > 0;
    }

    /** "maria.perez@dominio.com" -> "maria.perez", con sufijo numérico si ya existe. */
    private function usuarioDisponibleDesde(string $correo): string
    {
        $base = strtolower((string) preg_replace('/[^a-z0-9._-]/i', '', strstr($correo, '@', true) ?: $correo));
        $base = $base !== '' ? $base : 'usuario';
        $model = new UsuarioModel();

        $candidato = $base;
        $sufijo = 1;
        while ($model->where('usuario', $candidato)->first()) {
            $sufijo++;
            $candidato = $base . $sufijo;
        }

        return $candidato;
    }

    private function sesionActual(): ?array
    {
        // Solo Bearer: sin Authorization válido no hay sesión (alineado con AuthFilter).
        return AuthToken::desdeRequest();
    }
}
