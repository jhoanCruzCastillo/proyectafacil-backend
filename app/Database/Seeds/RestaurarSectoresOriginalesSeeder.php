<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Deshace, sobre datos YA EXISTENTES, el error del seeder anterior (MigrarSectoresATemasEspecialidadSeeder,
// ya eliminado): esa migración había reemplazado los 13 sectores MEF por los 10 temas de
// especialidad DENTRO de la misma tabla `sectores` — pedido explícito del usuario de deshacerlo:
// "Proyectos de Inversión con IA" (fichas/plantillas) debe seguir viendo los sectores MEF de
// siempre; "Temas de especialidad" del asesor usa un catálogo aparte (`temas_especialidad`, ver
// TemasEspecialidadSeeder). Este seeder es de UNA SOLA VEZ, sobre la BD que quedó en el estado
// intermedio equivocado — no hace falta correrlo en una BD sembrada desde cero (ahí SectoresSeeder
// ya siembra los 13 sectores MEF correctos directamente).
//
// Orden importante: primero reinserta los 13 sectores MEF y reasigna todo lo que dependía de ellos
// (fichas, intereses de clientes, especialidad de Pedro) ANTES de borrar las 10 filas "temas
// disfrazadas de sector" — sectores tiene FK ON DELETE CASCADE desde plantillas/cliente_intereses/
// asesor_especialidades, borrar sin reasignar antes las habría vuelto a borrar en cascada.
//
// Uso (una sola vez): php spark db:seed RestaurarSectoresOriginalesSeeder
class RestaurarSectoresOriginalesSeeder extends Seeder
{
    /** usuario_id => [códigos de sector originales], capturado en vivo antes de la migración
     * equivocada (ver conversación) — cliente_intereses no guarda de qué ficha/plantilla salió el
     * interés, así que no hay forma de recalcularlo solo, se restaura tal cual estaba. */
    private const INTERESES_ORIGINALES = [
        19 => ['SAL', 'EDU'],
        21 => ['SAL', 'EDU'],
        23 => ['EDU', 'VYS', 'DIS'],
        24 => ['EDU', 'VYS'],
    ];

    public function run(): void
    {
        $codigosNuevos = ['PIN', 'CON', 'PLN', 'PRE', 'PEC', 'CGU', 'EJO', 'ECO', 'LEG', 'FIN'];
        $temaComoSector = $this->db->table('sectores')->select('id, codigo')->whereIn('codigo', $codigosNuevos)->get()->getResultArray();
        if ($temaComoSector === []) {
            echo "No hay filas de 'temas disfrazadas de sector' en `sectores` — esta BD ya está en el estado correcto (o se sembró desde cero).\n";

            return;
        }
        $idsFalsos = array_map(static fn (array $f) => (int) $f['id'], $temaComoSector);

        // 1. Reinsertar los 13 sectores MEF originales (SectoresSeeder ya lee el sectores.json
        // restaurado — ids nuevos, no hace falta que coincidan con los de antes).
        $this->call(SectoresSeeder::class);

        $idPorCodigoSector = [];
        foreach ($this->db->table('sectores')->select('id, codigo')->get()->getResultArray() as $f) {
            $idPorCodigoSector[$f['codigo']] = (int) $f['id'];
        }

        // 2. Reasignar cada ficha a su sector MEF original (por su propio código de plantilla, que
        // nunca se tocó) — usando el mapeo mock-id → código de los data/*.json restaurados.
        $sectoresJson  = json_decode(file_get_contents(__DIR__ . '/data/sectores.json'), true);
        $plantillasJson = json_decode(file_get_contents(__DIR__ . '/data/plantillas.json'), true);
        $codigoPorMockId = [];
        foreach ($sectoresJson as $s) {
            $codigoPorMockId[$s['id']] = $s['codigo'];
        }

        $fichasCorregidas = 0;
        foreach ($plantillasJson as $p) {
            $codigoSectorOriginal = $codigoPorMockId[$p['sectorId']] ?? null;
            $sectorIdReal = $codigoSectorOriginal !== null ? ($idPorCodigoSector[$codigoSectorOriginal] ?? null) : null;
            if ($sectorIdReal === null) {
                continue;
            }
            $this->db->table('plantillas')->where('codigo', $p['codigo'])->update(['sector_id' => $sectorIdReal]);
            $fichasCorregidas++;
        }
        echo "Listo — {$fichasCorregidas} fichas reasignadas a su sector MEF original.\n";

        // 3. Restaurar cliente_intereses tal como estaba.
        $this->db->table('cliente_intereses')->whereIn('sector_id', $idsFalsos)->delete();
        foreach (self::INTERESES_ORIGINALES as $usuarioId => $codigos) {
            foreach ($codigos as $codigo) {
                if (! isset($idPorCodigoSector[$codigo])) {
                    continue;
                }
                $this->db->table('cliente_intereses')->ignore(true)->insert(['usuario_id' => $usuarioId, 'sector_id' => $idPorCodigoSector[$codigo]]);
            }
        }
        echo "Listo — cliente_intereses restaurado a sus sectores originales.\n";

        // 4. Limpiar la especialidad de Pedro Ríos apuntando a las filas falsas — se re-siembra
        // aparte con AsesoriasDemoAsesor1Seeder (sectores reales SAL/VYS/DIS).
        $this->db->table('asesor_especialidades')->whereIn('sector_id', $idsFalsos)->delete();

        // 5. Ahora sí, borrar las 10 filas "tema disfrazado de sector" — ya no las referencia nada.
        $this->db->table('sectores')->whereIn('id', $idsFalsos)->delete();
        echo "Listo — {$this->db->affectedRows()} filas de temas-disfrazados-de-sector eliminadas de `sectores`.\n";
    }
}
