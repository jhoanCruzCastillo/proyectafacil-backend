<?php

namespace App\Libraries;

use DateTimeImmutable;

// Espejo exacto de frontend/src/lib/horarioRecurrencia.ts (`ocurreEnFecha`) — deben mantenerse
// iguales en ambos lados. Usado donde el backend necesita saber, para una fecha real, si una
// regla de `horarios_docente` (fecha_inicio + tipo_repeticion) produce una ocurrencia ese día:
// SolicitudAsesoriaHelpersTrait::asesoresPorHorario (matchmaking real de una solicitud) y
// TicketsAsesoriaController::coberturaHorarios (mapa de calor del Administrativo de Asesorías).
class HorarioRecurrencia
{
    /** $regla trae al menos 'fecha_inicio' ("Y-m-d") y 'tipo_repeticion'. */
    public static function ocurreEnFecha(array $regla, string $fechaIso): bool
    {
        $fechaInicio = substr((string) $regla['fecha_inicio'], 0, 10);
        if ($fechaIso < $fechaInicio) {
            return false;
        }

        $inicio   = new DateTimeImmutable($fechaInicio);
        $objetivo = new DateTimeImmutable($fechaIso);

        switch ((string) $regla['tipo_repeticion']) {
            case 'diaria':
                return true;
            case 'lunes_a_viernes':
                $dia = (int) $objetivo->format('N'); // 1=lunes..7=domingo
                return $dia >= 1 && $dia <= 5;
            case 'semanal':
                return ((int) $inicio->diff($objetivo)->days) % 7 === 0;
            case 'mensual':
                return $inicio->format('j') === $objetivo->format('j');
            case 'anual':
                return $inicio->format('m-d') === $objetivo->format('m-d');
            default:
                return false;
        }
    }
}
