<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

// Lleva a un ambiente YA EN USO (Railway con alumnos/asesores/clientes reales) el contenido de
// plantillas que hoy solo vive en data/plantillas_estructura.json, SIN borrar nada de lo que ya
// existe. Reemplaza el camino "LimpiarBaseDatosSeeder + DeployDemoCompletoSeeder", que destruye
// usuarios y fichas de clientes.
//
// Qué toca (y solo esto):
// - `archivos.contenido_json` de la estructura asignada a cada plantilla (se actualiza EN SU
//   SITIO: mismo archivo, mismo id, mismos vínculos). Si la plantilla aún no tiene estructura, se
//   crea como hace EstructuraPlantillasSeeder. NUNCA pisa una estructura existente con `null`, ni
//   toca `url`/`nombre` del archivo (en producción puede apuntar ya al Excel real subido).
// - Ejemplos AUTORADOS POR EL ADMIN (propietario_usuario_id NULL): los que no existen se crean; los
//   marcados `esReferenciaIA` se actualizan (contenido + marca de referencia few-shot, exclusiva
//   por plantilla). Los demás ejemplos del admin que ya existan no se tocan.
//
// Qué NO toca jamás: usuarios, sesiones, planes/facturación, asesorías, tickets, chats,
// candidatos, ni ninguna ficha de cliente (ejemplos con propietario_usuario_id).
//
// Antes de sobrescribir una estructura guarda una copia del contenido anterior en
// writable/backups/contenido-plantillas/<fecha>/<codigo>.json, e informa cuántas fichas de clientes
// tienen valores en identificadores que la nueva estructura ya no trae (esos valores quedarían
// huérfanos — siguen en la BD, solo dejan de mostrarse en el editor).
class SincronizadorContenidoPlantillas
{
    private const FLAGS_JSON = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /** @var callable(string):void */
    private $log;

    private string $carpetaBackup;

    /** @var array<string,int> */
    private array $resumen = ['estructuras_actualizadas' => 0, 'estructuras_creadas' => 0, 'estructuras_sin_cambios' => 0, 'ejemplos_creados' => 0, 'ejemplos_actualizados' => 0];

    public function __construct(private BaseConnection $db, private bool $aplicar, callable $log)
    {
        $this->log           = $log;
        $this->carpetaBackup = WRITEPATH . 'backups/contenido-plantillas/' . date('Ymd-His');
    }

    /** @return array<string,int> */
    public function ejecutar(): array
    {
        $ruta = APPPATH . 'Database/Seeds/data/plantillas_estructura.json';
        if (! is_file($ruta)) {
            ($this->log)("No existe {$ruta}.");

            return $this->resumen;
        }
        $data = json_decode((string) file_get_contents($ruta), true);

        $plantillas = [];
        foreach ($this->db->table('plantillas')->select('id, codigo, nombre, archivo_default_url, asignado_archivo_id')->get()->getResultArray() as $fila) {
            $plantillas[$fila['codigo']] = $fila;
        }

        ($this->log)('== Estructuras de plantilla ==');
        foreach ($data['plantillas'] ?? [] as $p) {
            $fila = $plantillas[$p['codigo']] ?? null;
            if ($fila === null) {
                ($this->log)("  {$p['codigo']}: la plantilla no existe en esta BD — se omite (corre PlantillasSeeder).");

                continue;
            }
            $this->sincronizarEstructura($fila, $p);
        }

        ($this->log)('== Ejemplos de referencia (autorados por el admin) ==');
        foreach ($data['ejemplos'] ?? [] as $e) {
            $fila = $plantillas[$e['plantillaCodigo']] ?? null;
            if ($fila === null) {
                continue;
            }
            $this->sincronizarEjemplo($fila, $e);
        }

        return $this->resumen;
    }

    private function sincronizarEstructura(array $plantilla, array $p): void
    {
        $codigo = $plantilla['codigo'];
        $nuevo  = $p['contenidoJson'] ?? null;
        $ahora  = date('Y-m-d H:i:s');

        $archivo = $plantilla['asignado_archivo_id'] !== null
            ? $this->db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray()
            : null;

        if ($archivo === null) {
            ($this->log)("  {$codigo}: sin estructura en esta BD -> se crea" . ($this->aplicar ? '' : ' (simulación)') . '.');
            $this->resumen['estructuras_creadas']++;
            if ($this->aplicar) {
                $this->db->table('archivos')->insert([
                    'propietario_tipo' => 'plantilla',
                    'plantilla_id'     => $plantilla['id'],
                    'nombre'           => $p['archivoNombre'] ?? basename((string) $plantilla['archivo_default_url']),
                    'url'              => $p['archivoUrl'] ?? $plantilla['archivo_default_url'] ?? '',
                    'contenido_json'   => $nuevo !== null ? json_encode($nuevo, self::FLAGS_JSON) : null,
                    'fecha_subida'     => $ahora,
                ]);
                $this->db->table('plantillas')->where('id', $plantilla['id'])->update(['asignado_archivo_id' => $this->db->insertID()]);
            }

            return;
        }

        if ($nuevo === null) {
            $this->resumen['estructuras_sin_cambios']++;

            return; // el snapshot no trae contenido: jamás se pisa lo que ya hay
        }

        $actualTexto = $archivo['contenido_json'];
        $actual      = $actualTexto !== null ? json_decode((string) $actualTexto, true) : null;
        if ($actual !== null && json_encode($actual, self::FLAGS_JSON) === json_encode($nuevo, self::FLAGS_JSON)) {
            $this->resumen['estructuras_sin_cambios']++;
            ($this->log)("  {$codigo}: sin cambios.");

            return;
        }

        $idsViejos = $actual !== null ? $this->identificadores($actual) : [];
        $idsNuevos = $this->identificadores($nuevo);
        $quitados  = array_values(array_diff($idsViejos, $idsNuevos));
        $agregados = array_values(array_diff($idsNuevos, $idsViejos));
        $afectadas = $quitados !== [] ? $this->fichasClienteConValoresEn((int) $plantilla['id'], $quitados) : 0;

        ($this->log)(sprintf(
            '  %s: se ACTUALIZA%s — campos +%d / -%d; fichas de clientes con datos en campos quitados: %d',
            $codigo,
            $this->aplicar ? '' : ' (simulación)',
            count($agregados),
            count($quitados),
            $afectadas,
        ));
        if ($quitados !== []) {
            ($this->log)('      identificadores que ya no existen: ' . implode(', ', array_slice($quitados, 0, 12)) . (count($quitados) > 12 ? ' …' : ''));
        }
        $this->resumen['estructuras_actualizadas']++;

        if ($this->aplicar) {
            $this->respaldar($codigo . '.json', $actualTexto);
            $this->db->table('archivos')->where('id', $archivo['id'])->update(['contenido_json' => json_encode($nuevo, self::FLAGS_JSON)]);
        }
    }

    private function sincronizarEjemplo(array $plantilla, array $e): void
    {
        $existente = $this->db->table('ejemplos')
            ->where('plantilla_id', $plantilla['id'])
            ->where('nombre', $e['nombre'])
            ->where('propietario_usuario_id IS NULL', null, false)
            ->orderBy('id', 'ASC')
            ->get()->getRowArray();

        $esReferencia = (bool) ($e['esReferenciaIA'] ?? false);
        $ahora        = date('Y-m-d H:i:s');
        $etiqueta     = "  {$plantilla['codigo']} · {$e['nombre']}";

        if ($existente === null) {
            ($this->log)("{$etiqueta}: se CREA" . ($esReferencia ? ' (referencia IA)' : '') . ($this->aplicar ? '' : ' (simulación)') . '.');
            $this->resumen['ejemplos_creados']++;
            if (! $this->aplicar) {
                return;
            }
            $this->db->table('ejemplos')->insert([
                'plantilla_id'           => $plantilla['id'],
                'nombre'                 => $e['nombre'],
                'subtitulo'              => $e['subtitulo'] ?? null,
                'detalle'                => $e['detalle'] ?? null,
                'activo'                 => ($e['activo'] ?? true) ? 1 : 0,
                'propietario_usuario_id' => null,
                'creado_por_usuario_id'  => null,
                'compartida'             => ($e['compartida'] ?? false) ? 1 : 0,
                'estado'                 => $e['estado'] ?? 'archivado',
                'fuente_verdad_texto'    => $e['fuenteVerdadTexto'] ?? null,
                'es_referencia_ia'       => 0,
                'created_at'             => $ahora,
                'updated_at'             => $ahora,
            ]);
            $ejemploId = (int) $this->db->insertID();
            foreach ($e['tipologiasIoarr'] ?? [] as $tipologia) {
                $this->db->table('ejemplo_tipologia_ioarr')->ignore(true)->insert(['ejemplo_id' => $ejemploId, 'tipologia' => $tipologia]);
            }
            if (($e['contenidoJson'] ?? null) !== null) {
                $this->db->table('archivos')->insert([
                    'propietario_tipo' => 'ejemplo',
                    'ejemplo_id'       => $ejemploId,
                    'nombre'           => $e['nombre'],
                    'url'              => '',
                    'contenido_json'   => json_encode($e['contenidoJson'], self::FLAGS_JSON),
                    'fecha_subida'     => $ahora,
                ]);
            }
            if ($esReferencia) {
                $this->marcarReferencia((int) $plantilla['id'], $ejemploId);
            }

            return;
        }

        if (! $esReferencia) {
            return; // ejemplos del admin que no son la referencia IA: no se pisan
        }

        $archivoActual = $this->db->table('archivos')->where('ejemplo_id', $existente['id'])->get()->getRowArray();
        $actual        = $archivoActual !== null && $archivoActual['contenido_json'] !== null ? json_decode((string) $archivoActual['contenido_json'], true) : null;
        $igual         = ($e['contenidoJson'] ?? null) === null
            || ($actual !== null && json_encode($actual, self::FLAGS_JSON) === json_encode($e['contenidoJson'], self::FLAGS_JSON));
        if ($igual && (int) $existente['es_referencia_ia'] === 1) {
            return; // ya está al día
        }

        ($this->log)("{$etiqueta}: se ACTUALIZA (referencia IA)" . ($this->aplicar ? '' : ' (simulación)') . '.');
        $this->resumen['ejemplos_actualizados']++;
        if (! $this->aplicar) {
            return;
        }
        $ejemploId = (int) $existente['id'];
        $this->db->table('ejemplos')->where('id', $ejemploId)->update([
            'subtitulo'  => $e['subtitulo'] ?? null,
            'detalle'    => $e['detalle'] ?? null,
            'updated_at' => $ahora,
        ]);
        if (($e['contenidoJson'] ?? null) !== null) {
            $nuevo   = json_encode($e['contenidoJson'], self::FLAGS_JSON);
            $archivo = $this->db->table('archivos')->where('ejemplo_id', $ejemploId)->get()->getRowArray();
            if ($archivo === null) {
                $this->db->table('archivos')->insert([
                    'propietario_tipo' => 'ejemplo',
                    'ejemplo_id'       => $ejemploId,
                    'nombre'           => $e['nombre'],
                    'url'              => '',
                    'contenido_json'   => $nuevo,
                    'fecha_subida'     => $ahora,
                ]);
            } else {
                $this->respaldar("ejemplo-{$ejemploId}.json", $archivo['contenido_json']);
                $this->db->table('archivos')->where('id', $archivo['id'])->update(['contenido_json' => $nuevo]);
            }
        }
        $this->marcarReferencia((int) $plantilla['id'], $ejemploId);
    }

    /** A lo más una referencia IA por plantilla (ver EjemplosController::marcarReferenciaIA). */
    private function marcarReferencia(int $plantillaId, int $ejemploId): void
    {
        $this->db->table('ejemplos')->where('plantilla_id', $plantillaId)->where('id !=', $ejemploId)->update(['es_referencia_ia' => 0]);
        $this->db->table('ejemplos')->where('id', $ejemploId)->update(['es_referencia_ia' => 1]);
    }

    /** @return list<string> identificadores no vacíos de todos los campos de una estructura. */
    private function identificadores(array $nodo): array
    {
        $ids = [];
        $this->recolectarIdentificadores($nodo, $ids);

        return array_keys($ids);
    }

    /** @param array<string,true> $ids */
    private function recolectarIdentificadores(array $nodo, array &$ids): void
    {
        if (isset($nodo['identificador']) && is_string($nodo['identificador']) && $nodo['identificador'] !== '') {
            $ids[$nodo['identificador']] = true;
        }
        foreach ($nodo as $hijo) {
            if (is_array($hijo)) {
                $this->recolectarIdentificadores($hijo, $ids);
            }
        }
    }

    /** @param list<string> $identificadores */
    private function fichasClienteConValoresEn(int $plantillaId, array $identificadores): int
    {
        $filas = $this->db->table('ejemplos e')
            ->select('a.contenido_json')
            ->join('archivos a', 'a.ejemplo_id = e.id')
            ->where('e.plantilla_id', $plantillaId)
            ->where('e.propietario_usuario_id IS NOT NULL', null, false)
            ->get()->getResultArray();

        $buscados = array_flip($identificadores);
        $cuenta   = 0;
        foreach ($filas as $f) {
            $valores = json_decode((string) $f['contenido_json'], true)['valores'] ?? [];
            foreach ($valores as $id => $valor) {
                if (isset($buscados[$id]) && $valor !== '' && $valor !== null && $valor !== '[]') {
                    $cuenta++;

                    break;
                }
            }
        }

        return $cuenta;
    }

    private function respaldar(string $nombre, ?string $contenido): void
    {
        if ($contenido === null) {
            return;
        }
        if (! is_dir($this->carpetaBackup)) {
            mkdir($this->carpetaBackup, 0775, true);
        }
        file_put_contents($this->carpetaBackup . '/' . $nombre, $contenido);
    }
}
