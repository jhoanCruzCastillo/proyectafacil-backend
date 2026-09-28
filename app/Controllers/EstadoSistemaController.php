<?php

namespace App\Controllers;

use Config\Mantenimiento;
use CodeIgniter\HTTP\ResponseInterface;

// Endpoint público (sin sesión, ver Routes.php) que el frontend consulta al arrancar la SPA para
// saber si debe redirigir todo a la página de mantenimiento — ver frontend/src/router/index.ts.
// El interruptor y el mensaje viven en el .env del backend (ver Config\Mantenimiento), nunca en
// código: cambiarlos no requiere rebuild ni redeploy del frontend, solo editar el .env.
class EstadoSistemaController extends BaseController
{
    public function index(): ResponseInterface
    {
        $config = config(Mantenimiento::class);

        return $this->response->setJSON([
            'mantenimiento' => $config->activo,
            'mensaje'       => $config->mensaje,
        ]);
    }
}
