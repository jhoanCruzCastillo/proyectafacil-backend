<?php

namespace App\Models;

use CodeIgniter\Model;

class SectorModel extends Model
{
    protected $table = 'sectores';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['codigo', 'nombre', 'icono', 'color_accent', 'descripcion', 'tipo_sector', 'activo'];
}
