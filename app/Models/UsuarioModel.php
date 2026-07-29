<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'nombre', 'usuario', 'password_hash', 'rol', 'apodo', 'tema', 'estado',
        'cuenta_cliente_id', 'tipo_usuario_id', 'origen', 'correo', 'foto_url', 'vigencia_alumno_hasta',
        'origen_cambiado_por_id', 'origen_cambiado_en', 'disponible',
    ];
}
