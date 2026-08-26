<?php

namespace App\Models;

use CodeIgniter\Model;

class ActividadModel extends Model
{
    protected $table = 'actividad_reciente';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['mensaje', 'color', 'created_at', 'actor_id', 'objetivo_id', 'categoria'];
}
