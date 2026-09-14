<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Nuevo tipo de campo/columna "Archivo" (ver plan de implementación): el binario sube al mismo
// bucket S3 que ya usan los Excel de plantilla, así que se registra en la misma tabla `archivos`
// — pero un archivo de campo no pertenece a una plantilla ni a un ejemplo concretos (es contenido
// suelto del usuario, igual de "huérfano" que ya son las imágenes de Cloudinary hoy), por eso el
// tercer valor de `propietario_tipo` ('campo') exige AMBOS `plantilla_id`/`ejemplo_id` en null, al
// revés de los otros dos que exigen exactamente uno de los dos.
class AllowCampoEnArchivosPropietario extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->db->query('ALTER TABLE archivos DROP CONSTRAINT chk_archivos_propietario_tipo');
        $this->db->query('ALTER TABLE archivos DROP CONSTRAINT chk_archivos_propietario');

        $this->addEnumCheck('archivos', 'propietario_tipo', ['plantilla', 'ejemplo', 'campo']);
        $this->db->query(
            'ALTER TABLE archivos ADD CONSTRAINT chk_archivos_propietario ' .
            "CHECK ((propietario_tipo = 'plantilla' AND plantilla_id IS NOT NULL AND ejemplo_id IS NULL) " .
            "OR (propietario_tipo = 'ejemplo' AND ejemplo_id IS NOT NULL AND plantilla_id IS NULL) " .
            "OR (propietario_tipo = 'campo' AND plantilla_id IS NULL AND ejemplo_id IS NULL))",
        );
    }

    public function down()
    {
        $this->db->query("DELETE FROM archivos WHERE propietario_tipo = 'campo'");

        $this->db->query('ALTER TABLE archivos DROP CONSTRAINT chk_archivos_propietario');
        $this->db->query('ALTER TABLE archivos DROP CONSTRAINT chk_archivos_propietario_tipo');

        $this->addEnumCheck('archivos', 'propietario_tipo', ['plantilla', 'ejemplo']);
        $this->db->query(
            'ALTER TABLE archivos ADD CONSTRAINT chk_archivos_propietario ' .
            "CHECK ((propietario_tipo = 'plantilla' AND plantilla_id IS NOT NULL AND ejemplo_id IS NULL) " .
            "OR (propietario_tipo = 'ejemplo' AND ejemplo_id IS NOT NULL AND plantilla_id IS NULL))",
        );
    }
}
