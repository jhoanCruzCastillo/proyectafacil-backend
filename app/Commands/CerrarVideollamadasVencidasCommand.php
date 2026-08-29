<?php

namespace App\Commands;

use App\Libraries\GoogleMeetService;
use Config\Google as GoogleConfig;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

// Job periódico (pensado para correr cada 10 min, ej. Railway Cron Jobs) — hace, sobre solicitudes
// de videollamada cuyo horario ya pasó:
//
// 1) Red de seguridad: si el asesor se olvidó de cortar la llamada (y con ella, la grabación),
//    la corta pasado horario_hora_fin + MARGEN_CORTE_MIN. Ver GoogleMeetService::
//    terminarLlamadaSiActiva() — no hay forma de "solo detener la grabación" en la Meet API.
// 2) Sincroniza el link de la grabación y el del resumen de Gemini: Google tarda en procesar
//    ambos archivos después de que la llamada termina, así que no alcanza con revisar una sola
//    vez — cada corrida reintenta las solicitudes que todavía no tienen el link guardado. Cuando
//    aparece, comparte el archivo de Drive (público o solo cliente/asesor según
//    google.compartirGrabacionPublica — ver GoogleMeetService::compartirPublico/compartirGrabacion)
//    y recién ahí guarda el link — así nunca se guarda un link que la app puede mostrar pero la
//    persona no puede abrir.
//
// Uso: php spark asesoria:cerrar-videollamadas-vencidas
class CerrarVideollamadasVencidasCommand extends BaseCommand
{
    protected $group       = 'app';
    protected $name        = 'asesoria:cerrar-videollamadas-vencidas';
    protected $description = 'Corta videollamadas que quedaron activas de más y sincroniza grabación/resumen.';

    private const MARGEN_CORTE_MIN = 15;
    private const MARGEN_ARCHIVOS_MIN = 20;

    public function run(array $params)
    {
        $db       = db_connect();
        // Una sola instancia para toda la corrida — evita renegociar el token OAuth por cada fila
        // (GoogleMeetService lo cachea internamente mientras la instancia viva). Si construirla
        // falla (ej. credenciales mal configuradas), mejor cortar todo el comando de una que
        // fallar fila por fila con el mismo error.
        $servicio = new GoogleMeetService();

        $this->cortarLlamadasActivas($db, $servicio);
        $this->sincronizarArchivo($db, $servicio, 'link_grabacion', fn (string $c) => $servicio->grabacionLista($c));
        // El resumen además guarda el texto ya extraído (resumen_ia_texto) — así el panel del
        // ticket no tiene que leer el Google Doc cada vez que se abre.
        $this->sincronizarArchivo(
            $db,
            $servicio,
            'link_resumen',
            fn (string $c) => $servicio->resumenListo($c),
            fn (array $archivo) => ['resumen_ia_texto' => $servicio->resumenTexto($archivo['fileId'])],
        );
    }

    private function cortarLlamadasActivas($db, GoogleMeetService $servicio): void
    {
        $limite = date('Y-m-d H:i:s', strtotime('-' . self::MARGEN_CORTE_MIN . ' minutes'));

        $filas = $db->table('solicitudes_asesoria')
            ->select('id, link_reunion')
            ->where('tipo', 'video')
            ->where('estado', 'agendado')
            ->like('link_reunion', 'https://meet.google.com/', 'after')
            ->where("CONCAT(horario_fecha, ' ', horario_hora_fin) <", $limite)
            ->get()->getResultArray();

        foreach ($filas as $fila) {
            try {
                $servicio->terminarLlamadaSiActiva((string) $fila['link_reunion']);
                CLI::write("Solicitud #{$fila['id']}: llamada verificada/cortada.", 'green');
            } catch (Throwable $e) {
                log_message('warning', 'CerrarVideollamadasVencidas: no se pudo cortar la llamada de la solicitud {id}: {msg}', [
                    'id'  => $fila['id'],
                    'msg' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Busca y comparte el archivo (grabación o resumen, según `$columna` y `$buscar`) de cada
     * solicitud de video que todavía no lo tiene guardado. `$buscar` recibe el meetingCode y
     * devuelve `['url'=>..., 'fileId'=>...]|null` — mismo contrato en grabacionLista() y
     * resumenListo(). El filtro `LIKE 'https://meet.google.com/%'` descarta de una los links
     * simulados de los seeders de demo (`proyectafacil.app/reunion/demo-...`) — nunca son
     * reuniones reales, no vale la pena ni el viaje a la API de Google.
     *
     * `$extra`, si se pasa, recibe el `$archivo` encontrado y devuelve columnas adicionales para
     * guardar junto con `$columna` en el mismo UPDATE (ver uso con resumen_ia_texto).
     */
    private function sincronizarArchivo($db, GoogleMeetService $servicio, string $columna, callable $buscar, ?callable $extra = null): void
    {
        $limite = date('Y-m-d H:i:s', strtotime('-' . self::MARGEN_ARCHIVOS_MIN . ' minutes'));

        $filas = $db->table('solicitudes_asesoria')
            ->select("id, cliente_id, docente_id, link_reunion")
            ->where('tipo', 'video')
            ->like('link_reunion', 'https://meet.google.com/', 'after')
            ->where("{$columna} IS NULL", null, false)
            ->where("CONCAT(horario_fecha, ' ', horario_hora_fin) <", $limite)
            ->get()->getResultArray();

        foreach ($filas as $fila) {
            $meetingCode = trim((string) parse_url((string) $fila['link_reunion'], PHP_URL_PATH), '/');
            if ($meetingCode === '') {
                continue;
            }

            try {
                $archivo = $buscar($meetingCode);
                if ($archivo === null) {
                    continue;
                }

                // Config temporal mientras no existe la sección "Seguridad" del panel — ver
                // google.compartirGrabacionPublica.
                if (config(GoogleConfig::class)->compartirGrabacionPublica) {
                    $servicio->compartirPublico($archivo['fileId']);
                } else {
                    $correos = $db->table('usuarios')
                        ->select('correo')
                        ->whereIn('id', [(int) $fila['cliente_id'], (int) $fila['docente_id']])
                        ->get()->getResultArray();
                    $correos = array_values(array_filter(array_map(static fn (array $u) => $u['correo'] ?? null, $correos)));

                    $servicio->compartirGrabacion($archivo['fileId'], $correos);
                }

                $db->table('solicitudes_asesoria')->where('id', $fila['id'])->update(array_merge(
                    [$columna => $archivo['url'], 'updated_at' => date('Y-m-d H:i:s')],
                    $extra !== null ? $extra($archivo) : [],
                ));
                CLI::write("Solicitud #{$fila['id']}: {$columna} sincronizado y compartido.", 'green');
            } catch (Throwable $e) {
                log_message('warning', 'CerrarVideollamadasVencidas: no se pudo sincronizar {columna} de la solicitud {id}: {msg}', [
                    'columna' => $columna,
                    'id'      => $fila['id'],
                    'msg'     => $e->getMessage(),
                ]);
            }
        }
    }
}
