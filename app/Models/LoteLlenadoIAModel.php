<?php

namespace App\Models;

use CodeIgniter\Model;

class LoteLlenadoIAModel extends Model
{
    protected $table = 'llenado_ia_lotes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'ejemplo_id', 'openai_batch_id', 'openai_file_id', 'estado', 'mapeo_json', 'resultado_json', 'error', 'reintentos',
    ];
}
