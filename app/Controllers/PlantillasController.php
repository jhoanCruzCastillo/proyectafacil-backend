<?php

namespace App\Controllers;

use App\Models\ArchivoModel;
use App\Models\PlantillaModel;
use CodeIgniter\HTTP\ResponseInterface;

// Espejo de `Plantilla` en frontend/src/types/index.ts. `secciones` ya no vive en tablas propias
// (ver "Cambio de diseño v2" en docs/database-design.md) — se lee/escribe como JSON opaco dentro de
// `archivos.contenido_json`, en la fila que `plantillas.asignado_archivo_id` señala. Si la plantilla
// no tiene un archivo asignado todavía, `secciones` es un array vacío: es el estado real de una
// plantilla nueva, no un stub.
class PlantillasController extends BaseController
{
    private array $tipologias = ['optimizacion', 'ampliacion_marginal', 'reposicion', 'rehabilitacion'];

    public function index(): ResponseInterface
    {
        $filas = (new PlantillaModel())->orderBy('id')->findAll();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function listBySector($sectorId = null): ResponseInterface
    {
        $filas = (new PlantillaModel())->where('sector_id', $sectorId)->orderBy('id')->findAll();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function show($id = null): ResponseInterface
    {
        $fila = (new PlantillaModel())->find($id);
        if (! $fila) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Plantilla no encontrada']);
        }

        return $this->response->setJSON($this->toDto($fila));
    }

    public function create(): ResponseInterface
    {
        $dto = $this->request->getJSON(true) ?? [];
        $model = new PlantillaModel();
        $id = $model->insert($this->fromDto($dto), true);
        $this->sincronizarTipologias((int) $id, $dto['tipologiasIoarr'] ?? []);

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    public function update($id = null): ResponseInterface
    {
        $model = new PlantillaModel();
        $fila = $model->find($id);
        if (! $fila) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Plantilla no encontrada']);
        }

        $dto = $this->request->getJSON(true) ?? [];

        if (array_key_exists('secciones', $dto)) {
            if (! $fila['asignado_archivo_id']) {
                return $this->response->setStatusCode(400)->setJSON([
                    'error' => 'La plantilla no tiene un Excel asignado — asígnalo antes de editar su estructura',
                ]);
            }
            (new ArchivoModel())->update($fila['asignado_archivo_id'], [
                'contenido_json' => json_encode(['secciones' => $dto['secciones']], JSON_UNESCAPED_UNICODE),
            ]);
        }

        $cambios = $this->fromDto($dto, soloProvistos: true);
        if ($cambios !== []) {
            $model->update($id, $cambios);
        }

        if (array_key_exists('tipologiasIoarr', $dto)) {
            $this->sincronizarTipologias((int) $id, $dto['tipologiasIoarr'] ?? []);
        }

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    public function delete($id = null): ResponseInterface
    {
        (new PlantillaModel())->delete($id);

        return $this->response->setJSON((object) []);
    }

    private function toDto(array $fila): array
    {
        $db = db_connect();

        $tipologias = $db->table('plantilla_tipologia_ioarr')
            ->select('tipologia')
            ->where('plantilla_id', $fila['id'])
            ->get()
            ->getResultArray();

        $cantidadEjemplos = $db->table('ejemplos')->where('plantilla_id', $fila['id'])->countAllResults();

        $secciones = [];
        if ($fila['asignado_archivo_id']) {
            $archivo = (new ArchivoModel())->find($fila['asignado_archivo_id']);
            if ($archivo && $archivo['contenido_json']) {
                $contenido = json_decode((string) $archivo['contenido_json'], true);
                $secciones = $contenido['secciones'] ?? [];
            }
        }

        return [
            'id'                  => (string) $fila['id'],
            'codigo'              => $fila['codigo'],
            'nombre'              => $fila['nombre'],
            'descripcion'         => $fila['descripcion'],
            'sectorId'            => (string) $fila['sector_id'],
            'instrumento'         => $fila['instrumento'],
            'tipologiasIoarr'     => array_map(static fn (array $t) => $t['tipologia'], $tipologias),
            'cantidadSecciones'   => count($secciones),
            'cantidadEjemplos'    => $cantidadEjemplos,
            'fechaActualizacion'  => $fila['fecha_actualizacion'],
            'secciones'           => $secciones,
            'archivoDefaultUrl'   => $fila['archivo_default_url'],
            'disponibleNivel0'    => (bool) $fila['disponible_nivel0'],
            'estado'              => $fila['estado'],
        ];
    }

    private function fromDto(array $dto, bool $soloProvistos = false): array
    {
        $mapa = [
            'sectorId'           => 'sector_id',
            'codigo'             => 'codigo',
            'nombre'             => 'nombre',
            'descripcion'        => 'descripcion',
            'instrumento'        => 'instrumento',
            'fechaActualizacion' => 'fecha_actualizacion',
            'archivoDefaultUrl'  => 'archivo_default_url',
            'disponibleNivel0'   => 'disponible_nivel0',
            'estado'             => 'estado',
        ];

        $fila = [];
        foreach ($mapa as $claveDto => $columna) {
            if ($soloProvistos && ! array_key_exists($claveDto, $dto)) {
                continue;
            }
            $valor = $dto[$claveDto] ?? null;
            if ($columna === 'disponible_nivel0') {
                $valor = (int) (bool) $valor;
            }
            if ($columna === 'fecha_actualizacion' && is_string($valor)) {
                $valor = $this->normalizarFecha($valor);
            }
            $fila[$columna] = $valor;
        }
        if (! $soloProvistos && ! isset($fila['fecha_actualizacion'])) {
            $fila['fecha_actualizacion'] = date('Y-m-d');
        }
        // Nace archivado por defecto — el admin decide explícitamente cuándo publicarla.
        if (! $soloProvistos && ! isset($fila['estado'])) {
            $fila['estado'] = 'archivado';
        }

        return $fila;
    }

    private function sincronizarTipologias(int $plantillaId, array $tipologias): void
    {
        $db = db_connect();
        $db->table('plantilla_tipologia_ioarr')->where('plantilla_id', $plantillaId)->delete();

        foreach (array_intersect($tipologias, $this->tipologias) as $tipologia) {
            $db->table('plantilla_tipologia_ioarr')->insert([
                'plantilla_id' => $plantillaId,
                'tipologia'    => $tipologia,
            ]);
        }
    }

    // El frontend manda a veces `d/m/Y` (Date.toLocaleDateString('es-PE')); la columna es DATE (`Y-m-d`).
    // Sin esto PostgreSQL rechaza p.ej. "14/8/2026" y el PUT entero falla — también bloqueando el
    // guardado de valores del ejemplo que venía después en el mismo flujo del editor.
    private function normalizarFecha(string $valor): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $valor)) {
            return substr($valor, 0, 10);
        }
        $partes = explode('/', $valor);
        if (count($partes) === 3) {
            [$d, $m, $y] = $partes;

            return sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
        }

        return $valor;
    }
}
