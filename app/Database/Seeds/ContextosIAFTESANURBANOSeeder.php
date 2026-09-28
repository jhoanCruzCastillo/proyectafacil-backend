<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Guías por sección de la FTE de los servicios de saneamiento en el ámbito urbano (FTE-SAN-URBANO),
// destiladas del instructivo del MVCS (58 páginas).
//
// Por qué destilado y troceado: el instructivo son ~149.000 caracteres (~37.000 tokens). Como
// contexto GENERAL se reenviaría en cada una de las ~124 llamadas de un llenado completo de esta
// ficha (18 secciones + 106 tablas). Troceado por sección, cada llamada carga solo lo suyo.
//
// Mismo patrón que ContextosIAFTEPESALSeeder / ContextosIAFTEEBRSeeder. El markdown vive en
// content/guias-por-seccion/san-urbano-*.md (no inline) para poder leerlo y versionarlo como texto.
//
// Alcance actual: SECCIÓN I (Aspectos Generales) y todo el MÓDULO II (Identificación, seis
// secciones). Las demás se agregan sumando entradas a contextosPorSeccion().
//
// Idempotente: actualiza la fila si ya existe. Uso: php spark db:seed ContextosIAFTESANURBANOSeeder
class ContextosIAFTESANURBANOSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-SAN-URBANO';

    private CloudinaryUploader $cloudinary;

    public function run(): void
    {
        $this->cloudinary = new CloudinaryUploader();

        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null) {
            echo 'No existe la plantilla ' . self::CODIGO_PLANTILLA . " — corre PlantillasSeeder primero.\n";

            return;
        }
        $plantillaId = (int) $plantilla['id'];

        $secciones = $this->seccionesDe($plantilla);
        if ($secciones === []) {
            echo "La plantilla no tiene estructura importada todavía — no hay secciones a las que asociar contexto.\n";

            return;
        }

        $globalIds  = $this->globalesExistentes();
        $insertados = 0;

        foreach ($this->contextosPorSeccion() as $clave => $datos) {
            $seccionId = $this->buscarSeccion($secciones, $clave);
            if ($seccionId === null) {
                echo "  · No se encontró la sección «{$clave}» — se omite.\n";

                continue;
            }
            $ruta = __DIR__ . '/content/guias-por-seccion/' . $datos['archivo'];
            if (! is_file($ruta)) {
                echo "  · Falta el markdown {$datos['archivo']} — se omite.\n";

                continue;
            }
            $ids = array_values(array_filter(array_map(static fn (string $n) => $globalIds[$n] ?? null, $datos['globales'])));
            $this->guardarContexto($plantillaId, $seccionId, (string) file_get_contents($ruta), $ids);
            $insertados++;
        }

        echo "Listo — {$insertados} contexto(s) de sección sembrados para " . self::CODIGO_PLANTILLA . ".\n";
    }

    /**
     * Clave = fragmento del nombre de la sección tal como está en la plantilla.
     *
     * "Estructura de datos — Fichas técnicas" va en toda sección que tenga tablas (acá casi todas:
     * la ficha tiene 106). "Finanzas" solo en los tramos de costos, flujos y evaluación social.
     */
    private function contextosPorSeccion(): array
    {
        return [
            'I. ASPECTOS GENERALES' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Invierte.pe'],
                'archivo'  => 'san-urbano-01-aspectos-generales.md',
            ],
            // Módulo II. Las seis claves de abajo son substrings ÚNICOS: las seis secciones empiezan
            // con "II. IDENTIFICACIÓN", y la del módulo raíz se resuelve por coincidencia exacta
            // (ver buscarSeccion()).
            'II. IDENTIFICACIÓN' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Invierte.pe'],
                'archivo'  => 'san-urbano-02-area-estudio.md',
            ],
            'Diagnóstico de la UP: agua potable' => [
                'globales' => ['Estructura de datos — Fichas técnicas'],
                'archivo'  => 'san-urbano-03-up-agua-potable.md',
            ],
            'alcantarillado y tratamiento' => [
                'globales' => ['Estructura de datos — Fichas técnicas'],
                'archivo'  => 'san-urbano-04-up-alcantarillado-ptar.md',
            ],
            '2.2.6 y 2.2.7' => [
                'globales' => ['Estructura de datos — Fichas técnicas'],
                'archivo'  => 'san-urbano-05-exposicion-vulnerabilidad.md',
            ],
            // Lleva "Finanzas" por el costo de O&M mensual, la tarifa y el subsidio de 6.01.1.
            '2.2.8. Diagnóstico de la gestión operativa' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Finanzas'],
                'archivo'  => 'san-urbano-06-gestion-operativa.md',
            ],
            // La más larga del módulo: población, problema/objetivo, alternativas y aporte al cierre
            // de brecha. "Finanzas" por el bloque de consumo de los no conectados y los cálculos de
            // brecha del 2.6.
            '2.3 a 2.6' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Invierte.pe', 'Finanzas'],
                'archivo'  => 'san-urbano-07-poblacion-problema-alternativas.md',
            ],
        ];
    }

    /** @return array<string,int> nombre => id de los contextos GLOBALES ya sembrados. */
    private function globalesExistentes(): array
    {
        $ids = [];
        foreach ($this->db->table('contextos_ia_globales')->get()->getResultArray() as $g) {
            $ids[$g['nombre']] = (int) $g['id'];
        }

        return $ids;
    }

    private function seccionesDe(array $plantilla): array
    {
        if (empty($plantilla['asignado_archivo_id'])) {
            return [];
        }
        $archivo = $this->db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        if ($archivo === null || empty($archivo['contenido_json'])) {
            return [];
        }

        return json_decode((string) $archivo['contenido_json'], true)['secciones'] ?? [];
    }

    /** Busca la sección cuyo nombre contenga la clave (sin acentos ni mayúsculas). */
    private function buscarSeccion(array $secciones, string $clave): ?string
    {
        $normaliza = static fn (string $t) => strtr(
            mb_strtolower($t, 'UTF-8'),
            ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', '°' => ''],
        );
        $buscado = $normaliza($clave);

        // Exacto primero. En esta ficha SEIS secciones empiezan con "II. IDENTIFICACION" y cinco lo
        // llevan como prefijo de un nombre más largo; buscando solo por substring, la clave del
        // módulo raíz se llevaría la primera coincidencia y las demás quedarían sin guía (o peor,
        // con la guía equivocada según el orden del arreglo).
        foreach ($secciones as $s) {
            if ($normaliza((string) ($s['nombre'] ?? '')) === $buscado) {
                return (string) $s['id'];
            }
        }
        foreach ($secciones as $s) {
            if (str_contains($normaliza((string) ($s['nombre'] ?? '')), $buscado)) {
                return (string) $s['id'];
            }
        }

        return null;
    }

    private function guardarContexto(int $plantillaId, string $seccionId, string $markdown, array $globalIds): void
    {
        $ahora = date('Y-m-d H:i:s');
        $url   = $this->cloudinary->subirMarkdown($markdown, "seccion-{$plantillaId}-{$seccionId}.md");
        $fila  = $this->db->table('contextos_ia_seccion')
            ->where('plantilla_id', $plantillaId)->where('seccion_id', $seccionId)
            ->get()->getRowArray();

        if ($fila === null) {
            $this->db->table('contextos_ia_seccion')->insert([
                'plantilla_id' => $plantillaId,
                'seccion_id'   => $seccionId,
                'url'          => $url,
                'created_at'   => $ahora,
                'updated_at'   => $ahora,
            ]);
            $contextoId = (int) $this->db->insertID();
        } else {
            $contextoId = (int) $fila['id'];
            $this->db->table('contextos_ia_seccion')->where('id', $contextoId)
                ->update(['url' => $url, 'updated_at' => $ahora]);
        }

        $this->db->table('contexto_seccion_globales')->where('contexto_seccion_id', $contextoId)->delete();
        foreach (array_unique($globalIds) as $gid) {
            $this->db->table('contexto_seccion_globales')->insert([
                'contexto_seccion_id' => $contextoId,
                'contexto_global_id'  => $gid,
            ]);
        }
    }
}
