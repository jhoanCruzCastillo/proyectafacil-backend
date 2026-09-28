<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Guia de llenado del Formato N.o 07-C (Registro de IOARR), destilada de los Lineamientos IOARR
// del MEF (77 paginas).
//
// Caso particular respecto de las otras plantillas: el 07-C tiene UNA SOLA seccion con los 128
// campos, asi que no hay troceo por hojas — la guia entera acompana a cada llamada de llenado.
// Aun asi vale la pena que viva aca y no en el contexto general, porque lo que explica es COMO se
// llena el formato (bloques excluyentes, umbral de 75 UIT), mientras el general "Lineamientos
// IOARR" explica QUE es una IOARR y cuando deja de serlo.
//
// Lleva los tres globales: "Estructura de datos" por las 16 tablas del formato, "Invierte.pe" por
// la nomenclatura del Banco de Inversiones y "Finanzas" por los bloques F y G (costos, cronogramas
// de inversion y de mantenimiento).
//
// Idempotente: actualiza la fila si ya existe. Uso: php spark db:seed ContextosIAIOARR7CSeeder
class ContextosIAIOARR7CSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = '7C';

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

    /** Clave = fragmento del nombre de la seccion tal como esta en la plantilla. */
    private function contextosPorSeccion(): array
    {
        return [
            'Registro de IOARR' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Invierte.pe', 'Finanzas'],
                'archivo'  => 'ioarr-7c-registro.md',
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
