<?php

namespace App\Libraries;

use App\Models\SesionModel;
use Config\Encryption;

/**
 * Token Bearer firmado (HMAC-SHA256) para la API.
 * Evita depender de la cookie de sesión PHP — el SPA guarda el token en localStorage
 * y lo envía en Authorization en cada request.
 *
 * `sesionId` (jti) referencia una fila real en `sesiones` (ver SesionModel/SesionesController) —
 * sin esto el JWT era válido hasta que expiraba solo, sin forma de revocar una sesión puntual
 * ("Cerrar sesión" en el panel de detalles del usuario). hidratarSesionSiHay() valida en cada
 * request que esa fila siga sin revocar.
 */
class AuthToken
{
    /** 30 días — sesión de trabajo larga; el logout del cliente invalida el uso local. */
    private const TTL_SEGUNDOS = 60 * 60 * 24 * 30;

    /**
     * @param array{usuarioId: string, nombre: string, usuario: string, rol: string, iniciadaEn: string} $sesion
     */
    public static function emitir(array $sesion, string $sesionId): string
    {
        $now = time();
        $header  = self::b64(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = self::b64([
            'usuarioId'  => (string) $sesion['usuarioId'],
            'nombre'     => (string) $sesion['nombre'],
            'usuario'    => (string) $sesion['usuario'],
            'rol'        => (string) $sesion['rol'],
            'iniciadaEn' => (string) $sesion['iniciadaEn'],
            'sesionId'   => $sesionId,
            'iat'        => $now,
            'exp'        => $now + self::TTL_SEGUNDOS,
        ]);
        $firma = self::b64Raw(hash_hmac('sha256', $header . '.' . $payload, self::secreto(), true));

        return $header . '.' . $payload . '.' . $firma;
    }

    /**
     * @return array{usuarioId: string, nombre: string, usuario: string, rol: string, iniciadaEn: string, sesionId: string}|null
     */
    public static function verificar(string $token): ?array
    {
        $partes = explode('.', $token);
        if (count($partes) !== 3) {
            return null;
        }

        [$header, $payload, $firma] = $partes;
        $esperada = self::b64Raw(hash_hmac('sha256', $header . '.' . $payload, self::secreto(), true));
        if (! hash_equals($esperada, $firma)) {
            return null;
        }

        $data = self::jsonDecode($payload);
        if (! is_array($data)) {
            return null;
        }

        if (! isset($data['exp']) || (int) $data['exp'] < time()) {
            return null;
        }

        if (
            ! isset($data['usuarioId'], $data['nombre'], $data['usuario'], $data['rol'], $data['iniciadaEn'])
            || $data['usuarioId'] === ''
        ) {
            return null;
        }

        return [
            'usuarioId'  => (string) $data['usuarioId'],
            'nombre'     => (string) $data['nombre'],
            'usuario'    => (string) $data['usuario'],
            'rol'        => (string) $data['rol'],
            'iniciadaEn' => (string) $data['iniciadaEn'],
            // Tokens emitidos antes de que existiera `sesiones` no traen este claim — '' hace que
            // la validación contra la BD (ver hidratarSesionSiHay) los rechace, forzando relogin.
            'sesionId'   => isset($data['sesionId']) ? (string) $data['sesionId'] : '',
        ];
    }

    /**
     * Lee Authorization: Bearer … del request actual y confirma contra `sesiones` que ese token no
     * fue revocado. Punto único de verificación — tanto AuthFilter (hidratarSesionSiHay) como
     * AuthController::me()/sesionActual() pasan por acá, así que "Cerrar sesión" desde el panel de
     * detalles corta el acceso de verdad en cualquier endpoint, no solo en los que van detrás del
     * filtro 'auth'.
     *
     * @return array{usuarioId: string, nombre: string, usuario: string, rol: string, iniciadaEn: string, sesionId: string}|null
     */
    public static function desdeRequest(): ?array
    {
        $header = service('request')->getHeaderLine('Authorization');
        if ($header === '' || ! preg_match('/^Bearer\s+(\S+)/i', $header, $m)) {
            return null;
        }

        $claims = self::verificar($m[1]);
        if ($claims === null || $claims['sesionId'] === '') {
            return null;
        }

        $fila = (new SesionModel())->find((int) $claims['sesionId']);
        if (! $fila || (int) $fila['revocada'] === 1 || (int) $fila['usuario_id'] !== (int) $claims['usuarioId']) {
            return null;
        }

        return $claims;
    }

    /**
     * Hidrata la sesión CI4 desde el Bearer (para código que aún lee session()->get('usuario_*')).
     * La validación de revocación ya la hizo desdeRequest() — acá solo se refresca la marca de
     * actividad de la fila en `sesiones`.
     */
    public static function hidratarSesionSiHay(): bool
    {
        $claims = self::desdeRequest();
        if ($claims === null) {
            return false;
        }

        (new SesionModel())->update((int) $claims['sesionId'], ['ultima_actividad' => date('Y-m-d H:i:s')]);

        session()->set([
            'usuario_id'          => $claims['usuarioId'],
            'usuario_nombre'      => $claims['nombre'],
            'usuario_usuario'     => $claims['usuario'],
            'usuario_rol'         => $claims['rol'],
            'usuario_iniciada_en' => $claims['iniciadaEn'],
            'sesion_id'           => $claims['sesionId'],
        ]);

        return true;
    }

    private static function secreto(): string
    {
        $key = config(Encryption::class)->key;
        if ($key === '') {
            // Fallback solo para entornos mal configurados — en prod encryption.key debe existir.
            return 'proyectafacil-dev-auth-secret';
        }

        return $key;
    }

    /** @param array<string, mixed> $data */
    private static function b64(array $data): string
    {
        return self::b64Raw(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private static function b64Raw(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /** @return array<string, mixed>|null */
    private static function jsonDecode(string $b64): ?array
    {
        $json = base64_decode(strtr($b64, '-_', '+/'), true);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }
}
