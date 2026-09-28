<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// "Descripción / ayuda" campo por campo de la FTE de los servicios de saneamiento en el ámbito
// urbano (FTE-SAN-URBANO), destilada del instructivo del MVCS (58 páginas).
//
// Esa descripción cumple dos funciones a la vez:
//  1. Va al prompt del llenado con IA — construirPromptSeccion() la manda por campo, y
//     construirPromptTabla() la manda por tabla (ver docs/llenado-automatico-ia.md §8.1).
//  2. Es lo que el cliente lee en el modal del "?" de cada tarjeta (CampoAyudaModal.vue).
//
// Alcance actual: SECCIÓN I (Aspectos Generales) y todo el MÓDULO II (Identificación), 69 campos.
// El resto se agrega sumando entradas al arreglo — el seeder no necesita otro cambio.
//
// Lo que más se equivoca en esta sección, y por eso las descripciones insisten:
//  - La cadena funcional (1.03.x) es FIJA para toda la tipología y NO se deduce del expediente.
//  - Los indicadores de brecha (1.05.1) son una lista CERRADA de cinco, con texto exacto.
//  - 1.04.2 lleva UNA FILA POR SISTEMA; antes del arreglo de filas dinámicas el modelo tendía a
//    apretar los tres sistemas en una sola celda.
//
// Idempotente: solo escribe donde la descripción está vacía; correrlo dos veces no pisa nada.
// Uso: php spark db:seed PrepararIAFTESANURBANOSeeder
class PrepararIAFTESANURBANOSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-SAN-URBANO';

    /**
     * Identificadores cuya descripción se REESCRIBE aunque ya esté llena.
     *
     * El resto del seeder es idempotente a propósito (no pisa lo que ya hay, para no borrar
     * ediciones del administrador). Pero cuando la descripción que sembramos nosotros resulta estar
     * mal, dejarla quieta perpetúa el error en el prompt del llenado y en el modal de ayuda. Esta
     * lista es la vía explícita para corregirla, con el motivo anotado.
     *
     * - 1.04.3: la versión anterior decía "si el centro poblado no tiene UBIGEO propio, se usa el
     *   del distrito" sin aclarar que la regla se aplica FILA POR FILA. En la prueba del 2026-09-28
     *   (ficha 98) el modelo la leyó como regla de tabla y le puso el UBIGEO del distrito también a
     *   la fila del CCPP, que sí tenía código propio.
     */
    private const REESCRIBIR = ['1.04.3'];

    /** identificador => descripción del campo. */
    private const DESCRIPCIONES = [
        // ------------------------------------------- 1.1 Institucionalidad — Unidad Formuladora
        '1.01.1' => 'Nivel de gobierno de la Unidad Formuladora: Nacional, Regional o Local. Sale del '
            . 'documento de designación o de la ficha institucional del expediente.',
        '1.01.2' => 'Entidad (pliego) a la que pertenece la Unidad Formuladora: la municipalidad, el '
            . 'gobierno regional o la entidad prestadora que corresponda.',
        '1.01.3' => 'Nombre del órgano que hace de Unidad Formuladora. La UF es la responsable de '
            . 'formular y evaluar el proyecto mediante la FTE, debe estar registrada en el Banco de '
            . 'Inversiones y contar con competencia legal para formular.',
        '1.01.4' => 'Nombre completo del responsable (titular) de la Unidad Formuladora.',
        '1.01.5' => 'Nombre completo de quien formula el proyecto. NO es lo mismo que el responsable '
            . 'de la UF: este firma la formulación (suele ser un consultor o un profesional del '
            . 'equipo), aquel es el titular del órgano. Si el expediente solo trae uno de los dos, '
            . 'llena el que corresponda y deja el otro vacío — no repitas el mismo nombre por '
            . 'simetría.',

        // ------------------------------- 1.1 Institucionalidad — Unidad Ejecutora de Inversiones
        '1.02.1' => 'Nivel de gobierno de la Unidad Ejecutora de Inversiones recomendada: Nacional, '
            . 'Regional o Local.',
        '1.02.2' => 'Entidad (pliego) a la que pertenece la Unidad Ejecutora de Inversiones.',
        '1.02.3' => 'Nombre del órgano o dependencia que se encargará de ejecutar el proyecto. Si hay '
            . 'más de una UEI a cargo de la ejecución, se consignan todas.',
        '1.02.4' => 'Nombre completo del responsable de la Unidad Ejecutora de Inversiones. Si hay '
            . 'más de una UEI, se consigna el responsable de cada una.',

        // ------------------------------------------------ 1.2 Responsabilidad funcional (FIJA)
        '1.03.1' => 'Función del clasificador de responsabilidad funcional. Para TODA la tipología de '
            . 'saneamiento urbano es siempre "18 SANEAMIENTO" — no se deduce de la evidencia. Sale '
            . 'del Anexo N.° 02 de la Directiva N.° 001-2019-EF/63.01.',
        '1.03.2' => 'División funcional. Para toda la tipología es siempre "040 SANEAMIENTO". No se '
            . 'deduce de la evidencia: si el expediente dice otra cosa, el expediente está mal.',
        '1.03.3' => 'Grupo funcional. Para toda la tipología es siempre "0088 SANEAMIENTO URBANO". Es '
            . 'lo que distingue el ámbito urbano del rural; recuerda que urbano es población mayor a '
            . '2000 habitantes según el PNS 2022-2026.',
        '1.03.4' => 'Sector responsable. Siempre "VIVIENDA, CONSTRUCCIÓN Y SANEAMIENTO".',

        // ----------------------------------------------------------- 1.3 Nombre del proyecto
        '1.04.1' => 'Nombre del proyecto de inversión. Se construye con una fórmula fija: NATURALEZA '
            . 'DE INTERVENCIÓN + OBJETO DE INTERVENCIÓN (los servicios) + SISTEMAS + LOCALIZACIÓN '
            . '(centro poblado urbano, distrito, provincia y región). Ejemplo del instructivo: '
            . '"MEJORAMIENTO Y AMPLIACIÓN DE LOS SERVICIOS DE AGUA POTABLE Y SANEAMIENTO DE LOS '
            . 'SISTEMAS DE AGUA POTABLE, ALCANTARILLADO Y TRATAMIENTO DE AGUAS RESIDUALES DEL CCPP '
            . 'CHINCHA ALTA, DISTRITO DE CHINCHA ALTA, PROVINCIA DE CHINCHA, REGIÓN ICA". Va en '
            . 'mayúsculas y sin abreviar los servicios. Si el expediente trae un nombre que no sigue '
            . 'la fórmula, recomponlo con la fórmula en vez de copiarlo.',
        '1.04.2' => 'Tabla de naturaleza y objeto de intervención: UNA FILA POR CADA PAR '
            . 'servicio/sistema que interviene el proyecto, repitiendo en cada fila la naturaleza que '
            . 'le corresponde. Si el proyecto toca agua potable, alcantarillado y tratamiento de '
            . 'aguas residuales, son TRES filas — nunca una sola con los tres sistemas separados por '
            . 'comas. Las naturalezas posibles son CREACIÓN (la UP no existe), AMPLIACIÓN (capacidad '
            . 'para nuevos usuarios), MEJORAMIENTO (más calidad para quienes ya tienen el servicio) y '
            . 'RECUPERACIÓN (los activos colapsaron o fueron dañados), y pueden combinarse.',
        '1.04.3' => 'Tabla de localización: una fila por centro poblado urbano (AA.HH. / '
            . 'Urbanización / Sector / Localidad), con departamento, provincia, distrito y código de '
            . 'UBIGEO. El UBIGEO se decide FILA POR FILA, no de una sola vez para toda la tabla: si '
            . 'ese centro poblado tiene código propio en el INEI, va el suyo (10 dígitos); SOLO si no '
            . 'lo tiene se usa el del distrito (6 dígitos). Es común que en una misma tabla convivan '
            . 'los dos casos — un CCPP con código propio y un AA.HH. sin él —, así que no copies el '
            . 'mismo UBIGEO a todas las filas. Los códigos de centro poblado se consultan en el INEI.',
        '1.04.4' => 'Solo se llena si el proyecto interviene en MÁS distritos o localidades de los '
            . 'que ya están en la tabla de localización. Si esa tabla ya los cubre todos, este campo '
            . 'va vacío. Recuerda que la FTE aplica a uno o más centros poblados que comparten la '
            . 'MISMA Unidad Productora por servicio: si hay UP independientes por servicio, '
            . 'corresponden proyectos separados.',

        // ------------------------------------------------- 1.4 Alineamiento a brecha prioritaria
        '1.05.1' => 'Tabla de indicadores de brecha a los que se alinea el proyecto. La lista es '
            . 'CERRADA: cinco indicadores, tres de cobertura y dos de calidad, con su texto exacto. '
            . 'Cobertura: "PORCENTAJE DE LA POBLACIÓN URBANA SIN ACCESO AL SERVICIO DE AGUA POTABLE '
            . 'MEDIANTE RED PÚBLICA O PILETA PÚBLICA" (Personas), "PORCENTAJE DE LA POBLACIÓN URBANA '
            . 'SIN ACCESO A SERVICIOS DE SANEAMIENTO MEDIANTE ALCANTARILLADO U OTRAS FORMAS DE '
            . 'DISPOSICIÓN SANITARIA DE EXCRETAS" (Personas) y "PORCENTAJE DE VOLUMEN DE AGUAS '
            . 'RESIDUALES NO TRATADAS" (M3). Calidad: "PORCENTAJE DE VIVIENDAS URBANAS CON SERVICIO '
            . 'DE AGUA CON CLORO RESIDUAL MENOR AL LÍMITE PERMISIBLE (0.5 MG/L)" (Viviendas) y '
            . '"PORCENTAJE DE POBLACIÓN URBANA QUE NO TIENE CONTINUIDAD DEL SERVICIO DE AGUA '
            . 'POTABLE" (Personas). Van SOLO los que corresponden a lo que el proyecto hace: creación '
            . 'y ampliación aportan a cobertura, mejoramiento y recuperación a calidad. El nivel de '
            . 'desagregación suele ser Distrital. Las columnas de año y valor las calcula el Excel a '
            . 'partir del numeral 2.6 — no las escribas a mano.',

        // ===================== II. IDENTIFICACIÓN — 2.1 Área de estudio e influencia ==========
        '2.01.1' => 'Tabla del ÁREA DE ESTUDIO: el espacio geográfico de referencia donde se localiza '
            . 'el problema, la Unidad Productora a intervenir (o donde podría construirse una nueva) y '
            . 'la población afectada; incluye también el área de otras UP a las que esa población '
            . 'podría acceder. Es el área MÁS GRANDE de las dos — el área de influencia va dentro. Una '
            . 'fila por centro poblado urbano, con departamento, provincia, distrito y UBIGEO. Varias '
            . 'columnas las calcula el Excel arrastrándolas del numeral 1.3: no las escribas.',
        '2.01.2' => 'Zona geográfica del área de estudio: Costa, Sierra o Selva. Se deduce de la '
            . 'altitud y la ubicación del centro poblado.',
        '2.02.1' => 'Tabla del ÁREA DE INFLUENCIA: solo el ámbito específico donde se ubica la '
            . 'población afectada. Es igual o MENOR que el área de estudio — si sale más grande, hay '
            . 'un error. Misma forma que la tabla anterior, y con las mismas columnas calculadas.',
        '2.02.2' => 'Zona geográfica del área de influencia: Costa, Sierra o Selva. Normalmente la '
            . 'misma que la del área de estudio.',
        '2.02.3' => 'Nota al pie de la tabla de área de influencia; solo se llena cuando hay algo que '
            . 'precisar. Si no corresponde, va vacío.',
        '2.03.1' => 'Tabla de características físicas, geográficas y climatológicas del área donde se '
            . 'ubica la UP: una fila por centro poblado, con tipo de suelo, altitud (m.s.n.m.), '
            . 'temperatura (°C), precipitación (mm), humedad (%) y la fuente de información. La fuente '
            . 'habitual del clima es SENAMHI; el tipo de suelo sale del estudio de suelos o la memoria '
            . 'geotécnica. Estos datos condicionan el diseño, la demanda y los costos del proyecto, '
            . 'así que si falta uno se deja vacío en vez de estimarlo.',
        '2.04.1' => 'Tabla de disponibilidad del recurso hídrico: UNA FILA POR FUENTE DE AGUA. El '
            . 'instructivo pide evaluar TODAS las fuentes factibles del área de estudio, incluidas '
            . 'las que la UP hoy NO usa, porque de ahí salen las alternativas de solución del numeral '
            . '2.5.3. Por cada fuente: nombre, fecha de aforo, caudal aforado (l/s), caudal mínimo '
            . 'estimado (l/s) — por aforo en época de estiaje o por referencia de la población —, cota '
            . 'referencial (msnm), coordenadas WGS84 (Este/Norte), uso actual, si cuenta con '
            . 'disponibilidad hídrica otorgada por la ANA/ALA, el número de esa resolución, y la '
            . 'calidad del agua señalando expresamente los parámetros que superan los Límites Máximos '
            . 'Permisibles (LMP).',
        '2.04.2' => 'Indica si del análisis de las fuentes resulta que se NECESITA TRATAMIENTO. Es '
            . 'consecuencia directa de la columna de calidad de la tabla anterior: si alguna fuente '
            . 'supera los LMP, la respuesta no puede ser que no se necesita tratamiento.',
        '2.05.1' => 'Tabla de peligros que pueden ocurrir en el área de estudio: una fila por peligro '
            . '(sismos, deslizamientos, inundaciones, huaicos, heladas...). Por cada uno se declara si '
            . 'existen antecedentes de ocurrencia (Sí/No) con su frecuencia, intensidad y grado de '
            . 'peligro, y si existe información que indique futuros cambios en sus características o '
            . 'nuevos peligros, precisando cuáles. La fuente habitual es el mapa de peligros de '
            . 'CENEPRED. Esta tabla alimenta el numeral 2.2.6 (exposición de la UP): los peligros que '
            . 'se listen acá deben ser los mismos que aparezcan allá.',

        // ================== II. IDENTIFICACIÓN — 2.2.1 y 2.2.2 UP de agua potable =============
        '3.01.1' => 'Tabla de identificación de las Unidades Productoras: UNA FILA POR UP EXISTENTE, '
            . 'es decir por sistema. Un proyecto que toca los tres sistemas lleva tres filas (agua '
            . 'potable, alcantarillado sanitario, tratamiento de aguas residuales). Por cada una: '
            . 'código de la UP solo si el Sector lo definió (si no existe se deja vacío, no se '
            . 'inventa), coordenadas WGS84 del punto referencial, nombre del centro poblado urbano '
            . 'donde está la población atendida, y tres respuestas Sí/No que condicionan permisos: si '
            . 'está en área natural protegida o zona de amortiguamiento (se consulta a SERNANP), si '
            . 'está en zona de restos arqueológicos (deriva en CIRA) y si está en zona inundable.',
        '3.02.1' => 'Tabla de fuentes hídricas de cada UP del sistema de agua potable: una fila por '
            . 'UP, con el número de fuentes, el tipo de fuente (superficial, subterránea o mixto), el '
            . 'nombre de las fuentes y el tipo de sistema de agua potable existente (por gravedad, por '
            . 'bombeo o mixto; con o sin tratamiento). Las fuentes que se nombren acá deben ser las '
            . 'mismas que se listaron en el numeral 2.1.4.',
        '3.03.1' => 'Tabla de situación actual de la UP de agua potable: UNA FILA POR ACTIVO '
            . 'ESTRATÉGICO. Los componentes típicos son captación, estación de bombeo, línea de '
            . 'impulsión, línea de conducción, planta de tratamiento de agua potable, reservorio, '
            . 'línea de aducción, redes de distribución (primarias y secundarias) y conexiones '
            . 'domiciliarias. Por cada activo: unidad física (U.M. y cantidad) y dimensión física '
            . '(U.M. y cantidad) — son dos cosas distintas, no las mezcles ni pongas metros donde va '
            . 'milímetros —, tipo de suelo, presencia de nivel freático, presencia de falla geológica, '
            . 'antigüedad en años, operatividad (SÍ/NO), estado de conservación (BUENO/REGULAR/MALO), '
            . 'lugar donde se ubica, si hay documento que acredite la disponibilidad del terreno, '
            . 'coordenadas y una breve descripción del estado actual que diga QUÉ falla y con qué '
            . 'consecuencia, no solo un adjetivo. La nomenclatura de activos la publica el MVCS.',

        // ============ II. IDENTIFICACIÓN — 2.2.3 a 2.2.5 alcantarillado, PTAR y excretas ======
        '4.01.1' => 'Tabla de características del sistema de alcantarillado sanitario: una fila por '
            . 'UP, con capacidad de diseño (l/s), volumen de producción (l/s), antigüedad, '
            . 'operatividad, estado, coordenadas UTM y una descripción del estado actual que diga de '
            . 'qué está compuesto el sistema y qué le pasa. El volumen de producción NO puede superar '
            . 'la capacidad de diseño: si la evidencia lo hace, hay un error.',
        '4.01.2' => 'Tabla de situación actual del sistema de alcantarillado, activo por activo. Los '
            . 'componentes principales son colector primario, colector secundario, estación de bombeo, '
            . 'línea de impulsión, emisor y efluente (ingreso y salida de la PTAR) y conexiones de '
            . 'alcantarillado. Por cada uno: unidad física y dimensión física (U.M. y cantidad en '
            . 'ambos casos), tipo de suelo, nivel freático, falla geológica, antigüedad, operatividad, '
            . 'estado, documento de disponibilidad de terreno, coordenadas y descripción del estado '
            . 'actual.',
        '4.02.1' => 'Tabla de características de la planta de tratamiento de aguas residuales: una '
            . 'fila por unidad de tratamiento, con el tipo de tratamiento (preliminar, primario, '
            . 'secundario, terciario), la opción tecnológica (laguna de estabilización, lodos '
            . 'activados, filtro percolador, RAFA/UASB, humedal...), centro poblado, capacidad de '
            . 'diseño (l/s), volumen tratado (l/s), tipo de suelo, presencia de nivel freático, '
            . 'presencia de falla geológica, antigüedad, operatividad, estado, coordenadas y '
            . 'descripción del estado actual.',
        '4.02.2' => 'Tabla de características del cuerpo receptor, o sea dónde va a parar el efluente: '
            . 'nombre del cuerpo receptor (río, quebrada, mar, canal, suelo), centro poblado, volumen '
            . 'vertido, volumen tratado, y el cumplimiento de LMP y de ECA. OJO, no son lo mismo y se '
            . 'confunden: el LMP (Límite Máximo Permisible) aplica a lo que SALE de la planta; el ECA '
            . '(Estándar de Calidad Ambiental) aplica al CUERPO DE AGUA que lo recibe. Se declaran por '
            . 'separado y no llevan el mismo valor.',
        '4.02.3' => 'Tabla de situación actual de la PTAR, activo por activo: unidad física y '
            . 'dimensión física (U.M. y cantidad), antigüedad, operatividad, estado, documento de '
            . 'disponibilidad de terreno, coordenadas y descripción del estado actual.',
        '4.03.1' => 'Tabla de características del área donde se ubica la UP de disposición sanitaria '
            . 'de excretas: por UP, presencia de nivel freático, si está en zona inundable, resultado '
            . 'del test de percolación y características del suelo. El test de percolación es el que '
            . 'decide si una opción técnica con infiltración es viable; si no está en la evidencia, se '
            . 'deja vacío — nunca se supone.',
        '4.03.2' => 'Tabla de situación actual del sistema de disposición sanitaria de excretas: una '
            . 'fila por tipo de solución existente (UBS con arrastre hidráulico, UBS sin arrastre '
            . 'hidráulico, UBS colectivas, letrinas u otros), con cantidad, material, antigüedad, '
            . 'estado, cuántas están operativas y un diagnóstico breve. Recuerda que alcantarillado '
            . 'sanitario y UBS son Unidades Productoras DISTINTAS: no se plantean como dos '
            . 'alternativas comparables entre sí, aunque una misma alternativa sí puede contemplar '
            . 'ambos sistemas.',

        // ============== II. IDENTIFICACIÓN — 2.2.6 y 2.2.7 exposición y vulnerabilidad ========
        '5.01.1' => 'Tabla de exposición de la UP frente a los peligros. La columna de peligro es '
            . 'CALCULADA: el Excel arrastra los peligros declarados en el numeral 2.1.5, así que no '
            . 'escribas peligros nuevos acá (si falta alguno, lo que hay que corregir es el 2.1.5). '
            . 'Por cada peligro se marca UNA SOLA de las tres columnas de grado de exposición '
            . '(bajo/medio/alto) con una X, y se indica qué UP está expuesta — puede ser el conjunto '
            . '("TODOS LOS SISTEMAS") o un sistema concreto. Exposición significa solo una cosa: si la '
            . 'UP está o estaría localizada en el área de probable impacto negativo del peligro. No es '
            . 'lo mismo que vulnerabilidad.',
        '5.02.1' => 'Tabla de vulnerabilidad por factores de fragilidad y resiliencia. La columna de '
            . 'unidad productora es CALCULADA (viene del 2.2.1): una fila por sistema. Se llenan seis '
            . 'celdas por fila con el grado de vulnerabilidad, y los valores admitidos son solo cuatro: '
            . 'Bajo, Medio, Alto o Muy Alto. FRAGILIDAD (grado de resistencia o protección frente al '
            . 'impacto de un peligro, según formas constructivas, diseño, materiales y tecnología): '
            . 'tipo de construcción y aplicación de normas de construcción. RESILIENCIA (nivel de '
            . 'asimilación y recuperación para seguir prestando el servicio en condiciones mínimas '
            . 'después del evento): capacidades de los operadores para responder ante un evento '
            . 'natural, capacidades de respuesta de la organización ante una contingencia, capacidades '
            . 'financieras de la entidad para la respuesta y existencia de recursos financieros para '
            . 'respuesta. Los dos últimos no son lo mismo: uno es la CAPACIDAD de movilizar fondos, el '
            . 'otro es si HOY existe un fondo o partida asignada.',

        // ================== II. IDENTIFICACIÓN — 2.2.8 gestión operativa del servicio =========
        '6.01.1' => 'Tabla de características de la gestión actual de los servicios: una fila por '
            . 'servicio (o por centro poblado urbano, según corresponda). Se declara el operador del '
            . 'servicio (EPS, ATM, JASS, la municipalidad u otro), si cuenta con plan operativo y con '
            . 'recursos humanos y logísticos para la O&M, el porcentaje de cobertura, el costo de '
            . 'operación y mantenimiento en SOLES POR MES (no anual — si la evidencia lo trae al año, '
            . 'divídelo entre 12 antes de escribirlo), el pago por el servicio, el subsidio de haberlo, '
            . 'el número de conexiones existentes por tipo de usuario (doméstico, comercial, estatal, '
            . 'social e industrial) y las principales restricciones o limitaciones para la O&M '
            . '(morosidad, falta de personal o equipos, ausencia de micromedición, tarifa por debajo '
            . 'del costo...). La columna de total es calculada: la suma la hace el Excel.',
        '6.02.1' => 'Tabla de continuidad y calidad del servicio de agua potable. La columna de nombre '
            . 'de la UP es calculada (viene del 2.2.1). Solo se llenan dos números, y son los que '
            . 'alimentan después los indicadores de brecha de CALIDAD del numeral 2.6.2: (1) población '
            . 'con continuidad del servicio por red pública LAS 24 HORAS Y LOS 7 DÍAS de la semana — '
            . 'es un criterio estricto: si el servicio es de 6 horas al día, la población con '
            . 'continuidad es 0, no la población atendida; y (2) número de viviendas con presencia de '
            . 'cloro residual MAYOR O IGUAL a 0.5 mg/l, que es el límite permisible. Por debajo de ese '
            . 'valor la vivienda cuenta como SIN cloro residual adecuado.',
        '6.03.1' => 'Tabla de mantenimiento del SISTEMA DE AGUA POTABLE: una fila por activo que '
            . 'efectivamente recibe mantenimiento, indicando si es preventivo o correctivo (pueden ser '
            . 'ambos), la frecuencia (mensual, trimestral, semestral, anual, eventual...), la fecha del '
            . 'último mantenimiento realizado y las acciones concretas ejecutadas ("limpieza de '
            . 'instalaciones", "limpieza de accesorios"), no genéricas. Si el sistema no existe, o '
            . 'existe pero no recibe mantenimiento, la tabla va VACÍA — eso se explica en las '
            . 'restricciones de 6.01.1, no se inventan filas con "ninguno".',
        '6.03.2' => 'Tabla de mantenimiento del SISTEMA DE ALCANTARILLADO SANITARIO. Misma forma y '
            . 'mismas reglas que la tabla de agua potable: una fila por activo que recibe '
            . 'mantenimiento, con tipo, frecuencia, fecha del último y acciones concretas. Vacía si el '
            . 'sistema no existe o no recibe mantenimiento.',
        '6.03.3' => 'Tabla de mantenimiento del SISTEMA DE TRATAMIENTO DE AGUAS RESIDUALES. Misma '
            . 'forma y mismas reglas que las anteriores. Vacía si el sistema no existe o no recibe '
            . 'mantenimiento.',
        '6.03.4' => 'Tabla de mantenimiento del SISTEMA DE DISPOSICIÓN SANITARIA DE EXCRETAS. Misma '
            . 'forma y mismas reglas. Es la que más seguido va vacía: si en la UP no hay UBS ni '
            . 'letrinas, no se rellena por simetría con las otras tres.',

        // ========== II. IDENTIFICACIÓN — 2.3 a 2.6 población, problema y alternativas =========
        '7.01.1' => 'Año base del diagnóstico: el año de referencia del que parte la proyección de la '
            . 'población.',
        '7.01.2' => 'Tabla de información general del área de influencia: una fila por dato, con '
            . 'detalle, unidad de medida, valor y FUENTE. Acá van la población afectada, la tasa de '
            . 'crecimiento intercensal (provincial, distrital o de centro poblado, estimada entre los '
            . 'últimos censos), la densidad por vivienda y similares. La fuente esperada es el Censo '
            . 'Nacional de Población y Vivienda del INEI o el padrón de usuarios: un valor sin fuente '
            . 'no sirve para sustentar la proyección.',
        '7.02.1' => 'Tabla de información del centro poblado urbano. La columna de centro poblado es '
            . 'calculada (viene del numeral 1.3). Se llenan población, viviendas, ingreso promedio y '
            . 'la fuente.',
        '7.03.1' => 'Número de cilindros, tanques u otros recipientes que el camión cisterna llena a '
            . 'la semana. Este bloque solo aplica a la población SIN conexión y sirve para estimar '
            . 'después el beneficio por ahorro de recursos. Si toda la población está conectada, va '
            . 'vacío.',
        '7.03.2' => 'Pago que se realiza por cada cilindro, tanque u otro recipiente al camión '
            . 'cisterna. Junto con la capacidad permite calcular el costo por m3 que hoy paga la '
            . 'población no conectada, que suele ser muy superior a la tarifa de red.',
        '7.03.3' => 'Capacidad del cilindro, tanque u otro recipiente que llena el camión cisterna, en '
            . 'la unidad que corresponda (normalmente litros).',
        '7.03.4' => 'Tabla de consumo por ACARREO DESDE LA FUENTE: una fila por tipo de persona que '
            . 'acarrea, con el tiempo empleado, el número de viajes, los baldes por viaje y la '
            . 'capacidad del balde. Es la otra modalidad de abastecimiento de los no conectados, '
            . 'junto al camión cisterna. Si toda la población está conectada, va vacía.',
        '7.04.1' => 'Tabla de población con y sin acceso al servicio de AGUA POTABLE. Varias columnas '
            . 'son calculadas: el Excel deriva el ámbito y el "sin acceso" (sin acceso = ámbito menos '
            . 'con acceso). Lo que se llena es la población y las viviendas CON acceso, sustentadas en '
            . 'el padrón de usuarios o documento similar donde se vea la cantidad de personas por '
            . 'vivienda.',
        '7.04.2' => 'Tabla de población con y sin acceso al servicio de SANEAMIENTO. Misma forma y '
            . 'mismas columnas calculadas que la de agua potable: solo se llena lo que tiene acceso, '
            . 'con el padrón de usuarios como respaldo.',
        '7.05.1' => 'Tabla de diagnóstico de involucrados: una fila por involucrado, con su POSICIÓN '
            . '(cooperante u oponente), su INTERÉS, su ESTRATEGIA y su COMPROMISO. Los involucrados '
            . 'típicos son la Entidad Prestadora del Servicio de Saneamiento, el Gobierno Local y la '
            . 'población beneficiaria; pueden sumarse comisiones de regantes, juntas vecinales o '
            . 'comunidades. Los compromisos de operación y mantenimiento deben estar sustentados y '
            . 'adjuntarse en anexos: no basta con declararlos acá.',
        '7.06.1' => 'Problema central, redactado como el déficit con su población, sus servicios y su '
            . 'localización. Ejemplo del instructivo: "Población con limitado y deficiente acceso a '
            . 'los servicios de agua potable y saneamiento mediante alcantarillado sanitario y '
            . 'tratamiento de aguas residuales en el CCPP Chincha Alta, distrito de Chincha Alta, '
            . 'provincia de Chincha, departamento de Ica". Tiene que estar sustentado en el '
            . 'diagnóstico: si acá dice "deficiente", el numeral 2.2 debe mostrarlo.',
        '7.06.2' => 'Tabla de CAUSAS DIRECTAS, con una columna por sistema (agua potable, '
            . 'alcantarillado, tratamiento, saneamiento básico y gestión). Las causas directas están '
            . 'predeterminadas y se relacionan con las unidades productoras. Si el proyecto no '
            . 'interviene un sistema, esa columna va vacía: no se inventa una causa para rellenar.',
        '7.06.3' => 'Tabla de CAUSAS INDIRECTAS, con la misma estructura de columnas por sistema. Son '
            . 'el detalle de las causas directas: "insuficiente capacidad de captación", "redes de '
            . 'distribución deterioradas", "insuficientes conexiones domiciliarias". De estas causas '
            . 'indirectas salen, de manera inversa, los medios fundamentales del numeral 2.5.2.',
        '7.06.4' => 'Efectos directos del problema. El instructivo los acota principalmente a dos: el '
            . 'incremento de la incidencia de enfermedades gastrointestinales y dérmicas, y el '
            . 'incremento del gasto en salud de las familias por enfermedades relacionadas al consumo '
            . 'de agua de mala calidad.',
        '7.07.1' => 'Objetivo central del proyecto: es la situación OPUESTA al problema central y debe '
            . 'estar relacionado con la naturaleza de intervención. Si el problema dice "población con '
            . 'limitado y deficiente acceso...", el objetivo dice "población con suficiente y adecuado '
            . 'acceso...", con la misma localización y los mismos servicios.',
        '7.08.1' => 'Tabla de MEDIOS DE PRIMER NIVEL, con una columna por sistema. Son la situación '
            . 'opuesta a las causas directas: "suficiente y adecuada infraestructura de agua potable", '
            . '"adecuada gestión de los servicios de saneamiento".',
        '7.08.2' => 'Tabla de MEDIOS FUNDAMENTALES, con una columna por sistema. Se relacionan de '
            . 'manera INVERSA a las causas indirectas del numeral 2.4: si una causa indirecta fue '
            . '"redes de distribución deterioradas", el medio fundamental es "adecuada infraestructura '
            . 'de redes de distribución". Revisa la correspondencia una por una — un medio sin causa '
            . 'que le corresponda, o una causa sin medio, es un error de construcción del árbol. De '
            . 'estos medios salen las acciones de las alternativas de solución.',
        '7.08.3' => 'Fin específico: el espejo de los efectos directos. Disminución de la incidencia de '
            . 'enfermedades gastrointestinales y dérmicas, y disminución del gasto en salud de las '
            . 'familias por enfermedades relacionadas al consumo de agua de mala calidad.',
        '7.09.1' => 'Tabla de planteamiento de alternativas de solución: una fila por combinación de '
            . 'alternativa + servicio + UP, con la descripción de las ACCIONES que la componen. Lo que '
            . 'distingue una alternativa de otra suele ser la fuente de agua: por ejemplo Alternativa '
            . '1 con captación subterránea (pozos, manantes, galerías filtrantes) y Alternativa 2 con '
            . 'captación superficial (río, lago, laguna); también cabe una fuente mixta. Cada '
            . 'alternativa debe dar solución INTEGRAL a la demanda de todos los servicios, no de uno '
            . 'solo. Las acciones deben corresponder a los activos estratégicos aprobados por el MVCS. '
            . 'Alcantarillado y UBS son UP distintas: no se plantean como dos alternativas comparables '
            . 'entre sí, aunque una misma alternativa puede contemplar ambos sistemas.',
        '7.09.2' => 'Nota del numeral 2.5.4. SOLO se llena si se propone una ÚNICA alternativa de '
            . 'solución: entonces hay que sustentar técnicamente por qué no es posible plantear otras. '
            . 'Si hay dos o más alternativas, va vacío.',
        '7.10.1' => 'Tabla de aporte al cierre de brecha de COBERTURA en población: los indicadores de '
            . 'agua potable y de saneamiento. La aritmética es (a) universo del ámbito de influencia, '
            . '(b) los que SÍ tienen el servicio, (c) = (a) menos (b), y (d) la contribución al cierre '
            . 'de brechas, que NUNCA puede superar (c). Las columnas (a), (c) y la de brecha son '
            . 'calculadas: lo que se llena es (b) y (d). Si la evidencia da una contribución mayor que '
            . 'el déficit, es un error del expediente — se usa el déficit.',
        '7.10.2' => 'Tabla de aporte al cierre de brecha de COBERTURA en aguas residuales, en m3/año: '
            . 'producidas en el ámbito de influencia (a), con tratamiento (b), sin tratamiento (c) = '
            . '(a) menos (b), y contribución (d) menor o igual que (c). Mismas columnas calculadas y '
            . 'mismo tope que la tabla anterior.',
        '7.11.1' => 'Tabla de aporte al cierre de brecha de CALIDAD por cloro residual, en viviendas: '
            . 'total de viviendas del ámbito de influencia (a), viviendas con cloro residual mayor o '
            . 'igual a 0.5 mg/l (b), viviendas sin esa condición (c) = (a) menos (b), y contribución '
            . '(d) menor o igual que (c). El valor de (b) tiene que ser coherente con lo declarado en '
            . 'el numeral 2.2.8 literal B (campo 6.02.1).',
        '7.11.2' => 'Tabla de aporte al cierre de brecha de CALIDAD por continuidad, en personas: '
            . 'población del ámbito de influencia (a), población con continuidad del servicio las 24 '
            . 'horas y 7 días a la semana (b), población sin continuidad (c) = (a) menos (b), y '
            . 'contribución (d) menor o igual que (c). El valor de (b) tiene que ser coherente con lo '
            . 'declarado en el campo 6.02.1: si allá la continuidad es 0, acá (b) también es 0 y el '
            . 'déficit es toda la población del ámbito.',
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
                    if (trim((string) ($campo['descripcion'] ?? '')) !== '' && ! in_array($id, self::REESCRIBIR, true)) {
                        continue;
                    }
                    // `?? null` y no acceso directo: un campo que nunca se sembró no trae la clave.
                    if (($campo['descripcion'] ?? null) === self::DESCRIPCIONES[$id]) {
                        continue; // ya está corregido — no cuenta como escritura
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
