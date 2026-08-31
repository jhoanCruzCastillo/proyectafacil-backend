<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Asignaciones del tab "Estructura" del panel Contextos IA para FTE-CUIDADO-DIURNO — qué insumo
// puntual usa el sistema para cada uno de los 4 pasos asignables (1=rol, 2=prompt del sistema,
// 4=reglas de llenado, 5=contexto general — ver CreateContextosIAPasos y PASOS_ASIGNABLES en
// LlenadoIAController). Se hicieron a mano desde el admin el 2026-08-30 y vivían SOLO en la BD local
// — sin este seeder, un ambiente sembrado desde cero (Railway) se queda sin nada asignado acá y cae
// al fallback por nombre reservado (funciona, pero no es lo que el admin dejó configurado a propósito
// en local: paso 5 con 4 insumos en un orden específico, no "todos los generales sin orden").
//
// Busca cada insumo por NOMBRE (no por id numérico) porque el orden/cantidad de filas en
// contextos_ia_globales / contextos_ia_general puede variar entre ambientes según qué otros
// seeders corrieron antes.
//
// Idempotente: no duplica una fila (plantilla_id, paso, tipo_insumo, insumo_id) que ya exista —
// la tabla no tiene UNIQUE real (ver CreateContextosIAPasos), así que se verifica antes de insertar.
//
// Uso: php spark db:seed ContextosIAPasosCuidadoDiurnoSeeder
class ContextosIAPasosCuidadoDiurnoSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CUIDADO-DIURNO';

    // paso => lista de [tipo_insumo, nombre del insumo], en el orden que debe quedar (columna `orden`).
    private const ASIGNACIONES = [
        1 => [
            ['global', 'Rol del asistente de IA'],
        ],
        2 => [
            ['general', 'Prompt del sistema'],
        ],
        4 => [
            ['global', 'Reglas de llenado automático con IA'],
        ],
        5 => [
            ['general', 'Contexto general'],
            ['general', 'CIAI — Cuidado Diurno'],
            ['general', 'FTE CIAI — Reglas de uso'],
            ['general', 'Guía de llenado campo por campo'],
        ],
    ];

    public function run(): void
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null) {
            echo 'No existe la plantilla ' . self::CODIGO_PLANTILLA . " — corre PlantillasSeeder primero.\n";

            return;
        }
        $plantillaId = (int) $plantilla['id'];
        $ahora       = date('Y-m-d H:i:s');
        $creadas     = 0;
        $faltantes   = 0;

        foreach (self::ASIGNACIONES as $paso => $insumos) {
            foreach ($insumos as $orden => [$tipo, $nombre]) {
                $insumoId = $tipo === 'global'
                    ? $this->idGlobalPorNombre($nombre)
                    : $this->idGeneralPorNombre($plantillaId, $nombre);

                if ($insumoId === null) {
                    echo "  Insumo no encontrado ({$tipo} \"{$nombre}\") para el paso {$paso} — se omite.\n";
                    $faltantes++;

                    continue;
                }

                $existente = $this->db->table('contextos_ia_pasos')
                    ->where('plantilla_id', $plantillaId)
                    ->where('paso', $paso)
                    ->where('tipo_insumo', $tipo)
                    ->where('insumo_id', $insumoId)
                    ->get()->getRowArray();

                if ($existente !== null) {
                    continue;
                }

                $this->db->table('contextos_ia_pasos')->insert([
                    'plantilla_id' => $plantillaId,
                    'paso'         => $paso,
                    'tipo_insumo'  => $tipo,
                    'insumo_id'    => $insumoId,
                    'orden'        => $orden + 1,
                    'created_at'   => $ahora,
                ]);
                $creadas++;
            }
        }

        echo "Asignaciones de pasos creadas: {$creadas}" . ($faltantes > 0 ? " ({$faltantes} insumos no encontrados)" : '') . ".\n";
    }

    private function idGlobalPorNombre(string $nombre): ?int
    {
        $fila = $this->db->table('contextos_ia_globales')->where('nombre', $nombre)->get()->getRowArray();

        return $fila !== null ? (int) $fila['id'] : null;
    }

    private function idGeneralPorNombre(int $plantillaId, string $nombre): ?int
    {
        $fila = $this->db->table('contextos_ia_general')
            ->where('plantilla_id', $plantillaId)
            ->where('nombre', $nombre)
            ->get()->getRowArray();

        return $fila !== null ? (int) $fila['id'] : null;
    }
}
