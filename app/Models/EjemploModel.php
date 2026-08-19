<?php

namespace App\Models;

use CodeIgniter\Model;

class EjemploModel extends Model
{
    protected $table = 'ejemplos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'plantilla_id', 'nombre', 'subtitulo', 'detalle', 'activo',
        'propietario_usuario_id', 'creado_por_usuario_id', 'compartida', 'estado',
        'excel_actualizado', 'fuente_verdad_texto', 'es_referencia_ia',
    ];
}
