<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Señal de que el usuario canceló un trabajo de `llenado_ia_trabajos` mientras corría (ver
 * LlenadoIAController::ejecutarLoteEnParalelo() y IaEjecutarLlenado::run()) — se distingue de un
 * Throwable real para que el trabajo quede en estado 'cancelado' en vez de 'error'.
 */
class LlenadoIACanceladoException extends RuntimeException
{
}
