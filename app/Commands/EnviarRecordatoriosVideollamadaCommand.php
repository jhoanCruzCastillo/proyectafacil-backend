<?php

namespace App\Commands;

use App\Libraries\CorreoService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

// Job periódico (pensado para correr cada 1-2 min, ej. Railway Cron Jobs — cuanto más seguido
// corra, más preciso el "5 minutos antes") — manda un correo recordatorio al cliente y al docente
// registrados de cada videollamada agendada cuyo horario de inicio cae dentro de los próximos 5
// minutos. `recordatorio_enviado` evita reenviarlo en corridas siguientes; se marca aunque algún
// correo puntual falle (ver enviarSiCorresponde) para no reintentar por siempre la misma fila.
//
// Uso: php spark asesoria:enviar-recordatorios-videollamada
class EnviarRecordatoriosVideollamadaCommand extends BaseCommand
{
    protected $group       = 'app';
    protected $name        = 'asesoria:enviar-recordatorios-videollamada';
    protected $description = 'Envía el correo "tu videollamada empieza en 5 minutos" a cliente y docente.';

    private const MINUTOS_ANTES = 5;

    public function run(array $params)
    {
        $db      = db_connect();
        $ahora   = date('Y-m-d H:i:s');
        $limite  = date('Y-m-d H:i:s', strtotime('+' . self::MINUTOS_ANTES . ' minutes'));
        $correo  = new CorreoService();

        $filas = $db->table('solicitudes_asesoria')
            ->select('id, cliente_id, docente_id, horario_fecha, horario_hora_inicio, link_reunion')
            ->where('tipo', 'video')
            ->where('estado', 'agendado')
            ->where('recordatorio_enviado', 0)
            ->like('link_reunion', 'https://meet.google.com/', 'after')
            ->where("CONCAT(horario_fecha, ' ', horario_hora_inicio) >", $ahora)
            ->where("CONCAT(horario_fecha, ' ', horario_hora_inicio) <=", $limite)
            ->get()->getResultArray();

        foreach ($filas as $fila) {
            $horaInicio = substr((string) $fila['horario_hora_inicio'], 0, 5);
            $usuarios   = $db->table('usuarios')
                ->select('id, nombre, correo')
                ->whereIn('id', array_filter([(int) $fila['cliente_id'], (int) $fila['docente_id']]))
                ->get()->getResultArray();

            foreach ($usuarios as $u) {
                if (empty($u['correo'])) {
                    continue; // no registró correo — nada que enviar
                }
                try {
                    $correo->enviarRecordatorioVideollamada((string) $u['correo'], (string) $u['nombre'], $horaInicio, (string) $fila['link_reunion']);
                    CLI::write("Solicitud #{$fila['id']}: recordatorio enviado a {$u['correo']}.", 'green');
                } catch (Throwable $e) {
                    log_message('warning', 'EnviarRecordatoriosVideollamada: no se pudo enviar el recordatorio de la solicitud {id} a {correo}: {msg}', [
                        'id'     => $fila['id'],
                        'correo' => $u['correo'],
                        'msg'    => $e->getMessage(),
                    ]);
                }
            }

            // Se marca aunque algún correo individual haya fallado — evita reintentar por siempre
            // la misma fila en cada corrida (ver comentario de la clase).
            $db->table('solicitudes_asesoria')->where('id', $fila['id'])->update(['recordatorio_enviado' => 1]);
        }
    }
}
