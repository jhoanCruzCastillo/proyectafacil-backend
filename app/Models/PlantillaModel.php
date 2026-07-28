<?php

namespace App\Models;

use CodeIgniter\Model;

class PlantillaModel extends Model
{
    protected $table = 'plantillas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'sector_id', 'codigo', 'nombre', 'descripcion', 'instrumento', 'fecha_actualizacion',
        'archivo_default_url', 'disponible_nivel0', 'asignado_archivo_id', 'estado',
    ];
}
