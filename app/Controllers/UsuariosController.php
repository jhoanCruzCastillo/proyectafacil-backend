<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use CodeIgniter\HTTP\ResponseInterface;

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

        if ($cambios !== []) {
            $model->update($id, $cambios);
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
