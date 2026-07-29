<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table = 'planes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'numero_nivel', 'nombre', 'precio', 'periodicidad',
        'limite_fichas_base', 'limite_consultas_base', 'limite_usuarios_base',
    ];
}
