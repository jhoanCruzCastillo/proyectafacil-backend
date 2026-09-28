# GUÍA DE LLENADO — SECCIONES B a F

*(FTE establecimientos de salud de 12 horas con rol Puerta de Entrada — hoja "FTE Sección B-F")*

Es la hoja principal de la ficha: concentra Datos Generales (B), Alineamiento a brecha (C),
Institucionalidad (D), Identificación / Formulación / Evaluación (E) y Conclusiones (F).

> **Requisito previo.** El instructivo es explícito: antes de llenar la Sección E hay que tener
> llenas la **Sección G** (análisis RIS), la **Sección H** (infraestructura) y la **Sección I**
> (PMF). Buena parte de lo que aparece acá viene predeterminado de esas hojas.

---

## B — Nombre del proyecto

**Se genera automáticamente.** Sale de combinar la naturaleza de intervención + el código único
**RENIPRESS** del ES (con todos sus dígitos) + el nombre del centro poblado. No se redacta a mano.

Las cuatro naturalezas admitidas y su criterio están en el contexto general: Mejoramiento (calidad),
Ampliación (cobertura), Mejoramiento y Ampliación (ambas), Recuperación (capacidad afectada).

---

## C — Alineamiento a una brecha prioritaria

Casi todo viene **predeterminado por el sector**. No se inventa ni se ajusta:

| Campo | Valor fijo |
|---|---|
| Servicio público con brecha | Servicio de Atención de Salud Básica |
| Indicador de producto | % de ES del primer nivel de atención con capacidad instalada inadecuada (brecha de **calidad**) |
| Unidad de medida | Establecimiento de Salud |
| **Contribución al cierre de brecha** | **1** |
| Tipología del proyecto | Establecimientos de salud del primer nivel de atención |
| Año base | 2025 |
| Valor del indicador | el que maneja la OPMI a nivel distrital |
| Función / división / grupo funcional / sector | predefinidos en la FTE |

La contribución es **1** porque la unidad es el establecimiento: el PI lo interviene de forma
integral y, al terminar, cierra la brecha de calidad de ese ES.

---

## D — Institucionalidad

Tres bloques (OPMI, UF, UEI). En cada uno: seleccionado el Sector, se consigna el **pliego**, la
**unidad orgánica** y el **nombre completo** del responsable. En la UF se consigna además el
responsable de la **formulación**, que puede ser distinto del responsable de la UF. Si el proyecto lo
formula una mancomunidad, se selecciona "Mancomunidades Regionales y Municipales".

---

## E1 — Identificación

### E1.1.1 El territorio
Área de estudio y área de influencia **vienen de la Sección G**. En proyectos del primer nivel de
atención **ambas coinciden**: el área de estudio contiene al área de influencia y por lo general son
la misma, porque la población beneficiaria es la suma de la asignada al ES más la del ámbito de
influencia.

Lo que **sí** se llena a mano: localización, coordenadas geográficas, y dos listas que son **insumo
directo del cálculo de la demanda**:

- **Región**: costa, sierra, selva, Lima Metropolitana y Callao.
- **Zona**: urbana o rural.

### E1.1.2 La población
Predeterminada desde las tablas **G1.2 y G1.3**. La fórmula es:

> Población total = población asignada al ES Puerta de Entrada + Σ población de los ES vinculados

Se valida con la DIRESA/GERESA/DIRIS. Fuentes complementarias: **REUNIS** y **GEO RIS**.

La **tasa de crecimiento poblacional** sí se digita a mano (distrital, provincial o regional; se
sugiere INEI), junto con su fuente verificable. Sirve para proyectar la demanda.

### E1.1.3 La Unidad Productora
- **Datos generales**: se consigna el **RENIPRESS completo, incluyendo los ceros**, y el Excel
  genera solo el nombre del ES, su categoría actual, distrito, provincia, departamento y
  coordenadas. La localidad se coloca solo de corresponder.
- **Evolución de la producción**: servicios que brinda hoy el ES, unidad de medida y producción de
  los **últimos 5 años**.
- **Diagnóstico de UPSS (factores productivos)**: se evalúan infraestructura, equipo y recursos
  humanos.

**Criterio del estado del equipo — el instructivo lo define con precisión:**

| Estado | Definición |
|---|---|
| **Bueno** | Perfectas condiciones técnicas y físicas, **dentro** de su vida útil y **sin** mantenimiento correctivo previo. |
| **Regular / deficiente** | Opera en condiciones normales y dentro de parámetros, pero **superó** su vida útil y **tuvo** mantenimiento correctivo. |
| **Malo** | Condiciones deficientes, opera fuera de parámetros o no opera; superó su vida útil, haya tenido o no mantenimiento. |

Fuente: inventario de equipos y Formatos N.° 01 y N.° 02 de la Directiva N.° 004-2013-DGIEM/MINSA.
Los recursos humanos se analizan contra la cartera aprobada y el número de ambientes, según la
**R.M. N.° 176-2014/MINSA**.

### E1.1.3.4 Oferta sin proyecto — el factor limitante
La oferta actual **no es la suma de capacidades**: es la del activo con **menor** capacidad, el que
limita al conjunto.

> **Oferta sin proyecto = mín f(OH, OI, OE)** — recursos humanos, infraestructura, equipamiento.

Dos casos que el instructivo ejemplifica:
- Si la limitante es un **equipo deteriorado**, solo cuentan las atenciones brindadas con el equipo
  funcionando; las realizadas con el equipo malogrado se computan como **cero** (mala calidad).
- Si la limitante es la **infraestructura**, con informe de inhabitabilidad de Defensa Civil o de un
  ingeniero estructuralista que concluya demolición, la oferta sin proyecto puede ser **cero**.

### E1.2 Problema y causas — vienen predeterminadas
El problema central sigue una plantilla fija, que puede ajustarse pero se identifica **desde la
demanda** (necesidad insatisfecha):

> *"Población del área de influencia del [ES] accede a inadecuados y limitados servicios de salud del
> primer nivel de atención en salud"*

**Causas directas (2, predeterminadas):** inadecuada e insuficiente capacidad operativa y funcional
en los servicios de salud · existencia de barreras socioeconómicas y culturales que limitan el
acceso.

**Causas indirectas (5, predeterminadas):** infraestructura física limitada e inadecuada ·
equipamiento inadecuado e insuficiente · inadecuado mantenimiento preventivo y correctivo ·
limitados recursos humanos *(el proyecto **no** interviene en este factor: corresponde a gasto
corriente y a ordenamiento de la oferta)* · inadecuada y limitada promoción de la cartera de
servicios.

### E1.3 Planteamiento
El **objetivo central** es el espejo del problema: *"…accede a **adecuados** servicios de salud del
primer nivel de atención en salud"*.

El **indicador** del objetivo se elige según la realidad del ES; se sugiere **población atendida**,
con unidad de medida **Atenciones**. Hay que consignar **línea base**, **valor al final del PI** y
**fuente de verificación** (boletines, informes de gestión, ASIS local o regional).

---

## E2 — Formulación

### E2.1 Horizonte de evaluación
La ficha está diseñada para **13 años en total**: ejecución + funcionamiento. La UF define el reparto.
Combinaciones que ejemplifica el instructivo: **3 + 10**, **2 + 11** o **1 + 12**.

### E2.2 y E2.3 Demanda y balance
La demanda está **predeterminada** a partir de la Sección G (población, referencias, cartera,
atenciones de los últimos 5 años). El balance oferta-demanda resulta de cruzar esa demanda con la
oferta optimizada de E1.1.3.4.

### E2.4 Análisis técnico
El nivel de detalle esperado es **ingeniería conceptual**: suficiente para aproximar magnitud de
inversión, costos y beneficios.

- **Tamaño (PMF)**: el número de ambientes del **Anexo N.° 03** es **referencial** y puede
  modificarse, pero solo con sustento técnico de la Autoridad Sanitaria Regional y atendiendo a
  cuatro criterios: **i)** grado de utilización, **ii)** perfil epidemiológico de la zona,
  **iii)** densidad poblacional, **iv)** accesibilidad, distancia y tiempo. Todo incremento debe
  quedar sustentado en el documento de aprobación de la DIRESA/GERESA/DIRIS.
- **Localización**: viene de la Sección J.
- **Tecnología**: dos caminos, y el costeo cambia según cuál sea.
  - **Sistema convencional** → costos según **R.D. 041-2013-DGIEM** (Directiva N.° 003-2013-DGIEM/MINSA)
    y los Cuadros de Valores Unitarios Oficiales de Edificaciones vigentes para costa, sierra o selva.
    Las obras exteriores se calculan con el cuadro de valores unitarios a costo directo de obras
    complementarias publicado en el diario oficial.
  - **Sistema no convencional** → requiere aprobación de **SENCICO**, y los costos se sustentan con
    **cotizaciones**.

### E2.4.4 Costos
- **Inversión**: se ingresa manualmente (infraestructura, equipamiento, mobiliario, otros), con la
  fecha de inicio y término del expediente técnico y de la ejecución física. El equipamiento se
  estima con costos referenciales del sector o cotizaciones.
- **Cronograma**: fecha (día, mes y año) de inicio, **tipo de periodo (semestral o trimestral)** y
  número de periodos. Se desagrega en **cronograma físico** (unidad de medida y cantidad por ítem) y
  **cronograma financiero** (inversión en soles por periodo).
- **Reposición en la fase de funcionamiento**: se estima en **20 % en el año 5** y **30 % en el año
  9** de la operación y mantenimiento. Se llena manualmente.
- **O&M sin proyecto**: costos reales en que incurre hoy el ES (remuneraciones, servicios, insumos,
  mantenimiento) a precios de mercado — los precios sociales aparecen predeterminados. Fuente:
  planillas, reportes de gastos, inventario, facturas, recibos y rendiciones del propio ES.
- **O&M con proyecto**: recursos humanos **por grupo ocupacional** (médico cirujano, obstetra,
  enfermero, nutricionista, administrativo…), con cantidad, condición laboral, remuneración anual,
  aguinaldo y costo total anual. La brecha de RRHH se calcula según la **R.M. N.° 176-2014/MINSA**.

---

## E3 — Evaluación social

- **Sostenibilidad**: se consignan los datos de la **Unidad Ejecutora Presupuestal** responsable de
  la O&M. El **documento de compromiso de O&M** —que incluye la disponibilidad de recursos humanos—
  debe estar suscrito por la **Autoridad Sanitaria (DIRESA/GERESA/DIRIS)**, no por la UF.
- **Índice de cobertura**: se calcula aparte, en hoja Excel complementaria.

  > índice = (ingresos incrementales a precios de mercado ÷ costos incrementales de O&M a precios de
  > mercado) × 100

  Los ingresos salen del número de atenciones proyectadas por el tarifario del ES y del SIS.
- **Mitigación de riesgos**: identificar riesgo (operacional, cambio climático, mercado, financiero)
  e impacto, y las medidas del PI. Los costos de reducción de riesgo **no se desagregan**, salvo que
  sean obras complementarias (diques, muros de contención).
- **Criterios de decisión**: los indicadores se calculan **automáticamente**; solo se digita el
  **número de atenciones**.
- **Modalidad de ejecución** y **fuente de financiamiento**: se seleccionan de lista.

---

## F — Conclusiones

- **F1**: marcar con "X" **VIABLE** o **NO VIABLE**. Una sola de las dos.
- **F2**: argumentos del resultado — cierre de brechas, accesibilidad, niveles de intervención,
  población beneficiaria en el horizonte, sostenibilidad. Además, recomendaciones para la fase de
  ejecución que aseguren consistencia con la concepción técnica aprobada (saneamiento físico legal
  pendiente, compromisos de terceros, obras de mitigación).
- **F3**: documentos que **deben** estar registrados en el Banco de Inversiones **antes** de declarar
  la viabilidad. Sin ellos no puede otorgarse, bajo responsabilidad de la UF:
  1. Formato 07-A impreso desde el aplicativo.
  2. Ficha Técnica Estándar firmada.
  3. Resumen ejecutivo.
  4. Resolución Directoral de RIS conformada/estructurada, o documento de RIS simulada con el acta de
     tele reunión.
  5. Oficio e informe de aprobación del **PMF** por DIRESA/GERESA/DIRIS, con el PMF y el análisis de
     red (Secciones I y G).
  6. Documento de compromiso de O&M firmado por la Autoridad Sanitaria.

---

## Errores que hay que evitar

- Redactar el nombre del proyecto a mano: se autogenera.
- Cambiar los valores predeterminados de la Sección C, en especial la contribución al cierre de
  brecha (siempre **1**).
- Calcular la oferta sin proyecto sumando capacidades en vez de tomar el **mínimo** de los tres
  factores.
- Proponer intervenir en recursos humanos: el instructivo lo excluye expresamente del alcance del PI.
- Repartir el horizonte fuera de los 13 años totales.
- Modificar el número de ambientes del PMF sin el sustento de la Autoridad Sanitaria Regional.
- Costear un sistema no convencional con los valores unitarios oficiales: esos son para el
  convencional; el no convencional va con cotizaciones y aprobación de SENCICO.
