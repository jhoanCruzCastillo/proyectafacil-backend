<?php

namespace App\Controllers;

use App\Libraries\UbigeoResolver;
use App\Models\ArchivoModel;
use App\Models\EjemploModel;
use App\Models\LoteLlenadoIAModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

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

    /**
     * Tablas que son de solo-fórmula (el Excel las calcula solas) y por lo tanto no se ofrecen para
     * llenado con IA vía llenarTabla() — lista hardcodeada por `plantilla.codigo` porque el JSON de
     * estructura no marca esto en ningún campo (`editable` es true en las 96 tablas de esta ficha,
     * incluidas estas 4). Verificado contra el instructivo MIDIS: 7.02.01/7.03.01/7.05.01/7.06.01 dicen
     * explícitamente "el cálculo se realiza de manera automática"; 7.04.01 NO lo dice (pide identificar
     * escenarios de optimización) y por eso SÍ se ofrece para llenado.
     *
     * 12/13/14.02.1, .02.2, .03.1, .04.1, .05.1 (Evaluación Social, las 3 alternativas — costos
     * sociales, flujo a precios sociales, indicadores de rentabilidad y análisis de sensibilidad):
     * encontrado en vivo (2026-08-19) probando la Alternativa 1 — el JSON las marca `tabla` igual que
     * cualquier otra, así que `llenarTablasFaltantes()` las ofrecía, pero SON fórmulas derivadas de
     * Costos del Proyecto de esa misma alternativa (factor de corrección social, VAC, ratio
     * costo-eficacia, sensibilidad bidimensional) — el propio ejemplo de referencia las trae con todas
     * las celdas hoja vacías después de años de uso, confirmando que nadie las llena a mano nunca.
     * `12/13/14.01.1` (Beneficios) NO se excluye: es una tabla `tabla` real, pero con una lista fija de
     * 5 beneficios ya precargada como `valorEjemplo` — llenarla es inofensivo (a lo sumo re-confirma lo
     * mismo), así que no hace falta bloquearla.
     */
    private const CAMPOS_TABLA_EXCLUIDOS = [
        'FTE-CUIDADO-DIURNO' => [
            '7.02.01', '7.03.01', '7.05.01', '7.06.01',
            '12.02.1', '12.02.2', '12.03.1', '12.04.1', '12.05.1',
            '13.02.1', '13.02.2', '13.03.1', '13.04.1', '13.05.1',
            '14.02.1', '14.02.2', '14.03.1', '14.04.1', '14.05.1',
        ],
    ];

    /**
     * Tablas de ubicación (filas_dinamicas) cuya columna "ubigeo" recibe de la IA un nombre
     * "Departamento | Provincia | Distrito" (ver la `nota` sembrada por
     * MarcarColumnasUbigeoCalculadasSeeder) en vez del código de 6 dígitos — resolverUbigeoEnTabla()
     * lo resuelve de forma determinista contra el catálogo real después de validarFormaTabla().
     */
    private const TABLAS_UBIGEO = ['2.01.01', '2.02.01', '3.03.01'];

    /**
     * Tablas que dependen del Excel vivo del cliente (catálogo en cascada, ver
     * opcionesLlenadoCascada() en el frontend) y por eso quedan FUERA del lote de "Llenar toda la
     * ficha" (ver enviarLoteFicha) — un lote server-side no tiene forma de leer el Excel del
     * navegador ni de ver el borrador de la tabla anterior antes de pedir la siguiente. El cliente las
     * sigue llenando de una en una, síncrono, en cuanto el lote termina.
     */
    private const TABLAS_CASCADA_FUERA_DE_LOTE = [
        'FTE-CUIDADO-DIURNO' => ['5.01.02', '5.02.02', '5.02.04'],
    ];

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

        $reglas     = $this->contenidoGlobalPorNombre('Reglas de llenado automático con IA');
        $generales  = $this->contextosGeneralesDe($plantillaId);
        $referencia = $this->valoresEjemploReferencia($plantillaId);
        // Snapshot de lo que YA está confirmado en la ficha (de una corrida anterior, o de secciones
        // ya llenadas a mano). Se va ampliando con lo que este MISMO run acepta sección a sección, así
        // que si en un solo lote se llenan varias secciones seguidas, la última ve lo que propusieron
        // las anteriores — no solo lo que ya estaba antes de apretar el botón.
        $yaConfirmados = $this->valoresYaConfirmados($ejemploId);

        $valoresFinal      = [];
        $estadosFinal      = [];
        $confianzaFinal    = [];
        $fuentesFinal      = [];
        $resumenPorSeccion = [];
        $idsAfectados      = [];
        $costoTotalUsd     = 0.0;

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
            $idsSeccion = array_column($campos, 'identificador');
            $referenciaSeccion = array_intersect_key($referencia, array_flip($idsSeccion));
            // Nunca los propios campos de esta sección: eso es justo lo que se le está pidiendo
            // proponer, no "ya confirmado en otra sección".
            $otrasSeccionesConfirmadas = array_diff_key($yaConfirmados, array_flip($idsSeccion));
            $usuario = $this->construirPromptSeccion((string) $seccion['id'], (string) $seccion['nombre'], $campos, $referenciaSeccion, $otrasSeccionesConfirmadas);

            $propuesta = $this->llamarModeloJson($config, $sistema, $usuario, 'sección ' . (string) $seccion['id']);
            $aceptados = 0;
            $costoSeccionUsd = 0.0;
            if ($propuesta !== null) {
                $costoSeccionUsd = $propuesta['costoUsd'] ?? 0.0;
                $costoTotalUsd  += $costoSeccionUsd;
                $idsValidos          = array_column($campos, 'identificador');
                $opcionesPorIdentif  = array_column(
                    array_filter($campos, static fn (array $c): bool => is_array($c['opciones'] ?? null) && $c['opciones'] !== []),
                    'opciones',
                    'identificador',
                );
                foreach ($propuesta['valores'] as $identificador => $texto) {
                    if (! in_array($identificador, $idsValidos, true)) {
                        continue; // la IA no puede inventar identificadores que no le pasamos
                    }
                    $texto = trim((string) $texto);
                    if ($texto === '') {
                        continue;
                    }
                    // Campo con catálogo cerrado: la IA debe copiar el texto EXACTO. Si "casi acertó"
                    // (mayúscula, acento) se normaliza al texto canónico; si no matchea ninguna opción,
                    // se descarta en vez de guardar un valor que rompería el VLOOKUP del Excel en silencio.
                    $opciones = $opcionesPorIdentif[$identificador] ?? null;
                    if (is_array($opciones)) {
                        $normalizado = $this->normalizarOpcion($texto);
                        $match       = null;
                        foreach ($opciones as $opcion) {
                            if ($this->normalizarOpcion((string) $opcion) === $normalizado) {
                                $match = (string) $opcion;
                                break;
                            }
                        }
                        if ($match === null) {
                            log_message('warning', '[llenado-ia] {id}: "{texto}" no calza con ninguna opción del catálogo ({opciones}) — descartado.', [
                                'id' => $identificador, 'texto' => $texto, 'opciones' => implode(' | ', $opciones),
                            ]);
                            continue;
                        }
                        $texto = $match;
                    }
                    $valoresFinal[$identificador] = $texto;
                    $yaConfirmados[$identificador] = $texto; // visible para las secciones siguientes de este mismo run
                    if (isset($propuesta['estados'][$identificador])) {
                        $estadosFinal[$identificador] = $propuesta['estados'][$identificador];
                    }
                    if (isset($propuesta['confianza'][$identificador])) {
                        $confianzaFinal[$identificador] = $propuesta['confianza'][$identificador];
                    }
                    if (isset($propuesta['fuentes'][$identificador]) && $propuesta['fuentes'][$identificador] !== '') {
                        $fuentesFinal[$identificador] = $propuesta['fuentes'][$identificador];
                    }
                    $aceptados++;
                }
            }

            $resumenPorSeccion[] = [
                'seccionId' => (string) $seccion['id'],
                'nombre'    => (string) $seccion['nombre'],
                'campos'    => count($campos),
                'llenados'  => $aceptados,
                'costoUsd'  => round($costoSeccionUsd, 5),
            ];
        }

        $this->guardarValores($ejemploId, $valoresFinal, $fuentesFinal, $parcial ? array_keys($idsAfectados) : null);

        return $this->response->setJSON([
            'valores'       => (object) $valoresFinal,
            'estados'       => (object) $estadosFinal,
            'confianza'     => (object) $confianzaFinal,
            'fuentes'       => (object) $fuentesFinal,
            'secciones'     => $resumenPorSeccion,
            'costoTotalUsd' => round($costoTotalUsd, 5),
        ]);
    }

    /**
     * Llena UNA tabla con IA (a diferencia de llenarFicha, que llena toda una sección de campos de
     * texto). No persiste nada: solo propone un valor con la misma forma que el actual, para que el
     * cliente lo revise en el flujo de borrador/confirmar que ya usa ExampleTableEditor.
     */
    public function llenarTabla($ejemploId = null): ResponseInterface
    {
        // El margen de PHP debe superar el timeout del curl a OpenAI (170s, ver la llamada a
        // llamarOpenAICrudo() más abajo) con margen: con set_time_limit(60) == timeout de curl, tablas
        // grandes/lentas hacían que PHP matara el script ANTES de que curl devolviera null con gracia
        // — eso salía como HTTP 500 crudo en vez de un 502 "no se pudo consultar", confirmado en logs
        // reales (ErrorException: Maximum execution time of 60 seconds exceeded) durante una prueba con
        // tablas grandes (jerárquicas con muchas columnas, ej. 08.03.1/08.06.1/09.02.1).
        set_time_limit(220);
        $ejemploId = (int) $ejemploId;

        $config = config('Ia');
        if ($config->openaiApiKey === '') {
            return $this->response->setStatusCode(503)->setJSON(['error' => 'El llenado con IA todavía no está configurado en el servidor.']);
        }

        $body          = $this->request->getJSON(true) ?? [];
        $identificador = trim((string) ($body['identificador'] ?? ''));
        $seccionId     = trim((string) ($body['seccionId'] ?? ''));
        if ($identificador === '' || $seccionId === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta identificador o seccionId']);
        }

        $ejemplo = (new EjemploModel())->find($ejemploId);
        if (! $ejemplo) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ficha no encontrada']);
        }
        $plantillaId = (int) $ejemplo['plantilla_id'];

        $plantilla = db_connect()->table('plantillas')->where('id', $plantillaId)->get()->getRowArray();
        if ($plantilla === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Plantilla no encontrada']);
        }

        $excluidos = self::CAMPOS_TABLA_EXCLUIDOS[$plantilla['codigo']] ?? [];
        if (in_array($identificador, $excluidos, true)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Esta tabla se calcula automáticamente en el Excel — no se llena con IA.']);
        }

        $secciones = $this->seccionesDe($plantillaId);
        $seccion   = null;
        foreach ($secciones as $s) {
            if ((string) ($s['id'] ?? '') === $seccionId) {
                $seccion = $s;
                break;
            }
        }
        if ($seccion === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Sección no encontrada']);
        }

        $campo = null;
        foreach ($seccion['subsecciones'] ?? [] as $sub) {
            foreach ($sub['campos'] ?? [] as $c) {
                if ((string) ($c['identificador'] ?? '') === $identificador) {
                    $campo = $c;
                    break 2;
                }
            }
        }
        if ($campo === null || ($campo['tipo'] ?? '') !== 'tabla' || empty($campo['configTabla'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Este campo no es una tabla']);
        }

        $configTabla = $campo['configTabla'];
        $subtipo     = (string) ($configTabla['subtipo'] ?? 'filas_dinamicas');
        $agrupador   = (bool) ($configTabla['agrupador'] ?? false);
        $columnas    = $configTabla['columnas'] ?? [];

        // Valor actual: el del ejemplo si ya lo tocaron; si no, el default en blanco de la plantilla
        // (que ya trae la cantidad de filas/nodos base correcta) — sirve de referencia de forma.
        $valorActualCrudo = $this->valorActualDeEjemplo($ejemploId, $identificador) ?? ($campo['valorEjemplo'] ?? null);
        $valorActual      = $valorActualCrudo !== null ? json_decode((string) $valorActualCrudo, true) : null;
        if (! is_array($valorActual)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Esta tabla no tiene una estructura base válida para comparar.']);
        }

        $fuenteVerdad = $this->fuenteDeLaVerdad($ejemploId, $ejemplo['fuente_verdad_texto'] ?? '');
        if (trim($fuenteVerdad) === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Carga al menos un documento o escribe información del proyecto antes de llenar con IA']);
        }

        $reglas          = $this->contenidoGlobalPorNombre('Reglas de llenado automático con IA');
        $generales       = $this->contextosGeneralesDe($plantillaId);
        $contextoSeccion = $this->contextoDeSeccion($plantillaId, $seccionId);

        // Ejemplo de referencia: esta MISMA tabla, ya resuelta en otro proyecto marcado por el admin
        // como few-shot (ver valoresEjemploReferencia) — null si no hay ninguno marcado, si nunca se
        // tocó esta tabla en ese ejemplo, o si su valor guardado no decodifica a un array.
        $valorReferencia = null;
        $refCrudo = $this->valoresEjemploReferencia($plantillaId)[$identificador] ?? null;
        if ($refCrudo !== null) {
            $decoded = json_decode((string) $refCrudo, true);
            if (is_array($decoded)) {
                $valorReferencia = $decoded;
            }
        }

        // Igual que en llenarFicha(): que la tabla sea consistente con lo que ya está confirmado en
        // OTRAS secciones de esta misma ficha (mismo nombre de entidad, mismo CIAI, etc.).
        $otrasSeccionesConfirmadas = $this->valoresYaConfirmados($ejemploId);
        unset($otrasSeccionesConfirmadas[$identificador]);

        // El frontend ya tiene el Excel real cargado en vivo (useExcelVivo) cuando el usuario está en
        // el editor — para columnas cuyo desplegable depende de otro campo (INDIRECT, ej. la sección
        // 5 Problema-Objetivo), el catálogo estático del JSON no sirve (o no existe). El frontend
        // resuelve las opciones vigentes con xlsxListas.ts y las manda aquí; se combinan con las
        // opciones fijas que ya trae cada columna en el JSON (estas ganan solo si el override no trae
        // nada para esa columna).
        $opcionesPorColumna = is_array($body['opcionesPorColumna'] ?? null) ? $body['opcionesPorColumna'] : [];
        // Guía condicional para catálogos de más de un nivel (ej. "si tu Causa Directa es X, las
        // Causas Indirectas válidas son A, B, C; si es Y, son D") — el frontend la arma enumerando
        // las ramas posibles con opcionesDeConOverride ANTES de saber qué va a elegir la IA. La
        // validación de opciones sigue siendo la unión (ver $catalogoPorColumna más abajo); esta
        // guía es lo que ayuda al modelo a no mezclar una causa de una rama con la indirecta de otra.
        $contextoAdicional = trim((string) ($body['contextoAdicional'] ?? ''));

        $sistema = $this->construirSistemaTabla($reglas, $generales, $contextoSeccion, $fuenteVerdad);
        $usuario = $this->construirPromptTabla($campo, $subtipo, $agrupador, $columnas, $valorActual, $opcionesPorColumna, $contextoAdicional, $valorReferencia, $otrasSeccionesConfirmadas);

        // Cambiado a OpenAI (2026-08-19, usar créditos de OpenAI en vez de Anthropic) —
        // llamarClaudeCrudo()/llamarModeloCrudo() (Gemini) se dejan intactas por si hace falta volver
        // a swapear. Modelo híbrido: gpt-5-mini (barato) para el grueso de las tablas, pero cuando el
        // frontend manda `contextoAdicional` (guía condicional de cascada — hoy solo las 3 tablas de la
        // Sección 5 Problema-Objetivo) se usa gpt-5 (más caro, `$config->openaiModelo`) — ese es el
        // prompt más denso/exigente de toda la ficha (árbol + reglas por rama) y el modelo barato no lo
        // seguía de forma confiable con Claude Haiku; se mantiene el mismo criterio de split con OpenAI.
        $modeloParaEstaTabla = $contextoAdicional !== '' ? $config->openaiModelo : $config->openaiModeloLlenado;
        // 8000 se quedaba corto para tablas grandes (ej. 16.01.1 "Plan de implementación": 12 columnas
        // × varios grupos) — encontrado en vivo (2026-08-19): el modelo cortaba la respuesta a mitad del
        // primer grupo (finish_reason "length"), el JSON quedaba incompleto y el parseo fallaba, lo
        // que el cliente veía como un 502 genérico sin pista de la causa real (estaba en el log, no en
        // la respuesta HTTP). No hay downside de subir el techo: max_completion_tokens es un tope, no
        // un objetivo — una tabla chica sigue costando/tardando lo mismo que antes.
        $respuesta = $this->llamarOpenAICrudo($config, $sistema, $usuario, 32000, 170, $modeloParaEstaTabla, "tabla {$identificador}");
        if ($respuesta === null) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo consultar a la IA. Intenta de nuevo.']);
        }
        $valorPropuesto = $respuesta['valor'];

        $columnasCalculadas = array_values(array_filter(array_map(
            static fn (array $c): ?string => ($c['tipo'] ?? '') === 'calculado' ? (string) ($c['id'] ?? '') : null,
            $columnas,
        )));
        // Solo cubrimos el caso real presente en esta ficha: columna `calculado` como ÚLTIMA columna de
        // una tabla jerárquica (3.07.01/3.07.02) — el árbol no nombra sus niveles, así que no hay forma
        // fiable de proteger una columna calculada en cualquier posición sin la semántica exacta de
        // `agrupadorNivel`. Ampliar esto requeriría confirmar esa semántica primero.
        $ultimaColumnaCalculada = $columnas !== [] && ((array_values($columnas)[count($columnas) - 1]['tipo'] ?? '') === 'calculado');

        // Catálogo por columna (para validar que la IA no "casi acertó" una opción con mayúscula o
        // acento distinto — eso rompería el VLOOKUP en Excel en silencio). Incluye columnas booleano.
        $catalogoPorColumna = $this->catalogoPorColumnaDe($columnas, $opcionesPorColumna);
        // En filas_dinamicas el valor hoja ya viene con su id de columna al lado ({col_id: valor}),
        // así que $catalogoPorColumna solo alcanza para validarlo ahí. En jerárquica el árbol no trae
        // esa referencia por nivel — el "value" de cada nodo no dice de qué columna es — pero SÍ se
        // puede inferir: cada profundidad del árbol corresponde, en orden, a una columna de
        // `configTabla.columnas` (ver construirPromptTabla), con una capa extra al inicio si hay
        // agrupador (el título de grupo, que no es de ninguna columna). Este mapeo profundidad->id de
        // columna es lo que le permite a compararForma validar también las hojas de un árbol.
        $columnaIdPorProfundidad = [];
        foreach ($columnas as $i => $c) {
            $profundidad                             = $i + ($agrupador ? 1 : 0);
            $columnaIdPorProfundidad[$profundidad]    = (string) ($c['id'] ?? '');
        }

        $columnasConSubcolumnas = $this->columnasConSubcolumnasDe($columnas);
        $resultado = $this->validarFormaTabla($valorActual, $valorPropuesto['valor'] ?? null, $columnasCalculadas, $ultimaColumnaCalculada, $catalogoPorColumna, $columnaIdPorProfundidad, $columnasConSubcolumnas);
        if (! $resultado['valido']) {
            log_message('warning', '[llenado-tabla-ia] rechazado {id}: {motivo}', ['id' => $identificador, 'motivo' => $resultado['motivo']]);

            return $this->response->setStatusCode(422)->setJSON(['error' => $resultado['motivo']]);
        }

        if (in_array($identificador, self::TABLAS_UBIGEO, true)) {
            [$resultado['valorSaneado'], $advertenciasUbigeo] = $this->resolverUbigeoEnTabla($resultado['valorSaneado']);
            $resultado['advertencias'] = array_merge($resultado['advertencias'], $advertenciasUbigeo);
        }

        return $this->response->setJSON([
            'valor'        => $resultado['valorSaneado'],
            'estado'       => 'requiere_confirmacion',
            'advertencias' => $resultado['advertencias'],
            'fuente'       => mb_substr(trim((string) ($valorPropuesto['fuente'] ?? '')), 0, 240),
            'costoUsd'     => round($respuesta['costoUsd'], 5),
        ]);
    }

    /**
     * Devuelve, para una sección, el prompt EXACTO que armarían llenarFicha()/llenarTabla() —
     * sistema (cacheable + variable) y usuario, por campo de texto y por cada tabla — SIN llamar al
     * modelo (sin costo). Existe para que el admin pueda auditar en el propio panel "Contexto IA" si
     * las piezas que edita ahí (reglas, contexto general, guía de sección, nota de columna) componen
     * bien entre sí, en vez de tener que adivinar — encontrado en vivo esta sesión que la única forma
     * de detectar una contradicción real entre piezas (la guía decía "las tablas no se llenan" justo
     * cuando llenarTabla() les pedía llenarlas) era armar el prompt a mano fuera de la plataforma.
     *
     * La fuente de la verdad es la del ejemplo marcado como referencia (ver ejemploReferenciaId) — es
     * lo más parecido a un caso real sin depender de una ficha de cliente puntual; si no hay ninguno
     * marcado (o no tiene fuente de la verdad cargada), se deja un texto placeholder explícito en vez
     * de fingir que ese bloque está vacío en producción.
     */
    public function previsualizarPrompt($plantillaId = null): ResponseInterface
    {
        $plantillaId = (int) $plantillaId;
        $seccionId   = trim((string) ($this->request->getGet('seccionId') ?? ''));
        if ($seccionId === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta seccionId']);
        }

        $secciones = $this->seccionesDe($plantillaId);
        $seccion   = null;
        foreach ($secciones as $s) {
            if ((string) ($s['id'] ?? '') === $seccionId) {
                $seccion = $s;
                break;
            }
        }
        if ($seccion === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Sección no encontrada']);
        }

        $reglas          = $this->contenidoGlobalPorNombre('Reglas de llenado automático con IA');
        $generales       = $this->contextosGeneralesDe($plantillaId);
        $contextoSeccion = $this->contextoDeSeccion($plantillaId, $seccionId);
        $referencia      = $this->valoresEjemploReferencia($plantillaId);

        $refId = $this->ejemploReferenciaId($plantillaId);
        $fuenteVerdad = $refId !== null ? $this->fuenteDeLaVerdad($refId, '') : '';
        $fuenteVerdadEsReal = trim($fuenteVerdad) !== '';
        if (! $fuenteVerdadEsReal) {
            $fuenteVerdad = '[No hay fuente de la verdad de referencia cargada — este bloque varía por cada ficha real del cliente. '
                . 'Marca un ejemplo con fuente de la verdad como "ejemplo de referencia" en el tab Ejemplos para verlo aquí de verdad.]';
        }

        $campos            = $this->camposLlenables($seccion);
        $referenciaSeccion = array_intersect_key($referencia, array_flip(array_column($campos, 'identificador')));
        $sistemaSeccion    = $this->construirSistema($reglas, $generales, $contextoSeccion, $fuenteVerdad);
        $usuarioSeccion    = $this->construirPromptSeccion((string) $seccion['id'], (string) $seccion['nombre'], $campos, $referenciaSeccion);

        $tablas = [];
        foreach ($seccion['subsecciones'] ?? [] as $sub) {
            foreach ($sub['campos'] ?? [] as $campo) {
                if (($campo['tipo'] ?? '') !== 'tabla' || empty($campo['configTabla'])) {
                    continue;
                }
                $configTabla = $campo['configTabla'];
                $subtipo     = (string) ($configTabla['subtipo'] ?? 'filas_dinamicas');
                $agrupador   = (bool) ($configTabla['agrupador'] ?? false);
                $columnas    = $configTabla['columnas'] ?? [];
                $valorActual = json_decode((string) ($campo['valorEjemplo'] ?? '[]'), true);
                if (! is_array($valorActual)) {
                    continue;
                }

                $valorReferenciaTabla = null;
                $refCrudo = $referencia[$campo['identificador']] ?? null;
                if ($refCrudo !== null) {
                    $decoded = json_decode((string) $refCrudo, true);
                    if (is_array($decoded)) {
                        $valorReferenciaTabla = $decoded;
                    }
                }

                $sistemaTabla = $this->construirSistemaTabla($reglas, $generales, $contextoSeccion, $fuenteVerdad);
                $usuarioTabla = $this->construirPromptTabla($campo, $subtipo, $agrupador, $columnas, $valorActual, [], '', $valorReferenciaTabla);

                $tablas[] = [
                    'identificador'    => $campo['identificador'],
                    'etiqueta'         => $campo['etiqueta'],
                    'sistemaCacheable' => $sistemaTabla['cacheable'],
                    'sistemaVariable'  => $sistemaTabla['variable'],
                    'usuario'          => $usuarioTabla,
                ];
            }
        }

        return $this->response->setJSON([
            'fuenteVerdadEsReal' => $fuenteVerdadEsReal,
            'seccion' => [
                'sistemaCacheable' => $sistemaSeccion['cacheable'],
                'sistemaVariable'  => $sistemaSeccion['variable'],
                'usuario'          => $usuarioSeccion,
                'camposIncluidos'  => count($campos),
            ],
            'tablas' => $tablas,
        ]);
    }

    /**
     * Envía TODA la ficha (secciones + tablas elegibles) como un LOTE (Batch API de OpenAI) en vez de
     * una llamada síncrona por sección/tabla — ~50% más barato, a cambio de no tener respuesta
     * inmediata (ver estadoLoteFicha()). Pedido explícito del usuario (2026-08-2x): usar esto SOLO
     * para "Llenar toda la ficha"; el botón individual "Llenar con IA" de un campo/tabla suelto sigue
     * yendo por llenarFicha()/llenarTabla() (síncronas, sin tocar) — un lote de una sola línea no
     * tiene sentido y perdería la iteración rápida que ese botón necesita para poder corregir la guía
     * en el momento, como se hizo toda la sesión de hoy.
     *
     * Deliberadamente NO se reusa el código de llenarFicha()/llenarTabla() (que sí llaman a la API) —
     * se duplica la parte de "armar el prompt" a propósito para no arriesgar esas dos rutas ya
     * probadas; si en algún momento diverge demasiado, vale la pena extraer un helper común.
     */
    public function enviarLoteFicha($ejemploId = null): ResponseInterface
    {
        $ejemploId = (int) $ejemploId;

        $config = config('Ia');
        if ($config->openaiApiKey === '') {
            return $this->response->setStatusCode(503)->setJSON(['error' => 'El llenado con IA todavía no está configurado en el servidor.']);
        }

        $ejemplo = (new EjemploModel())->find($ejemploId);
        if (! $ejemplo) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ficha no encontrada']);
        }
        $plantillaId = (int) $ejemplo['plantilla_id'];

        $plantilla = db_connect()->table('plantillas')->where('id', $plantillaId)->get()->getRowArray();
        if ($plantilla === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Plantilla no encontrada']);
        }

        $body             = $this->request->getJSON(true) ?? [];
        $seccionIdsFiltro = is_array($body['seccionIds'] ?? null) ? array_map('strval', $body['seccionIds']) : null;

        $todasLasSecciones = $this->seccionesDe($plantillaId);
        $secciones = $seccionIdsFiltro === null
            ? $todasLasSecciones
            : array_values(array_filter($todasLasSecciones, static fn (array $s): bool => in_array((string) ($s['id'] ?? ''), $seccionIdsFiltro, true)));
        if ($secciones === []) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Ninguna de las secciones indicadas existe en esta ficha']);
        }

        $fuenteVerdad = $this->fuenteDeLaVerdad($ejemploId, $ejemplo['fuente_verdad_texto'] ?? '');
        if (trim($fuenteVerdad) === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Carga al menos un documento o escribe información del proyecto antes de llenar con IA']);
        }

        $reglas         = $this->contenidoGlobalPorNombre('Reglas de llenado automático con IA');
        $generales      = $this->contextosGeneralesDe($plantillaId);
        $referencia     = $this->valoresEjemploReferencia($plantillaId);
        $yaConfirmados  = $this->valoresYaConfirmados($ejemploId);
        $excluidosTabla = self::CAMPOS_TABLA_EXCLUIDOS[$plantilla['codigo']] ?? [];
        // Las 3 tablas de catálogo en cascada de la Sección 5 (ver opcionesLlenadoCascada() en el
        // frontend) dependen de que el cliente resuelva en VIVO las opciones vigentes contra su Excel
        // cargado, y de ver el borrador recién propuesto de la tabla anterior antes de pedir la
        // siguiente (ver el fix de resolverValorExcel del 2026-08-19) — nada de eso existe en un lote
        // server-side sin ida y vuelta al navegador. Se excluyen del lote y el cliente las llena
        // igual que hoy (síncrono, secuencial) en cuanto el lote termina — ver useLlenadoIALote.ts.
        $excluidosCascada = self::TABLAS_CASCADA_FUERA_DE_LOTE[$plantilla['codigo']] ?? [];

        $lineas = [];
        $mapeo  = [];

        foreach ($secciones as $seccion) {
            $seccionId = (string) ($seccion['id'] ?? '');
            $campos    = $this->camposLlenables($seccion);

            if ($campos !== []) {
                $contextoSeccion = $this->contextoDeSeccion($plantillaId, $seccionId);
                $sistema         = $this->construirSistema($reglas, $generales, $contextoSeccion, $fuenteVerdad);
                $idsSeccion      = array_column($campos, 'identificador');
                $referenciaSeccion = array_intersect_key($referencia, array_flip($idsSeccion));
                // A diferencia de llenarFicha() síncrono, $yaConfirmados NO se va actualizando sección
                // por sección dentro del MISMO lote (todas las líneas se mandan juntas, sin orden
                // garantizado de procesamiento) — solo ve lo que ya estaba confirmado ANTES de enviar
                // el lote. Diferencia real y aceptada: es la misma razón por la que las 3 tablas de
                // cascada quedan fuera del lote.
                $otrasSeccionesConfirmadas = array_diff_key($yaConfirmados, array_flip($idsSeccion));
                $usuario  = $this->construirPromptSeccion($seccionId, (string) ($seccion['nombre'] ?? ''), $campos, $referenciaSeccion, $otrasSeccionesConfirmadas);
                $customId = 'seccion__' . $seccionId;
                $lineas[] = $this->lineaLoteOpenAI($customId, $config->openaiModeloLlenado, 8000, $sistema, $usuario);
                $mapeo[$customId] = [
                    'tipo'      => 'seccion',
                    'seccionId' => $seccionId,
                    'nombre'    => (string) ($seccion['nombre'] ?? ''),
                    'modelo'    => $config->openaiModeloLlenado,
                    'totalCampos' => count($campos),
                    // Igual que $opcionesPorIdentif en llenarFicha() — sin esto, procesarResultadosLote()
                    // no tenía forma de saber qué campos tienen catálogo cerrado y guardaba lo que la IA
                    // respondiera tal cual (encontrado en vivo: 3.06.11 guardó un valor fuera de las
                    // opciones del Excel).
                    'opcionesPorIdentificador' => array_column(
                        array_filter($campos, static fn (array $c): bool => is_array($c['opciones'] ?? null) && $c['opciones'] !== []),
                        'opciones',
                        'identificador',
                    ),
                ];
            }

            foreach ($seccion['subsecciones'] ?? [] as $sub) {
                foreach ($sub['campos'] ?? [] as $campo) {
                    $identificador = (string) ($campo['identificador'] ?? '');
                    if (($campo['tipo'] ?? '') !== 'tabla' || empty($campo['configTabla'])) {
                        continue;
                    }
                    if (in_array($identificador, $excluidosTabla, true) || in_array($identificador, $excluidosCascada, true)) {
                        continue;
                    }

                    $configTabla = $campo['configTabla'];
                    $subtipo     = (string) ($configTabla['subtipo'] ?? 'filas_dinamicas');
                    $agrupador   = (bool) ($configTabla['agrupador'] ?? false);
                    $columnas    = $configTabla['columnas'] ?? [];

                    $valorActualCrudo = $this->valorActualDeEjemplo($ejemploId, $identificador) ?? ($campo['valorEjemplo'] ?? null);
                    $valorActual      = $valorActualCrudo !== null ? json_decode((string) $valorActualCrudo, true) : null;
                    if (! is_array($valorActual)) {
                        continue; // sin base válida para comparar forma — mismo motivo de rechazo que llenarTabla()
                    }

                    $contextoSeccionTabla = $this->contextoDeSeccion($plantillaId, $seccionId);
                    $sistemaTabla = $this->construirSistemaTabla($reglas, $generales, $contextoSeccionTabla, $fuenteVerdad);

                    $valorReferencia = null;
                    $refCrudo = $referencia[$identificador] ?? null;
                    if ($refCrudo !== null) {
                        $decoded = json_decode((string) $refCrudo, true);
                        if (is_array($decoded)) {
                            $valorReferencia = $decoded;
                        }
                    }

                    $otrasSeccionesConfirmadasTabla = $yaConfirmados;
                    unset($otrasSeccionesConfirmadasTabla[$identificador]);

                    // Sin opcionesPorColumna/contextoAdicional del frontend (no hay Excel vivo en un
                    // lote server-side) — igual que previsualizarPrompt() para esta misma tabla, y
                    // exactamente lo que ya pasa hoy para cualquier tabla que NO sea una de las 3 de
                    // cascada (esas nunca llegan aquí, ver $excluidosCascada arriba).
                    $usuarioTabla = $this->construirPromptTabla($campo, $subtipo, $agrupador, $columnas, $valorActual, [], '', $valorReferencia, $otrasSeccionesConfirmadasTabla);
                    $customId     = 'tabla__' . $identificador;
                    $lineas[]     = $this->lineaLoteOpenAI($customId, $config->openaiModeloLlenado, 32000, $sistemaTabla, $usuarioTabla);

                    $columnasCalculadas = array_values(array_filter(array_map(
                        static fn (array $c): ?string => ($c['tipo'] ?? '') === 'calculado' ? (string) ($c['id'] ?? '') : null,
                        $columnas,
                    )));
                    $ultimaColumnaCalculada = $columnas !== [] && ((array_values($columnas)[count($columnas) - 1]['tipo'] ?? '') === 'calculado');
                    $catalogoPorColumna = $this->catalogoPorColumnaDe($columnas);
                    $columnaIdPorProfundidad = [];
                    foreach ($columnas as $i => $c) {
                        $columnaIdPorProfundidad[$i + ($agrupador ? 1 : 0)] = (string) ($c['id'] ?? '');
                    }

                    $mapeo[$customId] = [
                        'tipo'                    => 'tabla',
                        'identificador'           => $identificador,
                        'modelo'                  => $config->openaiModeloLlenado,
                        'valorActual'             => $valorActual,
                        'columnasCalculadas'      => $columnasCalculadas,
                        'ultimaColumnaCalculada'  => $ultimaColumnaCalculada,
                        'catalogoPorColumna'      => $catalogoPorColumna,
                        'columnaIdPorProfundidad' => $columnaIdPorProfundidad,
                        'columnasConSubcolumnas'  => $this->columnasConSubcolumnasDe($columnas),
                    ];
                }
            }
        }

        if ($lineas === []) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No hay nada que llenar en las secciones indicadas (o todo lo que había quedó fuera del lote — revisa el botón individual para esos campos).']);
        }

        $fileId = $this->subirArchivoLoteOpenAI($config, implode("\n", $lineas));
        if ($fileId === null) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo preparar el lote para la IA. Intenta de nuevo.']);
        }
        $batchId = $this->crearLoteOpenAI($config, $fileId);
        if ($batchId === null) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo enviar el lote a la IA. Intenta de nuevo.']);
        }

        $loteModel = new LoteLlenadoIAModel();
        $loteId    = $loteModel->insert([
            'ejemplo_id'      => $ejemploId,
            'openai_batch_id' => $batchId,
            'openai_file_id'  => $fileId,
            'estado'          => 'enviado',
            'mapeo_json'      => json_encode($mapeo, JSON_UNESCAPED_UNICODE),
        ], true);

        log_message('info', '[llenado-ia-lote] Lote {loteId} enviado a OpenAI (batch {batchId}): {n} solicitudes.', [
            'loteId' => $loteId, 'batchId' => $batchId, 'n' => count($lineas),
        ]);

        return $this->response->setJSON(['loteId' => $loteId, 'estado' => 'enviado', 'totalSolicitudes' => count($lineas)]);
    }

    /**
     * Consulta el estado de un lote enviado con enviarLoteFicha(). Si OpenAI ya terminó de
     * procesarlo, descarga el archivo de resultados UNA sola vez, aplica cada resultado (persiste los
     * campos de texto de sección igual que llenarFicha(), arma las propuestas de tabla igual que
     * llenarTabla() — sin persistirlas, quedan como borrador para que el cliente las confirme) y dej
     * el resultado final guardado en `resultado_json` para que un poll repetido no vuelva a bajar ni
     * reprocesar nada.
     */
    public function estadoLoteFicha($ejemploId = null, $loteId = null): ResponseInterface
    {
        $ejemploId = (int) $ejemploId;
        $loteId    = (int) $loteId;

        $config    = config('Ia');
        $loteModel = new LoteLlenadoIAModel();
        $lote      = $loteModel->find($loteId);
        if ($lote === null || (int) $lote['ejemplo_id'] !== $ejemploId) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Lote no encontrado']);
        }

        if ($lote['estado'] === 'completado') {
            $resultado = json_decode((string) $lote['resultado_json'], true);

            return $this->response->setJSON(['estado' => 'completado'] + (is_array($resultado) ? $resultado : []));
        }
        if ($lote['estado'] === 'error') {
            return $this->response->setJSON(['estado' => 'error', 'error' => 'La IA no pudo procesar el lote. Intenta de nuevo.', 'detalle' => $lote['error'] ?? null]);
        }

        $estadoBatch = $this->consultarLoteOpenAI($config, $lote['openai_batch_id']);
        if ($estadoBatch === null) {
            return $this->response->setJSON(['estado' => 'procesando']); // hipo de red consultando el estado — no es que el lote haya fallado
        }

        $estadoOpenAI = (string) ($estadoBatch['status'] ?? '');
        if (in_array($estadoOpenAI, ['validating', 'in_progress', 'finalizing'], true)) {
            $loteModel->update($loteId, ['estado' => 'procesando']);

            return $this->response->setJSON([
                'estado'      => 'procesando',
                'progreso'    => $estadoBatch['request_counts'] ?? null,
            ]);
        }
        if (in_array($estadoOpenAI, ['failed', 'expired', 'cancelled', 'cancelling'], true)) {
            $motivo = $estadoOpenAI === 'failed'
                ? json_encode($estadoBatch['errors'] ?? null, JSON_UNESCAPED_UNICODE)
                : "estado de OpenAI: {$estadoOpenAI}";
            $loteModel->update($loteId, ['estado' => 'error', 'error' => $motivo]);
            log_message('error', '[llenado-ia-lote] Lote {loteId} (batch {batchId}) terminó en {estado}: {motivo}', [
                'loteId' => $loteId, 'batchId' => $lote['openai_batch_id'], 'estado' => $estadoOpenAI, 'motivo' => $motivo,
            ]);

            return $this->response->setJSON(['estado' => 'error', 'error' => 'La IA no pudo procesar el lote. Intenta de nuevo.', 'detalle' => $motivo]);
        }
        if ($estadoOpenAI !== 'completed') {
            // Estado no reconocido — se trata como "sigue procesando" en vez de fallar de una, por si
            // OpenAI agrega un estado intermedio nuevo que no está en las listas de arriba.
            return $this->response->setJSON(['estado' => 'procesando']);
        }

        $outputFileId = $estadoBatch['output_file_id'] ?? null;
        if (! is_string($outputFileId) || $outputFileId === '') {
            // "completed" sin output_file_id: las líneas fallaron todas a nivel de OpenAI (ver
            // error_file_id) — no hay nada que procesar, pero tampoco es un 'failed' del batch en sí.
            $loteModel->update($loteId, ['estado' => 'error', 'error' => 'El lote terminó sin resultados (revisar error_file_id en OpenAI).']);

            return $this->response->setJSON(['estado' => 'error', 'error' => 'La IA no devolvió resultados para este lote.', 'detalle' => 'El lote terminó sin resultados (revisar error_file_id en OpenAI).']);
        }

        $contenido = $this->descargarArchivoOpenAI($config, $outputFileId);
        if ($contenido === null) {
            return $this->response->setJSON(['estado' => 'procesando']); // hipo bajando el archivo — se reintenta en el próximo poll
        }

        $mapeo = json_decode((string) $lote['mapeo_json'], true);
        if (! is_array($mapeo)) {
            $mapeo = [];
        }

        $resultado = $this->procesarResultadosLote($ejemploId, $mapeo, $contenido);

        $loteModel->update($loteId, [
            'estado'         => 'completado',
            'resultado_json' => json_encode($resultado, JSON_UNESCAPED_UNICODE),
        ]);

        log_message('info', '[llenado-ia-lote] Lote {loteId} completado: {secciones} secciones, {tablas} tablas propuestas — costo total estimado USD {costo}.', [
            'loteId' => $loteId, 'secciones' => count($resultado['secciones'] ?? []), 'tablas' => count($resultado['tablas'] ?? []),
            'costo' => number_format($resultado['costoTotalUsd'] ?? 0.0, 5),
        ]);

        return $this->response->setJSON(['estado' => 'completado'] + $resultado);
    }

    /**
     * Recorre cada línea del archivo de resultados del lote (JSONL, un objeto por `custom_id`),
     * aplica el mismo procesamiento que llenarFicha() (normalizarPropuesta + persistir) para las
     * secciones de texto, y el mismo que llenarTabla() (validarFormaTabla + UBIGEO) para las tablas —
     * estas últimas NO se persisten, se devuelven para que el cliente las aplique como borrador igual
     * que hoy.
     *
     * @param array<string,array<string,mixed>> $mapeo custom_id => metadata guardada en enviarLoteFicha()
     * @return array{secciones: list<array<string,mixed>>, tablas: list<array<string,mixed>>, costoTotalUsd: float}
     */
    private function procesarResultadosLote(int $ejemploId, array $mapeo, string $contenidoJsonl): array
    {
        $valoresFinal  = [];
        $estadosFinal  = [];
        $confianzaFinal = [];
        $fuentesFinal  = [];
        $idsAfectados  = [];
        $resumenSecciones = [];
        $tablas        = [];
        $costoTotalUsd = 0.0;

        foreach (preg_split('/\r?\n/', trim($contenidoJsonl)) as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                continue;
            }
            $item = json_decode($linea, true);
            if (! is_array($item)) {
                continue;
            }
            $customId = (string) ($item['custom_id'] ?? '');
            $meta     = $mapeo[$customId] ?? null;
            if ($meta === null) {
                continue; // línea que no reconocemos — no debería pasar, pero no tumbar el lote entero por una
            }

            $respuestaJson = $item['response']['body'] ?? null;
            $propuesta = is_array($respuestaJson)
                ? $this->procesarRespuestaChatOpenAI($respuestaJson, (string) ($meta['modelo'] ?? ''), $customId)
                : null;

            if ($meta['tipo'] === 'seccion') {
                $idsAfectados[$meta['seccionId']] = true; // marcador; se resuelve a ids reales más abajo vía camposLlenables si hiciera falta
                $aceptados = 0;
                $costoSeccionUsd = 0.0;
                if ($propuesta !== null) {
                    $costoSeccionUsd = $propuesta['costoUsd'] ?? 0.0;
                    $costoTotalUsd  += $costoSeccionUsd;
                    $normalizado = $this->normalizarPropuesta($propuesta['valor']);
                    $opcionesPorIdentif = $meta['opcionesPorIdentificador'] ?? [];
                    foreach ($normalizado['valores'] as $identificador => $texto) {
                        $texto = trim((string) $texto);
                        if ($texto === '') {
                            continue;
                        }
                        // Mismo criterio que llenarFicha(): un campo con catálogo cerrado no puede
                        // guardar cualquier texto — si "casi acertó" se normaliza al canónico, si no
                        // calza con ninguna opción se descarta (no rompe el resto de la sección).
                        $opciones = $opcionesPorIdentif[$identificador] ?? null;
                        if (is_array($opciones)) {
                            $match = $this->matchearOpcionOpcional($texto, $opciones);
                            if ($match === null) {
                                log_message('warning', '[llenado-ia-lote] {id}: "{texto}" no calza con ninguna opción del catálogo ({opciones}) — descartado.', [
                                    'id' => $identificador, 'texto' => $texto, 'opciones' => implode(' | ', $opciones),
                                ]);
                                continue;
                            }
                            $texto = $match;
                        }
                        $valoresFinal[$identificador] = $texto;
                        $idsAfectados[$identificador] = true;
                        if (isset($normalizado['estados'][$identificador])) {
                            $estadosFinal[$identificador] = $normalizado['estados'][$identificador];
                        }
                        if (isset($normalizado['confianza'][$identificador])) {
                            $confianzaFinal[$identificador] = $normalizado['confianza'][$identificador];
                        }
                        if (isset($normalizado['fuentes'][$identificador]) && $normalizado['fuentes'][$identificador] !== '') {
                            $fuentesFinal[$identificador] = $normalizado['fuentes'][$identificador];
                        }
                        $aceptados++;
                    }
                }
                $resumenSecciones[] = [
                    'seccionId' => $meta['seccionId'],
                    'nombre'    => $meta['nombre'],
                    'campos'    => (int) ($meta['totalCampos'] ?? 0),
                    'llenados'  => $aceptados,
                    'costoUsd'  => round($costoSeccionUsd, 5),
                ];
                continue;
            }

            // tabla
            $identificador = (string) $meta['identificador'];
            if ($propuesta === null) {
                $tablas[] = ['identificador' => $identificador, 'error' => 'No se pudo consultar a la IA para esta tabla.'];
                continue;
            }
            $costoTotalUsd += $propuesta['costoUsd'] ?? 0.0;

            $resultado = $this->validarFormaTabla(
                $meta['valorActual'],
                $propuesta['valor']['valor'] ?? null,
                $meta['columnasCalculadas'],
                $meta['ultimaColumnaCalculada'],
                $meta['catalogoPorColumna'],
                $meta['columnaIdPorProfundidad'],
                $meta['columnasConSubcolumnas'] ?? [],
            );
            if (! $resultado['valido']) {
                log_message('warning', '[llenado-ia-lote] tabla {id} rechazada: {motivo}', ['id' => $identificador, 'motivo' => $resultado['motivo']]);
                $tablas[] = ['identificador' => $identificador, 'error' => $resultado['motivo']];
                continue;
            }
            if (in_array($identificador, self::TABLAS_UBIGEO, true)) {
                [$resultado['valorSaneado'], $advertenciasUbigeo] = $this->resolverUbigeoEnTabla($resultado['valorSaneado']);
                $resultado['advertencias'] = array_merge($resultado['advertencias'], $advertenciasUbigeo);
            }
            $tablas[] = [
                'identificador' => $identificador,
                'valor'         => $resultado['valorSaneado'],
                'advertencias'  => $resultado['advertencias'],
                'fuente'        => mb_substr(trim((string) ($propuesta['valor']['fuente'] ?? '')), 0, 240),
                'costoUsd'      => round($propuesta['costoUsd'] ?? 0.0, 5),
            ];
        }

        if ($valoresFinal !== []) {
            $this->guardarValores($ejemploId, $valoresFinal, $fuentesFinal, array_keys($idsAfectados));
        }

        return [
            // Mismo shape que ResultadoLlenadoIA (frontend) — valores/estados/confianza/fuentes es lo
            // que construirResumenResultadoLlenado() necesita para armar el informe de "Ver resumen",
            // igual que con el flujo síncrono de llenarFicha().
            'valores'       => (object) $valoresFinal,
            'estados'       => (object) $estadosFinal,
            'confianza'     => (object) $confianzaFinal,
            'fuentes'       => (object) $fuentesFinal,
            'secciones'     => $resumenSecciones,
            // Extra respecto a ResultadoLlenadoIA — las tablas propuestas de este lote, para que el
            // cliente las aplique como borrador (ver useLlenadoIALote.ts). No forman parte del shape
            // síncrono porque llenarFicha() nunca toca tablas.
            'tablas'        => $tablas,
            'costoTotalUsd' => round($costoTotalUsd, 5),
        ];
    }

    /** Una línea del archivo JSONL que espera la Batch API de OpenAI. */
    private function lineaLoteOpenAI(string $customId, string $modelo, int $maxTokens, array $sistema, string $usuario): string
    {
        return json_encode([
            'custom_id' => $customId,
            'method'    => 'POST',
            'url'       => '/v1/chat/completions',
            'body'      => [
                'model'                 => $modelo,
                'max_completion_tokens' => $maxTokens,
                'response_format'       => ['type' => 'json_object'],
                'messages'              => [
                    ['role' => 'system', 'content' => $sistema['variable'] !== '' ? "{$sistema['cacheable']}\n\n{$sistema['variable']}" : $sistema['cacheable']],
                    ['role' => 'user', 'content' => $usuario],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /** Sube el .jsonl del lote a OpenAI (Files API, purpose=batch). Devuelve el file_id, o null si falló. */
    private function subirArchivoLoteOpenAI(object $config, string $jsonl): ?string
    {
        $limite = tempnam(sys_get_temp_dir(), 'lote_ia_');
        file_put_contents($limite, $jsonl);

        $ch = curl_init('https://api.openai.com/v1/files');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['authorization: Bearer ' . $config->openaiApiKey],
            CURLOPT_POSTFIELDS     => [
                'purpose' => 'batch',
                'file'    => new \CURLFile($limite, 'application/jsonl', 'lote.jsonl'),
            ],
            CURLOPT_TIMEOUT => 60,
        ]);
        $cuerpo = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);
        @unlink($limite);

        if ($cuerpo === false || $estado < 200 || $estado >= 300) {
            log_message('error', '[llenado-ia-lote] Subida de archivo a OpenAI falló ({estado}): {cuerpo} {error}', [
                'estado' => $estado, 'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : '(sin cuerpo)', 'error' => $error,
            ]);

            return null;
        }
        $json = json_decode((string) $cuerpo, true);

        return is_string($json['id'] ?? null) ? $json['id'] : null;
    }

    /** Crea el batch job en OpenAI a partir de un file_id ya subido. Devuelve el batch_id, o null si falló. */
    private function crearLoteOpenAI(object $config, string $fileId): ?string
    {
        $ch = curl_init('https://api.openai.com/v1/batches');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['content-type: application/json', 'authorization: Bearer ' . $config->openaiApiKey],
            CURLOPT_POSTFIELDS     => json_encode([
                'input_file_id'    => $fileId,
                'endpoint'         => '/v1/chat/completions',
                'completion_window' => '24h',
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);
        $cuerpo = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($cuerpo === false || $estado < 200 || $estado >= 300) {
            log_message('error', '[llenado-ia-lote] Creación del batch en OpenAI falló ({estado}): {cuerpo} {error}', [
                'estado' => $estado, 'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : '(sin cuerpo)', 'error' => $error,
            ]);

            return null;
        }
        $json = json_decode((string) $cuerpo, true);

        return is_string($json['id'] ?? null) ? $json['id'] : null;
    }

    /** Consulta el estado actual de un batch en OpenAI. Devuelve el objeto tal cual, o null si la consulta falló. */
    private function consultarLoteOpenAI(object $config, string $batchId): ?array
    {
        $ch = curl_init("https://api.openai.com/v1/batches/{$batchId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['authorization: Bearer ' . $config->openaiApiKey],
            CURLOPT_TIMEOUT        => 20,
        ]);
        $cuerpo = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($cuerpo === false || $estado < 200 || $estado >= 300) {
            return null;
        }
        $json = json_decode((string) $cuerpo, true);

        return is_array($json) ? $json : null;
    }

    /** Descarga el contenido de un archivo de OpenAI (el output_file_id de un batch completado). */
    private function descargarArchivoOpenAI(object $config, string $fileId): ?string
    {
        $ch = curl_init("https://api.openai.com/v1/files/{$fileId}/content");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['authorization: Bearer ' . $config->openaiApiKey],
            CURLOPT_TIMEOUT        => 60,
        ]);
        $cuerpo = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($cuerpo !== false && $estado >= 200 && $estado < 300) ? (string) $cuerpo : null;
    }

    /**
     * Para las 3 tablas de ubicación (ver TABLAS_UBIGEO): reemplaza el texto "Departamento |
     * Provincia | Distrito" que puso la IA en la celda "ubigeo" de cada fila por el código real de 6
     * dígitos, resuelto de forma determinista contra el catálogo oficial (UbigeoResolver) — nunca se
     * confía en que el modelo calcule o recuerde el código él mismo. Si una fila no trae las 3 partes
     * o no matchea ningún distrito real, se deja el texto tal cual y se agrega una advertencia (no se
     * rechaza la tabla completa por una fila: las demás pueden estar bien).
     *
     * @param list<array<string,string>> $filas
     * @return array{0: list<array<string,string>>, 1: list<string>}
     */
    private function resolverUbigeoEnTabla(array $filas): array
    {
        $advertencias = [];
        foreach ($filas as $i => &$fila) {
            $texto = trim((string) ($fila['ubigeo'] ?? ''));
            if ($texto === '' || preg_match('/^\d{6}$/', $texto) === 1) {
                continue; // vacío, o ya es un código de 6 dígitos (no lo tocamos ni lo revalidamos)
            }
            $codigo = UbigeoResolver::resolverDesdeTexto($texto);
            if ($codigo !== null) {
                $fila['ubigeo'] = $codigo;
            } else {
                $advertencias[] = "Fila " . ($i + 1) . ": no se pudo determinar el código UBIGEO para \"{$texto}\" — revísalo y complétalo manualmente.";
            }
        }
        unset($fila);

        return [$filas, $advertencias];
    }

    /**
     * Valores escalares (texto/número/fecha/catálogo — NUNCA tablas, para no inflar el prompt con un
     * JSON grande) que ESTE ejemplo ya tiene confirmados, de cualquier sección — se usa para que al
     * llenar la sección N la IA sea consistente con lo que ya se llenó en secciones anteriores (mismo
     * nombre de entidad, mismo CIAI, mismo UBIGEO, etc.) en vez de re-derivarlo de la fuente de la
     * verdad de forma independiente cada vez y arriesgarse a que salga ligeramente distinto. Pedido
     * explícito del usuario: "si ya llenamos 1 y 2, la IA debe tomarlos en cuenta al llenar las
     * siguientes secciones".
     *
     * @return array<string,string> identificador => valor
     */
    private function valoresYaConfirmados(int $ejemploId): array
    {
        $archivo = (new ArchivoModel())->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $ejemploId)->first();
        if (! $archivo || empty($archivo['contenido_json'])) {
            return [];
        }
        $contenido = json_decode((string) $archivo['contenido_json'], true);
        $valores   = is_array($contenido['valores'] ?? null) ? $contenido['valores'] : [];

        return array_filter($valores, static function ($v): bool {
            if (! is_string($v) || trim($v) === '') {
                return false;
            }
            $primero = $v[0] ?? '';

            return $primero !== '[' && $primero !== '{'; // deja fuera tablas y mapa_coordenadas
        });
    }

    /** Valor actual (string JSON) de este campo en el ejemplo, o null si nunca se tocó. */
    private function valorActualDeEjemplo(int $ejemploId, string $identificador): ?string
    {
        $archivo = (new ArchivoModel())->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $ejemploId)->first();
        if (! $archivo || empty($archivo['contenido_json'])) {
            return null;
        }
        $contenido = json_decode((string) $archivo['contenido_json'], true);
        $valores   = is_array($contenido['valores'] ?? null) ? $contenido['valores'] : [];
        $valor     = $valores[$identificador] ?? null;

        return is_string($valor) && trim($valor) !== '' ? $valor : null;
    }

    /**
     * Valores del ejemplo marcado por el admin como "referencia para IA" en esta plantilla (a lo más
     * uno, ver EjemplosController::marcarReferenciaIA) — un caso ya resuelto que se usa como few-shot:
     * se le muestra al modelo la FORMA/estilo de un dato real ya llenado, campo por campo, en vez de
     * solo reglas en prosa (el problema real que motivó esto: sin un ejemplo concreto, tablas de forma
     * exacta como 2.01.01 no tenían de dónde imitar el estilo esperado).
     *
     * @return array<string,string> identificador => valor crudo (texto plano, o JSON de tabla como
     *   string — tal como está guardado; el llamador decide si decodificarlo).
     */
    private function valoresEjemploReferencia(int $plantillaId): array
    {
        $refId = $this->ejemploReferenciaId($plantillaId);
        if ($refId === null) {
            return [];
        }
        $archivo = (new ArchivoModel())->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $refId)->first();
        if (! $archivo || empty($archivo['contenido_json'])) {
            return [];
        }
        $contenido = json_decode((string) $archivo['contenido_json'], true);
        $valores   = is_array($contenido['valores'] ?? null) ? $contenido['valores'] : [];

        return array_filter($valores, static fn ($v): bool => is_string($v) && trim($v) !== '');
    }

    /** Id del ejemplo marcado como referencia para IA en esta plantilla, o null si no hay ninguno. */
    private function ejemploReferenciaId(int $plantillaId): ?int
    {
        $ref = db_connect()->table('ejemplos')->where('plantilla_id', $plantillaId)->where('es_referencia_ia', 1)->get()->getRowArray();

        return $ref !== null ? (int) $ref['id'] : null;
    }

    /**
     * Ver construirSistema() — mismo split cacheable/variable, mismo motivo (contextoSeccion ya no
     * va antes de fuenteVerdad dentro del bloque cacheado, para que el caché sobreviva entre tablas
     * de secciones distintas, no solo entre tablas de la misma sección).
     *
     * @return array{cacheable: string, variable: string}
     */
    private function construirSistemaTabla(string $reglas, array $generales, array $contextoSeccion, string $fuenteVerdad): array
    {
        $partes = [
            'Eres un asistente experto en formulación de proyectos de inversión pública del Perú (Invierte.pe) que llena fichas técnicas oficiales del MEF a partir de la información real de un proyecto.',
            'Vas a llenar UNA tabla de la ficha. Respondes SIEMPRE con un único objeto JSON válido (sin markdown, sin texto fuera del JSON): {"valor": <estructura>, "fuente": "<breve descripción de dónde salió la información, ej. nombre del documento y qué parte>"}.',
            'La forma de "valor" debe calzar EXACTAMENTE con la tabla actual que te muestran en el mensaje de usuario: mismas claves en cada fila/objeto, mismo número de filas/bloques/nodos. Nunca agregues ni quites filas, columnas o niveles — solo cambias los valores de las celdas.',
            'Si no hay evidencia en la fuente de la verdad para una celda, déjala como cadena vacía "" — no inventes datos.',
            '"fuente" es UN solo texto breve para toda la tabla (no por celda) — ej. "perfil_proyecto.pdf, sección de costos de inversión". Si no llenaste ninguna celda con datos reales, deja "fuente" como cadena vacía.',
        ];
        if ($reglas !== '') {
            $partes[] = "Reglas de llenado automático:\n{$reglas}";
        }
        foreach ($generales as $nombre => $texto) {
            $partes[] = "Contexto general de esta ficha — {$nombre}:\n{$texto}";
        }
        $partes[] = "Fuente de la verdad (información real del proyecto, cargada por el cliente):\n{$fuenteVerdad}";

        $variable = [];
        foreach ($contextoSeccion as $nombre => $texto) {
            $variable[] = "Contexto de esta sección — {$nombre}:\n{$texto}";
        }

        return ['cacheable' => implode("\n\n", $partes), 'variable' => implode("\n\n", $variable)];
    }

    /**
     * @param array<string,list<string>> $opcionesPorColumna id de columna -> opciones resueltas en
     *   vivo por el frontend (útil cuando la columna depende de un INDIRECT y no tiene `opciones`
     *   estáticas en el JSON, ej. Sección 5 Problema-Objetivo). Si una columna no aparece aquí, se
     *   usan sus `opciones` propias del JSON (si las tiene).
     */
    /** @param array<string,string> $otrasSeccionesConfirmadas ver valoresYaConfirmados() */
    private function construirPromptTabla(array $campo, string $subtipo, bool $agrupador, array $columnas, array $valorActual, array $opcionesPorColumna = [], string $contextoAdicional = '', ?array $valorReferencia = null, array $otrasSeccionesConfirmadas = []): string
    {
        $lineas = [
            "Vas a llenar la tabla \"{$campo['etiqueta']}\" (identificador: {$campo['identificador']}).",
        ];
        if ($otrasSeccionesConfirmadas !== []) {
            $lineas[] = 'Valores YA CONFIRMADOS en otras secciones de esta misma ficha — mantente consistente con esto '
                . '(mismo nombre de entidad, mismo CIAI, mismo UBIGEO si aplica, etc.), no los repitas en esta tabla salvo que una columna lo pida:';
            foreach ($otrasSeccionesConfirmadas as $id => $valor) {
                $lineas[] = "- {$id}: {$valor}";
            }
        }
        if ($contextoAdicional !== '') {
            // Guía condicional para catálogos de más de un nivel — ver el parámetro homónimo en
            // llenarTabla(). Va ANTES de listar las columnas para que la IA la lea primero.
            $lineas[] = $contextoAdicional;
        }
        $lineas[] = 'Columnas de esta tabla (id real → nombre → tipo):';
        foreach ($columnas as $c) {
            $nivel    = isset($c['nivel']) ? ", nivel: {$c['nivel']}" : '';
            $linea    = "- \"{$c['id']}\" → {$c['nombre']} (tipo: {$c['tipo']}{$nivel})";
            $opciones = $opcionesPorColumna[$c['id']] ?? $c['opciones'] ?? null;
            if (is_array($opciones) && $opciones !== []) {
                $linea .= ' — SOLO estas opciones, copia el texto EXACTO de la que mejor corresponda: ' . implode(' | ', $opciones)
                    . '. Si la fuente de la verdad describe algo que corresponde conceptualmente a una de estas opciones aunque use'
                    . ' palabras distintas, ELIGE esa opción — no dejes la celda vacía solo porque la redacción exacta no aparece'
                    . ' en el texto fuente. Solo déjala vacía si el tema de la fila no se menciona en absoluto en la fuente de la verdad.'
                    . ' Si en la "tabla actual" de abajo esta columna YA tiene algo escrito, no asumas que está bien solo porque'
                    . ' ya hay texto ahí — si no es EXACTAMENTE una de estas opciones (ej. "Sí"/"No" sueltos cuando las opciones'
                    . ' reales son frases más largas), corrígelo a la opción correcta igual que si estuviera vacío.';
            }
            $etiquetasBooleano = $c['etiquetasBooleano'] ?? null;
            if (($c['tipo'] ?? '') === 'booleano' && is_array($etiquetasBooleano)) {
                $linea .= " — responder exactamente \"{$etiquetasBooleano['true']}\" o \"{$etiquetasBooleano['false']}\"";
            }
            if (in_array($c['tipo'] ?? '', ['numero', 'decimal'], true)) {
                // Encontrado en vivo (4.01.01 "Cantidad" = "2,450"): la app interpreta la coma como
                // separador DECIMAL (convención local), no de miles — "2,450" se leyó como 2.45, no
                // 2450, y rompió en cascada cualquier fórmula del Excel que dependiera de esa celda.
                $linea .= ' — escribe el número SIN separador de miles (ej. "2450", nunca "2,450" ni "2.450"); si de verdad necesitas decimales, usa punto (ej. "2450.5").';
            }
            if (! empty($c['subcolumnas']) && is_array($c['subcolumnas'])) {
                // Encontrado en vivo (4.01.01 "%"): esta columna normalmente es un objeto con estas
                // subcolumnas en cada fila — verificado contra el .xlsx real, esa celda es una FÓRMULA
                // de Excel (divide la fila contra la de arriba), no algo que la IA deba calcular; el
                // validador la fuerza al valor original pase lo que pase, así que intentar llenarla es
                // esfuerzo perdido. La ÚNICA excepción es la fila de la población/categoría TOTAL o
                // BASE (la que no es porcentaje de ninguna otra): ahí aparece como texto plano en vez
                // de objeto, y ese texto SÍ lo llena la IA con la FUENTE del dato (ej. "Padrón SISFOH
                // 2025", "Censo INEI 2022"), nunca un porcentaje ni "100%".
                $nombresSub = implode(', ', array_map(static fn (array $s): string => (string) ($s['nombre'] ?? $s['id'] ?? ''), $c['subcolumnas']));
                $linea .= " — es un objeto con subcolumnas ({$nombresSub}) que el Excel calcula solo: NO propongas nada ahí, se ignora cualquier valor que pongas. Solo en la fila TOTAL/BASE de la tabla aparece como texto plano en vez de objeto — esa fila sí la llenas tú, con la FUENTE del dato.";
            }
            if (($c['tipo'] ?? '') === 'coordenadas') {
                // Encontrado en vivo (2026-08-19, 3.03.01): el modelo devolvió {"lat":...,"lng":...}
                // para esta celda — objeto válido para un campo `mapa_coordenadas` suelto, pero esta
                // celda de TABLA espera texto plano (el valor por defecto es "", no {}) y el objeto
                // rompía la validación de forma. No es un tipo de columna que exista en ninguna otra
                // ficha probada hasta ahora, así que no hay "nota" sembrada para esto — se cubre acá.
                $linea .= ' — escribe "latitud, longitud" como TEXTO plano (ej. "-11.932480, -77.059210"), nunca un objeto {lat,lng}.';
            }
            // Convención que no se deduce del nombre/tipo de la columna (ej. "costo ANUAL, no
            // mensual", "monto en soles de ese trimestre, no %") — ver Franja B de la propuesta de
            // llenado híbrido: sin esto la IA asume la unidad por el nombre y falla en silencio.
            if (! empty($c['nota'])) {
                $linea .= " — IMPORTANTE: {$c['nota']}";
            }
            $lineas[] = $linea;
        }

        $calculadas = array_values(array_filter(array_map(
            static fn (array $c): ?string => ($c['tipo'] ?? '') === 'calculado' ? (string) ($c['id'] ?? '') : null,
            $columnas,
        )));
        if ($calculadas !== []) {
            $lineas[] = 'Estas columnas las calcula el Excel con fórmula — NO las llenes, déjalas tal cual están: ' . implode(', ', $calculadas);
        }

        if ($subtipo === 'jerarquica') {
            $capa = $agrupador ? ' (con una capa adicional al inicio para el título de grupo)' : '';
            $lineas[] = 'Es una tabla jerárquica: "valor" es un árbol de nodos {"value": <texto>, "children": [...]}.' .
                " Cada profundidad del árbol corresponde, en orden, a una de las columnas listadas arriba{$capa}." .
                ' Copia EXACTAMENTE la misma cantidad de nodos "children" en cada nivel que el árbol de abajo — solo cambia los "value" vacíos o incorrectos, nunca la forma del árbol.' .
                ' El número de nodos raíz y de hijos por nodo ya está fijado por la estructura del formato oficial — NO agregues un nodo nuevo por cada opción de catálogo que veas: sigue habiendo un único "value" por nodo existente, aunque la lista de opciones válidas tenga varios elementos.';
        } elseif ($agrupador) {
            $lineas[] = 'Es una tabla de filas agrupadas en bloques con título: "valor" es un array de bloques {"grupo": "<título, no lo cambies>", "filas": [ {mismas claves de columna que arriba}, ... ]}.' .
                ' No agregues ni quites bloques ni filas dentro de cada bloque.';
        } else {
            $lineas[] = 'Es una tabla de filas simples: "valor" es un array de objetos, cada uno con EXACTAMENTE las claves de columna listadas arriba. No cambies la cantidad de filas.';
        }

        if ($valorReferencia !== null) {
            $lineas[] = 'Ejemplo de referencia — así quedó esta MISMA tabla ya completada correctamente en OTRO proyecto (mírala para entender la FORMA y el estilo esperado en cada columna; los VALORES deben salir de la fuente de la verdad de ESTE proyecto, no copies estos literalmente):' .
                "\n" . json_encode($valorReferencia, JSON_UNESCAPED_UNICODE);
        }
        $lineas[] = "Tabla actual (completa lo vacío con evidencia real, corrige solo si hay evidencia mejor, conserva lo que ya esté bien):\n" . json_encode($valorActual, JSON_UNESCAPED_UNICODE);
        $lineas[] = 'Responde solo {"valor": <la misma estructura, con los valores llenados>}.';

        return implode("\n", $lineas);
    }

    /**
     * Compara la forma de la respuesta del modelo contra la tabla actual y sanea/rechaza. No acepta
     * en silencio una forma parcialmente incorrecta: cualquier discrepancia estructural rechaza toda la
     * respuesta con un motivo específico.
     *
     * @param array<string,list<string>> $catalogoPorColumna id de columna -> opciones válidas.
     * @param array<int,string> $columnaIdPorProfundidad profundidad del árbol (0-based) -> id de
     *   columna, para validar catálogo también en tablas jerárquicas (ver llenarTabla()).
     * @param list<string> $columnasConSubcolumnas ver compararForma().
     * @return array{valido: bool, motivo?: string, valorSaneado?: mixed, advertencias: list<string>}
     */
    private function validarFormaTabla(mixed $actual, mixed $propuesto, array $columnasCalculadas, bool $ultimaColumnaCalculada, array $catalogoPorColumna = [], array $columnaIdPorProfundidad = [], array $columnasConSubcolumnas = []): array
    {
        $advertencias = [];
        try {
            $saneado = $this->compararForma($actual, $propuesto, $columnasCalculadas, $ultimaColumnaCalculada, 'valor', $advertencias, $catalogoPorColumna, $columnaIdPorProfundidad, 0, $columnasConSubcolumnas);
        } catch (\RuntimeException $e) {
            return ['valido' => false, 'motivo' => $e->getMessage(), 'advertencias' => []];
        }

        return ['valido' => true, 'valorSaneado' => $saneado, 'advertencias' => $advertencias];
    }

    /** Compara ignorando mayúsculas/acentos, para no rechazar un "Si" que en realidad es "Sí". Mismo
     * criterio que normalizarOpcion() en xlsxListas.ts (frontend) — deben quedar equivalentes. */
    private function normalizarOpcion(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $transliterado = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        // iconv//TRANSLIT en este entorno no transforma "í"/"ó"/etc. directo a la letra base: deja un
        // apóstrofo delante ("í" -> "'i"). Sin quitarlo, "Sí" y "Si" normalizan distinto y dejan de
        // matchear — bug real encontrado probando UbigeoResolver (ver App\Libraries\UbigeoResolver),
        // que comparte este mismo criterio de normalización.
        return $transliterado !== false ? str_replace("'", '', $transliterado) : $s;
    }

    /** Nodo de árbol jerárquico genérico {value, children} — el orden de las claves en el JSON del
     * modelo no está garantizado, así que se comprueba por presencia, no por igualdad de array. */
    private function esNodoDeArbol(mixed $v): bool
    {
        return is_array($v) && count($v) === 2 && array_key_exists('value', $v) && array_key_exists('children', $v);
    }

    /**
     * Recorre en paralelo el valor actual y la respuesta del modelo exigiendo la misma forma exacta
     * (mismo tipo en cada posición, mismas claves en objetos, misma longitud en arrays) — subtipo-
     * agnóstico: sirve igual para filas_dinamicas y jerarquica, con o sin agrupador. Solo los valores
     * hoja (string/número/booleano) pueden diferir del original.
     *
     * @param list<string> $advertencias
     */
    /**
     * @param array<string,list<string>> $catalogoPorColumna id de columna -> opciones válidas.
     * @param array<int,string> $columnaIdPorProfundidad profundidad del árbol (0-based) -> id de
     *   columna — permite validar catálogo también en el "value" de un nodo jerárquico, que no trae
     *   su id de columna al lado (a diferencia de filas_dinamicas, `{col_id: valor}`).
     * @param int $profundidad profundidad actual dentro de un árbol jerárquico (0 = raíz). Solo se usa
     *   cuando el nodo actual es un nodo de árbol; en cualquier otra forma no significa nada.
     * @param list<string> $columnasConSubcolumnas ids de columna con `subcolumnas` (ej. "%" de
     *   4.01.01) — si la celda actual de esa columna YA está en forma de objeto, se fuerza siempre al
     *   valor original (ver el porqué en la rama de abajo). Distinto de $columnasCalculadas: esa lista
     *   es para columnas 100% calculadas SIEMPRE; esta es para columnas donde la mayoría de filas son
     *   calculadas pero alguna fila específica (forma de texto plano, no de objeto) sigue editable.
     */
    private function compararForma(mixed $actual, mixed $propuesto, array $columnasCalculadas, bool $ultimaColumnaCalculada, string $ruta, array &$advertencias, array $catalogoPorColumna = [], array $columnaIdPorProfundidad = [], int $profundidad = 0, array $columnasConSubcolumnas = []): mixed
    {
        if (is_array($actual) && array_is_list($actual)) {
            if (! is_array($propuesto) || ! array_is_list($propuesto)) {
                throw new \RuntimeException("En \"{$ruta}\": se esperaba una lista y la IA devolvió otra cosa.");
            }
            if (count($propuesto) !== count($actual)) {
                throw new \RuntimeException("En \"{$ruta}\": se esperaban " . count($actual) . ' elementos, la IA devolvió ' . count($propuesto) . '.');
            }
            // OJO: esta rama NUNCA recorre un "children" de árbol (esos se manejan aparte, en la rama
            // de nodo de árbol de abajo, que sí incrementa la profundidad) — aquí solo llegan la lista
            // de filas de nivel superior de la tabla (sean nodos raíz de un árbol o filas planas), que
            // están a la MISMA profundidad que el nodo que las contiene. Incrementar acá también sería
            // contar el nivel dos veces para las hojas de un árbol.
            $out = [];
            foreach ($actual as $i => $item) {
                $out[] = $this->compararForma($item, $propuesto[$i], $columnasCalculadas, $ultimaColumnaCalculada, "{$ruta}[{$i}]", $advertencias, $catalogoPorColumna, $columnaIdPorProfundidad, $profundidad, $columnasConSubcolumnas);
            }

            return $out;
        }

        if ($this->esNodoDeArbol($actual)) {
            // Nodo de árbol jerárquico {value, children}: en la práctica el modelo a veces omite
            // "children" en las hojas en vez de devolver un array vacío — equivalente en significado
            // (sin hijos), así que se normaliza antes de comparar en vez de rechazar una respuesta por
            // lo demás correcta.
            if (is_array($propuesto) && array_key_exists('value', $propuesto) && ! array_key_exists('children', $propuesto)) {
                $propuesto['children'] = [];
            }
            if (! $this->esNodoDeArbol($propuesto)) {
                throw new \RuntimeException("En \"{$ruta}\": se esperaba un nodo {value, children}.");
            }

            // Si es una hoja (sin hijos) y la ÚLTIMA columna configurada es `calculado`, este nodo
            // representa esa columna — se fuerza siempre al valor original, igual que con columnas
            // calculadas nombradas en filas_dinamicas.
            if ($actual['children'] === [] && $ultimaColumnaCalculada) {
                return ['value' => $actual['value'], 'children' => []];
            }

            $valorPropuesto = $propuesto['value'];
            if (is_array($valorPropuesto)) {
                throw new \RuntimeException("En \"{$ruta}.value\": se esperaba un valor simple y la IA devolvió una estructura.");
            }
            $valorSaneado = $this->sanearHoja($valorPropuesto, $actual['value']);

            // Catálogo por PROFUNDIDAD (no hay id de columna al lado de "value" en un árbol — se
            // deduce de a qué columna corresponde este nivel, ver columnaIdPorProfundidad en llenarTabla()).
            $colId    = $columnaIdPorProfundidad[$profundidad] ?? null;
            $opciones = $colId !== null ? ($catalogoPorColumna[$colId] ?? null) : null;
            if (is_array($opciones) && $valorSaneado !== '') {
                if ($valorSaneado !== $actual['value']) {
                    // La IA propuso un cambio: si no calza con el catálogo, es un error NUEVO de esta
                    // respuesta — se rechaza toda la tabla (como antes).
                    $valorSaneado = $this->matchearOpcion($valorSaneado, $opciones, "{$ruta}.value");
                } else {
                    // Bug real encontrado en vivo (08.03.1 "¿Se incluye como parte del PI?"): el valor
                    // YA VENÍA MAL de antes (placeholder tipo "Sí" en vez de "Se incluye en el PI") y,
                    // como la IA lo devolvía sin tocar, este chequeo se saltaba SIEMPRE — el catálogo
                    // nunca llegaba a validarse para esa celda, sin importar cuántas veces se regenerara.
                    // No corresponde rechazar la tabla entera por algo que la IA no propuso, pero tampoco
                    // hay que dejarlo pasar en silencio (rompería el VLOOKUP del Excel) — se intenta
                    // normalizar (acentos/mayúsculas) y, si no calza con nada, se vacía y se avisa.
                    $canonico = $this->matchearOpcionOpcional($valorSaneado, $opciones);
                    if ($canonico !== null) {
                        $valorSaneado = $canonico;
                    } else {
                        $advertencias[] = "\"{$ruta}.value\" tenía \"{$valorSaneado}\", que no es una de las opciones válidas (" . implode(' | ', $opciones) . ') — se vació para que lo elijas manualmente.';
                        $valorSaneado = '';
                    }
                }
            }

            // Mismo chequeo que la rama de lista de arriba (líneas 810-811), pero para "children" de un
            // nodo de árbol — sin esto, un árbol con menos hijos de los esperados en algún nivel (ej.
            // la IA se saltó un nodo intermedio) hacía que PHP intentara leer un índice inexistente de
            // $propuesto['children'] más abajo en el foreach, lo que CI4 convierte en una excepción NO
            // controlada (500 crudo, sin mensaje útil) en vez del 422 con motivo claro que da cualquier
            // otro rechazo de forma. Encontrado en vivo (2026-08-19, tabla 18.01.1 Marco Lógico):
            // "Undefined array key 0" en compararForma(), ruta valor[8].children[0].children[4].children[0].
            if (! is_array($propuesto['children'] ?? null) || count($propuesto['children']) !== count($actual['children'])) {
                $cantidadRecibida = is_array($propuesto['children'] ?? null) ? count($propuesto['children']) : 0;
                throw new \RuntimeException("En \"{$ruta}.children\": se esperaban " . count($actual['children']) . " elementos, la IA devolvió {$cantidadRecibida}.");
            }
            $children = [];
            foreach ($actual['children'] as $i => $hijo) {
                $children[] = $this->compararForma($hijo, $propuesto['children'][$i], $columnasCalculadas, $ultimaColumnaCalculada, "{$ruta}.children[{$i}]", $advertencias, $catalogoPorColumna, $columnaIdPorProfundidad, $profundidad + 1, $columnasConSubcolumnas);
            }

            return ['value' => $valorSaneado, 'children' => $children];
        }

        if (is_array($actual)) {
            if (! is_array($propuesto) || array_is_list($propuesto)) {
                throw new \RuntimeException("En \"{$ruta}\": se esperaba un objeto y la IA devolvió otra cosa.");
            }
            $clavesEsperadas = array_keys($actual);
            $clavesRecibidas = array_keys($propuesto);
            $faltantes       = array_diff($clavesEsperadas, $clavesRecibidas);
            $inventadas      = array_diff($clavesRecibidas, $clavesEsperadas);
            if ($faltantes !== []) {
                throw new \RuntimeException("En \"{$ruta}\": faltan las claves: " . implode(', ', $faltantes) . '.');
            }
            if ($inventadas !== []) {
                throw new \RuntimeException("En \"{$ruta}\": la IA inventó claves que no existen: " . implode(', ', $inventadas) . '.');
            }
            $out = [];
            foreach ($actual as $clave => $valor) {
                if (in_array((string) $clave, $columnasCalculadas, true)) {
                    $out[$clave] = $valor; // columna calculada: siempre el valor original
                    continue;
                }
                // Columna con subcolumnas (ej. "%" de 4.01.01: {parte1, parte2}) cuya celda YA está
                // en forma de objeto: en el Excel real esto es una fórmula que divide contra la fila
                // de arriba (verificado en vivo en el .xlsx real, ej. J9 "=+IF(I8>0,I9/I8,"")") — la
                // IA no tiene forma de saber ese cálculo y proponía un porcentaje inventado (llegó a
                // devolver "200%"). Se fuerza el valor original completo (ambas subcolumnas) igual
                // que una columna calculada de verdad. La fila EXCEPCIÓN de esa misma columna (texto
                // plano en vez de objeto, ej. la fila de población TOTAL con una fuente/nota) no entra
                // aquí — sigue siendo editable por la IA, ver la rama de abajo.
                if (in_array((string) $clave, $columnasConSubcolumnas, true) && is_array($valor)) {
                    $out[$clave] = $valor;
                    continue;
                }
                $resultado = $this->compararForma($valor, $propuesto[$clave], $columnasCalculadas, $ultimaColumnaCalculada, "{$ruta}.{$clave}", $advertencias, $catalogoPorColumna, $columnaIdPorProfundidad, $profundidad, $columnasConSubcolumnas);

                $opciones = $catalogoPorColumna[$clave] ?? null;
                if (is_array($opciones) && is_string($resultado) && $resultado !== '') {
                    if ($resultado !== $valor) {
                        // La IA propuso un cambio: si no calza con el catálogo, es un error NUEVO de
                        // esta respuesta — se rechaza toda la tabla (como antes).
                        $resultado = $this->matchearOpcion($resultado, $opciones, "{$ruta}.{$clave}");
                    } else {
                        // Mismo bug que en la rama de árbol de arriba: si el valor YA venía mal de antes
                        // y la IA lo devuelve sin tocar, este chequeo se saltaba siempre. No corresponde
                        // rechazar la tabla entera por algo que la IA no propuso, pero tampoco dejarlo
                        // pasar en silencio — se normaliza si calza (acentos/mayúsculas) o se vacía y se
                        // avisa.
                        $canonico = $this->matchearOpcionOpcional($resultado, $opciones);
                        if ($canonico !== null) {
                            $resultado = $canonico;
                        } else {
                            $advertencias[] = "\"{$ruta}.{$clave}\" tenía \"{$resultado}\", que no es una de las opciones válidas (" . implode(' | ', $opciones) . ') — se vació para que lo elijas manualmente.';
                            $resultado = '';
                        }
                    }
                }

                $out[$clave] = $resultado;
            }

            return $out;
        }

        // Hoja: string/número/booleano/null.
        if (is_array($propuesto)) {
            throw new \RuntimeException("En \"{$ruta}\": se esperaba un valor simple y la IA devolvió una estructura.");
        }

        return $this->sanearHoja($propuesto, $actual);
    }

    /** Coerción de un valor hoja (string/número/booleano/null) al texto que se guarda — sin catálogo,
     * eso lo hace matchearOpcion() aparte porque no siempre aplica (no toda hoja tiene columna con catálogo). */
    private function sanearHoja(mixed $propuesto, mixed $actual): string
    {
        if ($propuesto === null) {
            return is_string($actual) ? $actual : ''; // no inventes null donde ya había texto; conserva lo que había
        }
        if (is_bool($propuesto)) {
            return $propuesto ? 'true' : 'false';
        }
        if (is_int($propuesto) || is_float($propuesto)) {
            return (string) $propuesto;
        }

        return trim((string) $propuesto);
    }

    /**
     * Verifica que `$valor` calce con alguna opción del catálogo (ignorando mayúsculas/acentos) y
     * devuelve el texto CANÓNICO exacto — o rechaza toda la tabla si no calza con ninguna, para no
     * guardar un valor que rompería el `VLOOKUP` del Excel en silencio.
     *
     * @param list<string> $opciones
     */
    private function matchearOpcion(string $valor, array $opciones, string $ruta): string
    {
        $normalizado = $this->normalizarOpcion($valor);
        foreach ($opciones as $opcion) {
            if ($this->normalizarOpcion($opcion) === $normalizado) {
                return $opcion;
            }
        }

        throw new \RuntimeException("En \"{$ruta}\": \"{$valor}\" no es una de las opciones válidas (" . implode(' | ', $opciones) . ').');
    }

    /**
     * Como matchearOpcion(), pero para un campo SUELTO de sección (no una celda de tabla): ahí no
     * tiene sentido tirar toda la sección por un solo campo que no calzó — se trata como
     * "no_encontrado" en vez de rechazar el resto de campos ya bien extraídos. Usado por
     * llenarFicha()/procesarResultadosLote() para los campos con `opciones` (ej. 3.06.11), que hasta
     * ahora el prompt le pasaba a la IA pero nada validaba la respuesta contra ellas.
     *
     * @param list<string> $opciones
     */
    private function matchearOpcionOpcional(string $valor, array $opciones): ?string
    {
        $normalizado = $this->normalizarOpcion($valor);
        foreach ($opciones as $opcion) {
            if ($this->normalizarOpcion($opcion) === $normalizado) {
                return $opcion;
            }
        }

        return null;
    }

    /**
     * Mapa id de columna -> opciones válidas, para matchearOpcion()/compararForma(). Incluye tanto
     * columnas `catalogo`/`catalogo_encadenado` (su `opciones` propia, o el override del Excel vivo
     * del cliente) como columnas `booleano` (sus dos `etiquetasBooleano` como catálogo de 2 valores)
     * — sin esto último, el prompt le pedía a la IA responder EXACTAMENTE "Sí" o "No" pero nada
     * rechazaba una respuesta como "Parcial" (encontrado en vivo, tabla 3.05.01 columna Sí/No).
     *
     * @param array<string,list<string>> $opcionesPorColumnaOverride ver $opcionesPorColumna en llenarTabla().
     * @return array<string,list<string>>
     */
    private function catalogoPorColumnaDe(array $columnas, array $opcionesPorColumnaOverride = []): array
    {
        $catalogo = [];
        foreach ($columnas as $c) {
            $id = (string) ($c['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $etiquetasBooleano = $c['etiquetasBooleano'] ?? null;
            if (($c['tipo'] ?? '') === 'booleano' && is_array($etiquetasBooleano) && isset($etiquetasBooleano['true'], $etiquetasBooleano['false'])) {
                $catalogo[$id] = [(string) $etiquetasBooleano['true'], (string) $etiquetasBooleano['false']];
                continue;
            }
            $opciones = $opcionesPorColumnaOverride[$id] ?? $c['opciones'] ?? null;
            if (is_array($opciones) && $opciones !== []) {
                $catalogo[$id] = $opciones;
            }
        }

        return $catalogo;
    }

    /**
     * Ids de columna que declaran `subcolumnas` (ej. "%" de 4.01.01: {parte1, parte2}) — ver
     * compararForma(), que fuerza el valor original cuando la celda actual de esa columna ya está en
     * forma de objeto (verificado en vivo contra el .xlsx real: esa celda es una fórmula de Excel que
     * divide contra la fila de arriba, no algo que la IA deba calcular).
     *
     * @return list<string>
     */
    private function columnasConSubcolumnasDe(array $columnas): array
    {
        $ids = [];
        foreach ($columnas as $c) {
            if (! empty($c['subcolumnas']) && is_array($c['subcolumnas'])) {
                $ids[] = (string) ($c['id'] ?? '');
            }
        }

        return $ids;
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

    /**
     * Devuelve el prompt de sistema partido en dos bloques para el caché de Anthropic (ver
     * llamarClaudeCrudo): 'cacheable' es todo lo que NO cambia entre secciones de un mismo ejemplo
     * (instrucciones fijas + reglas + contextos generales + fuente de la verdad — el bloque grande,
     * ~65-85k tokens) y 'variable' es el contexto de ESTA sección (chico, cambia en cada llamada).
     * Antes iban concatenados en un solo string con contextoSeccion ANTES de fuenteVerdad — como el
     * caché de prompt solo sirve si el prefijo es idéntico byte a byte, cada cambio de sección
     * invalidaba el bloque completo (incluida la fuente de la verdad, que no había cambiado),
     * forzando una reescritura de caché (~1.25x el precio) en cada sección en vez de una lectura
     * (~0.1x) — confirmado en logs reales: ráfaga de 11 llamadas seguidas con "0 leídos" cada una,
     * una por sección distinta. Separar el contexto de sección en su propio bloque SIN cache_control
     * deja el bloque grande cacheable de verdad a través de TODA la ficha, no solo dentro de una
     * misma sección.
     *
     * @return array{cacheable: string, variable: string}
     */
    private function construirSistema(string $reglas, array $generales, array $contextoSeccion, string $fuenteVerdad): array
    {
        $partes = [
            'Eres un asistente experto en formulación de proyectos de inversión pública del Perú (Invierte.pe) que llena fichas técnicas oficiales del MEF a partir de la información real de un proyecto.',
            'Respondes SIEMPRE con un único objeto JSON válido (sin markdown). Contrato de salida de ESTA llamada:',
            // Mismo enum de estados que acepta normalizarPropuesta() y que usa el "Prompt del
            // sistema" sembrado (contexto general) — desincronizados antes: ese contrato listaba
            // "calculado" como estado válido y este no, aunque el parser ya lo soportaba.
            '{"seccion_id":"<id>","campos":[{"id":"<identificador>","valor_propuesto":"<texto o null>","estado":"extraido|inferido|requiere_confirmacion|no_encontrado|conflictivo|calculado","confianza":0.0,"fuente":"...","evidencia":"..."}]}',
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
        $partes[] = "Fuente de la verdad (información real del proyecto, cargada por el cliente):\n{$fuenteVerdad}";

        $variable = [];
        foreach ($contextoSeccion as $nombre => $texto) {
            $variable[] = "Contexto de esta sección — {$nombre}:\n{$texto}";
        }

        return ['cacheable' => implode("\n\n", $partes), 'variable' => implode("\n\n", $variable)];
    }

    /** @param array<string,string> $valorReferencia identificador => valor, del ejemplo marcado como
     *   referencia para IA (ver valoresEjemploReferencia) — solo los que caen en esta sección. */
    /** @param array<string,string> $otrasSeccionesConfirmadas ver valoresYaConfirmados() */
    private function construirPromptSeccion(string $seccionId, string $nombreSeccion, array $campos, array $valorReferencia = [], array $otrasSeccionesConfirmadas = []): string
    {
        $lineas = [
            "Llena los campos de la sección \"{$nombreSeccion}\" (seccion_id: {$seccionId}) según la fuente de la verdad.",
            'Devuelve el JSON con el arreglo "campos" usando los identificadores listados abajo.',
        ];
        if ($otrasSeccionesConfirmadas !== []) {
            $lineas[] = 'Valores YA CONFIRMADOS en otras secciones de esta misma ficha — mantente consistente con esto '
                . '(mismo nombre de entidad, mismo CIAI, mismo UBIGEO si aplica, etc.), no los repitas como si fueran nuevos:';
            foreach ($otrasSeccionesConfirmadas as $id => $valor) {
                $lineas[] = "- {$id}: {$valor}";
            }
        }
        $lineas[] = 'Campos disponibles:';
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
            if (in_array($c['tipo'] ?? '', ['numero', 'decimal'], true)) {
                // Mismo motivo que en construirPromptTabla(): la app lee la coma como separador
                // DECIMAL, no de miles — "2,450" se interpretó como 2.45 y rompió en cascada las
                // fórmulas del Excel que dependían de esa celda (encontrado en vivo, 4.01.01 "Cantidad").
                $linea .= ' — escribe el número SIN separador de miles (ej. "2450", nunca "2,450" ni "2.450"); si de verdad necesitas decimales, usa punto (ej. "2450.5").';
            }
            if (! empty($c['descripcion'])) {
                $linea .= " — {$c['descripcion']}";
            }
            $ref = $valorReferencia[$c['identificador']] ?? null;
            if ($ref !== null) {
                // Pistas de estilo, no el dato a copiar — de ahí el aviso explícito: sin él el modelo
                // tiende a repetir el valor literal de OTRO proyecto en vez de solo imitar la forma.
                $linea .= " — ejemplo de referencia (OTRO proyecto ya resuelto, NO copiar literal, solo la forma/estilo): \"{$ref}\"";
            }
            $lineas[] = $linea;
        }

        return implode("\n", $lineas);
    }

    /**
     * Persiste valores (y su fuente/procedencia, para el botón "?" del editor) del ejemplo.
     * - Sin $idsAfectados: reemplazo total (llenado de toda la ficha).
     * - Con $idsAfectados: limpia solo esos campos y fusiona los nuevos (llenado parcial por sección).
     *
     * @param array<string,string> $fuentesNuevas
     * @param list<string>|null $idsAfectados
     */
    private function guardarValores(int $ejemploId, array $valoresNuevos, array $fuentesNuevas, ?array $idsAfectados): void
    {
        $archivoModel = new ArchivoModel();
        $existente    = $archivoModel->where('propietario_tipo', 'ejemplo')->where('ejemplo_id', $ejemploId)->first();

        $previosValores = [];
        $previasFuentes = [];
        if ($existente && ! empty($existente['contenido_json'])) {
            $decoded        = json_decode((string) $existente['contenido_json'], true);
            $previosValores = is_array($decoded['valores'] ?? null) ? $decoded['valores'] : [];
            $previasFuentes = is_array($decoded['fuentes'] ?? null) ? $decoded['fuentes'] : [];
        }

        if ($idsAfectados === null) {
            $valores = $valoresNuevos;
            $fuentes = $fuentesNuevas;
        } else {
            foreach ($idsAfectados as $id) {
                unset($previosValores[$id], $previasFuentes[$id]);
            }
            $valores = array_merge($previosValores, $valoresNuevos);
            $fuentes = array_merge($previasFuentes, $fuentesNuevas);
        }

        $contenido = json_encode(['valores' => $valores, 'fuentes' => $fuentes], JSON_UNESCAPED_UNICODE);

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

    /**
     * Descarga el .md de Cloudinary; '' si no hay URL o la descarga falla.
     *
     * Cacheado 30 min: un llenado de ficha completa hace 1 petición HTTP por sección (para evitar el
     * timeout del gateway), y cada una vuelve a pedir los mismos contextos globales/generales — sin
     * caché eso es ~1 descarga redundante de los mismos .md por cada sección de la ficha.
     */
    private function contenidoDeUrl(?string $url): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }

        $clave = 'llenado_ia_md_' . md5($url);
        $cache = Services::cache();
        $cacheado = $cache->get($clave);
        if (is_string($cacheado)) {
            return $cacheado;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $cuerpo = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $texto = ($cuerpo !== false && $estado >= 200 && $estado < 300) ? trim((string) $cuerpo) : '';
        if ($texto !== '') {
            $cache->save($clave, $texto, 1800);
        }

        return $texto;
    }

    /**
     * Precio por millón de tokens (USD) — Google Gemini, agosto 2026 (ai.google.dev/gemini-api/docs/pricing,
     * nivel de pago). Los tokens de "thinking" se facturan al precio de salida, pero igual van con
     * thinkingBudget:0 (ver llamarModeloCrudo) para no gastar en razonamiento que este flujo no necesita.
     * Si el modelo configurado no está en esta lista, se usa el precio de Flash como referencia (evita
     * que el costo estimado quede en cero por un modelo no listado).
     */
    private const PRECIOS_GEMINI_POR_MTOK = [
        'gemini-2.5-pro'        => ['input' => 1.25, 'output' => 10.00],
        'gemini-2.5-flash'      => ['input' => 0.30, 'output' => 2.50],
        'gemini-2.5-flash-lite' => ['input' => 0.10, 'output' => 0.40],
    ];

    /**
     * @return array{valores: array<string,string>, estados: array<string,string>, confianza: array<string,float>, fuentes: array<string,string>, costoUsd: float}|null
     */
    /** @param array{cacheable: string, variable: string} $sistema */
    private function llamarModeloJson(object $config, array $sistema, string $usuario, ?string $etiqueta = null): ?array
    {
        // Cambiado a OpenAI (2026-08-19) — llamarClaudeCrudo()/llamarModeloCrudo() (Gemini) se dejan
        // intactas por si hace falta volver a swapear (ver nota en llenarTabla()). Los campos de texto
        // de sección usan el modelo barato siempre (no hay caso de cascada aquí, a diferencia de
        // llenarTabla()).
        $respuesta = $this->llamarOpenAICrudo($config, $sistema, $usuario, 8000, 120, $config->openaiModeloLlenado, $etiqueta);
        if ($respuesta === null) {
            return null;
        }
        $normalizado = $this->normalizarPropuesta($respuesta['valor']);
        $normalizado['costoUsd'] = $respuesta['costoUsd'];

        return $normalizado;
    }

    /**
     * Llama a la API generateContent de Gemini y devuelve el objeto JSON crudo que propuso el modelo,
     * sin ninguna normalización específica de flujo (la usan tanto el llenado de sección — texto plano
     * vía normalizarPropuesta — como el llenado de una tabla, que necesita el objeto tal cual para
     * validar su forma) — junto con el costo estimado de esta consulta.
     *
     * A diferencia de Anthropic/OpenAI, la key va como query param `?key=` (no header), y se le pide
     * salida JSON estructurada nativa (`responseMimeType: application/json`) en vez de coaccionarla por
     * prompt. `thinkingConfig.thinkingBudget: 0` apaga el razonamiento — este flujo es extracción
     * determinista contra un schema fijo, no necesita "pensar", y el thinking se factura como salida.
     *
     * @param array{cacheable: string, variable: string} $sistema Mismo split que llamarClaudeCrudo()
     *   (ver construirSistema()) — Gemini no tiene un cache_control explícito como Anthropic en este
     *   flujo, así que acá los dos bloques simplemente se concatenan de vuelta en un solo texto; el
     *   split existe para que ambos proveedores compartan la misma firma y no haya que bifurcar
     *   construirSistema()/construirSistemaTabla() por proveedor.
     * @param string|null $etiqueta identificador de sección/tabla, solo para que el log diga a qué
     *   llamada corresponde cada línea (mismo motivo que en llamarClaudeCrudo()).
     * @return array{valor: array, usage: array, costoUsd: float}|null
     */
    private function llamarModeloCrudo(object $config, array $sistema, string $usuario, int $maxTokens, int $timeout, ?string $etiqueta = null): ?array
    {
        $sistemaTexto = $sistema['variable'] !== '' ? "{$sistema['cacheable']}\n\n{$sistema['variable']}" : $sistema['cacheable'];
        $url  = $config->geminiEndpoint . '/' . $config->geminiModelo . ':generateContent?key=' . $config->geminiApiKey;
        $body = json_encode([
            'contents'          => [
                ['role' => 'user', 'parts' => [['text' => $usuario]]],
            ],
            'systemInstruction' => ['parts' => [['text' => $sistemaTexto]]],
            'generationConfig'  => [
                'maxOutputTokens'  => $maxTokens,
                'responseMimeType' => 'application/json',
                'thinkingConfig'   => ['thinkingBudget' => 0],
            ],
        ]);

        // La API key de este proyecto tiene un tope de 20 requests/DÍA para gemini-2.5-flash mientras
        // el proyecto de Google Cloud no tenga facturación habilitada (confirmado leyendo
        // error.details[].quotaId de un 429 real: "GenerateRequestsPerDayPerProjectPerModel-FreeTier"
        // — el mensaje de texto libre solo dice "limit: 20" sin aclarar que es diario, no por minuto).
        // Este reintento con backoff NO resuelve ese límite diario (ver docs/llenado-automatico-ia.md,
        // sección 5) — sigue siendo correcto tenerlo porque evita que un 429 cualquiera (los límites
        // por minuto normales, una vez habilitada la facturación) tumbe la petición con un fatal error
        // de PHP en vez de fallar limpio. Gemini sugiere cuánto esperar en el cuerpo del error
        // ("Please retry in 2.29s"); se usa ese valor (+ margen) en vez de un backoff a ciegas.
        //
        // OJO: `$timeout` es el presupuesto TOTAL de esta llamada — reintentos incluidos — no el
        // timeout de cada intento por separado. Un primer intento con `CURLOPT_TIMEOUT => $timeout`
        // ya puede tardar hasta $timeout segundos; permitir 4 intentos de $timeout cada uno podía
        // sumar 4× ese tiempo y superar el set_time_limit() del método que llama a esto — reproducido
        // en vivo: un 500 con cuerpo vacío a los 150.4s, exactamente el set_time_limit() de
        // llenarTabla(). Por eso cada intento usa el tiempo que QUEDA del presupuesto total, y se deja
        // de reintentar (se falla limpio con null, nunca con un fatal error de PHP) en cuanto no
        // alcanza ni para un intento mínimo.
        $limiteAbsoluto    = microtime(true) + $timeout;
        $margenMinimo      = 5.0; // segundos — bajo esto no vale la pena intentar otra vez
        $intentosMax       = 4;
        for ($intento = 1; $intento <= $intentosMax; $intento++) {
            $restante = $limiteAbsoluto - microtime(true);
            if ($restante < $margenMinimo) {
                log_message('warning', '[llenado-ia] Sin presupuesto de tiempo para otro intento (quedan {restante}s) — se da por fallida.', [
                    'restante' => round($restante, 1),
                ]);

                return null;
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_TIMEOUT        => (int) ceil($restante),
                CURLOPT_HTTPHEADER     => ['content-type: application/json'],
            ]);
            $cuerpo    = curl_exec($ch);
            $estado    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errorCurl = curl_error($ch);
            curl_close($ch);

            if ($estado === 429 && $intento < $intentosMax) {
                $espera = $this->esperaSugeridaPor429(is_string($cuerpo) ? $cuerpo : '') ?? (2 ** $intento);
                if ($limiteAbsoluto - microtime(true) - $espera < $margenMinimo) {
                    log_message('warning', '[llenado-ia] Gemini 429 y no queda presupuesto para esperar {espera}s más — se da por fallida.', ['espera' => $espera]);

                    return null;
                }
                log_message('warning', '[llenado-ia] Gemini 429 (intento {intento}/{max}) — reintentando en {espera}s.', [
                    'intento' => $intento, 'max' => $intentosMax, 'espera' => $espera,
                ]);
                usleep((int) ($espera * 1_000_000));
                continue;
            }

            if ($cuerpo === false || $estado < 200 || $estado >= 300) {
                log_message('error', '[llenado-ia] Gemini ({etiqueta}) respondió {estado}: {cuerpo} {curl}', [
                    'etiqueta' => $etiqueta ?? '?',
                    'estado' => $estado,
                    'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : '(sin cuerpo)',
                    'curl'   => $errorCurl,
                ]);

                return null;
            }

            break;
        }

        $json = json_decode((string) $cuerpo, true);

        $candidato    = $json['candidates'][0] ?? null;
        $razonParada  = $candidato['finishReason'] ?? null;
        if (in_array($razonParada, ['SAFETY', 'RECITATION', 'PROHIBITED_CONTENT', 'BLOCKLIST'], true)) {
            log_message('warning', '[llenado-ia] Gemini ({etiqueta}) rechazó la solicitud ({razon}): {detalle}', [
                'etiqueta' => $etiqueta ?? '?',
                'razon'   => $razonParada,
                'detalle' => json_encode($candidato['safetyRatings'] ?? null, JSON_UNESCAPED_UNICODE),
            ]);

            return null;
        }

        $texto = '';
        foreach ($candidato['content']['parts'] ?? [] as $parte) {
            $texto .= (string) ($parte['text'] ?? '');
        }
        $valor = json_decode($this->limpiarCercoMarkdown($texto), true);

        if (! is_array($valor)) {
            log_message('error', '[llenado-ia] Gemini ({etiqueta}) devolvió JSON no parseable: {texto}', [
                'etiqueta' => $etiqueta ?? '?',
                'texto' => substr($texto, 0, 500),
            ]);

            return null;
        }

        $usage = is_array($json['usageMetadata'] ?? null) ? $json['usageMetadata'] : [];

        // Vista previa de lo que realmente propuso el modelo — sin esto, una respuesta "válida" pero
        // vacía o rara (ej. todas las celdas en "") es indistinguible en el log de una bien llenada:
        // ambas pasan por aquí sin error ni advertencia. Encontrado en vivo el 2026-08-19: 2.01.01
        // pareció "no llenarse" y no había forma de saber, solo con el log, si el modelo propuso vacío
        // de verdad o si algo posterior (validación, resolución de UBIGEO) descartó una respuesta buena.
        log_message('debug', '[llenado-ia] Gemini ({etiqueta}) propuso: {preview}', [
            'etiqueta' => $etiqueta ?? '?',
            'preview'  => mb_substr(json_encode($valor, JSON_UNESCAPED_UNICODE), 0, 1000),
        ]);

        return [
            'valor'    => $valor,
            'usage'    => $usage,
            'costoUsd' => $this->registrarUso((string) $config->geminiModelo, $usage, $etiqueta),
        ];
    }

    /**
     * Igual que llamarModeloCrudo() pero contra la Messages API de Anthropic (Claude), vía cURL
     * crudo — no hay SDK de Anthropic instalado en este backend, y el resto del controlador ya llama
     * a Gemini así, así que se mantiene el mismo patrón en vez de sumar una dependencia nueva para
     * una prueba puntual (2026-08-18: cuota de Gemini agotada por hoy, IA key de Anthropic ya
     * configurada con presupuesto real — ver [[proyectafacil-llenado-ia-gemini-quota]]).
     *
     * `thinking` va explícitamente desactivado: esto es extracción determinista contra un schema fijo
     * (mismo motivo que thinkingBudget:0 en Gemini) y esta llamada nunca usa tools, así que no aplica
     * ninguno de los 2 modos de falla documentados de thinking desactivado en Opus 5 (fugas de tool
     * call como texto plano / tags de thinking) — de todas formas se usa Sonnet 5, no Opus 5.
     *
     * @return array{valor: array, usage: array, costoUsd: float}|null
     */
    /**
     * @param array{cacheable: string, variable: string} $sistema
     * @param string|null $etiqueta identificador de sección/tabla, solo para que el log diga a qué
     *   llamada corresponde cada línea — antes no había forma de saber, viendo el log, cuál llamada
     *   fue por cuál campo, y eso hizo imposible diagnosticar en vivo por qué una tabla puntual
     *   (2.01.01) parecía no llenarse sin adivinar por patrones de tokens.
     */
    private function llamarClaudeCrudo(object $config, array $sistema, string $usuario, int $maxTokens, int $timeout, ?string $modelo = null, ?string $etiqueta = null): ?array
    {
        $modelo = $modelo ?? $config->modeloLlenado; // default: claude-haiku-4-5 — extracción determinista, no necesita Sonnet
        $url    = $config->endpoint; // https://api.anthropic.com/v1/messages
        // Prompt caching en DOS bloques: `cacheable` (reglas + contextos generales + fuente de la
        // verdad, ~65-85k tokens) es IDÉNTICO en todas las llamadas de un mismo ejemplo sin importar
        // la sección/tabla — lleva el cache_control. `variable` (contexto de la sección actual, chico)
        // va en un bloque aparte SIN cache_control, para que cambiar de sección no invalide el bloque
        // grande. Antes iban concatenados en un solo string con el contexto de sección ANTES de la
        // fuente de la verdad: cualquier cambio de sección invalidaba TODO el bloque (incluida la
        // fuente de la verdad, que no había cambiado) y forzaba una reescritura completa de caché
        // (~1.25x el precio) en cada sección distinta — confirmado en logs reales: ráfaga de 11
        // llamadas seguidas, cada una con "0 leídos" de caché, una por sección/tabla distinta. Sin
        // ningún breakpoint, cada llamada paga el precio completo (~$0.17/llamada, ~$19-20 por ficha
        // completa de ~112 llamadas); con el split, el bloque grande cachea de verdad A TRAVÉS de toda
        // la ficha (no solo dentro de una sección), bajando el costo por llamada a ~$0.01-0.03 una vez
        // que el caché está escrito, sin importar en qué sección/tabla se esté.
        $systemBlocks = [
            ['type' => 'text', 'text' => $sistema['cacheable'], 'cache_control' => ['type' => 'ephemeral']],
        ];
        if (($sistema['variable'] ?? '') !== '') {
            $systemBlocks[] = ['type' => 'text', 'text' => $sistema['variable']];
        }
        $body = json_encode([
            'model'      => $modelo,
            'max_tokens' => $maxTokens,
            'system'     => $systemBlocks,
            'thinking'   => ['type' => 'disabled'],
            'messages'   => [
                ['role' => 'user', 'content' => $usuario],
            ],
        ]);

        $limiteAbsoluto = microtime(true) + $timeout;
        $margenMinimo   = 5.0;
        $intentosMax    = 4;
        for ($intento = 1; $intento <= $intentosMax; $intento++) {
            $restante = $limiteAbsoluto - microtime(true);
            if ($restante < $margenMinimo) {
                log_message('warning', '[llenado-ia] Claude: sin presupuesto de tiempo para otro intento (quedan {restante}s) — se da por fallida.', [
                    'restante' => round($restante, 1),
                ]);

                return null;
            }

            $cabecerasCrudas = [];
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_TIMEOUT        => (int) ceil($restante),
                CURLOPT_HTTPHEADER     => [
                    'content-type: application/json',
                    'x-api-key: ' . $config->anthropicApiKey,
                    'anthropic-version: ' . $config->anthropicVersion,
                ],
                // Anthropic manda el retry-after como header HTTP normal (no en el cuerpo, a
                // diferencia de Gemini) — hay que capturar headers para poder leerlo en un 429.
                CURLOPT_HEADERFUNCTION => function ($curl, $linea) use (&$cabecerasCrudas) {
                    $cabecerasCrudas[] = $linea;

                    return strlen($linea);
                },
            ]);
            $cuerpo    = curl_exec($ch);
            $estado    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errorCurl = curl_error($ch);
            curl_close($ch);

            if ($estado === 429 && $intento < $intentosMax) {
                $espera = $this->retryAfterDeHeaders($cabecerasCrudas) ?? (2 ** $intento);
                if ($limiteAbsoluto - microtime(true) - $espera < $margenMinimo) {
                    log_message('warning', '[llenado-ia] Claude 429 y no queda presupuesto para esperar {espera}s más — se da por fallida.', ['espera' => $espera]);

                    return null;
                }
                log_message('warning', '[llenado-ia] Claude 429 (intento {intento}/{max}) — reintentando en {espera}s.', [
                    'intento' => $intento, 'max' => $intentosMax, 'espera' => $espera,
                ]);
                usleep((int) ($espera * 1_000_000));
                continue;
            }

            if ($cuerpo === false || $estado < 200 || $estado >= 300) {
                log_message('error', '[llenado-ia] Claude ({etiqueta}) respondió {estado}: {cuerpo} {curl}', [
                    'etiqueta' => $etiqueta ?? '?',
                    'estado' => $estado,
                    'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : '(sin cuerpo)',
                    'curl'   => $errorCurl,
                ]);

                return null;
            }

            break;
        }

        $json = json_decode((string) $cuerpo, true);

        $stopReason = $json['stop_reason'] ?? null;
        if ($stopReason === 'refusal') {
            log_message('warning', '[llenado-ia] Claude ({etiqueta}) rechazó la solicitud (stop_reason: refusal): {detalle}', [
                'etiqueta' => $etiqueta ?? '?',
                'detalle' => json_encode($json['stop_details'] ?? null, JSON_UNESCAPED_UNICODE),
            ]);

            return null;
        }

        $texto = '';
        foreach ($json['content'] ?? [] as $bloque) {
            if (($bloque['type'] ?? '') === 'text') {
                $texto .= (string) ($bloque['text'] ?? '');
            }
        }
        $valor = json_decode($this->limpiarCercoMarkdown($texto), true);
        $usage = is_array($json['usage'] ?? null) ? $json['usage'] : [];

        if (! is_array($valor)) {
            // Se registra el costo/caché igual que en un éxito — esta llamada se cobró aunque el
            // parseo haya fallado, y perder esa visibilidad hace más difícil depurar el próximo caso.
            $this->registrarUsoClaude($modelo, $usage, $etiqueta);
            // `stop_reason` ayuda a distinguir un corte por max_tokens de un stop temprano genuino del
            // modelo — encontrado en vivo un caso con $texto = "```json" (7 caracteres) y nada más, sin
            // forma de saber cuál de los dos fue sin este dato (antes no se registraba en este camino).
            log_message('error', '[llenado-ia] Claude ({etiqueta}) devolvió JSON no parseable (stop_reason: {razon}): {texto}', [
                'etiqueta' => $etiqueta ?? '?',
                'razon' => $stopReason ?? '(ninguno)',
                'texto' => substr($texto, 0, 500),
            ]);

            return null;
        }

        // Misma vista previa que llamarModeloCrudo() (Gemini) — ver el comentario ahí (2026-08-19).
        log_message('debug', '[llenado-ia] Claude ({etiqueta}) propuso: {preview}', [
            'etiqueta' => $etiqueta ?? '?',
            'preview'  => mb_substr(json_encode($valor, JSON_UNESCAPED_UNICODE), 0, 1000),
        ]);

        return [
            'valor'    => $valor,
            'usage'    => $usage,
            'costoUsd' => $this->registrarUsoClaude($modelo, $usage, $etiqueta),
        ];
    }

    /** Busca el header `retry-after` (segundos) en las líneas crudas capturadas de un 429 de Anthropic. */
    private function retryAfterDeHeaders(array $lineasCrudas): ?float
    {
        foreach ($lineasCrudas as $linea) {
            if (preg_match('/^retry-after:\s*(\d+(?:\.\d+)?)/i', trim($linea), $m) === 1) {
                return (float) $m[1];
            }
        }

        return null;
    }

    /**
     * Llama a la API Chat Completions de OpenAI y devuelve el objeto JSON crudo que propuso el
     * modelo (sin normalización específica de flujo — la usan tanto el llenado de sección, vía
     * llamarModeloJson()/normalizarPropuesta(), como el llenado de una tabla en llenarTabla(), que
     * necesita el objeto tal cual para validar su forma) — junto con el costo estimado de esta
     * consulta. Mismo contrato de retorno que llamarClaudeCrudo() (dormida, ver arriba) y
     * llamarModeloCrudo() (Gemini, dormida) — cualquiera de las tres es intercambiable en los call
     * sites de llenarFicha()/llenarTabla().
     *
     * Diferencias con Claude: el system prompt va como un mensaje más (`role: system`, primero en la
     * lista) en vez de un array de bloques con cache_control explícito — OpenAI cachea
     * automáticamente el prefijo repetido entre llamadas (sin anotación), así que basta con mandar
     * `$sistema['cacheable']` siempre en el mismo orden/contenido para beneficiarse igual; el ahorro
     * real aparece en `usage.prompt_tokens_details.cached_tokens` (ver registrarUsoOpenAI()). Se pide
     * `response_format: {type: json_object}` para forzar salida JSON válida (requiere que la palabra
     * "JSON" aparezca en el prompt — ya la piden construirSistema()/construirSistemaTabla() para los
     * otros proveedores, así que no hace falta agregarla aparte). `max_completion_tokens` es el
     * nombre del parámetro para modelos gpt-5 (max_tokens quedó deprecado con esta familia — mismo
     * parámetro que ya usa AsistenteIAController::llamarOpenAI() para el asesor).
     *
     * @param array{cacheable: string, variable: string} $sistema Mismo split que llamarClaudeCrudo().
     * @return array{valor: array, usage: array, costoUsd: float}|null
     */
    private function llamarOpenAICrudo(object $config, array $sistema, string $usuario, int $maxTokens, int $timeout, ?string $modelo = null, ?string $etiqueta = null): ?array
    {
        $modelo       = $modelo ?? $config->openaiModeloLlenado;
        $sistemaTexto = $sistema['variable'] !== '' ? "{$sistema['cacheable']}\n\n{$sistema['variable']}" : $sistema['cacheable'];
        $body = json_encode([
            'model'                 => $modelo,
            'max_completion_tokens' => $maxTokens,
            'response_format'       => ['type' => 'json_object'],
            'messages'              => [
                ['role' => 'system', 'content' => $sistemaTexto],
                ['role' => 'user', 'content' => $usuario],
            ],
        ]);

        $limiteAbsoluto = microtime(true) + $timeout;
        $margenMinimo   = 5.0;
        $intentosMax    = 4;
        for ($intento = 1; $intento <= $intentosMax; $intento++) {
            $restante = $limiteAbsoluto - microtime(true);
            if ($restante < $margenMinimo) {
                log_message('warning', '[llenado-ia] OpenAI: sin presupuesto de tiempo para otro intento (quedan {restante}s) — se da por fallida.', [
                    'restante' => round($restante, 1),
                ]);
                return null;
            }

            $cabecerasCrudas = [];
            $ch = curl_init($config->openaiEndpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_TIMEOUT        => (int) ceil($restante),
                CURLOPT_HTTPHEADER     => [
                    'content-type: application/json',
                    'authorization: Bearer ' . $config->openaiApiKey,
                ],
                CURLOPT_HEADERFUNCTION => function ($curl, $linea) use (&$cabecerasCrudas) {
                    $cabecerasCrudas[] = $linea;
                    return strlen($linea);
                },
            ]);
            $cuerpo    = curl_exec($ch);
            $estado    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errorCurl = curl_error($ch);
            curl_close($ch);

            if ($estado === 429 && $intento < $intentosMax) {
                $espera = $this->retryAfterDeHeaders($cabecerasCrudas) ?? (2 ** $intento);
                if ($limiteAbsoluto - microtime(true) - $espera < $margenMinimo) {
                    log_message('warning', '[llenado-ia] OpenAI 429 y no queda presupuesto para esperar {espera}s más — se da por fallida.', ['espera' => $espera]);
                    return null;
                }
                log_message('warning', '[llenado-ia] OpenAI 429 (intento {intento}/{max}) — reintentando en {espera}s.', [
                    'intento' => $intento, 'max' => $intentosMax, 'espera' => $espera,
                ]);
                usleep((int) ($espera * 1_000_000));
                continue;
            }

            if ($cuerpo === false || $estado < 200 || $estado >= 300) {
                log_message('error', '[llenado-ia] OpenAI ({etiqueta}) respondió {estado}: {cuerpo} {curl}', [
                    'etiqueta' => $etiqueta ?? '?',
                    'estado' => $estado,
                    'cuerpo' => is_string($cuerpo) ? substr($cuerpo, 0, 500) : '(sin cuerpo)',
                    'curl'   => $errorCurl,
                ]);
                return null;
            }

            break;
        }

        $json = json_decode((string) $cuerpo, true);

        return $this->procesarRespuestaChatOpenAI($json, $modelo, $etiqueta);
    }

    /**
     * Extrae y valida el objeto JSON propuesto por el modelo a partir de una respuesta YA RECIBIDA
     * de la API Chat Completions de OpenAI — separado de llamarOpenAICrudo() (que además hace la
     * llamada curl con reintentos) para que el mismo parseo sirva tanto ahí como al procesar los
     * resultados de un LOTE (Batch API): ahí la respuesta de cada línea llega ya completa desde el
     * archivo de resultados descargado, sin ninguna llamada HTTP que hacer en ese momento — ver
     * procesarLoteFicha().
     *
     * @return array{valor: array, usage: array, costoUsd: float}|null
     */
    private function procesarRespuestaChatOpenAI(array $json, string $modelo, ?string $etiqueta = null): ?array
    {
        $finishReason = $json['choices'][0]['finish_reason'] ?? null;
        if ($finishReason === 'content_filter') {
            log_message('warning', '[llenado-ia] OpenAI ({etiqueta}) rechazó la solicitud (finish_reason: content_filter).', [
                'etiqueta' => $etiqueta ?? '?',
            ]);
            return null;
        }

        $texto = (string) ($json['choices'][0]['message']['content'] ?? '');
        $valor = json_decode($this->limpiarCercoMarkdown($texto), true);
        $usage = is_array($json['usage'] ?? null) ? $json['usage'] : [];

        if (! is_array($valor)) {
            // Se registra el costo/caché igual que en un éxito — esta llamada se cobró aunque el
            // parseo haya fallado (mismo criterio que llamarClaudeCrudo()).
            $this->registrarUsoOpenAI($modelo, $usage, $etiqueta);
            log_message('error', '[llenado-ia] OpenAI ({etiqueta}) devolvió JSON no parseable (finish_reason: {razon}): {texto}', [
                'etiqueta' => $etiqueta ?? '?',
                'razon' => $finishReason ?? '(ninguno)',
                'texto' => substr($texto, 0, 500),
            ]);
            return null;
        }

        log_message('debug', '[llenado-ia] OpenAI ({etiqueta}) propuso: {preview}', [
            'etiqueta' => $etiqueta ?? '?',
            'preview'  => mb_substr(json_encode($valor, JSON_UNESCAPED_UNICODE), 0, 1000),
        ]);

        return [
            'valor'    => $valor,
            'usage'    => $usage,
            'costoUsd' => $this->registrarUsoOpenAI($modelo, $usage, $etiqueta),
        ];
    }

    /**
     * Precio por millón de tokens (USD) — OpenAI, precios de lanzamiento de la familia GPT-5
     * (platform.openai.com/docs/pricing) — verificar que sigan vigentes si ha pasado mucho tiempo
     * desde el 2026-08-19. Si el modelo configurado no está en esta lista, se usa el precio de
     * gpt-5-mini como referencia (evita que el costo estimado quede en cero por un modelo no listado).
     */
    private const PRECIOS_OPENAI_POR_MTOK = [
        'gpt-5'      => ['input' => 1.25, 'output' => 10.00],
        'gpt-5-mini' => ['input' => 0.25, 'output' => 2.00],
        'gpt-5-nano' => ['input' => 0.05, 'output' => 0.40],
    ];

    private function registrarUsoOpenAI(string $modelo, array $usage, ?string $etiqueta = null): float
    {
        $entrada = (int) ($usage['prompt_tokens'] ?? 0);
        $salida  = (int) ($usage['completion_tokens'] ?? 0);
        // Caché automático de OpenAI (sin cache_control explícito, a diferencia de Anthropic): los
        // tokens cacheados vienen incluidos DENTRO de prompt_tokens, no aparte — hay que restarlos del
        // precio normal de entrada y cobrarlos a su tarifa reducida (~10% del precio de input), o el
        // costo estimado saldría inflado contando esos tokens dos veces a precio completo.
        $cacheLectura  = (int) ($usage['prompt_tokens_details']['cached_tokens'] ?? 0);
        $entradaNormal = max(0, $entrada - $cacheLectura);
        $precios       = self::PRECIOS_OPENAI_POR_MTOK[$modelo] ?? self::PRECIOS_OPENAI_POR_MTOK['gpt-5-mini'];
        $costo = ($entradaNormal * $precios['input'] + $cacheLectura * $precios['input'] * 0.1 + $salida * $precios['output']) / 1_000_000;

        log_message('info', '[llenado-ia] Consulta ({etiqueta}) a {modelo}: {entrada} tok. entrada + {salida} tok. salida{cache} — costo estimado USD {costo}', [
            'etiqueta' => $etiqueta ?? '?',
            'modelo'  => $modelo,
            'entrada' => $entrada,
            'salida'  => $salida,
            'cache'   => $cacheLectura > 0 ? sprintf(' (caché: %d leídos)', $cacheLectura) : '',
            'costo'   => number_format($costo, 5),
        ]);

        return $costo;
    }

    /**
     * Precio por millón de tokens (USD) — Anthropic, agosto 2026 (platform.claude.com/docs/en/pricing).
     * Sonnet 5 tiene precio de lanzamiento vigente hasta 2026-08-31 ($2/$10 en vez de $3/$15) — hoy
     * (2026-08-18) todavía aplica. Revisar esta tabla si se sigue usando Claude después de esa fecha.
     * DORMIDA junto con llamarClaudeCrudo() desde el 2026-08-19 (ver Config\Ia) — el llenado con IA
     * usa OpenAI ahora; esto se conserva por si hay que volver a swapear.
     */
    private const PRECIOS_CLAUDE_POR_MTOK = [
        'claude-sonnet-5' => ['input' => 2.00, 'output' => 10.00],
        'claude-opus-5'   => ['input' => 5.00, 'output' => 25.00],
        'claude-haiku-4-5' => ['input' => 1.00, 'output' => 5.00],
    ];

    private function registrarUsoClaude(string $modelo, array $usage, ?string $etiqueta = null): float
    {
        $entrada      = (int) ($usage['input_tokens'] ?? 0);
        $salida       = (int) ($usage['output_tokens'] ?? 0);
        $cacheEscrita = (int) ($usage['cache_creation_input_tokens'] ?? 0);
        $cacheLectura = (int) ($usage['cache_read_input_tokens'] ?? 0);
        $precios      = self::PRECIOS_CLAUDE_POR_MTOK[$modelo] ?? self::PRECIOS_CLAUDE_POR_MTOK['claude-sonnet-5'];
        // Cache write ~1.25x el precio de input normal; cache read ~0.1x — ver el cache_control en
        // llamarClaudeCrudo(). $cacheLectura es la señal de que el caché SÍ está sirviendo: si sale
        // en 0 llamada tras llamada con el mismo `$sistema`, algo invalida el prefijo (ver
        // shared/prompt-caching.md § Silent invalidators de la skill claude-api).
        $costo = ($entrada * $precios['input'] + $cacheEscrita * $precios['input'] * 1.25 + $cacheLectura * $precios['input'] * 0.1 + $salida * $precios['output']) / 1_000_000;

        log_message('info', '[llenado-ia] Consulta ({etiqueta}) a {modelo}: {entrada} tok. entrada + {salida} tok. salida{cache} — costo estimado USD {costo}', [
            'etiqueta' => $etiqueta ?? '?',
            'modelo'  => $modelo,
            'entrada' => $entrada,
            'salida'  => $salida,
            'cache'   => ($cacheEscrita > 0 || $cacheLectura > 0)
                ? sprintf(' (caché: %d escritos, %d leídos)', $cacheEscrita, $cacheLectura)
                : '',
            'costo'   => number_format($costo, 5),
        ]);

        return $costo;
    }

    /** Extrae el "Please retry in 2.29s" del cuerpo de un 429 de Gemini — null si no viene ese hint
     * (algún otro RESOURCE_EXHAUSTED sin sugerencia de espera). */
    private function esperaSugeridaPor429(string $cuerpo): ?float
    {
        if (preg_match('/retry in ([\d.]+)s/i', $cuerpo, $m) === 1) {
            return (float) $m[1] + 0.3; // margen chico: el reloj de Gemini y el nuestro no son el mismo
        }

        return null;
    }

    /**
     * Salvaguarda: pese a pedir salida JSON estructurada, a veces igual llega una cerca de código, o
     * (encontrado en vivo con Claude) una nota explicativa en prosa ANTES del JSON — pasa cuando el
     * modelo detecta un conflicto real en la fuente de la verdad (ej. dos documentos con ubicaciones
     * distintas) y siente la necesidad de aclararlo, rompiendo el contrato de "solo JSON, sin texto
     * fuera" del prompt de sistema. Se extrae el primer bloque `{...}` balanceado del texto en vez de
     * asumir que el string entero ya es JSON.
     */
    private function limpiarCercoMarkdown(string $texto): string
    {
        $texto = trim($texto);
        if (str_starts_with($texto, '```')) {
            $texto = preg_replace('/^```[a-zA-Z]*\n?/', '', $texto) ?? $texto;
            $texto = preg_replace('/```\s*$/', '', trim($texto)) ?? $texto;
        }
        $texto = trim($texto);

        return str_starts_with($texto, '{') ? $texto : ($this->extraerBloqueJson($texto) ?? $texto);
    }

    /**
     * Prueba cada `{` del texto como posible inicio de un objeto JSON (balanceando llaves, respetando
     * strings/escapes) y se queda con el candidato MÁS LARGO que de verdad decodifica como JSON
     * válido — ni el primero ni el último bastan:
     *   - El primer `{...}` balanceado puede ser una llave suelta de la prosa de aclaración ("...(con
     *     { llaves } raras en prosa)... {"valor": [...]}") — no es JSON, se descarta al no decodificar.
     *   - El último candidato en orden de aparición puede ser un objeto ANIDADO dentro del real (cada
     *     elemento de un array es JSON válido por sí solo) — devolvía un fragmento interno en vez del
     *     objeto completo con "valor"/"estado". El objeto completo siempre es el más largo de los
     *     candidatos válidos, porque los contiene a todos como subcadena.
     */
    private function extraerBloqueJson(string $texto): ?string
    {
        $mejor  = null;
        $offset = 0;
        while (($inicio = strpos($texto, '{', $offset)) !== false) {
            $fin = $this->finDeBloqueBalanceado($texto, $inicio);
            if ($fin !== null) {
                $bloque = substr($texto, $inicio, $fin - $inicio + 1);
                if (json_decode($bloque, true) !== null && ($mejor === null || strlen($bloque) > strlen($mejor))) {
                    $mejor = $bloque;
                }
            }
            $offset = $inicio + 1;
        }

        return $mejor;
    }

    /** Índice de la `}` que cierra el `{` en `$inicio` (balanceando anidamiento, respetando
     * strings/escapes) — null si nunca cierra. */
    private function finDeBloqueBalanceado(string $texto, int $inicio): ?int
    {
        $profundidad  = 0;
        $dentroString = false;
        $escapando    = false;
        $largo        = strlen($texto);
        for ($i = $inicio; $i < $largo; $i++) {
            $c = $texto[$i];
            if ($escapando) {
                $escapando = false;
                continue;
            }
            if ($c === '\\' && $dentroString) {
                $escapando = true;
                continue;
            }
            if ($c === '"') {
                $dentroString = ! $dentroString;
                continue;
            }
            if ($dentroString) {
                continue;
            }
            if ($c === '{') {
                $profundidad++;
            } elseif ($c === '}') {
                $profundidad--;
                if ($profundidad === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * Calcula el costo estimado (USD) de esta consulta según tokens de entrada/salida y lo deja en el
     * log — es la forma de ver cuánto gasta cada consulta sin tener que ir al dashboard de Google AI Studio.
     */
    private function registrarUso(string $modelo, array $usage, ?string $etiqueta = null): float
    {
        $entrada      = (int) ($usage['promptTokenCount'] ?? 0);
        $salida       = (int) ($usage['candidatesTokenCount'] ?? 0);
        $pensamiento  = (int) ($usage['thoughtsTokenCount'] ?? 0);
        $cacheLectura = (int) ($usage['cachedContentTokenCount'] ?? 0);
        $precios      = self::PRECIOS_GEMINI_POR_MTOK[$modelo] ?? self::PRECIOS_GEMINI_POR_MTOK['gemini-2.5-flash'];
        $costo        = ($entrada * $precios['input'] + ($salida + $pensamiento) * $precios['output']) / 1_000_000;

        log_message('info', '[llenado-ia] Consulta ({etiqueta}) a {modelo}: {entrada} tok. entrada + {salida} tok. salida{pensamiento}{cache} — costo estimado USD {costo}', [
            'etiqueta'     => $etiqueta ?? '?',
            'modelo'       => $modelo,
            'entrada'      => $entrada,
            'salida'       => $salida,
            'pensamiento'  => $pensamiento > 0 ? " (+{$pensamiento} de thinking)" : '',
            'cache'        => $cacheLectura > 0 ? " ({$cacheLectura} desde caché)" : '',
            'costo'        => number_format($costo, 5),
        ]);

        return $costo;
    }

    /**
     * Acepta el formato rico del Prompt del sistema (`campos[]`) y el mapa plano legado.
     *
     * @return array{valores: array<string,string>, estados: array<string,string>, confianza: array<string,float>, fuentes: array<string,string>}
     */
    private function normalizarPropuesta(array $raw): array
    {
        $valores   = [];
        $estados   = [];
        $confianza = [];
        $fuentes   = [];

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
                $fuenteDoc = trim((string) ($item['fuente'] ?? ''));
                $evidencia = trim((string) ($item['evidencia'] ?? ''));
                $descripcionFuente = $fuenteDoc !== '' && $evidencia !== ''
                    ? "{$fuenteDoc} — \u{201c}{$evidencia}\u{201d}"
                    : ($fuenteDoc !== '' ? $fuenteDoc : $evidencia);
                if ($descripcionFuente !== '') {
                    $fuentes[$id] = mb_substr($descripcionFuente, 0, 240);
                }
            }

            return ['valores' => $valores, 'estados' => $estados, 'confianza' => $confianza, 'fuentes' => $fuentes];
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

        return ['valores' => $valores, 'estados' => $estados, 'confianza' => $confianza, 'fuentes' => $fuentes];
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
