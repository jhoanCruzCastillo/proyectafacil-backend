<?php

namespace App\Libraries;

// Convierte a PNG los formatos de imagen que no son web. El caso real: la plantilla oficial del
// CIAI incrusta sus mapas como EMF (Enhanced Metafile de Windows) — un formato vectorial que ni el
// navegador pinta en un <img>, ni Cloudinary acepta como resource_type=image.
//
// La conversión se delega a LibreOffice headless, que importa EMF/WMF de forma fiable. Es una
// dependencia PESADA (~400 MB en la imagen Docker), así que esta clase está escrita para que su
// AUSENCIA no rompa nada: si el binario no está, `disponible()` devuelve false y el llamador decide
// qué hacer (en el volcado, reportar la imagen como omitida en vez de fallar).
class ConversorImagen
{
    /** Formatos que el navegador muestra directamente y Cloudinary acepta como imagen */
    private const FORMATOS_WEB = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg'];

    /** Rutas donde suele estar el binario de LibreOffice según el sistema */
    private const CANDIDATOS = [
        'soffice',
        '/usr/bin/soffice',
        '/usr/lib/libreoffice/program/soffice',
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
    ];

    public static function esFormatoWeb(string $formato): bool
    {
        return in_array(strtolower($formato), self::FORMATOS_WEB, true);
    }

    /** Ruta al binario de LibreOffice, o null si no está instalado. */
    public static function binario(): ?string
    {
        foreach (self::CANDIDATOS as $cmd) {
            // -h termina rápido y no abre interfaz; basta para saber si el binario responde.
            $salida = [];
            $codigo = 1;
            @exec(escapeshellarg($cmd) . ' --version 2>&1', $salida, $codigo);
            if ($codigo === 0) {
                return $cmd;
            }
        }

        return null;
    }

    public static function disponible(): bool
    {
        return self::binario() !== null;
    }

    /**
     * Convierte un binario de imagen a PNG. Devuelve los bytes del PNG, o null si no hay conversor
     * instalado o la conversión falla — nunca lanza: el llamador ya tiene un camino para "esta
     * imagen no se pudo traer".
     */
    public static function aPng(string $bytes, string $formatoOrigen): ?string
    {
        $soffice = self::binario();
        if ($soffice === null) {
            return null;
        }

        $dir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'pf_img_' . bin2hex(random_bytes(6));
        if (! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            return null;
        }

        $entrada = $dir . DIRECTORY_SEPARATOR . 'origen.' . preg_replace('/[^a-z0-9]/i', '', $formatoOrigen);
        $png     = null;

        try {
            if (@file_put_contents($entrada, $bytes) === false) {
                return null;
            }

            $cmd = escapeshellarg($soffice)
                . ' --headless --norestore --convert-to png --outdir '
                . escapeshellarg($dir) . ' ' . escapeshellarg($entrada) . ' 2>&1';

            $salida = [];
            $codigo = 1;
            @exec($cmd, $salida, $codigo);

            $destino = $dir . DIRECTORY_SEPARATOR . 'origen.png';
            if (is_file($destino)) {
                $contenido = @file_get_contents($destino);
                $png       = $contenido === false ? null : $contenido;
            }
        } finally {
            // Limpieza best-effort: el directorio es temporal y por proceso.
            foreach (@glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($dir);
        }

        return $png;
    }
}
