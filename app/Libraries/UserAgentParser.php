<?php

namespace App\Libraries;

// Detección simple de SO/navegador a partir del User-Agent — no hay librería de parsing en el
// proyecto y no hace falta una: alcanza con substrings para los casos reales (Windows/macOS/
// Linux/Android/iOS × Chrome/Firefox/Safari/Edge).
class UserAgentParser
{
    public static function dispositivo(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS')  => 'macOS',
            str_contains($userAgent, 'iPhone')  => 'iPhone',
            str_contains($userAgent, 'iPad')    => 'iPad',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Linux')   => 'Linux',
            default                             => 'Desconocido',
        };
    }

    public static function navegador(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/')     => 'Edge',
            str_contains($userAgent, 'OPR/')     => 'Opera',
            str_contains($userAgent, 'Chrome/')  => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/')  => 'Safari',
            default                              => 'Desconocido',
        };
    }
}
