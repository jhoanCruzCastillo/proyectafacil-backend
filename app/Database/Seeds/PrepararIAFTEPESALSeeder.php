<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// "Descripción / ayuda" campo por campo de la FTE de establecimientos de salud de 12 horas con rol
// Puerta de Entrada (FTE-PE-SAL), destilada del instructivo de la OPMI Salud (MINSA, 119 páginas).
//
// Esa descripción cumple dos funciones a la vez:
//  1. Va al prompt del llenado con IA — construirPromptSeccion() la manda por campo, y
//     construirPromptTabla() la manda por tabla (ver docs/llenado-automatico-ia.md §8.1).
//  2. Es lo que el cliente lee en el modal del "?" de cada tarjeta (CampoAyudaModal.vue).
//
// Por eso están escritas para una persona, no como notas telegráficas: dicen QUÉ va en el campo,
// DE DÓNDE sale la evidencia y qué error concreto evitar.
//
// Alcance: TODA la ficha — las 7 secciones, 166 campos descritos.
// el seeder está armado para que sumar entradas al arreglo sea lo único que haga falta.
//
// Nota sobre las listas cerradas de esta sección: los desplegables NO están en el JSON de la
// estructura, viven solo en la validación de datos del Excel. El llenado los resuelve en caliente
// (LlenadoIAController::completarOpcionesDesdeExcel), así que acá NO se repiten las opciones una por
// una — se explica el criterio para elegir y se deja que el prompt reciba la lista exacta del libro.
//
// Idempotente: solo escribe donde la descripción está vacía; correrlo dos veces no pisa nada.
// Uso: php spark db:seed PrepararIAFTEPESALSeeder
class PrepararIAFTEPESALSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-PE-SAL';

    /** identificador => descripción del campo. */
    private const DESCRIPCIONES = [
        // ---------------------------------------------------------------- A1. Tipo de inversión
        '1.01.01' => 'Elige, de la lista del Excel, la frase que describe el ALCANCE real de la '
            . 'intervención sobre el establecimiento de salud. Es la casilla que decide todo el '
            . 'bloque A1: de ella el Excel deriva el tipo de inversión y la acción que corresponde. '
            . 'El criterio es cuantitativo, no de impresión — las opciones se separan por umbrales '
            . 'de porcentaje de área construida (40 %) y de incremento de capacidad de atención '
            . '(20 %). La evidencia sale del informe de diagnóstico situacional del ES, de la '
            . 'evaluación de infraestructura (Sección H) o de la memoria descriptiva. Copia el texto '
            . 'EXACTO de la opción; no combines dos ni redactes una variante.',
        '1.01.02' => 'Lo calcula el Excel a partir de A1.1: indica qué tipo de inversión corresponde '
            . '(proyecto de inversión o IOARR). No se llena a mano.',
        '1.01.03' => 'Lo calcula el Excel a partir de A1.1 y A1.2: indica la acción que debe tomar la '
            . 'Unidad Formuladora. No se llena a mano.',

        // ------------------------------------------------------------------- A2. Duplicidad
        '1.02.01' => 'Elige, de la lista del Excel, la situación de esta idea de proyecto frente a '
            . 'otras inversiones ya registradas. Se sustenta con la consulta al Banco de Inversiones '
            . 'o el informe de la OPMI, no con una apreciación: hay que haber buscado inversiones con '
            . 'el mismo objetivo, los mismos componentes, el mismo factor productivo, los mismos '
            . 'beneficiarios, o un PI sin cerrar sobre el mismo establecimiento. Si la búsqueda no '
            . 'arrojó coincidencias, la opción correcta es la que declara que la inversión no se '
            . 'duplica. Copia el texto EXACTO de la opción.',
        '1.02.02' => 'Lo calcula el Excel a partir de A2.1: indica si existe o no duplicación. No se '
            . 'llena a mano.',
        '1.02.03' => 'Lo calcula el Excel a partir de A2.1 y A2.2: indica la acción que debe tomar la '
            . 'Unidad Formuladora. No se llena a mano.',

        // ------------------------------------------------------- A3. Rol de Puerta de Entrada
        '1.03.01' => 'Responder "Si" o "No" según el establecimiento sea, efectivamente, un ES de 12 '
            . 'horas con rol Puerta de Entrada. No basta con suponerlo por la categoría: se verifica '
            . 'en el visor GEO RIS del MINSA Y, además, con el documento que la DIRESA/GERESA/DIRIS '
            . 'emite para sustentar la RIS — resolución directoral si la RIS está conformada; oficio '
            . 'con el acta de talleres de estructuración si está estructurada; oficio con el acta de '
            . 'tele reunión si es simulada. Si ese documento no está en el expediente, el campo no '
            . 'puede darse por afirmativo.',

        // -------------------------------------------------- A4. Saneamiento físico legal
        '1.04.01' => 'Elige, de la lista del Excel, el documento con el que se acredita la tenencia '
            . 'legal del terreno donde se ejecutaría el proyecto. El nombre de cada opción ES el '
            . 'nombre del documento que debe existir en el expediente de saneamiento físico legal '
            . '(ver Anexo N°07 del instructivo): partida registral, cesión o afectación en uso, '
            . 'escritura pública, acuerdo de concejo con su resolución, constancia o certificado de '
            . 'posesión, acta de entrega, etc. Si el expediente no contiene ninguno de ellos, la '
            . 'respuesta correcta es la opción que declara que no existe documento que acredite la '
            . 'propiedad — no se elige el documento "más parecido". Copia el texto EXACTO.',
        '1.04.02' => 'Lo calcula el Excel a partir de A4.1: indica la situación real del predio en '
            . 'cuanto a saneamiento. No se llena a mano.',
        '1.04.03' => 'Lo calcula el Excel a partir de A4.1 y A4.2: indica la acción que debe tomar la '
            . 'Unidad Formuladora. No se llena a mano.',

        // ------------------------------------------ A5. Localización y zonificación
        '1.05.01' => 'Elige, de la lista del Excel, si el terreno cumple los criterios de '
            . 'localización. La lista distingue el caso de terreno nuevo del de terreno en uso, e '
            . 'incluye la salida "se ha previsto mitigar vulnerabilidad" para cuando no se cumplen '
            . 'todos los criterios pero el proyecto contempla medidas. Se sustenta con el informe de '
            . 'localización y/o la evaluación de riesgos del expediente. Copia el texto EXACTO.',
        '1.05.02' => 'Elige, de la lista del Excel, si la zonificación del terreno es compatible con '
            . 'actividades de salud. Se sustenta con el certificado de parámetros urbanísticos o el '
            . 'plano de zonificación municipal. Cuando el terreno no está zonificado para salud pero '
            . 'el proyecto contempla gestionar el cambio, existe la opción que prevé la propuesta de '
            . 'zonificación urbana. Copia el texto EXACTO.',

        // ------------------------------------------------------------------ A6. Veredicto
        '1.06.01' => 'Lo calcula el Excel a partir de todo el análisis previo (A1 a A5): indica si '
            . 'corresponde seguir con el proyecto de inversión, elaborar una IOARR, o si la '
            . 'intervención no corresponde. No se llena a mano. Si el resultado dice que no es '
            . 'pertinente, la Unidad Formuladora debe abstenerse de formular y de declarar viable el '
            . 'proyecto, bajo responsabilidad.',
        // ============================ SECCIÓN G — Análisis de servicios de salud (RIS) ========
        // Se llena ANTES que la Sección E: define área de estudio e influencia. Va impresa y firmada.
        '4.01.01' => 'Determina la RIS a la que pertenece el establecimiento y, dentro de ella, la '
            . 'Zona Sanitaria (que será el AREA DE ESTUDIO del proyecto) y los Sectores Sanitarios '
            . '(que serán el AREA DE INFLUENCIA). Se toma del documento que sustenta la RIS emitido '
            . 'por la DIRESA/GERESA/DIRIS y se contrasta con el visor GEO RIS del MINSA. No se '
            . 'deduce del expediente: es la autoridad sanitaria regional la que determina y valida '
            . 'esta información.',
        '4.01.02' => 'Ubicación de la Zona Sanitaria y de los Sectores Sanitarios: nombre del ES de '
            . '24 horas con rol Zona Sanitaria y su UBIGEO (define el área de estudio), y cada uno de '
            . 'los sectores sanitarios con su UBIGEO (definen el área de influencia). Primera fuente: '
            . 'GEO RIS; para mayor precisión, coordinar con la DIRESA/GERESA/DIRIS.',
        '4.01.03' => 'Identifica el o los establecimientos de salud de 12 horas con rol Puerta de '
            . 'Entrada relacionados a uno o más sectores sanitarios. El código RENIPRESS se escribe '
            . 'COMPLETO, con todos sus ceros a la izquierda. La población se consigna desagregada '
            . 'como la emite la autoridad sanitaria: por grupo etario, edades especiales, '
            . 'nacimientos, población femenina, población femenina por grupos especiales y gestantes '
            . 'esperadas. Esta población deberá ser validada por la DIRESA/GERESA/DIRIS junto con el '
            . 'PMF.',
        '4.01.04' => 'Establecimientos de salud que refieren (vinculados) al ES de 12 horas con rol '
            . 'Puerta de Entrada. Mismo criterio que el campo anterior: RENIPRESS completo con todos '
            . 'sus ceros por cada establecimiento vinculado, y población desagregada por grupo '
            . 'etario, edades especiales, nacimientos, población femenina, población femenina por '
            . 'grupos especiales y gestantes esperadas, según el reporte anual de la '
            . 'DIRESA/GERESA/DIRIS.',
        '4.01.05' => 'Población total del área de influencia del proyecto. Se obtiene de manera '
            . 'AUTOMATICA sumando lo consignado en G1.2 y G1.3 — no se escribe a mano ni se estima '
            . 'aparte. Es un insumo crítico: de acá sale la demanda proyectada de servicios del '
            . 'establecimiento.',
        '4.02.01' => 'Cómo se refieren los pacientes HOY. Por cada uno de los TRES principales '
            . 'establecimientos de referencia: nombre, categoría, distancia, tiempo, vías de acceso, '
            . 'medios de comunicación, forma de traslado del paciente y la UPSS o actividad a la que '
            . 'se hace la referencia. Sale de los registros de referencia del propio establecimiento.',
        '4.02.02' => 'Cómo será el flujo de referencias CON el proyecto. Mismos datos que la '
            . 'referencia actual (establecimiento, categoría, distancia, tiempo, vías de acceso, '
            . 'medios de comunicación y forma de traslado), pero proyectados según el flujo que '
            . 'establece la RIS: el ES de 12 horas con rol Puerta de Entrada refiere al ES de 24 '
            . 'horas con rol Zona Sanitaria.',
        '4.02.03' => 'Mapa de flujo ACTUAL de las referencias, en un diseño básico. Es una imagen: se '
            . 'adjunta el archivo, no se describe en texto.',
        '4.02.04' => 'Mapa de flujo PROYECTADO de las referencias, en un diseño básico. Es una '
            . 'imagen: se adjunta el archivo, no se describe en texto.',
        '4.02.05' => 'Principales causas por las que hoy se refieren pacientes desde este '
            . 'establecimiento. Sale de los registros de referencia y del perfil de morbilidad, no de '
            . 'una apreciación del formulador.',
        '4.02.06' => 'Cartera de servicios actual de los establecimientos del área de estudio. Se '
            . 'marca con X el servicio que cada establecimiento presta; se pueden agregar o quitar '
            . 'filas según corresponda. Debe incluir la cartera del ES de REFERENCIA (el de mayor '
            . 'capacidad resolutiva al que se refiere), no solo la del establecimiento del proyecto.',
        '4.02.07' => 'Otros servicios existentes en un radio menor o igual a 2 horas. Sirve para '
            . 'verificar si la población ya tiene acceso a esas prestaciones por otra vía antes de '
            . 'justificar nueva capacidad.',
        '4.02.08' => 'Las 10 principales causas de morbilidad del área de influencia. Salen de los '
            . 'reportes de morbilidad del establecimiento o de la DIRESA/GERESA/DIRIS (HIS, REUNIS), '
            . 'ordenadas por frecuencia.',
        '4.02.09' => 'Principales indicadores de salud del área de influencia, del perfil '
            . 'epidemiológico oficial. Acompañan a las causas de morbilidad para caracterizar la '
            . 'necesidad.',
        '4.03.01' => 'Atenciones y atendidos de los ULTIMOS 5 AÑOS, por servicio médico, del '
            . 'establecimiento o del establecimiento de referencia que sí preste ese servicio. '
            . 'IMPORTANTE: solo se llenan los servicios que están en la CARTERA PROYECTADA — si la '
            . 'cartera proyectada contempla 4 servicios, se llenan esos 4, no todos los del formato. '
            . 'Si ningún establecimiento presta el servicio, la demanda se elabora por morbilidad del '
            . 'área de influencia, debidamente sustentada. Es información primaria: de acá sale el '
            . 'cálculo de la demanda de servicios médicos.',

        // ==================== SECCIÓN H — Evaluación de la infraestructura de la UP ==========
        // La evaluación la firman como mínimo un Arquitecto y un Ingeniero Civil (con sello).
        '5.01.01' => 'Evaluación general de la infraestructura existente de la Unidad Productora. La '
            . 'elabora como mínimo un Arquitecto y un Ingeniero Civil, con firma y sello — un llenado '
            . 'sin ese respaldo no tiene sustento. Alimenta el dimensionamiento de la Sección E y '
            . 'justifica la naturaleza de intervención.',
        '5.02.01' => 'Evaluación ambiente por ambiente de la UPSS Consulta Externa, bajo dos '
            . 'criterios. CANTIDAD: número de ambientes físicos, capacidad actual en m2, capacidad '
            . 'mínima requerida en m2 SEGUN LA NTS (es un estándar normativo, no se ajusta a lo que '
            . 'hay) y brecha en m2, que es la resta y sale NEGATIVA cuando falta área. CALIDAD: '
            . 'valoración Bajo/Medio/Alto y grado de seguridad del ambiente. Un ambiente que no '
            . 'existe NO se deja en blanco: se registra con cantidad y capacidad actual en cero, y su '
            . 'brecha queda igual a toda la capacidad mínima requerida en negativo — eso es lo que '
            . 'sustenta construirlo. NA es válido para ambientes que no corresponden a la cartera de '
            . 'este establecimiento.',
        '5.02.02' => 'Evaluación de los ambientes de Anatomía Patológica / Ecografía, con el mismo '
            . 'criterio de cantidad (capacidad actual vs. capacidad mínima de la NTS, y su brecha) y '
            . 'de calidad (Bajo/Medio/Alto y grado de seguridad) que el resto de la Sección H.',
        '5.02.03' => 'Evaluación de los ambientes de Consulta Externa - Módulo II, con el mismo '
            . 'criterio de cantidad y calidad que el resto de la Sección H.',
        '5.02.04' => 'Evaluación de los ambientes de Atención de Medicamentos - Módulo IV, con el '
            . 'mismo criterio de cantidad y calidad que el resto de la Sección H.',
        '5.02.05' => 'Evaluación de los ambientes de Urgencias y Emergencias, con el mismo criterio '
            . 'de cantidad y calidad que el resto de la Sección H.',
        '5.02.06' => 'Evaluación de los ambientes de Salud Familiar y Comunitaria, con el mismo '
            . 'criterio de cantidad y calidad que el resto de la Sección H.',
        '5.02.07' => 'Evaluación de los ambientes de Patología Clínica, con el mismo criterio de '
            . 'cantidad y calidad que el resto de la Sección H.',
        '5.02.08' => 'Evaluación de los ambientes de Desinfección y Esterilización, con el mismo '
            . 'criterio de cantidad y calidad que el resto de la Sección H.',

        // ========================= SECCIÓN I — Programa Médico Funcional ======================
        // Va impresa y firmada. Sustenta el Programa Arquitectónico y, con él, el presupuesto.
        '6.01.01' => 'Programa Médico Funcional del establecimiento: el dimensionamiento funcional de '
            . 'los servicios, expresado en UPSS y actividades, a partir del estudio de oferta y '
            . 'demanda. Se desarrolla SOBRE LA CARTERA DE SERVICIOS que la DGAIN fija para los ES de '
            . '12 horas con rol Puerta de Entrada (Memorándum D000112-2025-DGAIN-MINSA), que además '
            . 'varía según el ámbito sea urbano, rural o rural disperso. El número de ambientes se '
            . 'sustenta en el GDU (Grado de Utilización del Ambiente: tiempo utilizado sobre tiempo '
            . 'disponible) — no se propone un ambiente sin ese respaldo. La demanda sale de la '
            . 'Sección G, no de una proyección aparte. El PMF se valida con la DIRESA/GERESA/DIRIS.',
        '6.01.02' => 'Unidades Prestadoras de Servicios (UPS) que acompañan a las UPSS del '
            . 'establecimiento. Se consignan con las precisiones del Anexo N.05 del instructivo y con '
            . 'el mismo criterio del PMF: solo las que correspondan a la cartera proyectada, con el '
            . 'nombre de ambiente que esa cartera les asigna.',

        // ================ SECCIÓN J — Del terreno de la alternativa seleccionada ==============
        // Se elabora sobre el Informe Técnico Legal (ITL), NTS 113-MINSA/DGIEM y R.M. 637-2024/MINSA.
        '7.01.01' => 'Terreno donde se realizará la intervención. Viene PREDETERMINADO desde la '
            . 'Sección A: acá se hereda, no se vuelve a decidir.',
        '7.01.02' => 'Selección del terreno, heredada de la Sección A. Solo cambia si el proyecto '
            . 'plantea un terreno nuevo distinto del actual.',
        '7.01.03' => 'Descripción y características del terreno o de la alternativa planteada. '
            . 'IMPORTANTE: los principales argumentos que impidan intervenir en el mismo terreno solo '
            . 'se llenan cuando se propone un TERRENO NUEVO — son la justificación de por qué no se '
            . 'puede usar el actual (área insuficiente, riesgo no mitigable, zonificación '
            . 'incompatible, imposibilidad de saneamiento, etc.), tomada del Informe Técnico Legal.',
        '7.02.01' => 'Indicar si el terreno tiene libre disponibilidad. Es un REQUISITO '
            . 'INDISPENSABLE: sin libre disponibilidad el proyecto no puede avanzar sobre ese '
            . 'terreno. Se sustenta con el Informe Técnico Legal.',
        '7.02.02' => 'Partida registral del predio. Viene predeterminada desde la Sección A (A4.1) — '
            . 'es el mismo documento de tenencia que ya se identificó allí.',
        '7.02.03' => 'Detalle de la partida registral: número, oficina registral y demás datos que '
            . 'identifican el asiento, según el Informe Técnico Legal.',
        '7.02.04' => 'Documento de arreglo institucional, cuando la tenencia no es una partida '
            . 'registral. Viene predeterminado desde la Sección A (A4.1).',
        '7.02.05' => 'Cuando la tenencia es por arreglo institucional: pasos a seguir, cronograma con '
            . 'plazos y nombre del responsable de lograr el saneamiento físico legal del predio. El '
            . 'instructivo pide los tres datos, no solo la mención de que está en trámite.',
        '7.03.01' => 'Dimensionamiento del terreno en m2. IMPORTANTE: debe ser CONCORDANTE CON EL '
            . 'LEVANTAMIENTO TOPOGRAFICO, no con el área que figura en la partida registral. Si los '
            . 'documentos discrepan, manda el levantamiento y la diferencia debe quedar señalada.',
        '7.03.02' => 'Perímetro del terreno, concordante con el estudio topográfico.',
        '7.03.03' => 'Metraje del lindero por el Norte, según el levantamiento topográfico.',
        '7.03.04' => 'Metraje del lindero por el Sur, según el levantamiento topográfico.',
        '7.03.05' => 'Metraje del lindero por el Este, según el levantamiento topográfico.',
        '7.03.06' => 'Metraje del lindero por el Oeste, según el levantamiento topográfico.',
        '7.04.01' => 'Disponibilidad del servicio de agua en el terreno. Se verifica con el '
            . 'certificado de factibilidad de la empresa prestadora. Si no existe, hay que describir '
            . 'la alternativa técnica factible en el campo correspondiente.',
        '7.04.02' => 'Disponibilidad del servicio de comunicaciones en el terreno, según certificado '
            . 'de factibilidad del operador.',
        '7.04.03' => 'Disponibilidad del servicio de energía eléctrica en el terreno, según '
            . 'certificado de factibilidad de la concesionaria.',
        '7.04.04' => 'Disponibilidad del servicio de desagüe en el terreno, según certificado de '
            . 'factibilidad de la empresa prestadora.',
        '7.04.05' => 'Cuando falta alguno de los servicios públicos esenciales, describir la '
            . 'alternativa técnica factible que garantice la prestación. No basta con declarar que el '
            . 'servicio no existe. Si otra entidad está ejecutando ese servicio, adjuntar cronograma '
            . 'y responsable; la implementación debe darse ANTES del inicio de la fase de ejecución '
            . 'del proyecto. Además, el responsable de la UF debe adjuntar en notas de formulación '
            . 'las memorias descriptivas de las soluciones planteadas.',
        '7.05.01' => 'Accesibilidad, localización y ubicación del terreno, según el Informe Técnico '
            . 'Legal y el numeral 6.1 de la NTS 113-MINSA/DGIEM.',
        '7.06.01' => 'Peligros NATURALES potenciales que afectan al terreno. Cada peligro se '
            . 'caracteriza con parámetros, no con adjetivos: intensidad, magnitud, frecuencia, '
            . 'período de recurrencia y área de impacto, entre otros — los parámetros de '
            . 'caracterización se consultan en CENEPRED (2019). Debe considerarse además la '
            . 'influencia del cambio climático sobre las características del peligro.',
        '7.06.02' => 'Peligros ANTROPICOS que afectan al terreno. Van en tabla separada de los '
            . 'naturales, no se mezclan. Considerar los procesos de uso y ocupación del territorio '
            . 'que pueden generar peligros socio-naturales.',
        '7.07.01' => 'Otras variables del terreno que el formato solicita verificar, según el Informe '
            . 'Técnico Legal.',
        '7.08.01' => 'Mapa de peligros del territorio por el o los eventos potencialmente '
            . 'predominantes. Es una imagen: se adjunta el archivo del mapa.',
        '7.08.02' => 'Estratificación del nivel de peligro. Es una imagen: se adjunta el archivo '
            . 'correspondiente, normalmente del informe de evaluación de riesgos (EVAR).',
        // ======================= SECCIÓN 2 — FTE Secciones B a F ==============================
        // --- B. Datos generales ---
        '2.01.01' => 'Nombre del proyecto de inversión. Se GENERA AUTOMATICAMENTE combinando la '
            . 'naturaleza de intervención (Mejoramiento, Ampliación, Mejoramiento y Ampliación o '
            . 'Recuperación), el código único RENIPRESS del establecimiento con todos sus dígitos y '
            . 'el nombre del centro poblado. No se redacta a mano.',
        '2.01.02' => 'Nombre del proyecto generado por el Excel a partir de la naturaleza, el '
            . 'RENIPRESS y el centro poblado. No se llena a mano.',

        // --- C. Alineamiento a una brecha prioritaria (casi todo predeterminado) ---
        '2.02.01' => 'Responsabilidad funcional del proyecto: función, división funcional, grupo '
            . 'funcional y sector responsable. Vienen PREDEFINIDOS en la FTE, en concordancia con el '
            . 'indicador de producto y el servicio público con brecha. No se modifican.',
        '2.02.02' => 'Servicio público con brecha identificada y priorizada. Está predeterminado: '
            . 'Servicio de Atención de Salud Básica. Es el único servicio para el que corresponde '
            . 'aplicar esta ficha.',
        '2.02.03' => 'Indicador de producto asociado a la brecha. Está predeterminado: porcentaje de '
            . 'establecimientos de salud del primer nivel de atención con capacidad instalada '
            . 'inadecuada, que es brecha de CALIDAD. Las cuatro naturalezas admitidas se asocian '
            . 'siempre a calidad, porque se interviene la UP existente para que cumpla la NTS.',
        '2.02.04' => 'Unidad de medida del indicador de brecha. Es Establecimiento de Salud — valor '
            . 'fijado por el sector, no se cambia.',
        '2.02.05' => 'Espacio geográfico al que corresponde el valor del indicador de brecha. Es el '
            . 'ámbito territorial de la medición; se elige de la lista del Excel.',
        '2.02.06' => 'Año base del indicador de brecha. Está predeterminado en 2025: es el año de la '
            . 'medición publicada en el diagnóstico de brechas del sector.',
        '2.02.07' => 'Valor del indicador de brecha. Está predeterminado en función de la brecha que '
            . 'maneja la OPMI a nivel distrital. No se llena a mano.',
        '2.02.08' => 'Contribución al cierre de brechas. Es 1. La razón: la unidad de medida es '
            . 'ESTABLECIMIENTO DE SALUD y el proyecto interviene un establecimiento con todos sus '
            . 'servicios y equipamiento, de forma integral — al concluir la ejecución se cierra la '
            . 'brecha de calidad de ese ES. No es la población beneficiaria ni el número de '
            . 'ambientes.',
        '2.02.09' => 'Tipología del proyecto de inversión. Es Establecimientos de salud del primer '
            . 'nivel de atención — valor fijado por el sector.',

        // --- D. Institucionalidad ---
        '2.03.01' => 'Oficina de Programación Multianual de Inversiones (OPMI): seleccionado el '
            . 'Sector, se consigna el nombre del Pliego, el de la unidad orgánica que hace las veces '
            . 'de OPMI y el nombre completo de la persona responsable. Si el proyecto lo formula una '
            . 'mancomunidad, seleccionar Mancomunidades Regionales y Municipales.',
        '2.03.02' => 'Unidad Formuladora (UF): nombre del Pliego, nombre de la UF, responsable de la '
            . 'UF y responsable de la FORMULACION, con nombres y apellidos completos. Ojo: el '
            . 'responsable de la formulación puede ser una persona distinta del responsable de la UF; '
            . 'el instructivo pide los dos.',
        '2.03.03' => 'Unidad Ejecutora de Inversiones (UEI): nombre del Pliego y de la UEI que se '
            . 'recomienda para ejecutar el proyecto, más el nombre completo de su responsable.',

        // --- E1.1.1 El territorio ---
        '2.04.01' => 'Área de estudio y área de influencia del proyecto. Vienen PREDETERMINADAS de la '
            . 'Sección G. En proyectos del primer nivel de atención ambas suelen coincidir: el área '
            . 'de estudio contiene al área de influencia, porque la población beneficiaria es la suma '
            . 'de la asignada al ES más la del ámbito de influencia. La localización y las '
            . 'coordenadas geográficas sí se llenan a mano.',
        '2.04.02' => 'Región natural donde se ubica el establecimiento: costa, sierra, selva, o Lima '
            . 'Metropolitana y Callao. Se elige de la lista del Excel. IMPORTANTE: no es un dato '
            . 'descriptivo — es una de las dos variables que alimentan el cálculo de la demanda.',
        '2.04.03' => 'Zona donde se ubica el establecimiento: urbana o rural, según las definiciones '
            . 'del numeral 3.2 del instructivo. Se elige de la lista del Excel. Igual que la Región, '
            . 'es insumo directo del cálculo de la demanda.',

        // --- E1.1.2 La población ---
        '2.05.01' => 'Población del área de influencia, incluyendo la del ES de 12 horas con rol '
            . 'Puerta de Entrada. Viene predeterminada de las tablas G1.2 y G1.3 de la Sección G. Se '
            . 'coordina y valida con la DIRESA/GERESA/DIRIS; fuentes complementarias: REUNIS y GEO '
            . 'RIS.',
        '2.05.02' => 'Población del ámbito de influencia del ES: la suma de las poblaciones de los '
            . 'establecimientos vinculados. Lo calcula el Excel desde la Sección G.',
        '2.05.03' => 'Población asignada al ES Puerta de Entrada. Lo calcula el Excel desde la '
            . 'Sección G.',
        '2.05.04' => 'Población total del ámbito de influencia del proyecto: población asignada al ES '
            . 'Puerta de Entrada MAS la suma de las poblaciones de los ES vinculados. Lo calcula el '
            . 'Excel; no se escribe a mano.',
        '2.05.05' => 'Tasa de crecimiento poblacional, en porcentaje. Este SI se digita a mano, junto '
            . 'con su fuente verificable. Puede ser distrital, provincial o regional — se sugiere la '
            . 'del INEI. Sirve para proyectar la demanda del proyecto. Si no hubiera tasa disponible, '
            . 'el instructivo permite calcularla a partir de dos poblaciones censales, respetando la '
            . 'evolución histórica aun cuando sea decreciente.',

        // --- E1.1.3 La Unidad Productora ---
        '2.06.01' => 'Datos generales de la Unidad Productora. Se consigna el código único RENIPRESS '
            . 'CON TODOS SUS DIGITOS, incluidos los ceros: con eso el Excel genera automáticamente el '
            . 'nombre del establecimiento, su categoría actual, distrito, provincia, departamento y '
            . 'coordenadas. La localidad se coloca solo de corresponder. El RENIPRESS se verifica en '
            . 'el registro de SUSALUD.',
        '2.07.01' => 'Servicios que brinda actualmente el establecimiento, su unidad de medida y su '
            . 'producción de los ULTIMOS 5 AÑOS. Es la base para evaluar la evolución del servicio y '
            . 'contrastarla con la demanda.',
        '2.08.01' => 'Diagnóstico de las UPSS por factor productivo. La FTE trae predefinidos los '
            . 'ambientes prestacionales; hay que marcar la condición de la INFRAESTRUCTURA (bueno, '
            . 'regular o malo) y describir brevemente el estado de cada ambiente, tomándolo de la '
            . 'Sección H. Para el EQUIPO el instructivo define los tres estados de forma precisa: '
            . 'BUENO es el que está en perfectas condiciones, dentro de su vida útil y sin '
            . 'mantenimiento correctivo previo; REGULAR el que opera dentro de parámetros pero ya '
            . 'superó su vida útil y tuvo mantenimiento correctivo; MALO el que opera fuera de '
            . 'parámetros o no opera y superó su vida útil, haya tenido o no mantenimiento. Fuente: '
            . 'inventario de equipos y los Formatos N.01 y N.02 de la Directiva 004-2013-DGIEM/MINSA. '
            . 'Los RECURSOS HUMANOS se analizan contra la cartera aprobada y el número de ambientes '
            . 'según la R.M. 176-2014/MINSA.',
        '2.09.01' => 'Factor productivo que se está optimizando en la situación sin proyecto. Es el '
            . 'factor limitante identificado en el diagnóstico: el activo de menor capacidad, el que '
            . 'define el techo de producción de la UP.',
        '2.09.02' => 'Determinación de la oferta sin proyecto. IMPORTANTE: la oferta NO es la suma de '
            . 'las capacidades, es el MINIMO de los tres factores — mín f(OH, OI, OE): recursos '
            . 'humanos, infraestructura y equipamiento. Dos casos que el instructivo ejemplifica: si '
            . 'la limitante es un equipo deteriorado, las atenciones realizadas con ese equipo '
            . 'malogrado se computan como CERO (mala calidad del servicio); si la limitante es la '
            . 'infraestructura y hay informe de inhabitabilidad de Defensa Civil o de un ingeniero '
            . 'estructuralista que concluya demolición, la oferta sin proyecto puede ser CERO.',

        // --- E1.2 Problema y causas (predeterminadas) ---
        '2.10.01' => 'Problema central. La FTE propone una plantilla que puede ajustarse, pero el '
            . 'problema debe identificarse SIEMPRE desde el lado de la demanda (necesidad '
            . 'insatisfecha) determinada en el diagnóstico. La plantilla es: "Población del área de '
            . 'influencia del [establecimiento] accede a inadecuados y limitados servicios de salud '
            . 'del primer nivel de atención en salud".',
        '2.10.02' => 'Causa directa 1, predeterminada por la FTE: inadecuada e insuficiente capacidad '
            . 'operativa y funcional en los servicios de salud.',
        '2.10.03' => 'Causa directa 2, predeterminada por la FTE: existencia de barreras '
            . 'socioeconómicas y culturales que limitan el acceso a los servicios de salud.',
        '2.10.04' => 'Causa indirecta 1.1, predeterminada: limitada e inadecuada infraestructura '
            . 'física para la prestación de servicios de salud.',
        '2.10.05' => 'Causa indirecta 1.2, predeterminada: equipamiento inadecuado e insuficiente '
            . 'para brindar los servicios de salud.',
        '2.10.06' => 'Causa indirecta 1.3, predeterminada: inadecuado mantenimiento preventivo y '
            . 'correctivo de infraestructura y equipamiento.',
        '2.10.07' => 'Causa indirecta 2.1, predeterminada: inadecuada y limitada promoción de la '
            . 'cartera de servicios a la comunidad. NOTA del instructivo: entre las causas figura '
            . 'también el limitado recurso humano, pero el proyecto NO interviene en ese factor '
            . 'porque corresponde a gasto corriente y a un tema de ordenamiento de la oferta.',

        // --- E1.3 Planteamiento ---
        '2.11.01' => 'Objetivo central del proyecto: la situación deseada tras la intervención. Es el '
            . 'espejo del problema central — "Población del área de influencia del [establecimiento] '
            . 'accede a ADECUADOS servicios de salud del primer nivel de atención en salud".',
        '2.11.02' => 'Indicadores del objetivo central. Se elige el que mejor se relacione con el '
            . 'objetivo según la realidad del ES; el instructivo sugiere POBLACION ATENDIDA, cuya '
            . 'unidad de medida es Atenciones (la unidad se genera al elegir el indicador). Hay que '
            . 'consignar la LINEA BASE, el VALOR AL FINAL DEL PI y la FUENTE DE VERIFICACION '
            . '(publicaciones mensuales o anuales, boletines, informes de gestión, ASIS local o '
            . 'regional).',
        '2.12.01' => 'Definición de las acciones del proyecto y su clasificación. Los medios '
            . 'fundamentales vienen predeterminados; el formulador determina las acciones que '
            . 'corresponden a cada uno.',
        '2.13.01' => 'Descripción de la Alternativa 1 de solución, planteada a partir del objetivo '
            . 'central y los medios fundamentales identificados.',
        '2.13.02' => 'Descripción de la Alternativa 2 de solución. Su desarrollo completo (desde '
            . 'localización hasta mitigación de riesgos) va en la hoja Alt 2 de la ficha.',

        // --- E2 Formulación ---
        '2.14.01' => 'Fases del proyecto y horizonte de evaluación. La ficha está diseñada para 13 '
            . 'AÑOS EN TOTAL: la Unidad Formuladora reparte entre fase de ejecución y fase de '
            . 'funcionamiento. Combinaciones que ejemplifica el instructivo: 3 + 10, 2 + 11 o 1 + 12 '
            . 'años. El reparto elegido gobierna después el cronograma y los flujos de costos.',
        '2.15.01' => 'Demanda de los servicios de salud. Está PREDETERMINADA a partir de la Sección G '
            . '(población del ES y de los ES vinculados, referencias actual y proyectada, cartera de '
            . 'servicios y atenciones de los últimos 5 años). No se calcula aparte.',
        '2.16.01' => 'Balance de oferta y demanda: la brecha que resulta de cruzar la demanda '
            . 'proyectada con la oferta optimizada determinada en E1.1.3.4. Es información '
            . 'preestablecida a partir de esos dos insumos.',

        // --- E2.4 Análisis técnico ---
        '2.17.01' => 'Programa Médico Funcional del proyecto (tamaño). El número de ambientes del '
            . 'Anexo N.03, propuesto por la OPMI y la DGAIN del MINSA, es REFERENCIAL: puede '
            . 'modificarse, pero solo con sustento técnico de la Autoridad Sanitaria Regional y '
            . 'atendiendo a cuatro criterios — i) grado de utilización, ii) perfil epidemiológico de '
            . 'la zona, iii) densidad poblacional, iv) accesibilidad, distancia y tiempo. Todo '
            . 'incremento debe quedar sustentado en el documento de aprobación de la '
            . 'DIRESA/GERESA/DIRIS. La cartera de referencia es la de la R.M. 222-2024/MINSA.',
        '2.17.02' => 'Terreno de la alternativa: viene de la Sección J. Acá se hereda, no se vuelve a '
            . 'decidir.',
        '2.17.03' => 'Descripción y características del terreno o de la alternativa planteada, según '
            . 'el Informe Técnico Legal y lo consignado en la Sección J.',
        '2.17.04' => 'Sistema constructivo del proyecto. La elección cambia cómo se costea: el '
            . 'CONVENCIONAL se estima con la R.D. 041-2013-DGIEM y los Cuadros de Valores Unitarios '
            . 'Oficiales de Edificaciones vigentes para costa, sierra o selva; el NO CONVENCIONAL '
            . 'requiere aprobación de SENCICO y sus costos se sustentan con cotizaciones.',
        '2.17.05' => 'Descripción de la tecnología y del sistema constructivo propuesto, con el nivel '
            . 'de detalle de ingeniería conceptual: suficiente para aproximar la magnitud de la '
            . 'inversión, los costos y los beneficios.',
        '2.17.06' => 'Impacto ambiental del proyecto: impactos identificados y medidas previstas.',
        '2.17.07' => 'Gestión integral del riesgo: peligros que afectan al proyecto y medidas de '
            . 'reducción previstas, concordantes con la Sección J.',
        '2.17.08' => 'Metas físicas del proyecto por componente, con su unidad de medida y cantidad.',
        '2.17.09' => 'Estimación de los costos de inversión a precios de mercado. Se ingresan '
            . 'MANUALMENTE: infraestructura, equipamiento, mobiliario y demás componentes, junto con '
            . 'las fechas de inicio y término del expediente técnico y de la ejecución física. El '
            . 'equipamiento se estima con los costos referenciales publicados por el sector o con '
            . 'cotizaciones de equipos similares. Las obras exteriores (cerco perimétrico, control de '
            . 'ingreso, veredas, acometidas de servicios) se calculan con el cuadro de valores '
            . 'unitarios de obras complementarias publicado en el diario oficial.',
        '2.17.10' => 'Cronograma de ejecución: fecha de inicio del proyecto con día, mes y año; tipo '
            . 'de periodo (SEMESTRAL o TRIMESTRAL, se elige uno) y número de periodos, coherente con '
            . 'el tipo elegido y con el plazo de ejecución del horizonte.',
        '2.17.11' => 'Cronograma de ejecución FISICA: por cada ítem, la unidad física o dimensión '
            . '(unidad de medida y cantidad) y su programación en el tiempo.',
        '2.17.12' => 'Cronograma de ejecución FINANCIERA: la inversión a costo de mercado prevista en '
            . 'cada periodo, en soles, de acuerdo con el plazo de ejecución del horizonte.',
        '2.17.13' => 'Costos de inversión durante la fase de FUNCIONAMIENTO: reposición de '
            . 'equipamiento según su vida útil promedio (R.M. 533-2016-MINSA). El instructivo fija la '
            . 'pauta: 20 % en el año 5 y 30 % en el año 9 de la operación y mantenimiento. Se llena '
            . 'manualmente según los años de ejecución.',
        '2.17.14' => 'Costos de OPERACION sin proyecto: lo que hoy gasta el establecimiento en '
            . 'remuneraciones, servicios, insumos y otros, a precios de mercado (los precios sociales '
            . 'aparecen predeterminados). Fuente: el propio establecimiento — planillas de '
            . 'remuneración, reportes de gastos, inventario, facturas, recibos y rendiciones de '
            . 'cuentas.',
        '2.17.15' => 'Costos de MANTENIMIENTO sin proyecto, a precios de mercado, con la misma fuente '
            . 'documental del establecimiento.',
        '2.17.16' => 'Costos de OPERACION con proyecto. Para los recursos humanos se consigna por '
            . 'GRUPO OCUPACIONAL (médico cirujano, obstetra, enfermero, nutricionista, personal '
            . 'administrativo, etc.): cantidad, condición laboral, remuneración anual, aguinaldo o '
            . 'gratificación anual y costo total anual. La brecha de recursos humanos se estima según '
            . 'la R.M. 176-2014/MINSA.',
        '2.17.17' => 'Costos de MANTENIMIENTO con proyecto, coherentes con la infraestructura y el '
            . 'equipamiento que resultan del proyecto.',
        '2.17.18' => 'Costos incrementales: la diferencia entre los costos con proyecto y sin '
            . 'proyecto. Son los que alimentan la evaluación social y el índice de cobertura.',
        '2.17.19' => 'Costos totales a precio social, resultado de aplicar los factores de corrección '
            . 'a los costos a precios de mercado.',

        // --- E3 Evaluación social ---
        '2.18.01' => 'Unidad Ejecutora Presupuestal responsable de la operación y mantenimiento del '
            . 'proyecto. En algunas regiones son las Redes de Salud; la Autoridad Sanitaria '
            . '(DIRESA/GERESA/DIRIS) es quien debe asumir los costos de O&M del ES Puerta de Entrada.',
        '2.18.02' => 'Documento de compromiso de operación y mantenimiento, que incluye la '
            . 'disponibilidad de recursos humanos para operar el establecimiento. IMPORTANTE: debe '
            . 'estar suscrito por la AUTORIDAD SANITARIA correspondiente (DIRESA/GERESA/DIRIS), no '
            . 'por la Unidad Formuladora. Es uno de los documentos obligatorios en el Banco de '
            . 'Inversiones antes de la viabilidad.',
        '2.18.03' => 'Índice de cobertura de los costos incrementales de operación y mantenimiento. '
            . 'Se calcula en hoja Excel complementaria: ingresos incrementales a precios de mercado '
            . '(número de atenciones proyectadas por el tarifario del ES y del SIS) DIVIDIDO entre '
            . 'los costos incrementales de O&M a precios de mercado, y el resultado multiplicado por '
            . '100.',
        '2.18.04' => 'Resultado de la sostenibilidad financiera, derivado del índice de cobertura. Lo '
            . 'calcula el Excel.',
        '2.18.05' => 'Mitigación de riesgos: identificar el riesgo (operacional, asociado al cambio '
            . 'climático, de mercado, financiero) y su impacto, y señalar las acciones que el '
            . 'proyecto considera para mitigar el riesgo de desastres. Los costos de inversión '
            . 'asociados a medidas de reducción de riesgo NO se desagregan, salvo que sean obras '
            . 'complementarias del proyecto como diques o muros de contención.',
        '2.18.06' => 'Criterios de decisión de inversión. Los indicadores de evaluación social se '
            . 'calculan AUTOMATICAMENTE: lo único que se digita a mano es el número de atenciones, '
            . 'coherente con el horizonte definido en E2.1.',
        '2.18.07' => 'Modalidad de ejecución de la alternativa seleccionada. Se selecciona para cada '
            . 'uno de los componentes del proyecto.',
        '2.18.08' => 'Fuente de financiamiento que se utilizará en la fase de ejecución. Se '
            . 'selecciona de la lista desplegable del Excel.',

        // --- F. Conclusiones ---
        '2.19.01' => 'Marcar con X si el proyecto de inversión resulta VIABLE. Es excluyente con el '
            . 'campo NO VIABLE: se marca uno solo de los dos.',
        '2.19.02' => 'Marcar con X si el proyecto de inversión resulta NO VIABLE. Es excluyente con '
            . 'el campo VIABLE.',
        '2.19.03' => 'Principales argumentos que sustentan el resultado de la formulación y '
            . 'evaluación. Describir las conclusiones y recomendaciones referidas al cierre de '
            . 'brechas, la accesibilidad a servicios públicos, los niveles de intervención que aborda '
            . 'el proyecto, la población beneficiaria en el horizonte y la sostenibilidad. Además, '
            . 'recomendar las acciones de la fase de ejecución que aseguren la consistencia con la '
            . 'concepción técnica y el dimensionamiento aprobados — en particular el saneamiento '
            . 'físico legal si estuviera pendiente, y los compromisos de terceros como obras de '
            . 'mitigación.',
        '2.19.04' => 'Documentos que DEBEN estar registrados en el Banco de Inversiones ANTES de '
            . 'declarar la viabilidad. Sin ellos no puede otorgarse, bajo responsabilidad de la '
            . 'Unidad Formuladora: Formato 07-A impreso desde el aplicativo; Ficha Técnica Estándar '
            . 'firmada; resumen ejecutivo; Resolución Directoral de RIS conformada o estructurada, o '
            . 'documento de RIS simulada con el acta de tele reunión; oficio e informe de aprobación '
            . 'del PMF por la DIRESA/GERESA/DIRIS conteniendo el PMF y el análisis de red (Secciones '
            . 'I y G); y el documento de compromiso de O&M firmado por la Autoridad Sanitaria.',
        '2.19.05' => 'Fecha de culminación de la formulación del proyecto de inversión.',
        // ===================== SECCIÓN 3 — Hoja "Alt 2" (Alternativa 2 de solución) ==========
        // El instructivo lo dice explícito: la Alternativa 1 se llena en la hoja "FTE Sección B-F"
        // y la Alternativa 2 en la hoja "Alt 2", ambas DESDE "b. Localización" HASTA "E3.3
        // Mitigación de riesgo". Por eso el criterio de llenado de cada campo es el mismo que su
        // equivalente de la Alternativa 1 (2.17.x / 2.18.x); lo que cambia es la solución técnica
        // que se está costeando. Las dos alternativas deben ser COMPARABLES: mismo horizonte, misma
        // demanda y mismo PMF aprobado — lo que varía es cómo se resuelve, no el problema.
        '3.01.01' => 'Terreno de la Alternativa 2. Resume los criterios establecidos en la Sección J '
            . 'de la ficha. Si esta alternativa se ejecuta en un terreno distinto al de la '
            . 'Alternativa 1, es acá donde esa diferencia debe quedar explícita.',
        '3.01.02' => 'Detalle de la localización de la Alternativa 2, según la Sección J.',
        '3.01.03' => 'Descripción y características del terreno o de la alternativa planteada para la '
            . 'Alternativa 2. Si comparte terreno con la Alternativa 1, se indica; si no, hay que '
            . 'justificar la diferencia con el Informe Técnico Legal correspondiente.',
        '3.01.04' => 'Sistema constructivo de la Alternativa 2. Igual que en la Alternativa 1, la '
            . 'elección cambia cómo se costea: el CONVENCIONAL se estima con la R.D. '
            . '041-2013-DGIEM y los Cuadros de Valores Unitarios Oficiales de Edificaciones vigentes '
            . 'para costa, sierra o selva; el NO CONVENCIONAL requiere aprobación de SENCICO y sus '
            . 'costos se sustentan con cotizaciones. Es habitual que la diferencia entre las dos '
            . 'alternativas sea justamente el sistema constructivo.',
        '3.01.05' => 'Selección del sistema constructivo de la Alternativa 2, de la lista del Excel.',
        '3.01.06' => 'Descripción de la tecnología y del sistema constructivo de la Alternativa 2, '
            . 'con nivel de ingeniería conceptual: suficiente para aproximar magnitud de inversión, '
            . 'costos y beneficios.',
        '3.01.07' => 'Impacto ambiental de la Alternativa 2: impactos identificados y medidas '
            . 'previstas, con el mismo criterio que la Alternativa 1.',
        '3.01.08' => 'Gestión integral del riesgo de la Alternativa 2: peligros que la afectan y '
            . 'medidas de reducción previstas, concordantes con la Sección J.',
        '3.01.09' => 'Metas físicas de la Alternativa 2 por componente, con unidad de medida y '
            . 'cantidad. Deben responder al MISMO PMF aprobado que la Alternativa 1 — lo que cambia '
            . 'es cómo se ejecuta, no cuánto servicio se entrega.',
        '3.01.10' => 'Estimación de los costos de inversión de la Alternativa 2 a precios de mercado. '
            . 'Se ingresan manualmente: infraestructura, equipamiento, mobiliario y demás '
            . 'componentes. El equipamiento se estima con los costos referenciales del sector o con '
            . 'cotizaciones; las obras exteriores, con el cuadro de valores unitarios de obras '
            . 'complementarias publicado en el diario oficial. Es el número que se compara con el de '
            . 'la Alternativa 1 para sustentar la selección.',
        '3.01.11' => 'Fecha prevista de inicio de ejecución de la Alternativa 2 (día, mes y año).',
        '3.01.12' => 'Tipo de periodo del cronograma de la Alternativa 2: SEMESTRAL o TRIMESTRAL. Se '
            . 'elige uno solo, y el número de periodos debe ser coherente con esa elección.',
        '3.01.13' => 'Número de periodos de ejecución de la Alternativa 2, coherente con el tipo de '
            . 'periodo elegido y con el plazo de ejecución del horizonte (13 años en total entre '
            . 'ejecución y funcionamiento).',
        '3.01.14' => 'Cronograma de ejecución FISICA de la Alternativa 2: por cada ítem, la unidad '
            . 'física o dimensión (unidad de medida y cantidad) y su programación en el tiempo.',
        '3.01.15' => 'Cronograma de ejecución FINANCIERA de la Alternativa 2: la inversión a costo de '
            . 'mercado prevista en cada periodo, en soles, según el plazo de ejecución.',
        '3.01.16' => 'Costos de inversión de la Alternativa 2 durante la fase de FUNCIONAMIENTO: '
            . 'reposición de equipamiento según su vida útil promedio (R.M. 533-2016-MINSA). Misma '
            . 'pauta que la Alternativa 1: 20 % en el año 5 y 30 % en el año 9 de la operación y '
            . 'mantenimiento.',
        '3.01.17' => 'Costos de OPERACION sin proyecto. IMPORTANTE: la situación sin proyecto es la '
            . 'MISMA para las dos alternativas — describe el establecimiento tal como está hoy, no '
            . 'depende de qué alternativa se elija. Debe coincidir con lo consignado en la '
            . 'Alternativa 1. Fuente: planillas, reportes de gastos, inventario, facturas y '
            . 'rendiciones del propio establecimiento.',
        '3.01.18' => 'Costos de MANTENIMIENTO sin proyecto, a precios de mercado. Igual que el campo '
            . 'anterior: la situación sin proyecto es única y debe coincidir con la de la '
            . 'Alternativa 1.',
        '3.01.19' => 'Costos de OPERACION con proyecto para la Alternativa 2. Los recursos humanos se '
            . 'consignan por GRUPO OCUPACIONAL (médico cirujano, obstetra, enfermero, nutricionista, '
            . 'personal administrativo, etc.) con cantidad, condición laboral, remuneración anual, '
            . 'aguinaldo y costo total anual. La brecha de recursos humanos se estima según la R.M. '
            . '176-2014/MINSA.',
        '3.01.20' => 'Costos de MANTENIMIENTO con proyecto para la Alternativa 2, coherentes con la '
            . 'infraestructura y el equipamiento que resultan de ESTA alternativa — que normalmente '
            . 'difieren de los de la Alternativa 1.',
        '3.01.21' => 'Costos incrementales de la Alternativa 2: la diferencia entre los costos con '
            . 'proyecto de esta alternativa y los costos sin proyecto. Alimentan su evaluación social '
            . 'y su índice de cobertura.',
        '3.01.22' => 'Costos totales a precio social de la Alternativa 2, resultado de aplicar los '
            . 'factores de corrección a los costos a precios de mercado.',
        '3.02.01' => 'Unidad Ejecutora Presupuestal responsable de la operación y mantenimiento en la '
            . 'Alternativa 2. Normalmente es la misma que en la Alternativa 1: la Autoridad Sanitaria '
            . '(DIRESA/GERESA/DIRIS) es quien debe asumir los costos de O&M del ES Puerta de Entrada.',
        '3.02.02' => 'Documento de compromiso de operación y mantenimiento de la Alternativa 2, que '
            . 'incluye la disponibilidad de recursos humanos. Debe estar suscrito por la AUTORIDAD '
            . 'SANITARIA (DIRESA/GERESA/DIRIS), no por la Unidad Formuladora.',
        '3.02.03' => 'Índice de cobertura de los costos incrementales de O&M de la Alternativa 2. Se '
            . 'calcula en hoja Excel complementaria: ingresos incrementales a precios de mercado '
            . '(atenciones proyectadas por el tarifario del ES y del SIS) divididos entre los costos '
            . 'incrementales de O&M a precios de mercado, por 100.',
        '3.02.04' => 'Resultado de la sostenibilidad financiera de la Alternativa 2, derivado de su '
            . 'índice de cobertura. Lo calcula el Excel.',
        '3.02.05' => 'Mitigación de riesgos de la Alternativa 2: riesgo identificado (operacional, '
            . 'asociado al cambio climático, de mercado, financiero), su impacto y las medidas '
            . 'previstas. Los costos de reducción de riesgo NO se desagregan, salvo que sean obras '
            . 'complementarias como diques o muros de contención.',
        '3.02.06' => 'Criterios de decisión de inversión de la Alternativa 2. Los indicadores de '
            . 'evaluación social se calculan automáticamente; lo único que se digita es el número de '
            . 'atenciones, coherente con el horizonte. La comparación de estos indicadores contra los '
            . 'de la Alternativa 1 es lo que sustenta cuál se selecciona.',
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
