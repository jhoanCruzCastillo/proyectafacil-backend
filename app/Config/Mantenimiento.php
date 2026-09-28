<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Modo mantenimiento — activarlo/desactivarlo y cambiar el mensaje es solo editar el .env del
// backend, sin tocar código ni el frontend (ver EstadoSistemaController::index, consumido por el
// frontend al arrancar la SPA — frontend/src/router/index.ts). Pedido explícito del usuario: un
// interruptor rápido por variable de entorno, sin rebuild.
//
// En el .env del backend:
//   mantenimiento.activo = true
//   mantenimiento.mensaje = Estamos actualizando la plataforma. Volvemos en unos minutos.
class Mantenimiento extends BaseConfig
{
    public bool $activo = false;
    public string $mensaje = 'La plataforma está en mantenimiento. Volvemos en unos minutos.';
}
