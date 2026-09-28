<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Contexto de IA (pilar 3, guía por sección) para la FTE de Carreteras Interurbanas, sector
// Transportes — extraído del "Instructivo de la Ficha Técnica Estándar para la Formulación y
// Evaluación de Proyectos de Inversión en Carreteras Interurbanas" (MTC, julio 2023, V.2).
//
// La plantilla FTE-CARRETERAS tiene UNA sola sección llamada "FICHA ESTÁNDAR" que agrupa los 24
// ítems del instructivo (I. Aspectos Generales, II. Identificación, III. Formulación y
// Evaluación), más 14 secciones aparte con los anexos técnicos de tráfico/demanda/evaluación
// económica. Esta guía cubre COMPLETA la sección "FICHA ESTÁNDAR" (ítems 1 a 24) — las 14
// secciones de anexos técnicos quedan para pasadas siguientes, y se agregarán como entradas nuevas
// al array de `contextosPorSeccion()` (una guía markdown por sección, como ya hace ésta).
//
// Mismo patrón que ContextosIAFTEEBRSeeder: siembra el contexto LOCAL de la sección con los
// globales verdaderamente compartidos que le aplican ("Estructura de datos — Fichas técnicas" por
// los campos tabla, "Invierte.pe" por las reglas normativas del SNPMGI, "Transporte" por la
// terminología del sector ya sembrada en ContextosIAGlobalesSeeder).
//
// Uso: php spark db:seed ContextosIAFTECarreterasSeeder
class ContextosIAFTECarreterasSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

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

        $globalIds = $this->globalesExistentes();

        $secciones = $this->seccionesDe($plantilla);
        if ($secciones === []) {
            echo "La plantilla no tiene estructura importada todavía — no hay secciones a las que asociar contexto.\n";

            return;
        }

        $insertados = 0;
        foreach ($this->contextosPorSeccion() as $clave => $datos) {
            $seccionId = $this->buscarSeccion($secciones, $clave);
            if ($seccionId === null) {
                echo "  · No se encontró la sección «{$clave}» — se omite.\n";

                continue;
            }
            $ids = array_values(array_filter(array_map(static fn (string $n) => $globalIds[$n] ?? null, $datos['globales'])));
            $this->guardarContexto($plantillaId, $seccionId, $datos['markdown'], $ids);
            $insertados++;
        }

        echo "Listo — {$insertados} contexto(s) de sección sembrados para " . self::CODIGO_PLANTILLA . ".\n";
    }

    /** @return array<string,int> nombre => id, de los contextos GLOBALES ya sembrados (por ContextosIAGlobalesSeeder u otro). */
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

    /**
     * Clave = fragmento del nombre de la sección en la plantilla. `globales` reutiliza los
     * verdaderamente compartidos: "Transporte" (terminología del sector, ya sembrado por
     * ContextosIAGlobalesSeeder), "Invierte.pe" (naturalezas de intervención, brechas — reglas
     * generales del SNPMGI) y "Estructura de datos — Fichas técnicas" (convención JSON de tablas,
     * la sección "FICHA ESTÁNDAR" ya tiene un campo tabla en el ítem 4).
     */
    private function contextosPorSeccion(): array
    {
        return [
            'FICHA ESTÁNDAR' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Invierte.pe', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Cubre la sección "FICHA ESTÁNDAR" completa (ítems 1 a 24 del instructivo oficial): nombre del proyecto, alineamiento con el cierre de brechas, institucionalidad, ubicación geográfica, diagnóstico de la UP/población/involucrados, problema-causas-efectos, objetivos, alternativas de solución, requerimientos técnicos, horizonte de evaluación, estudio de mercado, análisis técnico, costo del proyecto, criterio de decisión de inversión, evaluación social, sostenibilidad, modalidad de ejecución, fuente de financiamiento, impacto ambiental, competencia, conclusiones y resultado de viabilidad. Los 14 anexos técnicos de tráfico/demanda/evaluación económica (secciones aparte de "FICHA ESTÁNDAR") todavía no tienen guía propia; no inventes contenido para ellos a partir de esta guía.

## 1.01 Nombre del proyecto
- `1.01.01` Naturaleza de intervención: EXACTAMENTE una de **Mejoramiento**, **Recuperación** o **Ampliación** (Ampliación solo aplica si el proyecto construye una segunda calzada o aumenta carriles). Nunca "Creación" — si la fuente de la verdad describe una vía que no existe todavía, esta FTE no aplica; repórtalo como observación, no fuerces una de las 3 opciones válidas.
- `1.01.02` es **calculado** (se arma solo con naturaleza + objeto de la intervención + localización) — nunca lo llenes.
- `1.01.03`/`1.01.04`/`1.01.05` son un grupo: "¿pertenece a un Programa de Inversión?" (Sí/No), y si es "Sí", nombre y código único de inversión (CUI) del programa. Si es "No", los dos últimos quedan vacíos — nunca inventados.

## 1.02 Alineamiento y contribución al cierre de brechas
- `1.02.01`-`1.02.02`/`1.02.04` (Función, División Funcional, Sector Responsable) son valores FIJOS de esta FTE ("015 Transporte", "033 Transporte Terrestre", "Transportes y Comunicaciones") — no los dejes vacíos "por si acaso", ni inventes un valor distinto salvo que la fuente de la verdad contradiga explícitamente el sector.
- `1.02.03` Grupo Funcional: depende de la jerarquía de la vía — "064 Vías Nacionales", "065 Vías Departamentales" o "066 Vías Vecinales". Debe ser coherente con la red vial (RVN/RVD/RVV) del proyecto (ver contexto general).
- `1.02.05` es fijo: "Servicio de Transitabilidad Vial Interurbana" (único servicio de esta FTE).
- `1.02.06` Indicador de brecha: "Porcentaje de la red vial [nacional/departamental/vecinal] en condiciones inadecuadas" — usa la misma red vial elegida en `1.02.03`.
- `1.02.07`/`1.02.08` son **calculados** (contribución al cierre de brechas y definición del servicio público) — nunca los llenes directamente.

## 1.03 Institucionalidad
Cuatro roles, cada uno con Entidad + Nombre del órgano + Responsable (excepto UEP, que no lleva responsable individual):
- `1.03.01`-`1.03.03` OPMI (Oficina de Programación Multianual de Inversiones).
- `1.03.04`-`1.03.06` UF (Unidad Formuladora).
- `1.03.07`-`1.03.09` UEI (Unidad Ejecutora de Inversiones).
- `1.03.10`-`1.03.11` UEP (Unidad Ejecutora Presupuestal, solo Entidad + Nombre).
Todos estos datos deben coincidir con lo inscrito en el SNPMGI — no completar con el nombre genérico de la Municipalidad si el registro real designa a otra Unidad Orgánica.

## 1.04 Ubicación geográfica
`1.04.01` es una tabla con una fila precargada por punto (Origen/Destino de la vía, o un tramo adicional si el proyecto interviene más de un tramo — la columna "Tramo" es calculada, no la toques). Completa por cada fila: Departamento, Provincia, Distrito y Ubigeo (según el Sistema Ubigeo del INEI — nunca inventados), Región Geográfica (Costa/Sierra/Selva), coordenadas WGS84 del punto y centro poblado de referencia. No agregues ni quites filas de la tabla.

## 1.05 Diagnóstico de la Unidad Productora (UP)
- `1.05.01` Denominación de la vía: nomenclatura oficial según el RENAC (ej. "Carretera Vecinal PA 627: Emp. PA-107 - Pampa Hermosa"). Si la vía no está inscrita en el RENAC, adviértelo en vez de inventar un código.
- `1.05.02` es tipo imagen (mapa/esquema del trazo) — se excluye del lote de texto antes de llegar a este prompt, no vas a verlo.
- `1.05.03` Longitud (Km): debe ser consistente con las coordenadas de origen/destino de `1.04.01` y con las progresivas `1.05.04`/`1.05.05`.
- `1.05.04`/`1.05.05` Progresivas de inicio y fin de tramo (formato Km, ej. "Km 0+000").
- `1.05.06` Código de la UP: el mismo código RENAC que la trayectoria de la vía — si el proyecto interviene más de una vía de la misma jerarquía, detalla todos los códigos.

## 1.06 Diagnóstico de la población beneficiada y área de influencia
`1.06.01` Población beneficiaria directa: cantidad de personas de los centros poblados/localidades del área de influencia (contigua a la carretera), según INEI — cita la fuente si la fuente de la verdad la menciona.

## 1.07 Diagnóstico de otros agentes involucrados
`1.07.01` es una matriz de filas dinámicas, NO un catálogo fijo — agrega una fila por cada grupo social o entidad relacionado con la ejecución/operación/mantenimiento (beneficiarios, cooperantes, oponentes o perjudicados) que identifique la fuente de la verdad. La columna "Posición" debe ser exactamente una de: Cooperante, Beneficiario, Oponente o Perjudicado.

## 1.08 Problema central, causas y efectos
- `1.08.01` es **calculado** (se arma solo desde el diagnóstico de la UP, `1.05`) — nunca lo llenes.
- `1.08.02`/`1.08.03` son catálogos FIJOS de causas y efectos (columnas de catálogo son calculadas, no las edites) — tu única tarea es marcar "X" en la columna "Marcar" de las filas de causas/efectos INDIRECTOS que el diagnóstico realmente sustente para este proyecto. Nunca marques todas por defecto, ni dejes la tabla sin ninguna marca si hay evidencia clara.

## 1.09 Definición de los objetivos del proyecto
- `1.09.01` es **calculado** (situación opuesta al problema central) — nunca lo llenes.
- `1.09.02` catálogo fijo de indicadores (IMDA, Tiempo de viaje, Costos de Operación Vehicular) — las magnitudes "sin/con Proyecto" son calculadas desde el estudio de tráfico/demanda de esta misma ficha (secciones A1-A2), no las llenes aquí manualmente.
- `1.09.04` catálogo fijo de medios fundamentales — marca "X" en "Marcar" solo los medios coherentes con las causas indirectas ya marcadas en `1.08.02`.

## 1.10 Descripción de las alternativas de solución
`1.10.02` tiene 2 alternativas precargadas (columna "Alternativa" es calculada) — redacta en "Descripción" las acciones concretas de cada una, coherentes con los medios fundamentales marcados en `1.09.04`. `1.10.03` solo se llena si la fuente de la verdad evidencia una única alternativa viable (sustento de por qué no hay una segunda).

## 1.11 Requerimientos técnicos, regulatorios y/o normativos
`1.11.01` catálogo fijo de documentos a gestionar (columna de catálogo es calculada) — marca "X" en "Marque con (x)" solo los que el proyecto realmente deba gestionar. `1.11.02`/`1.11.03` son un par: documento adicional no listado + su marca "X" (ambos vacíos si no aplica). `1.11.04` cita normas técnicas/manuales del MTC relacionados, si la fuente de la verdad las menciona.

## 1.12 Horizonte de evaluación
`1.12.01` en años: 10 para soluciones básicas (afirmado/estabilizado, con reinversión en el año 5) o 20-25 para pavimento asfáltico/concreto hidráulico — debe ser coherente con la alternativa técnica del ítem 1.14, no un número suelto.

## 1.13 Estudio de mercado del servicio público
Las 3 tablas (`1.13.02` demanda, `1.13.03` oferta, `1.13.04` brecha) son enteramente CALCULADAS — se completan solas desde el estudio de tráfico y la hoja "B. Situación de la UP" de esta misma ficha. Nunca propongas valores para ninguna de sus columnas; solo `1.13.01` (número de tramos) es un dato de entrada real.

## 1.14 Análisis técnico de las alternativas
`1.14.01` describe las alternativas técnicas (dimensionamiento, ubicación, tecnología — ver Anexo N.° 03) y su relación con las alternativas de solución del ítem 1.10.

## 1.15 Costo del proyecto
La mayoría de las 8 tablas de este ítem (`1.15.01`, `1.15.03`, `1.15.06`, `1.15.07`, `1.15.12`, `1.15.13`) son enteramente CALCULADAS — resumen el presupuesto detallado (Anexo N.° 04) y los costos de operación/mantenimiento; nunca propongas valores para ellas. Las únicas columnas realmente editables de todo el ítem 15 son los períodos (1-12) de `1.15.08` (avance financiero, %) y `1.15.11` (avance físico, %) — en ambas, la suma de los 12 períodos de una misma fila debe totalizar 100%. `1.15.02`/`1.15.05`/`1.15.10` son calculados (región geográfica).

## 1.16 Criterio de decisión de inversión
`1.16.01`: "Costo-Eficiencia (C/E)" si el IMDA con proyecto en el año base (IMDACP0) es ≤ 200 veh/día, "Beneficio-Costo (B/C)" si es > 200 veh/día — depende del resultado real del estudio de demanda (secciones A1-A2), nunca se asume sin haberlo calculado.

## 1.17 Evaluación social
`1.17.01` es un catálogo fijo cuyas filas dependen de la metodología elegida en 1.16 (C/E: Ratio C/E por km; B/C: VAN, B/C, TIR). Las columnas "Alternativa 1"/"Alternativa 2" se completan COPIANDO el resultado ya calculado en las hojas de evaluación económica de esta ficha (D1/D2 para C/E, E1/E2 para B/C) — nunca se recalculan aquí.

## 1.18 Sostenibilidad
- `1.18.01` responsable de O&M (nombre + código SIAF) y cómo se operará/mantendrá la vía.
- `1.18.03` matriz libre (no catálogo) de documentos de sustento del financiamiento de O&M.
- `1.18.05` catálogo fijo de peligros (Sismos, Tsunamis, Heladas, etc.) — completa Ocurrencia (Sí/No), y solo si es "Sí": Nivel (Alto/Medio/Bajo), Probabilidad (Muy alta/Alta/Media/Baja) y la medida de reducción de riesgo.

## 1.19 / 1.20 Modalidad de ejecución y fuente de financiamiento
`1.19.01`: marca "X" en UNA sola de las 4 modalidades (Administración Directa / Indirecta por Contrata / APP / Gobierno a Gobierno-Obras por Impuestos). `1.20.01`: marca "X" en una o más de las 5 fuentes de financiamiento del clasificador de la Ley de Presupuesto — a diferencia de la modalidad, aquí sí puede haber más de una marcada (financiamiento mixto).

## 1.21 Impacto ambiental
`1.21.01` catálogo fijo de etapas (Ejecución/Funcionamiento) — para cada una, describe impactos negativos reales, medidas de prevención/control/mitigación, medio de verificación, frecuencia y costo (S/), coherente con el Reglamento de Protección Ambiental del Sector Transportes.

## 1.22 Competencia
`1.22.01`/`1.22.02` son un par excluyente (SI/NO) — si es "NO", debe existir un convenio de delegación de competencias adjunto en anexos.

## 1.23 / 1.24 Conclusiones y resultado
`1.23.01` resume brecha, contribución, alternativa elegida, costo, resultado de la evaluación y recomendaciones para la UEI. `1.24.01`/`1.24.02` (VIABLE/NO VIABLE) son un par excluyente que debe reflejar fielmente el resultado real de `1.17.01` — nunca marques "VIABLE" porque "suena bien", solo si los indicadores de esa misma ficha lo sustentan.

## Reglas
- No inventes código RENAC, Ubigeo ni coordenadas: si la fuente de la verdad no los trae, déjalos vacíos — son datos verificables (RENAC del MTC, Sistema Ubigeo del INEI), no aproximables.
- La naturaleza de intervención (`1.01.01`) determina el rango de inversión máximo aplicable y qué red vial es coherente en `1.02.03`/`1.02.06` — revisa esa relación antes de proponer valores contradictorios entre campos.
- Las causas indirectas marcadas en `1.08.02` deben ser coherentes con los medios fundamentales marcados en `1.09.04` y con las alternativas descritas en `1.10.02` — son la misma cadena lógica (causa → medio → alternativa), no tres decisiones independientes.
- La metodología de evaluación (`1.16.01`), los indicadores completados en `1.17.01` y el resultado de viabilidad (`1.24.01`/`1.24.02`) deben ser la misma historia contada tres veces, nunca decisiones independientes entre sí.
- La mayoría de las tablas del ítem 15 (costos) son calculadas: si dudas si una columna es de catálogo/calculada o editable, revisa esta guía antes de proponer un valor — proponer un valor a una columna calculada nunca se usa y puede ocultar un error real de mapeo.
- Toda la ficha tiene carácter de Declaración Jurada: ningún campo se completa con un valor "razonable" sin evidencia real del proyecto.
MD,
            ],
            'A1. TRÁFICO' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Registrar el CONTEO DE TRÁFICO de campo (Anexo N.° 01 del instructivo: Pautas para el Estudio de Tráfico) — 3 estaciones principales de conteo, 7 días cada una (21 bloques idénticos en estructura). Es el insumo crudo del que se deriva todo el estudio de demanda (sección A2) y, en cascada, el IMDA, la metodología de evaluación (ítem 1.16) y la evaluación social (ítem 1.17).

## Naturaleza de esta sección: datos de campo, no razonamiento
A diferencia de "FICHA ESTÁNDAR", acá casi no hay criterio que aplicar — son cifras que un equipo de campo ya contó y que la fuente de la verdad debe traer explícitas (conteo por hora, por tipo de vehículo, por día). Si la fuente de la verdad no trae un estudio de tráfico real con cifras hora por hora, esta sección entera debe quedar vacía — nunca inventes ni "estimes" un patrón típico de tráfico.

## Estructura repetida (idéntica en las 21 subsecciones 2.01 a 2.21)
- Campos `.01` a `.09`: identificación de la estación (número, nombre, sentido, ubicación, código) y fecha del conteo (día/mes/año) — transcribe tal como los trae el estudio de tráfico.
- Campo `.10`: tabla de conteo vehicular clasificado, una fila por hora (24 horas), con columnas por tipo de vehículo según el Reglamento Nacional de Vehículos (D.S. N.° 058-2003-MTC): vehículos ligeros (Auto, Station Wagon, Pick Up, Panel, Rural Combi, Micro), Bus (2E/3E/4E), Camión (2E/3E/4E), Semitráyler (2S1-2S2/2S3/3S1-3S2/≥3S3) y Tráiler (2T2/2T3/3T2/≥3T3). Ojo: las columnas "2 E"/"3 E"/"4 E" aparecen DOS veces (una para Bus, otra para Camión) — no mezcles los conteos de un grupo con el otro.
- Solo en la Estación 3 (`2.15` a `2.21`): 3 tablas adicionales (`.11`, `.12`, `.13`) enteramente CALCULADAS (resumen por tramo horario y totales por tipo de vehículo) — nunca les propongas valores.

## Reglas
- Nunca inventes un conteo hora por hora que no esté explícito en la fuente de la verdad — a diferencia de campos narrativos, acá no hay margen para "inferir" razonablemente.
- Si la fuente de la verdad trae el conteo agregado por día (no hora por hora), dilo como observación en vez de repartir el total en 24 horas de forma arbitraria.
- Un vehículo pesado mal clasificado (ej. camión de 3 ejes anotado como bus de 3 ejes) distorsiona el IMDA y, en cascada, la metodología de evaluación completa — verifica la columna correcta antes de escribir el número.
MD,
            ],
            'A2. DEMANDA' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Derivar el IMDA actual y proyectado a partir del conteo de tráfico de campo (sección A1) y las tasas de crecimiento oficiales (sección A4) — el resultado de esta sección (IMDA con proyecto en el año base) determina la metodología de evaluación social del ítem 1.16.

## Naturaleza de esta sección: casi todo es CALCULADO
La cadena Conteo (A1) → Resumen semanal (3.02.03) → IMD/IMDA actual (2.1) → Proyección sin proyecto (2.2, con tasas de A4) → Proyección con proyecto (2.3) es enteramente calculada por el propio Excel. Las únicas entradas reales de esta sección son:
- `3.01.02`/`3.01.03` (Provincia/Distrito, deben coincidir con la ubicación geográfica del ítem 1.04).
- `3.02.01`/`3.02.02` (mes/año del estudio de tráfico).
- `3.02.04`/`3.02.05` (factores de corrección, copiados de la sección A3, nunca inventados).
- `3.05.02` (% de tráfico generado, según el rango 10-30% del instructivo) y `3.05.03` (¿existe vía alterna?).
- `3.05.05` (tráfico desviado por año) — ÚNICA tabla con columnas numéricas realmente editables, y solo si `3.05.03` fue "Sí".

## Reglas
- Si `3.05.03` (¿existe vía alterna?) es "No", todos los años de `3.05.05` deben quedar en 0 — nunca inventes tráfico desviado sin una vía alterna real.
- No calcules tú el IMDA ni ninguna proyección: son fórmulas del Excel que dependen de A1/A3/A4; tu único trabajo en esta sección es completar los pocos campos de entrada reales listados arriba.
MD,
            ],
            'P1. EJES EQUIVALENTES' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Calcular los ejes equivalentes (EE) de diseño a partir del tráfico proyectado (sección A2) — insumo del diseño de pavimento. Casi toda la sección es CALCULADA; los únicos datos de entrada reales son las características físicas de la vía (`4.01.01` a `4.01.03`, `4.01.06`).

## Reglas
- `4.01.01`-`4.01.03` deben ser coherentes con la naturaleza de intervención (`1.01.01`): una Ampliación a segunda calzada implica 2 calzadas, no 1.
- `4.01.06` (tipo de pavimento) debe ser coherente con el horizonte de evaluación ya registrado en el ítem 1.12 (afirmado/estabilizado → 10 años, asfáltico/concreto → 20-25 años).
- El resto de la sección (`4.01.04`, `4.01.05`, `4.02.01`, `4.03.01`, `4.03.02`) es enteramente calculado — nunca propongas valores.
MD,
            ],
            'A3. FACTOR DE CORRECCIÓN' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Tabla de referencia OFICIAL (factores de corrección estacional por unidad de peaje, promedio 2010-2020) que el propio aplicativo Excel de la FTE trae precargada — se usa para llevar el conteo de tráfico de campo (sección A1) al IMDA (sección A2).

## Regla
Esta sección es enteramente calculada/de referencia — nunca propongas ni modifiques ningún valor de sus 2 tablas (vehículos ligeros y pesados), sin importar qué diga la fuente de la verdad del cliente.
MD,
            ],
            'A4. TASA DE CRECIMIENTO' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Tabla de referencia OFICIAL (tasa de crecimiento anual vehicular por departamento, vehículos ligeros ligada al crecimiento poblacional y pesados al PBI) que el propio aplicativo Excel de la FTE trae precargada — alimenta la proyección de demanda (sección A2, ítems 3.04.01/3.04.02).

## Regla
Esta sección es enteramente calculada/de referencia — nunca propongas ni modifiques ningún valor de sus 2 tablas, sin importar qué diga la fuente de la verdad del cliente.
MD,
            ],
            'B. SITUACIÓN DE LA UNIDAD PRODUCTORA' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Describir el estado técnico actual de la vía (situación sin proyecto) y contrastarlo con la situación de diseño de la alternativa seleccionada (con proyecto) — insumo del análisis técnico de alternativas (ítem 1.14).

## Reglas
- `7.01.01`/`7.01.02` (estado actual, velocidad de diseño) deben ser coherentes con el diagnóstico de la UP ya registrado en el ítem 1.05.
- `7.02.01`: cada fila es UNA característica técnica distinta (ancho de calzada, tipo de superficie, radio mínimo, pendiente máxima, etc.) — completa ambas columnas (sin/con proyecto) con la evidencia real; si el proyecto no cambia una característica puntual, repite el mismo valor en ambas columnas solo cuando eso sea efectivamente cierto, no por defecto.
MD,
            ],
            'C. COSTOS' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte', 'Finanzas'],
                'markdown' => <<<'MD'
## Objetivo
Calcular el costo de inversión y los costos de operación y mantenimiento referenciales de cada una de las 2 alternativas técnicas — insumo directo de la evaluación económica (secciones D1/D2 o E1/E2, según la metodología del ítem 1.16).

## Estructura (idéntica en Alternativa 1 y 2)
- Tabla de costo de inversión por tramo: catálogo de activos estratégicos (columnas "Activos" y "Tipo de factor producción" ya predefinidos) — completa por fila la naturaleza de la acción, unidad y cantidad física, cantidad de dimensión y precio unitario real del proyecto.
- Gastos Generales, Utilidad, Supervisión, Expediente Técnico, Gestión del Proyecto y Liquidación: montos o porcentajes REALES del proyecto, nunca "típicos" — en Administración Directa, Utilidad debe ser 0%.
- Todos los subtotales (Costo Directo, Costos Indirectos, Otros Costos, Costo Total, IGV) son calculados — nunca los llenes.
- Tabla de costos de O&M referenciales: catálogo fijo de políticas de mantenimiento (rutinario/periódico, sin/con proyecto) — completa solo el precio real por km.

## Reglas
- El "Costo Total de la Inversión" (A+B+C) calculado debe coincidir con el costo de inversión ya declarado en el ítem 15.1 — si no coincide, es señal de un error de mapeo, no algo que corregir a mano.
- La naturaleza de la acción sobre cada activo (construcción/reforzamiento estructural/remodelación/reparación integral/reparación parcial) debe ser consistente con la naturaleza de intervención general del proyecto (ítem 1.01.01: Mejoramiento/Recuperación/Ampliación).
MD,
            ],
            'D1. EVALUACIÓN (C-E)' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte', 'Finanzas', 'Invierte.pe'],
                'markdown' => <<<'MD'
## Objetivo
Evaluación social por el método Costo-Eficiencia (C/E) — aplica cuando el IMDA con proyecto en el año base es ≤ 200 veh/día (ítem 1.16). Casi toda la sección es CALCULADA a partir de los costos de la sección C; las únicas entradas reales son los 4 factores de conversión a precios sociales, iguales en ambas alternativas.

## Los 4 factores de conversión (A) — únicos datos de entrada reales
- Tasa Social de Descuento: 0.08 (8%), valor oficial del Anexo N.° 11 del SNPMGI.
- Inversión: 0.79 (referencial, Anexo N.° 11).
- Mantenimiento y Operación: 0.75 (referencial, Anexo N.° 11).
- Valor Residual: 35% (concreto), 25% (asfalto) o 10% (estabilizado/afirmado) — según el tipo de pavimento de ESA alternativa (ver sección P1, ítem 4.01.06). Puede diferir entre Alternativa 1 y 2 si usan pavimentos distintos.

## Reglas
- Todo lo demás (Costos a precios de mercado B, Costos a precios sociales C, Costos Incrementales D, VAC, Ratio C-E, CAE, VAC/Km) es calculado — nunca propongas valores.
- El Ratio C-E resultante se compara (fuera de esta sección) contra la línea de corte de D2 — tú no decides la viabilidad aquí, solo completas los 4 factores de entrada.
MD,
            ],
            'D2. RATIO C-E' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Tabla de referencia OFICIAL (líneas de corte de Costo-Eficiencia por km, según IMDA/solución técnica/región natural) publicada por el MTC — se usa para comparar el Ratio C-E de cada alternativa (sección D1) y determinar si el proyecto es viable.

## Regla
Esta sección es enteramente calculada/de referencia — nunca propongas ni modifiques ningún valor de su tabla, sin importar qué diga la fuente de la verdad del cliente.
MD,
            ],
            'E1. EVALUACIÓN (B-C)' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte', 'Finanzas', 'Invierte.pe'],
                'markdown' => <<<'MD'
## Objetivo
Evaluación social por el método Beneficio-Costo (B/C) — aplica cuando el IMDA con proyecto en el año base es > 200 veh/día (ítem 1.16). Es la sección MÁS calculada de toda la ficha: el Costo de Operación Vehicular (COV) es un parámetro oficial del MTC (sección E2), no un dato del proyecto.

## Entradas reales (el resto de la sección es calculado)
- Longitud total y horizonte de evaluación (deben coincidir con los ítems 1.05.03 y 1.12 — en Alternativa 2 se registran a mano, en Alternativa 1 se autocompletan).
- "T.C.": parámetro de la metodología COV vigente del MTC — no lo inventes, verifícalo en el manual de la metodología.
- Recorrido reducido por tráfico desviado: coherente con "¿Existe vía alterna?" y el tráfico desviado de la sección A2.
- Los mismos 4 factores de conversión a precios sociales de D1 (Tasa Social de Descuento 0.08, Inversión 0.79, Mantenimiento y Operación 0.75, Valor Residual según pavimento).

## Reglas
- Nunca propongas valores para las tablas de Situación/COV/Beneficios/Costos: todas se calculan desde otras secciones (ubicación, A2, C, E2).
- VAN, B/C y TIR son resultados calculados — tu único trabajo en esta sección es completar los pocos parámetros de entrada listados arriba.
MD,
            ],
            'E2. COV INTERURBANO' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Tabla de referencia OFICIAL (Costo de Operación Vehicular por tipo de vehículo, según región natural, orografía, superficie y estado de la vía) publicada por el MTC — alimenta automáticamente la evaluación Beneficio-Costo de la sección E1.

## Regla
Esta sección es enteramente calculada/de referencia — nunca propongas ni modifiques ningún valor de su tabla, sin importar qué diga la fuente de la verdad del cliente.
MD,
            ],
            'A5. FORMATO DE CONTEO' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Formato de campo (Anexo N.° 01, hoja "A.5 Formato de Conteo") para transcribir el conteo vehicular clasificado — mismo tipo de dato crudo de campo que la sección A1 (Tráfico), pero organizado en 3 bloques horarios (00-08, 08-16, 16-24) en vez de 24 filas por hora.

## Reglas
- Igual que A1: nunca inventes un conteo que no esté explícito en la fuente de la verdad — son cifras de campo, no algo que se infiera.
- Un vehículo pesado mal clasificado distorsiona el IMDA en cascada — verifica la columna correcta (Bus vs Camión del mismo número de ejes) antes de escribir el número.
MD,
            ],
            'A6. FORMATO ENCUESTA PASAJE' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Formato de campo (Anexo N.° 01, hoja "A.6") para transcribir la encuesta Origen-Destino de pasajeros — determina el área de influencia directa/indirecta y el tráfico desviado (sección A2).

## Reglas
- Una fila por cada encuestado real — nunca completes filas "de ejemplo" sin una encuesta real detrás en la fuente de la verdad.
- El motivo de viaje es EXCLUYENTE: a lo sumo una de las 4 columnas (Trabajo, Turismo, Estudio, Salud) lleva "X" por fila.
MD,
            ],
            'A7. FORMATO ENCUESTA CARGA' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Transporte'],
                'markdown' => <<<'MD'
## Objetivo
Formato de campo (Anexo N.° 01, hoja "A.7") para transcribir la encuesta Origen-Destino de carga — mismo propósito que A6 pero para vehículos de carga (peso, producto transportado, capacidad).

## Regla
Una fila por cada encuestado real — nunca completes filas "de ejemplo" sin una encuesta real detrás en la fuente de la verdad.
MD,
            ],
        ];
    }
}
