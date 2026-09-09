<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Contexto de IA para la FTE de Educación Inicial, Primaria y Secundaria (EBR) — V03, sector
// Educación — extraído del "Instructivo de la Ficha Técnica Estándar..." (MINEDU, diciembre 2025,
// versión 03).
//
// La plantilla FTE-EBR-V03 hoy solo modela la Sección I (Aspectos Generales, ítems 1-8.2.1 del
// instructivo) y los Anexos 01-05 (costos unitarios, presupuesto de infraestructura, presupuesto
// de mobiliario y equipo, resumen/acciones consolidadas, evaluación económica costo-eficacia por
// alternativa) — las secciones II-IV del instructivo (Identificación more allá de 8.2.1,
// Formulación narrativa, Evaluación social/sostenibilidad) todavía no están importadas a esta
// plantilla, así que esta guía cubre exactamente las 7 secciones que sí existen hoy.
//
// Mismo patrón que ContextosIACuidadoDiurnoSeeder: siembra contextos GENERALES propios de esta
// ficha (ninguno nuevo hace falta aquí — la terminología EBR ya vive en el global "Educación" de
// ContextosIAGlobalesSeeder, y las reglas normativas de Invierte.pe ya son un global compartido)
// y el contexto LOCAL de cada sección con los globales verdaderamente compartidos que le apliquen.
//
// Uso: php spark db:seed ContextosIAFTEEBRSeeder
class ContextosIAFTEEBRSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-EBR-V03';

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

        echo "Listo — {$insertados} contextos de sección sembrados para " . self::CODIGO_PLANTILLA . ".\n";
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
     * verdaderamente compartidos: "Educación" (terminología del sector, ya sembrado por
     * ContextosIAGlobalesSeeder), "Invierte.pe" (naturalezas de intervención, brechas — reglas
     * generales del SNPMGI), "Finanzas" (criterios de montos/precios de mercado) y "Estructura de
     * datos — Fichas técnicas" (convención JSON de tablas, se asocia a TODAS: las 7 secciones
     * tienen al menos un campo tabla).
     */
    private function contextosPorSeccion(): array
    {
        return [
            'ASPECTOS GENERALES' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Invierte.pe', 'Educación'],
                'markdown' => <<<'MD'
## Objetivo
Definir el nombre del proyecto, su responsabilidad funcional, la institucionalidad (OPMI/UF/UEI/UEP), su alineamiento con el cierre de brechas, la ubicación geográfica, el área de estudio/influencia, el diagnóstico de la población afectada y el diagnóstico inicial de la Unidad Productora (servicios básicos y terreno). Es la única sección de "Identificación/Diagnóstico" que existe hoy en esta plantilla — cubre los ítems 1 a 8.2.1 del instructivo oficial.

## 01.01 Nombre del proyecto de inversión
- `01.01.1` es **calculado** (se arma solo con naturaleza + servicio + I.E. + localización) — nunca lo llenes.
- `01.01.2` Código del proyecto de inversión: solo si el cliente ya tiene un CUI asignado por el Banco de Inversiones (aplicativo informático) — si el proyecto es nuevo, déjalo vacío, nunca inventes un código.
- `01.01.3` Naturaleza de intervención: EXACTAMENTE una de **Mejoramiento**, **Ampliación**, **Recuperación** o **Mejoramiento y Ampliación**. Nunca "Creación" — si la fuente de la verdad describe una I.E. que no existe todavía, esta FTE no aplica; repórtalo como observación, no fuerces una de las 4 opciones.
- `01.01.4`/`01.01.5` son un par: "¿pertenece a un Programa de Inversión?" respuesta SI / respuesta NO. Marca **"X" en uno solo** de los dos (nunca en ambos, nunca en ninguno si la fuente de la verdad no lo menciona explícitamente).
- `01.01.6` Código del programa de inversión: solo si `01.01.4` (SI) quedó marcado con X.

## 01.02 Responsabilidad funcional — valores fijos de esta FTE, no varían por proyecto
- `01.02.1` Función: **22 – Educación**.
- `01.02.2` División Funcional: **047 – Educación Básica**.
- `01.02.3` Grupo Funcional: elige según el nivel que interviene el proyecto — **0103 Educación Inicial**, **0104 Educación Primaria**, **0105 Educación Secundaria** o **0106 Educación Básica Alternativa** (puede ser más de uno si el proyecto interviene varios niveles: sepáralos con coma o indícalo tal como lo acepte el campo).
- `01.02.4` Sector Responsable: **Educación**.
Estos 4 valores son casi siempre los mismos para cualquier proyecto de esta FTE — no los dejes vacíos "por si acaso", ni inventes un valor distinto salvo que la fuente de la verdad contradiga explícitamente el nivel educativo.

## 01.03 Institucionalidad (tablas OPMI / UF / UEI)
`01.03.1`, `01.03.2` y `01.03.3` son tres tablas de filas dinámicas (una por OPMI, UF y UEI respectivamente) — cada una recoge el nombre de la instancia y su responsable. Completa con los nombres reales que dé la fuente de la verdad (para el Sector Educación la OPMI suele ser "Oficina de Programación Multianual de Inversiones — MINEDU" salvo que el cliente indique otra). `01.03.4` es el nombre de la Unidad Ejecutora Presupuestal (UEP), casi siempre la UGEL correspondiente.

## 01.04 Alineamiento y contribución al cierre de brechas
- `01.04.1` tabla "Servicios públicos con brecha identificada y priorizada": marca con X **cada** servicio (Inicial/Primaria/Secundaria/Básica Alternativa) que el proyecto realmente interviene — puede ser más de uno.
- `01.04.2`/`01.04.3` Brecha de Calidad / Brecha de Cobertura: marca la(s) que corresponda según la naturaleza de intervención — **Mejoramiento y Recuperación → solo Calidad**; **Ampliación → Cobertura, y también Calidad si a la vez mejora infraestructura existente** (naturaleza "Mejoramiento y Ampliación").
- `01.04.4` tabla de indicadores de **brecha de calidad**: una fila por cada servicio marcado en `01.04.1` con brecha de calidad — nombre del indicador ("Porcentaje de instalaciones educativas que brindan el servicio de educación [nivel] en condiciones inadecuadas"), U.M. siempre vacío/no aplica en esta tabla (la U.M. real es "Instalación Educativa" y se usa en `01.04.5`), Espacio geográfico (Distrital/Provincial/Departamental/Nacional según qué nivel de gobierno ejecuta y qué información esté disponible), Año base, Valor (el % de brecha vigente, si la fuente de la verdad lo trae — si no, déjalo vacío, no es lo mismo que la contribución de `01.04.5`).
- `01.04.6` es la misma estructura pero para **brecha de cobertura** ("Porcentaje de personas no matriculadas en el nivel [x] respecto a la demanda potencial").
- `01.04.5`/`01.04.7` tablas "Contribución al cierre de brechas" (Valor | U.M.) — para calidad, Valor = número de instalaciones educativas (AP) que el proyecto interviene, U.M. = "Instalación Educativa"; para cobertura, Valor = número de personas/año nuevas que accederán al servicio, U.M. = "Persona/año". Nunca calcules este valor de la nada: debe salir de la cantidad de AP a intervenir (`01.05.6`) o de la brecha de cobertura estimada en la fuente de la verdad.

## 01.05 Ubicación geográfica
- `01.05.1`-`01.05.3` (Departamento/Provincia/Ubigeo del área de estudio) son **calculados** a partir del código de local — nunca los llenes directamente.
- `01.05.4` Código del local (según ESCALE-MINEDU) — es la clave que resuelve `01.05.1`-`01.05.3` automáticamente; complétalo con evidencia real, no lo inventes.
- `01.05.5` tabla "Datos de la Unidad Productora": nombre de cada Institución Educativa del local, si es zona Urbana o Rural, y si se interviene con el proyecto (Sí/No).
- `01.05.6` tabla "Datos de las áreas productivas identificadas": una fila POR CADA AP (cada servicio/nivel educativo con su propio código modular) — ítem, código modular, nivel (Inicial/Primaria/Secundaria/EBA), latitud, longitud, altitud (msnm), y si se interviene con el proyecto (Sí/No). El número de filas con "Sí" debe coincidir con los servicios marcados en `01.04.1`.

## 01.06 Área de estudio y área de influencia
- `01.06.1` tabla: una fila por cada localidad/centro poblado de donde provienen o pueden provenir los estudiantes (no solo la localidad del local educativo) — Departamento, Provincia, Distrito, Localidad/Centro Poblado.
- `01.06.2` texto libre: describe y sustenta la delimitación del área de estudio/influencia — de dónde proviene la población, si hay locales educativos alternos del mismo nivel en el entorno, y el criterio de distancia/tiempo de desplazamiento (referencia oficial: Inicial 500 m / 15 min, Primaria 1 500 m / 30 min, Secundaria 3 000 m / 45 min — cita estos valores solo si la fuente de la verdad no da uno propio).

## 01.07 Diagnóstico de la población afectada
`01.07.1` texto libre: caracteriza a la población afectada en sus tres dimensiones — socioeconómica (actividades económicas, indicadores educativos), demográfica (cuántos son, tendencia de crecimiento, edades) y social/cultural (idioma, patrones culturales, actitud frente al servicio). Cita la fuente (INEI, DIRESA, trabajo de campo) si la fuente de la verdad la menciona.

## 01.08 Diagnóstico de la Unidad Productora (servicios básicos y terreno)
- `01.08.1` tabla de servicios básicos por institución educativa (agua, desagüe, electricidad, alumbrado público, telefonía, internet, residuos sólidos, gas natural) — si NO hay red pública, se indica el tipo alternativo de provisión (ver `01.08.2`, el catálogo de opciones: pozo propio/camión cisterna para agua, tanque séptico/pozo percolador para desagüe, generador/panel solar para electricidad, etc.).
- `01.08.3` (¿estudio topográfico adjunto?), `01.08.4` (¿límites definidos?), `01.08.5` (cerco perimétrico: completo/incompleto/no cuenta), `01.08.6` (forma del terreno: regular/irregular) — responde Sí/No o la opción exacta del catálogo, nunca una descripción larga.
- `01.08.7` medidas del terreno: Área de terreno (m²), Área de terreno útil (m², descuenta zonas no utilizables por pendiente/riesgo), Perímetro (m).
- `01.08.8` coordenadas UTM X/Y de un vértice del terreno o del ingreso.
- `01.08.9` tipo de topografía (llano/inclinado/muy inclinado/accidentado) según el % de pendiente del estudio topográfico — si hay más de un terreno/predio, una columna por terreno.

## Reglas
- No inventes código modular, código de local, UBIGEO ni coordenadas: si la fuente de la verdad no los trae, déjalos vacíos — son datos verificables en ESCALE/INEI, no aproximables.
- La naturaleza de intervención (`01.01.3`) determina qué brechas marcar en `01.04.2`/`01.04.3` — revisa esa regla antes de marcar brechas, no las marques ambas "por si acaso".
- El número de AP con "Sí" en `01.05.6` debe ser coherente con los servicios marcados en `01.04.1` y con las filas de `01.06.1`/`01.08.1`.
MD,
            ],
            'ANEXO 01' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Finanzas', 'Educación'],
                'markdown' => <<<'MD'
## Objetivo
Registrar, por cada activo estratégico de infraestructura, el costo unitario referencial (por m²) que después alimenta el Anexo 02 (Presupuesto de Infraestructura). Es la base de precios de todo el presupuesto de obra civil de la ficha.

## Campos
- `02.01.1` Fuente de los costos asumidos: cita la fuente real (ej. "Costos Unitarios Referenciales PRONIED, actualizados a [fecha]", cotizaciones propias, expedientes de proyectos similares) — nunca la dejes en blanco ni la inventes si la fuente de la verdad no la menciona explícitamente; en ese caso indícalo como pendiente de sustento.
- `02.01.2`/`02.01.3` Gastos Generales (%) y Utilidad (%) sobre el costo directo — en administración directa la Utilidad debe ser 0%; en administración indirecta (por contrata) usa el % que indique la fuente de la verdad, nunca un porcentaje inventado "típico".
- `02.01.4` Fecha de actualización de los costos: la fecha real del listado de precios usado, no la fecha de hoy.
- `02.01.5` % IGV: 18% salvo que la fuente de la verdad indique otro régimen.
- `02.01.6` tabla de costos unitarios por activo estratégico de infraestructura: una fila por cada activo de la lista de 19 (ver contexto general). Si la Unidad Formuladora tiene su propio costo unitario (columna "Ingresar: Costos Unitarios estimados por la UF"), regístralo tal como lo da la fuente de la verdad y justifica la variación (distancia a la obra, tipo de suelo, condiciones climáticas/topográficas) en la columna de justificación — nunca dejes esa columna con una justificación genérica si no hay una razón real citada.

## Reglas
- El "Costo Unitario Directo asumido" (sin CD+GGyU+IGV) es la única columna que se propaga al Anexo 02 — verifica que sea coherente con el costo total dado (si la fuente da un costo "todo incluido", esta columna es el resultado de descontarle GG, Utilidad e IGV, no el mismo número).
- No completes un activo estratégico que el proyecto no va a intervenir — deja esa fila vacía en vez de copiar un costo genérico "por si se necesita después".
MD,
            ],
            'ANEXO 02' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Finanzas', 'Educación'],
                'markdown' => <<<'MD'
## Objetivo
Estimar el presupuesto de infraestructura completo: edificaciones, obras exteriores, obras provisionales/preliminares, obras de contingencia, costos indirectos y el resumen de todo el presupuesto (infraestructura + mobiliario y equipo). Es el anexo más extenso de la ficha — sigue los 11 pasos del instructivo oficial, distribuidos en 3 subsecciones de esta plantilla.

## A) Descripción general (subsección 03.01 — Pasos 1-2)
- `03.01.1` es una imagen/croquis del plano de planta con las edificaciones numeradas (E-01, E-02... en sentido antihorario desde el ingreso) — no se llena con IA, es un archivo que sube el usuario.
- `03.01.2` Área de terreno útil es **calculada** (toma el valor ya registrado en `01.08.7` de Aspectos Generales) — nunca la llenes aparte.
- `03.01.3` Número de edificaciones: cuenta real de bloques/pabellones identificados en el plano.
- `03.01.4` tabla con el detalle de cada edificación: código (E-01, E-02...), número de pisos, niveles educativos que alberga (I/P/S o combinaciones IP/IS/PS/IPS), y nombre descriptivo (ej. "Pabellón de Aulas Primaria", "Bloque Administrativo").

## B) Lista de activos estratégicos (subsección 03.02)
Tablas de referencia con el catálogo fijo de activos de infraestructura y obras exteriores (ver contexto general) — son catálogos, no se editan por proyecto.

## C) Programa arquitectónico y estimación de costos (subsección 03.03 — Pasos 3-11)
- `03.03.1` (Paso 3, Propuesta técnica de edificaciones): **una fila por cada ambiente** de cada edificación — edificación, piso, naturaleza de acción (1-5, ver contexto general), nombre del ambiente, metrado en m², activo estratégico asociado (del catálogo de 19), nivel(es) educativo(s). Las columnas de costo (costo unitario, incidencia %, costo parcial, costo directo acumulado) son cálculos automáticos a partir del Anexo 01 — no las llenes, solo asegúrate de que metrado/activo/naturaleza de acción estén correctos, porque de ahí sale todo lo demás.
- `03.03.2` (Paso 4, verificación de aulas): compara la demanda de aulas ya estimada (fuera de esta ficha, en el análisis técnico del tamaño) contra las aulas realmente propuestas en `03.03.1` — si hay variación, la columna de recomendación debe explicar el motivo (tamaño del terreno, disponibilidad de plazas docentes, etc.), nunca dejarla en blanco cuando hay diferencia.
- `03.03.3`/`03.03.4` (Paso 5, obras exteriores): mismo patrón que `03.03.1` pero para cercos, muros de contención, espacios deportivos, espacios exteriores e instalaciones exteriores de servicios básicos (ver el catálogo de 5 activos de obras exteriores).
- `03.03.5` (Paso 6, obras provisionales/preliminares/seguridad y salud): actividades que NO generan un activo estratégico propio pero son necesarias para ejecutar la obra (cartel de obra, limpieza de terreno, movimiento de tierras, seguridad y salud, mitigación ambiental durante la obra) — normalmente expresadas como % del costo directo o % del costo de edificaciones, no como montos sueltos inventados.
- `03.03.6` (Paso 7, obras de contingencia): SOLO si el proyecto requiere que el servicio educativo continúe mientras dura la obra (aulas prefabricadas, alquiler de local temporal, etc.) — si la fuente de la verdad no menciona contingencia, esta tabla queda vacía, no inventes una intervención de contingencia "por si acaso".
- `03.03.7` (Resumen total de costo directo): agrega infraestructura + obras exteriores + Paso 6 — es un resumen, generalmente calculado; no lo fuerces si sus insumos ya están completos.
- `03.03.8` (Paso 8, costos indirectos): % de Gastos Generales y Utilidad para obras civiles definitivas, obras de contingencia y mobiliario/equipo por separado — en administración directa, Utilidad = 0% en todas las columnas.
- `03.03.9`, `03.03.11`, `03.03.17`, `03.03.18` (Paso 9, resumen general — tablas agrupadas): consolidan de forma automática los costos de infraestructura (A1+A2), mobiliario/equipo (B) y otros factores productivos (C) — solo completa "Otros Factores Productivos" (`03.03.18`) si la fuente de la verdad describe un factor productivo real fuera de infraestructura/mobiliario/equipo (poco común); si no existe, la tabla queda vacía.
- `03.03.10`, `03.03.12`, `03.03.13`, `03.03.15`, `03.03.19` son **calculados** — nunca los llenes, son totales derivados de las tablas anteriores.
- `03.03.20` (Paso 10, verificación final) es calculado — informa si la ficha y los anexos son compatibles; no se propone valor.
- `03.03.21`-`03.03.29` (Paso 11, ratios de edificaciones): información general de terreno, capacidad productora y ratios soles/m² — son mayormente cálculos automáticos a partir de lo ya registrado; solo verifica que `03.03.28` (nombre de cada edificación) sea coherente con `03.01.4`.

## Reglas
- La forma exacta de cada tabla (número de filas/columnas) la define la estructura del formato oficial — nunca agregues ni quites filas/columnas, solo completa los valores de las celdas vacías o corrígelas si hay evidencia mejor.
- Nunca propongas un costo unitario en `03.03.1`/`03.03.4`/`03.03.5` distinto al que ya está fijado en el Anexo 01 para ese activo estratégico — esas columnas son de lectura, no de reescritura.
- Las columnas de naturaleza de acción (1-5) deben ser consistentes con lo que describe el diagnóstico de la Unidad Productora: no marques "construcción" (1) para un ambiente que la fuente de la verdad describe como "en buen estado, solo requiere reparación menor".
MD,
            ],
            'ANEXO 03' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Finanzas', 'Educación'],
                'markdown' => <<<'MD'
## Objetivo
Estimar el presupuesto de mobiliario y equipo, separando los activos estratégicos (los que definen capacidad, ej. mobiliario de aula) de los activos no estratégicos (kits de mobiliario/equipo para ambientes complementarios: cocina, tópico, espacios deportivos, etc.).

## Campos
- `04.01.1` tabla jerárquica resumen: no se llena directamente, se actualiza sola a partir del detalle de `04.03.3` — muestra la cantidad y costo total por cada uno de los 7 activos estratégicos de mobiliario y los 7 de equipos.
- `04.02.1`/`04.02.2` resumen de kits de mobiliario y de equipos (activos NO estratégicos) — también se actualizan solos a partir de `04.03.3`.
- `04.03.1` % de incremento por transporte y embalaje: solo si el local educativo está en una zona alejada donde el precio de mercado del mobiliario/equipo no incluye llevarlo a obra — si el precio ya es "puesto en obra", este porcentaje es 0%. Nunca un porcentaje inventado sin que la fuente de la verdad lo sustente (distancia, medio de transporte).
- `04.03.3` (Parte C, detalle por ambientes): **la tabla principal que sí llenas campo por campo** — una fila por cada bien de mobiliario o equipo, indicando: tipo de ambiente (del catálogo: Aula Inicial/Primaria/Secundaria, Sala de Psicomotricidad, Aula de Innovación Pedagógica, Laboratorio, Taller creativo/arte/EPT, Cocina, Tópico, Espacios deportivos, Otros Espacios), nombre del bien (ej. "Mesa individual para estudiantes de secundaria"), si es Activo Estratégico (Sí/No — Sí si va directo a un aula/ambiente básico que define capacidad; No si es parte de un kit de un ambiente complementario), tipo Mobiliario (M) o Equipo (E), número de ambientes, cantidad por ambiente, precio unitario de mercado.

## Reglas
- Cada bien debe tener su "¿Activo Estratégico?" correctamente marcado — un error acá desalinea los resúmenes de `04.01.1`/`04.02.1`/`04.02.2`, que dependen enteramente de esta columna.
- No inventes bienes de mobiliario/equipo genéricos: usa los que describa la fuente de la verdad o los que exija la normativa de diseño de locales educativos para el ambiente correspondiente (aula, laboratorio, taller, etc.) — si no hay evidencia de qué mobiliario específico se requiere para un ambiente nuevo, es preferible dejarlo pendiente que inventar una lista genérica.
- El costo total (`04.03.2`, calculado) nunca se llena a mano.
MD,
            ],
            'ANEXO 04' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Finanzas', 'Educación'],
                'markdown' => <<<'MD'
## Objetivo
Consolidar en un solo resumen todo lo ya calculado en los Anexos 02 y 03 — obras civiles por tipo de edificación, mobiliario/equipo por tipo, y el listado final de acciones (activo + naturaleza de acción + costo) tal como se registra en el Formato N° 07-A del Banco de Inversiones.

## Campos
Casi toda esta sección es **calculada** a partir de los Anexos 02 y 03 (`05.01.2`-`05.01.5`, `05.01.7`, `05.01.9`-`05.01.11` son totales/validaciones automáticas, nunca se llenan con IA). Los únicos insumos que dependen de evidencia real son:
- `05.01.1` tabla "Obras Civiles": espejo del detalle de edificaciones ya registrado en el Anexo 02 (`03.03.28`/`03.03.29`) — si esos campos ya están completos, esta tabla se resuelve sola; no dupliques el trabajo inventando de nuevo la lista de edificaciones.
- `05.01.6`/`05.01.8` resúmenes por tipo de activo (obras civiles y mobiliario/equipo respectivamente) — también derivados, no se editan directamente.
- `05.01.12`/`05.01.13` "Consolidado de Acciones en el Banco de Inversiones": una fila por cada combinación única de (acción + activo estratégico + tipo de factor productivo) que efectivamente tiene costo en los anexos anteriores — no agregues una fila para un activo que no se intervino.

## Reglas
- Si esta sección muestra un total distinto al de los Anexos 02/03, el problema está en el anexo de origen (metrados, costos unitarios o naturaleza de acción mal registrados) — nunca "ajustes" un valor acá directamente para que cuadre.
- No propongas valores para los campos calculados aunque parezcan vacíos — revisa primero si los anexos de origen (02 y 03) están completos; si lo están, estos campos se resuelven solos al guardar.
MD,
            ],
            'ALTERNATIVA N°1' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Finanzas', 'Invierte.pe', 'Educación'],
                'markdown' => <<<'MD'
## Objetivo
Calcular el flujo de costos a precios sociales y los costos incrementales de la **Alternativa técnica 1**, insumo directo para el indicador de rentabilidad social (costo-eficacia) de esta alternativa.

## Campos
- `06.01.1` tabla de factores de conversión a precios sociales: un factor por cada tipo de obra/insumo (obras civiles, mobiliario, equipo, mano de obra calificada/no calificada, etc.) — estos factores los fija el Anexo N° 11 de la Directiva N° 001-2019-EF/63.01 (Parámetros de Evaluación Social del MEF), **nunca los inventes ni los adaptes**: si la fuente de la verdad no trae la tabla vigente de factores, indícalo como pendiente en vez de aproximar un factor "razonable".
- `06.01.2` Tasa Social de Descuento: la vigente según la normativa del MEF (no una tasa de mercado ni una inventada).
- `06.01.3` Valor Residual: solo si aplica (normalmente el 10% del valor de la infraestructura en el último año del horizonte) — déjalo vacío si la fuente de la verdad no lo sustenta.
- `06.01.4` "Costos de inversión y mantenimiento a precios de mercado": por año del horizonte de evaluación, columnas Sin Proyecto / Inversión / Mantenimiento — estos montos deben ser consistentes con el Costo de Inversión Viable ya calculado en los Anexos 02-04 para ESTA alternativa (nunca inventes un monto distinto).
- `06.01.5` "Costos de inversión y mantenimiento a precios sociales": aplica los factores de `06.01.1` a los montos de `06.01.4` — es un cálculo derivado, verifica que los insumos (06.01.1 y 06.01.4) estén completos antes de proponer nada aquí a mano.
- `06.01.6` "Costos incrementales": la diferencia (con proyecto − sin proyecto) año por año — también derivado de las filas anteriores.

## Reglas
- Esta sección corresponde exclusivamente a la **Alternativa 1** — sus insumos de inversión deben salir de los anexos de costos de ESA alternativa, nunca de otra.
- Los factores de corrección social (`06.01.1`) son un dato normativo, no un supuesto del formulador — si no hay evidencia de la tabla vigente, no rellenes con valores de otro proyecto o de memoria.
MD,
            ],
            'ALTERNATIVA N°2' => [
                'globales' => ['Estructura de datos — Fichas técnicas', 'Finanzas', 'Invierte.pe', 'Educación'],
                'markdown' => <<<'MD'
## Objetivo
Igual estructura y reglas que la Evaluación Económica de la Alternativa 1 (factores de conversión social, tasa social de descuento, valor residual, costos de inversión/mantenimiento a precios de mercado y sociales, costos incrementales) — ver ese contexto para el detalle de cada campo.

## Diferencia con la Alternativa 1
Esta sección corresponde a la **Alternativa técnica 2** — sus montos deben salir de los Anexos 02-04 calculados para esa segunda alternativa, no de la Alternativa 1. Si el proyecto solo plantea una alternativa técnica real (lo más común en proyectos de mejoramiento/recuperación de un local ya existente, donde tamaño/localización/tecnología quedan condicionados y no hay una segunda alternativa genuina), esta sección queda sin desarrollar — no la rellenes copiando los valores de la Alternativa 1.

## Reglas
- Nunca copiar montos ni factores de la Alternativa 1 sin que el usuario confirme explícitamente que aplican igual a la Alternativa 2.
- Si no existe una segunda alternativa técnica definida, decirlo explícitamente (dejar la sección vacía) en vez de inventarla.
MD,
            ],
        ];
    }
}
