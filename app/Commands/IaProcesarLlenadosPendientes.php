<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Orquestador de `llenado_ia_trabajos` — pensado para dispararse desde un Cron Job de Railway cada 5
 * minutos (`php spark ia:procesar-llenados-pendientes`, el mínimo que permite Railway). Reclama hasta
 * MAX_CONCURRENTES trabajos `pendiente` de forma atómica y lanza cada uno como un proceso hijo
 * (`ia:ejecutar-llenado`) vía `proc_open` — no usa `pcntl` (no está instalado en el Dockerfile).
 *
 * Railway NO deja que una ejecución de Cron se solape consigo misma (si la anterior sigue corriendo,
 * la siguiente simplemente se salta) — por eso este comando espera a que TODOS sus hijos terminen
 * antes de salir, en vez de lanzarlos y despedirse: así una tanda larga no se pisa con la siguiente.
 *
 * Uso: php spark ia:procesar-llenados-pendientes
 */
class IaProcesarLlenadosPendientes extends BaseCommand
{
    protected $group       = 'IA';
    protected $name        = 'ia:procesar-llenados-pendientes';
    protected $description = 'Reclama y procesa en paralelo (hasta 5 a la vez) los trabajos pendientes de llenado con IA.';

    /** Cuántos trabajos como máximo se procesan a la vez por cada disparo del Cron — ver la Fase 1 del
     * plan sobre el trade-off costo/latencia elegido con el usuario. Fácil de ajustar según carga real. */
    private const MAX_CONCURRENTES = 5;

    /** Un hijo que muere sin avisar (crash, OOM) no debe dejar su trabajo colgado en 'procesando' para
     * siempre — se recupera y se reintenta pasado este umbral. */
    private const MINUTOS_ANTES_DE_CONSIDERAR_HUERFANO = 90;

    public function run(array $params)
    {
        $db = db_connect();

        $huerfanos = $db->table('llenado_ia_trabajos')
            ->where('estado', 'procesando')
            ->where('iniciado_en <', date('Y-m-d H:i:s', time() - self::MINUTOS_ANTES_DE_CONSIDERAR_HUERFANO * 60))
            ->get()->getResultArray();
        foreach ($huerfanos as $h) {
            $db->table('llenado_ia_trabajos')->where('id', $h['id'])->update([
                'estado'       => 'error',
                'error'        => 'El proceso que lo ejecutaba se interrumpió sin terminar (más de ' . self::MINUTOS_ANTES_DE_CONSIDERAR_HUERFANO . ' min en "procesando").',
                'terminado_en' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
            CLI::write("Trabajo huérfano {$h['id']} marcado como error.", 'red');
        }

        $ids = [];
        for ($i = 0; $i < self::MAX_CONCURRENTES; $i++) {
            // Reclamo atómico: SKIP LOCKED evita que dos disparos del comando que se solapen (o un
            // orquestador manual corrido junto al de Cron) tomen el mismo trabajo dos veces.
            $fila = $db->query(
                "UPDATE llenado_ia_trabajos SET estado = 'procesando', updated_at = NOW()
                 WHERE id = (
                     SELECT id FROM llenado_ia_trabajos
                     WHERE estado = 'pendiente'
                     ORDER BY created_at ASC
                     FOR UPDATE SKIP LOCKED
                     LIMIT 1
                 )
                 RETURNING id",
            )->getRowArray();
            if ($fila === null) {
                break; // no hay más pendientes
            }
            $ids[] = (int) $fila['id'];
        }

        if ($ids === []) {
            CLI::write('Sin trabajos pendientes.', 'yellow');

            return EXIT_SUCCESS;
        }

        CLI::write('Procesando en paralelo: ' . implode(', ', $ids), 'green');

        // Redirige stdout/stderr del hijo A ARCHIVO, no a pipes ['pipe','w'] — con pipes, el SO le da
        // al hijo un buffer chico (~64KB) y si nadie lo lee (este bucle solo consulta proc_get_status,
        // nunca fread() los pipes) el hijo se BLOQUEA en su propio write() en cuanto lo llena. Con un
        // lote grande (~90 solicitudes, cada respuesta de OpenAI logueada en DEBUG) el buffer se llena
        // fácil — reproducido en vivo (2026-09-24): el hijo quedó colgado con 0% CPU a mitad de un
        // trabajo real, nunca marcado como terminado. Un archivo no tiene ese límite.
        $dirLogs = ROOTPATH . 'writable/logs/ia-hijos/';
        if (! is_dir($dirLogs)) {
            mkdir($dirLogs, 0775, true);
        }

        $rutaSpark = ROOTPATH . 'spark';
        $procesos  = [];
        foreach ($ids as $id) {
            // -y automático no existe acá; el comando hijo ya cambia el trabajo a 'procesando' de
            // nuevo (no-op, ya lo dejamos así arriba) y sigue de largo — se acepta la doble escritura
            // por simplicidad, es idempotente.
            $cmd        = 'php ' . escapeshellarg($rutaSpark) . ' ia:ejecutar-llenado ' . escapeshellarg((string) $id);
            $rutaSalida = $dirLogs . 'trabajo-' . $id . '-' . date('Ymd-His') . '.log';
            $proc       = proc_open($cmd, [1 => ['file', $rutaSalida, 'w'], 2 => ['file', $rutaSalida, 'a']], $pipes, ROOTPATH);
            if ($proc === false) {
                CLI::error("No se pudo lanzar el proceso hijo para el trabajo {$id}.");

                continue;
            }
            $procesos[$id] = ['proc' => $proc];
        }

        // Espera activa a que todos los hijos terminen — evita que Railway considere este disparo de
        // Cron "terminado" mientras el trabajo real sigue en curso (ver cabecera de la clase).
        while ($procesos !== []) {
            foreach ($procesos as $id => $p) {
                $estado = proc_get_status($p['proc']);
                if (! $estado['running']) {
                    proc_close($p['proc']);
                    CLI::write("Trabajo {$id}: proceso hijo terminó (código {$estado['exitcode']}).", $estado['exitcode'] === 0 ? 'green' : 'red');
                    unset($procesos[$id]);
                }
            }
            if ($procesos !== []) {
                sleep(3);
            }
        }

        CLI::write('Listo — todos los hijos de esta tanda terminaron.', 'green');

        return EXIT_SUCCESS;
    }
}
