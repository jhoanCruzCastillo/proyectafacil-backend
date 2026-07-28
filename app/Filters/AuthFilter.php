<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

// Sesión CI4 + cookie httpOnly (no JWT) — decisión ya documentada en Notion "STACK TÉCNICO": un solo
// dominio permite cookie de sesión same-origin sin fricción de CORS. Se aplica a grupos de rutas en
// app/Config/Routes.php (alias 'auth', registrado en app/Config/Filters.php), nunca a /api/auth/*.
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('usuario_id')) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'No autenticado']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
