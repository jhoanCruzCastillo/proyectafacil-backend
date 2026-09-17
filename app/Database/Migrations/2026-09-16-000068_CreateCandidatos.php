<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// candidatos — postulaciones públicas al equipo de especialistas ILPIIE Live, recibidas desde el
// formulario /registro-especialista. Tabla independiente de `usuarios`: un candidato NO es una
// cuenta con login hasta que el equipo lo apruebe y decida promoverlo (fuera de alcance de esta
// migración) — mismo razonamiento que "candidato != empleado" en cualquier ATS. El estado modela
// el flujo pedido por el cliente: registrado → en_evaluacion → para_entrevista → aprobado/
// desaprobado (mismo patrón portable VARCHAR+CHECK que el resto del proyecto, ver
// PortableEnumTrait; columna VARCHAR(30) desde el día uno aunque el valor más largo hoy
// ("para_entrevista", 15) entraría en 20, porque este enum es exactamente el tipo que ya se vio
// crecer más adelante en `solicitudes_asesoria.estado`).
//
// `password_hash`: el formulario pide contraseña desde la postulación para que, si en una fase
// futura se promueve el candidato a una cuenta `usuarios`, no haga falta un paso aparte de
// "define tu contraseña" — se guarda hasheada (password_hash(), nunca en texto plano, ver
// CLAUDE.md) pero no se usa para nada todavía en esta pasada.
//
// `cv_url`/`cv_nombre_original`: el archivo en sí va a Cloudinary/S3 (CandidatoDocumentoStorage,
// mismo patrón que AdjuntoChatStorage), acá solo la URL/clave devuelta y el nombre original para
// el nombre de descarga.
//
// `actividades`: JSON de los 7 valores fijos del mockup (asesorías en vivo, ponencias/docencia,
// investigaciones...) — sin catálogo propio en su propia tabla porque, a diferencia de los temas
// de especialidad, esta lista no se reutiliza en ningún otro lugar de la app.
//
// candidato_temas_especialidad — mismo patrón exacto que asesor_temas_especialidad
// (2026-09-13-000063_CreateAsesorTemasEspecialidad.php): PK compuesta, FKs CASCADE/CASCADE, sin id
// propio ni timestamps, apuntando al catálogo ya existente `temas_especialidad` (el mismo que ya
// sirve TemasEspecialidadController::publico() — se reutiliza en vez de duplicar el catálogo).
//
// candidato_disponibilidad — un bloque de 1 hora por fila (selección discreta de casillas del
// calendario del formulario, no un rango contiguo como horarios_docente). dia_semana 1=lunes ..
// 6=sábado, igual numeración que horarios_docente pero sin domingo (el formulario no lo ofrece).
class CreateCandidatos extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nombre'              => ['type' => 'VARCHAR', 'constraint' => 150],
            'dni'                 => ['type' => 'VARCHAR', 'constraint' => 12],
            'correo'              => ['type' => 'VARCHAR', 'constraint' => 150],
            'telefono'            => ['type' => 'VARCHAR', 'constraint' => 30],
            'password_hash'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'profesion'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'nivel_academico'     => ['type' => 'VARCHAR', 'constraint' => 60],
            'colegiatura'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'anios_experiencia'   => ['type' => 'VARCHAR', 'constraint' => 30],
            'nivel_especialidad'  => $this->enumField(['Especialista', 'Senior', 'Altamente especializado']),
            'otros_temas'         => ['type' => 'TEXT', 'null' => true],
            'actividades'         => ['type' => 'TEXT'],
            'cv_url'              => ['type' => 'VARCHAR', 'constraint' => 500],
            'cv_nombre_original'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'linkedin'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'otras_redes'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'comentarios'         => ['type' => 'TEXT', 'null' => true],
            'estado'              => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'registrado'],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('dni');
        $this->forge->addUniqueKey('correo');
        $this->forge->createTable('candidatos');
        $this->addEnumCheck('candidatos', 'nivel_especialidad', ['Especialista', 'Senior', 'Altamente especializado']);
        $this->addEnumCheck('candidatos', 'estado', ['registrado', 'en_evaluacion', 'para_entrevista', 'aprobado', 'desaprobado']);

        $this->forge->addField([
            'candidato_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tema_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['candidato_id', 'tema_id']);
        $this->forge->addForeignKey('candidato_id', 'candidatos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('tema_id', 'temas_especialidad', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('candidato_temas_especialidad');

        $this->forge->addField([
            'candidato_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'dia_semana'   => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true],
            'hora_inicio'  => ['type' => 'TIME'],
        ]);
        $this->forge->addPrimaryKey(['candidato_id', 'dia_semana', 'hora_inicio']);
        $this->forge->addForeignKey('candidato_id', 'candidatos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('candidato_disponibilidad');
    }

    public function down()
    {
        $this->forge->dropTable('candidato_disponibilidad');
        $this->forge->dropTable('candidato_temas_especialidad');
        $this->forge->dropTable('candidatos');
    }
}
