<?php

namespace App\Controllers;

use App\Models\SesionMentoriaModel;
use CodeIgniter\HTTP\ResponseInterface;

// Espejo de `SesionMentoria`/`PreguntaMentoria` en frontend/src/types/index.ts. Solo expone lo que
// `MentoriasApi` necesita (list/inscribirse/enviarPregunta) — no hay creación de sesiones desde el
// frontend todavía, se siembran vía MentoriasSeeder.
class MentoriasController extends BaseController
{
    public function index(): ResponseInterface
    {
        $filas = (new SesionMentoriaModel())->orderBy('fecha', 'ASC')->findAll();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    public function inscribirse($sesionId = null): ResponseInterface
    {
        $sesionId = (int) $sesionId;
        $model    = new SesionMentoriaModel();
        $sesion   = $model->find($sesionId);
        if (! $sesion) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Sesión no encontrada']);
        }

        $dto        = $this->request->getJSON(true) ?? [];
        $cuentaId   = (int) ($dto['cuentaId'] ?? 0);
        $db         = db_connect();
        $inscritos  = $db->table('mentoria_inscripciones')->where('sesion_id', $sesionId)->countAllResults();
        $yaInscrito = $cuentaId > 0 && $db->table('mentoria_inscripciones')
            ->where('sesion_id', $sesionId)->where('usuario_id', $cuentaId)->countAllResults() > 0;

        if ($cuentaId > 0 && ! $yaInscrito && $inscritos < (int) $sesion['cupos_totales']) {
            $db->table('mentoria_inscripciones')->insert([
                'sesion_id'         => $sesionId,
                'usuario_id'        => $cuentaId,
                'fecha_inscripcion' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->response->setJSON($this->toDto($sesion));
    }

    public function enviarPregunta($sesionId = null): ResponseInterface
    {
        $sesionId = (int) $sesionId;
        $model    = new SesionMentoriaModel();
        $sesion   = $model->find($sesionId);
        if (! $sesion) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Sesión no encontrada']);
        }

        $dto = $this->request->getJSON(true) ?? [];
        db_connect()->table('preguntas_mentoria')->insert([
            'sesion_id'      => $sesionId,
            'usuario_id'     => (int) ($dto['usuarioId'] ?? 0),
            'pregunta'       => (string) ($dto['pregunta'] ?? ''),
            'fecha_pregunta' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON($this->toDto($sesion));
    }

    private function toDto(array $fila): array
    {
        $db = db_connect();

        $inscritos = $db->table('mentoria_inscripciones')
            ->select('usuario_id')
            ->where('sesion_id', $fila['id'])
            ->get()->getResultArray();

        $preguntas = $db->table('preguntas_mentoria')
            ->select('id, usuario_id, pregunta, fecha_pregunta, respuesta, fecha_respuesta')
            ->where('sesion_id', $fila['id'])
            ->orderBy('fecha_pregunta', 'ASC')
            ->get()->getResultArray();

        return [
            'id'           => (string) $fila['id'],
            'tema'         => $fila['tema'],
            'mentor'       => $fila['mentor'],
            'fechaISO'     => $this->datetimeAIso($fila['fecha']),
            'cuposTotales' => (int) $fila['cupos_totales'],
            'inscritos'    => array_map(static fn (array $i) => (string) $i['usuario_id'], $inscritos),
            'linkReunion'  => $fila['link_reunion'],
            'grabacionUrl' => $fila['grabacion_url'],
            'preguntas'    => array_map(static fn (array $p) => [
                'id'             => (string) $p['id'],
                'usuarioId'      => (string) $p['usuario_id'],
                'pregunta'       => $p['pregunta'],
                'fechaPregunta'  => $p['fecha_pregunta'] !== null ? str_replace(' ', 'T', $p['fecha_pregunta']) : null,
                'respuesta'      => $p['respuesta'],
                'fechaRespuesta' => $p['fecha_respuesta'] !== null ? str_replace(' ', 'T', $p['fecha_respuesta']) : null,
            ], $preguntas),
        ];
    }

    private function datetimeAIso(string $valor): string
    {
        return str_replace(' ', 'T', $valor);
    }
}
