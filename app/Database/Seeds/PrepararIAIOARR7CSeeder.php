<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// "Descripción / ayuda" campo por campo del Formato N.° 07-C — Registro de IOARR, destilada de los
// Lineamientos para la identificación y registro de las IOARR (MEF, 77 páginas).
//
// Ojo con la diferencia respecto de los otros seeders de este tipo: esto NO es una ficha técnica.
// El 07-C es un formato de REGISTRO en el Banco de Inversiones con carácter de Declaración Jurada,
// así que las descripciones no explican cómo formular ni cómo evaluar alternativas — explican qué
// dato va en la casilla, de dónde sale y qué regla lo invalida.
//
// La descripción cumple dos funciones a la vez:
//  1. Va al prompt del llenado con IA — construirPromptSeccion() la manda por campo, y
//     construirPromptTabla() la manda por tabla (ver docs/llenado-automatico-ia.md §8.1).
//  2. Es lo que el cliente lee en el modal del "?" de cada tarjeta (CampoAyudaModal.vue).
//
// Alcance: los 128 campos del formato.
//
// Lo que más pesa acá son los BLOQUES EXCLUYENTES, y por eso varias descripciones lo repiten:
//  - El tipo de IOARR (campo 3.02.01) decide cuál de los diez bloques de la sección E se llena.
//  - El umbral de 75 UIT decide si va el registro completo (6.x) o el simplificado (7.x) — nunca
//    los dos. Sin ese recordatorio en la descripción, el modelo tiende a llenar todo "por si acaso".
//
// Idempotente: solo escribe donde la descripción está vacía; correrlo dos veces no pisa nada.
// Uso: php spark db:seed PrepararIAIOARR7CSeeder
class PrepararIAIOARR7CSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = '7C';

    /** identificador => descripción del campo. */
    private const DESCRIPCIONES = [
        // ================================ ENCABEZADO ==========================================
        '0.01.01' => 'Nombre de la inversión. Se construye con una fórmula fija: ACCIONES + ACTIVOS + '
            . 'NOMBRE DE LA UNIDAD PRODUCTORA + LOCALIZACION GEOGRAFICA de la UP. Ejemplo real: '
            . '"ADQUISICION DE EQUIPO Y MOBILIARIO DE AULAS DE INNOVACION PEDAGOGICA Y EQUIPO Y '
            . 'MOBILIARIO DE LABORATORIO Y TALLERES PARA LA IE EDELMIRA DEL PANDO, DISTRITO DE ATE, '
            . 'PROVINCIA LIMA, REGION DE LIMA". No es una descripción libre: si falta alguno de los '
            . 'cuatro elementos, el nombre está mal formado.',
        '0.01.02' => 'Código único de la inversión, solo si el Banco de Inversiones ya lo asignó. Si '
            . 'la inversión es nueva y todavía no tiene código, el campo va VACIO — nunca se inventa '
            . 'ni se usa un correlativo interno.',
        '0.01.03' => 'Marcar con X si la inversión se enmarca en un Decreto Supremo (Sí) o no (No). '
            . 'Se marca una sola de las dos opciones.',
        '0.01.04' => 'Número del Decreto Supremo. Solo se llena si la respuesta anterior fue Sí.',

        // ====================== A. ALINEAMIENTO A UNA BRECHA PRIORITARIA =======================
        '1.01.01' => 'Función del clasificador de responsabilidad funcional a la que corresponde la '
            . 'inversión (por ejemplo EDUCACION, SALUD, TRANSPORTE). Sale del Anexo N.02 de la '
            . 'Directiva 001-2019-EF/63.01.',
        '1.01.02' => 'División funcional, dentro de la función elegida (por ejemplo EDUCACION BASICA).',
        '1.01.03' => 'Grupo funcional, dentro de la división (por ejemplo EDUCACION SECUNDARIA).',
        '1.01.04' => 'Sector responsable de la inversión (por ejemplo EDUCACION).',
        '1.01.05' => 'Servicio público con brecha identificada y priorizada que atiende la inversión '
            . '(por ejemplo SERVICIO DE EDUCACION SECUNDARIA). Sale del diagnóstico de brechas del '
            . 'Sector. Se puede incluir MAS DE UN servicio.',
        '1.01.06' => 'Tipología de proyecto asociada al servicio (por ejemplo EDUCACION SECUNDARIA). '
            . 'Ojo: para una Ampliación Marginal del Servicio la tipología debe estar ESTANDARIZADA '
            . 'por el Sector del Gobierno Nacional; si no lo está, no cabe ese tipo de IOARR.',
        '1.01.07' => 'Indicadores de brecha. Por cada uno: el TIPO (Calidad o Cobertura), el nombre '
            . 'del indicador, su unidad de medida y la contribución del proyecto a la brecha del PMI '
            . '(valor). Se puede incluir más de un indicador y más de un servicio. Ejemplo: tipo '
            . 'Calidad, indicador "Porcentaje de locales educativos con educación secundaria con '
            . 'capacidad instalada inadecuada", unidad LOCAL EDUCATIVO, contribución 1; y tipo '
            . 'Cobertura, indicador "Porcentaje de personas no matriculadas en el nivel secundaria '
            . 'respecto a la demanda potencial", unidad ALUMNO/AÑO, contribución 443.',
        '1.01.08' => 'Espacio geográfico de la brecha: Nacional, Departamental, Provincial o '
            . 'Distrital. Se consigna el ámbito concreto (por ejemplo "LIMA, LIMA, ATE").',

        // ============================= B. INSTITUCIONALIDAD ===================================
        '2.01.01' => 'Nivel de gobierno de la OPMI: Nacional, Regional o Local.',
        '2.01.02' => 'Entidad (pliego) a la que pertenece la OPMI.',
        '2.01.03' => 'Nombre de la Oficina de Programación Multianual de Inversiones.',
        '2.01.04' => 'Nombre completo del responsable de la OPMI.',
        '2.01.05' => 'Nivel de gobierno de la Unidad Formuladora: Nacional, Regional o Local.',
        '2.01.06' => 'Entidad (pliego) a la que pertenece la Unidad Formuladora.',
        '2.01.07' => 'Nombre de la Unidad Formuladora.',
        '2.01.08' => 'Nombre completo del responsable de la Unidad Formuladora.',
        '2.01.09' => 'Nivel de gobierno de la Unidad Ejecutora de Inversiones.',
        '2.01.10' => 'Entidad (pliego) a la que pertenece la Unidad Ejecutora de Inversiones.',
        '2.01.11' => 'Nombre de la Unidad Ejecutora de Inversiones que se recomienda para ejecutar.',
        '2.01.12' => 'Nombre completo del responsable de la Unidad Ejecutora de Inversiones.',
        '2.01.13' => 'Nombre de la Unidad Ejecutora Presupuestal.',

        // ============================== C. DATOS GENERALES ====================================
        '3.01.01' => 'Código de identificación de la Unidad Productora, en caso el Sector lo haya '
            . 'definido: código modular, código de establecimiento, código de rutas, código de '
            . 'inventario de recursos turísticos, etc. Se toma del registro sectorial que '
            . 'corresponda, no se inventa.',
        '3.01.02' => 'Nombre de la Unidad Productora de bienes y/o servicios sobre la que se '
            . 'interviene (por ejemplo el nombre de la institución educativa o del establecimiento).',
        '3.01.03' => 'Localización de la Unidad Productora: departamento, provincia, distrito, centro '
            . 'poblado y coordenadas UTM (latitud y longitud).',
        '3.01.04' => 'Solo para UNIDADES PRODUCTORAS LINEALES (carreteras, redes, canales): se '
            . 'adjunta un archivo KML/Excel con las coordenadas UTM y el número de orden secuencial. '
            . 'Si la UP no es lineal, no aplica.',
        '3.02.01' => 'Tabla que DECIDE el resto del formato. Por cada activo se consigna: si la '
            . 'inversión es mayor a 75 UIT (Sí/No), el TIPO DE IOARR, la ACCION sobre el activo, el '
            . 'ACTIVO y el TIPO DE FACTOR PRODUCTIVO. Es posible considerar MAS DE UN tipo de '
            . 'inversión por Unidad Productora. Dos reglas del instructivo: si el activo no está en '
            . 'la base de datos, la UF debe pedir su inclusión a la OPMI del Sector indicando a qué '
            . 'UP pertenece y qué función cumple; y NO se coloca el nombre del activo cuando la '
            . 'IOARR es de Liberación de Interferencias.',

        // ============ D. DATOS DE INVERSION (activos con monto MAYOR a 75 UIT) ================
        '4.01.01' => 'Solo para infraestructura: marcar con X si el activo sujeto a rehabilitación, '
            . 'optimización o ampliación marginal tiene inscripción registral (Sí/No).',
        '4.01.02' => 'Número de la partida registral del activo. Solo si la respuesta anterior fue Sí.',
        '4.01.03' => 'Nombre de la oficina registral donde está inscrita la partida (por ejemplo '
            . 'LIMA NORTE). Solo si hay inscripción registral.',
        '4.01.04' => 'Si el activo NO tiene inscripción registral: descripción del documento que '
            . 'sustenta el inicio del saneamiento físico legal, en caso corresponda.',
        '4.01.05' => 'Marcar con X si el activo está registrado en el inventario de la entidad '
            . 'pública (Sí/No). Recordar que todos los activos generados por PI e IOARR deben formar '
            . 'parte del inventario de activos de la entidad titular.',
        '4.01.06' => 'Código del inventario del activo. Solo si la respuesta anterior fue Sí.',
        '4.02.01' => 'Unidad Ejecutora Presupuestal que asumirá el financiamiento del mantenimiento '
            . 'de los activos (por ejemplo la UGEL correspondiente).',
        '4.02.02' => 'Solo si una organización PRIVADA asumirá el financiamiento del mantenimiento: '
            . 'nombre de esa organización. Si el mantenimiento lo asume la entidad pública, va vacío.',

        // ===================== E.1 — INVERSIONES DE OPTIMIZACION ==============================
        // Este bloque se llena SOLO si el tipo de IOARR elegido en 3.02.01 es Optimización.
        '5.01.01' => 'Descripción del estado situacional de la oferta existente de la UP que motiva '
            . 'la optimización. Se describe qué activos están subutilizados o mal empleados y cómo '
            . 'eso limita la capacidad actual. Sale del informe de diagnóstico de la UP.',
        '5.01.02' => 'Restricciones a la provisión del servicio: qué impide hoy prestar el servicio '
            . 'con la calidad o cantidad adecuadas.',
        '5.01.03' => 'Problema operativo identificado, expresado en términos concretos y medibles '
            . '(cuántos usuarios se atienden, cuál es la capacidad, qué activos exceden su vida útil).',
        '5.01.04' => 'Informe sobre el análisis de la oferta del servicio que respalda lo declarado. '
            . 'Es un adjunto.',
        '5.01.05' => 'Tabla del objetivo de la optimización: se marca con X el o los objetivos que apliquen. Las opciones '
            . 'son: a) aumentar el nivel de calidad del servicio para satisfacer un cambio menor en '
            . 'la demanda; b) aumentar la cantidad producida para satisfacer un cambio menor en la '
            . 'demanda; c) aumentar el número de usuarios atendidos; d) mejorar procesos para reducir '
            . 'tiempos de producción; e) mejorar procesos para reducir tiempos del usuario (colas y '
            . 'desplazamientos); f) reducir costos de producción. La optimización PUEDE TENER MAS DE '
            . 'UN OBJETIVO.',
        '5.01.06' => 'La intervención: qué se va a adquirir, reparar o reorganizar concretamente, '
            . 'activo por activo. Es la descripción operativa de lo que se ejecutará.',
        '5.01.07' => 'Unidad de medida de la capacidad de producción del servicio (por ejemplo '
            . 'Alumnos/año, turistas/día, tn/día, kwh, m3/s).',
        '5.01.08' => 'Capacidad de DISEÑO de la UP: para cuánto fue diseñada originalmente. Es el '
            . 'denominador del límite del 20 %.',
        '5.01.09' => 'Capacidad de producción ACTUAL del servicio, antes de la intervención. En una '
            . 'optimización debe ser INFERIOR a la capacidad de diseño. Excepcionalmente puede ser '
            . 'nula, siempre que la UP no lleve más de un año inoperativa.',
        '5.01.10' => 'Capacidad de producción del servicio CON la optimización (capacidad final). '
            . 'Debe ser la capacidad óptima de la UP.',
        '5.01.11' => 'Incremento porcentual de la capacidad con la optimización. IMPORTANTE: el '
            . 'máximo es 20 % sobre la capacidad de DISEÑO. Si el incremento supera ese límite, la '
            . 'intervención ya no es una IOARR y debe formularse como Proyecto de Inversión de '
            . 'Mejoramiento (sin cambio de cobertura) o de Ampliación (con cambio de cobertura).',

        // ============ E.2 — AMPLIACION MARGINAL DEL SERVICIO ==================================
        '5.02.01' => 'Descripción del estado situacional de la oferta existente de la UP que motiva '
            . 'la ampliación marginal del servicio. A diferencia de la optimización, acá la capacidad '
            . 'actual está CERCANA a la demanda y/o a la capacidad de diseño: el problema es que '
            . 'llegan nuevos usuarios.',
        '5.02.02' => 'Número de usuarios atendidos actualmente, sin la IOARR.',
        '5.02.03' => 'Número de potenciales usuarios que hoy NO están siendo atendidos. Es el '
            . 'incremento de cobertura que justifica la ampliación marginal.',
        '5.02.04' => 'Consumo estimado por familia o por conexión domiciliaria, cuando el servicio se '
            . 'mide así (típico en saneamiento).',
        '5.02.05' => 'Tamaño de población para la cual se diseñó la UP, sin la IOARR.',
        '5.02.06' => 'Población actual en el área de atención de la UP.',
        '5.02.07' => 'Unidad de medida de la capacidad de producción del servicio.',
        '5.02.08' => 'Capacidad de DISEÑO original de la UP.',
        '5.02.09' => 'Capacidad de producción actual del servicio, antes de la IOARR.',
        '5.02.10' => 'Capacidad de producción CON la IOARR (capacidad final).',
        '5.02.11' => 'Incremento porcentual de la capacidad con la ampliación marginal. IMPORTANTE: '
            . 'máximo 20 % sobre la capacidad de diseño original, y el incremento debe deberse '
            . 'UNICAMENTE a mayor cobertura (nuevos usuarios). Si supera el 20 %, corresponde un '
            . 'Proyecto de Inversión de Ampliación.',

        // ======= E.3 — AMPLIACION MARGINAL DE LA EDIFICACION U OBRA CIVIL =====================
        '5.03.01' => 'Sustento de la necesidad de la edificación u obra civil nueva y adicional: por '
            . 'qué hace falta y por qué constituye una ampliación marginal y no un proyecto.',
        '5.03.02' => 'La intervención: qué edificación u obra civil se construirá, con sus '
            . 'características principales.',

        // ==== E.4 — AMPLIACION MARGINAL PARA ADQUISICION ANTICIPADA DE TERRENOS ===============
        '5.04.01' => 'Documento o informe de planificación de la ampliación de la capacidad de '
            . 'producción que sustenta la necesidad del terreno.',
        '5.04.02' => 'Código de idea o código único del proyecto, inversión de optimización o '
            . 'ampliación marginal a la que servirá el terreno. Es la INVERSION PRINCIPAL que '
            . 'justifica la adquisición anticipada.',
        '5.04.03' => 'Información sobre la inversión principal a la que está asociado el terreno.',
        '5.04.04' => 'Área del terreno requerida, en m2.',
        '5.04.05' => 'Tabla con la ubicación estimada del terreno a adquirir: departamento, provincia, distrito y referencia.',
        '5.04.06' => 'Uso futuro del terreno y justificación del dimensionamiento del área '
            . 'requerida: por qué ese tamaño y no otro.',
        '5.04.07' => 'Copia del sustento indicado. Es un adjunto.',
        '5.04.08' => 'Normas técnicas aplicables, detallando los acápites o artículos que establecen '
            . 'el requerimiento de área. Recordar que NO se puede adquirir un segundo terreno para la '
            . 'misma edificación de una UP.',

        // ======= E.5 — AMPLIACION MARGINAL PARA LIBERACION DE INTERFERENCIAS ==================
        '5.05.01' => 'Nombre del proyecto de inversión en formulación y evaluación al que corresponde '
            . 'la liberación de interferencias.',
        '5.05.02' => 'Tabla con la localización geográfica de la liberación de interferencia. RECORDAR: en este '
            . 'tipo de IOARR NO se coloca el nombre del activo.',

        // ============================ E.6 — REPOSICION ========================================
        '5.06.01' => 'Sustento de la necesidad de la reposición: por qué el activo llegó al fin de su '
            . 'vida útil y debe reemplazarse. Cuidado: NO se puede usar una reposición para '
            . 'incrementar la capacidad de la UP — eso sería fraccionamiento.',
        '5.06.02' => 'Tabla de la intervención sobre los activos a reponer: una fila por activo, con lo que se repone.',
        '5.06.03' => 'Otras inversiones asociadas a la reposición, si las hubiera.',
        '5.06.04' => 'Antigüedad, en años, del equipo, mobiliario o vehículo a reponer.',
        '5.06.05' => 'Estado actual del equipo, mobiliario o vehículo a reponer.',
        '5.06.06' => 'Costo anual de mantenimiento del equipo, mobiliario o vehículo A REPONER (el '
            . 'viejo). Se compara con el del nuevo para justificar la reposición.',
        '5.06.07' => 'Expectativa de vida útil, en años, del equipo, mobiliario o vehículo NUEVO.',
        '5.06.08' => 'Costo anual de mantenimiento del equipo, mobiliario o vehículo NUEVO.',

        // ================= E.7 — REHABILITACION DE INFRAESTRUCTURA ============================
        '5.07.01' => 'Sustento de la necesidad de la rehabilitación de infraestructura: qué deterioro '
            . 'medible presenta y cómo amenaza la continuidad del servicio. Recordar que el '
            . 'mantenimiento permanente NO es IOARR, y que remodelar o reparar instalaciones '
            . 'sanitarias o eléctricas por sí solo tampoco lo es.',
        '5.07.02' => 'La intervención en infraestructura: qué se rehabilita concretamente.',
        '5.07.03' => 'Unidad de medida de la dimensión física de la infraestructura de la UP (por '
            . 'ejemplo m2, ml, km).',
        '5.07.04' => 'Valor de la dimensión física TOTAL de la UP, en la unidad indicada.',
        '5.07.05' => 'Valor de la dimensión física que abarca LA REHABILITACION. Es una parte del '
            . 'total anterior.',

        // ================ E.8 — REHABILITACION DE EQUIPOS MAYORES =============================
        '5.08.01' => 'Valor de mercado actual del equipo mayor a reparar.',
        '5.08.02' => 'Sustento de la necesidad de la rehabilitación del equipo mayor, con el reporte '
            . 'técnico que acredite el deterioro.',
        '5.08.03' => 'Copia del reporte indicado. Es un adjunto.',
        '5.08.04' => 'La intervención: qué reparación se ejecutará sobre el equipo mayor.',

        // ====== E.9 — PARAMETROS COMUNES DE REHABILITACION / REPOSICION =======================
        '5.09.01' => 'Antigüedad, en años, de la infraestructura o equipo mayor a rehabilitar.',
        '5.09.02' => 'Estado actual de la infraestructura o equipo mayor a rehabilitar.',
        '5.09.03' => 'Costo anual de mantenimiento de la infraestructura o equipo mayor A REHABILITAR '
            . '(en su estado actual).',
        '5.09.04' => 'Expectativa de vida útil, en años, de la infraestructura o equipo mayor YA '
            . 'REHABILITADO.',
        '5.09.05' => 'Costo anual de mantenimiento de la infraestructura o equipo mayor YA '
            . 'REHABILITADO. La comparación con el costo actual sustenta la conveniencia de rehabilitar.',

        // ============== E.10 — INVERSION MASIVA DE ACTIVOS PARA VARIAS UP =====================
        '5.10.01' => 'Sustento de la necesidad de la inversión masiva. Es el único tipo de IOARR que '
            . 'abarca VARIAS Unidades Productoras a la vez — la excepción a la regla de "una sola UP '
            . 'por registro".',
        '5.10.02' => 'Copia del informe indicado. Es un adjunto.',
        '5.10.03' => 'La intervención sobre los activos: qué se hará, de forma agregada.',
        '5.10.04' => 'Nombre genérico de la Unidad Productora (el tipo de UP que se interviene, no '
            . 'una en particular).',
        '5.10.05' => 'Ámbito geográfico que abarca la inversión masiva.',
        '5.10.06' => 'Listado de las Unidades Productoras y los activos a intervenir, una fila por UP.',

        // ====== F. COSTOS Y CRONOGRAMAS (activos con monto MAYOR a 75 UIT) ====================
        // Este bloque y el bloque G (registro simplificado) NO se llenan a la vez.
        '6.01.01' => 'Metas físicas, costo y plazo, una fila por activo. Por cada uno: tipo de IOARR, '
            . 'acción, activo, tipo de factor productivo, unidad de medida, cantidad, costo de '
            . 'inversión en soles, y las fechas de inicio y término de la ejecución física. Se '
            . 'adjunta la estructura referencial de costos.',
        '6.01.02' => 'Costo del expediente técnico o documento equivalente, en soles.',
        '6.01.03' => 'Costo de la supervisión, en soles. Va en cero si no corresponde.',
        '6.01.04' => 'Costo de la liquidación, en soles. Va en cero si no corresponde.',
        '6.01.05' => 'Costo total de inversión. Lo calcula el Excel sumando los costos por activo más '
            . 'expediente técnico, supervisión y liquidación. No se llena a mano.',
        '6.02.01' => 'Fecha prevista de inicio de ejecución (mes y año).',
        '6.02.02' => 'Tipo de período del cronograma: MES, TRIMESTRE, SEMESTRE o AÑO. Nota del '
            . 'formato: solo para el grupo funcional de Infraestructura aeroportuaria se permite el '
            . 'ingreso del período en años hasta 5 años.',
        '6.02.03' => 'Número de períodos de ejecución, coherente con el tipo de período elegido.',
        '6.02.04' => 'Cronograma de inversión por acción y activo: cuánto se invierte en cada período, '
            . 'en soles. El total por fila debe coincidir con el costo de inversión de ese activo en '
            . 'las metas físicas.',
        '6.02.05' => 'Cronograma de inversión de expediente técnico, supervisión y liquidación, '
            . 'distribuido por período.',
        '6.03.01' => 'Cronograma de las metas físicas esperadas: por cada acción y activo, la unidad '
            . 'de medida y la cantidad programada en cada período. El total por fila debe coincidir '
            . 'con la cantidad declarada en las metas físicas.',
        '6.04.01' => 'Fecha prevista de inicio de la operación y mantenimiento (mes y año). Es '
            . 'posterior al término de la ejecución.',
        '6.04.02' => 'Tipo de período del cronograma de mantenimiento (normalmente ANUAL).',
        '6.04.03' => 'Número de períodos de mantenimiento.',
        '6.04.04' => 'Costo de mantenimiento por activo y por período. Es el costo recurrente que '
            . 'asumirá la Unidad Ejecutora Presupuestal declarada en D.2.',
        '6.05.01' => 'Modalidad de ejecución prevista para la inversión.',
        '6.06.01' => 'Fuente de financiamiento de la inversión.',

        // ====== G. REGISTRO SIMPLIFICADO (activos con monto MENOR O IGUAL a 75 UIT) ===========
        // Camino alternativo al bloque F: se llena uno u otro, nunca los dos.
        '7.01.01' => 'Nombre de la inversión para el registro simplificado. Misma fórmula que el '
            . 'nombre del encabezado: acciones + activos + nombre de la UP + localización geográfica.',
        '7.02.01' => 'Registro simplificado de los activos: la versión abreviada de las metas físicas '
            . 'y costos, que aplica cuando el monto de inversión del activo es MENOR O IGUAL a 75 '
            . 'UIT. Si el monto supera las 75 UIT, este bloque no va: corresponde el registro '
            . 'completo del bloque F.',
        '7.02.02' => 'Costo del expediente técnico o documento equivalente, en soles (registro '
            . 'simplificado).',
        '7.02.03' => 'Costo de la supervisión, en soles (registro simplificado).',
        '7.02.04' => 'Costo de la liquidación, en soles (registro simplificado).',
        '7.02.05' => 'Costo total de inversión del registro simplificado. Lo calcula el Excel; no se '
            . 'llena a mano.',
        '7.03.01' => 'Tipo de período del cronograma de mantenimiento en el registro simplificado.',
        '7.03.02' => 'Número de períodos de mantenimiento en el registro simplificado.',
        '7.03.03' => 'Costo de mantenimiento por activo y por período, en el registro simplificado.',
        '7.04.01' => 'Modalidad de ejecución prevista (registro simplificado).',
        '7.05.01' => 'Fuente de financiamiento (registro simplificado).',
    ];

    public function run(): void
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null || empty($plantilla['asignado_archivo_id'])) {
            echo 'No existe ' . self::CODIGO_PLANTILLA . ' con archivo asignado — nada que preparar.' . PHP_EOL;

            return;
        }

        $archivo = $this->db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        if ($archivo === null || empty($archivo['contenido_json'])) {
            echo 'La plantilla no tiene contenido_json — nada que preparar.' . PHP_EOL;

            return;
        }

        $contenido = json_decode((string) $archivo['contenido_json'], true);
        $escritas  = 0;
        $vistas    = [];

        $contenido['secciones'] ??= [];
        foreach ($contenido['secciones'] as &$seccion) {
            $seccion['subsecciones'] ??= [];
            foreach ($seccion['subsecciones'] as &$sub) {
                $sub['campos'] ??= [];
                foreach ($sub['campos'] as &$campo) {
                    $id = (string) ($campo['identificador'] ?? '');
                    if ($id === '' || ! isset(self::DESCRIPCIONES[$id])) {
                        continue;
                    }
                    $vistas[$id] = true;
                    if (trim((string) ($campo['descripcion'] ?? '')) !== '') {
                        continue;
                    }
                    $campo['descripcion'] = self::DESCRIPCIONES[$id];
                    $escritas++;
                }
                unset($campo);
            }
            unset($sub);
        }
        unset($seccion);

        // Un identificador del arreglo que no aparezca en la estructura casi siempre es un typo al
        // transcribir del instructivo, y sin este aviso se pierde en silencio: el seeder diría
        // "listo" y ese campo se quedaría sin descripción para siempre.
        $faltantes = array_diff(array_keys(self::DESCRIPCIONES), array_keys($vistas));
        if ($faltantes !== []) {
            echo 'AVISO — estos identificadores no existen en la estructura: ' . implode(', ', $faltantes) . PHP_EOL;
        }

        if ($escritas === 0) {
            echo 'Ya estaba todo descrito — nada que escribir.' . PHP_EOL;

            return;
        }

        $this->db->table('archivos')->where('id', $archivo['id'])->update([
            'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
        ]);

        echo sprintf(
            '%s: %d descripciones escritas (de %d definidas).' . PHP_EOL,
            self::CODIGO_PLANTILLA,
            $escritas,
            count(self::DESCRIPCIONES),
        );
    }
}
