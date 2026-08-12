# SECCIÓN N°17: ANEXOS

## Descripción de la sección

- **Qué representa:** SECCIÓN N°17: ANEXOS.
- **Objetivo (según instructivo):** Información no determinada por los archivos proporcionados.
- **Hoja Excel:** `Anexos`
- **JSON `id` de sección:** `20`

### Contexto del instructivo (extracto)

a. Precisar y sustentar cuál de las alternativas propuestas es la seleccionada. b. Sustentar el cumplimiento de los tres atributos que definen la condición de viabilidad de un proyecto, en caso el proyecto resulte viable. Si el resultado es no viable, indicar qué atributo o atributos no se logró cumplir. c. Emitir un juicio técnico sobre la calidad y la pertinencia del grado de profundización de la información empl eada para la elaboración de la ficha técnica, así como la consistencia y coherencia de los supuestos establecidos, las fuentes de información, las normas técnicas, los parámetros y metodologías empleadas, entre otros elementos claves relacionados con el fu ndamento técnico y económico de la decisión de inversión. SECCIÓN 16 - FIRMAS Se debe presentar la información personal (nombres y apellidos) y las respectivas firmas del responsable de la elaboración de la ficha técnica estándar y del responsable de la declaración de viabilidad, de corresponder. SECCIÓN 17 - ANEXOS Se debe presentar TODOS los anexos requeridos, como parte de la Ficha Técnica Estándar, los mismos que deben ser firmados, visados y sellados por los especialistas correspondientes: A. Resumen ejecutivo. B. Informe técnico de la situación actual de la unidad productora (infraestructura, mobiliario, equipamiento y capacitación). C. Evidencias de la realización de talleres, reuniones y actividades similares, tales como fotografías, listas firmad as de participantes y documentos de acuerdos como actas, entre otros. D. Informe técnico de la propuesta de intervención (en infraestructura, mobiliario, equipamiento y capacitación). E. Opinión favorable a la propuesta técnica por parte Programa Nacional Cuna Más, a la propuesta de intervención. F. Detalle del costo de inversión de las alternativas técnicas. G. Documento de opinión favorable del Programa Nacional Cuna Más, para la sostenibilidad del proyecto de inversión del CIAI. H. Documento de sustento del saneamiento físico legal o arreglo institucional que corresponda.

**Regla de ejemplos:** cada bloque de ejemplo es el **objeto `campo` completo** del `JSON EJEMPLO.json` correspondiente a esta sección/alternativa.

### Subsecciones / grupos

- `20.01` — 17.01 Anexos

---

# 17.01 Anexos

**Nota del JSON:** Importante: Se debe presentar TODOS los anexos requeridos, como parte de la Ficha

---

## Campo 20.01.1 — Anexos

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `996f2beb-0db7-40eb-a9b2-7544f017acd7` | Nro. | texto_corto |
| `05002340-1c88-4e96-a052-de0ac39623e4` | Descripción del anexo | texto_corto |
| `7e4efd07-e046-42be-92a5-1be70f4e4491` | ¿Se presenta el anexo como parte de la Ficha Técnica Estándar? | texto_corto |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "20.01.1",
  "nombre": "Anexos",
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
    "fila_inicial": 7,
    "filas_base": 8,
    "columnas": [
      {
        "id": "996f2beb-0db7-40eb-a9b2-7544f017acd7",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "05002340-1c88-4e96-a052-de0ac39623e4",
        "columna": "C",
        "abarca_columnas": 13
      },
      {
        "id": "7e4efd07-e046-42be-92a5-1be70f4e4491",
        "columna": "P",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "996f2beb-0db7-40eb-a9b2-7544f017acd7",
      "nombre": "Nro.",
      "tipo": "texto_corto"
    },
    {
      "id": "05002340-1c88-4e96-a052-de0ac39623e4",
      "nombre": "Descripción del anexo",
      "tipo": "texto_corto"
    },
    {
      "id": "7e4efd07-e046-42be-92a5-1be70f4e4491",
      "nombre": "¿Se presenta el anexo como parte de la Ficha Técnica Estándar?",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "996f2beb-0db7-40eb-a9b2-7544f017acd7": "A",
      "05002340-1c88-4e96-a052-de0ac39623e4": "Resumen ejecutivo.",
      "7e4efd07-e046-42be-92a5-1be70f4e4491": ""
    },
    {
      "996f2beb-0db7-40eb-a9b2-7544f017acd7": "B",
      "05002340-1c88-4e96-a052-de0ac39623e4": "Informe técnico de la situación actual de la unidad productora  (infraestructura, mobiliario, equipamiento y capacitación), suscrito por el equipo responsable de la formulación",
      "7e4efd07-e046-42be-92a5-1be70f4e4491": ""
    },
    {
      "996f2beb-0db7-40eb-a9b2-7544f017acd7": "C",
      "05002340-1c88-4e96-a052-de0ac39623e4": "Evidencias de la realización de talleres, reuniones y actividades similares, tales como fotografías, listas firmadas de participantes y documentos de acuerdos como actas, entre otros.",
      "7e4efd07-e046-42be-92a5-1be70f4e4491": ""
    },
    {
      "996f2beb-0db7-40eb-a9b2-7544f017acd7": "D",
      "05002340-1c88-4e96-a052-de0ac39623e4": "Informe técnico de la propuesta de intervención (en infraestructura, mobiliario, equipamiento y capacitación), suscrito por el equipo responsable de la formulación",
      "7e4efd07-e046-42be-92a5-1be70f4e4491": ""
    },
    {
      "996f2beb-0db7-40eb-a9b2-7544f017acd7": "E",
      "05002340-1c88-4e96-a052-de0ac39623e4": "Opinión favorable a la propuesta técnica por parte Programa Nacional Cuna Más, a la propuesta de intervención.",
      "7e4efd07-e046-42be-92a5-1be70f4e4491": ""
    },
    {
      "996f2beb-0db7-40eb-a9b2-7544f017acd7": "F",
      "05002340-1c88-4e96-a052-de0ac39623e4": "Detalle del costo de inversión de las alternativas técnicas, suscrito por el equipo responsable de la formulación",
      "7e4efd07-e046-42be-92a5-1be70f4e4491": ""
    },
    {
      "996f2beb-0db7-40eb-a9b2-7544f017acd7": "G",
      "05002340-1c88-4e96-a052-de0ac39623e4": "Documento de opinión favorable del Programa Nacional Cuna Más, para la sostenibilidad del proyecto de inversión del CIAI.",
      "7e4efd07-e046-42be-92a5-1be70f4e4491": ""
    },
    {
      "996f2beb-0db7-40eb-a9b2-7544f017acd7": "H",
      "05002340-1c88-4e96-a052-de0ac39623e4": "Documento de sustento del saneamiento físico legal o arreglo institucional que corresponda.",
      "7e4efd07-e046-42be-92a5-1be70f4e4491": ""
    }
  ]
}
```

---

## Resumen de acción para autollenado

| ID | Nombre | Tipo | Editable | Acción sugerida |
|---|---|---|---|---|
| 20.01.1 | Anexos | tabla | Sí | Llenar tabla |
