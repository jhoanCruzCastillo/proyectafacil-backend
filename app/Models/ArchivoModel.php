<?php

namespace App\Models;

use CodeIgniter\Model;

// Tabla única archivos (ver docs/database-design.md "Tabla unificada archivos") — filas con
// propietario_tipo='plantilla' son el catálogo de Excel+JSON de una plantilla; propietario_tipo=
// 'ejemplo' es el snapshot 1:1 de un ejemplo/ficha (Módulo 2, aún no implementado).
class ArchivoModel extends Model
{
    protected $table = 'archivos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'propietario_tipo', 'plantilla_id', 'ejemplo_id', 'nombre', 'url', 'contenido_json', 'fecha_subida',
    ];
}
