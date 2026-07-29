<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Complementa a PlantillasSeeder (que solo siembra metadata: código/nombre/sector) con el
// contenido REAL construido a mano en el editor — secciones/subsecciones/campos, vía
// data/plantillas_estructura.json (generado por `php spark plantillas:export-estructura` contra
// la BD local). No depende de Cloudinary: usa `archivo_default_url` de la propia plantilla como
// `url` del archivo (referencia relativa, igual que hoy antes de subir el Excel real) — subir el
// binario a Cloudinary sigue siendo aparte, vía UploadFichasExcelCommand, cuando haya credenciales.
//
// Requiere que PlantillasSeeder ya haya corrido (necesita que las plantillas por código existan).
// Idempotente: si una plantilla ya tiene asignado_archivo_id, o un ejemplo de referencia con el
// mismo nombre para esa plantilla ya existe, se salta.
class EstructuraPlantillasSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = __DIR__ . '/data/plantillas_estructura.json';
        if (! is_file($ruta)) {
            echo "No existe {$ruta} — corre primero 'php spark plantillas:export-estructura' en el ambiente de origen.\n";

            return;
        }

        $data = json_decode(file_get_contents($ruta), true);

        $idPorCodigo = [];
        foreach ($this->db->table('plantillas')->select('id, codigo, archivo_default_url')->get()->getResultArray() as $row) {
            $idPorCodigo[$row['codigo']] = $row;
        }

        $this->sembrarArchivosDePlantilla($data['plantillas'] ?? [], $idPorCodigo);
        $this->sembrarEjemplosDeReferencia($data['ejemplos'] ?? [], $idPorCodigo);
    }

    private function sembrarArchivosDePlantilla(array $plantillas, array $idPorCodigo): void
    {
        $ahora = date('Y-m-d H:i:s');

        foreach ($plantillas as $p) {
            $plantilla = $idPorCodigo[$p['codigo']] ?? null;
            if ($plantilla === null) {
                continue;
            }

            $yaAsignado = $this->db->table('plantillas')->where('id', $plantilla['id'])->where('asignado_archivo_id IS NOT NULL', null, false)->countAllResults() > 0;
            if ($yaAsignado) {
                continue;
            }

            $contenidoJson = $p['contenidoJson'] !== null ? json_encode($p['contenidoJson'], JSON_UNESCAPED_UNICODE) : null;

            $this->db->table('archivos')->insert([
                'propietario_tipo' => 'plantilla',
                'plantilla_id'     => $plantilla['id'],
                'nombre'           => $p['archivoNombre'] ?? basename((string) $plantilla['archivo_default_url']),
                'url'              => $p['archivoUrl'] ?? $plantilla['archivo_default_url'] ?? '',
                'contenido_json'   => $contenidoJson,
                'fecha_subida'     => $ahora,
            ]);
            $archivoId = $this->db->insertID();

            $this->db->table('plantillas')->where('id', $plantilla['id'])->update(['asignado_archivo_id' => $archivoId]);
        }
    }

    private function sembrarEjemplosDeReferencia(array $ejemplos, array $idPorCodigo): void
    {
        $ahora = date('Y-m-d H:i:s');

        foreach ($ejemplos as $e) {
            $plantilla = $idPorCodigo[$e['plantillaCodigo']] ?? null;
            if ($plantilla === null) {
                continue;
            }

            $yaExiste = $this->db->table('ejemplos')
                ->where('plantilla_id', $plantilla['id'])
                ->where('nombre', $e['nombre'])
                ->where('propietario_usuario_id IS NULL', null, false)
                ->countAllResults() > 0;
            if ($yaExiste) {
                continue;
            }

            $this->db->table('ejemplos')->insert([
                'plantilla_id'            => $plantilla['id'],
                'nombre'                  => $e['nombre'],
                'subtitulo'               => $e['subtitulo'] ?? null,
                'detalle'                 => $e['detalle'] ?? null,
                'activo'                  => $e['activo'] ? 1 : 0,
                'propietario_usuario_id'  => null,
                'creado_por_usuario_id'   => null,
                'compartida'              => $e['compartida'] ? 1 : 0,
                'estado'                  => $e['estado'] ?? 'archivado',
                'created_at'              => $ahora,
                'updated_at'              => $ahora,
            ]);
            $ejemploId = $this->db->insertID();

            foreach ($e['tipologiasIoarr'] ?? [] as $tipologia) {
                $this->db->table('ejemplo_tipologia_ioarr')->ignore(true)->insert([
                    'ejemplo_id' => $ejemploId,
                    'tipologia'  => $tipologia,
                ]);
            }

            if ($e['contenidoJson'] !== null) {
                $this->db->table('archivos')->insert([
                    'propietario_tipo' => 'ejemplo',
                    'ejemplo_id'       => $ejemploId,
                    'nombre'           => $e['nombre'],
                    'url'              => '',
                    'contenido_json'   => json_encode($e['contenidoJson'], JSON_UNESCAPED_UNICODE),
                    'fecha_subida'     => $ahora,
                ]);
            }
        }
    }
}
