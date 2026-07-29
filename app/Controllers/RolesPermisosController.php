<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Pantalla "Gestionar roles y permisos base" (Ruta B). Distinto de UsuariosController::sincronizarPermisos
// (que guarda overrides de UN usuario en usuario_permisos) — esto guarda el set por defecto de
// TODO un rol en roles_permisos_base. Superusuario no es editable (siempre acceso total) y no
// tiene filas propias, así que ni aparece en la lista de roles editables.
class RolesPermisosController extends BaseController
{
    private const ROLES_EDITABLES = ['administrador', 'administrativo_asesorias', 'cliente', 'asesor'];

    public function index(): ResponseInterface
    {
        $filas = db_connect()->table('roles_permisos_base')->get()->getResultArray();

        $mapa = array_fill_keys(self::ROLES_EDITABLES, []);
        foreach ($filas as $fila) {
            if (isset($mapa[$fila['rol']])) {
                $mapa[$fila['rol']][] = $fila['permiso_clave'];
            }
        }

        return $this->response->setJSON($mapa);
    }

    public function update($rol = null): ResponseInterface
    {
        if (! in_array($rol, self::ROLES_EDITABLES, true)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Rol no editable']);
        }

        $dto = $this->request->getJSON(true) ?? [];
        $permisos = $dto['permisos'] ?? [];

        $db = db_connect();
        $db->table('roles_permisos_base')->where('rol', $rol)->delete();
        foreach ($permisos as $clave) {
            $db->table('roles_permisos_base')->insert(['rol' => $rol, 'permiso_clave' => $clave]);
        }

        return $this->response->setJSON(array_values($permisos));
    }
}
