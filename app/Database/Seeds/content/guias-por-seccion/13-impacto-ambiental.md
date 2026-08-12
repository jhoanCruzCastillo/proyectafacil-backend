# SECCIÓN N°13: IMPACTO AMBIENTAL (de la alternativa seleccionada)

## Descripción de la sección

- **Qué representa:** SECCIÓN N°13: IMPACTO AMBIENTAL (de la alternativa seleccionada).
- **Objetivo (según instructivo):** Información no determinada por los archivos proporcionados.
- **Hoja Excel:** `(sin hoja declarada en JSON)`
- **JSON `id` de sección:** `17`

### Contexto del instructivo (extracto)

Ejemplo: 12.04. Entidad que estará a cargo de la operación y mantenimiento La entidad que estará a cargo de la operación y mantenimiento del CIAI, una vez ejecutado el proyecto de inversión, es el Programa Nacional Cuna Más. Ejemplo: 12.05. Fuente de financiamiento Se debe definir, entre las siguientes opciones, la fuente de financiamiento del proyecto de inversión en CIAI:  Recursos ordinarios.  Recursos directamente recaudados.  Recursos por operaciones oficiales de crédito.  Donaciones y transferencias.  Recursos determinados. Ejemplo: SECCIÓN 13 - IMPACTO AMBIENTAL En este punto se presentan las orientaciones para identificar los impactos negativos del proyecto de inversión, durante la fase de Ejecución y de Funcionamiento, además de plantear las medidas de mitigación que sean pertinentes. 12.03 Requerimientos institucionales y normativos en la fase de Ejecución y fase de Funcionamiento Marcar X X Licencia de construcción Se tramitará durante la elaboración del Expediente Técnico Certificado de parámetros urbanísticos Se tramitará antes de la elaboración del Expediente Técnico Condiciones previas relevantes para la fase de ejecución Estado situacional Saneamiento técnico legal o arreglo institucional Cesión en uso, a favor del ONCM Factibilidad de servicios de agua, desagüe y electricidad Cuenta con la factibilidad de servicios Condición o requerimiento 12.04 Entidad que estará a cargo de la operación y mantenimiento Programa Nacional Cuna Más 12.05 Fuente de financiamiento Fuente de Financiamiento Recursos determinados 13.01. Impacto ambiental El propósito es identificar los impactos negativos que el proyecto puede generar sobre el ambiente y plantear medidas de gestión ambiental, concerniente a acciones de prevención, corrección y mitigación, de corresponder, acorde con las regulaciones ambientales que sean pertinentes para la fase de Formulación y Evaluación del proyecto. Los costos de implementación de las medidas deben formar parte del costo de inversión del proyecto. Ejemplo: SECCIÓN 14 - MARCO LÓGICO En este punto se presentan las orientaciones para construir el Marco Lógico, que es una herramienta que resume la información esencial de la coherencia y consisten cia de un proyecto. Es importante precisar que se debe desarrollar este punto para la alternativa seleccionada en la Evaluación social. 14.01. Marco Lógico El Marco Lógico se construye a partir de la definición del árbol de objetivos y la propuesta de acciones para alcanzar los medios fundamentales del proyecto de inversión del CIAI. En tal sentido, la columna de objetivos guarda consistencia con los instrume ntos desarrollados en los puntos Definición de los objetivos del proyecto y Costos del proyecto. 13.01 Impacto ambiental COSTO (S/) Se incluye dentro de las actividades previstas en el presupuesto del proyecto Durante el Funcionamiento Ninguna IMPACTOS NEGATIVOS MEDIDAS DE MITIGACIÓN Durante la Ejecución Impacto 1: Generación de ruido y emisión de polvareda, durante el proceso de construcción Se considerará, durante la ejecución de obra, acciones dirigidas a controlar y evitar la generación de ruido y polvo,

**Regla de ejemplos:** cada bloque de ejemplo es el **objeto `campo` completo** del `JSON EJEMPLO.json` correspondiente a esta sección/alternativa.

## Nota sobre alternativa seleccionada (instructivo)

Según el instructivo, este punto se desarrolla **para la alternativa seleccionada** (la elegida tras la evaluación social de las alternativas técnicas), no para las tres en paralelo.

### Subsecciones / grupos

- `17.01` — 13.01 Impacto ambiental

---

# 13.01 Impacto ambiental

**Nota del JSON:** Nota: los costos deben formar parte del costo de inversión del proyecto

---

## Campo 17.01.1 — Impacto ambiental

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `95a84e92-b574-46e4-a6a5-afca60a91360` | IMPACTOS NEGATIVOS | texto_corto |
| `0fe96cb4-ee65-43d7-81b8-dab82cdaa1e0` | MEDIDAS DE MITIGACIÓN | texto_corto |
| `c5c9a0b1-aa0a-4b8d-8eb0-2c09ce1149e0` | COSTO (S/) | texto_corto |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": true, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "17.01.1",
  "nombre": "Impacto ambiental",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 8,
    "filas_base": 8,
    "columnas": [
      {
        "id": "95a84e92-b574-46e4-a6a5-afca60a91360",
        "columna": "B",
        "abarca_columnas": 4
      },
      {
        "id": "0fe96cb4-ee65-43d7-81b8-dab82cdaa1e0",
        "columna": "F",
        "abarca_columnas": 3
      },
      {
        "id": "c5c9a0b1-aa0a-4b8d-8eb0-2c09ce1149e0",
        "columna": "I",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "95a84e92-b574-46e4-a6a5-afca60a91360",
      "nombre": "IMPACTOS NEGATIVOS",
      "tipo": "texto_corto"
    },
    {
      "id": "0fe96cb4-ee65-43d7-81b8-dab82cdaa1e0",
      "nombre": "MEDIDAS DE MITIGACIÓN",
      "tipo": "texto_corto"
    },
    {
      "id": "c5c9a0b1-aa0a-4b8d-8eb0-2c09ce1149e0",
      "nombre": "COSTO (S/)",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "agrupador": {
        "inicia": "95a84e92-b574-46e4-a6a5-afca60a91360",
        "abarca_columnas": 4,
        "nombre": "Grupo 1",
        "valores": {}
      },
      "valores": [
        {
          "95a84e92-b574-46e4-a6a5-afca60a91360": "",
          "0fe96cb4-ee65-43d7-81b8-dab82cdaa1e0": "",
          "c5c9a0b1-aa0a-4b8d-8eb0-2c09ce1149e0": ""
        },
        {
          "95a84e92-b574-46e4-a6a5-afca60a91360": "",
          "0fe96cb4-ee65-43d7-81b8-dab82cdaa1e0": "",
          "c5c9a0b1-aa0a-4b8d-8eb0-2c09ce1149e0": ""
        },
        {
          "95a84e92-b574-46e4-a6a5-afca60a91360": "",
          "0fe96cb4-ee65-43d7-81b8-dab82cdaa1e0": "",
          "c5c9a0b1-aa0a-4b8d-8eb0-2c09ce1149e0": ""
        },
        {
          "95a84e92-b574-46e4-a6a5-afca60a91360": "",
          "0fe96cb4-ee65-43d7-81b8-dab82cdaa1e0": "",
          "c5c9a0b1-aa0a-4b8d-8eb0-2c09ce1149e0": ""
        },
        {
          "95a84e92-b574-46e4-a6a5-afca60a91360": "",
          "0fe96cb4-ee65-43d7-81b8-dab82cdaa1e0": "",
          "c5c9a0b1-aa0a-4b8d-8eb0-2c09ce1149e0": ""
        },
        {
          "95a84e92-b574-46e4-a6a5-afca60a91360": "",
          "0fe96cb4-ee65-43d7-81b8-dab82cdaa1e0": "",
          "c5c9a0b1-aa0a-4b8d-8eb0-2c09ce1149e0": ""
        },
        {
          "95a84e92-b574-46e4-a6a5-afca60a91360": "",
          "0fe96cb4-ee65-43d7-81b8-dab82cdaa1e0": "",
          "c5c9a0b1-aa0a-4b8d-8eb0-2c09ce1149e0": ""
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
| 17.01.1 | Impacto ambiental | tabla | Sí | Llenar tabla |
