<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Guías por sección de la FTE de establecimientos de salud de 12 horas con rol Puerta de Entrada
// (FTE-PE-SAL), destiladas del instructivo de la OPMI Salud (MINSA, 119 páginas).
//
// Por qué destilado y no el PDF entero: el instructivo son ~264.800 caracteres (~66.000 tokens),
// 6 veces la guía más grande que tiene hoy el sistema. Si se cargara como contexto GENERAL se
// reenviaría en cada una de las ~84 llamadas de un llenado completo de esta ficha (7 secciones +
// 77 tablas). Troceado por sección, cada llamada carga solo lo suyo — que es además lo que el
// modelo necesita para esa sección y nada más.
//
// Mismo patrón que ContextosIAFTEEBRSeeder / ContextosIACuidadoDiurnoSeeder. El markdown vive en
// content/guias-por-seccion/pe-sal-*.md (no inline) para poder leerlo y versionarlo como texto.
//
// Alcance actual: SECCIÓN A. Las demás se agregan sumando entradas a contextosPorSeccion().
//
// Idempotente: actualiza la fila si ya existe. Uso: php spark db:seed ContextosIAFTEPESALSeeder
class ContextosIAFTEPESALSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-PE-SAL';

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
     * La Sección A no lleva el global "Estructura de datos — Fichas técnicas" a propósito: no tiene
     * ningún campo tabla, así que esa convención JSON (~76.000 caracteres) sería peso muerto en cada
     * llamada de esta sección.
     */
    private function contextosPorSeccion(): array
    {
        return [
            'ANÁLISIS PREVIO DE LA INTERVENCIÓN' => [
                'globales' => ['Invierte.pe'],
                'archivo'  => 'pe-sal-01-analisis-previo.md',
            ],
            // La hoja principal: B (datos generales), C (brecha), D (institucionalidad),
            // E (identificación/formulación/evaluación) y F (conclusiones). Lleva "Finanzas" por
            // todo el bloque de costos, cronogramas y evaluación social de E2.4 y E3.
            'FTE Sección B-F' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Invierte.pe', 'Finanzas'],
                'archivo'  => 'pe-sal-02-secciones-b-f.md',
            ],
            // Mismo tramo de campos que la Alternativa 1 (de "b. Localización" a "E3.3"), por eso
            // lleva los mismos globales que la hoja B-F.
            'ALTERNATIVA 2 DE SOLUCIÓN' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Invierte.pe', 'Finanzas'],
                'archivo'  => 'pe-sal-03-alternativa-2.md',
            ],
            // G, H, I y J llevan "Estructura de datos — Fichas técnicas": son casi todo tablas
            // (13, 9 y 2 respectivamente, y 5 en J), así que la convención JSON sí les hace falta.
            'ANALISIS  DE SERVICIOS DE SALUD RIS' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Invierte.pe'],
                'archivo'  => 'pe-sal-04-seccion-g-ris.md',
            ],
            'EVALUACIÓN DE LA INFRAESTRUCTURA' => [
                'globales' => ['Estructura de datos — Fichas técnicas'],
                'archivo'  => 'pe-sal-05-seccion-h-infraestructura.md',
            ],
            'PROGRAMA MÉDICO FUNCIONAL' => [
                'globales' => ['Estructura de datos — Fichas técnicas'],
                'archivo'  => 'pe-sal-06-seccion-i-pmf.md',
            ],
            'DEL TERRENO ALTERNATIVA SELECCIONADA' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Invierte.pe'],
                'archivo'  => 'pe-sal-07-seccion-j-terreno.md',
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
