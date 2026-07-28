<?php

namespace App\Models;

use CodeIgniter\Model;

class SesionMentoriaModel extends Model
{
    protected $table = 'sesiones_mentoria';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'tema', 'mentor', 'fecha', 'cupos_totales', 'link_reunion', 'grabacion_url',
    ];
}
