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

        // DEBUG TEMPORAL — quitar cuando se resuelva el issue de producción devolviendo datos mock.
        error_log("[DEBUG login] host=" . ($_SERVER['HTTP_HOST'] ?? '?') . " origin=" . ($_SERVER['HTTP_ORIGIN'] ?? '?') . " usuario='{$usuario}'");

        $fila = (new UsuarioModel())
            ->where('usuario', $usuario)
            ->where('estado', 'activo')
            ->first();

        error_log('[DEBUG login] fila_encontrada=' . ($fila ? 'SI id=' . $fila['id'] . ' rol=' . $fila['rol'] : 'NO'));

        if (! $fila || ! password_verify($password, $fila['password_hash'])) {
            error_log('[DEBUG login] resultado=401 credenciales_invalidas');

            return $this->response->setStatusCode(401)->setJSON(['error' => 'Credenciales inválidas']);
        }

        session()->set([
            'usuario_id'          => $fila['id'],
            'usuario_nombre'      => $fila['nombre'],
            'usuario_usuario'     => $fila['usuario'],
            'usuario_rol'         => $fila['rol'],
            'usuario_iniciada_en' => date(DATE_ATOM),
        ]);

        error_log('[DEBUG login] resultado=200 sesion_creada usuario_id=' . $fila['id']);

        return $this->response->setJSON($this->sesionActual());
    }

    public function me(): ResponseInterface
    {
        // DEBUG TEMPORAL
        error_log('[DEBUG me] host=' . ($_SERVER['HTTP_HOST'] ?? '?') . ' cookie=' . (isset($_COOKIE[session_name()]) ? 'presente' : 'ausente') . ' usuario_id_en_sesion=' . (session()->get('usuario_id') ?? 'null'));

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
