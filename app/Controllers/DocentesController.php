<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Lista de docentes (rol='docente', activos) con su horario semanal de referencia — usada por el
// cliente para elegir a quién solicitarle asesoría — y el endpoint con el que un docente reemplaza
// su propio horario completo. `dia_semana` es 1=lunes .. 7=domingo.
class DocentesController extends BaseController
{
    public function index(): ResponseInterface
    {
        $db = db_connect();

        $docentes = $db->table('usuarios')
            ->select('id, nombre')
            ->where('rol', 'docente')
            ->where('estado', 'activo')
            ->orderBy('nombre', 'ASC')
            ->get()->getResultArray();

        $horarios = $db->table('horarios_docente')
            ->select('id, usuario_id, dia_semana, hora_inicio, hora_fin')
            ->orderBy('dia_semana', 'ASC')
            ->orderBy('hora_inicio', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map(
            fn (array $d) => $this->toDtoDocente($d, $horarios),
            $docentes,
        ));
    }

    public function actualizarHorario($docenteId = null): ResponseInterface
    {
        $docenteId = (int) $docenteId;
        $dto       = $this->request->getJSON(true) ?? [];
        $bloques   = is_array($dto['horario'] ?? null) ? $dto['horario'] : [];

        $db = db_connect();
        $db->transStart();
        $db->table('horarios_docente')->where('usuario_id', $docenteId)->delete();
        foreach ($bloques as $b) {
            $db->table('horarios_docente')->insert([
                'usuario_id'  => $docenteId,
                'dia_semana'  => (int) ($b['diaSemana'] ?? 1),
                'hora_inicio' => $this->conSegundos((string) ($b['horaInicio'] ?? '00:00')),
                'hora_fin'    => $this->conSegundos((string) ($b['horaFin'] ?? '00:00')),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();

        return $this->response->setJSON($this->toDtoHorario($docenteId));
    }

    private function toDtoDocente(array $d, array $todosHorarios): array
    {
        $propios = array_values(array_filter($todosHorarios, static fn (array $h) => (int) $h['usuario_id'] === (int) $d['id']));

        return [
            'id'      => (string) $d['id'],
            'nombre'  => $d['nombre'],
            'horario' => array_map([$this, 'toDtoBloque'], $propios),
        ];
    }

    private function toDtoHorario(int $docenteId): array
    {
        $filas = db_connect()->table('horarios_docente')
            ->where('usuario_id', $docenteId)
            ->orderBy('dia_semana', 'ASC')->orderBy('hora_inicio', 'ASC')
            ->get()->getResultArray();

        return array_map([$this, 'toDtoBloque'], $filas);
    }

    private function toDtoBloque(array $h): array
    {
        return [
            'id'         => (string) $h['id'],
            'diaSemana'  => (int) $h['dia_semana'],
            'horaInicio' => substr((string) $h['hora_inicio'], 0, 5),
            'horaFin'    => substr((string) $h['hora_fin'], 0, 5),
        ];
    }

    private function conSegundos(string $horaHM): string
    {
        return strlen($horaHM) === 5 ? "{$horaHM}:00" : $horaHM;
    }
}
