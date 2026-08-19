<?php

namespace App\Libraries;

// Resuelve (Departamento, Provincia, Distrito) -> código UBIGEO de 6 dígitos, contra el catálogo
// oficial de 1874 distritos extraído de la hoja oculta "UBIGEO" del formato Excel real del MEF
// (`UBIGEO!$A$3:$D$1876`, columnas Ubigeo/Distrito/Provincia/Departamento). Existe porque a la IA se
// le pide el NOMBRE de la ubicación, no el código: pedirle el código de 6 dígitos directo demostró
// (sesión de llenado manual del CIAI Amanecer) producir códigos plausibles pero incorrectos —
// 060104 en vez de 060105. El código real se resuelve aquí, de forma determinista y verificada
// exacta contra el catálogo real, nunca "adivinado" por el modelo.
class UbigeoResolver
{
    /** @var array<string,string>|null clave normalizada "depto|prov|dist" -> código, cargado una vez por request */
    private static ?array $indice = null;

    /**
     * @return string|null el código de 6 dígitos si (departamento, provincia, distrito) matchea
     *   EXACTAMENTE una fila del catálogo (ignorando mayúsculas/acentos/espacios) — null si no hay
     *   match (evita adivinar entre distritos homónimos de departamentos distintos).
     */
    public static function resolver(string $departamento, string $provincia, string $distrito): ?string
    {
        self::cargar();

        return self::$indice[self::clave($departamento, $provincia, $distrito)] ?? null;
    }

    /**
     * Parsea el formato "Departamento | Provincia | Distrito" que se le pide a la IA (ver la nota de
     * la columna "ubigeo" en LlenadoIAController::construirPromptTabla) y resuelve el código. Tolera
     * espacios extra alrededor de cada parte; devuelve null si el texto no trae las 3 partes o si no
     * matchea el catálogo.
     */
    public static function resolverDesdeTexto(string $texto): ?string
    {
        $partes = array_map('trim', explode('|', $texto));
        if (count($partes) !== 3 || in_array('', $partes, true)) {
            return null;
        }

        return self::resolver($partes[0], $partes[1], $partes[2]);
    }

    private static function clave(string $departamento, string $provincia, string $distrito): string
    {
        return self::normalizar($departamento) . '|' . self::normalizar($provincia) . '|' . self::normalizar($distrito);
    }

    /**
     * Mismo criterio que LlenadoIAController::normalizarOpcion() y xlsxListas.ts (frontend):
     * minúsculas + sin tildes. Además quita un artículo inicial ("La Encañada" / "Encañada" deben
     * matchear igual) — el nombre OFICIAL del catálogo a veces lo trae y a veces no (ej. distrito
     * 060105 es literalmente "Encañada" en el catálogo, pero la fuente de la verdad y el uso común
     * dicen "distrito de La Encañada"), y no hay forma de saber de antemano cuál usará la IA.
     * Verificado sin colisiones: quitar el artículo de los 1874 distritos no genera 2 claves iguales.
     */
    private static function normalizar(string $s): string
    {
        $s             = mb_strtolower(trim($s));
        $transliterado = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        // iconv//TRANSLIT en este entorno no transforma "í"/"ó"/etc. directo a la letra base: deja un
        // apóstrofo delante ("í" -> "'i"). Sin quitarlo, "Junín" y "Junin" normalizan distinto y dejan
        // de matchear — se quita explícitamente en vez de confiar en que TRANSLIT ya da ASCII limpio.
        $s = $transliterado !== false ? str_replace("'", '', $transliterado) : $s;

        return preg_replace('/^(la|el|los|las)\s+/', '', $s) ?? $s;
    }

    private static function cargar(): void
    {
        if (self::$indice !== null) {
            return;
        }

        $ruta     = __DIR__ . '/data/ubigeo_peru.json';
        $catalogo = json_decode((string) file_get_contents($ruta), true);

        $indice = [];
        foreach (is_array($catalogo) ? $catalogo : [] as $fila) {
            $clave = self::clave((string) ($fila['departamento'] ?? ''), (string) ($fila['provincia'] ?? ''), (string) ($fila['distrito'] ?? ''));
            // En el catálogo real no hay 2 filas con la misma terna depto/prov/dist normalizada — si
            // alguna vez la hubiera, gana la primera (comportamiento estable, no aleatorio).
            $indice[$clave] ??= (string) ($fila['codigo'] ?? '');
        }
        self::$indice = $indice;
    }
}
