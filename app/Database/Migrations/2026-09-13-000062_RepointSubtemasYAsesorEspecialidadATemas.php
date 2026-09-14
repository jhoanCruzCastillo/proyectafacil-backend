<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// `subtemas_especialidad` apuntaba a `sectores` (sector_id) — pasa a apuntar a `temas_especialidad`
// (tema_id), la tabla nueva creada en la migración anterior. Los subtemas son de un TEMA de
// especialidad (Proyectos de Inversión → Formatos, Fichas Técnicas...), no de un sector MEF.
//
// OJO: `asesor_especialidades` (usuario_id, sector_id) NO se toca acá — sigue apuntando a
// `sectores` tal cual siempre estuvo, porque sigue siendo el mecanismo real de
// SolicitudAsesoriaHelpersTrait::asesoresPorSector() para el aviso automático de solicitudes de
// chat (filtra asesores por el sector MEF de la ficha). La especialidad del asesor por TEMA vive en
// una tabla nueva y separada, `asesor_temas_especialidad` (ver migración siguiente) — el asesor
// ahora marca AMBAS cosas: "Sectores que atiendes" (sectores, para el aviso automático) y "Temas de
// especialidad" (temas_especialidad, informativo/para asesoría puntual).
//
// Postgres permite renombrar una columna sin tocar sus constraints existentes (PK/UNIQUE se
// resuelven por posición interna, no por nombre) — solo hace falta recrear el FOREIGN KEY apuntando
// a la tabla nueva, y de paso se renombran los constraints para que su nombre no quede mintiendo
// que todavía hablan de "sector".
class RepointSubtemasYAsesorEspecialidadATemas extends Migration
{
    public function up()
    {
        // Vacía la tabla antes de repuntar el FK: las filas que ya existan (todas demo/transitorias,
        // sembradas por SubtemasEspecialidadSeeder bajo la vieja tabla `sectores`) apuntan a ids que
        // no existen todavía en `temas_especialidad` (recién creada, vacía) — agregar el FOREIGN KEY
        // con filas violándolo falla la migración. Se reseeda después con
        // php spark db:seed SubtemasEspecialidadSeeder.
        // DELETE en vez de TRUNCATE: solicitudes_asesoria.subtema_id referencia esta tabla (ON
        // DELETE SET NULL) — Postgres no permite TRUNCATE con una FK activa desde otra tabla, pero
        // sí un DELETE normal (respeta el SET NULL fila por fila).
        $this->db->query('DELETE FROM asesor_subtemas');
        $this->db->query('DELETE FROM subtemas_especialidad');

        $this->db->query('ALTER TABLE subtemas_especialidad DROP CONSTRAINT subtemas_especialidad_sector_id_foreign');
        $this->db->query('ALTER TABLE subtemas_especialidad RENAME COLUMN sector_id TO tema_id');
        $this->db->query('ALTER TABLE subtemas_especialidad RENAME CONSTRAINT subtemas_especialidad_sector_id_nombre TO subtemas_especialidad_tema_id_nombre');
        $this->db->query('ALTER TABLE subtemas_especialidad ADD CONSTRAINT subtemas_especialidad_tema_id_foreign FOREIGN KEY (tema_id) REFERENCES temas_especialidad(id) ON UPDATE CASCADE ON DELETE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE subtemas_especialidad DROP CONSTRAINT subtemas_especialidad_tema_id_foreign');
        $this->db->query('ALTER TABLE subtemas_especialidad RENAME CONSTRAINT subtemas_especialidad_tema_id_nombre TO subtemas_especialidad_sector_id_nombre');
        $this->db->query('ALTER TABLE subtemas_especialidad RENAME COLUMN tema_id TO sector_id');
        $this->db->query('ALTER TABLE subtemas_especialidad ADD CONSTRAINT subtemas_especialidad_sector_id_foreign FOREIGN KEY (sector_id) REFERENCES sectores(id) ON UPDATE CASCADE ON DELETE CASCADE');
    }
}
