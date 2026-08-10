<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Contexto de IA para la FTE de Servicios de Cuidado Diurno (sector Desarrollo e Inclusión Social),
// extraído del instructivo oficial del MIDIS y del documento de aprobación que acompañan a la
// plantilla Excel.
//
// Siembra dos cosas:
//  1. Dos contextos GLOBALES propios de la tipología CIAI.
//  2. El contexto LOCAL de cada sección de la plantilla FTE-CUIDADO-DIURNO, con sus globales
//     asociados.
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

    public function run(): void
    {
        $globalIds = $this->sembrarGlobales();

        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null) {
            echo 'No existe la plantilla ' . self::CODIGO_PLANTILLA . " — corre PlantillasSeeder primero.\n";

            return;
        }

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
            $this->guardarContexto((int) $plantilla['id'], $seccionId, $datos['markdown'], $ids);
            $insertados++;
        }

        echo "Listo — {$insertados} contextos de sección sembrados para " . self::CODIGO_PLANTILLA . ".\n";
    }

    /** @return array<string,int> nombre => id */
    private function sembrarGlobales(): array
    {
        $ahora = date('Y-m-d H:i:s');
        $ids   = [];

        foreach ($this->globales() as $g) {
            $existente = $this->db->table('contextos_ia_globales')->where('nombre', $g['nombre'])->get()->getRowArray();
            if ($existente === null) {
                $this->db->table('contextos_ia_globales')->insert($g + ['created_at' => $ahora, 'updated_at' => $ahora]);
                $ids[$g['nombre']] = (int) $this->db->insertID();
            } else {
                $this->db->table('contextos_ia_globales')->where('id', $existente['id'])
                    ->update(['markdown' => $g['markdown'], 'icono' => $g['icono'], 'updated_at' => $ahora]);
                $ids[$g['nombre']] = (int) $existente['id'];
            }
        }

        // Los globales ya sembrados por ContextosIAGlobalesSeeder también se pueden asociar.
        foreach ($this->db->table('contextos_ia_globales')->get()->getResultArray() as $g) {
            $ids[$g['nombre']] ??= (int) $g['id'];
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
        $fila  = $this->db->table('contextos_ia_seccion')
            ->where('plantilla_id', $plantillaId)->where('seccion_id', $seccionId)
            ->get()->getRowArray();

        if ($fila === null) {
            $this->db->table('contextos_ia_seccion')->insert([
                'plantilla_id' => $plantillaId,
                'seccion_id'   => $seccionId,
                'markdown'     => $markdown,
                'created_at'   => $ahora,
                'updated_at'   => $ahora,
            ]);
            $contextoId = (int) $this->db->insertID();
        } else {
            $contextoId = (int) $fila['id'];
            $this->db->table('contextos_ia_seccion')->where('id', $contextoId)
                ->update(['markdown' => $markdown, 'updated_at' => $ahora]);
        }

        $this->db->table('contexto_seccion_globales')->where('contexto_seccion_id', $contextoId)->delete();
        foreach (array_unique($globalIds) as $gid) {
            $this->db->table('contexto_seccion_globales')->insert([
                'contexto_seccion_id' => $contextoId,
                'contexto_global_id'  => $gid,
            ]);
        }
    }

    private function globales(): array
    {
        return [
            [
                'nombre' => 'CIAI — Cuidado Diurno',
                'icono'  => 'faHandHoldingHeart',
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
                'icono'  => 'faFileInvoice',
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
        ];
    }

    /** Clave = fragmento del nombre de la sección en la plantilla. */
    private function contextosPorSeccion(): array
    {
        return [
            'Datos Generales' => [
                'globales' => ['FTE CIAI — Reglas de uso', 'Invierte.pe'],
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
                'globales' => ['CIAI — Cuidado Diurno', 'Sociales'],
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
                'globales' => ['CIAI — Cuidado Diurno'],
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
                'globales' => ['Sociales', 'CIAI — Cuidado Diurno'],
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
                'globales' => ['CIAI — Cuidado Diurno', 'Invierte.pe'],
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
                'globales' => ['CIAI — Cuidado Diurno', 'Finanzas'],
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
        ];
    }
}
