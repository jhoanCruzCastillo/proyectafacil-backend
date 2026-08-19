# Guía de llenado campo por campo — FTE Servicio de Cuidado Diurno

Este contexto describe, campo por campo (por su `identificador` único), cómo debe llenarse cada campo de la Ficha Técnica Estándar del Servicio de Cuidado Diurno (CIAI). Los ejemplos marcados como "Ejemplo real" provienen del ejemplo de referencia ya cargado para esta ficha ("4_ejemplo_anexo") — úsalos como guía de formato y estilo, no los copies literalmente salvo que el contexto del usuario coincida.

Reglas generales (ya cubiertas por el contexto global "Reglas de llenado automático con IA", reforzadas aquí para esta ficha):
- Los campos de tipo **tabla** se llenan con un flujo aparte, una tabla a la vez (botón "Llenar con IA" del propio campo, no este listado por sección) — se listan aquí solo para que sepas qué contienen si el usuario pregunta.
- Los campos **calculado** nunca se llenan — el Excel los calcula solo.
- Los campos **imagen** y **mapa_coordenadas** no se llenan con texto.

## Sección 1: SECCIÓN N°01: DATOS GENERALES DEL PROYECTO

### 1.01 Institucionalidad

- **1.01.01** — Nivel de gobierno: elegir EXACTAMENTE una de estas opciones: Gobierno Nacional | Gobierno Regional | Gobierno Local. Ejemplo real: "Gobierno Nacional".
- **1.01.02** — Entidad: Ejemplo real: "Programa Nacional Cuna Más".
- **1.01.03** — Nombre de la UF: Ejemplo real: "Unidad Formuladora Programa Nacional Cuna Más".
- **1.01.04** — Responsable de la UF: Ejemplo real: "Juan Perez".
- **1.01.05** — Nivel de gobierno: elegir EXACTAMENTE una de estas opciones: Gobierno Nacional | Gobierno Regional | Gobierno Local. Ejemplo real: "Gobierno Nacional".
- **1.01.06** — Entidad: Ejemplo real: "Programa Nacional Cuna Más".
- **1.01.07** — Nombre de la UEI: Ejemplo real: "UEI Cuna Más".
- **1.01.08** — Responsable de la UEI: Ejemplo real: "Carlos Perez".

### 1.02 Responsabilidad funcional y tipología del proyecto de inversión

- **1.02.01** — Función: Ejemplo real: "23 PROTECCIÓN SOCIAL".
- **1.02.02** — División funcional: Ejemplo real: "051 ASISTENCIA SOCIAL".
- **1.02.03** — Grupo funcional: Ejemplo real: "0115 PROTECCIÓN DE POBLACIONES EN RIESGO".
- **1.02.04** — Sector responsable: Ejemplo real: "DESARROLLO E INCLUSION SOCIAL".
- **1.02.05** — Tipología de proyecto: Ejemplo real: "CENTRO INFANTIL DE ATENCIÓN INTEGRAL".

### 1.03 Nombre del proyecto de inversión

- **1.03.01** — Naturaleza de intervención: elegir EXACTAMENTE una de estas opciones: Mejoramiento | Ampliación | Mejoramiento y ampliación | Recuperación. Ejemplo real: "Mejoramiento".
- **1.03.02** — Objeto de intervención: **NO LLENAR** — lo calcula el Excel automáticamente (aunque el campo está marcado como editable, en la práctica esta celda del Excel es una fórmula).
- **1.03.03** — Localización: **NO LLENAR** — lo calcula el Excel automáticamente (aunque el campo está marcado como editable, en la práctica esta celda del Excel es una fórmula).

### 1.04 Alineamiento y contribución al cierre de una brecha prioritaria

- **1.04.01** — Servicios públicos con brecha identificada y priorizada: **NO LLENAR** — lo calcula el Excel automáticamente.
- **1.04.02** — Nombre del Indicador de brecha de acceso a servicios: **NO LLENAR** — lo calcula el Excel automáticamente.
- **1.04.03** — Unidad de medida: **NO LLENAR** — lo calcula el Excel automáticamente.
- **1.04.04** — Espacio geográfico: **NO LLENAR** — lo calcula el Excel automáticamente.
- **1.04.05** — Año: **NO LLENAR** — lo calcula el Excel automáticamente.
- **1.04.06** — Valor: valor numérico. Ejemplo real: "60%".
- **1.04.07** — Contribución del Cierre de Brecha (Valor): valor numérico. Ejemplo real: "1".

## Sección 2: SECCIÓN N°02: DIAGNÓSTICO DEL TERRITORIO

### 2.01 Localización del área de estudio del proyecto

- **2.01.01** — Localización del área de estudio del proyecto: tabla. Columnas: N°, Ubigeo, Departamento, Provincia, Distrito, Localidad/Centro poblado.

### 2.02 Localización del área de influencia del proyecto

- **2.02.01** — Localización del área de influencia del proyecto: tabla. Columnas: N°, Ubigeo, Departamento, Provincia, Distrito, Localidad/Centro poblado.

### 2.03 Macro y micro localización del área de estudio

- **2.03.01** — Mapa de macrolocalización: no se llena con texto (tipo imagen).
- **2.03.02** — Leyenda del mapa de macrolocalización: **NO LLENAR** — lo calcula el Excel automáticamente.
- **2.03.03** — Mapa de microlocalización: no se llena con texto (tipo imagen).
- **2.03.04** — Leyenda del mapa de microlocalización: **NO LLENAR** — lo calcula el Excel automáticamente.
- **2.03.05** — Fuente de información: (el ejemplo de referencia no tiene este campo completado — redactar en base al contexto de sección y a lo que indique el usuario).

### 2.04 Análisis de las características del distrito donde se ubica o ubicará el CIAI

- **2.04.1** — Altitud: Ejemplo real: "3295".
- **2.04.02** — Temperatura media anual: Ejemplo real: "12° C".
- **2.04.03** — Humedad: Ejemplo real: "0%".
- **2.04.04** — Precipitación media anual: Ejemplo real: "3 mm".
- **2.04.05** — Coordenadas geográficas en decimales Latitud y Longitud: no se llena con texto (tipo mapa_coordenadas).
- **2.04.06** — Accesibilidad: Ejemplo real: "El territorio de este distrito se extiende en 89,44 kilómetros cuadrados y se encuentra dentro del conurbano de la ciudad de Cuzco. Se encuentra localizado a 13º 31’ 49” Latitud Sur y 71º 56’ 14” Longitud Oeste. Se encuentra a 15 mitutos del centro de la ciudad de Cusco. Las vías de acceso son asfaltadas, se encuentran en buen estado."".
- **2.04.07** — Condiciones de pobreza: Ejemplo real: "En el distrito aún existe una exclusión social que genera pobreza monetaria en la población principalmente del área rural y zonas periurbanas de expansión, donde la población tiene carencias, ello está condicionado a las limitadas oportunidades de desarrollo que brindan las autoridades, como la calidad educativa que se brinda, el nivel educativo que tiene la población, limitadas oportunidades laborales, servicios de salud integrales que garanticen la esperanza de vida. El distrito de San Sebastián alcanza al año 2019 un Índice de Desarrollo Humano IDH de 0,6806, lo que significa que el nivel de desarrollo humano en el distrito se encuentra en nivel medio. Fuente: Plan de Desarrollo Concertado MD San Sebastián al 2033".
- **2.04.08** — Principales características sociales y económicas: Ejemplo real: "La vulnerabilidad de los derechos de los niños, niñas y adolescentes es un problema que podrían deberse a las limitadas acciones la disfunción familiar, entorno familiar desfavorable y el limitado sistema de protección de las niñas, niños y adolescentes por las instancias que velan por sus derechos esta es ocasionado por los propios padres por múltiples factores como discusiones, disfunciones; así como por personas extrañas que violenta, vulneran sus derechos y dañan su integridad afectando el desarrollo integral, socioemocional y su desarrollo en el futuro como ciudadano, así mismo las instancias que velan por sus derechos no protegen adecuadamente permitiendo la persistencia e incremento de la vulneración de sus derechos. para su reducción se debe promover acciones multisectoriales desde la educación, salud, seguridad y fortalecer las instancias que velan por los derechos de los niños, niñas y adolescentes. Fuente: Plan de Desarrollo Concertado MD San Sebastián al 2033".
- **2.04.09** — C. Acceso a servicios públicos en el distrito: tabla. Columnas: Servicio público, Porcentaje de viviendas con acceso, Año de información, Fuente de información (incluir enlace).

### 2.05 Identificar los peligros que pueden ocurrir en el área de estudio

- **2.05.01** — Identificar los peligros que pueden ocurrir en el área de estudio: tabla. Columnas: Peligros, Sí / No, Características (Intensidad, frecuencia, área de impacto, otros), Sí/No, Características de los cambios o los nuevos peligros.

## Sección 3: SECCIÓN N°03: DIAGNÓSTICO DE LA UNIDAD PRODUCTORA

### 3.01 Nombre de la Unidad Productora

- **3.01.01** — Nombre de la Unidad Productora: Ejemplo real: "CIAI San Antonio".

### 3.02 Código de identificación del CIAI (Local ID) y tipo de CIAI

- **3.02.01** — Código de CIAI: valor numérico. Ejemplo real: "987654".

### 3.03 Localización geográfica de la Unidad Productora

- **3.03.01** — Localización geográfica de la Unidad Productora: tabla. Columnas: N°, UBIGEO, Departamento, Provincia, Distrito, Localidad / Centro poblado, Coordenadas geográficas en decimales Latitud y Longitud.

### 3.04 Diagnóstico de procesos de la Unidad Productora

- **3.04.01** — Caracterización de los procesos de producción del CIAI: tabla. Columnas: N°, Servicio y sus procesos de producción, Descripción ¿En qué consiste el proceso?, Situación actual.

### 3.05 Diagnóstico de los activos de la UP

- **3.05.01** — Diagnóstico de los activos de la UP: tabla. Columnas: Servicio y procesos de producción, Tipo de Factor productivo, Activos estratégicos, Norma técnica, Sí / No, Estado Situacional.

### 3.06 Condiciones técnicas del local del CIAI

- **3.06.1** — ¿El CIAI está alejado de agentes contaminantes o peligros que no puedan ser mitigados?: responder exactamente "Sí" o "No". Ejemplo real: "Sí".
- **3.06.02** — ¿El CIAI está a una distancia no menor de 50 metros de estaciones de expendio de combustible?: responder exactamente "Sí" o "No". Ejemplo real: "Sí".
- **3.06.03** — ¿El CIAI está a una distancia no menor a 25 metros de una línea de alta tensión eléctrica?: responder exactamente "Sí" o "No". Ejemplo real: "Sí".
- **3.06.04** — ¿El CIAI es colindante con hospitales, centros médicos, centros de atención de salud, o similares, de categoría I-3, categoría I-4, categoría II o categoría III?: responder exactamente "Sí" o "No". Ejemplo real: "No".
- **3.06.05** — ¿Las salas del CIAI se ubican en el primer piso?: responder exactamente "Sí" o "No". Ejemplo real: "Sí".
- **3.06.06** — ¿Las puertas de ingreso y salida del CIAI están orientados directamente a una vía de alto tránsito vehicular?: responder exactamente "Sí" o "No". Ejemplo real: "No".
- **3.06.07** — Abastecimiento de Agua: El CIAI cuenta con conexión a una red pública: responder exactamente "Sí" o "No". Ejemplo real: "Sí".
- **3.06.08** — Desagüe: El CIAI cuenta con conexión a una red pública de alcantarillado: responder exactamente "Sí" o "No". Ejemplo real: "Sí".
- **3.06.09** — Energía eléctrica: El CIAI cuenta con conexión a la red pública de abastecimiento de energía eléctrica: responder exactamente "Sí" o "No". Ejemplo real: "Sí".
- **3.06.10** — Titularidad del local donde funciona el CIAI: elegir EXACTAMENTE una de estas opciones: Propiedad del Estado | Propiedad de particulares (privados). Ejemplo real: "Propiedad del Estado".
- **3.06.11** — Situación actual del saneamiento físico legal o arreglo institucional: elegir EXACTAMENTE una de estas opciones: Inscrito en Registros Públicos a favor del PNCM | Caso Gobierno Regional: Resolución y/o acuerdo de consejo que aprueba la afectación a favor o transferencia a favor del PNCM | Caso Municipalidad: Resolución de alcaldía y/o acuerdo de consejo que aprueba la transferencia o afectación en uso a favor del PNCM | Caso Comunidad: Acta de trasferencia o cesión en uso a favor del PNCM, o a favor de una municipalidad, o Gobierno Regional que posteriormente se comprometan a transferirlo a favor de PNCM. | Carta de intención del propietario, declarando su voluntad de vender y se especifique el área y costo por m2, o una intención de donación | Acta de compromiso de la UEI de gestionar el Saneamiento Físico Legal del Terreno antes del inicio de la elaboración del Expediente Técnico. Ejemplo real: "Caso Gobierno Regional: Resolución y/o acuerdo de consejo que aprueba la afectación a favor o transferencia a favor del PNCM".

### 3.07 Detallar las prácticas de operación y mantenimiento del CIAI, en la situación actual

- **3.07.01** — a. Personal: tabla. Columnas: Descripción de las prácticas de operación, en la situación sin proyecto, Detalle, Cantidad, Costo, Total.
- **3.07.02** — b. Servicios y mantenimiento: tabla. Columnas: Descripción de las condiciones del mantenimiento y los servicios del CIAI, en la situación sin proyecto, Detalle, Cantidad, Costo, Total.

### 3.08 Evolución del nivel de producción de servicio de cuidado diurno provisto en el CIAI

- **3.08.01** — Evolución del nivel de producción de servicio de cuidado diurno provisto en el CIAI: tabla. Columnas: Servicios, Unidad de Medida, Año -5, Año -4, Año -3, Año -2, Año -1.

### 3.09 Estimar la exposición de la UP frente a los peligros identificados en el diagnóstico del área de estudio

- **3.09.01** — Estimar la exposición de la UP frente a los peligros identificados en el diagnóstico del área de estudio: tabla. Columnas: Peligros, ¿Cuál es el nivel de exposición del CIAI al peligro?, ¿Cuál es el nivel de fragilidad del CIAI ante la ocurrencia del peligro?, ¿Se cuenta con un plan de contingencia ante la interrupción o alteración del servicio ocasionado por el peligro?.

## Sección 4: SECCIÓN N°04: DIAGNÓSTICO DE LOS INVOLUCRADOS

### 4.01 Descripción de la población afectada

- **4.01.01** — Descripción de la población afectada: tabla. Columnas: Tipo de población, Descripción, Unidad de medida, Cantidad, %.

### 4.02 Caracterización de la población afectada

- **4.02.01** — Niños y niñas que comprenden la población demandante potencial: tabla. Columnas: Variables /indicadores, Categorías, Número de niños, Número de niñas, % respecto a la población demandante efectiva, Fuente.

### 4.03 Matriz de involucrados

- **4.03.01** — Matriz de involucrados: tabla. Columnas: Grupos involucrados, Posición, Situación negativa percibida, Intereses o expectativas, Estrategias del Proyecto de Inversión, Acuerdos y compromisos.

## Sección 5: SECCIÓN N°05: PROBLEMA/OBJETIVO

### 5.01 Definición del problema, sus causas y efectos

- **5.01.01** — Definición del problema central: tabla. Columnas: Descripción del problema central, Indicador, Descripción del indicador, UM, Valor.
- **5.01.02** — Causas directas, indirectas y evidencias: tabla. Columnas: Causas Directas (CD), Causas indirectas (CI), Evidencias.
- **5.01.03** — Efectos directos, sustento y evidencias: tabla. Columnas: Sustento (evidencias), Sustento (evidencias), Efectos Directos (ED).

### 5.02 Definición de los objetivos del proyecto

- **5.02.01** — Definición del objetivo central: tabla. Columnas: Descripción del objetivo central, Indicador, Descripción del indicador, UM, Valor.
- **5.02.02** — Medios fundamentales: tabla. Columnas: N°, Medios fundamentales (componentes), Acciones.
- **5.02.04** — Medios fundamentales: tabla. Columnas: N°, Medios fundamentales (componentes), Acciones.
- **5.02.03** — Fines directos e indirectos: tabla. Columnas: Fines Indirectos (FI), Fines directos (FD).

### 5.03 Descripción de la alternativa de solución al problema

- **5.03.01** — Alternativa de solución: **NO LLENAR** — lo calcula el Excel automáticamente.

## Sección 6: SECCIÓN N°06: HORIZONTE DE EVALUACIÓN

### 6.01 Horizonte de evaluación

- **6.01.01** — Fecha prevista para declarar viabilidad del PI — Fecha de finalización: fecha en formato DD/MM/AAAA. Ejemplo real: "2024-03-23".
- **6.01.02** — Fechas previstas para el proceso de contratación para elaboración del Expediente Técnico — Fecha de inicio: fecha en formato DD/MM/AAAA. Ejemplo real: "2024-03-28".
- **6.01.03** — Fechas previstas para el proceso de contratación para elaboración del Expediente Técnico — Fecha de finalización: fecha en formato DD/MM/AAAA. Ejemplo real: "2024-05-27".
- **6.01.04** — Fechas previstas para inicio de elaboración y aprobación del Expediente Técnico — Fecha de inicio: fecha en formato DD/MM/AAAA. Ejemplo real: "2024-05-28".
- **6.01.05** — Fechas previstas para inicio de elaboración y aprobación del Expediente Técnico — Fecha de finalización: fecha en formato DD/MM/AAAA. Ejemplo real: "2024-10-25".
- **6.01.06** — Fechas previstas para el proceso de contratación para ejecución física del proyecto — Fecha de inicio: fecha en formato DD/MM/AAAA. Ejemplo real: "2024-10-30".
- **6.01.07** — Fechas previstas para el proceso de contratación para ejecución física del proyecto — Fecha de finalización: fecha en formato DD/MM/AAAA. Ejemplo real: "2025-01-28".
- **6.01.08** — Fechas previstas para ejecución física — Fecha de inicio: fecha en formato DD/MM/AAAA. Ejemplo real: "2025-02-02".
- **6.01.09** — Fechas previstas para ejecución física — Fecha de finalización: fecha en formato DD/MM/AAAA. Ejemplo real: "2025-10-30".
- **6.01.10** — Fecha prevista para la fase de Funcionamiento — Fecha de inicio: fecha en formato DD/MM/AAAA. Ejemplo real: "2025-11-04".
- **6.01.11** — Total de horizonte de evaluación (años): **NO LLENAR** — lo calcula el Excel automáticamente.
- **6.01.12** — Fase de Ejecución: **NO LLENAR** — lo calcula el Excel automáticamente.
- **6.01.13** — Fase de Funcionamiento: valor numérico. Ejemplo real: "10".

## Sección 7: SECCIÓN N°07: ANÁLISIS DE MERCADO

### 7.01 Definición y caracterización del Servicio de Cuidado Diurno

- **7.01.01** — Definición y caracterización del Servicio de Cuidado Diurno: Ejemplo real: "El Servicio de Cuidado Diuirno consiste en brindar atención integral a niños entre 6 y 36 meses de edad, que viven en zonas de pobreza y pobreza extrema, y requieren de atención en sus necesidades básicas de salud, nutrición, seguridad, protección, afecto, descanso, juego, aprendizaje y desarrollo de habilidades. Comprende: - Aprendizaje Infantil: Cuidado y Juego. - Promoción de prácticas en el cuidado de la salud en la niña o niño. - Atención alimentaria y nutricional. - Fortalecimiento de prácticas de cuidado saludable y aprendizaje en la familia usuaria. La unidad de medida del servicio es: niños y niñas atendidos en el CIAI".

### 7.02 Análisis y proyección de la demanda del servicio

- **7.02.01** — Análisis y proyección de la demanda del servicio: tabla. Columnas: Tipo de población, Año 0, Año 1, Año 2, Año 3, Año 4, Año 5, Año 6, Año 7, Año 8, Año 9, Año 10.
- **7.02.02** — Consideraciones para definir la demanda del servicio: Ejemplo real: "Población Total: es la población total del área de influencia definida en el diagnóstico. Población de Referencia: es la proporción de la población total, vinculada con el objetivo central del proyecto de inversión, es decir, que está dentro del grupo etario que podría recibir el Servicio de Cuidado Diurno. Población Demandante Potencial: es el segmento de la población de referencia, que es afectada por el problema central, que no accede al Servicio de Cuidado Diurno o accede de forma inadecuada. Población Demandante Efectiva: es el segmento de la población demandante potencial que cumple con los criterios de focalización definidos por el Sector para acceder al Servicio de Cuidado Diurno, en un CIAI. Población Objetivo: es aquella parte de la población demandante efectiva que el proyecto está en condiciones de atender de forma integral en un CIAI, considerando la política de focalización del Sector y la definición de la meta correspondiente. Es definida por el Programa Nacional Cuna Más.".

### 7.03 Proyección de la demanda del Servicio de Cuidado Diurno

- **7.03.01** — Proyección de la demanda del Servicio de Cuidado Diurno: tabla. Columnas: Detalle, Unidad de Medida, Año 0, Año 1, Año 2, Año 3, Año 4, Año 5, Año 6, Año 7, Año 8, Año 9, Año 10.

### 7.04 Estimación de la oferta optimizada (sin proyecto)

- **7.04.01** — Estimación de la oferta optimizada (sin proyecto): tabla. Columnas: Condiciones para optimización de la oferta, Capacidad actual del CIAI, Capacidad optimizada del CIAI, Factor de producción a optimizar, Acción para optimizar la oferta actual.

### 7.05 Proyección de la oferta del Servicio de Cuidado Diurno

- **7.05.01** — Proyección de la oferta del Servicio de Cuidado Diurno: tabla. Columnas: Detalle, Unidad de Medida, , , , , , , , , , , .

### 7.06 Brecha del Servicio de Cuidado Diurno

- **7.06.01** — Brecha del Servicio de Cuidado Diurno: tabla. Columnas: Detalle, Unidad de Medida, , , , , , , , , , , .

## Sección 08: SECCIÓN N°08: ANÁLISIS TÉCNICO

### 8.01 ANÁLISIS DE TAMAÑO (¿cuánto producir?)

- **08.01.1** — Nuevo campo: tabla. Columnas: N°, Factor condicionante, Unidad de Medida, Valor, Tamaño (tipo) de CIAI, según factor condicionante, Área mínima rquerida del CIAI, no incluye Servicio Alimentario (m2) 1/, Área minima requerida del CIAI, incluye Servicio alimentario (m2) 1/.
- **08.01.2** — Tamaño de CIAI a implementar con el proyecto de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.

### 8.02 análisis de localización (¿dónde producir?)

- **08.02.1** — El CIAI esta alejado de agentes contaminantes o peligros que no puedan ser mitigados: Ejemplo real: "Cumple".
- **08.02.2** — EI CIAI esta a una distancia no menor de 50 metros de estaciones de expendio de combustible: Ejemplo real: "Cumple".
- **08.02.3** — EI CIAI esta a una distancia no menor a 25 metros de una linea de alta tension electrica: Ejemplo real: "Cumple".
- **08.02.4** — EI CIAI es colindante con hospitales, centros medicos, centros de atención de salud, o similares, de categoría I-3, categoría I-4, categoría II o categoría III: Ejemplo real: "Cumple".
- **08.02.5** — Las puertas de ingreso y salida del CIAI estan orientados directamente a una vía de alto transito vehicular: Ejemplo real: "Cumple".
- **08.02.6** — Abastecimiento de Agua: EI CIAI cuenta o contara con conexion a una red pública: Ejemplo real: "Cumple".
- **08.02.7** — Desague: EI CIAI cuenta o contara con conexion a una red publica de alcantarillado: Ejemplo real: "Cumple".
- **08.02.8** — Energía eléctrica: EI CIAI cuenta o contara con conexion a la red pública de abastecimiento de energía eléctrica.: Ejemplo real: "Cumple".
- **08.02.9** — Titularidad del local donde funciona el CIAI: Ejemplo real: "Propiedad del Estado".
- **08.02.10** — Situación del saneamiento físico legal o arreglo institucional, prevista para la implementación del proyecto: Ejemplo real: "Caso Gobierno Regional: Resolución y/o acuerdo de consejo que aprueba la afectación a favor o transferencia a favor del PNCM".
- **08.02.11** — Ubicación del CIAI: tabla. Columnas: N°, Departamento, Provincia, Distrito, Localidad/Centro poblado, Coordenadas geográficas en decimales Latitud y Longitud.

### 8.03 ANÁLISIS DE TECNOLOGÍA (¿CÓMO PRODUCIR?)

- **08.03.1** — Descripción del proceso de producción del servicio (con proyecto): tabla. Columnas: Servicio, Tipo Factor Productivo, Activo del CIAI, Descripción, ¿Se incluye como parte del PI?, Normativa aplicable (del PNCM o RNE, según corresponda).

### 8.04 IDENTIficación de medidas de reducción del riesgo de desastres

- **08.04.1** — Descripción de las medidas de reducción del riesgo de desastres, en función al análisis de la exposición y fragilidad del CIAI: tabla. Columnas: Peligros, Exposición, Fragilidad, Resiliencia, Descripcion de las medidas de reduccion del riesgo de desastres, en funcion al resultado del analisis de la exposicion y fragilidad del CIAI (proponer medidas en los casos que el resultado sea "Alto" o "Medio").

### 8.05 resumen de las alternativas técnicas

- **08.05.1** — Resumen de las alternativas técnicas: tabla. Columnas: Descripción de alternativas técnicas, Tamaño, Localización, Descripción de la tecnología.

### 8.06 METAS FÍSICAS DE LOS ACTIVOS QUE SE BUSCAN CREAR O INTERVENIR CON EL PROYECTO

- **08.06.1** — Componente 1: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Área normativa mínima requerida por unidad física (m2), Unidad de medida, Cantidad, Unidad de medida, Cantidad.
- **08.06.2** — Componente 2: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad.
- **08.06.3** — Componente 3: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad.
- **08.06.4** — Componente 4: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad.

## Sección 09: SECCIÓN N°09: COSTOS DEL PROYECTO - ALTERNATIVA 1

### 9.01 cOSTO DE EJECUCIÓN Física de las acciones

- **09.01.1** — Componente 1.1: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Unidad de medida, Cantidad, Costo unitario, Costo total.
- **09.01.2** — Componente 1.2: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Unidad de medida, Cantidad, Costo unitario, Costo total.
- **09.01.3** — Componente 2: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Costo total.
- **09.01.4** — Componente 3: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Costo total.
- **09.01.5** — Componente 4: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Costo total.
- **09.01.6** — Costos indirectos: tabla. Columnas: Otros costos, Costos a precios de mercado, % respecto al costo directo.
- **09.01.7** — Subtotal de otros costos de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.
- **09.01.8** — Costo Total de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.

### 9.02 COstos de reinversión

- **09.02.1** — Costos de reinversión: tabla. Columnas: Activos, UM, Cantidad, y1, y2, y3, y4, y5, y6, y7, y8, y9, y10.

### 9.03 cOSTOS DE OPERACIÓN Y MANTENIMIENTO CON Y SIN PROYECTO

- **09.03.1** — Fecha prevista de inicio de operaciones: (mes / año): **NO LLENAR** — lo calcula el Excel automáticamente.
- **09.03.2** — Horizonte de funcionamiento (años): Ejemplo real: "10".
- **09.03.3** — Costos de operación y mantenimiento — Situación sin proyecto: tabla. Columnas: Detalle, Cantidad, Costo, Total.
- **09.03.4** — Total de costos de operación y mantenimiento (sin proyecto): **NO LLENAR** — lo calcula el Excel automáticamente.
- **09.03.5** — Costos de operación y mantenimiento — Situación con proyecto: tabla. Columnas: Detalle, Cantidad, Costo, Total.
- **09.03.6** — Total de costos de operación y mantenimiento (con proyecto): **NO LLENAR** — lo calcula el Excel automáticamente.

### 9.04 Cronograma de inversión de metas financieras

- **09.04.1** — Fecha prevista de inicio de ejecución (mes y año): **NO LLENAR** — lo calcula el Excel automáticamente.
- **09.04.2** — Tipo de periodo: Ejemplo real: "Trimestre".
- **09.04.3** — Número de periodos: valor numérico.
- **09.04.4** — Cronograma de inversión de metas financieras: tabla. Columnas: Componente, Tipo de factor productivo, Tri 1, Tri 2, Tri 3, Tri 4, Tri 5, Tri 6, Tri 7, Tri 8, Costo estimado de inversión a precios de mercado (Soles).
- **09.04.5** — Sub total del cronograma de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.
- **09.04.6** — Otros costos: tabla. Columnas: Otros costos, Tri 1, Tri 2, Tri 3, Tri 4, Tri 5, Tri 6, Tri 7, Tri 8, Costos a precio de mercado.
- **09.04.7** — Sub total de otros costos: **NO LLENAR** — lo calcula el Excel automáticamente.
- **09.04.8** — Costo total de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.
- **09.04.9** — Control concurrente: valor numérico. Ejemplo real: "33921.1".

### 9.05 Cronograma de metas físicas

- **09.05.1** — Cronograma de metas físicas: tabla. Columnas: Componente, Tipo de factor productivo, Unidad de medida representativa, Tri 1, Tri 2, Tri 3, Tri 4, Tri 5, Tri 6, Tri 7, Tri 8, Total Meta Física.

## Sección 10: SECCIÓN N°09: COSTOS DEL PROYECTO - ALTERNATIVA 2

### 9.01 Costo de ejecución física de las acciones

- **10.01.1** — Componente 1: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Unidad de medida, Cantidad, Costo unitario, Costo total.
- **10.01.2** — Componente 2: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Costo total.
- **10.01.3** — Componente 3: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Costo total.
- **10.01.4** — Componente 4: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Costo total.
- **10.01.5** — Medidas de reducción del riesgo de desastre y mitigación ambiental: tabla. Columnas: Activos, Unidad de medida, Cantidad, Costo unitario, Costo total.
- **10.01.6** — Sub Total de costos directos: **NO LLENAR** — lo calcula el Excel automáticamente.
- **10.01.7** — Costos indirectos: tabla. Columnas: Otros costos, Costos a precios de mercado, % respecto al costo directo.
- **10.01.8** — Subtotal de otros costos de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.
- **10.01.9** — Costo Total de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.

### 9.02 Costos de reinversión

- **10.02.1** — Costos de reinversión: tabla. Columnas: Activos, UM, Cantidad, Año 1, Año 2, Año 3, Año 4, Año 5, Año 6, Año 7, Año 8, Año 9, Año 10.

### 9.03 Costos de operación y mantenimiento con y sin proyecto

- **10.03.1** — Fecha prevista de inicio de operaciones (mes / año): **NO LLENAR** — lo calcula el Excel automáticamente.
- **10.03.2** — Horizonte de funcionamiento (años): valor numérico. Ejemplo real: "10".
- **10.03.3** — Costos de operación y mantenimiento — Situación sin proyecto: tabla. Columnas: Detalle, Cantidad, Costo, Total.
- **10.03.4** — Total de costos de operación y mantenimiento (sin proyecto): **NO LLENAR** — lo calcula el Excel automáticamente.
- **10.03.5** — Costos de operación y mantenimiento — Situación con proyecto: tabla. Columnas: Detalle, Cantidad, Costo, Total.
- **10.03.6** — Total de costos de operación y mantenimiento (con proyecto): **NO LLENAR** — lo calcula el Excel automáticamente.

### 9.04 Cronograma de inversión de metas financieras

- **10.04.1** — Fecha prevista de inicio de ejecución (mes y año): **NO LLENAR** — lo calcula el Excel automáticamente.
- **10.04.2** — Tipo de periodo: Ejemplo real: "Trimestre".
- **10.04.3** — Número de periodos: valor numérico.
- **10.04.4** — Cronograma de inversión de metas financieras: tabla. Columnas: Componente, Tipo de factor productivo, Tri 1, Tri 2, Tri 3, Tri 4, Tri 5, Tri 6, Tri 7, Tri 8, Costo estimado de inversión a precios de mercado (Soles).
- **10.04.5** — Sub total del cronograma de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.
- **10.04.6** — Otros costos: tabla. Columnas: Otros costos, Tri 1, Tri 2, Tri 3, Tri 4, Tri 5, Tri 6, Tri 7, Tri 8, Costos a precio de mercado.
- **10.04.7** — Sub total de otros costos: **NO LLENAR** — lo calcula el Excel automáticamente.
- **10.04.8** — Costo total de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.
- **10.04.9** — Control concurrente: valor numérico.

### 9.05 Cronograma de metas físicas

- **10.05.1** — Cronograma de metas físicas: tabla. Columnas: Componente, Tipo de factor productivo, Unidad de medida representativa, Tri 1, Tri 2, Tri 3, Tri 4, Tri 5, Tri 6, Tri 7, Tri 8, Total Meta Física.

## Sección 11: SECCIÓN N°09: COSTOS DEL PROYECTO - ALTERNATIVA 3

### 9.01 Costo de ejecución física de las acciones

- **11.01.1** — Componente 1: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Unidad de medida, Cantidad, Costo unitario, Costo total.
- **11.01.2** — Componente 2: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Costo total.
- **11.01.3** — Componente 3: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Costo total.
- **11.01.4** — Componente 4: tabla. Columnas: Acción sobre el activo, Tipo de factor productivo, Activos, Unidad de medida, Cantidad, Costo total.
- **11.01.5** — Medidas de reducción del riesgo de desastre y mitigación ambiental: tabla. Columnas: Activos, Unidad de medida, Cantidad, Costo unitario, Costo total.
- **11.01.6** — Sub Total de costos directos: **NO LLENAR** — lo calcula el Excel automáticamente.
- **11.01.7** — Costos indirectos: tabla. Columnas: Otros costos, Costos a precios de mercado, % respecto al costo directo.
- **11.01.8** — Subtotal de otros costos de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.
- **11.01.9** — Costo Total de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.

### 9.02 Costos de reinversión

- **11.02.1** — Costos de reinversión: tabla. Columnas: Activos, UM, Cantidad, Año 1, Año 2, Año 3, Año 4, Año 5, Año 6, Año 7, Año 8, Año 9, Año 10.

### 9.03 Costos de operación y mantenimiento con y sin proyecto

- **11.03.1** — Fecha prevista de inicio de operaciones (mes / año): **NO LLENAR** — lo calcula el Excel automáticamente.
- **11.03.2** — Horizonte de funcionamiento (años): valor numérico. Ejemplo real: "10".
- **11.03.3** — Costos de operación y mantenimiento — Situación sin proyecto: tabla. Columnas: Detalle, Cantidad, Costo, Total.
- **11.03.4** — Total de costos de operación y mantenimiento (sin proyecto): **NO LLENAR** — lo calcula el Excel automáticamente.
- **11.03.5** — Costos de operación y mantenimiento — Situación con proyecto: tabla. Columnas: Detalle, Cantidad, Costo, Total.
- **11.03.6** — Total de costos de operación y mantenimiento (con proyecto): **NO LLENAR** — lo calcula el Excel automáticamente.

### 9.04 Cronograma de inversión de metas financieras

- **11.04.1** — Fecha prevista de inicio de ejecución (mes y año): **NO LLENAR** — lo calcula el Excel automáticamente.
- **11.04.2** — Tipo de periodo: Ejemplo real: "Trimestre".
- **11.04.3** — Número de periodos: valor numérico.
- **11.04.4** — Cronograma de inversión de metas financieras: tabla. Columnas: Componente, Tipo de factor productivo, Tri 1, Tri 2, Tri 3, Tri 4, Tri 5, Tri 6, Tri 7, Tri 8, Costo estimado de inversión a precios de mercado (Soles).
- **11.04.5** — Sub total del cronograma de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.
- **11.04.6** — Otros costos: tabla. Columnas: Otros costos, Tri 1, Tri 2, Tri 3, Tri 4, Tri 5, Tri 6, Tri 7, Tri 8, Costos a precio de mercado.
- **11.04.7** — Sub total de otros costos: **NO LLENAR** — lo calcula el Excel automáticamente.
- **11.04.8** — Costo total de inversión: **NO LLENAR** — lo calcula el Excel automáticamente.
- **11.04.9** — Control concurrente: valor numérico.

### 9.05 Cronograma de metas físicas

- **11.05.1** — Cronograma de metas físicas: tabla. Columnas: Componente, Tipo de factor productivo, Unidad de medida representativa, Tri 1, Tri 2, Tri 3, Tri 4, Tri 5, Tri 6, Tri 7, Tri 8, Total Meta Física.

## Sección 12: SECCIÓN N°10: EVALUACIÓN SOCIAL - ALTERNATIVA 1

### 10.01 BENEFICIOS SOCIALES

- **12.01.1** — Benificios de la intervención: tabla. Columnas: Benificios de la intervención.

### 10.02 COSTOS SOCIALES

- **12.02.1** — Transformación de costos de inversión a precios sociales: tabla. Columnas: Detalle, Costo a precios de mercado , Factor de corrección, Costo a precios sociales .
- **12.02.2** — Transformación de costos de operación y mantenimiento a precios sociales: tabla. Columnas: Detalle, Costo a precios de mercado , Factor de corrección, Costo a precios sociales .

### 10.03 FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL)

- **12.03.1** — FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL): tabla. Columnas: Años, Año 0, Año 1, Año 2, Año 3, Año 4, Año 5, Año 6, Año 7, Año 8, Año 9, Año 10.

### 10.04 INDICADORES DE RENTABILIDAD SOCIAL

- **12.04.1** — INDICADORES DE RENTABILIDAD SOCIAL: tabla. Columnas: Tipo, Criterio de elección**, Alternativa 1.

### 10.05 ANÁLISIS DE SENSIBILIDAD

- **12.05.1** — ANALISIS DE SENSIBILIDAD BIDIMENSIONAL: tabla. Columnas: ICE (S/), Variación % del total de beneficiarios, 20, 15, 10, 0, -10, -15, -20.

## Sección 13: SECCIÓN N°10: EVALUACIÓN SOCIAL - ALTERNATIVA 2

### 10.01 BENEFICIOS SOCIALES

- **13.01.1** — Benificios de la intervención: tabla. Columnas: Benificios de la intervención.

### 10.02 COSTOS SOCIALES

- **13.02.1** — Transformación de costos de inversión a precios sociales: tabla. Columnas: Detalle, Costo a precios de mercado , Factor de corrección, Costo a precios sociales .
- **13.02.2** — Transformación de costos de operación y mantenimiento a precios sociales: tabla. Columnas: Detalle, Costo a precios de mercado , Factor de corrección, Costo a precios sociales .

### 10.03 FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL)

- **13.03.1** — FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL): tabla. Columnas: Años, Año 0, Año 1, Año 2, Año 3, Año 4, Año 5, Año 6, Año 7, Año 8, Año 9, Año 10.

### 10.04 INDICADORES DE RENTABILIDAD SOCIAL

- **13.04.1** — INDICADORES DE RENTABILIDAD SOCIAL: tabla. Columnas: Tipo, Criterio de elección**, Alternativa 1.

### 10.05 ANÁLISIS DE SENSIBILIDAD

- **13.05.1** — ANALISIS DE SENSIBILIDAD BIDIMENSIONAL: tabla. Columnas: ICE / VAN, Variación % del total de beneficiarios, 20, 15, 10, 0, -10, -15, -20.

## Sección 14: SECCIÓN N°10: EVALUACIÓN SOCIAL - ALTERNATIVA 3

### 10.01 BENEFICIOS SOCIALES

- **14.01.1** — Benificios de la intervención: tabla. Columnas: Benificios de la intervención.

### 10.02 COSTOS SOCIALES

- **14.02.1** — Transformación de costos de inversión a precios sociales: tabla. Columnas: Detalle, Costo a precios de mercado , Factor de corrección, Costo a precios sociales .
- **14.02.2** — Transformación de costos de operación y mantenimiento a precios sociales: tabla. Columnas: Detalle, Costo a precios de mercado , Factor de corrección, Costo a precios sociales .

### 10.03 FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL)

- **14.03.1** — FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL): tabla. Columnas: Años, Año 0, Año 1, Año 2, Año 3, Año 4, Año 5, Año 6, Año 7, Año 8, Año 9, Año 10.

### 10.04 INDICADORES DE RENTABILIDAD SOCIAL

- **14.04.1** — INDICADORES DE RENTABILIDAD SOCIAL: tabla. Columnas: Tipo, Criterio de elección**, Alternativa 1.

### 10.05 ANÁLISIS DE SENSIBILIDAD

- **14.05.1** — ANALISIS DE SENSIBILIDAD BIDIMENSIONAL: tabla. Columnas: ICE / VAN, Variación % del total de beneficiarios, 20, 15, 10, 0, -10, -15, -20.

## Sección 15: SECCIÓN N°11: SOSTENIBILIDAD (de la alternativa seleccionada)

### 11.01 dEscripción de la capacidad institucional en la sostenibilidad del proyecto

- **15.01.1** — Nuevo campo: tabla. Columnas: Ítem, Descripción, Documento de opinión favorable del PNCM.

### 11.02 Gestión integral de los riesgos

- **15.02.1** — Gestión integral de los riesgos: tabla. Columnas: Tipo de riesgo, Descripción del riesgo, Probabilidad de ocurrencia, Impacto, Estimación de riesgo, Medidas de mitigación del riesgo.

## Sección 16: SECCIÓN N°12: GESTIÓN DEL PROYECTO (de la alternativa seleccionada)

### 12.01 plan de implementación

- **16.01.1** — Nuevo campo: tabla. Columnas: Actividades del Plan de Implementación, Inicio, Fin, Órgano Responsable, 1, 2, 3, 4, 5, 6, 7, 8.

### 12.02 MODALIDAD DE EJECUCIÓN DE PROYECTO

- **16.02.1** — Tipo de ejecución: Ejemplo real: "Administración directa".

### 12.03 REQUERIMIENTOS INSTITUCIONALES Y NORMATIVOS EN LA FASE DE EJECUCIÓN Y FASE DE FUNCIONAMIENTO

- **16.03.1** — Condiciones previas relevantes para la fase de ejecución: tabla. Columnas: Condición o requerimiento, Marcar, Estado situacional.

### 12.04 eNTIDAD QUE ESTARÁ A CARGO DE LA OPERACIÓN Y MANTENIMIENTO

- **16.04.1** — Programa Nacional Cuna Más: Ejemplo real: "Programa Nacional Cuna Más".

### 12.05 FUENTE DE FINANCIAMIENTO

- **16.05.1** — Fuente de Financiamiento: Ejemplo real: "Recursos ordinarios".

## Sección 17: SECCIÓN N°13: IMPACTO AMBIENTAL (de la alternativa seleccionada)

### 13.01 Impacto ambiental

- **17.01.1** — Impacto ambiental: tabla. Columnas: IMPACTOS NEGATIVOS, MEDIDAS DE MITIGACIÓN, COSTO (S/).

## Sección 18: SECCIÓN N°14: MARCO LÓGICO (de la alternativa seleccionada)

### 14.01 RESUmen del proyecto: matriz del marco lógico

- **18.01.1** — Matriz del marco lógico: tabla. Columnas: Supuestos, Medios de verificación, Nivel de objetivo, Indicador, Valor.

## Sección 19: SECCIÓN N°15: CONCLUSIONES Y RECOMENDACIONES

### 15.01 CONCLUSIONES Y RECOMENDACIONES

- **19.01.1** — CONCLUSIONES Y RECOMENDACIONES: (el ejemplo de referencia no tiene este campo completado — redactar en base al contexto de sección y a lo que indique el usuario).

## Sección 20: SECCIÓN N°17: ANEXOS

### 17.01 Anexos

- **20.01.1** — Anexos: tabla. Columnas: Nro., Descripción del anexo, ¿Se presenta el anexo como parte de la Ficha Técnica Estándar?.
