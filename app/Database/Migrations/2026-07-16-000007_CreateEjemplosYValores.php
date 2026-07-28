<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

class CreateEjemplosYValores extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        // ejemplos
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'plantilla_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 200],
            'subtitulo' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'detalle' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            // NULL = ejemplo de referencia autorado por el admin. No-NULL = ficha de un cliente
            // (usuarioId de la cuenta titular, ver cuentaEfectivaDe en el prototipo).
            'propietario_usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'creado_por_usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'compartida' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('plantilla_id', 'plantillas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('propietario_usuario_id', 'usuarios', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('creado_por_usuario_id', 'usuarios', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('ejemplos');

        // ejemplo_tipologia_ioarr — descompone Ejemplo.tipologiasIoarr[]
        $this->forge->addField([
            'ejemplo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipologia' => $this->enumField(['optimizacion', 'ampliacion_marginal', 'reposicion', 'rehabilitacion']),
        ]);
        $this->forge->addPrimaryKey(['ejemplo_id', 'tipologia']);
        $this->forge->addForeignKey('ejemplo_id', 'ejemplos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ejemplo_tipologia_ioarr');
        $this->addEnumCheck('ejemplo_tipologia_ioarr', 'tipologia', ['optimizacion', 'ampliacion_marginal', 'reposicion', 'rehabilitacion']);

        // archivos — tabla única para todo par (Excel, JSON) del sistema, sin importar de quién es:
        // la Estructura de una plantilla, o el snapshot de un ejemplo/ficha. Reemplaza a
        // archivos_excel + archivos_ejemplo (que a su vez ya reemplazaban a archivos_excel_ejemplo +
        // valores_campo de v1) — evita crear una tabla "archivos_X" nueva cada vez que aparece un
        // dueño distinto. plantilla_id/ejemplo_id son mutuamente excluyentes: el CHECK de abajo
        // obliga a que solo uno esté lleno, coherente con propietario_tipo.
        //
        // url = Excel (binario, va a Cloudinary — sí necesita CDN/entrega). contenido_json = el
        // documento de estructura/valores en sí — vive directo en la fila (JSON nativo), no como
        // string apuntando a un archivo externo: es contenido que el backend lee/escribe en cada
        // petición del editor, no un medio para servir vía CDN. Misma justificación que la excepción
        // deliberada de valor_json en v1 (ver "Excepciones deliberadas" en docs/database-design.md),
        // ahora aplicada al documento completo en vez de a una sola celda.
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'propietario_tipo' => $this->enumField(['plantilla', 'ejemplo']),
            'plantilla_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'ejemplo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 200],
            'url' => ['type' => 'VARCHAR', 'constraint' => 500],
            'contenido_json' => ['type' => 'JSON', 'null' => true],
            'fecha_subida' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        // Único NULL-able: un ejemplo tiene como máximo 1 archivo propio (1:1), a diferencia de una
        // plantilla, que puede tener varios candidatos en su catálogo (solo uno "asignado").
        $this->forge->addUniqueKey('ejemplo_id');
        $this->forge->addForeignKey('plantilla_id', 'plantillas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ejemplo_id', 'ejemplos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('archivos');
        $this->addEnumCheck('archivos', 'propietario_tipo', ['plantilla', 'ejemplo']);
        $this->db->query(
            'ALTER TABLE archivos ADD CONSTRAINT chk_archivos_propietario ' .
            "CHECK ((propietario_tipo = 'plantilla' AND plantilla_id IS NOT NULL AND ejemplo_id IS NULL) " .
            "OR (propietario_tipo = 'ejemplo' AND ejemplo_id IS NOT NULL AND plantilla_id IS NULL))",
        );

        // Ahora sí: qué archivo está activo para cada plantilla (puntero hacia su catálogo en `archivos`)
        $this->forge->addColumn('plantillas', [
            'asignado_archivo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'archivo_default_url'],
        ]);
        $this->forge->addForeignKey('asignado_archivo_id', 'archivos', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('plantillas');
    }

    public function down()
    {
        $this->forge->dropForeignKey('plantillas', 'plantillas_asignado_archivo_id_foreign');
        $this->forge->dropColumn('plantillas', 'asignado_archivo_id');
        $this->forge->dropTable('archivos');
        $this->forge->dropTable('ejemplo_tipologia_ioarr');
        $this->forge->dropTable('ejemplos');
    }
}
