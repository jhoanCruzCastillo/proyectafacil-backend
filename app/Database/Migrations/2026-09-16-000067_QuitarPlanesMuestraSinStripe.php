<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// `FacturacionController::get()` regalaba Nivel 1 (Visa 4242, sin Stripe) al consultar
// facturación — al crear un cliente/alumno o abrir "Membresía y pagos". Ese código ya no
// existe; esta migración limpia las filas de muestra que quedaron. Idempotente: si no hay
// coincidencias, no hace nada. No toca compras reales (customer/subscription de Stripe) ni
// planes asignados por admin (sin tarjeta 4242).
class QuitarPlanesMuestraSinStripe extends Migration
{
    public function up()
    {
        if (
            ! $this->db->tableExists('facturaciones')
            || ! $this->db->tableExists('usuarios')
            || ! $this->db->fieldExists('stripe_subscription_id', 'facturaciones')
        ) {
            return;
        }

        $filas = $this->db->table('facturaciones')
            ->select('facturaciones.usuario_id')
            ->join('usuarios', 'usuarios.id = facturaciones.usuario_id')
            ->where('usuarios.rol', 'cliente')
            ->where('facturaciones.tarjeta_ultimos4', '4242')
            ->groupStart()
                ->where('facturaciones.stripe_subscription_id', null)
                ->orWhere('facturaciones.stripe_subscription_id', '')
            ->groupEnd()
            ->groupStart()
                ->where('facturaciones.stripe_customer_id', null)
                ->orWhere('facturaciones.stripe_customer_id', '')
            ->groupEnd()
            ->get()
            ->getResultArray();

        $ids = array_values(array_unique(array_map(static fn (array $f) => (int) $f['usuario_id'], $filas)));
        if ($ids === []) {
            return;
        }

        if ($this->db->tableExists('tickets_consulta')) {
            $this->db->table('tickets_consulta')
                ->where('origen', 'plan')
                ->whereIn('usuario_id', $ids)
                ->delete();
        }

        $this->db->table('facturaciones')->whereIn('usuario_id', $ids)->delete();
    }

    public function down()
    {
        // Limpieza de datos de muestra — no hay restauración sensata.
    }
}
