<?php

namespace App\Libraries;

use Psr\Http\Message\StreamInterface;

/**
 * Escribe un stream binario (S3/remoto) directo al cliente HTTP, a trozos, sin cargarlo entero en
 * memoria de PHP ni bufferizarlo en el ciclo de Response de CI4 (si lo bufferizáramos, el % de
 * progreso del navegador saltaría 0→100 de golpe). Extraído de ArchivosController::contenido() —
 * segundo consumidor: AsesoriaController::adjuntoMensaje().
 */
class StreamProxy
{
    /** Sale del ciclo normal de Response con exit — no hay nada útil que devolver después de esto. */
    public static function pipe(StreamInterface $stream, string $mime, string $nombre, ?int $length, string $disposition = 'inline'): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');

        $safeName = str_replace(['"', "\r", "\n"], '', $nombre);

        header('Content-Type: ' . $mime);
        if ($length !== null && $length > 0) {
            header('Content-Length: ' . $length);
        }
        header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
        header('Cache-Control: private, max-age=300');
        header('X-Accel-Buffering: no');
        header('Connection: close');

        if (function_exists('fastcgi_finish_request') === false) {
            flush();
        }

        $chunk = 64 * 1024;
        while (! $stream->eof()) {
            $data = $stream->read($chunk);
            if ($data === '') {
                break;
            }
            echo $data;
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
        $stream->close();

        exit;
    }
}
