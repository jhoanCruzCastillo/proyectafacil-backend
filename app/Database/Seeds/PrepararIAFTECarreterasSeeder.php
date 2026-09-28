<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Completa la preparación de FTE-CARRETERAS para el llenado con IA usando el mecanismo de
// "Descripción / ayuda" por campo (mismo patrón que PrepararIAFTEEBRSeeder). Cubre COMPLETA la
// sección "FICHA ESTÁNDAR" — los 24 ítems del instructivo oficial (I. ASPECTOS GENERALES, II.
// IDENTIFICACIÓN, III. FORMULACIÓN Y EVALUACIÓN), identificadores 1.01.01 a 1.24.02. Los 14
// anexos técnicos de tráfico/demanda/evaluación económica (secciones aparte de "FICHA ESTÁNDAR")
// quedan para pasadas siguientes — no se tocan acá.
//
// Fuente: "Instructivo de la Ficha Técnica Estándar para la Formulación y Evaluación de Proyectos
// de Inversión en Carreteras Interurbanas" (MTC — Oficina de Programación Multianual de
// Inversiones, julio 2023, V.2), páginas 7 a 11.
//
// También siembra `nota` en las columnas de la tabla de ubicación geográfica (1.04.01), que es la
// clave que lee construirPromptTabla() para las columnas (no `descripcion`, esa es de campo).
//
// Idempotente: solo escribe lo que falta; correrlo dos veces no duplica ni pisa texto existente.
// Uso: php spark db:seed PrepararIAFTECarreterasSeeder
class PrepararIAFTECarreterasSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CARRETERAS';

    /** identificador => descripción de campo (la que el prompt manda como ayuda). */
    private const DESCRIPCIONES = [
        // --- 1.01 Nombre del proyecto -------------------------------------------------------
        '1.01.01' => 'Seleccionar EXACTAMENTE una de: "Mejoramiento", "Recuperación" o "Ampliación" '
            . '(esta última, solo si el proyecto construye una segunda calzada o aumenta el número '
            . 'de carriles). Nunca "Creación": si la vía no existe todavía, esta ficha no aplica — '
            . 'repórtalo como observación en vez de forzar una de las 3 opciones válidas.',
        '1.01.02' => 'Se genera automáticamente combinando la naturaleza de intervención, el objeto '
            . 'de la intervención (servicio + nombre de la UP) y la localización — no lo completes '
            . 'manualmente, es un campo calculado.',
        '1.01.03' => 'Responder "Sí" o "No" según si el proyecto en formulación corresponde a un '
            . 'Programa de Inversión ya registrado.',
        '1.01.04' => 'Solo si el ítem anterior fue "Sí": nombre del Programa de Inversión al que '
            . 'pertenece el proyecto. Dejar vacío si la respuesta fue "No".',
        '1.01.05' => 'Solo si el proyecto pertenece a un Programa de Inversión: su Código Único de '
            . 'Inversión (CUI). Dejar vacío si no pertenece a ningún programa o si el programa no '
            . 'tiene código asignado todavía — nunca inventar un código.',

        // --- 1.02 Alineamiento y contribución al cierre de brechas ---------------------------
        '1.02.01' => 'Valor fijo de esta ficha: "015 Transporte". No cambiar salvo indicación '
            . 'explícita distinta en la fuente de la verdad.',
        '1.02.02' => 'Valor fijo de esta ficha: "033 Transporte Terrestre". No cambiar.',
        '1.02.03' => 'Seleccionar según la jerarquía de la vía intervenida: "064 Vías Nacionales", '
            . '"065 Vías Departamentales" o "066 Vías Vecinales" — debe coincidir con la red vial '
            . '(RVN/RVD/RVV) a la que pertenece la carretera del proyecto.',
        '1.02.04' => 'Valor fijo de esta ficha: "Transportes y Comunicaciones". No cambiar.',
        '1.02.05' => 'Valor fijo para esta ficha: "Servicio de Transitabilidad Vial Interurbana" — '
            . 'es el único servicio público asociado a esta FTE.',
        '1.02.06' => 'Indicador de brecha según la red vial de la carretera: "Porcentaje de la red '
            . 'vial [nacional/departamental/vecinal] en condiciones inadecuadas", medido en Km. '
            . 'Usa la misma red vial (RVN/RVD/RVV) elegida en el Grupo Funcional.',
        '1.02.07' => 'Se autocompleta con la longitud en kilómetros registrada en el diagnóstico de '
            . 'la Unidad Productora (sección de longitud de la vía) — no lo llenes manualmente, es '
            . 'un campo calculado.',
        '1.02.08' => 'Debe quedar relacionado con el servicio público señalado como brecha '
            . 'identificada (Servicio de Transitabilidad Vial Interurbana) — es un campo calculado '
            . '/ derivado, no lo redactes libremente.',

        // --- 1.03 Institucionalidad -----------------------------------------------------------
        '1.03.01' => 'Nombre de la Entidad a la que pertenece la OPMI (Oficina de Programación '
            . 'Multianual de Inversiones), según su inscripción en el SNPMGI.',
        '1.03.02' => 'Nombre del órgano (Unidad Orgánica) declarado como OPMI, según los datos de '
            . 'inscripción en el SNPMGI — no es necesariamente "Oficina de Programación..." si la '
            . 'entidad registró otro nombre de órgano.',
        '1.03.03' => 'Nombre completo del responsable de la OPMI.',
        '1.03.04' => 'Nombre de la Entidad a la que pertenece la Unidad Formuladora (UF), según su '
            . 'inscripción en el SNPMGI.',
        '1.03.05' => 'Nombre del órgano (Unidad Orgánica) declarado como UF, según los datos de '
            . 'inscripción en el SNPMGI.',
        '1.03.06' => 'Nombre completo del responsable de la UF.',
        '1.03.07' => 'Nombre de la Entidad a la que pertenece la Unidad Ejecutora de Inversiones '
            . '(UEI), según su inscripción en el SNPMGI.',
        '1.03.08' => 'Nombre del órgano (Unidad Orgánica) declarado como UEI, según los datos de '
            . 'inscripción en el SNPMGI.',
        '1.03.09' => 'Nombre completo del responsable de la UEI.',
        '1.03.10' => 'Nombre de la Entidad a la que pertenece la Unidad Ejecutora Presupuestal '
            . '(UEP).',
        '1.03.11' => 'Nombre del órgano designado como Unidad Ejecutora Presupuestal (UEP) — a '
            . 'diferencia de OPMI/UF/UEI, este rol no lleva responsable individual en la ficha.',

        // --- 1.04 Ubicación geográfica ---------------------------------------------------------
        '1.04.01' => 'Ubicación geográfica de los puntos de origen y destino de la vía intervenida '
            . '— una fila ya viene precargada por punto (Origen/Destino, o un tramo adicional si el '
            . 'proyecto interviene más de un tramo). Completa departamento, provincia, distrito, '
            . 'región geográfica (Costa/Sierra/Selva), coordenadas WGS84, centro poblado y Ubigeo '
            . 'según el Sistema Ubigeo del INEI — no agregues ni quites filas.',

        // --- 1.05 Diagnóstico de la Unidad Productora (UP) -------------------------------------
        '1.05.01' => 'Nomenclatura oficial de la carretera según el RENAC del SINAC (ej. '
            . '"Carretera Vecinal PA 627: Emp. PA-107 - Pampa Hermosa"). Si la vía no está inscrita '
            . 'en el RENAC, indícalo como observación — no inventes un código.',
        '1.05.02' => 'Referencia gráfica del trazo de la carretera (archivo .shp/.kml, o una '
            . 'captura de un mapa vial oficial/Google Earth) — campo de tipo imagen, no se llena '
            . 'con IA.',
        '1.05.03' => 'Extensión de la carretera en kilómetros — debe ser consistente con las '
            . 'coordenadas de origen y destino registradas en la ubicación geográfica (ítem 1.04) y '
            . 'con las progresivas de inicio/fin de tramo (1.05.04/1.05.05).',
        '1.05.04' => 'Progresiva de inicio del tramo intervenido (formato Km, ej. "Km 0+000"), '
            . 'según el estudio de la vía.',
        '1.05.05' => 'Progresiva de fin del tramo intervenido (formato Km) — debe ser consistente '
            . 'con la Longitud (Km) registrada en 1.05.03.',
        '1.05.06' => 'Código de la carretera según el RENAC (Registro Nacional de Carreteras) del '
            . 'MTC — debe ser el mismo código que la trayectoria de la vía en el RENAC. Si el '
            . 'proyecto interviene más de una vía de la misma jerarquía, detalla todos los códigos.',

        // --- 1.06 Diagnóstico de la población beneficiada y área de influencia ----------------
        '1.06.01' => 'Cantidad de personas de los centros poblados o localidades del área de '
            . 'influencia (contigua a la carretera), según datos estadísticos del INEI — cita la '
            . 'fuente si la fuente de la verdad la menciona explícitamente.',

        // --- 1.07 Diagnóstico de otros agentes involucrados ------------------------------------
        '1.07.01' => 'Una fila por cada grupo social o entidad relacionado con la ejecución, '
            . 'operación o mantenimiento del proyecto (beneficiarios, cooperantes, oponentes o '
            . 'perjudicados) — no es un catálogo fijo: agrega tantas filas como involucrados '
            . 'identifique la fuente de la verdad.',

        // --- 1.08 Problema central, causas y efectos -------------------------------------------
        '1.08.01' => 'Se genera automáticamente a partir del diagnóstico de la Unidad Productora '
            . '(ítem 1.05) — nunca lo redactes manualmente, es un campo calculado (ej. '
            . '"Inadecuadas condiciones de transitabilidad vial de la Carretera X").',
        '1.08.02' => 'Catálogo fijo de causas directas e indirectas (las columnas "Causas '
            . 'directas"/"Causas Indirectas" son calculadas, no las edites) — marca con "X" en la '
            . 'columna "Marcar" únicamente las causas indirectas que el diagnóstico de la UP '
            . 'sustente para este proyecto, nunca todas por defecto.',
        '1.08.03' => 'Catálogo fijo de efectos directos e indirectos (las columnas "Efectos '
            . 'directos"/"Efectos Indirecto" son calculadas, no las edites) — marca con "X" en la '
            . 'columna "Marcar" únicamente los efectos indirectos coherentes con las causas '
            . 'marcadas en 1.08.02.',

        // --- 1.09 Definición de los objetivos del proyecto -------------------------------------
        '1.09.01' => 'Se genera automáticamente como la situación opuesta al problema central '
            . '(ítem 1.08) — nunca lo redactes manualmente, es un campo calculado (ej. "Adecuadas '
            . 'condiciones de transitabilidad vial de la Carretera X").',
        '1.09.02' => 'Catálogo fijo de indicadores del objetivo (IMDA, Tiempo de viaje, Costos de '
            . 'Operación Vehicular), con unidad y fuente ya predefinidas — las magnitudes "sin '
            . 'Proyecto"/"con Proyecto" se calculan a partir del estudio de tráfico y demanda de '
            . 'esta misma ficha (secciones A1-A2), no las llenes manualmente aquí.',
        '1.09.04' => 'Catálogo fijo de medios fundamentales (columna "Medios fundamentales" es '
            . 'calculada, no la edites) — marca con "X" en la columna "Marcar" solo los medios '
            . 'coherentes con las causas indirectas marcadas en 1.08.02.',

        // --- 1.10 Descripción de las alternativas de solución ----------------------------------
        '1.10.02' => 'Dos alternativas precargadas ("Alternativa de Solución 1" y "2", columna '
            . 'calculada) — redacta en la columna "Descripción" las acciones concretas que '
            . 'contempla cada alternativa para lograr los medios fundamentales marcados en 1.09.04.',
        '1.10.03' => 'Solo si la fuente de la verdad identifica una única alternativa de solución '
            . 'viable: sustenta por qué no se plantearon más alternativas. Dejar vacío si sí hay '
            . 'dos alternativas.',

        // --- 1.11 Requerimientos técnicos, regulatorios y/o normativos -------------------------
        '1.11.01' => 'Catálogo fijo de documentos/estudios a gestionar en la fase de ejecución '
            . '(columna "Documentos..." es calculada, no la edites) — marca con "X" en la columna '
            . '"Marque con (x)" solo los que el proyecto realmente deba gestionar según la fuente '
            . 'de la verdad, nunca todos por defecto ni ninguno si hay evidencia de al menos uno.',
        '1.11.02' => 'Si hay un documento/estudio adicional no listado en la tabla anterior que el '
            . 'proyecto deba gestionar, indícalo aquí. Dejar vacío si no aplica.',
        '1.11.03' => 'Marcar "X" únicamente si se indicó un documento adicional en el campo '
            . '"Otros (Señale)" (1.11.02).',
        '1.11.04' => 'Cita las normas técnicas o manuales del MTC (ej. Manuales de Carreteras) que '
            . 'apliquen a los estudios técnicos requeridos por el proyecto, si la fuente de la '
            . 'verdad las menciona explícitamente.',

        // --- 1.12 Horizonte de evaluación -------------------------------------------------------
        '1.12.01' => 'Periodo total de evaluación (ejecución + funcionamiento) en años, según la '
            . 'vida útil de la solución técnica elegida — típicamente 10 años para soluciones '
            . 'básicas (afirmado/estabilizado, con reinversión en el año 5) o 20-25 años para '
            . 'pavimento asfáltico o de concreto hidráulico. Debe ser coherente con la alternativa '
            . 'técnica del ítem 14 — no inventes el valor.',

        // --- 1.13 Estudio de mercado del servicio público ---------------------------------------
        '1.13.01' => 'Cantidad de tramos homogéneos en que se dividió la vía para el estudio de '
            . 'tráfico y demanda (ver Anexo N.° 01) — debe ser consistente con los tramos usados '
            . 'en las secciones de estudio de tráfico de esta misma ficha.',
        '1.13.02' => 'Tabla enteramente calculada — se completa sola a partir del estudio de '
            . 'tráfico (conteo vehicular) proyectado durante el horizonte de evaluación. No '
            . 'propongas valores para ninguna de sus columnas.',
        '1.13.03' => 'Tabla enteramente calculada — describe la situación actual de la vía ("B. '
            . 'Situación de la UP") y su proyección, en la misma unidad de medida que la demanda '
            . '(veh./día). No propongas valores para ninguna de sus columnas.',
        '1.13.04' => 'Tabla enteramente calculada — resulta de comparar la demanda con proyecto y '
            . 'la oferta sin proyecto (13.1/13.2), ambas en la misma unidad de medida del IMDA. No '
            . 'propongas valores para ninguna de sus columnas.',

        // --- 1.14 Análisis técnico de las alternativas -------------------------------------------
        '1.14.01' => 'Describe las alternativas técnicas y su relación con las alternativas de '
            . 'solución del ítem 1.10, según los estudios de dimensionamiento (Anexo N.° 03) y la '
            . 'hoja "B. Situación de la UP".',

        // --- 1.15 Costo del proyecto -------------------------------------------------------------
        '1.15.01' => 'Tabla enteramente calculada — resume el costo de inversión por producto/'
            . 'proyecto a partir de las metas físicas y precios unitarios del presupuesto detallado '
            . '(Anexo N.° 04). No propongas valores para ninguna de sus columnas.',
        '1.15.02' => 'Se autocompleta con la región geográfica (Costa/Sierra/Selva) de la '
            . 'ubicación del proyecto — no lo llenes manualmente, es un campo calculado.',
        '1.15.03' => 'Tabla enteramente calculada — desagrega el costo de inversión por tramo y '
            . 'acción, según el presupuesto detallado (Anexo N.° 04). No propongas valores para '
            . 'ninguna de sus columnas.',
        '1.15.05' => 'Se autocompleta con la región geográfica (Costa/Sierra/Selva) de la '
            . 'ubicación del proyecto — no lo llenes manualmente, es un campo calculado.',
        '1.15.06' => 'Tabla enteramente calculada — programación por período (1 a 12, mensual/'
            . 'bimestral/trimestral) del costo de inversión por tramo y actividad; la suma total '
            . 'debe igualar el costo de inversión del ítem 15.1. No propongas valores para ninguna '
            . 'de sus columnas.',
        '1.15.07' => 'Tabla enteramente calculada — fila resumen del costo total por período de '
            . '15.2. No propongas valores para ninguna de sus columnas.',
        '1.15.08' => 'Registra, período por período (1 a 12), el porcentaje previsto de avance '
            . 'FINANCIERO — la suma de todos los períodos de una misma fila debe totalizar 100%. '
            . 'Solo llena las columnas de período (decimal); las etiquetas son calculadas.',
        '1.15.10' => 'Se autocompleta con la región geográfica (Costa/Sierra/Selva) de la '
            . 'ubicación del proyecto — no lo llenes manualmente, es un campo calculado.',
        '1.15.11' => 'Registra, período por período (1 a 12), el porcentaje previsto de avance '
            . 'FÍSICO de cada actividad — la suma de todos los períodos de una misma fila debe '
            . 'totalizar 100%. Solo llena las columnas de período (decimal); tramo/actividad/UM/'
            . 'meta son calculados.',
        '1.15.12' => 'Tabla enteramente calculada — costos de operación y mantenimiento con y sin '
            . 'proyecto (años 1 a 10), estimados para la Fase de Funcionamiento (incluye medidas de '
            . 'reducción de riesgo y mitigación ambiental). No propongas valores para ninguna de '
            . 'sus columnas.',
        '1.15.13' => 'Tabla enteramente calculada — continuación de 15.4 para los años 11 a 20. No '
            . 'propongas valores para ninguna de sus columnas.',

        // --- 1.16 Criterio de decisión de inversión ----------------------------------------------
        '1.16.01' => 'Metodología de evaluación social a aplicar, según el IMDA con proyecto en el '
            . 'año base (IMDACP0): "Costo-Eficiencia (C/E)" si el IMDACP0 es ≤ 200 veh/día, o '
            . '"Beneficio-Costo (B/C)" si es > 200 veh/día. Debe ser coherente con el estudio de '
            . 'demanda ya calculado en esta ficha (secciones A1-A2).',

        // --- 1.17 Evaluación social ---------------------------------------------------------------
        '1.17.01' => 'Catálogo fijo de indicadores según la metodología elegida en el ítem 1.16 '
            . '(Costo-Eficiencia: Ratio C/E por km; Beneficio-Costo: VAN, B/C, TIR) — completa '
            . '"Alternativa 1"/"Alternativa 2" solo con los resultados YA CALCULADOS en las hojas '
            . 'de evaluación económica de esta ficha (D1/D2 si es C/E, E1/E2 si es B/C), nunca '
            . 'inventes ni recalcules estos valores.',

        // --- 1.18 Sostenibilidad -------------------------------------------------------------------
        '1.18.01' => 'Nombre y código SIAF de la entidad pública responsable de la operación y '
            . 'mantenimiento de la vía durante la Fase de Funcionamiento, y una breve explicación '
            . 'de cómo se realizará (actividades, frecuencia del mantenimiento rutinario y '
            . 'periódico, fuente de financiamiento).',
        '1.18.03' => 'Una fila por cada documento que sustente el financiamiento de la operación y '
            . 'mantenimiento (ej. carta de compromiso del Gobernador Regional o Alcalde, convenio '
            . 'de delegación de competencias) — no es un catálogo fijo.',
        '1.18.05' => 'Catálogo fijo de peligros (Sismos, Tsunamis, Heladas, etc. — columna '
            . 'calculada) — para cada peligro que sí haya ocurrido en el área del proyecto, '
            . 'completa Ocurrencia, Nivel, Probabilidad de Ocurrencia y la medida de reducción de '
            . 'riesgo. Deja las filas sin evidencia con Ocurrencia "No" y el resto vacío.',

        // --- 1.19 / 1.20 Modalidad de ejecución y fuente de financiamiento -----------------------
        '1.19.01' => 'Catálogo fijo de 4 modalidades de ejecución (columna calculada) — marca "X" '
            . 'en la columna "Elegir Modalidad de Ejecución" en UNA sola fila, la que sustente la '
            . 'fuente de la verdad.',
        '1.20.01' => 'Catálogo fijo de 5 fuentes de financiamiento del clasificador de la Ley de '
            . 'Presupuesto (columna calculada) — marca "X" en la(s) fuente(s) reales de '
            . 'financiamiento según la fuente de la verdad; puede haber más de una.',

        // --- 1.21 Impacto ambiental -----------------------------------------------------------------
        '1.21.01' => 'Catálogo fijo de etapas del proyecto (Ejecución/Funcionamiento — columna '
            . 'calculada) — para cada etapa, describe los impactos ambientales negativos reales, '
            . 'sus medidas de prevención/control/mitigación, el medio de verificación, la '
            . 'frecuencia de ejecución y el costo estimado (S/), coherente con el Reglamento de '
            . 'Protección Ambiental del Sector Transportes.',

        // --- 1.22 Competencia --------------------------------------------------------------------
        '1.22.01' => 'Marca "X" solo si el proyecto ES competencia del nivel de Gobierno de la '
            . 'Unidad Formuladora. Nunca marques este Y el de "NO" a la vez.',
        '1.22.02' => 'Marca "X" solo si el proyecto NO es competencia del nivel de Gobierno de la '
            . 'Unidad Formuladora — en ese caso debe existir un convenio de delegación de '
            . 'competencias adjunto en anexos. Nunca marques este Y el de "SI" a la vez.',

        // --- 1.23 / 1.24 Conclusiones y resultado -------------------------------------------------
        '1.23.01' => 'Resume: el indicador de brecha al que se vincula el proyecto, su '
            . 'contribución al cierre de brechas, la alternativa seleccionada, el costo de '
            . 'inversión, el resultado de la evaluación (viable o no, con los parámetros que lo '
            . 'sustentan) y las recomendaciones para la UEI en la fase de Ejecución.',
        '1.24.01' => 'Marca "X" solo si el resultado real de la evaluación social (ítem 1.17) '
            . 'sustenta la viabilidad del proyecto (Ratio C/E dentro de la línea de corte, o '
            . 'indicadores B/C positivos según corresponda). Nunca marques este Y "NO VIABLE" a la '
            . 'vez.',
        '1.24.02' => 'Marca "X" solo si el resultado real de la evaluación social (ítem 1.17) NO '
            . 'sustenta la viabilidad del proyecto. Nunca marques este Y "VIABLE" a la vez.',
    ];

    private const NOTA_UBIGEO_INEI = 'Según el Sistema Ubigeo del Perú del INEI — no lo inventes '
        . 'ni lo aproximes si la fuente de la verdad no lo trae explícito.';

    /** `nota` por columna (lo que lee construirPromptTabla), donde el nombre visible no basta. */
    private const NOTAS_COLUMNAS = [
        '1.04.01' => [
            'Departamento' => self::NOTA_UBIGEO_INEI,
            'Provincia' => self::NOTA_UBIGEO_INEI,
            'Distrito' => self::NOTA_UBIGEO_INEI,
            'Región Geográfica (1)' => 'Una de: Costa, Sierra o Selva. No otra clasificación.',
            'Coordenadas geográficas de la intervención (2)' => 'Latitud y longitud en formato '
                . 'WGS84 del punto (origen o destino) de esa fila — no las de todo el tramo.',
            'Centro Poblado (3)' => 'Centro poblado más cercano o de referencia para el punto '
                . '(origen/destino) de esa fila.',
            'Ubigeo (4)' => 'Código Ubigeo de 6 dígitos del distrito de esa fila, según el Sistema '
                . 'Ubigeo del INEI.',
            // "Tramo" es tipo=calculado (ya viene "1 - Origen" / "2 - Destino" precargado) — no
            // lleva nota porque nunca se ofrece a la IA para llenado (ver reglas de columnas
            // calculadas en el global "Reglas de llenado automático con IA").
        ],
        '1.07.01' => [
            'Involucrado' => 'Nombre del grupo social o entidad involucrada (ej. "Asociación de '
                . 'regantes", "Municipalidad Distrital de X").',
            'Ámbito del participante' => 'Ámbito de actuación del involucrado: local, regional o '
                . 'nacional.',
            'Entidad a la que pertenece' => 'Entidad pública o privada a la que pertenece el '
                . 'involucrado, si aplica.',
            'Posición (Cooperante, Beneficiario, Oponente, Perjudicado)' => 'EXACTAMENTE una de: '
                . 'Cooperante, Beneficiario, Oponente o Perjudicado — según su relación con el '
                . 'proyecto.',
            'Intereses' => 'Interés principal del involucrado respecto al proyecto (qué espera '
                . 'obtener o evitar).',
            'Contribución' => 'Aporte o compromiso del involucrado con el proyecto '
                . '(financiamiento, terrenos, mano de obra, actas de compromiso).',
        ],
        '1.08.02' => [
            'Marcar' => 'Marca "X" únicamente en las filas de causas indirectas que la fuente de '
                . 'la verdad sustente para este proyecto — no marques todas por defecto.',
        ],
        '1.08.03' => [
            'Marcar' => 'Marca "X" únicamente en las filas de efectos indirectos coherentes con '
                . 'las causas ya marcadas en la tabla de causas (1.08.02).',
        ],
        '1.09.04' => [
            'Marcar' => 'Marca "X" solo en los medios fundamentales coherentes con las causas '
                . 'indirectas ya marcadas en 1.08.02 — no marques todos por defecto.',
        ],
        '1.10.02' => [
            'Descripción' => 'Describe las acciones concretas de esta alternativa (qué obras o '
                . 'intervenciones incluye) — no repitas el nombre genérico de la alternativa.',
        ],
        '1.11.01' => [
            'Marque con (x)' => 'Marca "X" solo en los documentos que el proyecto realmente deba '
                . 'gestionar según la fuente de la verdad — no marques todos por defecto ni dejes '
                . 'la tabla vacía si hay evidencia de al menos uno.',
        ],
        // Las columnas de período de 1.15.08/1.15.11 se llaman literalmente "1".."12" en el Excel
        // (ver `nombre` real de cada columna) — mismo criterio en las 12, solo cambia financiero
        // vs. físico (NOTA_AVANCE_FINANCIERO/NOTA_AVANCE_FISICO, definidas más abajo).
        '1.15.08' => [
            '1' => self::NOTA_AVANCE_FINANCIERO, '2' => self::NOTA_AVANCE_FINANCIERO,
            '3' => self::NOTA_AVANCE_FINANCIERO, '4' => self::NOTA_AVANCE_FINANCIERO,
            '5' => self::NOTA_AVANCE_FINANCIERO, '6' => self::NOTA_AVANCE_FINANCIERO,
            '7' => self::NOTA_AVANCE_FINANCIERO, '8' => self::NOTA_AVANCE_FINANCIERO,
            '9' => self::NOTA_AVANCE_FINANCIERO, '10' => self::NOTA_AVANCE_FINANCIERO,
            '11' => self::NOTA_AVANCE_FINANCIERO, '12' => self::NOTA_AVANCE_FINANCIERO,
        ],
        '1.15.11' => [
            '1' => self::NOTA_AVANCE_FISICO, '2' => self::NOTA_AVANCE_FISICO,
            '3' => self::NOTA_AVANCE_FISICO, '4' => self::NOTA_AVANCE_FISICO,
            '5' => self::NOTA_AVANCE_FISICO, '6' => self::NOTA_AVANCE_FISICO,
            '7' => self::NOTA_AVANCE_FISICO, '8' => self::NOTA_AVANCE_FISICO,
            '9' => self::NOTA_AVANCE_FISICO, '10' => self::NOTA_AVANCE_FISICO,
            '11' => self::NOTA_AVANCE_FISICO, '12' => self::NOTA_AVANCE_FISICO,
        ],
        '1.17.01' => [
            'Alternativa 1' => 'Valor numérico del indicador para la Alternativa 1, tal como '
                . 'resulta de la hoja de evaluación económica correspondiente (D1/D2 si es C/E, '
                . 'E1/E2 si es B/C) — no lo calcules aquí, cópialo del resultado ya obtenido en esa '
                . 'hoja.',
            'Alternativa 2' => 'Valor numérico del indicador para la Alternativa 2, tal como '
                . 'resulta de la hoja de evaluación económica correspondiente (D1/D2 si es C/E, '
                . 'E1/E2 si es B/C) — no lo calcules aquí, cópialo del resultado ya obtenido en esa '
                . 'hoja.',
        ],
        '1.18.05' => [
            'Ocurrencia' => 'Sí o No — si el peligro (fila) ha afectado alguna vez el área del '
                . 'proyecto.',
            'Nivel' => 'Solo si Ocurrencia es "Sí": EXACTAMENTE una de Alto, Medio o Bajo — '
                . 'intensidad del acontecimiento.',
            'Probabilidad de Ocurrencia' => 'Solo si Ocurrencia es "Sí": EXACTAMENTE una de Muy '
                . 'alta, Alta, Media o Baja.',
            'Medida de Reducción de Riesgo en el Contexto de Cambio Climático' => 'Acciones que '
                . 'desarrollará el proyecto para reducir y/o mitigar este riesgo. Dejar vacío si '
                . 'Ocurrencia es "No".',
        ],
        '1.19.01' => [
            'Elegir Modalidad de Ejecución (X)' => 'Marca "X" en UNA sola fila — la modalidad de '
                . 'ejecución real del proyecto según la fuente de la verdad.',
        ],
        '1.20.01' => [
            'Marcar (X)' => 'Marca "X" en la(s) fuente(s) de financiamiento reales del proyecto — '
                . 'puede marcarse más de una fila si el financiamiento es mixto.',
        ],
        '1.21.01' => [
            'IMPACTOS NEGATIVOS' => 'Impacto ambiental negativo real que generará el proyecto en '
                . 'esta etapa — no un impacto genérico de cualquier obra vial.',
            'MEDIDAS DE PREVENCIÓN, CONTROL Y/O MITIGACIÓN' => 'Medida concreta de prevención, '
                . 'control o mitigación para el impacto descrito en esta misma fila.',
            'MEDIOS DE VERIFICACIÓN DEL CUMPLIMIENTO' => 'Documento o mecanismo con el que se '
                . 'verificará el cumplimiento de la medida (ej. informe de supervisión, reporte '
                . 'ambiental).',
            'FRECUENCIA DE EJECUCIÓN' => 'Frecuencia con la que se ejecutará la medida (ej. '
                . 'mensual, por evento, una sola vez).',
            'COSTO (S/)' => 'Monto en soles (S/) del presupuesto estimado para esta medida — debe '
                . 'reflejarse también en el presupuesto general del proyecto (ítem 15).',
        ],
    ];

    /** Nota compartida por las 12 columnas de período (p1..p12) de 1.15.08/1.15.11 — mismo criterio
     * en ambas tablas (porcentaje de avance, no monto), solo cambia financiero vs. físico. */
    private const NOTA_AVANCE_FINANCIERO = 'Porcentaje (no monto) de avance FINANCIERO previsto '
        . 'para este período — la suma de todos los períodos de esta fila debe totalizar 100%.';
    private const NOTA_AVANCE_FISICO = 'Porcentaje de avance FÍSICO previsto para este período (no '
        . 'monto) — la suma de todos los períodos de esta fila debe totalizar 100%.';

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
        $c = ['desc' => 0, 'nota' => 0];

        $contenido['secciones'] ??= [];
        foreach ($contenido['secciones'] as &$seccion) {
            $seccion['subsecciones'] ??= [];
            foreach ($seccion['subsecciones'] as &$sub) {
                $sub['campos'] ??= [];
                foreach ($sub['campos'] as &$campo) {
                    $id = (string) ($campo['identificador'] ?? '');
                    if ($id === '') {
                        continue;
                    }

                    $nueva = self::DESCRIPCIONES[$id] ?? null;
                    if ($nueva !== null && trim((string) ($campo['descripcion'] ?? '')) === '') {
                        $campo['descripcion'] = $nueva;
                        $c['desc']++;
                    }

                    $notas = self::NOTAS_COLUMNAS[$id] ?? null;
                    if ($notas !== null && ! empty($campo['configTabla']['columnas'])) {
                        foreach ($campo['configTabla']['columnas'] as &$col) {
                            $nota = $notas[$col['nombre'] ?? ''] ?? null;
                            if ($nota !== null && trim((string) ($col['nota'] ?? '')) === '') {
                                $col['nota'] = $nota;
                                $c['nota']++;
                            }
                        }
                        unset($col);
                    }
                }
                unset($campo);
            }
            unset($sub);
        }
        unset($seccion);

        if (array_sum($c) === 0) {
            echo 'Ya estaba todo preparado — nada que escribir.' . PHP_EOL;

            return;
        }

        $this->db->table('archivos')->where('id', $archivo['id'])->update([
            'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
        ]);

        echo sprintf(
            'FTE-CARRETERAS preparada (ítems 1-24, sección "FICHA ESTÁNDAR" completa): %d descripciones, %d notas de columna.' . PHP_EOL,
            $c['desc'],
            $c['nota']
        );
    }
}
