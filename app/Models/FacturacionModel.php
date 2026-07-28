<?php

namespace App\Models;

use CodeIgniter\Model;

class FacturacionModel extends Model
{
    protected $table = 'facturaciones';
    protected $primaryKey = 'usuario_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'usuario_id', 'plan_id', 'cancelada', 'fecha_renovacion', 'fecha_inicio_plan',
        'metodo_pago', 'tarjeta_marca', 'tarjeta_ultimos4', 'telefono_pago',
    ];
}
