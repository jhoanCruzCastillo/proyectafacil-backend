<?php

namespace App\Models;

use CodeIgniter\Model;

class SesionModel extends Model
{
    protected $table      = 'sesiones';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['usuario_id', 'dispositivo', 'navegador', 'ip', 'ubicacion', 'revocada', 'revocada_en', 'ultima_actividad', 'created_at'];
}
