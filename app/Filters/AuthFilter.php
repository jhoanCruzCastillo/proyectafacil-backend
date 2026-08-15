<?php

namespace App\Filters;

use App\Libraries\AuthToken;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

// Auth por Bearer token (HMAC JWT en Authorization). Sin token → 401 (no basta cookie PHP).
// Se aplica a grupos de rutas en app/Config/Routes.php (alias 'auth'), nunca a /api/auth/*.
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (AuthToken::hidratarSesionSiHay()) {
            return;
        }

        return service('response')
            ->setStatusCode(401)
            ->setJSON(['error' => 'No autenticado']);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
