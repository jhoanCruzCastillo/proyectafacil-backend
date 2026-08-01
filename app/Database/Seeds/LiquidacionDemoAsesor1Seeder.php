<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Historial de consultas completadas para el asesor demo (Pedro Ríos / asesor1), para poder probar
// la pantalla "Mi Liquidación" con volumen real: gráfico por mes, detalle por periodo y pendientes
// de pago.
//
// Determinista a propósito (sin random): 48 consultas repartidas en 11 meses, de las cuales las 9
// más recientes quedan sin pago autorizado. Con la tarifa de S/ 550 eso da S/ 26,400 histórico y
// S/ 4,950 pendiente, que es lo que muestran los diseños aprobados.
//
// Idempotente: se salta por completo si ya se sembró antes.
//
// Uso: php spark db:seed LiquidacionDemoAsesor1Seeder
class LiquidacionDemoAsesor1Seeder extends Seeder
{
    private const MARCADOR = '(demo liquidacion asesor1)';

    /** Consultas por mes, contando hacia atrás desde el último mes cerrado. Suman 48. */
    private const POR_MES = [11, 3, 5, 3, 5, 3, 4, 4, 3, 4, 3];

    /** Las N consultas más recientes quedan pendientes de pago. */
    private const SIN_PAGAR = 9;

    /** Combinaciones sector/subtema/modalidad que se van rotando. */
    private const COMBOS = [
        ['GEN', 'Liquidación por contrata', 'video'],
        ['SAL', 'Equipamiento biomédico', 'chat'],
        ['VYS', 'Agua potable en zonas rurales', 'video'],
        ['VYS', 'Plantas de tratamiento de aguas residuales', 'chat'],
        ['AGR', 'Riego tecnificado parcelario', 'video'],
        ['VYS', 'Alcantarillado y redes urbanas', 'chat'],
        ['SAL', 'Hospitales de segundo y tercer nivel', 'video'],
        ['EDU', 'Locales educativos de primaria y secundaria', 'chat'],
        ['DIS', 'Centros de desarrollo infantil temprano', 'video'],
        ['GEN', 'Liquidación por administración directa', 'video'],
        ['EDU', 'Institutos y escuelas de educación superior', 'chat'],
        ['GEN', 'Saldos de obra', 'video'],
    ];

    /**
     * Las fechas se guardan en UTC (hora del servidor) y el navegador las muestra en hora local.
     * Para que en Perú (UTC-5) las consultas caigan en horario de oficina se siembran desplazadas:
     * 13:00–21:00 UTC ≡ 08:00–16:00 en Lima. Sin esto, sembrar "08:00" se vería como "3:00 am".
     */
    private const UTC_A_LIMA = 5;

    public function run(): void
    {
        // A diferencia de otros seeders demo, este REEMPLAZA en vez de saltarse: sus fechas son
        // relativas al mes actual, así que al correrlo semanas después conviene regenerarlas.
        $previas = $this->db->table('solicitudes_asesoria')->like('mensaje_inicial', self::MARCADOR)->countAllResults();
        if ($previas > 0) {
            $this->db->table('solicitudes_asesoria')->like('mensaje_inicial', self::MARCADOR)->delete();
            echo "Se borraron {$previas} consultas demo previas para regenerarlas.\n";
        }

        $pedro = $this->db->table('usuarios')->where('usuario', 'asesor1')->get()->getRowArray();
        if ($pedro === null) {
            echo "No existe el usuario 'asesor1' — corre UsuariosSeeder primero.\n";

            return;
        }
        $pedroId = (int) $pedro['id'];

        $alumnos = $this->alumnosDisponibles();
        if (count($alumnos) < 4) {
            echo "Faltan alumnos demo — corre AsesoriasDemoSeeder primero.\n";

            return;
        }

        $sectorId  = $this->sectorIdsPorCodigo();
        $subtemaId = $this->subtemaIdsPorSectorYNombre();

        // Se siembra hasta el último mes CERRADO: fechar consultas "completadas" en días que aún no
        // han ocurrido sería inconsistente (una asesoría no puede haberse cerrado en el futuro).
        $mesBase = date('Y-m-01', strtotime('first day of last month'));

        $entradas = [];
        $combo    = 0;
        foreach (self::POR_MES as $i => $cantidad) {
            $mes      = date('Y-m-01', strtotime("{$mesBase} -{$i} month"));
            $diasMes  = (int) date('t', strtotime($mes));
            // Repartidas a lo largo del mes para que el desglose por semana no salga todo junto.
            $paso     = max((int) floor($diasMes / max($cantidad, 1)), 1);

            for ($n = 0; $n < $cantidad; $n++) {
                $dia   = min(2 + $n * $paso, $diasMes);
                $hora  = 8 + ($n % 9) + self::UTC_A_LIMA;
                $c     = self::COMBOS[$combo % count(self::COMBOS)];
                $combo++;

                $entradas[] = [
                    'fecha'   => sprintf('%s-%02d %02d:%02d:00', substr($mes, 0, 7), $dia, $hora, ($n % 2) * 30),
                    'alumno'  => $alumnos[$combo % count($alumnos)],
                    'sector'  => $c[0],
                    'subtema' => $c[1],
                    'tipo'    => $c[2],
                ];
            }
        }

        // Las más recientes son las que aún no se han pagado.
        usort($entradas, static fn (array $a, array $b) => strcmp($a['fecha'], $b['fecha']));
        $corteSinPagar = count($entradas) - self::SIN_PAGAR;

        foreach ($entradas as $idx => $e) {
            $pagado = $idx < $corteSinPagar;
            $creado = date('Y-m-d H:i:s', strtotime($e['fecha'] . ' -2 days'));

            $this->db->table('solicitudes_asesoria')->insert([
                'cliente_id'          => $e['alumno'],
                'docente_id'          => $pedroId,
                'ejemplo_id'          => null,
                'sector_id'           => $sectorId[$e['sector']] ?? null,
                'subtema_id'          => $subtemaId[$e['sector']][$e['subtema']] ?? null,
                'tipo_documento'      => 'ficha_tecnica',
                'tipo'                => $e['tipo'],
                'estado'              => 'completado',
                'mensaje_inicial'     => $e['subtema'] . ' ' . self::MARCADOR,
                'horario_fecha'       => $e['tipo'] === 'video' ? substr($e['fecha'], 0, 10) : null,
                'horario_hora_inicio' => $e['tipo'] === 'video' ? substr($e['fecha'], 11, 8) : null,
                'horario_hora_fin'    => $e['tipo'] === 'video' ? date('H:i:s', strtotime($e['fecha'] . ' +30 minutes')) : null,
                'sla_vence_en'        => null,
                'link_reunion'        => $e['tipo'] === 'video' ? $this->linkSimulado() : null,
                'calificacion'        => 4 + ($idx % 2),
                'completado_en'       => $e['fecha'],
                'pago_autorizado_en'  => $pagado ? date('Y-m-d H:i:s', strtotime($e['fecha'] . ' +5 days')) : null,
                'created_at'          => $creado,
                'updated_at'          => $e['fecha'],
            ]);
        }

        $total = count($entradas);
        echo "Listo — {$total} consultas completadas para asesor1 (Pedro Ríos), " . self::SIN_PAGAR . " sin pago autorizado.\n";
    }

    private function sectorIdsPorCodigo(): array
    {
        $porCodigo = [];
        foreach ($this->db->table('sectores')->select('id, codigo')->get()->getResultArray() as $f) {
            $porCodigo[$f['codigo']] = (int) $f['id'];
        }

        return $porCodigo;
    }

    /** [codigoSector][nombreSubtema] => id */
    private function subtemaIdsPorSectorYNombre(): array
    {
        $filas = $this->db->table('subtemas_especialidad t')
            ->select('t.id, t.nombre, s.codigo')
            ->join('sectores s', 's.id = t.sector_id')
            ->get()->getResultArray();

        $mapa = [];
        foreach ($filas as $f) {
            $mapa[$f['codigo']][$f['nombre']] = (int) $f['id'];
        }

        return $mapa;
    }

    private function alumnosDisponibles(): array
    {
        $usuarios = ['cliente', 'cliente2', 'alumno.rosa', 'alumno.mateo', 'cliente.lucia', 'cliente.diego'];
        $ids = [];
        foreach ($usuarios as $u) {
            $fila = $this->db->table('usuarios')->where('usuario', $u)->get()->getRowArray();
            if ($fila !== null) {
                $ids[] = (int) $fila['id'];
            }
        }

        return $ids;
    }

    private function linkSimulado(): string
    {
        return 'https://proyectafacil.app/reunion/demo-' . strtolower(bin2hex(random_bytes(4)));
    }
}
