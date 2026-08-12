# SECCIÓN N°09: COSTOS DEL PROYECTO - ALTERNATIVA 2

## Descripción de la sección

- **Qué representa:** SECCIÓN N°09: COSTOS DEL PROYECTO - ALTERNATIVA 2.
- **Objetivo (según instructivo):** Información no determinada por los archivos proporcionados.
- **Hoja Excel:** `CostosAlt2`
- **JSON `id` de sección:** `10`

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

**Esta guía documenta la Alternativa 2** (JSON id `10`).

### Subsecciones / grupos

- `10.01` — 9.01 Costo de ejecución física de las acciones
- `10.02` — 9.02 Costos de reinversión
- `10.03` — 9.03 Costos de operación y mantenimiento con y sin proyecto
- `10.04` — 9.04 Cronograma de inversión de metas financieras
- `10.05` — 9.05 Cronograma de metas físicas

---

# 9.01 Costo de ejecución física de las acciones

---

## Campo 10.01.1 — Componente 1

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `accion` | Acción sobre el activo | texto_corto |
| `tipo_factor` | Tipo de factor productivo | texto_corto |
| `activos` | Activos | texto_corto |
| `um_fisica` | Unidad de medida | texto_corto |
| `cant_fisica` | Cantidad | decimal |
| `um_dimension` | Unidad de medida | texto_corto |
| `cant_dimension` | Cantidad | decimal |
| `costo_unitario` | Costo unitario | decimal |
| `costo_total` | Costo total | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Unidad Física",
    "hijos": [
      "um_fisica",
      "cant_fisica"
    ]
  },
  {
    "titulo": "Dimensión física",
    "hijos": [
      "um_dimension",
      "cant_dimension"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.01.1",
  "nombre": "Componente 1",
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
    "filas_base": 9,
    "columnas": [
      {
        "id": "accion",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "tipo_factor",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "activos",
        "columna": "E",
        "abarca_columnas": 5
      },
      {
        "id": "um_fisica",
        "columna": "J",
        "abarca_columnas": 1
      },
      {
        "id": "cant_fisica",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "um_dimension",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "cant_dimension",
        "columna": "M",
        "abarca_columnas": 1
      },
      {
        "id": "costo_unitario",
        "columna": "N",
        "abarca_columnas": 1
      },
      {
        "id": "costo_total",
        "columna": "O",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Unidad Física",
      "hijos": [
        "um_fisica",
        "cant_fisica"
      ]
    },
    {
      "titulo": "Dimensión física",
      "hijos": [
        "um_dimension",
        "cant_dimension"
      ]
    }
  ],
  "columnas": [
    {
      "id": "accion",
      "nombre": "Acción sobre el activo",
      "tipo": "texto_corto"
    },
    {
      "id": "tipo_factor",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "activos",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "um_fisica",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "cant_fisica",
      "nombre": "Cantidad",
      "tipo": "decimal"
    },
    {
      "id": "um_dimension",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "cant_dimension",
      "nombre": "Cantidad",
      "tipo": "decimal"
    },
    {
      "id": "costo_unitario",
      "nombre": "Costo unitario",
      "tipo": "decimal"
    },
    {
      "id": "costo_total",
      "nombre": "Costo total",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cant_fisica": "",
      "um_dimension": "",
      "cant_dimension": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cant_fisica": "",
      "um_dimension": "",
      "cant_dimension": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cant_fisica": "",
      "um_dimension": "",
      "cant_dimension": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cant_fisica": "",
      "um_dimension": "",
      "cant_dimension": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cant_fisica": "",
      "um_dimension": "",
      "cant_dimension": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cant_fisica": "",
      "um_dimension": "",
      "cant_dimension": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cant_fisica": "",
      "um_dimension": "",
      "cant_dimension": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cant_fisica": "",
      "um_dimension": "",
      "cant_dimension": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cant_fisica": "",
      "um_dimension": "m2",
      "cant_dimension": "",
      "costo_unitario": "",
      "costo_total": ""
    }
  ]
}
```

---

## Campo 10.01.2 — Componente 2

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `accion` | Acción sobre el activo | texto_corto |
| `tipo_factor` | Tipo de factor productivo | texto_corto |
| `activos` | Activos | texto_corto |
| `um_fisica` | Unidad de medida | texto_corto |
| `cantidad` | Cantidad | decimal |
| `costo_total` | Costo total | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Unidad Física",
    "hijos": [
      "um_fisica",
      "cantidad"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.01.2",
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
        "id": "accion",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "tipo_factor",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "activos",
        "columna": "E",
        "abarca_columnas": 5
      },
      {
        "id": "um_fisica",
        "columna": "J",
        "abarca_columnas": 2
      },
      {
        "id": "cantidad",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "costo_total",
        "columna": "M",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Unidad Física",
      "hijos": [
        "um_fisica",
        "cantidad"
      ]
    }
  ],
  "columnas": [
    {
      "id": "accion",
      "nombre": "Acción sobre el activo",
      "tipo": "texto_corto"
    },
    {
      "id": "tipo_factor",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "activos",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "um_fisica",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "cantidad",
      "nombre": "Cantidad",
      "tipo": "decimal"
    },
    {
      "id": "costo_total",
      "nombre": "Costo total",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_total": ""
    }
  ]
}
```

---

## Campo 10.01.3 — Componente 3

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `accion` | Acción sobre el activo | texto_corto |
| `tipo_factor` | Tipo de factor productivo | texto_corto |
| `activos` | Activos | texto_corto |
| `um_fisica` | Unidad de medida | texto_corto |
| `cantidad` | Cantidad | decimal |
| `costo_total` | Costo total | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Unidad Física",
    "hijos": [
      "um_fisica",
      "cantidad"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.01.3",
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
        "id": "accion",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "tipo_factor",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "activos",
        "columna": "E",
        "abarca_columnas": 5
      },
      {
        "id": "um_fisica",
        "columna": "J",
        "abarca_columnas": 2
      },
      {
        "id": "cantidad",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "costo_total",
        "columna": "M",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Unidad Física",
      "hijos": [
        "um_fisica",
        "cantidad"
      ]
    }
  ],
  "columnas": [
    {
      "id": "accion",
      "nombre": "Acción sobre el activo",
      "tipo": "texto_corto"
    },
    {
      "id": "tipo_factor",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "activos",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "um_fisica",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "cantidad",
      "nombre": "Cantidad",
      "tipo": "decimal"
    },
    {
      "id": "costo_total",
      "nombre": "Costo total",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_total": ""
    },
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_total": ""
    }
  ]
}
```

---

## Campo 10.01.4 — Componente 4

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `accion` | Acción sobre el activo | texto_corto |
| `tipo_factor` | Tipo de factor productivo | texto_corto |
| `activos` | Activos | texto_corto |
| `um_fisica` | Unidad de medida | texto_corto |
| `cantidad` | Cantidad | decimal |
| `costo_total` | Costo total | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Unidad Física",
    "hijos": [
      "um_fisica",
      "cantidad"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.01.4",
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
        "id": "accion",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "tipo_factor",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "activos",
        "columna": "E",
        "abarca_columnas": 5
      },
      {
        "id": "um_fisica",
        "columna": "J",
        "abarca_columnas": 2
      },
      {
        "id": "cantidad",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "costo_total",
        "columna": "M",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Unidad Física",
      "hijos": [
        "um_fisica",
        "cantidad"
      ]
    }
  ],
  "columnas": [
    {
      "id": "accion",
      "nombre": "Acción sobre el activo",
      "tipo": "texto_corto"
    },
    {
      "id": "tipo_factor",
      "nombre": "Tipo de factor productivo",
      "tipo": "texto_corto"
    },
    {
      "id": "activos",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "um_fisica",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "cantidad",
      "nombre": "Cantidad",
      "tipo": "decimal"
    },
    {
      "id": "costo_total",
      "nombre": "Costo total",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "accion": "",
      "tipo_factor": "",
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_total": ""
    }
  ]
}
```

---

## Campo 10.01.5 — Medidas de reducción del riesgo de desastre y mitigación ambiental

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `activos` | Activos | texto_corto |
| `um_fisica` | Unidad de medida | texto_corto |
| `cantidad` | Cantidad | decimal |
| `costo_unitario` | Costo unitario | decimal |
| `costo_total` | Costo total | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Unidad Física",
    "hijos": [
      "um_fisica",
      "cantidad"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.01.5",
  "nombre": "Medidas de reducción del riesgo de desastre y mitigación ambiental",
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
    "fila_inicial": 47,
    "filas_base": 16,
    "columnas": [
      {
        "id": "activos",
        "columna": "B",
        "abarca_columnas": 8
      },
      {
        "id": "um_fisica",
        "columna": "J",
        "abarca_columnas": 2
      },
      {
        "id": "cantidad",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "costo_unitario",
        "columna": "M",
        "abarca_columnas": 1
      },
      {
        "id": "costo_total",
        "columna": "N",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Unidad Física",
      "hijos": [
        "um_fisica",
        "cantidad"
      ]
    }
  ],
  "columnas": [
    {
      "id": "activos",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "um_fisica",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "cantidad",
      "nombre": "Cantidad",
      "tipo": "decimal"
    },
    {
      "id": "costo_unitario",
      "nombre": "Costo unitario",
      "tipo": "decimal"
    },
    {
      "id": "costo_total",
      "nombre": "Costo total",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    },
    {
      "activos": "",
      "um_fisica": "",
      "cantidad": "",
      "costo_unitario": "",
      "costo_total": ""
    }
  ]
}
```

---

## Campo 10.01.6 — Sub Total de costos directos

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.01.6",
  "nombre": "Sub Total de costos directos",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "M",
    "fila": 64,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": "0.00"
}
```

---

## Campo 10.01.7 — Costos indirectos

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `otros_costos` | Otros costos | texto_corto |
| `costos_mercado` | Costos a precios de mercado | decimal |
| `pct_directo` | % respecto al costo directo | decimal |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.01.7",
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
        "id": "otros_costos",
        "columna": "B",
        "abarca_columnas": 3
      },
      {
        "id": "costos_mercado",
        "columna": "E",
        "abarca_columnas": 2
      },
      {
        "id": "pct_directo",
        "columna": "G",
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
      "id": "costos_mercado",
      "nombre": "Costos a precios de mercado",
      "tipo": "decimal"
    },
    {
      "id": "pct_directo",
      "nombre": "% respecto al costo directo",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "otros_costos": "Gestión del proyecto",
      "costos_mercado": "",
      "pct_directo": ""
    },
    {
      "otros_costos": "Expediente técnico o documento equivalente",
      "costos_mercado": "",
      "pct_directo": ""
    },
    {
      "otros_costos": "Supervisión",
      "costos_mercado": "",
      "pct_directo": ""
    },
    {
      "otros_costos": "Liquidación",
      "costos_mercado": "",
      "pct_directo": ""
    },
    {
      "otros_costos": "Gastos generales",
      "costos_mercado": "",
      "pct_directo": ""
    }
  ]
}
```

---

## Campo 10.01.8 — Subtotal de otros costos de inversión

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.01.8",
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
  "valor": "0"
}
```

---

## Campo 10.01.9 — Costo Total de inversión

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.01.9",
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
  "valor": "0.00"
}
```

---

# 9.02 Costos de reinversión

---

## Campo 10.02.1 — Costos de reinversión

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `activos` | Activos | texto_corto |
| `um` | UM | texto_corto |
| `cantidad` | Cantidad | decimal |
| `anio_1` | Año 1 | decimal |
| `anio_2` | Año 2 | decimal |
| `anio_3` | Año 3 | decimal |
| `anio_4` | Año 4 | decimal |
| `anio_5` | Año 5 | decimal |
| `anio_6` | Año 6 | decimal |
| `anio_7` | Año 7 | decimal |
| `anio_8` | Año 8 | decimal |
| `anio_9` | Año 9 | decimal |
| `anio_10` | Año 10 | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "AÑOS (Soles)",
    "hijos": [
      "anio_1",
      "anio_2",
      "anio_3",
      "anio_4",
      "anio_5",
      "anio_6",
      "anio_7",
      "anio_8",
      "anio_9",
      "anio_10"
    ]
  }
]
```

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.02.1",
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
    "fila_inicial": 86,
    "filas_base": 8,
    "columnas": [
      {
        "id": "activos",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "um",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "cantidad",
        "columna": "E",
        "abarca_columnas": 1
      },
      {
        "id": "anio_1",
        "columna": "F",
        "abarca_columnas": 1
      },
      {
        "id": "anio_2",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "anio_3",
        "columna": "H",
        "abarca_columnas": 1
      },
      {
        "id": "anio_4",
        "columna": "I",
        "abarca_columnas": 1
      },
      {
        "id": "anio_5",
        "columna": "J",
        "abarca_columnas": 1
      },
      {
        "id": "anio_6",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "anio_7",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "anio_8",
        "columna": "M",
        "abarca_columnas": 1
      },
      {
        "id": "anio_9",
        "columna": "N",
        "abarca_columnas": 1
      },
      {
        "id": "anio_10",
        "columna": "O",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "AÑOS (Soles)",
      "hijos": [
        "anio_1",
        "anio_2",
        "anio_3",
        "anio_4",
        "anio_5",
        "anio_6",
        "anio_7",
        "anio_8",
        "anio_9",
        "anio_10"
      ]
    }
  ],
  "columnas": [
    {
      "id": "activos",
      "nombre": "Activos",
      "tipo": "texto_corto"
    },
    {
      "id": "um",
      "nombre": "UM",
      "tipo": "texto_corto"
    },
    {
      "id": "cantidad",
      "nombre": "Cantidad",
      "tipo": "decimal"
    },
    {
      "id": "anio_1",
      "nombre": "Año 1",
      "tipo": "decimal"
    },
    {
      "id": "anio_2",
      "nombre": "Año 2",
      "tipo": "decimal"
    },
    {
      "id": "anio_3",
      "nombre": "Año 3",
      "tipo": "decimal"
    },
    {
      "id": "anio_4",
      "nombre": "Año 4",
      "tipo": "decimal"
    },
    {
      "id": "anio_5",
      "nombre": "Año 5",
      "tipo": "decimal"
    },
    {
      "id": "anio_6",
      "nombre": "Año 6",
      "tipo": "decimal"
    },
    {
      "id": "anio_7",
      "nombre": "Año 7",
      "tipo": "decimal"
    },
    {
      "id": "anio_8",
      "nombre": "Año 8",
      "tipo": "decimal"
    },
    {
      "id": "anio_9",
      "nombre": "Año 9",
      "tipo": "decimal"
    },
    {
      "id": "anio_10",
      "nombre": "Año 10",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "activos": "",
      "um": "",
      "cantidad": "",
      "anio_1": "",
      "anio_2": "",
      "anio_3": "",
      "anio_4": "",
      "anio_5": "",
      "anio_6": "",
      "anio_7": "",
      "anio_8": "",
      "anio_9": "",
      "anio_10": ""
    },
    {
      "activos": "",
      "um": "",
      "cantidad": "",
      "anio_1": "",
      "anio_2": "",
      "anio_3": "",
      "anio_4": "",
      "anio_5": "",
      "anio_6": "",
      "anio_7": "",
      "anio_8": "",
      "anio_9": "",
      "anio_10": ""
    },
    {
      "activos": "",
      "um": "",
      "cantidad": "",
      "anio_1": "",
      "anio_2": "",
      "anio_3": "",
      "anio_4": "",
      "anio_5": "",
      "anio_6": "",
      "anio_7": "",
      "anio_8": "",
      "anio_9": "",
      "anio_10": ""
    },
    {
      "activos": "",
      "um": "",
      "cantidad": "",
      "anio_1": "",
      "anio_2": "",
      "anio_3": "",
      "anio_4": "",
      "anio_5": "",
      "anio_6": "",
      "anio_7": "",
      "anio_8": "",
      "anio_9": "",
      "anio_10": ""
    },
    {
      "activos": "",
      "um": "",
      "cantidad": "",
      "anio_1": "",
      "anio_2": "",
      "anio_3": "",
      "anio_4": "",
      "anio_5": "",
      "anio_6": "",
      "anio_7": "",
      "anio_8": "",
      "anio_9": "",
      "anio_10": ""
    },
    {
      "activos": "",
      "um": "",
      "cantidad": "",
      "anio_1": "",
      "anio_2": "",
      "anio_3": "",
      "anio_4": "",
      "anio_5": "",
      "anio_6": "",
      "anio_7": "",
      "anio_8": "",
      "anio_9": "",
      "anio_10": ""
    },
    {
      "activos": "",
      "um": "",
      "cantidad": "",
      "anio_1": "",
      "anio_2": "",
      "anio_3": "",
      "anio_4": "",
      "anio_5": "",
      "anio_6": "",
      "anio_7": "",
      "anio_8": "",
      "anio_9": "",
      "anio_10": ""
    },
    {
      "activos": "",
      "um": "",
      "cantidad": "",
      "anio_1": "",
      "anio_2": "",
      "anio_3": "",
      "anio_4": "",
      "anio_5": "",
      "anio_6": "",
      "anio_7": "",
      "anio_8": "",
      "anio_9": "",
      "anio_10": ""
    }
  ]
}
```

---

# 9.03 Costos de operación y mantenimiento con y sin proyecto

---

## Campo 10.03.1 — Fecha prevista de inicio de operaciones (mes / año)

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.03.1",
  "nombre": "Fecha prevista de inicio de operaciones (mes / año)",
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

## Campo 10.03.2 — Horizonte de funcionamiento (años)

**Tipo:** Decimal.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.03.2",
  "nombre": "Horizonte de funcionamiento (años)",
  "tipo_nodo": "campo",
  "tipo": "decimal",
  "editable": true,
  "captura": {
    "columna": "G",
    "fila": 98,
    "abarca_columnas": 2,
    "abarca_filas": 1
  },
  "valor": 10
}
```

---

## Campo 10.03.3 — Costos de operación y mantenimiento — Situación sin proyecto

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
  "id": "10.03.3",
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

## Campo 10.03.4 — Total de costos de operación y mantenimiento (sin proyecto)

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.03.4",
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

## Campo 10.03.5 — Costos de operación y mantenimiento — Situación con proyecto

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
  "id": "10.03.5",
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

## Campo 10.03.6 — Total de costos de operación y mantenimiento (con proyecto)

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.03.6",
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
  "valor": "0"
}
```

---

# 9.04 Cronograma de inversión de metas financieras

---

## Campo 10.04.1 — Fecha prevista de inicio de ejecución (mes y año)

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.04.1",
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

## Campo 10.04.2 — Tipo de periodo

**Tipo:** Texto corto.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.04.2",
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

## Campo 10.04.3 — Número de periodos

**Tipo:** Decimal.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.04.3",
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

## Campo 10.04.4 — Cronograma de inversión de metas financieras

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
  "id": "10.04.4",
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
          "tri_4": "",
          "tri_5": "",
          "tri_6": "",
          "tri_7": "",
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
          "tri_7": "",
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
          "tri_7": "",
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
          "tri_6": "",
          "tri_7": "",
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

## Campo 10.04.5 — Sub total del cronograma de inversión

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.04.5",
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
  "valor": "0"
}
```

---

## Campo 10.04.6 — Otros costos

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
  "id": "10.04.6",
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
      "tri_4": "",
      "tri_5": "",
      "tri_6": "",
      "tri_7": "",
      "tri_8": "",
      "costo_mercado": ""
    }
  ]
}
```

---

## Campo 10.04.7 — Sub total de otros costos

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.04.7",
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
  "valor": "0"
}
```

---

## Campo 10.04.8 — Costo total de inversión

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.04.8",
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
  "valor": "0"
}
```

---

## Campo 10.04.9 — Control concurrente

**Tipo:** Decimal.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "10.04.9",
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
  "valor": ""
}
```

---

# 9.05 Cronograma de metas físicas

---

## Campo 10.05.1 — Cronograma de metas físicas

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
  "id": "10.05.1",
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
| 10.01.1 | Componente 1 | tabla | Sí | Llenar tabla |
| 10.01.2 | Componente 2 | tabla | Sí | Llenar tabla |
| 10.01.3 | Componente 3 | tabla | Sí | Llenar tabla |
| 10.01.4 | Componente 4 | tabla | Sí | Llenar tabla |
| 10.01.5 | Medidas de reducción del riesgo de desastre y mitigación ambiental | tabla | Sí | Llenar tabla |
| 10.01.6 | Sub Total de costos directos | calculado | No | NO LLENAR |
| 10.01.7 | Costos indirectos | tabla | Sí | Llenar tabla |
| 10.01.8 | Subtotal de otros costos de inversión | calculado | No | NO LLENAR |
| 10.01.9 | Costo Total de inversión | calculado | No | NO LLENAR |
| 10.02.1 | Costos de reinversión | tabla | Sí | Llenar tabla |
| 10.03.1 | Fecha prevista de inicio de operaciones (mes / año) | calculado | No | NO LLENAR |
| 10.03.2 | Horizonte de funcionamiento (años) | decimal | Sí | Llenar |
| 10.03.3 | Costos de operación y mantenimiento — Situación sin proyecto | tabla | Sí | Llenar tabla |
| 10.03.4 | Total de costos de operación y mantenimiento (sin proyecto) | calculado | No | NO LLENAR |
| 10.03.5 | Costos de operación y mantenimiento — Situación con proyecto | tabla | Sí | Llenar tabla |
| 10.03.6 | Total de costos de operación y mantenimiento (con proyecto) | calculado | No | NO LLENAR |
| 10.04.1 | Fecha prevista de inicio de ejecución (mes y año) | calculado | No | NO LLENAR |
| 10.04.2 | Tipo de periodo | texto_corto | Sí | Llenar |
| 10.04.3 | Número de periodos | decimal | Sí | Llenar |
| 10.04.4 | Cronograma de inversión de metas financieras | tabla | Sí | Llenar tabla |
| 10.04.5 | Sub total del cronograma de inversión | calculado | No | NO LLENAR |
| 10.04.6 | Otros costos | tabla | Sí | Llenar tabla |
| 10.04.7 | Sub total de otros costos | calculado | No | NO LLENAR |
| 10.04.8 | Costo total de inversión | calculado | No | NO LLENAR |
| 10.04.9 | Control concurrente | decimal | Sí | Llenar |
| 10.05.1 | Cronograma de metas físicas | tabla | Sí | Llenar tabla |
