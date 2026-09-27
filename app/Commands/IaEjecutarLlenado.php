<?php

namespace App\Commands;

use App\Controllers\LlenadoIAController;
use App\Exceptions\LlenadoIACanceladoException;
use App\Libraries\CorreoService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Procesa UN trabajo de `llenado_ia_trabajos` de punta a punta — el "trabajador" que
 * `ia:procesar-llenados-pendientes` (el orquestador) lanza como proceso hijo vía `proc_open`. También
 * se puede correr a mano para depurar un trabajo puntual sin pasar por el orquestador:
 *
 *   php spark ia:ejecutar-llenado <trabajoId>
 *
 * Corre como proceso CLI aparte — sin request HTTP de por medio, así que no hereda ningún
 * `set_time_limit` ni timeout de gateway: puede tardar los minutos que haga falta.
 */
class IaEjecutarLlenado extends BaseCommand
{
    protected $group       = 'IA';
    protected $name        = 'ia:ejecutar-llenado';
    protected $description = 'Ejecuta un trabajo de llenado con IA en segundo plano (llenado_ia_trabajos) de punta a punta.';
    protected $usage       = 'ia:ejecutar-llenado <trabajoId>';

    public function run(array $params)
    {
        $trabajoId = isset($params[0]) ? (int) $params[0] : null;
        if (! $trabajoId) {
            CLI::error('Uso: ' . $this->usage);

            return EXIT_ERROR;
        }

        $db      = db_connect();
        $trabajo = $db->table('llenado_ia_trabajos')->where('id', $trabajoId)->get()->getRowArray();
        if ($trabajo === null) {
            CLI::error("No existe el trabajo {$trabajoId}.");

            return EXIT_ERROR;
        }

        $db->table('llenado_ia_trabajos')->where('id', $trabajoId)->update([
            'estado'      => 'procesando',
            'iniciado_en' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        CLI::write("Trabajo {$trabajoId} (ejemplo {$trabajo['ejemplo_id']}): procesando…", 'yellow');

        $seccionIds = $trabajo['seccion_ids'] !== null ? json_decode((string) $trabajo['seccion_ids'], true) : null;
        /** Último resumen de progreso emitido — de acá salen los contadores finales (ver más abajo). */
        $ultimoProgreso = null;

        try {
            // Sin initController(): este método no toca $this->request/$this->response/session — solo
            // llama a los helpers privados del controlador (db_connect/config/log_message), que no
            // necesitan inicialización de request HTTP. Ver LlenadoIAController::ejecutarLlenadoCompletoAsync().
            $controller = new LlenadoIAController();
            $resultado  = $controller->ejecutarLlenadoCompletoAsync(
                (int) $trabajo['ejemplo_id'],
                is_array($seccionIds) ? $seccionIds : null,
                // Se invoca cada vez que una unidad se despacha o aterriza (ver emitirProgreso) —
                // en una ficha grande son unos cientos de UPDATE a lo largo de varios minutos, que
                // es justo lo que hace que el modal del cliente avance de verdad en vez de saltar
                // de 0 a 100 al final. `campos_completados`/`campos_totales` se mantienen al día acá
                // (antes solo se escribían al terminar) para que la barra funcione aunque el cliente
                // no sepa leer `progreso_json`.
                function (string $texto, ?array $progreso = null) use ($db, $trabajoId, &$ultimoProgreso): void {
                    $ultimoProgreso = $progreso ?? $ultimoProgreso;
                    $fila = [
                        'progreso_texto' => mb_substr($texto, 0, 255),
                        'updated_at'     => date('Y-m-d H:i:s'),
                    ];
                    if ($progreso !== null) {
                        $fila['progreso_json']      = json_encode($progreso, JSON_UNESCAPED_UNICODE);
                        $fila['campos_completados'] = (int) $progreso['camposListos'];
                        $fila['campos_totales']     = (int) $progreso['camposTotales'];
                    }
                    $db->table('llenado_ia_trabajos')->where('id', $trabajoId)->update($fila);
                },
                // Consultado entre tandas (ver ejecutarLoteEnParalelo) — releer el estado real en vez de
                // confiar en una copia en memoria, porque LlenadoIAController::cancelarLlenadoAsync() lo
                // actualiza desde un request HTTP totalmente aparte mientras este proceso hijo corre.
                function () use ($db, $trabajoId): bool {
                    $fila = $db->table('llenado_ia_trabajos')->select('estado')->where('id', $trabajoId)->get()->getRowArray();

                    return ($fila['estado'] ?? null) === 'cancelado';
                },
            );
        } catch (LlenadoIACanceladoException $e) {
            $db->table('llenado_ia_trabajos')->where('id', $trabajoId)->update([
                'estado'         => 'cancelado',
                'progreso_texto' => 'Cancelado por el usuario.',
                'terminado_en'   => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
            CLI::write("Trabajo {$trabajoId}: cancelado por el usuario.", 'yellow');

            return EXIT_SUCCESS;
        } catch (Throwable $e) {
            log_message('error', '[llenado-ia-async] Trabajo {id} falló: {msg}', ['id' => $trabajoId, 'msg' => $e->getMessage()]);
            $db->table('llenado_ia_trabajos')->where('id', $trabajoId)->update([
                'estado'       => 'error',
                'error'        => $e->getMessage(),
                'terminado_en' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
            CLI::error("Trabajo {$trabajoId} falló: " . $e->getMessage());

            return EXIT_ERROR;
        }

        // Los contadores finales salen del MISMO progreso que el cliente estuvo viendo avanzar, no
        // de un recuento aparte sobre $resultado. Antes se recalculaban acá con otra definición y no
        // cuadraban justo al cerrar: en una corrida de solo tablas la barra mostraba "5 de 5" y
        // terminaba en "6 de —" (campos_totales quedaba null porque $resultado['secciones'] venía
        // vacío al no haber ninguna unidad de texto). El recuento viejo queda de reserva para un
        // trabajo que no llegó a emitir progreso.
        $camposCompletados = $ultimoProgreso['camposListos']
            ?? count((array) $resultado['valores']) + count(array_filter(
                $resultado['tablas'] ?? [],
                static fn (array $t): bool => ! isset($t['error']),
            ));
        $camposTotales = $ultimoProgreso['camposTotales'] ?? (count($resultado['secciones'] ?? []) > 0
            ? array_sum(array_column($resultado['secciones'], 'campos')) + count($resultado['tablas'] ?? [])
            : null);

        $db->table('llenado_ia_trabajos')->where('id', $trabajoId)->update([
            'estado'             => 'completado',
            'progreso_texto'     => 'Listo',
            'campos_completados' => $camposCompletados,
            'campos_totales'     => $camposTotales,
            'costo_usd'          => round($resultado['costoTotalUsd'] ?? 0.0, 5),
            'resultado_json'     => json_encode($resultado, JSON_UNESCAPED_UNICODE),
            'terminado_en'       => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
        CLI::write("Trabajo {$trabajoId}: completado — {$camposCompletados} campos, USD " . round($resultado['costoTotalUsd'] ?? 0.0, 4), 'green');

        $this->avisar((int) $trabajo['ejemplo_id'], (int) ($trabajo['usuario_id'] ?? 0), $camposCompletados, $resultado['costoTotalUsd'] ?? 0.0);

        return EXIT_SUCCESS;
    }

    /** Notificación in-app + correo — un fallo de cualquiera de los dos no debe tumbar el resultado
     * ya guardado (mismo criterio que UsuariosController::enviarAccesos). */
    private function avisar(int $ejemploId, int $usuarioId, int $camposCompletados, float $costoUsd): void
    {
        $db      = db_connect();
        $ejemplo = $db->table('ejemplos')->where('id', $ejemploId)->get()->getRowArray();
        $nombreFicha = (string) ($ejemplo['nombre'] ?? 'tu ficha');

        if ($usuarioId > 0) {
            $db->table('notificaciones')->insert([
                'usuario_id'       => $usuarioId,
                'tipo'             => 'llenado_ia_completado',
                'mensaje'          => "El llenado con IA de \"{$nombreFicha}\" ya terminó — {$camposCompletados} campos completados.",
                'referencia_tipo'  => 'ejemplo',
                'referencia_id'    => $ejemploId,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        $usuario = $usuarioId > 0 ? $db->table('usuarios')->where('id', $usuarioId)->get()->getRowArray() : null;
        if ($usuario === null || empty($usuario['correo'])) {
            CLI::write('Sin usuario/correo para avisar — se omite el correo.', 'yellow');

            return;
        }

        try {
            (new CorreoService())->enviarLlenadoIACompletado(
                $usuario['correo'],
                (string) $usuario['nombre'],
                $nombreFicha,
                "Se completaron {$camposCompletados} campos (costo estimado de la IA: USD " . number_format($costoUsd, 4) . ').',
            );
            CLI::write("Correo enviado a {$usuario['correo']}.", 'green');
        } catch (Throwable $e) {
            log_message('error', '[llenado-ia-async] No se pudo avisar por correo del trabajo del ejemplo {id}: {msg}', ['id' => $ejemploId, 'msg' => $e->getMessage()]);
            CLI::error('No se pudo enviar el correo: ' . $e->getMessage());
        }
    }
}
