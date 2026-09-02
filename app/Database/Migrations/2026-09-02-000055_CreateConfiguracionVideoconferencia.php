<?php

namespace App\Database\Migrations;

use App\Database\Migrations\Support\PortableEnumTrait;
use CodeIgniter\Database\Migration;

// Registro único de configuración (mismo patrón que configuracion_sla) para el tipo de acceso de
// las videollamadas de asesoría — ver GoogleMeetService::crearLinkReunion() y
// SolicitudAsesoriaHelpersTrait::tipoAccesoVideollamada(). 'abierta' = cualquiera con el link entra
// directo (Meet accessType OPEN); 'invitados' = solo los invitados directos entran sin tocar la
// puerta, el resto queda en la sala de espera (Meet accessType TRUSTED).
class CreateConfiguracionVideoconferencia extends Migration
{
    use PortableEnumTrait;

    public function up()
    {
        if (! $this->db->tableExists('configuracion_videoconferencia')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tipo_acceso' => $this->enumField(['abierta', 'invitados'], 'abierta'),
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('configuracion_videoconferencia');

            try {
                $this->addEnumCheck('configuracion_videoconferencia', 'tipo_acceso', ['abierta', 'invitados']);
            } catch (\Throwable $e) {
                // Constraint ya existe (re-corrida de la migración) — nada que hacer.
            }
        }

        if ($this->db->table('configuracion_videoconferencia')->countAllResults() === 0) {
            $this->db->table('configuracion_videoconferencia')->insert(['tipo_acceso' => 'abierta']);
        }
    }

    public function down()
    {
        $this->forge->dropTable('configuracion_videoconferencia', true);
    }
}
