<?php

namespace App\Controllers;

use App\Models\ArchivoModel;
use App\Models\EjemploModel;
use CodeIgniter\HTTP\ResponseInterface;

// Espejo de `Ejemplo` en frontend/src/types/index.ts. `valores` ya no es una columna propia (ni la
// vieja valores_campo de v1) — vive en archivos.contenido_json, en la fila propietario_tipo='ejemplo'
// asociada a este ejemplo (creada aparte por ExcelEjemplosController cuando se copia el Excel). Si
// el ejemplo todavía no tiene esa fila (por ejemplo recién creado, antes de copiar el Excel),
// `valores` es `{}` y los intentos de guardarlo se ignoran silenciosamente — así es como ya se
// comporta hoy el flujo real (usePlantillaEditor.ts: se crea el ejemplo con valores:{} y recién
// después se copia el Excel).
class EjemplosController extends BaseController
{
    private array $tipologias = ['optimizacion', 'ampliacion_marginal', 'reposicion', 'rehabilitacion'];

    public function index(): ResponseInterface
    {
        $filas = (new EjemploModel())->orderBy('id')->findAll();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function listByPlantilla($plantillaId = null): ResponseInterface
    {
        $filas = (new EjemploModel())->where('plantilla_id', $plantillaId)->orderBy('id')->findAll();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function show($id = null): ResponseInterface
    {
        $fila = (new EjemploModel())->find($id);
        if (! $fila) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ejemplo no encontrado']);
        }

        return $this->response->setJSON($this->toDto($fila));
    }

    public function create(): ResponseInterface
    {
        $dto = $this->request->getJSON(true) ?? [];
        $model = new EjemploModel();
        $id = $model->insert($this->fromDto($dto), true);
        $this->sincronizarTipologias((int) $id, $dto['tipologiasIoarr'] ?? []);

        return $this->response->setJSON($this->toDto($model->find($id)));
    }

    public function update($id = null): ResponseInterface
    {
        $model = new EjemploModel();
        $fila = $model->find($id);
        if (! $fila) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ejemplo no encontrado']);
        }

        $dto = $this->request->getJSON(true) ?? [];

        if (array_key_exists('valores', $dto)) {
            $archivo = (new ArchivoModel())->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $id)->first();
            if ($archivo) {
                (new ArchivoModel())->update($archivo['id'], [
                    'contenido_json' => json_encode(['valores' => $dto['valores']], JSON_UNESCAPED_UNICODE),
                ]);
            }
            // Sin archivo todavía: se ignora (ver nota de clase) — no es un error del cliente.
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
        (new EjemploModel())->delete($id);

        return $this->response->setJSON((object) []);
    }

    private function toDto(array $fila): array
    {
        $db = db_connect();

        $tipologias = $db->table('ejemplo_tipologia_ioarr')
            ->select('tipologia')
            ->where('ejemplo_id', $fila['id'])
            ->get()
            ->getResultArray();

        $archivo = (new ArchivoModel())->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $fila['id'])->first();
        $valores = [];
        if ($archivo && $archivo['contenido_json']) {
            $contenido = json_decode((string) $archivo['contenido_json'], true);
            $valores = $contenido['valores'] ?? [];
        }

        return [
            'id'                => (string) $fila['id'],
            'nombre'            => $fila['nombre'],
            'subtitulo'         => $fila['subtitulo'],
            'detalle'           => $fila['detalle'],
            'plantillaId'       => (string) $fila['plantilla_id'],
            'activo'            => (bool) $fila['activo'],
            // (object) fuerza `{}` en vez de `[]` cuando está vacío — Ejemplo.valores es
            // Record<string,string> en el frontend, no un array.
            'valores'           => (object) $valores,
            'tipologiasIoarr'   => array_map(static fn (array $t) => $t['tipologia'], $tipologias),
            'propietarioId'     => $fila['propietario_usuario_id'] !== null ? (string) $fila['propietario_usuario_id'] : null,
            'creadoPorUsuarioId' => $fila['creado_por_usuario_id'] !== null ? (string) $fila['creado_por_usuario_id'] : null,
            'compartida'        => (bool) $fila['compartida'],
            'estado'            => $fila['estado'],
        ];
    }

    private function fromDto(array $dto, bool $soloProvistos = false): array
    {
        $mapa = [
            'plantillaId'        => 'plantilla_id',
            'nombre'             => 'nombre',
            'subtitulo'          => 'subtitulo',
            'detalle'            => 'detalle',
            'activo'             => 'activo',
            'propietarioId'      => 'propietario_usuario_id',
            'creadoPorUsuarioId' => 'creado_por_usuario_id',
            'compartida'         => 'compartida',
            'estado'             => 'estado',
        ];

        $fila = [];
        foreach ($mapa as $claveDto => $columna) {
            if ($soloProvistos && ! array_key_exists($claveDto, $dto)) {
                continue;
            }
            $valor = $dto[$claveDto] ?? null;
            if (in_array($columna, ['activo', 'compartida'], true)) {
                $valor = (int) (bool) $valor;
            }
            $fila[$columna] = $valor;
        }
        // Nace archivado por defecto — el admin decide explícitamente cuándo publicarlo.
        if (! $soloProvistos && ! isset($fila['estado'])) {
            $fila['estado'] = 'archivado';
        }

        return $fila;
    }

    private function sincronizarTipologias(int $ejemploId, array $tipologias): void
    {
        $db = db_connect();
        $db->table('ejemplo_tipologia_ioarr')->where('ejemplo_id', $ejemploId)->delete();

        foreach (array_intersect($tipologias, $this->tipologias) as $tipologia) {
            $db->table('ejemplo_tipologia_ioarr')->insert([
                'ejemplo_id' => $ejemploId,
                'tipologia'  => $tipologia,
            ]);
        }
    }
}
