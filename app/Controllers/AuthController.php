<?php

namespace App\Controllers;

use App\Libraries\AuthToken;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\ResponseInterface;

// Contrato de respuesta: espejo de `Sesion` en frontend/src/types/index.ts (usuarioId, nombre,
// usuario, rol, iniciadaEn). login() además incluye `token` (Bearer JWT). me() devuelve Sesion
// o null. Auth por Authorization: Bearer — no depende de la cookie de sesión PHP.
class AuthController extends BaseController
{
    public function login(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];
        $usuario = trim((string) ($data['usuario'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $fila = (new UsuarioModel())
            ->where('usuario', $usuario)
            ->where('estado', 'activo')
            ->first();

        if (! $fila || ! password_verify($password, $fila['password_hash'])) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Credenciales inválidas']);
        }

        $sesion = [
            'usuarioId'  => (string) $fila['id'],
            'nombre'     => $fila['nombre'],
            'usuario'    => $fila['usuario'],
            'rol'        => $fila['rol'],
            'iniciadaEn' => date(DATE_ATOM),
        ];

        return $this->response->setJSON([
            ...$sesion,
            'token' => AuthToken::emitir($sesion),
        ]);
    }

    public function me(): ResponseInterface
    {
        return $this->response->setJSON($this->sesionActual());
    }

    public function logout(): ResponseInterface
    {
        // El token es JWT sin denylist: el cliente borra localStorage. Limpiamos sesión PHP
        // por si queda residual de versiones anteriores.
        session()->destroy();

        return $this->response->setJSON((object) []);
    }

    private function sesionActual(): ?array
    {
        // Solo Bearer: sin Authorization válido no hay sesión (alineado con AuthFilter).
        return AuthToken::desdeRequest();
    }
}
