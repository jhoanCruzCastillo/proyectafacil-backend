# SECCIÓN N°01: DATOS GENERALES DEL PROYECTO

## Descripción de la sección

Esta sección contiene la información general del proyecto de inversión.

La sección está organizada en las siguientes subsecciones:

- 1.01 Institucionalidad
- 1.02 Responsabilidad funcional y tipología del proyecto de inversión
- 1.03 Nombre del proyecto de inversión
- 1.04 Alineamiento y contribución al cierre de una brecha prioritaria

La información debe obtenerse de los documentos proporcionados por el cliente.

Para cada campo, identifica el nodo correspondiente mediante su nombre y
ubicación dentro de la sección y subsección. El identificador numérico del
campo se proporciona como referencia para relacionarlo con el JSON.

No inventes información que no esté respaldada por los documentos del cliente.

El instructivo establece que el Módulo 1 tiene como objetivo definir la institucionalidad, responsabilidad funcional, nombre del proyecto y su alineamiento y contribución al cierre de brecha; este módulo corresponde a la Sección 1 de la FTE.

## 1.01 Institucionalidad

Esta subsección contiene la información institucional de los órganos que
participan en las fases de Formulación y Evaluación y de Ejecución del ciclo
de inversión.

Se divide en:

- Unidad Formuladora (UF)
- Unidad Ejecutora de Inversiones (UEI)

El instructivo indica que para la UF se debe identificar el nombre de la UF registrada en el Banco de Inversiones, su nivel de gobierno, la entidad a la que pertenece y el responsable del órgano. Para la UEI se debe identificar igualmente su nombre, nivel de gobierno, entidad y responsable.

### Campo 1.01.01 — Nivel de gobierno :

**Representa:**

El nivel de gobierno al que pertenece la Unidad Formuladora (UF).

**Tipo de información:**

Texto corto.

**Valores permitidos:**

- Gobierno Nacional
- Gobierno Regional
- Gobierno Local

**Regla de llenado:**

Identifica en los documentos del cliente el nivel de gobierno al que
pertenece la Unidad Formuladora.

Utiliza únicamente uno de los valores permitidos por el campo.

No infieras el nivel de gobierno únicamente a partir del nombre de la
persona responsable. Debe existir información que permita determinar
la institución o nivel de gobierno correspondiente.

**Ejemplo de llenado:**

Gobierno Nacional

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.01.01",
  "nombre": "Nivel de gobierno :",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "etiquetas": [
    "Gobierno Nacional",
    "Gobierno Regional",
    "Gobierno Local"
  ],
  "captura": {
    "columna": "H",
    "fila": 8,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "Gobierno Nacional"
}
```

El JSON de estructura define exactamente esas tres opciones para 1.01.01.

### Campo 1.01.02 — Entidad :

**Representa:**

La entidad a la que pertenece la Unidad Formuladora (UF).

**Tipo de información:**

Texto corto.

**Regla de llenado:**

Identifica en los documentos del cliente la entidad a la que pertenece
la Unidad Formuladora.

Debe utilizarse el nombre de la entidad tal como aparece en la
documentación disponible.

**Ejemplo de llenado:**

Programa Nacional Cuna Más

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.01.02",
  "nombre": "Entidad :",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 10,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "Programa Nacional Cuna Más"
}
```

### Campo 1.01.03 — Nombre de la UF :

**Representa:**

El nombre de la Unidad Formuladora (UF) responsable de la formulación
y evaluación del proyecto.

**Regla de llenado:**

Identifica el nombre de la UF en los documentos del cliente.

Cuando exista información sobre el registro o denominación oficial de
la UF, utiliza dicha denominación.

No sustituyas el nombre de la UF por el nombre de la entidad a la que
pertenece.

**Ejemplo de llenado:**

Unidad Formuladora Programa Nacional Cuna Más

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.01.03",
  "nombre": "Nombre de la UF :",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 12,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "Unidad Formuladora Programa Nacional Cuna Más"
}
```

### Campo 1.01.04 — Responsable de la UF :

**Representa:**

La persona responsable de la Unidad Formuladora (UF).

**Regla de llenado:**

Identifica en los documentos del cliente a la persona responsable de
la UF.

Utiliza el nombre encontrado en la documentación.

No inventes nombres cuando la documentación no permita identificar
al responsable.

**Ejemplo de llenado:**

Juan Perez

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.01.04",
  "nombre": "Responsable de la UF :",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 14,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "Juan Perez"
}
```

### Campo 1.01.05 — Nivel de gobierno :

**Representa:**

El nivel de gobierno al que pertenece la Unidad Ejecutora de
Inversiones (UEI).

**Tipo de información:**

Texto corto.

**Valores permitidos:**

- Gobierno Nacional
- Gobierno Regional
- Gobierno Local

**Regla de llenado:**

Identifica en los documentos del cliente el nivel de gobierno al que
pertenece la UEI.

Utiliza únicamente uno de los valores permitidos.

**Ejemplo de llenado:**

Gobierno Nacional

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.01.05",
  "nombre": "Nivel de gobierno :",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "etiquetas": [
    "Gobierno Nacional",
    "Gobierno Regional",
    "Gobierno Local"
  ],
  "captura": {
    "columna": "H",
    "fila": 18,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "Gobierno Nacional"
}
```

### Campo 1.01.06 — Entidad :

**Representa:**

La entidad a la que pertenece la Unidad Ejecutora de Inversiones
(UEI).

**Regla de llenado:**

Identifica la entidad correspondiente a la UEI en los documentos
del cliente.

**Ejemplo de llenado:**

Programa Nacional Cuna Más

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.01.06",
  "nombre": "Entidad :",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 20,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "Programa Nacional Cuna Más"
}
```

### Campo 1.01.07 — Nombre de la UEI :

**Representa:**

El nombre de la Unidad Ejecutora de Inversiones (UEI) propuesta como
responsable de la fase de ejecución del proyecto.

**Regla de llenado:**

Identifica el nombre de la UEI en los documentos del cliente.

La UEI debe corresponder al órgano responsable de la ejecución del
proyecto.

**Ejemplo de llenado:**

UEI Cuna Más

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.01.07",
  "nombre": "Nombre de la UEI :",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 22,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "UEI Cuna Más"
}
```

### Campo 1.01.08 — Responsable de la UEI :

**Representa:**

La persona responsable del órgano correspondiente a la Unidad
Ejecutora de Inversiones (UEI).

**Regla de llenado:**

Identifica en los documentos del cliente a la persona responsable
de la UEI.

No inventes el nombre del responsable si no existe información
suficiente.

**Ejemplo de llenado:**

Carlos Perez

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.01.08",
  "nombre": "Responsable de la UEI :",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 24,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "Carlos Perez"
}
```

Los ocho campos de 1.01 están definidos de esa manera en la estructura JSON, y el JSON de ejemplo proporciona los valores de referencia anteriores.

## 1.02 Responsabilidad funcional y tipología del proyecto de inversión

Esta subsección contiene la responsabilidad funcional y la tipología
correspondiente al proyecto de inversión de CIAI.

Los campos son:

- Función
- División funcional
- Grupo funcional
- Sector responsable
- Tipología de proyecto

El instructivo define específicamente Función, División funcional, Grupo funcional, Sector responsable y Tipología de proyecto. Para el ejemplo del documento se utilizan, respectivamente, 23 Protección social, 051 Asistencia social, 0115 Protección de poblaciones en riesgo, Desarrollo e Inclusión Social y Centro Infantil de Atención Integral (CIAI).

### Campo 1.02.01 — Función

**Representa:**

La función correspondiente al proyecto dentro de la clasificación
funcional.

**Regla de llenado:**

Identifica la función correspondiente al proyecto según la
documentación y clasificación aplicable.

Para este formato, el ejemplo del instructivo corresponde a:

23 Protección social

**Ejemplo de llenado:**

23 PROTECCIÓN SOCIAL

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.02.01",
  "nombre": "Función",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 29,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "23 PROTECCIÓN SOCIAL"
}
```

### Campo 1.02.02 — División funcional

**Representa:**

La división funcional correspondiente al proyecto.

El instructivo describe la División funcional 051 como Asistencia
social, relacionada con acciones orientadas al desarrollo social,
amparo, asistencia, desarrollo de capacidades sociales y económicas
y promoción de la igualdad de oportunidades.

**Ejemplo de llenado:**

051 ASISTENCIA SOCIAL

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.02.02",
  "nombre": "División funcional",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 31,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "051 ASISTENCIA SOCIAL"
}
```

### Campo 1.02.03 — Grupo funcional

**Representa:**

El grupo funcional correspondiente al proyecto.

Para este tipo de proyecto, el instructivo utiliza como ejemplo
0115 Protección de poblaciones en riesgo.

**Ejemplo de llenado:**

0115 PROTECCIÓN DE POBLACIONES EN RIESGO

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.02.03",
  "nombre": "Grupo funcional",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 33,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "0115 PROTECCIÓN DE POBLACIONES EN RIESGO"
}
```

El instructivo describe este grupo funcional como las acciones orientadas a proteger a poblaciones en riesgo, principalmente población vulnerable.

### Campo 1.02.04 — Sector responsable

**Representa:**

El sector responsable de la intervención.

**Ejemplo de llenado:**

DESARROLLO E INCLUSION SOCIAL

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.02.04",
  "nombre": "Sector responsable",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 35,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "DESARROLLO E INCLUSION SOCIAL"
}
```

### Campo 1.02.05 — Tipología de proyecto

**Representa:**

La tipología del proyecto de inversión.

Para esta FTE corresponde a proyectos de la tipología
Centro Infantil de Atención Integral (CIAI).

**Ejemplo de llenado:**

CENTRO INFANTIL DE ATENCIÓN INTEGRAL

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.02.05",
  "nombre": "Tipología de proyecto",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "H",
    "fila": 37,
    "abarca_columnas": 13,
    "abarca_filas": 1
  },
  "valor": "CENTRO INFANTIL DE ATENCIÓN INTEGRAL"
}
```

La estructura y el ejemplo JSON confirman los cinco campos y sus valores de referencia.

## 1.03 Nombre del proyecto de inversión

El nombre del proyecto se construye a partir de tres elementos:

1. Naturaleza de intervención
2. Objeto de intervención
3. Localización

La naturaleza de intervención es el único campo editable de esta
subsección en la estructura JSON actual.

Los campos Objeto de intervención y Localización están definidos
actualmente como campos calculados y no editables.

El instructivo explica que la naturaleza depende del objetivo del proyecto y contempla Mejoramiento, Ampliación, Recuperación y, cuando corresponda, Mejoramiento y ampliación. También define el objeto como el servicio sobre el que interviene el proyecto y la localización como el lugar donde se ubicará el CIAI.

### Campo 1.03.01 — Naturaleza de intervención

**Representa:**

El tipo de intervención que se realizará mediante el proyecto.

**Valores permitidos:**

- Mejoramiento
- Ampliación
- Mejoramiento y ampliación
- Recuperación

**Reglas de interpretación:**

**Mejoramiento:**
Utilizar cuando el proyecto busca mejorar la calidad del Servicio
de Cuidado Diurno que se brinda en un CIAI existente.

**Ampliación:**
Utilizar cuando el proyecto busca ampliar la cobertura del Servicio
de Cuidado Diurno en un CIAI existente.

**Recuperación:**
Utilizar cuando el proyecto busca recuperar la capacidad de prestación
del Servicio de Cuidado Diurno en un CIAI existente cuyos factores de
producción hayan colapsado, sido dañados o destruidos total o
parcialmente.

**Mejoramiento y ampliación:**
Puede utilizarse cuando corresponda una intervención que combine
ambas naturalezas.

**Ejemplo de llenado:**

Mejoramiento

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.03.01",
  "nombre": "Naturaleza de intervención",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "etiquetas": [
    "Mejoramiento",
    "Ampliación",
    "Mejoramiento y ampliación",
    "Recuperación"
  ],
  "captura": {
    "columna": "B",
    "fila": 44,
    "abarca_columnas": 3,
    "abarca_filas": 1
  },
  "valor": "Mejoramiento"
}
```

### Campo 1.03.02 — Objeto de intervención

**Representa:**

El servicio sobre el que intervendrá el proyecto y el nombre de la
Unidad Productora (UP).

Para esta FTE, el instructivo establece que el objeto de intervención
corresponde al Servicio de Cuidado Diurno en el CIAI.

**Tipo de nodo:**

Calculado.

**Editable:**

No.

**Regla de llenado:**

Este campo no debe ser tratado como un campo de captura manual.

Su contenido debe derivarse según la lógica definida para la
estructura de la ficha y la información relacionada con el proyecto.

**Ejemplo de llenado:**

del Servicio de Cuidado Infantil en el Centro Infantil de Atención
Integral (CIAI)

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.03.02",
  "nombre": "Objeto de intervención",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "F",
    "fila": 44,
    "abarca_columnas": 4,
    "abarca_filas": 1
  },
  "valor": "del Servicio de Cuidado Infantil en el Centro Infantil de Atención Integral (CIAI)"
}
```

### Campo 1.03.03 — Localización

**Representa:**

El lugar donde se ubicará el CIAI.

Debe incluir:

- localidad o centro poblado
- distrito
- provincia
- departamento

**Tipo de nodo:**

Calculado.

**Editable:**

No.

**Regla de llenado:**

Este campo no debe ser tratado como un campo de captura manual.

Su contenido debe construirse a partir de la información de
localización disponible para el proyecto.

**Ejemplo de llenado:**

en la localidad de San Antonio, distrito de San Sebastian,
provincia de Cusco, departamento de Cusco

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.03.03",
  "nombre": "Localización",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "K",
    "fila": 44,
    "abarca_columnas": 10,
    "abarca_filas": 1
  },
  "valor": "en la localidad de San Antonio, distrito de San Sebastian, provincia de Cusco, departamento de Cusco"
}
```

La estructura actual confirma que 1.03.02 y 1.03.03 son calculado y editable: false; el JSON de ejemplo muestra los valores correspondientes.

## 1.04 Alineamiento y contribución al cierre de una brecha prioritaria

Esta subsección contiene la información relacionada con el servicio
público y la brecha prioritaria a cuyo cierre contribuye el proyecto.

Para esta FTE se considera la brecha de calidad del CIAI.

La brecha de calidad corresponde al porcentaje de centros infantiles
de atención integral que brindan el servicio de cuidado integral en
condición inadecuada (PCIAICI).

Los campos 1.04.01 a 1.04.05 son campos calculados y no editables
en la estructura JSON actual.

Los campos 1.04.06 y 1.04.07 son campos editables de tipo decimal.

El instructivo distingue entre brecha de cobertura y brecha de calidad, y establece que para esta FTE se considera la brecha de calidad.

### Campo 1.04.01 — Servicios públicos con brecha identificada y priorizada

**Representa:**

El servicio público relacionado con la brecha identificada y priorizada.

**Tipo de nodo:**

Calculado.

**Editable:**

No.

**Ejemplo de llenado:**

Servicio de cuidado diurno

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.04.01",
  "nombre": "Servicios públicos con brecha identificada y priorizada",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "M",
    "fila": 49,
    "abarca_columnas": 8,
    "abarca_filas": 1
  },
  "valor": "Servicio de cuidado diurno"
}
```

### Campo 1.04.02 — Nombre del Indicador de brecha de acceso a servicios

**Representa:**

El nombre del indicador de brecha utilizado para representar la brecha
que atiende el proyecto.

Para esta FTE, corresponde al indicador asociado a la brecha de calidad
del CIAI.

**Ejemplo de llenado:**

Porcentaje de centros infantiles de atención integral que brindan el
servicio de cuidado integral en condición inadecuada. (PCIAICI)

**Tipo de nodo:**

Calculado.

**Editable:**

No.

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.04.02",
  "nombre": "Nombre del Indicador de brecha de acceso a servicios",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "B",
    "fila": 53,
    "abarca_columnas": 7,
    "abarca_filas": 1
  },
  "valor": "Porcentaje de centros infantiles de atención integral que brindan el servicio de cuidado integral en condición inadecuada. (PCIAICI)"
}
```

### Campo 1.04.03 — Unidad de medida

**Representa:**

La unidad utilizada para expresar el indicador de brecha.

**Ejemplo de llenado:**

Centro Infantil de Atención Integral (CIAI)

**Tipo de nodo:**

Calculado.

**Editable:**

No.

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.04.03",
  "nombre": "Unidad de medida",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "J",
    "fila": 53,
    "abarca_columnas": 3,
    "abarca_filas": 1
  },
  "valor": "Centro Infantil de Atención Integral (CIAI)"
}
```

### Campo 1.04.04 — Espacio geográfico

**Representa:**

El ámbito geográfico al que corresponde el valor del indicador
de brecha.

**Ejemplo de llenado:**

Distrito de San Sebastian

**Tipo de nodo:**

Calculado.

**Editable:**

No.

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.04.04",
  "nombre": "Espacio geográfico",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "N",
    "fila": 53,
    "abarca_columnas": 3,
    "abarca_filas": 1
  },
  "valor": "Distrito de San Sebastian"
}
```

### Campo 1.04.05 — Año

**Representa:**

El año correspondiente al valor registrado para el indicador
de brecha.

**Ejemplo de llenado:**

2026

**Tipo de nodo:**

Calculado.

**Editable:**

No.

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.04.05",
  "nombre": "Año",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "R",
    "fila": 53,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": "2026"
}
```

### Campo 1.04.06 — Valor

**Representa:**

El valor del indicador de brecha correspondiente al espacio
geográfico y año registrados.

**Tipo de información:**

Decimal.

**Decimales configurados:**

2.

**Regla de llenado:**

Extrae el valor del indicador de brecha desde la documentación
proporcionada por el cliente.

Conserva el significado del valor y su unidad de medida.

**Ejemplo de llenado:**

60%

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.04.06",
  "nombre": "Valor",
  "tipo_nodo": "campo",
  "tipo": "decimal",
  "editable": true,
  "decimales": 2,
  "captura": {
    "columna": "T",
    "fila": 53,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": "60%"
}
```

### Campo 1.04.07 — Contribución del Cierre de Brecha (Valor)

**Representa:**

El valor que expresa la contribución del proyecto al cierre de
la brecha prioritaria identificada.

**Tipo de información:**

Decimal.

**Decimales configurados:**

2.

**Regla de llenado:**

Identifica el valor correspondiente en la información proporcionada
para el proyecto y registra dicho valor.

No confundas este campo con el valor del indicador de brecha
registrado en el campo 1.04.06.

**Ejemplo de llenado:**

1

**Nodo JSON de ejemplo:**

```json
{
  "id": "1.04.07",
  "nombre": "Contribución del Cierre de Brecha (Valor)",
  "tipo_nodo": "campo",
  "tipo": "decimal",
  "editable": true,
  "decimales": 2,
  "captura": {
    "columna": "M",
    "fila": 55,
    "abarca_columnas": 8,
    "abarca_filas": 1
  },
  "valor": 1
}
```

El JSON de ejemplo confirma los valores de 1.04.01 a 1.04.07: servicio de cuidado diurno, indicador PCIAICI, CIAI como unidad de medida, distrito de San Sebastian, año 2026, valor 60% y contribución 1.

## Reglas generales para la Sección 1

1. Identifica primero la sección:
   "SECCIÓN N°01: DATOS GENERALES DEL PROYECTO"

2. Identifica después la subsección por su nombre:
   - "1.01 Institucionalidad"
   - "1.02 Responsabilidad funcional y tipología del proyecto de inversión"
   - "1.03 Nombre del proyecto de inversión"
   - "1.04 Alineamiento y contribución al cierre de una brecha prioritaria"

3. Dentro de la subsección, identifica el campo utilizando su nombre
   exactamente o mediante coincidencia semántica clara.

4. Utiliza el ID numérico del campo únicamente como referencia para
   localizar el nodo correspondiente dentro del JSON.

5. No modifiques la estructura del nodo.

6. No modifiques:
   - id
   - nombre
   - tipo_nodo
   - tipo
   - editable
   - etiquetas
   - captura
   - decimales

7. Cuando corresponda llenar un campo, modifica únicamente su propiedad
   "valor".

8. No inventes información que no pueda ser sustentada por los
   documentos proporcionados por el cliente.

9. Si un campo requiere información que no está disponible en los
   documentos, no inventes un valor.

10. Respeta las opciones definidas en "etiquetas" cuando el campo las
    tenga.

11. Los campos cuyo "editable" sea false no deben tratarse como campos
    de captura manual. Son campos calculados o derivados.

12. Los campos calculados deben conservar la lógica definida por la
    estructura del sistema y no deben ser tratados como campos
    editables por la IA.

13. Cuando exista evidencia suficiente para completar un campo,
    registra el valor en el nodo JSON correspondiente.

14. El objetivo es actualizar los valores del JSON, no escribir
    directamente sobre las celdas del archivo Excel.
