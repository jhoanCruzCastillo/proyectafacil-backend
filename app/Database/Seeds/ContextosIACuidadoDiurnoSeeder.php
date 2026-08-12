<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Contexto de IA para la FTE de Servicios de Cuidado Diurno (sector Desarrollo e Inclusión Social),
// extraído del instructivo oficial del MIDIS y del documento de aprobación que acompañan a la
// plantilla Excel.
//
// Siembra tres cosas:
//  1. Tres contextos GENERALES (UI: "Locales") propios de esta ficha (aplican a TODA la
//     FTE-CUIDADO-DIURNO, pero no se comparten con otras fichas ni sectores): dos de reglas de la
//     tipología CIAI, y una guía de llenado campo por campo (por identificador) extraída del ejemplo
//     de referencia "4_ejemplo_anexo" — a diferencia de un contexto global de verdad como
//     "Estructura de datos" o "Invierte.pe".
//  2. El contexto LOCAL de cada sección de la plantilla, con los globales verdaderamente
//     compartidos que le apliquen (los generales de arriba se los da la IA automáticamente a toda la
//     ficha, no hace falta asociarlos sección por sección).
//
// El markdown de cada contexto se sube como archivo .md a Cloudinary (mismo patrón que Excel e
// imágenes) y solo se guarda la URL en la fila — ver CloudinaryUploader::subirMarkdown.
//
// Las secciones NO son una tabla: viven dentro de `archivos.contenido_json` del Excel asignado a la
// plantilla, y su id es un UUID generado al importar la estructura. Por eso el seeder las resuelve
// por nombre en tiempo de ejecución en vez de traer ids fijos — un id fijo no sobreviviría a un
// reimportado de la estructura.
//
// Idempotente: actualiza si ya existe.
//
// Uso: php spark db:seed ContextosIACuidadoDiurnoSeeder
class ContextosIACuidadoDiurnoSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CUIDADO-DIURNO';

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

        $this->sembrarGenerales($plantillaId);
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

    /** Sube (o actualiza) los contextos generales propios de esta ficha. */
    private function sembrarGenerales(int $plantillaId): void
    {
        $ahora = date('Y-m-d H:i:s');

        foreach ($this->generales() as $g) {
            $url = $this->cloudinary->subirMarkdown($g['markdown'], "general-{$plantillaId}-{$g['nombre']}.md");
            $existente = $this->db->table('contextos_ia_general')
                ->where('plantilla_id', $plantillaId)->where('nombre', $g['nombre'])
                ->get()->getRowArray();

            if ($existente === null) {
                $this->db->table('contextos_ia_general')->insert([
                    'plantilla_id' => $plantillaId,
                    'nombre'       => $g['nombre'],
                    'url'          => $url,
                    'created_at'   => $ahora,
                    'updated_at'   => $ahora,
                ]);
            } else {
                $this->db->table('contextos_ia_general')->where('id', $existente['id'])
                    ->update(['url' => $url, 'updated_at' => $ahora]);
            }
        }
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
            ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'],
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

    /** Contextos GENERALES: específicos de la tipología CIAI, aplican a toda esta ficha. */
    private function generales(): array
    {
        return [
            [
                'nombre' => 'CIAI — Cuidado Diurno',
                'markdown' => <<<'MD'
## Qué es
El **CIAI (Centro Infantil de Atención Integral)** es el espacio donde el Programa Nacional Cuna Más (PNCM) brinda el **Servicio de Cuidado Diurno** a niñas y niños de **6 a 36 meses** que viven en zonas de pobreza y pobreza extrema.

La unidad de medida del servicio es: **niñas y niños atendidos en el CIAI**.

## Qué comprende el servicio
- Aprendizaje infantil: cuidado y juego.
- Promoción de prácticas de cuidado de la salud del niño o niña.
- Atención alimentaria y nutricional.
- Fortalecimiento de prácticas de cuidado saludable y aprendizaje en la familia usuaria.

## Activos estratégicos del CIAI
Son los factores que limitan la capacidad de producción del servicio (RM N° 0221-2022-MIDIS):
Sala de cuidado diurno · Sala de usos múltiples · Ambiente de recreación activa · Ambiente de servicios generales · Ambiente de preparación y expendio de alimentos · Almacén · Cerco perimétrico · Muro de contención · Mobiliario de cuidado diurno · Mobiliario del ambiente de alimentos · Equipo del ambiente de alimentos.

## Las dos brechas
- **Brecha de calidad** — `PCIAICI`: porcentaje de CIAI que brindan el servicio en condición inadecuada (tienen al menos un factor de producción fuera de estándar).
- **Brecha de cobertura** — `PCIAIPI`: porcentaje de CIAI por implementar, es decir, el déficit para atender a la población objetivo.

## Reglas
- No confundir las dos brechas: mejoramiento y recuperación cierran brecha de **calidad**; ampliación cierra brecha de **cobertura**.
- El rango etario es siempre 6 a 36 meses. Si el usuario menciona otro, advertirlo.
MD,
            ],
            [
                'nombre' => 'FTE CIAI — Reglas de uso',
                'markdown' => <<<'MD'
## Cuándo aplica esta ficha
La **Ficha Técnica Estándar (FTE)** de CIAI se usa para proyectos de inversión cuya naturaleza sea **Mejoramiento, Ampliación y/o Recuperación** y cuyo monto de inversión **no supere S/ 5 000 000**. Es válida también como Ficha Técnica Simplificada.

Se debe elaborar **estudio de preinversión a nivel de Perfil** cuando:
- El monto supera S/ 5 000 000, o
- Se propone la **creación** de un CIAI, o
- Son proyectos para redes de servicios o programas de inversión.

**Nunca** usar la Ficha Técnica de Baja y Mediana Complejidad ni la FTS como documento técnico.

## Naturalezas de intervención admitidas
- **Mejoramiento**: interviene factores de producción de un CIAI existente para mejorar la calidad del servicio. → brecha de calidad.
- **Ampliación**: incrementa la capacidad del CIAI existente para atender nuevos usuarios. → brecha de cobertura.
- **Recuperación**: recupera la capacidad de un CIAI existente cuyos factores colapsaron o fueron dañados. → brecha de calidad.

> "Creación" NO es una naturaleza válida para esta ficha.

## Marco normativo
- Directiva General del SNPMGI, RD N° 001-2019-EF/63.01.
- Instructivo y FTE aprobados por la OPMI del sector Desarrollo e Inclusión Social (validados por la DGPMI del MEF, agosto 2024).

## Reglas
- Verificar que el monto declarado sea coherente con el tipo de documento técnico.
- No inventar cifras: si falta un dato, pedirlo al usuario.
MD,
            ],
            [
                'nombre'   => 'Guía de llenado campo por campo',
                'markdown' => file_get_contents(__DIR__ . '/content/fte-cuidado-diurno-campos-guia.md'),
            ],
        ];
    }

    /** Clave = fragmento del nombre de la sección en la plantilla. `globales` son SOLO los verdaderamente compartidos (Invierte.pe, Finanzas, Sociales...) — lo específico de CIAI ya lo cubren los contextos generales de esta ficha. */
    private function contextosPorSeccion(): array
    {
        return [
            'Datos Generales' => [
                'globales' => ['Invierte.pe'],
                'markdown' => <<<'MD'
## Objetivo
Definir la institucionalidad, la responsabilidad funcional, el nombre del proyecto y su alineamiento con el cierre de una brecha prioritaria.

## 1.01 Institucionalidad
- **UF (Unidad Formuladora)**: nombre registrado en el Banco de Inversiones, nivel de gobierno, entidad y responsable del órgano.
- **UEI (Unidad Ejecutora de Inversiones)**: la propuesta como responsable de la fase de ejecución, también registrada en el Banco de Inversiones.
- Las UF de gobiernos regionales y locales pueden pedir asistencia técnica a la UF del PNCM.
- Si la UEI pertenece a una entidad distinta de la que formula, se requiere **opinión favorable del órgano resolutivo** de esa entidad.

## 1.02 Responsabilidad funcional (valores fijos para CIAI)
- **Función**: 23 Protección social
- **División funcional**: 051 Asistencia social
- **Grupo funcional**: 0115 Protección de poblaciones en riesgo
- **Sector responsable**: Desarrollo e Inclusión Social

## 1.03 Nombre del proyecto
Se construye como: **naturaleza de intervención + servicio + nombre del CIAI + localización**.
Ejemplo: "Mejoramiento del Servicio de Cuidado Infantil en el Centro Infantil de Atención Integral … en la localidad de …, distrito de …, provincia de …, departamento de …".

## Reglas
- La naturaleza debe ser Mejoramiento, Ampliación o Recuperación (nunca Creación).
- Verificar coherencia entre nivel de gobierno, entidad y UF/UEI.
- No inventar nombres de entidades ni de responsables: pedirlos.
MD,
            ],
            'Territorio' => [
                'globales' => ['Sociales'],
                'markdown' => <<<'MD'
## Objetivo
Analizar el ámbito geográfico donde se ubican la población afectada y la unidad productora: área de estudio, área de influencia, macro y microlocalización, características del distrito y peligros.

## 2.01 Área de estudio
Es el área donde vive la población afectada (niñas y niños de 6 a 36 meses que demandan el servicio) **y** donde se ubica el CIAI a intervenir. Se precisa: localidad o centro poblado, distrito(s) con su **UBIGEO**, provincia y departamento.

## 2.02 Área de influencia
Espacio geográfico donde se ubica la población que demanda el servicio. Puede abarcar uno o más centros poblados, distritos o incluso provincias.

Debe enmarcarse en el **ámbito de intervención focalizada del PNCM**, que se circunscribe a distritos que cumplen al menos uno de estos criterios:
- Centros poblados urbanos de quintil 1 al 4 en departamentos con pobreza departamental mayor a 30.1%, con familias económicamente activas.
- Centros poblados urbanos de quintil 1 al 3 en departamentos con menor pobreza departamental.

## 2.05 Peligros
Identificar antecedentes de ocurrencia en el área de estudio y posibles cambios futuros, con sus características (intensidad, frecuencia, área de impacto).
Peligros a evaluar: inundaciones, movimientos en masa, lluvias intensas, heladas, nevadas, sismos, sequías, vulcanismo, tsunamis, incendios forestales y urbanos, erosión, vientos fuertes, friaje, radiación solar.
Fuente recomendada: **SIGRID – CENEPRED** (https://sigrid.cenepred.gob.pe/sigridv3/).

## Reglas
- El UBIGEO es obligatorio y determina departamento, provincia y distrito: no contradecirlo.
- Toda cifra de pobreza o población debe citar fuente y año.
MD,
            ],
            'Unidad Productora' => [
                'globales' => [],
                'markdown' => <<<'MD'
## Objetivo
Analizar las características del Servicio de Cuidado Diurno que se brinda en el CIAI y el estado situacional de sus factores de producción.

## Contenido
- **3.01 Nombre del CIAI** — lo define la Unidad Territorial del PNCM.
- **3.02 Código de identificación del CIAI**.
- **3.03 Localización geográfica de la UP**.
- **3.04 Diagnóstico de procesos** de la unidad productora.
- **3.05 Diagnóstico de los activos** de la UP.

## 3.05 Diagnóstico de activos
Por cada activo estratégico se registra: servicio y procesos de producción, tipo de factor productivo, activo estratégico, **norma técnica** aplicable, si **cumple (Sí/No)** con los estándares de calidad del PNCM, y su **estado situacional**.

Un CIAI está en condición inadecuada cuando **al menos un** factor de producción no cumple la lista de condiciones de infraestructura del servicio.

## Reglas
- Evaluar cada activo contra su norma técnica, no de forma genérica.
- Si un activo no aplica al CIAI, decirlo explícitamente en vez de marcarlo como cumplido.
- Describir también operación, mantenimiento y la evolución del servicio en los últimos años.
MD,
            ],
            'Involucrados' => [
                'globales' => ['Sociales'],
                'markdown' => <<<'MD'
## Objetivo
Describir y caracterizar a la población afectada, e identificar a los involucrados, su percepción, expectativas y nivel de participación.

## 4.01 Población afectada
Conjunto de personas afectadas por la problemática del Servicio de Cuidado Diurno, sea por falta de acceso (**cobertura**) o por recibirlo de forma inadecuada (**calidad**).

Grupos de análisis:
- **Población total**: población de los distritos del área de influencia. Fuente: **INEI**.
- **Población de referencia**: la parte de la población total dentro del grupo etario de **6 a 36 meses** que podría recibir el servicio, aplicando los criterios de focalización del PNCM.
- **Población demandante potencial**, **demandante efectiva** y **población objetivo**.

## 4.03 Matriz de involucrados
Registrar por cada actor: grupo, problemas percibidos, intereses, estrategias y acuerdos o compromisos.

## Reglas
- Toda cifra poblacional debe indicar fuente y año.
- Mantener la coherencia de estos grupos con el Análisis de Mercado: esa sección se calcula a partir de ellos.
MD,
            ],
            'Problema' => [
                'globales' => ['Invierte.pe'],
                'markdown' => <<<'MD'
## Objetivo
Identificar el problema central con sus causas y efectos, definir los objetivos y plantear alternativas de solución técnicamente posibles, pertinentes y comparables.

## 5.01 Problema central
Se identifica con la técnica del **árbol de causas y efectos**. Para proyectos de CIAI el problema central suele ser uno de estos dos:
- Las niñas y niños de 6 a 36 meses **acceden a una prestación inadecuada** del Servicio de Cuidado Diurno (el CIAI existe pero opera en condiciones inadecuadas).
- Las niñas y niños de 6 a 36 meses **no acceden** al Servicio de Cuidado Diurno (no existe el CIAI).

## 5.02 Objetivo central
Se formula como la situación positiva que revierte el problema central. Los medios se derivan de las causas y los fines de los efectos.

## 5.03 Alternativas
Deben guardar relación directa con el objetivo central y ser comparables entre sí.

## Reglas
- El problema debe redactarse como situación negativa, nunca como falta de una solución ("no hay mobiliario" no es un problema central).
- Causas y efectos deben ser consistentes con el diagnóstico del territorio, la unidad productora y los involucrados.
MD,
            ],
            'Horizonte' => [
                'globales' => ['Invierte.pe', 'Finanzas'],
                'markdown' => <<<'MD'
## Objetivo
Definir el horizonte de evaluación para determinar el flujo de costos y beneficiarios sujetos a evaluación.

## Composición
El horizonte comprende **fase de Ejecución + fase de Funcionamiento**.

- **Fase de Ejecución**: tiempo previsto para elaborar el expediente técnico o documento equivalente y para la ejecución física. Se definen fechas de inicio y fin de cada momento, incluidos los plazos de los **procesos de contratación** (tanto del expediente técnico como de la ejecución física).
- **Fase de Funcionamiento**: **10 años**, tiempo esperado durante el cual el CIAI brinda el servicio conforme a las normas técnicas del PNCM.

## Reglas
- La fase de funcionamiento es 10 años salvo justificación expresa.
- El total del horizonte es la suma de ambas fases; el Excel lo calcula solo, no sobrescribirlo.
- Las fechas deben ser coherentes entre sí: viabilidad → contratación → expediente → ejecución → funcionamiento.
MD,
            ],
            'Mercado' => [
                'globales' => ['Finanzas'],
                'markdown' => <<<'MD'
## Objetivo
Estimar oferta y demanda del Servicio de Cuidado Diurno para calcular la **brecha** que atenderá el proyecto.

## Contenido
- **7.01 Definición y caracterización del servicio** — atención integral a niñas y niños de 6 a 36 meses en zonas de pobreza y pobreza extrema. Unidad de medida: niñas y niños atendidos en el CIAI.
- **7.02 Análisis y proyección de la demanda** — población total, de referencia, demandante potencial, demandante efectiva y objetivo.
- **7.03 Proyección de la demanda** — población demandante efectiva y objetivo, con el nivel de cobertura que atenderá el proyecto.
- **7.04 Estimación de la oferta optimizada** (situación sin proyecto).
- **7.05 Proyección de la oferta**.
- **7.06 Brecha del servicio** = demanda − oferta.

## Reglas
- **Esta sección se calcula de forma automática** en la Ficha Técnica Estándar a partir de lo registrado en las secciones anteriores (sobre todo el diagnóstico de involucrados). No pedir al usuario que digite estos valores ni proponerle cifras.
- Si un resultado parece incorrecto, la corrección se hace en el diagnóstico de origen, no aquí.
- Si la brecha sale negativa o cero, advertir que el proyecto podría no estar justificado.
MD,
            ],
            'Análisis Técnico' => [
                'globales' => [],
                'markdown' => <<<'MD'
## Objetivo
Definir tamaño, localización y tecnología de la intervención, identificar medidas de reducción del riesgo de desastres, resumir las alternativas técnicas planteadas y fijar las metas físicas de los activos a crear o intervenir. Este bloque se desarrolla **una vez por cada alternativa técnica propuesta** (ver el resumen de alternativas), aunque en la ficha vive como una sola sección de análisis técnico común.

## Análisis de tamaño (¿cuánto producir?)
El tamaño depende de la población objetivo y del análisis de mercado (la brecha ya calculada). Debe cumplir la Directiva "Intervención en la infraestructura de locales del servicio de cuidado diurno" y la Directiva "Parámetros de diseño para la construcción de locales..." del PNCM. El factor condicionante típico es la **meta prevista por el PNCM para el CIAI** (niñas y niños), que determina el tipo de CIAI (ej. "Tipo III-B, 60 niños y niñas") y el área mínima requerida, con y sin servicio alimentario.

## Análisis de localización (¿dónde producir?)
Se verifica el cumplimiento de las condiciones técnicas del local — todas deben cumplirse para la situación con proyecto:
- Alejado de agentes contaminantes o peligros no mitigables.
- A no menos de 50 m de estaciones de expendio de combustible.
- A no menos de 25 m de una línea de alta tensión eléctrica.
- No colindante con hospitales/centros de salud de categoría I-3, I-4, II o III.
- Puertas de ingreso/salida no orientadas directamente a una vía de alto tránsito.
- Abastecimiento de agua, desagüe y energía eléctrica con conexión a red pública.

También se registra la titularidad del local (ej. "Propiedad del Estado") y la situación del saneamiento físico legal (ej. "Caso Gobierno Regional: Resolución y/o acuerdo de consejo que aprueba la afectación o transferencia a favor del PNCM"), más la ubicación exacta (departamento, provincia, distrito, localidad y coordenadas).

## Análisis de tecnología (¿cómo producir?)
Se usa **ingeniería conceptual**. Por cada activo del CIAI (infraestructura, mobiliario, equipo, intangibles — el mismo catálogo del diagnóstico de la Unidad Productora) se indica: descripción, si se incluye o no como parte del proyecto de inversión, y la normativa aplicable (Directiva PNCM, RNE). Los activos y su descripción vienen predefinidos en la ficha; el equipo formulador solo decide inclusión y normativa.

## Identificación de medidas de reducción del riesgo de desastres
Para cada peligro con exposición "Alto" o "Medio" (heredado del diagnóstico del territorio), se describe la medida concreta de reducción del riesgo. Ejemplos reales de esta tipología: coberturas de techos diseñadas para altos niveles de precipitación (lluvias intensas), ambientes con materiales térmicos (heladas), elementos antisísmicos según el RNE (sismos), estructuras diseñadas para vientos fuertes.

## Resumen de las alternativas técnicas
Tamaño y localización se completan automáticamente; se debe describir la **tecnología** de cada alternativa (ej. "En el diseño del proyecto se consideran los estándares definidos en la Directiva 'Parámetros de diseño...' y en la Directiva 'Equipamiento y Dotación de Materiales...' del PNCM"). Puede haber más de una alternativa técnica — cada una se evalúa por separado en la sección de Costos y de Evaluación Social correspondiente.

## Metas físicas de los activos
Se organiza por **componente** (normalmente uno por tipo de factor productivo: Infraestructura, Equipos, Mobiliario, Intangibles), y dentro de cada uno, por acción sobre el activo (Construcción, Reposición, Adquisición, Implementación de capacidades), con unidad de medida y cantidad. Ejemplo real: Componente 1 "Infraestructura del CIAI cumple con los estándares de calidad" incluye construcción de Sala de cuidado diurno (2 salas, 100.80 m² en total), Sala de usos múltiples (2 ambientes, 90 m²), Almacén (12.50 m²), Cerco perimétrico (200 ml), etc.

## Reglas
- No inventar normativa aplicable: si el usuario no la precisa, usar "Directiva PNCM, RNE" como referencia genérica y advertir que debe verificarse.
- El área/tamaño del CIAI depende de la meta de niñas y niños definida por el PNCM — no proponer una cifra sin ese dato.
- Cada activo que "no se incluye en el PI" igual debe listarse (con su descripción), simplemente marcado como no incluido — no omitirlo de la tabla.
- Anexar (o pedir al usuario) el Informe Técnico de la Propuesta de Intervención con la opinión favorable de la UT del PNCM sobre tamaño y tecnología.
MD,
            ],
            'COSTOS DEL PROYECTO - ALTERNATIVA 1' => [
                'globales' => ['Finanzas'],
                'markdown' => <<<'MD'
## Objetivo
Estimar, a precios de mercado, los costos de la **Alternativa técnica 1**: costo de ejecución física, reinversiones, operación y mantenimiento, y los cronogramas de inversión (financiero y físico).

## Costo de ejecución física de las acciones
Por cada **componente** (uno por tipo de factor productivo: Infraestructura, Equipos, Mobiliario, Intangibles) se detalla: acción sobre el activo, tipo de factor productivo, activo, unidad de medida y cantidad (unidad física y, para infraestructura, dimensión física en m²/ml), costo unitario y costo total. Esto es el **costo directo**. Ejemplo real: Componente 1 "Infraestructura del CIAI cumple con los estándares de calidad" — Sala de cuidado diurno (2 salas, 100.80 m², S/ 3,650/m², S/ 367,920), Ambiente de recreación activa (90 m², S/ 328,500), Almacén (60.48 m², S/ 220,752), Cerco perimétrico (200 ml, S/ 550/ml, S/ 110,000).

Además de los componentes, se suman **costos indirectos**: Gestión del proyecto, Expediente técnico o documento equivalente, Supervisión, Liquidación, Gastos generales — cada uno como monto y como % del costo directo. En el ejemplo oficial: Gestión del proyecto S/ 65,000 (5.0%), Expediente técnico S/ 89,700 (6.8%), Supervisión S/ 50,000 (3.8%), Liquidación S/ 30,000 (2.3%), Gastos generales S/ 150,000 (11.4%) — subtotal de otros costos S/ 384,700, costo total de inversión ≈ S/ 1,696,055 (varía según qué componentes se incluyan).

## Costos de reinversión
Activos cuya vida útil termina dentro del horizonte de evaluación (típicamente mobiliario y equipos, no la infraestructura). Se identifica el activo, su unidad de medida, cantidad, y en qué año del horizonte se reemplaza, con su costo proyectado (puede ajustarse por inflación). Ejemplo: reposición de mobiliario de sala de cuidado diurno (35 unidades) programada varios años después de la puesta en marcha.

## Costos de operación y mantenimiento con y sin proyecto
Se registra primero la fecha prevista de inicio de operaciones y el horizonte de funcionamiento (años — normalmente 10). La situación **sin proyecto** ya viene cargada automáticamente desde el diagnóstico; el equipo formulador solo completa la situación **con proyecto**: personal (actores comunales — madre cuidadora, madre guía, guía de familia, socia de cocina, repartidor, apoyo de limpieza y vigilancia — y equipo técnico del PNCM — acompañantes técnicos, especialistas integrales, especialistas en nutrición) y servicios (agua, luz, internet, seguridad, mantenimiento).

## Cronograma de inversión de metas financieras
Se define fecha prevista de inicio de ejecución, tipo de periodo (normalmente trimestre) y número de periodos. Cada componente y los costos indirectos se distribuyen en el cronograma trimestral según cuándo se ejecuta cada uno, con su costo estimado a precios de mercado. Incluye el "Control concurrente" (hasta el 2% del costo total de inversión).

## Cronograma de metas físicas
En base al cronograma financiero, se distribuyen en el tiempo las metas físicas de cada componente (m² construidos, número de equipos, número de mobiliario, número de eventos de capacitación), trimestre a trimestre, hasta llegar a la meta física total.

## Reglas
- Esta es la **Alternativa 1** — sus costos deben corresponder a la tecnología y metas físicas descritas para esta alternativa en el Análisis Técnico, no a otra alternativa.
- No inventar costos unitarios: si el usuario no los da, pedirlos o usar el ejemplo oficial como referencia solo para ilustrar el formato, dejándolo explícito.
- Verificar que el costo total de inversión del cronograma financiero coincida con el de la sección de costo de ejecución física.
MD,
            ],
            'COSTOS DEL PROYECTO - ALTERNATIVA 2' => [
                'globales' => ['Finanzas'],
                'markdown' => <<<'MD'
## Objetivo
Igual estructura y reglas que la sección de Costos del Proyecto de la Alternativa 1 (costo de ejecución física por componente, costos indirectos, reinversión, operación y mantenimiento, cronograma financiero y cronograma físico) — ver ese contexto para el detalle de cada campo y sus ejemplos.

## Diferencia con la Alternativa 1
Esta es la **Alternativa técnica 2**: sus montos y metas físicas deben corresponder a la tecnología descrita para esta segunda alternativa en el Análisis Técnico (Resumen de las alternativas técnicas). Si el proyecto solo tiene una alternativa técnica real, esta sección puede quedar sin desarrollar — no rellenarla copiando los valores de la Alternativa 1.

## Reglas
- Nunca copiar montos de otra alternativa sin que el usuario confirme que aplican igual.
- Si el usuario no ha definido una segunda alternativa técnica, decirlo explícitamente en vez de inventar una.
MD,
            ],
            'COSTOS DEL PROYECTO - ALTERNATIVA 3' => [
                'globales' => ['Finanzas'],
                'markdown' => <<<'MD'
## Objetivo
Igual estructura y reglas que la sección de Costos del Proyecto de la Alternativa 1 — ver ese contexto para el detalle de cada campo y sus ejemplos.

## Diferencia con las otras alternativas
Esta es la **Alternativa técnica 3**: sus montos y metas físicas deben corresponder a la tecnología descrita para esta tercera alternativa en el Análisis Técnico. La mayoría de proyectos de CIAI solo plantean 1 o 2 alternativas técnicas — si no existe una tercera, esta sección queda sin desarrollar.

## Reglas
- Nunca copiar montos de otra alternativa sin que el usuario confirme que aplican igual.
- Si el usuario no ha definido una tercera alternativa técnica, decirlo explícitamente en vez de inventarla.
MD,
            ],
            'EVALUACIÓN SOCIAL - ALTERNATIVA 1' => [
                'globales' => ['Finanzas', 'Invierte.pe'],
                'markdown' => <<<'MD'
## Objetivo
Identificar, medir y valorizar los beneficios y costos de la **Alternativa técnica 1** desde el punto de vista del bienestar social del país, estimar los indicadores de rentabilidad social y hacer el análisis de sensibilidad. Con el resultado de esta evaluación (comparando todas las alternativas) se selecciona la mejor alternativa técnica.

## Beneficios sociales
Se relacionan con los fines del proyecto (los efectos que revierte el problema central). Para CIAI son siempre los mismos cinco, ya predefinidos en la ficha: mayor acceso a raciones alimentarias y suplemento con hierro; suficiente seguimiento al control CRED; adecuado desarrollo cognitivo; ingesta adecuada de nutrientes; mayor nivel de desarrollo infantil temprano en niñas y niños de 6 a 36 meses en pobreza o pobreza extrema. No se valorizan monetariamente — se listan como parte del sustento cualitativo.

## Costos sociales
Se aplican **factores de corrección** (parámetros del Anexo N° 11 del SNPMGI) a los costos de inversión y a los de operación y mantenimiento, para pasar de precio de mercado a precio social. **Esta sección se calcula automáticamente** en la ficha a partir de los Costos del Proyecto de esta misma alternativa — no pedir al usuario que digite los costos sociales a mano, solo verificar que el costo de inversión de origen esté completo.

## Flujo de costos a precios sociales
Se arma con los costos de inversión, reinversión y operación/mantenimiento incremental (con proyecto menos sin proyecto) a precios sociales, año por año, a lo largo de todo el horizonte de evaluación. Incluye el valor de recuperación de la inversión en infraestructura (10%) en el último año.

## Indicadores de rentabilidad social
Para CIAI se usa la metodología **costo-eficacia** (no costo-beneficio, porque valorizar monetariamente los beneficios es muy complejo). El indicador es el ratio **costo-eficacia (CE)**: Valor Actual de los Costos Sociales (VACS) entre el total de niñas y niños de 6 a 36 meses que accederán al servicio (población objetivo) en todo el horizonte. **Se calcula automáticamente.**

## Análisis de sensibilidad
Matriz bidimensional que muestra cómo varía el índice costo-eficacia ante cambios porcentuales simultáneos en los costos del proyecto (eje columnas, ej. -20% a +20%) y en el total de beneficiarios (eje filas). Se genera automáticamente a partir de los datos ya registrados.

## Reglas
- Costos sociales, flujo, indicadores y sensibilidad son **cálculos automáticos** de la ficha — no se le pide al usuario que los calcule ni se le proponen cifras; solo se verifica que los insumos (costos de inversión, O&M, horizonte) estén completos.
- Esta evaluación corresponde a la Alternativa 1 — debe alimentarse de los Costos del Proyecto de esa misma alternativa, no de otra.
MD,
            ],
            'EVALUACIÓN SOCIAL - ALTERNATIVA 2' => [
                'globales' => ['Finanzas', 'Invierte.pe'],
                'markdown' => <<<'MD'
## Objetivo
Igual estructura y reglas que la Evaluación Social de la Alternativa 1 (beneficios sociales, costos sociales, flujo a precios sociales, indicadores costo-eficacia calculados automáticamente, análisis de sensibilidad) — ver ese contexto para el detalle.

## Diferencia con la Alternativa 1
Se alimenta de los **Costos del Proyecto de la Alternativa 2**, no de la Alternativa 1. Si esa segunda alternativa técnica no se desarrolló, esta sección queda sin completar — no debe rellenarse con los resultados de la Alternativa 1.

## Reglas
- Costos sociales, flujo, indicadores y sensibilidad son cálculos automáticos — no proponer cifras a mano.
- Al final, la alternativa con mejor ratio costo-eficacia (menor costo por beneficiario) es la que normalmente se selecciona para Sostenibilidad, Gestión, Impacto Ambiental y Marco Lógico.
MD,
            ],
            'EVALUACIÓN SOCIAL - ALTERNATIVA 3' => [
                'globales' => ['Finanzas', 'Invierte.pe'],
                'markdown' => <<<'MD'
## Objetivo
Igual estructura y reglas que la Evaluación Social de la Alternativa 1 — ver ese contexto para el detalle.

## Diferencia con las otras alternativas
Se alimenta de los **Costos del Proyecto de la Alternativa 3**. Si no existe una tercera alternativa técnica, esta sección queda sin completar.

## Reglas
- Costos sociales, flujo, indicadores y sensibilidad son cálculos automáticos — no proponer cifras a mano.
MD,
            ],
            'SOSTENIBILIDAD' => [
                'globales' => [],
                'markdown' => <<<'MD'
## Objetivo
Analizar la capacidad del proyecto para producir el Servicio de Cuidado Diurno de manera ininterrumpida durante todo el horizonte de evaluación. **Se desarrolla solo para la alternativa que resultó seleccionada en la Evaluación Social** (la de mejor costo-eficacia), no para todas.

## Descripción de la capacidad institucional en la sostenibilidad del proyecto
Se describen tres aspectos, cada uno con su documento de opinión favorable: (1) el **órgano técnico responsable** de la operación y mantenimiento del CIAI — para esta tipología siempre es el PNCM; (2) el **análisis de disponibilidad oportuna de recursos** para operación y mantenimiento; (3) los **arreglos institucionales** para la fase de Funcionamiento (ej. un convenio entre el PNCM y la municipalidad distrital donde se ubica el CIAI).

## Gestión integral de los riesgos
Se identifican los riesgos que pueden afectar al proyecto en Ejecución y Funcionamiento, con su probabilidad de ocurrencia y nivel de impacto (escala: Muy Alta/Alta/Moderada/Baja/Muy Baja de probabilidad × Muy Bajo/Bajo/Moderado/Alto/Muy Alto de impacto, adaptada de PMBOK), el nivel de riesgo resultante (probabilidad × impacto) y la medida de mitigación. Los riesgos típicos de esta tipología, ya predefinidos: (A) saneamiento físico legal o arreglo institucional pendiente; (B) los activos tendrán más de una ubicación; (C) impactos ambientales o conflictos sociales del proyecto; (D) la UEI propuesta no tiene experiencia en esta tipología; (E) modalidad de ejecución por núcleos ejecutores; (F) exposición a peligros naturales/socionaturales/antrópicos; (G) ubicación en zonas arqueológicas o patrimonio cultural; (H) interferencias con redes de agua, desagüe, gas o telefonía.

## Reglas
- No desarrollar esta sección para todas las alternativas — solo para la seleccionada.
- El órgano responsable de operación y mantenimiento de un CIAI es siempre el PNCM; no proponer otra entidad salvo que el usuario lo indique explícitamente.
- Cada riesgo necesita su medida de mitigación — no dejarlo solo con la probabilidad/impacto sin proponer qué hacer al respecto.
MD,
            ],
            'GESTIÓN DEL PROYECTO' => [
                'globales' => [],
                'markdown' => <<<'MD'
## Objetivo
Planear, ejecutar, supervisar y controlar la implementación de los componentes del proyecto. **Se desarrolla solo para la alternativa seleccionada** en la Evaluación Social.

## Plan de implementación
Detalla actividades y tareas con secuencia, ruta crítica, duración, responsable y recursos — considerando un escenario realista de plazos de contratación. Estructura típica: Expediente Técnico o Estudio Definitivo (elaboración), Supervisión (de la ejecución), Ejecución (una fila por componente: Infraestructura, Equipos, Mobiliario, Capacidades), cada uno con fecha de inicio, fecha de fin, órgano responsable (ej. "PNCM - UT Cusco") y en qué periodo/trimestre ocurre.

## Modalidad de ejecución de proyecto
Una sola opción entre las cinco oficiales: Administración directa, Administración indirecta – por contrata, Administración indirecta – Asociación Público Privado (APP), Administración indirecta – Núcleo Ejecutor, Administración indirecta – Ley 29230 (Obras por Impuestos).

## Requerimientos institucionales y normativos en la fase de Ejecución y de Funcionamiento
Se marca el estado situacional de cada condición previa relevante: saneamiento técnico legal o arreglo institucional, factibilidad de servicios de agua/desagüe/electricidad, certificado de parámetros urbanísticos, licencia de construcción — indicando si ya se cuenta con ella o cuándo se tramitará (ej. "se tramitará durante la elaboración del Expediente Técnico").

## Entidad a cargo de la operación y mantenimiento
Para esta tipología, siempre el **Programa Nacional Cuna Más**.

## Fuente de financiamiento
Una sola opción entre las cinco oficiales: Recursos ordinarios, Recursos directamente recaudados, Recursos por operaciones oficiales de crédito, Donaciones y transferencias, Recursos determinados.

## Reglas
- La modalidad de ejecución y la fuente de financiamiento son selección única entre las opciones oficiales — no inventar una modalidad ni fuente distinta.
- El plan de implementación debe ser coherente con el cronograma de inversión de la alternativa seleccionada (mismas fechas y componentes).
MD,
            ],
            'IMPACTO AMBIENTAL' => [
                'globales' => [],
                'markdown' => <<<'MD'
## Objetivo
Identificar los impactos negativos que el proyecto puede generar sobre el ambiente, durante Ejecución y Funcionamiento, y plantear medidas de gestión ambiental (prevención, corrección, mitigación). **Se desarrolla para la alternativa seleccionada.**

## Impacto ambiental
Se listan los impactos negativos separados por fase — "Durante la Ejecución" y "Durante el Funcionamiento" — cada uno con su medida de mitigación y el costo de implementarla. Ejemplo real: "Generación de ruido y emisión de polvareda durante el proceso de construcción" → medida: "acciones dirigidas a controlar y evitar la generación de ruido y polvo" → costo: "se incluye dentro de las actividades previstas en el presupuesto del proyecto" (no genera un costo adicional aparte). Si no hay impactos relevantes en alguna fase, se indica "Ninguna".

## Reglas
- El costo de las medidas de mitigación debe formar parte del costo de inversión del proyecto (ya registrado en Costos del Proyecto), no un monto nuevo y separado.
- Si el usuario no identifica impactos ambientales, no inventarlos — pero sí verificar explícitamente ambas fases (Ejecución y Funcionamiento) antes de dar la sección por completa.
MD,
            ],
            'MARCO LÓGICO' => [
                'globales' => ['Invierte.pe'],
                'markdown' => <<<'MD'
## Objetivo
Construir el Marco Lógico: la herramienta que resume la coherencia y consistencia del proyecto, a partir del árbol de objetivos y las acciones para lograr los medios fundamentales. **Se desarrolla para la alternativa seleccionada en la Evaluación Social**, y la columna de objetivos debe ser consistente con lo ya definido en Problema/Objetivo y en Costos del Proyecto.

## Estructura de la matriz
Cuatro niveles de objetivo, cada uno con indicador, valor meta, medios de verificación y supuestos:
- **Fin** — el efecto final (ej. "Mayor nivel de desarrollo infantil temprano de los niños y niñas menores de 36 meses que viven en situación de pobreza y pobreza extrema, en [localidad, distrito, provincia, departamento]"), medido por ejemplo como "% de niños/as menores de 36 meses que logran los hitos de desarrollo infantil temprano esperados para su edad" (valor meta ilustrativo: 85%), verificado con la Encuesta de Salud y Desarrollo en la Primera Infancia.
- **Propósito** — el objetivo central (ej. "Niños y niñas entre 6 y 36 meses acceden a una adecuada prestación del servicio de cuidado diurno en [localización]"), con su indicador de cobertura/calidad (ej. "% de niños y niñas con mínimo 6 meses de permanencia que cumplen los estándares de calidad del PNCM", valor meta ilustrativo 95%) y supuestos (ej. "padres toman conciencia de la importancia del cuidado", "actores comunales comprometidos").
- **Componentes** — uno por cada componente definido en Metas Físicas y Costos (ej. Componente 1: Infraestructura del CIAI cumple con los estándares de calidad; Componente 2: Equipos; Componente 3: Mobiliario; Componente 4: Suficientes capacidades organizacionales de los recursos humanos), cada uno con su indicador de meta física (m² de infraestructura, número de equipos, número de mobiliario, número de sesiones de capacitación) y su valor, verificado en el Registro del Formato N° 09 del Banco de Inversiones.
- **Acciones** — una por componente, con el monto de ejecución (ej. "Construcción, habilitación y/o adecuación de infraestructura del CIAI — ejecución del componente por un monto de S/ ...") y su medio de verificación (recepción y conformidad de la obra/equipamiento/mobiliario/capacitación).

## Reglas
- Los montos de las Acciones deben coincidir con los del Costo de Ejecución Física de la alternativa seleccionada — no son cifras nuevas.
- Los Componentes deben ser exactamente los mismos que en Metas Físicas de los Activos (mismo número, mismo nombre) de esa alternativa.
- No inventar indicadores: si el usuario no da un valor meta, dejarlo pendiente y pedirlo en vez de aproximarlo.
MD,
            ],
            'CONCLUSIONES' => [
                'globales' => ['Invierte.pe'],
                'markdown' => <<<'MD'
## Objetivo
Indicar el resultado del proceso de formulación y evaluación del proyecto — **viable o no viable** — y sustentarlo.

## Qué debe contener
a. Precisar y sustentar cuál de las alternativas técnicas propuestas es la seleccionada (normalmente la de mejor ratio costo-eficacia en la Evaluación Social).
b. Sustentar el cumplimiento de los tres atributos que definen la viabilidad de un proyecto de inversión (si es viable); si no lo es, indicar qué atributo(s) no se cumplió.
c. Emitir un juicio técnico sobre la calidad y pertinencia del nivel de profundización de la información empleada, y la consistencia de los supuestos, fuentes, normas técnicas, parámetros y metodologías usadas en toda la ficha.

## Reglas
- La alternativa que se declara seleccionada aquí debe ser la misma que se usó para Sostenibilidad, Gestión del Proyecto, Impacto Ambiental y Marco Lógico — verificar coherencia antes de cerrar la ficha.
- No declarar "viable" sin que los tres atributos estén efectivamente sustentados en las secciones anteriores.
MD,
            ],
            'ANEXOS' => [
                'globales' => [],
                'markdown' => <<<'MD'
## Objetivo
Listar y adjuntar todos los anexos requeridos por la Ficha Técnica Estándar, firmados, visados y sellados por los especialistas correspondientes.

## Anexos requeridos
A. Resumen ejecutivo.
B. Informe técnico de la situación actual de la Unidad Productora (infraestructura, mobiliario, equipamiento y capacitación) — sustenta el Diagnóstico de la Unidad Productora.
C. Evidencias de talleres, reuniones y actividades similares (fotografías, listas firmadas de participantes, actas de acuerdos).
D. Informe técnico de la propuesta de intervención (infraestructura, mobiliario, equipamiento y capacitación) — sustenta el Análisis Técnico, con opinión favorable de la UT del PNCM sobre tamaño y tecnología.
E. Opinión favorable del Programa Nacional Cuna Más a la propuesta de intervención.
F. Detalle del costo de inversión de las alternativas técnicas — sustenta el Costo de Ejecución Física de las Acciones, elaborado y suscrito por un equipo multidisciplinario.
G. Documento de opinión favorable del PNCM para la sostenibilidad del proyecto — sustenta la sección de Sostenibilidad.
H. Documento de sustento del saneamiento físico legal o arreglo institucional que corresponda.

## Reglas
- No marcar un anexo como presentado si el usuario no confirmó tener el documento — cada uno respalda una sección específica de la ficha (indicado arriba) y su ausencia debe advertirse, no omitirse en silencio.
MD,
            ],
        ];
    }
}
