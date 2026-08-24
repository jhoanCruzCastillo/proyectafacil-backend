<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Bug real descubierto al probar checkoutAddon() en vivo: `add_ons` nunca tuvo un constraint UNIQUE
// en `nombre` (a diferencia de `planes.numero_nivel`, que sí lo tiene) — así que cada re-run de
// PlanesYAddOnsSeeder::run() (el `ignore(true)->insert()` no tiene con qué chocar) insertaba 3 filas
// NUEVAS sin `stripe_price_id`, dejando el catálogo con duplicados. `PagosController` resuelve el
// add-on por `where('nombre', ...)->get()->getRowArray()` sin ORDER BY, así que a veces devolvía uno
// de esos duplicados sin precio → "Este add-on todavía no tiene un precio configurado en Stripe".
// Limpia los duplicados (se queda con el id más bajo de cada nombre — son los que ya tienen
// `stripe_price_id` seteado por el seeder) y agrega el UNIQUE que debió existir desde el inicio,
// para que `ignore(true)` en el seeder vuelva a ser un no-op real en los re-runs futuros.
class DedupeAddOnsYUniqueNombre extends Migration
{
    public function up()
    {
        $this->db->query('
            DELETE FROM add_ons a
            USING add_ons b
            WHERE a.nombre = b.nombre AND a.id > b.id
        ');

        $this->db->query('ALTER TABLE add_ons ADD CONSTRAINT uq_add_ons_nombre UNIQUE (nombre)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE add_ons DROP CONSTRAINT uq_add_ons_nombre');
    }
}
