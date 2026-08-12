<?php

namespace App\Controllers;

use App\Models\ArchivoModel;
use App\Models\EjemploModel;
use CodeIgniter\HTTP\ResponseInterface;

// Llenado automático de una ficha completa con IA: el cliente carga su "fuente de la verdad"
// (FuenteVerdadController) y esto la combina con los Contextos IA (ContextosIAController — el CÓMO
// llenar cada sección) para proponer valores, sección por sección, y REEMPLAZAR por completo los
// valores actuales del ejemplo (se limpia y se vuelve a llenar, nunca se mezcla con lo que hubiera).
//
// Campos fuera de alcance (ver contexto global "Reglas de llenado automático con IA"):
//  - `calculado`: el Excel los resuelve solo.
//  - `tabla` / `tabla_jerarquica`: estructura demasiado específica para generarla a ciegas.
//  - `imagen` / `firma`: no son datos de texto.
class LlenadoIAController extends BaseController
{
    // 'nota' no es un dato real (texto informativo intercalado), 'mapa_coordenadas' guarda un objeto
    // {lat,lng} en vez de texto plano — ambos quedan fuera igual que tabla/calculado/imagen/firma.
    private const TIPOS_EXCLUIDOS = ['calculado', 'tabla', 'tabla_jerarquica', 'imagen', 'firma', 'mapa_coordenadas', 'nota'];

    public function llenarFicha($ejemploId = null): ResponseInterface
    {
        set_time_limit(180); // 1 sección por petición (el cliente itera); margen para OpenAI + contexto.
        $ejemploId = (int) $ejemploId;

        $config = config('Ia');
        if ($config->openaiApiKey === '') {
            return $this->response->setStatusCode(503)->setJSON(['error' => 'El llenado con IA todavía no está configurado en el servidor.']);
        }

        $db      = db_connect();
        $ejemplo = (new EjemploModel())->find($ejemploId);
        if (! $ejemplo) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ficha no encontrada']);
        }
        $plantillaId = (int) $ejemplo['plantilla_id'];

        $secciones = $this->seccionesDe($plantillaId);
        if ($secciones === []) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Esta ficha todavía no tiene estructura importada']);
        }

        // Filtro opcional: el cliente puede pedir solo algunas secciones para acortar el tiempo.
        $body       = $this->request->getJSON(true) ?? [];
        $filtroIds  = $body['seccionIds'] ?? null;
        $parcial    = false;
        if (is_array($filtroIds) && $filtroIds !== []) {
            $filtroIds = array_map('strval', $filtroIds);
            $secciones = array_values(array_filter(
                $secciones,
                static fn (array $s): bool => in_array((string) ($s['id'] ?? ''), $filtroIds, true),
            ));
            $parcial = true;
            if ($secciones === []) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Ninguna de las secciones indicadas existe en esta ficha']);
            }
        }

        $fuenteVerdad = $this->fuenteDeLaVerdad($ejemploId, $ejemplo['fuente_verdad_texto'] ?? '');
        if (trim($fuenteVerdad) === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Carga al menos un documento o escribe información del proyecto antes de llenar con IA']);
        }

        $reglas    = $this->contenidoGlobalPorNombre('Reglas de llenado automático con IA');
        $generales = $this->contextosGeneralesDe($plantillaId);

        $valoresFinal      = [];
        $estadosFinal      = [];
        $confianzaFinal    = [];
        $resumenPorSeccion = [];
        $idsAfectados      = [];

        foreach ($secciones as $seccion) {
            $campos = $this->camposLlenables($seccion);
            if ($campos === []) {
                continue;
            }

            foreach ($campos as $c) {
                $idsAfectados[(string) $c['identificador']] = true;
            }

            $contextoSeccion = $this->contextoDeSeccion($plantillaId, (string) $seccion['id']);
            $sistema = $this->construirSistema($reglas, $generales, $contextoSeccion, $fuenteVerdad);
            $usuario = $this->construirPromptSeccion((string) $seccion['id'], (string) $seccion['nombre'], $campos);

            $propuesta = $this->llamarOpenAIJson($config, $sistema, $usuario);
            $aceptados = 0;
            if ($propuesta !== null) {
                $idsValidos = array_column($campos, 'identificador');
                foreach ($propuesta['valores'] as $identificador => $texto) {
                    if (! in_array($identificador, $idsValidos, true)) {
                        continue; // la IA no puede inventar identificadores que no le pasamos
                    }
                    $texto = trim((string) $texto);
                    if ($texto === '') {
                        continue;
                    }
                    $valoresFinal[$identificador] = $texto;
                    if (isset($propuesta['estados'][$identificador])) {
                        $estadosFinal[$identificador] = $propuesta['estados'][$identificador];
                    }
                    if (isset($propuesta['confianza'][$identificador])) {
                        $confianzaFinal[$identificador] = $propuesta['confianza'][$identificador];
                    }
                    $aceptados++;
                }
            }

            $resumenPorSeccion[] = [
                'seccionId' => (string) $seccion['id'],
                'nombre'    => (string) $seccion['nombre'],
                'campos'    => count($campos),
                'llenados'  => $aceptados,
            ];
        }

        $this->guardarValores($ejemploId, $valoresFinal, $parcial ? array_keys($idsAfectados) : null);

        return $this->response->setJSON([
            'valores'    => (object) $valoresFinal,
            'estados'    => (object) $estadosFinal,
            'confianza'  => (object) $confianzaFinal,
            'secciones'  => $resumenPorSeccion,
        ]);
    }

    /** @return array<int,array> secciones crudas (mismo JSON que usa PlantillasController::toDto) */
    private function seccionesDe(int $plantillaId): array
    {
        $plantilla = db_connect()->table('plantillas')->where('id', $plantillaId)->get()->getRowArray();
        if ($plantilla === null || ! $plantilla['asignado_archivo_id']) {
            return [];
        }
        $archivo = (new ArchivoModel())->find($plantilla['asignado_archivo_id']);
        if (! $archivo || ! $archivo['contenido_json']) {
            return [];
        }

        return json_decode((string) $archivo['contenido_json'], true)['secciones'] ?? [];
    }

    /** Campos de tipo texto/número/fecha/catálogo/selección de toda la sección (todas sus subsecciones), aplanados. */
    private function camposLlenables(array $seccion): array
    {
        $campos = [];
        foreach ($seccion['subsecciones'] ?? [] as $sub) {
            foreach ($sub['campos'] ?? [] as $campo) {
                if (in_array($campo['tipo'] ?? '', self::TIPOS_EXCLUIDOS, true)) {
                    continue;
                }
                if (empty($campo['identificador'])) {
                    continue;
                }
                $campos[] = $campo;
            }
        }

        return $campos;
    }

    /** Concatena el texto de todos los documentos de la fuente de la verdad + el texto adicional. */
    private function fuenteDeLaVerdad(int $ejemploId, string $textoAdicional): string
    {
        $archivos = db_connect()->table('fuente_verdad_archivos')->where('ejemplo_id', $ejemploId)->get()->getResultArray();
        $partes   = [];
        foreach ($archivos as $a) {
            $texto = trim((string) ($a['contenido_texto'] ?? ''));
            if ($texto !== '') {
                $partes[] = "--- {$a['nombre']} ---\n{$texto}";
            }
        }
        if (trim($textoAdicional) !== '') {
            $partes[] = "--- Notas adicionales del cliente ---\n" . trim($textoAdicional);
        }

        return implode("\n\n", $partes);
    }

    private function contenidoGlobalPorNombre(string $nombre): string
    {
        $fila = db_connect()->table('contextos_ia_globales')->where('nombre', $nombre)->get()->getRowArray();

        return $fila ? $this->contenidoDeUrl($fila['url'] ?? null) : '';
    }

    /** Contextos generales de la ficha (aplican a todas las secciones) — nombre => texto. */
    private function contextosGeneralesDe(int $plantillaId): array
    {
        $filas = db_connect()->table('contextos_ia_general')->where('plantilla_id', $plantillaId)->get()->getResultArray();
        $out   = [];
        foreach ($filas as $g) {
            $texto = $this->contenidoDeUrl($g['url'] ?? null);
            if ($texto !== '') {
                $out[$g['nombre']] = $texto;
            }
        }

        return $out;
    }

    /** Contexto propio de la sección + sus globales asociados (mismo criterio que AsistenteIAController). */
    private function contextoDeSeccion(int $plantillaId, string $seccionId): array
    {
        $db      = db_connect();
        $partes  = [];
        $fila    = $db->table('contextos_ia_seccion')->where('plantilla_id', $plantillaId)->where('seccion_id', $seccionId)->get()->getRowArray();
        if ($fila === null) {
            return $partes;
        }

        $texto = $this->contenidoDeUrl($fila['url'] ?? null);
        if ($texto !== '') {
            $partes['(instrucciones de esta sección)'] = $texto;
        }

        $globales = $db->table('contexto_seccion_globales sg')
            ->select('g.nombre, g.url')
            ->join('contextos_ia_globales g', 'g.id = sg.contexto_global_id')
            ->where('sg.contexto_seccion_id', (int) $fila['id'])
            ->get()->getResultArray();
        foreach ($globales as $g) {
            $texto = $this->contenidoDeUrl($g['url'] ?? null);
            if ($texto !== '') {
                $partes[$g['nombre']] = $texto;
            }
        }

        return $partes;
    }

    private function construirSistema(string $reglas, array $generales, array $contextoSeccion, string $fuenteVerdad): string
    {
        $partes = [
            'Eres un asistente experto en formulación de proyectos de inversión pública del Perú (Invierte.pe) que llena fichas técnicas oficiales del MEF a partir de la información real de un proyecto.',
            'Respondes SIEMPRE con un único objeto JSON válido (sin markdown). Contrato de salida de ESTA llamada:',
            '{"seccion_id":"<id>","campos":[{"id":"<identificador>","valor_propuesto":"<texto o null>","estado":"extraido|inferido|requiere_confirmacion|no_encontrado|conflictivo","confianza":0.0,"fuente":"...","evidencia":"..."}]}',
            'Usa exactamente los identificadores de campo que te pasan en el mensaje de usuario. No inventes ids.',
            'Si un campo no tiene evidencia en la fuente de la verdad: estado "no_encontrado" y valor_propuesto null. No inventes datos.',
            'Si la evidencia es explícita en los documentos (p. ej. "Nivel de gobierno: Gobierno Local"), marca estado "extraido" y copia el valor.',
        ];
        if ($reglas !== '') {
            $partes[] = "Reglas de llenado automático:\n{$reglas}";
        }
        foreach ($generales as $nombre => $texto) {
            $partes[] = "Contexto general de esta ficha — {$nombre}:\n{$texto}";
        }
        foreach ($contextoSeccion as $nombre => $texto) {
            $partes[] = "Contexto de esta sección — {$nombre}:\n{$texto}";
        }
        $partes[] = "Fuente de la verdad (información real del proyecto, cargada por el cliente):\n{$fuenteVerdad}";

        return implode("\n\n", $partes);
    }

    private function construirPromptSeccion(string $seccionId, string $nombreSeccion, array $campos): string
    {
        $lineas = [
            "Llena los campos de la sección \"{$nombreSeccion}\" (seccion_id: {$seccionId}) según la fuente de la verdad.",
            'Devuelve el JSON con el arreglo "campos" usando los identificadores listados abajo.',
            'Campos disponibles:',
        ];
        foreach ($campos as $c) {
            $linea = "- {$c['identificador']} — \"{$c['etiqueta']}\" (tipo: {$c['tipo']})";
            $opciones = $c['opciones'] ?? null;
            if (is_array($opciones) && $opciones !== []) {
                $linea .= ' — opciones válidas: ' . implode(' | ', $opciones);
            }
            $etiquetasBooleano = $c['etiquetasBooleano'] ?? null;
            if (($c['tipo'] ?? '') === 'booleano' && is_array($etiquetasBooleano)) {
                $linea .= " — responder exactamente \"{$etiquetasBooleano['true']}\" o \"{$etiquetasBooleano['false']}\"";
            }
            if (! empty($c['descripcion'])) {
                $linea .= " — {$c['descripcion']}";
            }
            $lineas[] = $linea;
        }

        return implode("\n", $lineas);
    }

    /**
     * Persiste valores del ejemplo.
     * - Sin $idsAfectados: reemplazo total (llenado de toda la ficha).
     * - Con $idsAfectados: limpia solo esos campos y fusiona los nuevos (llenado parcial por sección).
     *
     * @param list<string>|null $idsAfectados
     */
    private function guardarValores(int $ejemploId, array $valoresNuevos, ?array $idsAfectados): void
    {
        $archivoModel = new ArchivoModel();
        $existente    = $archivoModel->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $ejemploId)->first();

        if ($idsAfectados === null) {
            $valores = $valoresNuevos;
        } else {
            $previos = [];
            if ($existente && ! empty($existente['contenido_json'])) {
                $decoded = json_decode((string) $existente['contenido_json'], true);
                $previos = is_array($decoded['valores'] ?? null) ? $decoded['valores'] : [];
            }
            foreach ($idsAfectados as $id) {
                unset($previos[$id]);
            }
            $valores = array_merge($previos, $valoresNuevos);
        }

        $contenido = json_encode(['valores' => $valores], JSON_UNESCAPED_UNICODE);

        if ($existente) {
            $archivoModel->update($existente['id'], ['contenido_json' => $contenido]);
        } else {
            $archivoModel->insert([
                'propietario_tipo' => 'ejemplo',
                'ejemplo_id'       => $ejemploId,
                'plantilla_id'     => null,
                'nombre'           => '',
                'url'              => '',
                'contenido_json'   => $contenido,
                'fecha_subida'     => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** Descarga el .md de Cloudinary; '' si no hay URL o la descarga falla. */
    private function contenidoDeUrl(?string $url): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $cuerpo = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($cuerpo !== false && $estado >= 200 && $estado < 300) ? trim((string) $cuerpo) : '';
    }

    /**
     * @return array{valores: array<string,string>, estados: array<string,string>, confianza: array<string,float>}|null
     */
    private function llamarOpenAIJson(object $config, string $sistema, string $usuario): ?array
    {
        $ch = curl_init($config->openaiEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'                 => $config->openaiModelo,
                'max_completion_tokens' => 8000,
                'response_format'       => ['type' => 'json_object'],
                'messages'              => [
                    ['role' => 'system', 'content' => $sistema],
                    ['role' => 'user', 'content' => $usuario],
                ],
            ]),
            CURLOPT_TIMEOUT    => 120,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
                'authorization: Bearer ' . $config->openaiApiKey,
            ],
        ]);
        $cuerpo    = curl_exec($ch);
        $estado    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($cuerpo === false || $estado < 200 || $estado >= 300) {
            log_message('error', '[llenado-ia] OpenAI respondió {estado}: {cuerpo} {curl}', [
                'estado' => $estado,
                'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : '(sin cuerpo)',
                'curl'   => $errorCurl,
            ]);

            return null;
        }

        $json    = json_decode((string) $cuerpo, true);
        $texto   = (string) ($json['choices'][0]['message']['content'] ?? '');
        $valores = json_decode($texto, true);

        if (! is_array($valores)) {
            log_message('error', '[llenado-ia] OpenAI devolvió JSON no parseable: {texto}', [
                'texto' => substr($texto, 0, 500),
            ]);

            return null;
        }

        return $this->normalizarPropuesta($valores);
    }

    /**
     * Acepta el formato rico del Prompt del sistema (`campos[]`) y el mapa plano legado.
     *
     * @return array{valores: array<string,string>, estados: array<string,string>, confianza: array<string,float>}
     */
    private function normalizarPropuesta(array $raw): array
    {
        $valores   = [];
        $estados   = [];
        $confianza = [];

        if (isset($raw['campos']) && is_array($raw['campos'])) {
            foreach ($raw['campos'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $id = trim((string) ($item['id'] ?? $item['identificador'] ?? ''));
                if ($id === '') {
                    continue;
                }

                $estadoRaw = strtolower(trim((string) ($item['estado'] ?? '')));
                if (in_array($estadoRaw, ['no_encontrado', 'calculado'], true)) {
                    $estados[$id] = 'no_encontrado';
                    continue;
                }

                $texto = $this->valorPropuestoATexto($item['valor_propuesto'] ?? $item['valor'] ?? null);
                if ($texto === null) {
                    if ($estadoRaw !== '') {
                        $estados[$id] = $this->mapearEstado($estadoRaw);
                    }
                    continue;
                }

                $valores[$id] = $texto;
                $estados[$id] = $this->mapearEstado($estadoRaw !== '' ? $estadoRaw : 'extraido');
                if (isset($item['confianza']) && is_numeric($item['confianza'])) {
                    $confianza[$id] = max(0.0, min(1.0, (float) $item['confianza']));
                }
            }

            return ['valores' => $valores, 'estados' => $estados, 'confianza' => $confianza];
        }

        // Formato plano: { "1.01.01": "Gobierno Local", ... }
        foreach ($raw as $identificador => $valor) {
            if (! is_string($identificador)) {
                continue;
            }
            if (in_array($identificador, ['seccion_id', 'seccionId', 'campos'], true)) {
                continue;
            }
            $texto = $this->valorPropuestoATexto($valor);
            if ($texto === null) {
                continue;
            }
            $valores[$identificador] = $texto;
        }

        return ['valores' => $valores, 'estados' => $estados, 'confianza' => $confianza];
    }

    /** @return 'extraido'|'inferido'|'requiere_confirmacion'|'no_encontrado' */
    private function mapearEstado(string $estado): string
    {
        return match ($estado) {
            'extraido' => 'extraido',
            'inferido' => 'inferido',
            'requiere_confirmacion', 'conflictivo' => 'requiere_confirmacion',
            default => 'no_encontrado',
        };
    }

    private function valorPropuestoATexto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        if (is_bool($valor)) {
            return $valor ? 'true' : 'false';
        }
        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }
        if (is_array($valor) || is_object($valor)) {
            // Tablas/objetos no entran en este flujo de texto plano.
            return null;
        }
        $texto = trim((string) $valor);
        if ($texto === '' || strcasecmp($texto, 'null') === 0) {
            return null;
        }

        return $texto;
    }
}
