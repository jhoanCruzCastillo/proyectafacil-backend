# SECCIÓN N°09: COSTOS DEL PROYECTO - ALTERNATIVA 1

## Descripción de la sección

- **Qué representa:** SECCIÓN N°09: COSTOS DEL PROYECTO - ALTERNATIVA 1.
- **Objetivo (según instructivo):** Información no determinada por los archivos proporcionados.
- **Hoja Excel:** `CostosAlt1`
- **JSON `id` de sección:** `09`

### Contexto del instructivo (extracto)

Ejemplo: SECCIÓN 9 - COSTOS DEL PROYECTO En este punto se presentan las orientaciones para estimar los respectivos costos, a precios de mercado, de las alternativas técnicas planteadas. En el siguiente gráfico se presenta el contenido de este punto: 8.06 METAS FíSICAS DE LOS ACTIVOS QUE SE BUSCAN CREAR O INTERVENIR CON EL PROYECTO Infraestructura del CIAI cumple con los estándares de calidad Servicio de Cuidado Diurno Unidad de medida Cantidad Unidad de medida Cantidad Infraestructura 50.40 Sala 2.00 m2 100.80 Infraestructura 45.00 Sala 2.00 m2 90.00 Infraestructura 60.48 Ambiente 1.00 m2 60.48 Infraestructura 28.32 Ambiente m2 Infraestructura 28.92 Ambiente 1.00 m2 28.92 Infraestructura 12.50 Almacén 1.00 m2 12.50 Infraestructura Unidad 1.00 ml 200.00 Infraestructura Unidad ml o m3 Infraestructura 27.60 Unidad Equipos del CIAI cumplen con los estándares de calidad Cantidad Equipos 5.00 Mobiliario del CIAI cumple con los estándares de calidad Cantidad Mobiliario 30.00 Mobiliario 20.00 Suficientes capacidades organizacionales de los recursos humanos que operan en el CIAI Cantidad Intangibles 3.00 Construcción Activos Sala de cuidado diurno Sala de usos múltiples Ambiente de recreación activa Ambiente de servicios generales Ambiente de preparación y expendio de alimentos Dimensión físicaÁrea normativa mínima requerida por unidad física (m2) Tipo de factor productivo Unidad Física Componente 1 Acción sobre el activo Tipo de factor productivo Unidad Física Unidad de medida Mobiliario de sala de cuidado diurno Activos Componente 4 Acción sobre el activo Tipo de factor productivo Unidad Física Unidad de medida Activos Adquisición Adquisición Mobiliario de ambiente de preparación y expendio de alimentos- Cuna Más Número de mobiliario Unidad Física Unidad de medida Número de equiposEquipo de ambiente de preparación y expendio de alimentos - Cuna Más Activos Tipo de factor productivo Almacén Muro de contención Otros ambientes complementarios Cerco perimétrico Número de mobiliario Recursos humanos Componente 3 Construcción Construcción Construcción Componente 2 Construcción Construcción Reposición Acción sobre el activo Implementación de capacidades Número de eventos de capacitación Acción sobre el activo Gráfico 14. Contenido - Costos del proyecto Es importante precisar que se debe desarrollar este punto para cada una de las alternativas técnicas propuestas en el punto Resumen de las alternativas técnicas. 9.01. Costo de ejecución física de las acciones Los costos del proyecto se estiman, considerando las metas físicas definidas para cada componente y acciones de las alternativas técnicas planteadas y sus respectivos costos unitarios. También, se debe estimar el costo de implementación de las medidas de reducción de riesgo de desastre y mit igación ambiental. Esto constituye el costo directo del proyecto de inversión de CIAI. Además, se deben considerar los siguientes costos indirectos: Gestión del proyecto 17, Expediente técnico o documento equivalente, Supervisión, Liquidación, Gastos general es. El equipo responsable de la formulación del proyecto debe incluir, en el Anexo F Detalle del costo de inversión de las alternativas técnicas, el detalle de los costos indirectos. 17 La gestión del proyecto, durante la fase de Ejecución del proyecto, consiste en el planeamiento, organización, dirección, seguimiento y control para lograr una administración e implementación eficiente de las acciones destinadas a la formación o generación de la capacidad 

**Regla de ejemplos:** cada bloque de ejemplo es el **objeto `campo` completo** del `JSON EJEMPLO.json` correspondiente a esta sección/alternativa.

## Nota sobre alternativas (instructivo — SECCIÓN 9)

Esto **no es un error de modelado**. El instructivo indica que el punto de **Costos del proyecto** debe desarrollarse **para cada una de las alternativas técnicas** propuestas en el Resumen de las alternativas técnicas (sección de Análisis Técnico).

En el JSON existen tres hojas/nodos hermanos:

| JSON `id` | Hoja | Nombre |
|---|---|---|
| `09` | `CostosAlt1` | SECCIÓN N°09: COSTOS DEL PROYECTO - ALTERNATIVA 1 |
| `10` | `CostosAlt2` | SECCIÓN N°09: COSTOS DEL PROYECTO - ALTERNATIVA 2 |
| `11` | `CostosAlt3` | SECCIÓN N°09: COSTOS DEL PROYECTO - ALTERNATIVA 3 |

Comparten la misma lógica de sección (Costos), con IDs de campos propios por alternativa. Completar cada alternativa con los costos correspondientes a esa opción técnica.

**Esta guía documenta la Alternativa 1** (JSON id `09`).

### Subsecciones / grupos

- `09.01` — 9.01 cOSTO DE EJECUCIÓN Física de las acciones
- `09.02` — 9.02 COstos de reinversión
- `09.03` — 9.03 cOSTOS DE OPERACIÓN Y MANTENIMIENTO CON Y SIN PROYECTO
- `09.04` — 9.04 Cronograma de inversión de metas financieras
- `09.05` — 9.05 Cronograma de metas físicas

---

# 9.01 cOSTO DE EJECUCIÓN Física de las acciones

---

## Campo 09.01.1 — Componente 1.1

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `721b00a0-dba7-4330-a86c-cc1833d6d225` | Acción sobre el activo | texto_corto |
| `64ef2476-a003-4617-8cf9-8a50a73f267a` | Tipo de factor productivo | texto_corto |
| `d6d094ef-53a5-4769-bf1d-79a4113119e9` | Activos | texto_corto |
| `560d3c07-3f63-4a45-b1a1-84ccc8c32756` | Unidad de medida | texto_corto |
| `ef994295-781c-467e-9b34-0707503382b5` | Cantidad | texto_corto |
| `10165d26-b367-4aa4-b853-537b26938ad4` | Unidad de medida | texto_corto |
| `b6cda42e-28e2-4672-a050-8c8b5ea5f947` | Cantidad | texto_corto |
| `fa3c6abc-78fa-4fc3-a630-23b57428723d` | Costo unitario | texto_corto |
| `bb186ad8-9f08-4b60-995b-9ff3b9e067cc` | Costo total | texto_corto |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Dimensión física",
    "hijos": [
      "b6cda42e-28e2-4672-a050-8c8b5ea5f947",
      "10165d26-b367-4aa4-b853-537b26938ad4"
    ]
  },
  {
    "titulo": "Unidad Física",
    "hijos": [
      "ef994295-781c-467e-9b34-0707503382b5",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.01.1",
  "nombre": "Componente 1.1",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 11,
    "filas_base": 3,
    "columnas": [
      {
        "id": "721b00a0-dba7-4330-a86c-cc1833d6d225",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "64ef2476-a003-4617-8cf9-8a50a73f267a",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "d6d094ef-53a5-4769-bf1d-79a4113119e9",
        "columna": "E",
        "abarca_columnas": 5
      },
      {
        "id": "560d3c07-3f63-4a45-b1a1-84ccc8c32756",
        "columna": "J",
        "abarca_columnas": 1
      },
      {
        "id": "ef994295-781c-467e-9b34-0707503382b5",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "10165d26-b367-4aa4-b853-537b26938ad4",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "b6cda42e-28e2-4672-a050-8c8b5ea5f947",
        "columna": "M",
        "abarca_columnas": 1
      },
      {
        "id": "fa3c6abc-78fa-4fc3-a630-23b57428723d",
        "columna": "N",
        "abarca_columnas": 1
      },
      {
        "id": "bb186ad8-9f08-4b60-995b-9ff3b9e067cc",
        "columna": "O",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Dimensión física",
      "hijos": [
        "b6cda42e-28e2-4672-a050-8c8b5ea5f947",
        "10165d26-b367-4aa4-b853-537b26938ad4"
      ]
    },
    {
      "titulo": "Unidad Física",
      "hijos": [
        "ef994295-781c-467e-9b34-0707503382b5",
        "560d3c07-3f63-4a45-b1a1-84ccc8c32756"
      ]
    }
  ],
  "columnas": [
    {
      "id": "721b00a0-dba7-4330-a86c-cc1833d6d225",
      "nombre": "Acción sobre el activo",
      "tipo": "texto_corto"
    },
    {
      "id": "64ef2476-a003-4617-8cf9-8a50a73f267a",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "d6d094ef-53a5-4769-bf1d-79a4113119e9",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "560d3c07-3f63-4a45-b1a1-84ccc8c32756",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "ef994295-781c-467e-9b34-0707503382b5",
      "nombre": "Cantidad",
      "tipo": "texto_corto"
    },
    {
      "id": "10165d26-b367-4aa4-b853-537b26938ad4",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "b6cda42e-28e2-4672-a050-8c8b5ea5f947",
      "nombre": "Cantidad",
      "tipo": "texto_corto"
    },
    {
      "id": "fa3c6abc-78fa-4fc3-a630-23b57428723d",
      "nombre": "Costo unitario",
      "tipo": "texto_corto"
    },
    {
      "id": "bb186ad8-9f08-4b60-995b-9ff3b9e067cc",
      "nombre": "Costo total",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "721b00a0-dba7-4330-a86c-cc1833d6d225": "",
      "64ef2476-a003-4617-8cf9-8a50a73f267a": "",
      "d6d094ef-53a5-4769-bf1d-79a4113119e9": "",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756": "",
      "ef994295-781c-467e-9b34-0707503382b5": "",
      "10165d26-b367-4aa4-b853-537b26938ad4": "",
      "b6cda42e-28e2-4672-a050-8c8b5ea5f947": "",
      "fa3c6abc-78fa-4fc3-a630-23b57428723d": "3650",
      "bb186ad8-9f08-4b60-995b-9ff3b9e067cc": ""
    },
    {
      "721b00a0-dba7-4330-a86c-cc1833d6d225": "",
      "64ef2476-a003-4617-8cf9-8a50a73f267a": "",
      "d6d094ef-53a5-4769-bf1d-79a4113119e9": "",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756": "",
      "ef994295-781c-467e-9b34-0707503382b5": "",
      "10165d26-b367-4aa4-b853-537b26938ad4": "",
      "b6cda42e-28e2-4672-a050-8c8b5ea5f947": "",
      "fa3c6abc-78fa-4fc3-a630-23b57428723d": "3650",
      "bb186ad8-9f08-4b60-995b-9ff3b9e067cc": ""
    },
    {
      "721b00a0-dba7-4330-a86c-cc1833d6d225": "",
      "64ef2476-a003-4617-8cf9-8a50a73f267a": "",
      "d6d094ef-53a5-4769-bf1d-79a4113119e9": "",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756": "",
      "ef994295-781c-467e-9b34-0707503382b5": "",
      "10165d26-b367-4aa4-b853-537b26938ad4": "",
      "b6cda42e-28e2-4672-a050-8c8b5ea5f947": "",
      "fa3c6abc-78fa-4fc3-a630-23b57428723d": "3650",
      "bb186ad8-9f08-4b60-995b-9ff3b9e067cc": ""
    }
  ]
}
```

---

## Campo 09.01.2 — Componente 1.2

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `721b00a0-dba7-4330-a86c-cc1833d6d225` | Acción sobre el activo | texto_corto |
| `64ef2476-a003-4617-8cf9-8a50a73f267a` | Tipo de factor productivo | texto_corto |
| `d6d094ef-53a5-4769-bf1d-79a4113119e9` | Activos | texto_corto |
| `560d3c07-3f63-4a45-b1a1-84ccc8c32756` | Unidad de medida | texto_corto |
| `ef994295-781c-467e-9b34-0707503382b5` | Cantidad | texto_corto |
| `10165d26-b367-4aa4-b853-537b26938ad4` | Unidad de medida | texto_corto |
| `b6cda42e-28e2-4672-a050-8c8b5ea5f947` | Cantidad | texto_corto |
| `fa3c6abc-78fa-4fc3-a630-23b57428723d` | Costo unitario | texto_corto |
| `bb186ad8-9f08-4b60-995b-9ff3b9e067cc` | Costo total | texto_corto |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Dimensión física",
    "hijos": [
      "b6cda42e-28e2-4672-a050-8c8b5ea5f947",
      "10165d26-b367-4aa4-b853-537b26938ad4"
    ]
  },
  {
    "titulo": "Unidad Física",
    "hijos": [
      "ef994295-781c-467e-9b34-0707503382b5",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.01.2",
  "nombre": "Componente 1.2",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 15,
    "filas_base": 3,
    "columnas": [
      {
        "id": "721b00a0-dba7-4330-a86c-cc1833d6d225",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "64ef2476-a003-4617-8cf9-8a50a73f267a",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "d6d094ef-53a5-4769-bf1d-79a4113119e9",
        "columna": "E",
        "abarca_columnas": 5
      },
      {
        "id": "560d3c07-3f63-4a45-b1a1-84ccc8c32756",
        "columna": "J",
        "abarca_columnas": 1
      },
      {
        "id": "ef994295-781c-467e-9b34-0707503382b5",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "10165d26-b367-4aa4-b853-537b26938ad4",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "b6cda42e-28e2-4672-a050-8c8b5ea5f947",
        "columna": "M",
        "abarca_columnas": 1
      },
      {
        "id": "fa3c6abc-78fa-4fc3-a630-23b57428723d",
        "columna": "N",
        "abarca_columnas": 1
      },
      {
        "id": "bb186ad8-9f08-4b60-995b-9ff3b9e067cc",
        "columna": "O",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Dimensión física",
      "hijos": [
        "b6cda42e-28e2-4672-a050-8c8b5ea5f947",
        "10165d26-b367-4aa4-b853-537b26938ad4"
      ]
    },
    {
      "titulo": "Unidad Física",
      "hijos": [
        "ef994295-781c-467e-9b34-0707503382b5",
        "560d3c07-3f63-4a45-b1a1-84ccc8c32756"
      ]
    }
  ],
  "columnas": [
    {
      "id": "721b00a0-dba7-4330-a86c-cc1833d6d225",
      "nombre": "Acción sobre el activo",
      "tipo": "texto_corto"
    },
    {
      "id": "64ef2476-a003-4617-8cf9-8a50a73f267a",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "d6d094ef-53a5-4769-bf1d-79a4113119e9",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "560d3c07-3f63-4a45-b1a1-84ccc8c32756",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "ef994295-781c-467e-9b34-0707503382b5",
      "nombre": "Cantidad",
      "tipo": "texto_corto"
    },
    {
      "id": "10165d26-b367-4aa4-b853-537b26938ad4",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "b6cda42e-28e2-4672-a050-8c8b5ea5f947",
      "nombre": "Cantidad",
      "tipo": "texto_corto"
    },
    {
      "id": "fa3c6abc-78fa-4fc3-a630-23b57428723d",
      "nombre": "Costo unitario",
      "tipo": "texto_corto"
    },
    {
      "id": "bb186ad8-9f08-4b60-995b-9ff3b9e067cc",
      "nombre": "Costo total",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "721b00a0-dba7-4330-a86c-cc1833d6d225": "",
      "64ef2476-a003-4617-8cf9-8a50a73f267a": "",
      "d6d094ef-53a5-4769-bf1d-79a4113119e9": "",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756": "",
      "ef994295-781c-467e-9b34-0707503382b5": "",
      "10165d26-b367-4aa4-b853-537b26938ad4": "",
      "b6cda42e-28e2-4672-a050-8c8b5ea5f947": "",
      "fa3c6abc-78fa-4fc3-a630-23b57428723d": "3650",
      "bb186ad8-9f08-4b60-995b-9ff3b9e067cc": ""
    },
    {
      "721b00a0-dba7-4330-a86c-cc1833d6d225": "",
      "64ef2476-a003-4617-8cf9-8a50a73f267a": "",
      "d6d094ef-53a5-4769-bf1d-79a4113119e9": "",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756": "",
      "ef994295-781c-467e-9b34-0707503382b5": "",
      "10165d26-b367-4aa4-b853-537b26938ad4": "",
      "b6cda42e-28e2-4672-a050-8c8b5ea5f947": "",
      "fa3c6abc-78fa-4fc3-a630-23b57428723d": "3650",
      "bb186ad8-9f08-4b60-995b-9ff3b9e067cc": ""
    },
    {
      "721b00a0-dba7-4330-a86c-cc1833d6d225": "",
      "64ef2476-a003-4617-8cf9-8a50a73f267a": "",
      "d6d094ef-53a5-4769-bf1d-79a4113119e9": "",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756": "",
      "ef994295-781c-467e-9b34-0707503382b5": "",
      "10165d26-b367-4aa4-b853-537b26938ad4": "",
      "b6cda42e-28e2-4672-a050-8c8b5ea5f947": "",
      "fa3c6abc-78fa-4fc3-a630-23b57428723d": "550",
      "bb186ad8-9f08-4b60-995b-9ff3b9e067cc": ""
    }
  ]
}
```

---

## Campo 09.01.3 — Componente 2

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `721b00a0-dba7-4330-a86c-cc1833d6d225` | Acción sobre el activo | texto_corto |
| `64ef2476-a003-4617-8cf9-8a50a73f267a` | Tipo de factor productivo | texto_corto |
| `d6d094ef-53a5-4769-bf1d-79a4113119e9` | Activos | texto_corto |
| `560d3c07-3f63-4a45-b1a1-84ccc8c32756` | Unidad de medida | texto_corto |
| `ef994295-781c-467e-9b34-0707503382b5` | Cantidad | texto_corto |
| `bb186ad8-9f08-4b60-995b-9ff3b9e067cc` | Costo total | texto_corto |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Unidad Física",
    "hijos": [
      "ef994295-781c-467e-9b34-0707503382b5",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.01.3",
  "nombre": "Componente 2",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 25,
    "filas_base": 1,
    "columnas": [
      {
        "id": "721b00a0-dba7-4330-a86c-cc1833d6d225",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "64ef2476-a003-4617-8cf9-8a50a73f267a",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "d6d094ef-53a5-4769-bf1d-79a4113119e9",
        "columna": "E",
        "abarca_columnas": 5
      },
      {
        "id": "560d3c07-3f63-4a45-b1a1-84ccc8c32756",
        "columna": "J",
        "abarca_columnas": 2
      },
      {
        "id": "ef994295-781c-467e-9b34-0707503382b5",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "bb186ad8-9f08-4b60-995b-9ff3b9e067cc",
        "columna": "M",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Unidad Física",
      "hijos": [
        "ef994295-781c-467e-9b34-0707503382b5",
        "560d3c07-3f63-4a45-b1a1-84ccc8c32756"
      ]
    }
  ],
  "columnas": [
    {
      "id": "721b00a0-dba7-4330-a86c-cc1833d6d225",
      "nombre": "Acción sobre el activo",
      "tipo": "texto_corto"
    },
    {
      "id": "64ef2476-a003-4617-8cf9-8a50a73f267a",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "d6d094ef-53a5-4769-bf1d-79a4113119e9",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "560d3c07-3f63-4a45-b1a1-84ccc8c32756",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "ef994295-781c-467e-9b34-0707503382b5",
      "nombre": "Cantidad",
      "tipo": "texto_corto"
    },
    {
      "id": "bb186ad8-9f08-4b60-995b-9ff3b9e067cc",
      "nombre": "Costo total",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "721b00a0-dba7-4330-a86c-cc1833d6d225": "",
      "64ef2476-a003-4617-8cf9-8a50a73f267a": "",
      "d6d094ef-53a5-4769-bf1d-79a4113119e9": "",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756": "",
      "ef994295-781c-467e-9b34-0707503382b5": "",
      "bb186ad8-9f08-4b60-995b-9ff3b9e067cc": "25000"
    }
  ]
}
```

---

## Campo 09.01.4 — Componente 3

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `721b00a0-dba7-4330-a86c-cc1833d6d225` | Acción sobre el activo | texto_corto |
| `64ef2476-a003-4617-8cf9-8a50a73f267a` | Tipo de factor productivo | texto_corto |
| `d6d094ef-53a5-4769-bf1d-79a4113119e9` | Activos | texto_corto |
| `560d3c07-3f63-4a45-b1a1-84ccc8c32756` | Unidad de medida | texto_corto |
| `ef994295-781c-467e-9b34-0707503382b5` | Cantidad | texto_corto |
| `bb186ad8-9f08-4b60-995b-9ff3b9e067cc` | Costo total | texto_corto |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Unidad Física",
    "hijos": [
      "ef994295-781c-467e-9b34-0707503382b5",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.01.4",
  "nombre": "Componente 3",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 32,
    "filas_base": 2,
    "columnas": [
      {
        "id": "721b00a0-dba7-4330-a86c-cc1833d6d225",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "64ef2476-a003-4617-8cf9-8a50a73f267a",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "d6d094ef-53a5-4769-bf1d-79a4113119e9",
        "columna": "E",
        "abarca_columnas": 5
      },
      {
        "id": "560d3c07-3f63-4a45-b1a1-84ccc8c32756",
        "columna": "J",
        "abarca_columnas": 2
      },
      {
        "id": "ef994295-781c-467e-9b34-0707503382b5",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "bb186ad8-9f08-4b60-995b-9ff3b9e067cc",
        "columna": "M",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Unidad Física",
      "hijos": [
        "ef994295-781c-467e-9b34-0707503382b5",
        "560d3c07-3f63-4a45-b1a1-84ccc8c32756"
      ]
    }
  ],
  "columnas": [
    {
      "id": "721b00a0-dba7-4330-a86c-cc1833d6d225",
      "nombre": "Acción sobre el activo",
      "tipo": "texto_corto"
    },
    {
      "id": "64ef2476-a003-4617-8cf9-8a50a73f267a",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "d6d094ef-53a5-4769-bf1d-79a4113119e9",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "560d3c07-3f63-4a45-b1a1-84ccc8c32756",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "ef994295-781c-467e-9b34-0707503382b5",
      "nombre": "Cantidad",
      "tipo": "texto_corto"
    },
    {
      "id": "bb186ad8-9f08-4b60-995b-9ff3b9e067cc",
      "nombre": "Costo total",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "721b00a0-dba7-4330-a86c-cc1833d6d225": "",
      "64ef2476-a003-4617-8cf9-8a50a73f267a": "",
      "d6d094ef-53a5-4769-bf1d-79a4113119e9": "",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756": "",
      "ef994295-781c-467e-9b34-0707503382b5": "",
      "bb186ad8-9f08-4b60-995b-9ff3b9e067cc": "60000"
    },
    {
      "721b00a0-dba7-4330-a86c-cc1833d6d225": "",
      "64ef2476-a003-4617-8cf9-8a50a73f267a": "",
      "d6d094ef-53a5-4769-bf1d-79a4113119e9": "",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756": "",
      "ef994295-781c-467e-9b34-0707503382b5": "",
      "bb186ad8-9f08-4b60-995b-9ff3b9e067cc": "30000"
    }
  ]
}
```

---

## Campo 09.01.5 — Componente 4

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `721b00a0-dba7-4330-a86c-cc1833d6d225` | Acción sobre el activo | texto_corto |
| `64ef2476-a003-4617-8cf9-8a50a73f267a` | Tipo de factor productivo | texto_corto |
| `d6d094ef-53a5-4769-bf1d-79a4113119e9` | Activos | texto_corto |
| `560d3c07-3f63-4a45-b1a1-84ccc8c32756` | Unidad de medida | texto_corto |
| `ef994295-781c-467e-9b34-0707503382b5` | Cantidad | texto_corto |
| `bb186ad8-9f08-4b60-995b-9ff3b9e067cc` | Costo total | texto_corto |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Unidad Física",
    "hijos": [
      "ef994295-781c-467e-9b34-0707503382b5",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.01.5",
  "nombre": "Componente 4",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 40,
    "filas_base": 1,
    "columnas": [
      {
        "id": "721b00a0-dba7-4330-a86c-cc1833d6d225",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "64ef2476-a003-4617-8cf9-8a50a73f267a",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "d6d094ef-53a5-4769-bf1d-79a4113119e9",
        "columna": "E",
        "abarca_columnas": 5
      },
      {
        "id": "560d3c07-3f63-4a45-b1a1-84ccc8c32756",
        "columna": "J",
        "abarca_columnas": 2
      },
      {
        "id": "ef994295-781c-467e-9b34-0707503382b5",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "bb186ad8-9f08-4b60-995b-9ff3b9e067cc",
        "columna": "M",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Unidad Física",
      "hijos": [
        "ef994295-781c-467e-9b34-0707503382b5",
        "560d3c07-3f63-4a45-b1a1-84ccc8c32756"
      ]
    }
  ],
  "columnas": [
    {
      "id": "721b00a0-dba7-4330-a86c-cc1833d6d225",
      "nombre": "Acción sobre el activo",
      "tipo": "texto_corto"
    },
    {
      "id": "64ef2476-a003-4617-8cf9-8a50a73f267a",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "d6d094ef-53a5-4769-bf1d-79a4113119e9",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "560d3c07-3f63-4a45-b1a1-84ccc8c32756",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "ef994295-781c-467e-9b34-0707503382b5",
      "nombre": "Cantidad",
      "tipo": "texto_corto"
    },
    {
      "id": "bb186ad8-9f08-4b60-995b-9ff3b9e067cc",
      "nombre": "Costo total",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "721b00a0-dba7-4330-a86c-cc1833d6d225": "",
      "64ef2476-a003-4617-8cf9-8a50a73f267a": "",
      "d6d094ef-53a5-4769-bf1d-79a4113119e9": "",
      "560d3c07-3f63-4a45-b1a1-84ccc8c32756": "",
      "ef994295-781c-467e-9b34-0707503382b5": "",
      "bb186ad8-9f08-4b60-995b-9ff3b9e067cc": "18000"
    }
  ]
}
```

---

## Campo 09.01.6 — Costos indirectos

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `12ba6a6d-a723-4b6d-ac6e-8172e6d18c58` | Otros costos | texto_corto |
| `ad5b811c-34c8-4b85-8e8a-4c87894de596` | Costos a precios de mercado | texto_corto |
| `734fd8bc-a306-4532-bfb4-d401f8866507` | % respecto al costo directo | texto_corto |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.01.6",
  "nombre": "Costos indirectos",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 70,
    "filas_base": 5,
    "columnas": [
      {
        "id": "12ba6a6d-a723-4b6d-ac6e-8172e6d18c58",
        "columna": "B",
        "abarca_columnas": 3
      },
      {
        "id": "ad5b811c-34c8-4b85-8e8a-4c87894de596",
        "columna": "E",
        "abarca_columnas": 2
      },
      {
        "id": "734fd8bc-a306-4532-bfb4-d401f8866507",
        "columna": "G",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "12ba6a6d-a723-4b6d-ac6e-8172e6d18c58",
      "nombre": "Otros costos",
      "tipo": "texto_corto"
    },
    {
      "id": "ad5b811c-34c8-4b85-8e8a-4c87894de596",
      "nombre": "Costos a precios de mercado",
      "tipo": "texto_corto"
    },
    {
      "id": "734fd8bc-a306-4532-bfb4-d401f8866507",
      "nombre": "% respecto al costo directo",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "12ba6a6d-a723-4b6d-ac6e-8172e6d18c58": "Gestión del proyecto",
      "ad5b811c-34c8-4b85-8e8a-4c87894de596": "65000",
      "734fd8bc-a306-4532-bfb4-d401f8866507": ""
    },
    {
      "12ba6a6d-a723-4b6d-ac6e-8172e6d18c58": "Expediente técnico o documento equivalente",
      "ad5b811c-34c8-4b85-8e8a-4c87894de596": "89700",
      "734fd8bc-a306-4532-bfb4-d401f8866507": ""
    },
    {
      "12ba6a6d-a723-4b6d-ac6e-8172e6d18c58": "Supervisión",
      "ad5b811c-34c8-4b85-8e8a-4c87894de596": "50000",
      "734fd8bc-a306-4532-bfb4-d401f8866507": ""
    },
    {
      "12ba6a6d-a723-4b6d-ac6e-8172e6d18c58": "Liquidación",
      "ad5b811c-34c8-4b85-8e8a-4c87894de596": "30000",
      "734fd8bc-a306-4532-bfb4-d401f8866507": ""
    },
    {
      "12ba6a6d-a723-4b6d-ac6e-8172e6d18c58": "Gastos generales",
      "ad5b811c-34c8-4b85-8e8a-4c87894de596": "150000",
      "734fd8bc-a306-4532-bfb4-d401f8866507": ""
    }
  ]
}
```

---

## Campo 09.01.7 — Subtotal de otros costos de inversión

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.01.7",
  "nombre": "Subtotal de otros costos de inversión",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "E",
    "fila": 76,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": "384700"
}
```

---

## Campo 09.01.8 — Costo Total de inversión

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.01.8",
  "nombre": "Costo Total de inversión",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "E",
    "fila": 78,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": "1696055.00"
}
```

---

# 9.02 COstos de reinversión

---

## Campo 09.02.1 — Costos de reinversión

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `e19c1178-6152-4a97-8271-ec3727291cfd` | Activos | texto_corto |
| `95a64dcb-f127-4f26-a3ef-fc58543dcc4a` | UM | texto_corto |
| `3aef2f2c-2640-4466-94dc-d4155410fea0` | Cantidad | texto_corto |
| `05f978d0-ce75-4366-b947-b2ae4c674833` | y1 | texto_corto |
| `3c1b8d5b-0a6f-4736-91ef-618c92bda9a6` | y2 | texto_corto |
| `fafd6252-e52e-4d22-a15a-847f03e3623d` | y3 | texto_corto |
| `017ef6e2-4c44-42e5-bd6c-8e6ad995883e` | y4 | texto_corto |
| `e40e66bd-2006-4b92-bd40-77a865fa8aaf` | y5 | texto_corto |
| `95d9642d-1477-4869-95c2-66417124d8a1` | y6 | texto_corto |
| `639245d1-382a-4670-a87d-7b3847e729d2` | y7 | texto_corto |
| `2b3852a2-703a-489b-a4f8-ec6106721f25` | y8 | texto_corto |
| `1987b3a1-9c59-4571-a470-733e9cfd6776` | y9 | texto_corto |
| `c4f2ca2c-b8b9-443a-9d09-0176b8d17b28` | y10 | texto_corto |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "AÑOS (Soles)",
    "hijos": [
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28",
      "1987b3a1-9c59-4571-a470-733e9cfd6776",
      "2b3852a2-703a-489b-a4f8-ec6106721f25",
      "639245d1-382a-4670-a87d-7b3847e729d2",
      "95d9642d-1477-4869-95c2-66417124d8a1",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e",
      "fafd6252-e52e-4d22-a15a-847f03e3623d",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6",
      "05f978d0-ce75-4366-b947-b2ae4c674833"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.02.1",
  "nombre": "Costos de reinversión",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 84,
    "filas_base": 10,
    "columnas": [
      {
        "id": "e19c1178-6152-4a97-8271-ec3727291cfd",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "95a64dcb-f127-4f26-a3ef-fc58543dcc4a",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "3aef2f2c-2640-4466-94dc-d4155410fea0",
        "columna": "E",
        "abarca_columnas": 1
      },
      {
        "id": "05f978d0-ce75-4366-b947-b2ae4c674833",
        "columna": "F",
        "abarca_columnas": 1
      },
      {
        "id": "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "fafd6252-e52e-4d22-a15a-847f03e3623d",
        "columna": "H",
        "abarca_columnas": 1
      },
      {
        "id": "017ef6e2-4c44-42e5-bd6c-8e6ad995883e",
        "columna": "I",
        "abarca_columnas": 1
      },
      {
        "id": "e40e66bd-2006-4b92-bd40-77a865fa8aaf",
        "columna": "J",
        "abarca_columnas": 1
      },
      {
        "id": "95d9642d-1477-4869-95c2-66417124d8a1",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "639245d1-382a-4670-a87d-7b3847e729d2",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "2b3852a2-703a-489b-a4f8-ec6106721f25",
        "columna": "M",
        "abarca_columnas": 1
      },
      {
        "id": "1987b3a1-9c59-4571-a470-733e9cfd6776",
        "columna": "N",
        "abarca_columnas": 1
      },
      {
        "id": "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28",
        "columna": "O",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "AÑOS (Soles)",
      "hijos": [
        "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28",
        "1987b3a1-9c59-4571-a470-733e9cfd6776",
        "2b3852a2-703a-489b-a4f8-ec6106721f25",
        "639245d1-382a-4670-a87d-7b3847e729d2",
        "95d9642d-1477-4869-95c2-66417124d8a1",
        "e40e66bd-2006-4b92-bd40-77a865fa8aaf",
        "017ef6e2-4c44-42e5-bd6c-8e6ad995883e",
        "fafd6252-e52e-4d22-a15a-847f03e3623d",
        "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6",
        "05f978d0-ce75-4366-b947-b2ae4c674833"
      ]
    }
  ],
  "columnas": [
    {
      "id": "e19c1178-6152-4a97-8271-ec3727291cfd",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "95a64dcb-f127-4f26-a3ef-fc58543dcc4a",
      "nombre": "UM",
      "tipo": "texto_corto"
    },
    {
      "id": "3aef2f2c-2640-4466-94dc-d4155410fea0",
      "nombre": "Cantidad",
      "tipo": "texto_corto"
    },
    {
      "id": "05f978d0-ce75-4366-b947-b2ae4c674833",
      "nombre": "y1",
      "tipo": "texto_corto"
    },
    {
      "id": "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6",
      "nombre": "y2",
      "tipo": "texto_corto"
    },
    {
      "id": "fafd6252-e52e-4d22-a15a-847f03e3623d",
      "nombre": "y3",
      "tipo": "texto_corto"
    },
    {
      "id": "017ef6e2-4c44-42e5-bd6c-8e6ad995883e",
      "nombre": "y4",
      "tipo": "texto_corto"
    },
    {
      "id": "e40e66bd-2006-4b92-bd40-77a865fa8aaf",
      "nombre": "y5",
      "tipo": "texto_corto"
    },
    {
      "id": "95d9642d-1477-4869-95c2-66417124d8a1",
      "nombre": "y6",
      "tipo": "texto_corto"
    },
    {
      "id": "639245d1-382a-4670-a87d-7b3847e729d2",
      "nombre": "y7",
      "tipo": "texto_corto"
    },
    {
      "id": "2b3852a2-703a-489b-a4f8-ec6106721f25",
      "nombre": "y8",
      "tipo": "texto_corto"
    },
    {
      "id": "1987b3a1-9c59-4571-a470-733e9cfd6776",
      "nombre": "y9",
      "tipo": "texto_corto"
    },
    {
      "id": "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28",
      "nombre": "y10",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "e19c1178-6152-4a97-8271-ec3727291cfd": "",
      "95a64dcb-f127-4f26-a3ef-fc58543dcc4a": "",
      "3aef2f2c-2640-4466-94dc-d4155410fea0": "",
      "05f978d0-ce75-4366-b947-b2ae4c674833": "",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6": "",
      "fafd6252-e52e-4d22-a15a-847f03e3623d": "",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e": "",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf": "",
      "95d9642d-1477-4869-95c2-66417124d8a1": "",
      "639245d1-382a-4670-a87d-7b3847e729d2": "",
      "2b3852a2-703a-489b-a4f8-ec6106721f25": "",
      "1987b3a1-9c59-4571-a470-733e9cfd6776": "",
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28": ""
    },
    {
      "e19c1178-6152-4a97-8271-ec3727291cfd": "",
      "95a64dcb-f127-4f26-a3ef-fc58543dcc4a": "",
      "3aef2f2c-2640-4466-94dc-d4155410fea0": "",
      "05f978d0-ce75-4366-b947-b2ae4c674833": "",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6": "",
      "fafd6252-e52e-4d22-a15a-847f03e3623d": "",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e": "",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf": "",
      "95d9642d-1477-4869-95c2-66417124d8a1": "",
      "639245d1-382a-4670-a87d-7b3847e729d2": "",
      "2b3852a2-703a-489b-a4f8-ec6106721f25": "",
      "1987b3a1-9c59-4571-a470-733e9cfd6776": "",
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28": ""
    },
    {
      "e19c1178-6152-4a97-8271-ec3727291cfd": "Mobiliario de sala de cuidado diurno",
      "95a64dcb-f127-4f26-a3ef-fc58543dcc4a": "Número de mobiliario",
      "3aef2f2c-2640-4466-94dc-d4155410fea0": "30",
      "05f978d0-ce75-4366-b947-b2ae4c674833": "",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6": "",
      "fafd6252-e52e-4d22-a15a-847f03e3623d": "",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e": "",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf": "",
      "95d9642d-1477-4869-95c2-66417124d8a1": "60000",
      "639245d1-382a-4670-a87d-7b3847e729d2": "",
      "2b3852a2-703a-489b-a4f8-ec6106721f25": "",
      "1987b3a1-9c59-4571-a470-733e9cfd6776": "",
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28": ""
    },
    {
      "e19c1178-6152-4a97-8271-ec3727291cfd": "",
      "95a64dcb-f127-4f26-a3ef-fc58543dcc4a": "",
      "3aef2f2c-2640-4466-94dc-d4155410fea0": "",
      "05f978d0-ce75-4366-b947-b2ae4c674833": "",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6": "",
      "fafd6252-e52e-4d22-a15a-847f03e3623d": "",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e": "",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf": "",
      "95d9642d-1477-4869-95c2-66417124d8a1": "",
      "639245d1-382a-4670-a87d-7b3847e729d2": "",
      "2b3852a2-703a-489b-a4f8-ec6106721f25": "",
      "1987b3a1-9c59-4571-a470-733e9cfd6776": "",
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28": ""
    },
    {
      "e19c1178-6152-4a97-8271-ec3727291cfd": "",
      "95a64dcb-f127-4f26-a3ef-fc58543dcc4a": "",
      "3aef2f2c-2640-4466-94dc-d4155410fea0": "",
      "05f978d0-ce75-4366-b947-b2ae4c674833": "",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6": "",
      "fafd6252-e52e-4d22-a15a-847f03e3623d": "",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e": "",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf": "",
      "95d9642d-1477-4869-95c2-66417124d8a1": "",
      "639245d1-382a-4670-a87d-7b3847e729d2": "",
      "2b3852a2-703a-489b-a4f8-ec6106721f25": "",
      "1987b3a1-9c59-4571-a470-733e9cfd6776": "",
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28": ""
    },
    {
      "e19c1178-6152-4a97-8271-ec3727291cfd": "",
      "95a64dcb-f127-4f26-a3ef-fc58543dcc4a": "",
      "3aef2f2c-2640-4466-94dc-d4155410fea0": "",
      "05f978d0-ce75-4366-b947-b2ae4c674833": "",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6": "",
      "fafd6252-e52e-4d22-a15a-847f03e3623d": "",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e": "",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf": "",
      "95d9642d-1477-4869-95c2-66417124d8a1": "",
      "639245d1-382a-4670-a87d-7b3847e729d2": "",
      "2b3852a2-703a-489b-a4f8-ec6106721f25": "",
      "1987b3a1-9c59-4571-a470-733e9cfd6776": "",
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28": ""
    },
    {
      "e19c1178-6152-4a97-8271-ec3727291cfd": "",
      "95a64dcb-f127-4f26-a3ef-fc58543dcc4a": "",
      "3aef2f2c-2640-4466-94dc-d4155410fea0": "",
      "05f978d0-ce75-4366-b947-b2ae4c674833": "",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6": "",
      "fafd6252-e52e-4d22-a15a-847f03e3623d": "",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e": "",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf": "",
      "95d9642d-1477-4869-95c2-66417124d8a1": "",
      "639245d1-382a-4670-a87d-7b3847e729d2": "",
      "2b3852a2-703a-489b-a4f8-ec6106721f25": "",
      "1987b3a1-9c59-4571-a470-733e9cfd6776": "",
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28": ""
    },
    {
      "e19c1178-6152-4a97-8271-ec3727291cfd": "",
      "95a64dcb-f127-4f26-a3ef-fc58543dcc4a": "",
      "3aef2f2c-2640-4466-94dc-d4155410fea0": "",
      "05f978d0-ce75-4366-b947-b2ae4c674833": "",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6": "",
      "fafd6252-e52e-4d22-a15a-847f03e3623d": "",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e": "",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf": "",
      "95d9642d-1477-4869-95c2-66417124d8a1": "",
      "639245d1-382a-4670-a87d-7b3847e729d2": "",
      "2b3852a2-703a-489b-a4f8-ec6106721f25": "",
      "1987b3a1-9c59-4571-a470-733e9cfd6776": "",
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28": ""
    },
    {
      "e19c1178-6152-4a97-8271-ec3727291cfd": "",
      "95a64dcb-f127-4f26-a3ef-fc58543dcc4a": "",
      "3aef2f2c-2640-4466-94dc-d4155410fea0": "",
      "05f978d0-ce75-4366-b947-b2ae4c674833": "",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6": "",
      "fafd6252-e52e-4d22-a15a-847f03e3623d": "",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e": "",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf": "",
      "95d9642d-1477-4869-95c2-66417124d8a1": "",
      "639245d1-382a-4670-a87d-7b3847e729d2": "",
      "2b3852a2-703a-489b-a4f8-ec6106721f25": "",
      "1987b3a1-9c59-4571-a470-733e9cfd6776": "",
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28": ""
    },
    {
      "e19c1178-6152-4a97-8271-ec3727291cfd": "",
      "95a64dcb-f127-4f26-a3ef-fc58543dcc4a": "",
      "3aef2f2c-2640-4466-94dc-d4155410fea0": "",
      "05f978d0-ce75-4366-b947-b2ae4c674833": "",
      "3c1b8d5b-0a6f-4736-91ef-618c92bda9a6": "",
      "fafd6252-e52e-4d22-a15a-847f03e3623d": "",
      "017ef6e2-4c44-42e5-bd6c-8e6ad995883e": "",
      "e40e66bd-2006-4b92-bd40-77a865fa8aaf": "",
      "95d9642d-1477-4869-95c2-66417124d8a1": "",
      "639245d1-382a-4670-a87d-7b3847e729d2": "",
      "2b3852a2-703a-489b-a4f8-ec6106721f25": "",
      "1987b3a1-9c59-4571-a470-733e9cfd6776": "",
      "c4f2ca2c-b8b9-443a-9d09-0176b8d17b28": ""
    }
  ]
}
```

---

# 9.03 cOSTOS DE OPERACIÓN Y MANTENIMIENTO CON Y SIN PROYECTO

---

## Campo 09.03.1 — Fecha prevista de inicio de operaciones: (mes / año):

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.03.1",
  "nombre": "Fecha prevista de inicio de operaciones: (mes / año):",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "G",
    "fila": 97,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": "04/11/2025"
}
```

---

## Campo 09.03.2 — Horizonte de funcionamiento (años)

**Tipo:** Texto corto.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.03.2",
  "nombre": "Horizonte de funcionamiento (años)",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "G",
    "fila": 98,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": "10"
}
```

---

## Campo 09.03.3 — Costos de operación y mantenimiento — Situación sin proyecto

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `detalle` | Detalle | texto_corto |
| `cantidad` | Cantidad | decimal |
| `costo` | Costo | decimal |
| `total` | Total | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Costos anual",
    "hijos": [
      "cantidad",
      "costo",
      "total"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": true, "abarca_filas": 1, "agrupador_abarca_columnas": 4}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.03.3",
  "nombre": "Costos de operación y mantenimiento — Situación sin proyecto",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1,
    "agrupador_abarca_columnas": 4
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 103,
    "filas_base": 19,
    "columnas": [
      {
        "id": "detalle",
        "columna": "B",
        "abarca_columnas": 4
      },
      {
        "id": "cantidad",
        "columna": "F",
        "abarca_columnas": 1
      },
      {
        "id": "costo",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "total",
        "columna": "H",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Costos anual",
      "hijos": [
        "cantidad",
        "costo",
        "total"
      ]
    }
  ],
  "columnas": [
    {
      "id": "detalle",
      "nombre": "Detalle",
      "tipo": "texto_corto"
    },
    {
      "id": "cantidad",
      "nombre": "Cantidad",
      "tipo": "decimal"
    },
    {
      "id": "costo",
      "nombre": "Costo",
      "tipo": "decimal"
    },
    {
      "id": "total",
      "nombre": "Total",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 4,
        "nombre": "a. Personal",
        "valores": {
          "detalle": "a. Personal",
          "cantidad": "",
          "costo": "",
          "total": ""
        }
      },
      "valores": [
        {
          "detalle": "·       Actores comunales:",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Madre cuidadora",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Madre guía",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Guía de Familia",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Socia de cocina",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Repartidor",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Apoyo de Limpieza y Vigilancia",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "·       Equipo técnico profesional del PNCM: ",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Acompañantes técnicos (AT)",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Especialistas integrales",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Especialistas en nutrición",
          "cantidad": "",
          "costo": "",
          "total": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 4,
        "nombre": "b. Servicios",
        "valores": {
          "detalle": "b. Servicios",
          "cantidad": "",
          "costo": "",
          "total": ""
        }
      },
      "valores": [
        {
          "detalle": "·       Servicios",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Agua",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Luz",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Internet",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Seguridad",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Mantenimiento",
          "cantidad": "",
          "costo": "",
          "total": ""
        }
      ]
    }
  ]
}
```

---

## Campo 09.03.4 — Total de costos de operación y mantenimiento (sin proyecto)

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.03.4",
  "nombre": "Total de costos de operación y mantenimiento (sin proyecto)",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "H",
    "fila": 122,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": "274780"
}
```

---

## Campo 09.03.5 — Costos de operación y mantenimiento — Situación con proyecto

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `detalle` | Detalle | texto_corto |
| `cantidad` | Cantidad | decimal |
| `costo` | Costo | decimal |
| `total` | Total | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Costos anual",
    "hijos": [
      "cantidad",
      "costo",
      "total"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": true, "abarca_filas": 1, "agrupador_abarca_columnas": 4}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.03.5",
  "nombre": "Costos de operación y mantenimiento — Situación con proyecto",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1,
    "agrupador_abarca_columnas": 4
  },
  "captura": {
    "columna_inicial": "J",
    "fila_inicial": 103,
    "filas_base": 19,
    "columnas": [
      {
        "id": "detalle",
        "columna": "J",
        "abarca_columnas": 4
      },
      {
        "id": "cantidad",
        "columna": "N",
        "abarca_columnas": 1
      },
      {
        "id": "costo",
        "columna": "O",
        "abarca_columnas": 1
      },
      {
        "id": "total",
        "columna": "P",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Costos anual",
      "hijos": [
        "cantidad",
        "costo",
        "total"
      ]
    }
  ],
  "columnas": [
    {
      "id": "detalle",
      "nombre": "Detalle",
      "tipo": "texto_corto"
    },
    {
      "id": "cantidad",
      "nombre": "Cantidad",
      "tipo": "decimal"
    },
    {
      "id": "costo",
      "nombre": "Costo",
      "tipo": "decimal"
    },
    {
      "id": "total",
      "nombre": "Total",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 4,
        "nombre": "a. Personal",
        "valores": {
          "detalle": "a. Personal",
          "cantidad": "",
          "costo": "",
          "total": ""
        }
      },
      "valores": [
        {
          "detalle": "·       Actores comunales:",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Madre cuidadora",
          "cantidad": 2,
          "costo": 7800,
          "total": ""
        },
        {
          "detalle": "o   Madre guía",
          "cantidad": 6,
          "costo": 7800,
          "total": ""
        },
        {
          "detalle": "o   Guía de Familia",
          "cantidad": 1,
          "costo": 7800,
          "total": ""
        },
        {
          "detalle": "o   Socia de cocina",
          "cantidad": 4,
          "costo": 7800,
          "total": ""
        },
        {
          "detalle": "o   Repartidor",
          "cantidad": 1,
          "costo": 7800,
          "total": ""
        },
        {
          "detalle": "o   Apoyo de Limpieza y Vigilancia",
          "cantidad": 1,
          "costo": 14400,
          "total": ""
        },
        {
          "detalle": "·       Equipo técnico profesional del PNCM: ",
          "cantidad": 0,
          "costo": 0,
          "total": ""
        },
        {
          "detalle": "o   Acompañantes técnicos (AT)",
          "cantidad": 1,
          "costo": 54000,
          "total": ""
        },
        {
          "detalle": "o   Especialistas integrales",
          "cantidad": 1,
          "costo": 54000,
          "total": ""
        },
        {
          "detalle": "o   Especialistas en nutrición",
          "cantidad": 1,
          "costo": 54000,
          "total": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 4,
        "nombre": "b. Servicios",
        "valores": {
          "detalle": "b. Servicios",
          "cantidad": "",
          "costo": "",
          "total": ""
        }
      },
      "valores": [
        {
          "detalle": "·       Servicios",
          "cantidad": "",
          "costo": "",
          "total": ""
        },
        {
          "detalle": "o   Agua",
          "cantidad": 1,
          "costo": 1728,
          "total": ""
        },
        {
          "detalle": "o   Luz",
          "cantidad": 1,
          "costo": 3168,
          "total": ""
        },
        {
          "detalle": "o   Internet",
          "cantidad": 0,
          "costo": 0,
          "total": ""
        },
        {
          "detalle": "o   Seguridad",
          "cantidad": 0,
          "costo": 0,
          "total": ""
        },
        {
          "detalle": "o   Mantenimiento",
          "cantidad": 1,
          "costo": 17000,
          "total": ""
        }
      ]
    }
  ]
}
```

---

## Campo 09.03.6 — Total de costos de operación y mantenimiento (con proyecto)

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.03.6",
  "nombre": "Total de costos de operación y mantenimiento (con proyecto)",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "P",
    "fila": 122,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": "307496"
}
```

---

# 9.04 Cronograma de inversión de metas financieras

---

## Campo 09.04.1 — Fecha prevista de inicio de ejecución (mes y año)

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.04.1",
  "nombre": "Fecha prevista de inicio de ejecución (mes y año)",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "F",
    "fila": 126,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": "28/05/2024"
}
```

---

## Campo 09.04.2 — Tipo de periodo

**Tipo:** Texto corto.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.04.2",
  "nombre": "Tipo de periodo",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "F",
    "fila": 127,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": "Trimestre"
}
```

---

## Campo 09.04.3 — Número de periodos

**Tipo:** Decimal.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.04.3",
  "nombre": "Número de periodos",
  "tipo_nodo": "campo",
  "tipo": "decimal",
  "editable": true,
  "captura": {
    "columna": "F",
    "fila": 128,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": ""
}
```

---

## Campo 09.04.4 — Cronograma de inversión de metas financieras

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `componente` | Componente | texto_corto |
| `tipo_factor` | Tipo de factor productivo | texto_corto |
| `tri_1` | Tri 1 | decimal |
| `tri_2` | Tri 2 | decimal |
| `tri_3` | Tri 3 | decimal |
| `tri_4` | Tri 4 | decimal |
| `tri_5` | Tri 5 | decimal |
| `tri_6` | Tri 6 | decimal |
| `tri_7` | Tri 7 | decimal |
| `tri_8` | Tri 8 | decimal |
| `costo_estimado` | Costo estimado de inversión a precios de mercado (Soles) | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Cronograma de inversión (trimestres)",
    "hijos": [
      "tri_1",
      "tri_2",
      "tri_3",
      "tri_4",
      "tri_5",
      "tri_6",
      "tri_7",
      "tri_8"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": true, "abarca_filas": 1, "agrupador_abarca_columnas": 5}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.04.4",
  "nombre": "Cronograma de inversión de metas financieras",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1,
    "agrupador_abarca_columnas": 5
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 134,
    "filas_base": 10,
    "columnas": [
      {
        "id": "componente",
        "columna": "B",
        "abarca_columnas": 5
      },
      {
        "id": "tipo_factor",
        "columna": "G",
        "abarca_columnas": 2
      },
      {
        "id": "tri_1",
        "columna": "I",
        "abarca_columnas": 1
      },
      {
        "id": "tri_2",
        "columna": "J",
        "abarca_columnas": 1
      },
      {
        "id": "tri_3",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "tri_4",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "tri_5",
        "columna": "M",
        "abarca_columnas": 1
      },
      {
        "id": "tri_6",
        "columna": "N",
        "abarca_columnas": 1
      },
      {
        "id": "tri_7",
        "columna": "O",
        "abarca_columnas": 1
      },
      {
        "id": "tri_8",
        "columna": "P",
        "abarca_columnas": 1
      },
      {
        "id": "costo_estimado",
        "columna": "Q",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Cronograma de inversión (trimestres)",
      "hijos": [
        "tri_1",
        "tri_2",
        "tri_3",
        "tri_4",
        "tri_5",
        "tri_6",
        "tri_7",
        "tri_8"
      ]
    }
  ],
  "columnas": [
    {
      "id": "componente",
      "nombre": "Componente",
      "tipo": "texto_corto"
    },
    {
      "id": "tipo_factor",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "tri_1",
      "nombre": "Tri 1",
      "tipo": "decimal"
    },
    {
      "id": "tri_2",
      "nombre": "Tri 2",
      "tipo": "decimal"
    },
    {
      "id": "tri_3",
      "nombre": "Tri 3",
      "tipo": "decimal"
    },
    {
      "id": "tri_4",
      "nombre": "Tri 4",
      "tipo": "decimal"
    },
    {
      "id": "tri_5",
      "nombre": "Tri 5",
      "tipo": "decimal"
    },
    {
      "id": "tri_6",
      "nombre": "Tri 6",
      "tipo": "decimal"
    },
    {
      "id": "tri_7",
      "nombre": "Tri 7",
      "tipo": "decimal"
    },
    {
      "id": "tri_8",
      "nombre": "Tri 8",
      "tipo": "decimal"
    },
    {
      "id": "costo_estimado",
      "nombre": "Costo estimado de inversión a precios de mercado (Soles)",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "agrupador": {
        "inicia": "componente",
        "abarca_columnas": 5,
        "nombre": "Componente 1:",
        "valores": {
          "componente": "Componente 1:",
          "tipo_factor": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "costo_estimado": ""
        }
      },
      "valores": [
        {
          "componente": "",
          "tipo_factor": "Infraestructura",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": 294588.75,
          "tri_5": 294588.75,
          "tri_6": 294588.75,
          "tri_7": 294588.75,
          "tri_8": "",
          "costo_estimado": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "componente",
        "abarca_columnas": 5,
        "nombre": "Componente 2",
        "valores": {
          "componente": "Componente 2",
          "tipo_factor": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "costo_estimado": ""
        }
      },
      "valores": [
        {
          "componente": "",
          "tipo_factor": "Equipamiento",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": 25000,
          "tri_8": "",
          "costo_estimado": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "componente",
        "abarca_columnas": 5,
        "nombre": "Componente 3",
        "valores": {
          "componente": "Componente 3",
          "tipo_factor": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "costo_estimado": ""
        }
      },
      "valores": [
        {
          "componente": "",
          "tipo_factor": "Mobiliario",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": 160000,
          "tri_8": "",
          "costo_estimado": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "componente",
        "abarca_columnas": 5,
        "nombre": "Componente 4",
        "valores": {
          "componente": "Componente 4",
          "tipo_factor": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "costo_estimado": ""
        }
      },
      "valores": [
        {
          "componente": "",
          "tipo_factor": "Intangibles",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": 9000,
          "tri_7": 9000,
          "tri_8": "",
          "costo_estimado": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "componente",
        "abarca_columnas": 5,
        "nombre": "Medidas de reducción del riesgo de desastre y mitigación ambiental",
        "valores": {
          "componente": "Medidas de reducción del riesgo de desastre y mitigación ambiental",
          "tipo_factor": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "costo_estimado": ""
        }
      },
      "valores": [
        {
          "componente": "Implementación de medidas ",
          "tipo_factor": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "costo_estimado": ""
        }
      ]
    }
  ]
}
```

---

## Campo 09.04.5 — Sub total del cronograma de inversión

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.04.5",
  "nombre": "Sub total del cronograma de inversión",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "Q",
    "fila": 144,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": "1311355"
}
```

---

## Campo 09.04.6 — Otros costos

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `otros_costos` | Otros costos | texto_corto |
| `tri_1` | Tri 1 | decimal |
| `tri_2` | Tri 2 | decimal |
| `tri_3` | Tri 3 | decimal |
| `tri_4` | Tri 4 | decimal |
| `tri_5` | Tri 5 | decimal |
| `tri_6` | Tri 6 | decimal |
| `tri_7` | Tri 7 | decimal |
| `tri_8` | Tri 8 | decimal |
| `costo_mercado` | Costos a precio de mercado | decimal |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.04.6",
  "nombre": "Otros costos",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 148,
    "filas_base": 5,
    "columnas": [
      {
        "id": "otros_costos",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "tri_1",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "tri_2",
        "columna": "E",
        "abarca_columnas": 1
      },
      {
        "id": "tri_3",
        "columna": "F",
        "abarca_columnas": 1
      },
      {
        "id": "tri_4",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "tri_5",
        "columna": "H",
        "abarca_columnas": 1
      },
      {
        "id": "tri_6",
        "columna": "I",
        "abarca_columnas": 1
      },
      {
        "id": "tri_7",
        "columna": "J",
        "abarca_columnas": 1
      },
      {
        "id": "tri_8",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "costo_mercado",
        "columna": "L",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "otros_costos",
      "nombre": "Otros costos",
      "tipo": "texto_corto"
    },
    {
      "id": "tri_1",
      "nombre": "Tri 1",
      "tipo": "decimal"
    },
    {
      "id": "tri_2",
      "nombre": "Tri 2",
      "tipo": "decimal"
    },
    {
      "id": "tri_3",
      "nombre": "Tri 3",
      "tipo": "decimal"
    },
    {
      "id": "tri_4",
      "nombre": "Tri 4",
      "tipo": "decimal"
    },
    {
      "id": "tri_5",
      "nombre": "Tri 5",
      "tipo": "decimal"
    },
    {
      "id": "tri_6",
      "nombre": "Tri 6",
      "tipo": "decimal"
    },
    {
      "id": "tri_7",
      "nombre": "Tri 7",
      "tipo": "decimal"
    },
    {
      "id": "tri_8",
      "nombre": "Tri 8",
      "tipo": "decimal"
    },
    {
      "id": "costo_mercado",
      "nombre": "Costos a precio de mercado",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "otros_costos": "",
      "tri_1": "",
      "tri_2": "",
      "tri_3": "",
      "tri_4": 16250,
      "tri_5": 16250,
      "tri_6": 16250,
      "tri_7": 16250,
      "tri_8": "",
      "costo_mercado": ""
    },
    {
      "otros_costos": "",
      "tri_1": 89700,
      "tri_2": "",
      "tri_3": "",
      "tri_4": "",
      "tri_5": "",
      "tri_6": "",
      "tri_7": "",
      "tri_8": "",
      "costo_mercado": ""
    },
    {
      "otros_costos": "",
      "tri_1": "",
      "tri_2": "",
      "tri_3": "",
      "tri_4": 12500,
      "tri_5": 12500,
      "tri_6": 12500,
      "tri_7": 12500,
      "tri_8": "",
      "costo_mercado": ""
    },
    {
      "otros_costos": "",
      "tri_1": "",
      "tri_2": "",
      "tri_3": "",
      "tri_4": "",
      "tri_5": "",
      "tri_6": "",
      "tri_7": "",
      "tri_8": 30000,
      "costo_mercado": ""
    },
    {
      "otros_costos": "",
      "tri_1": "",
      "tri_2": "",
      "tri_3": "",
      "tri_4": 37500,
      "tri_5": 37500,
      "tri_6": 37500,
      "tri_7": 37500,
      "tri_8": "",
      "costo_mercado": ""
    }
  ]
}
```

---

## Campo 09.04.7 — Sub total de otros costos

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.04.7",
  "nombre": "Sub total de otros costos",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "L",
    "fila": 153,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": "384700"
}
```

---

## Campo 09.04.8 — Costo total de inversión

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.04.8",
  "nombre": "Costo total de inversión",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "L",
    "fila": 154,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": "1696055"
}
```

---

## Campo 09.04.9 — Control concurrente

**Tipo:** Decimal.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.04.9",
  "nombre": "Control concurrente",
  "tipo_nodo": "campo",
  "tipo": "decimal",
  "editable": true,
  "captura": {
    "columna": "L",
    "fila": 156,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": 33921.1
}
```

---

# 9.05 Cronograma de metas físicas

---

## Campo 09.05.1 — Cronograma de metas físicas

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `componente` | Componente | texto_corto |
| `tipo_factor` | Tipo de factor productivo | texto_corto |
| `unidad_medida` | Unidad de medida representativa | texto_corto |
| `tri_1` | Tri 1 | decimal |
| `tri_2` | Tri 2 | decimal |
| `tri_3` | Tri 3 | decimal |
| `tri_4` | Tri 4 | decimal |
| `tri_5` | Tri 5 | decimal |
| `tri_6` | Tri 6 | decimal |
| `tri_7` | Tri 7 | decimal |
| `tri_8` | Tri 8 | decimal |
| `total_meta` | Total Meta Física | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Cronograma de ejecución física (trimestres)",
    "hijos": [
      "tri_1",
      "tri_2",
      "tri_3",
      "tri_4",
      "tri_5",
      "tri_6",
      "tri_7",
      "tri_8"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": true, "abarca_filas": 1, "agrupador_abarca_columnas": 5}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "09.05.1",
  "nombre": "Cronograma de metas físicas",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1,
    "agrupador_abarca_columnas": 5
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 164,
    "filas_base": 10,
    "columnas": [
      {
        "id": "componente",
        "columna": "B",
        "abarca_columnas": 5
      },
      {
        "id": "tipo_factor",
        "columna": "G",
        "abarca_columnas": 2
      },
      {
        "id": "unidad_medida",
        "columna": "I",
        "abarca_columnas": 2
      },
      {
        "id": "tri_1",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "tri_2",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "tri_3",
        "columna": "M",
        "abarca_columnas": 1
      },
      {
        "id": "tri_4",
        "columna": "N",
        "abarca_columnas": 1
      },
      {
        "id": "tri_5",
        "columna": "O",
        "abarca_columnas": 1
      },
      {
        "id": "tri_6",
        "columna": "P",
        "abarca_columnas": 1
      },
      {
        "id": "tri_7",
        "columna": "Q",
        "abarca_columnas": 1
      },
      {
        "id": "tri_8",
        "columna": "R",
        "abarca_columnas": 1
      },
      {
        "id": "total_meta",
        "columna": "S",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Cronograma de ejecución física (trimestres)",
      "hijos": [
        "tri_1",
        "tri_2",
        "tri_3",
        "tri_4",
        "tri_5",
        "tri_6",
        "tri_7",
        "tri_8"
      ]
    }
  ],
  "columnas": [
    {
      "id": "componente",
      "nombre": "Componente",
      "tipo": "texto_corto"
    },
    {
      "id": "tipo_factor",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "unidad_medida",
      "nombre": "Unidad de medida representativa",
      "tipo": "texto_corto"
    },
    {
      "id": "tri_1",
      "nombre": "Tri 1",
      "tipo": "decimal"
    },
    {
      "id": "tri_2",
      "nombre": "Tri 2",
      "tipo": "decimal"
    },
    {
      "id": "tri_3",
      "nombre": "Tri 3",
      "tipo": "decimal"
    },
    {
      "id": "tri_4",
      "nombre": "Tri 4",
      "tipo": "decimal"
    },
    {
      "id": "tri_5",
      "nombre": "Tri 5",
      "tipo": "decimal"
    },
    {
      "id": "tri_6",
      "nombre": "Tri 6",
      "tipo": "decimal"
    },
    {
      "id": "tri_7",
      "nombre": "Tri 7",
      "tipo": "decimal"
    },
    {
      "id": "tri_8",
      "nombre": "Tri 8",
      "tipo": "decimal"
    },
    {
      "id": "total_meta",
      "nombre": "Total Meta Física",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "agrupador": {
        "inicia": "componente",
        "abarca_columnas": 5,
        "nombre": "Componente 1:",
        "valores": {
          "componente": "Componente 1:",
          "tipo_factor": "",
          "unidad_medida": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "total_meta": ""
        }
      },
      "valores": [
        {
          "componente": "",
          "tipo_factor": "Infraestructura",
          "unidad_medida": "m2",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "total_meta": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "componente",
        "abarca_columnas": 5,
        "nombre": "Componente 2",
        "valores": {
          "componente": "Componente 2",
          "tipo_factor": "",
          "unidad_medida": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "total_meta": ""
        }
      },
      "valores": [
        {
          "componente": "",
          "tipo_factor": "Equipo",
          "unidad_medida": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "total_meta": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "componente",
        "abarca_columnas": 5,
        "nombre": "Componente 3",
        "valores": {
          "componente": "Componente 3",
          "tipo_factor": "",
          "unidad_medida": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "total_meta": ""
        }
      },
      "valores": [
        {
          "componente": "",
          "tipo_factor": "Mobiliario",
          "unidad_medida": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "total_meta": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "componente",
        "abarca_columnas": 5,
        "nombre": "Componente 4",
        "valores": {
          "componente": "Componente 4",
          "tipo_factor": "",
          "unidad_medida": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "total_meta": ""
        }
      },
      "valores": [
        {
          "componente": "",
          "tipo_factor": "Intangibles",
          "unidad_medida": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "total_meta": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "componente",
        "abarca_columnas": 5,
        "nombre": "Medidas de reducción del riesgo de desastre y mitigación ambiental",
        "valores": {
          "componente": "Medidas de reducción del riesgo de desastre y mitigación ambiental",
          "tipo_factor": "",
          "unidad_medida": "",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "total_meta": ""
        }
      },
      "valores": [
        {
          "componente": "Implementación de medidas ",
          "tipo_factor": "",
          "unidad_medida": "Medidas",
          "tri_1": "",
          "tri_2": "",
          "tri_3": "",
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
          "tri_8": "",
          "total_meta": ""
        }
      ]
    }
  ]
}
```

---

## Resumen de acción para autollenado

| ID | Nombre | Tipo | Editable | Acción sugerida |
|---|---|---|---|---|
| 09.01.1 | Componente 1.1 | tabla | Sí | Llenar tabla |
| 09.01.2 | Componente 1.2 | tabla | Sí | Llenar tabla |
| 09.01.3 | Componente 2 | tabla | Sí | Llenar tabla |
| 09.01.4 | Componente 3 | tabla | Sí | Llenar tabla |
| 09.01.5 | Componente 4 | tabla | Sí | Llenar tabla |
| 09.01.6 | Costos indirectos | tabla | Sí | Llenar tabla |
| 09.01.7 | Subtotal de otros costos de inversión | calculado | No | NO LLENAR |
| 09.01.8 | Costo Total de inversión | calculado | No | NO LLENAR |
| 09.02.1 | Costos de reinversión | tabla | Sí | Llenar tabla |
| 09.03.1 | Fecha prevista de inicio de operaciones: (mes / año): | calculado | No | NO LLENAR |
| 09.03.2 | Horizonte de funcionamiento (años) | texto_corto | Sí | Llenar |
| 09.03.3 | Costos de operación y mantenimiento — Situación sin proyecto | tabla | Sí | Llenar tabla |
| 09.03.4 | Total de costos de operación y mantenimiento (sin proyecto) | calculado | No | NO LLENAR |
| 09.03.5 | Costos de operación y mantenimiento — Situación con proyecto | tabla | Sí | Llenar tabla |
| 09.03.6 | Total de costos de operación y mantenimiento (con proyecto) | calculado | No | NO LLENAR |
| 09.04.1 | Fecha prevista de inicio de ejecución (mes y año) | calculado | No | NO LLENAR |
| 09.04.2 | Tipo de periodo | texto_corto | Sí | Llenar |
| 09.04.3 | Número de periodos | decimal | Sí | Llenar |
| 09.04.4 | Cronograma de inversión de metas financieras | tabla | Sí | Llenar tabla |
| 09.04.5 | Sub total del cronograma de inversión | calculado | No | NO LLENAR |
| 09.04.6 | Otros costos | tabla | Sí | Llenar tabla |
| 09.04.7 | Sub total de otros costos | calculado | No | NO LLENAR |
| 09.04.8 | Costo total de inversión | calculado | No | NO LLENAR |
| 09.04.9 | Control concurrente | decimal | Sí | Llenar |
| 09.05.1 | Cronograma de metas físicas | tabla | Sí | Llenar tabla |
