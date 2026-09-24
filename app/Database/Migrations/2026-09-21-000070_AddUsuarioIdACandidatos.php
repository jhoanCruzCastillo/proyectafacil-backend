<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Enlaza la postulación con la cuenta de asesor que se crea al aprobarla.
//
// Hasta ahora `candidatos` y `usuarios` vivían desconectadas: aprobar un candidato solo cambiaba
// su `estado`, así que nunca aparecía en "Especialistas › Docentes / Asesores" (esa lista sale de
// `usuarios` con rol='asesor'). La promoción ya es funcional (ver CandidatosController::promover),
// y esta columna es lo que la hace **idempotente y rastreable**: si el candidato ya fue promovido,
// se sabe a qué cuenta, y volver a aprobar no crea un duplicado.
//
// Nullable a propósito: un candidato en cualquier estado previo a 'aprobado' —y cualquiera de los
// desaprobados— no tiene cuenta. ON DELETE SET NULL para que borrar la cuenta del asesor no
// arrastre consigo el historial de su postulación, que es un registro con valor propio.
class AddUsuarioIdACandidatos extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('usuario_id', 'candidatos')) {
            $this->forge->addColumn('candidatos', [
                'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            ]);
            $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'SET NULL', 'candidatos');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('usuario_id', 'candidatos')) {
            $this->forge->dropForeignKey('candidatos', 'candidatos_usuario_id_foreign');
            $this->forge->dropColumn('candidatos', 'usuario_id');
        }
    }
}
