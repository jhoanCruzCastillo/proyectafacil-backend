<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Lista de asesores (rol='asesor', activos) con su horario semanal de referencia — usada por el
// cliente para elegir a quién solicitarle asesoría — y el endpoint con el que un asesor reemplaza
// su propio horario completo. `dia_semana` es 1=lunes .. 7=domingo.
class DocentesController extends BaseController
{
    public function index(): ResponseInterface
    {
        $db = db_connect();

        $docentes = $db->table('usuarios')
            ->select('id, nombre, foto_url')
            ->where('rol', 'asesor')
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

    // Gestión de solo lectura para el Administrativo de Asesorías (Módulo 5, docs §5 "06 Docentes"):
    // incluye inactivos (para poder reactivarlos) — activar/desactivar reutiliza PUT /usuarios/:id
    // ya existente (campo 'estado'), no hace falta un endpoint nuevo para esa acción.
    public function indexAdmin(): ResponseInterface
    {
        $db = db_connect();

        $docentes = $db->table('usuarios')
            ->select('id, nombre, correo, foto_url, disponible, estado')
            ->where('rol', 'asesor')
            ->orderBy('nombre', 'ASC')
            ->get()->getResultArray();

        $especialidades = $db->table('asesor_especialidades ae')
            ->select('ae.usuario_id, s.id as sector_id, s.nombre as sector_nombre')
            ->join('sectores s', 's.id = ae.sector_id')
            ->get()->getResultArray();

        $inicioMes = date('Y-m-01 00:00:00');
        $consultas = $db->table('solicitudes_asesoria')
            ->select('docente_id, COUNT(*) as total')
            ->where('estado', 'completado')
            ->where('updated_at >=', $inicioMes)
            ->groupBy('docente_id')
            ->get()->getResultArray();
        $consultasPorDocente = [];
        foreach ($consultas as $c) {
            $consultasPorDocente[(int) $c['docente_id']] = (int) $c['total'];
        }

        return $this->response->setJSON(array_map(function (array $d) use ($especialidades, $consultasPorDocente) {
            $propias = array_values(array_filter($especialidades, static fn (array $e) => (int) $e['usuario_id'] === (int) $d['id']));

            return [
                'id'                    => (string) $d['id'],
                'nombre'                => $d['nombre'],
                'correo'                => $d['correo'],
                'fotoUrl'               => $d['foto_url'] ?? null,
                'disponible'            => (bool) $d['disponible'],
                'estado'                => $d['estado'],
                'especialidades'        => array_map(static fn (array $e) => ['id' => (string) $e['sector_id'], 'nombre' => $e['sector_nombre']], $propias),
                'consultasAtendidasMes' => $consultasPorDocente[(int) $d['id']] ?? 0,
            ];
        }, $docentes));
    }

    // Unión de disponibilidad de TODOS los asesores activos (toggle 'disponible'), sin nombres —
    // usada por la grilla de horario del alumno en la solicitud guiada de videollamada (docs
    // §4 Fase 1: "no verás qué docente te atenderá hasta que se confirme tu cita").
    public function disponibilidadAgregada(): ResponseInterface
    {
        $filas = db_connect()->table('horarios_docente hd')
            ->distinct()
            ->select('hd.dia_semana, hd.hora_inicio, hd.hora_fin')
            ->join('usuarios u', 'u.id = hd.usuario_id')
            ->where('u.rol', 'asesor')
            ->where('u.estado', 'activo')
            ->where('u.disponible', 1)
            ->orderBy('hd.dia_semana', 'ASC')
            ->orderBy('hd.hora_inicio', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map(static fn (array $h) => [
            'diaSemana'  => (int) $h['dia_semana'],
            'horaInicio' => substr((string) $h['hora_inicio'], 0, 5),
            'horaFin'    => substr((string) $h['hora_fin'], 0, 5),
        ], $filas));
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
            'fotoUrl' => $d['foto_url'] ?? null,
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
