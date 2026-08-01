<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Pantalla "Mi Liquidación" del asesor — la vista en primera persona de lo que el Administrativo
// ve agregado en TicketsAsesoriaController::liquidaciones(). Ambas leen los mismos hechos para que
// los números coincidan: una consulta es facturable cuando queda 'completado', vale
// Config\Asesoria::honorarioPorTicket, y está pagada cuando tiene `pago_autorizado_en`.
//
// La fecha de corte es `completado_en` (con COALESCE a updated_at para filas anteriores a esa
// columna): usar updated_at a secas haría que los totales por periodo cambien solos cuando la fila
// se toca por cualquier otro motivo.
class MiLiquidacionController extends BaseController
{
    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic'];

    // Tab 1 — histórico con granularidad configurable (día/semana/mes/año) + detalle del periodo.
    public function historico(): ResponseInterface
    {
        $usuarioId    = (int) ($this->request->getGet('usuarioId') ?? 0);
        $granularidad = (string) ($this->request->getGet('granularidad') ?? 'mes');
        $ancla        = $this->anclaValida((string) ($this->request->getGet('periodo') ?: $this->anclaPorDefecto($usuarioId)));
        $honorario    = $this->honorario();

        $buckets = $this->buckets($granularidad, $ancla);

        $completadas = $this->filasCompletadas($usuarioId);

        $serie = array_map(function (array $b) use ($completadas, $honorario) {
            $enRango = array_filter($completadas, static fn (array $f) => $f['corte'] >= $b['inicio'] && $f['corte'] < $b['fin']);

            return [
                'clave'     => $b['clave'],
                'etiqueta'  => $b['etiqueta'],
                'consultas' => count($enRango),
                'monto'     => count($enRango) * $honorario,
            ];
        }, $buckets);

        $sel      = $this->bucketSeleccionado($buckets, $granularidad, $ancla);
        $delRango = array_values(array_filter($completadas, static fn (array $f) => $f['corte'] >= $sel['inicio'] && $f['corte'] < $sel['fin']));

        $totalConsultas = count($completadas);
        $pagadas        = count(array_filter($completadas, static fn (array $f) => $f['pago_autorizado_en'] !== null));

        return $this->response->setJSON([
            'granularidad'  => $granularidad,
            'periodo'       => $ancla,
            'periodoClave'  => $sel['clave'],
            'periodoLabel'  => $sel['etiquetaLarga'],
            'honorario'     => $honorario,
            'kpis'          => [
                'consultasAtendidas' => $totalConsultas,
                'ingresoHistorico'   => $totalConsultas * $honorario,
                'promedioMensual'    => $this->promedioMensual($completadas, $honorario),
                'pagadoALaFecha'     => $pagadas * $honorario,
            ],
            'serie'         => $serie,
            'detalle'       => array_map([$this, 'toDtoDetalle'], $delRango),
            'totalPeriodo'  => count($delRango) * $honorario,
        ]);
    }

    // Tab 2 — lo completado que todavía no tiene pago autorizado.
    public function pendiente(): ResponseInterface
    {
        $usuarioId = (int) ($this->request->getGet('usuarioId') ?? 0);
        $honorario = $this->honorario();

        $pendientes = array_values(array_filter(
            $this->filasCompletadas($usuarioId),
            static fn (array $f) => $f['pago_autorizado_en'] === null,
        ));

        // Más antigua = la de mayor espera, que es la que primero hay que reclamar.
        usort($pendientes, static fn (array $a, array $b) => strcmp($a['corte'], $b['corte']));
        $diasMasAntigua = $pendientes === [] ? 0 : (int) floor((time() - strtotime($pendientes[0]['corte'])) / 86400);

        return $this->response->setJSON([
            'honorario'      => $honorario,
            'kpis'           => [
                'consultasPorCobrar' => count($pendientes),
                'montoPendiente'     => count($pendientes) * $honorario,
                'diasMasAntigua'     => $diasMasAntigua,
                'tarifaPorConsulta'  => $honorario,
            ],
            'detalle'        => array_map([$this, 'toDtoDetalle'], $pendientes),
            'totalPendiente' => count($pendientes) * $honorario,
        ]);
    }

    // Tab 3 — lo atendido dentro de un mes concreto, con desglose por semana.
    public function mes(): ResponseInterface
    {
        $usuarioId = (int) ($this->request->getGet('usuarioId') ?? 0);
        $ancla     = $this->anclaValida((string) ($this->request->getGet('periodo') ?: $this->anclaPorDefecto($usuarioId)));
        $honorario = $this->honorario();

        $inicio = date('Y-m-01 00:00:00', strtotime($ancla));
        $fin    = date('Y-m-01 00:00:00', strtotime($ancla . ' +1 month'));

        $delMes = array_values(array_filter(
            $this->filasCompletadas($usuarioId),
            static fn (array $f) => $f['corte'] >= $inicio && $f['corte'] < $fin,
        ));

        // Semanas del mes por número de semana dentro del mes (1..5/6), no ISO — es lo que se
        // entiende leyendo "Sem 1, Sem 2…" sobre un mes calendario.
        $porSemana = [];
        $semanasEnMes = (int) ceil(((int) date('t', strtotime($ancla)) + (int) date('N', strtotime($inicio)) - 1) / 7);
        for ($i = 1; $i <= $semanasEnMes; $i++) {
            $porSemana[$i] = 0;
        }
        foreach ($delMes as $f) {
            $diaDelMes = (int) date('j', strtotime($f['corte']));
            $offset    = (int) date('N', strtotime($inicio)) - 1;
            $semana    = (int) ceil(($diaDelMes + $offset) / 7);
            $porSemana[$semana] = ($porSemana[$semana] ?? 0) + 1;
        }

        $videollamadas = count(array_filter($delMes, static fn (array $f) => $f['tipo'] === 'video'));

        return $this->response->setJSON([
            'periodo'      => $ancla,
            'periodoLabel' => $this->mesLargo($ancla),
            'honorario'    => $honorario,
            'kpis'         => [
                'consultasDelMes' => count($delMes),
                'ingresoDelMes'   => count($delMes) * $honorario,
                'videollamadas'   => $videollamadas,
                'chats'           => count($delMes) - $videollamadas,
            ],
            'porSemana'    => array_map(
                static fn (int $n, int $c) => ['etiqueta' => "Sem {$n}", 'consultas' => $c],
                array_keys($porSemana),
                array_values($porSemana),
            ),
            'detalle'      => array_map([$this, 'toDtoDetalle'], $delMes),
            'totalMes'     => count($delMes) * $honorario,
        ]);
    }

    // ---------- helpers ----------

    private function honorario(): int
    {
        return config('Asesoria')->honorarioPorTicket;
    }

    /** Todas las consultas completadas del asesor, con la fecha de corte ya resuelta. */
    private function filasCompletadas(int $usuarioId): array
    {
        $filas = db_connect()->table('solicitudes_asesoria sa')
            ->select('sa.id, sa.tipo, sa.completado_en, sa.updated_at, sa.pago_autorizado_en, c.nombre as cliente_nombre, c.foto_url as cliente_foto_url, s.nombre as sector_nombre, t.nombre as subtema_nombre')
            ->join('usuarios c', 'c.id = sa.cliente_id')
            ->join('sectores s', 's.id = sa.sector_id', 'left')
            ->join('subtemas_especialidad t', 't.id = sa.subtema_id', 'left')
            ->where('sa.docente_id', $usuarioId)
            ->where('sa.estado', 'completado')
            ->get()->getResultArray();

        foreach ($filas as &$f) {
            $f['corte'] = (string) ($f['completado_en'] ?? $f['updated_at']);
        }
        unset($f);

        usort($filas, static fn (array $a, array $b) => strcmp($b['corte'], $a['corte']));

        return $filas;
    }

    private function toDtoDetalle(array $f): array
    {
        return [
            'id'             => (string) $f['id'],
            'clienteNombre'  => $f['cliente_nombre'],
            'clienteFotoUrl' => $f['cliente_foto_url'] ?? null,
            'sectorNombre'   => $f['sector_nombre'] ?? null,
            'subtemaNombre'  => $f['subtema_nombre'] ?? null,
            'tipo'           => $f['tipo'],
            'atendidoEn'     => str_replace(' ', 'T', $f['corte']) . 'Z',
            'pagado'         => $f['pago_autorizado_en'] !== null,
            'monto'          => $this->honorario(),
        ];
    }

    private function promedioMensual(array $completadas, int $honorario): int
    {
        if ($completadas === []) {
            return 0;
        }
        $meses = [];
        foreach ($completadas as $f) {
            $meses[substr($f['corte'], 0, 7)] = true;
        }

        return (int) round(count($completadas) * $honorario / max(count($meses), 1));
    }

    private function anclaValida(string $valor): string
    {
        $ts = strtotime($valor);

        return $ts === false ? date('Y-m-d') : date('Y-m-d', $ts);
    }

    /**
     * Ancla cuando el front no manda periodo: el mes más reciente CON consultas, no el mes actual.
     * Si un asesor no atendió nada este mes, abrirle la pantalla en un mes vacío parece un error;
     * mostrarle su último mes con actividad es lo que espera ver. Si nunca atendió nada, hoy.
     */
    private function anclaPorDefecto(int $usuarioId): string
    {
        $fila = db_connect()->table('solicitudes_asesoria')
            ->select('COALESCE(completado_en, updated_at) as corte')
            ->where('docente_id', $usuarioId)
            ->where('estado', 'completado')
            ->orderBy('corte', 'DESC')
            ->limit(1)
            ->get()->getRowArray();

        return $fila === null ? date('Y-m-d') : date('Y-m-d', strtotime((string) $fila['corte']));
    }

    /**
     * Buckets del gráfico según granularidad, siempre alrededor de la fecha ancla.
     * Cada bucket trae su rango [inicio, fin) ya resuelto para no repetir aritmética de fechas.
     */
    private function buckets(string $granularidad, string $ancla): array
    {
        $lista = [];

        if ($granularidad === 'dia') {
            $dias = (int) date('t', strtotime($ancla));
            for ($d = 1; $d <= $dias; $d++) {
                $inicio  = date('Y-m-', strtotime($ancla)) . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
                $lista[] = $this->bucket($inicio, '+1 day', $inicio, (string) $d, date('j \d\e F, Y', strtotime($inicio)));
            }
        } elseif ($granularidad === 'semana') {
            $lunes = date('Y-m-d', strtotime('monday this week', strtotime($ancla)));
            for ($i = 11; $i >= 0; $i--) {
                $inicio  = date('Y-m-d', strtotime("{$lunes} -{$i} week"));
                $lista[] = $this->bucket($inicio, '+1 week', $inicio, date('d/m', strtotime($inicio)), 'Semana del ' . date('j \d\e F, Y', strtotime($inicio)));
            }
        } elseif ($granularidad === 'anio') {
            $anio = (int) date('Y', strtotime($ancla));
            for ($i = 4; $i >= 0; $i--) {
                $inicio  = ($anio - $i) . '-01-01';
                $lista[] = $this->bucket($inicio, '+1 year', (string) ($anio - $i), (string) ($anio - $i), (string) ($anio - $i));
            }
        } else {
            $anio = (int) date('Y', strtotime($ancla));
            for ($m = 1; $m <= 12; $m++) {
                $inicio  = sprintf('%d-%02d-01', $anio, $m);
                $lista[] = $this->bucket($inicio, '+1 month', substr($inicio, 0, 7), self::MESES[$m - 1], $this->mesLargo($inicio));
            }
        }

        return $lista;
    }

    private function bucket(string $inicio, string $paso, string $clave, string $etiqueta, string $etiquetaLarga): array
    {
        return [
            'clave'         => $clave,
            'etiqueta'      => $etiqueta,
            'etiquetaLarga' => $etiquetaLarga,
            'inicio'        => date('Y-m-d 00:00:00', strtotime($inicio)),
            'fin'           => date('Y-m-d 00:00:00', strtotime($inicio . ' ' . $paso)),
        ];
    }

    private function bucketSeleccionado(array $buckets, string $granularidad, string $ancla): array
    {
        $clave = match ($granularidad) {
            'dia'    => (string) (int) date('j', strtotime($ancla)),
            'semana' => date('Y-m-d', strtotime('monday this week', strtotime($ancla))),
            'anio'   => date('Y', strtotime($ancla)),
            default  => date('Y-m', strtotime($ancla)),
        };

        foreach ($buckets as $b) {
            if ($b['clave'] === $clave) {
                return $b;
            }
        }

        return end($buckets) ?: $this->bucket($ancla, '+1 month', $ancla, $ancla, $ancla);
    }

    private function mesLargo(string $fecha): string
    {
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        return $meses[(int) date('n', strtotime($fecha)) - 1] . ' ' . date('Y', strtotime($fecha));
    }
}
