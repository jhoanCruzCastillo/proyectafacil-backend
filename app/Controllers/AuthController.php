<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use CodeIgniter\HTTP\ResponseInterface;

// Contrato de respuesta: espejo de `Sesion` en frontend/src/types/index.ts (usuarioId, nombre,
// usuario, rol, iniciadaEn). login()/me() devuelven ese objeto o `null` en JSON plano — nunca un
// envelope — para que el frontend pueda tipar la respuesta 1:1 contra Promise<Sesion | null>.
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

        session()->set([
            'usuario_id'          => $fila['id'],
            'usuario_nombre'      => $fila['nombre'],
            'usuario_usuario'     => $fila['usuario'],
            'usuario_rol'         => $fila['rol'],
            'usuario_iniciada_en' => date(DATE_ATOM),
        ]);

        return $this->response->setJSON($this->sesionActual());
    }

    public function me(): ResponseInterface
    {
        return $this->response->setJSON($this->sesionActual());
    }

    public function logout(): ResponseInterface
    {
        session()->destroy();

        return $this->response->setJSON((object) []);
    }

    private function sesionActual(): ?array
    {
        $session = session();
        if (! $session->get('usuario_id')) {
            return null;
        }

        return [
            'usuarioId'  => (string) $session->get('usuario_id'),
            'nombre'     => $session->get('usuario_nombre'),
            'usuario'    => $session->get('usuario_usuario'),
            'rol'        => $session->get('usuario_rol'),
            'iniciadaEn' => $session->get('usuario_iniciada_en'),
        ];
    }
}
