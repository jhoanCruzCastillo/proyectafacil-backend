<?php

namespace App\Models;

use CodeIgniter\Model;

class HistorialCambioModel extends Model
{
    protected $table = 'historial_cambios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['ejemplo_id', 'usuario_id', 'fecha'];
}
