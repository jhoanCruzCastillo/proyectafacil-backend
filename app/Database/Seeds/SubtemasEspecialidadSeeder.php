<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Catálogo OFICIAL de subtemas por tema de especialidad, tal como lo envió el cliente (tabla
// "Tema de Especialización / Subtemas", 2026-09-13). `temas_especialidad` es un catálogo SEPARADO
// de `sectores` (los 13 sectores MEF de las fichas/plantillas) — ver TemasEspecialidadSeeder. Los
// temas sin subtemas en la tabla del cliente (Contrataciones con el Estado, Planeamiento,
// Presupuesto, Peritaje Económico/Financiero/Contable, Control Gubernamental, Economía, Asesoría
// Legal, Asesoría Financiera) simplemente no tienen entrada aquí — el frontend los muestra como un
// seleccionable simple, sin acordeón.
//
// Uso: php spark db:seed SubtemasEspecialidadSeeder (requiere que TemasEspecialidadSeeder ya corrió)
class SubtemasEspecialidadSeeder extends Seeder
{
    /** Subtemas por código de tema de especialidad (ver data/temas_especialidad.json). */
    private const SUBTEMAS = [
        'PIN' => [
            'Formatos',
            'Fichas Técnicas',
            'Programación Multianual de Inversiones',
            'IOARR',
            'Perfiles',
        ],
        'EJO' => [
            'Expediente Técnico',
            'Especificaciones Técnicas',
            'Residencia de Obras',
            'Supervisión de Obras',
            'Administración de Obras',
            'Contabilidad de Obras',
            'Programación de Obras',
            'Valorización y Liquidación de Obras',
            'Liquidación Financiera de Obras',
            'Cierre de Inversiones',
            'BIM',
            'PMBOK',
        ],
    ];

    public function run(): void
    {
        $temaIdPorCodigo = [];
        foreach ($this->db->table('temas_especialidad')->select('id, codigo')->get()->getResultArray() as $f) {
            $temaIdPorCodigo[$f['codigo']] = (int) $f['id'];
        }

        $ahora = date('Y-m-d H:i:s');
        $insertados = 0;

        foreach (self::SUBTEMAS as $codigo => $nombres) {
            $temaId = $temaIdPorCodigo[$codigo] ?? null;
            if ($temaId === null) {
                echo "Tema de especialidad '{$codigo}' no existe — se omite (corre TemasEspecialidadSeeder primero).\n";

                continue;
            }

            foreach ($nombres as $nombre) {
                $yaExiste = $this->db->table('subtemas_especialidad')
                    ->where('tema_id', $temaId)
                    ->where('nombre', $nombre)
                    ->countAllResults() > 0;
                if ($yaExiste) {
                    continue;
                }

                $this->db->table('subtemas_especialidad')->insert([
                    'tema_id'    => $temaId,
                    'nombre'     => $nombre,
                    'activo'     => 1,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
                $insertados++;
            }
        }

        echo "Listo — {$insertados} subtemas nuevos insertados.\n";

        $this->marcarSubtemasDemoAsesor1();
    }

    // Marca algunos subtemas para el asesor demo (Pedro Ríos), dentro de los temas que ya tiene
    // como especialidad — así la pantalla no arranca vacía al probarla.
    private function marcarSubtemasDemoAsesor1(): void
    {
        $pedro = $this->db->table('usuarios')->where('usuario', 'asesor1')->get()->getRowArray();
        if ($pedro === null) {
            return;
        }
        $pedroId = (int) $pedro['id'];

        $temasDePedro = array_column(
            $this->db->table('asesor_temas_especialidad')->select('tema_id')->where('usuario_id', $pedroId)->get()->getResultArray(),
            'tema_id',
        );
        if ($temasDePedro === []) {
            return;
        }

        $marcados = 0;
        foreach ($temasDePedro as $temaId) {
            // Los 2 primeros subtemas de cada tema suyo — suficiente para ver el estado mixto
            // (tema con algunos subtemas marcados y otros no).
            $subtemas = $this->db->table('subtemas_especialidad')
                ->select('id')
                ->where('tema_id', $temaId)
                ->orderBy('id', 'ASC')
                ->limit(2)
                ->get()->getResultArray();

            foreach ($subtemas as $s) {
                $yaExiste = $this->db->table('asesor_subtemas')
                    ->where('usuario_id', $pedroId)
                    ->where('subtema_id', (int) $s['id'])
                    ->countAllResults() > 0;
                if ($yaExiste) {
                    continue;
                }

                $this->db->table('asesor_subtemas')->insert([
                    'usuario_id' => $pedroId,
                    'subtema_id' => (int) $s['id'],
                ]);
                $marcados++;
            }
        }

        echo "Listo — {$marcados} subtemas marcados para asesor1 (Pedro Ríos).\n";
    }
}
