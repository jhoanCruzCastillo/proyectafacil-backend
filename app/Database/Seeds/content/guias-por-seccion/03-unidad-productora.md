# SECCIÓN N°03: DIAGNÓSTICO DE LA UNIDAD PRODUCTORA

## Descripción de la sección

- **Qué representa:** el diagnóstico de la Unidad Productora (UP), es decir, del **CIAI**.
- **Objetivo (según instructivo):** analizar las características del Servicio de Cuidado Diurno que se brinda en el CIAI y el estado situacional de sus factores de producción.
- **Qué información contiene:** nombre y código del CIAI; localización; procesos; activos; condiciones técnicas y saneamiento físico-legal; operación y mantenimiento; evolución de la producción; exposición ante peligros.
- **Para qué sirve dentro de la ficha:** describe cómo opera hoy el CIAI, qué activos limitan el servicio y qué riesgos enfrenta.

**Hoja Excel:** `Unidad Productora`

**Subsecciones:** `3.01` … `3.09`

### Contenido (instructivo — Gráfico 5)

1. **3.01** Nombre del CIAI (UP)
2. **3.02** Código de identificación del CIAI
3. **3.03** Localización geográfica de la Unidad Productora
4. **3.04** Diagnóstico de procesos de la Unidad Productora
5. **3.05** Diagnóstico de los activos de la UP
6. **3.06** Condiciones técnicas del local del CIAI
7. **3.07** Prácticas de operación y mantenimiento
8. **3.08** Evolución de la producción del servicio
9. **3.09** Exposición de la UP frente a peligros del área de estudio

**Regla de ejemplos de este documento:** todo bloque de ejemplo es el **objeto `campo` completo** tal como aparece en `JSON EJEMPLO.json` (incluye `id`, `nombre`, `tipo`, `editable`, `captura`, `config`/`columnas`/`niveles` si aplica, y `valor`). No son valores sueltos inventados.

---

# 3.01 Nombre de la Unidad Productora:

Según el instructivo, el nombre del CIAI lo define la Unidad Territorial del PNCM. Se presenta el nombre del CIAI que será intervenido.

---

## Campo 3.01.01 — Nombre de la Unidad Productora

**Tipo:** texto_largo

**Editable:** Sí

**Qué representa:** nombre del CIAI / Unidad Productora.

**Qué debe contener:** el nombre oficial del CIAI intervenido.

**Regla de llenado:** texto largo. El ejemplo sugiere el patrón `CIAI <nombre/localidad>`.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.01.01",
  "nombre": "Nombre de la Unidad Productora",
  "tipo_nodo": "campo",
  "tipo": "texto_largo",
  "editable": true,
  "captura": {
    "columna": "C",
    "fila": 9,
    "abarca_columnas": 11,
    "abarca_filas": 1
  },
  "valor": "CIAI San Antonio"
}
```

---

# 3.02 Código de identificación del CIAI (Local ID) y tipo de CIAI

Según el instructivo, el código lo define la Unidad Territorial del PNCM. Fuente de cobertura referida: `https://www.cunamas.gob.pe/inicio/cobertura-de-servicios/`.

---

## Campo 3.02.01 — Código de CIAI

**Tipo:** numero

**Editable:** Sí

**Qué representa:** código de identificación (Local ID) del CIAI.

**Qué debe contener:** número (en el ejemplo, JSON number).

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.02.01",
  "nombre": "Código de CIAI",
  "tipo_nodo": "campo",
  "tipo": "numero",
  "editable": true,
  "captura": {
    "columna": "D",
    "fila": 14,
    "abarca_columnas": 3,
    "abarca_filas": 1
  },
  "valor": 987654
}
```

---

# 3.03 Localización geográfica de la Unidad Productora

Según el instructivo, precisar la ubicación exacta del CIAI e incluir coordenadas en decimales (latitud y longitud).

**Nota del JSON:** `Para buscar las coordenadas geográficas, puede utilizar la fuente: http://www.mundivideo.com/coordenadas.htm`

### Diferencia importante

Localiza la **UP/CIAI**, no el área de estudio (`2.01`) ni el área de influencia (`2.02`). Incluye columna `coordenadas`.

---

## Campo 3.03.01 — Localización geográfica de la Unidad Productora

**Tipo:** tabla

**Editable:** Sí

**Configuración:** `filas` planas, `columnas` fijas, `agrupador` false, `filas_base` 1.

### Estructura de columnas (schema)

| Columna (id) | Nombre | Tipo |
|---|---|---|
| `numero` | N° | numero |
| `ubigeo` | UBIGEO | texto_corto |
| `departamento` | Departamento | texto_corto |
| `provincia` | Provincia | texto_corto |
| `distrito` | Distrito | texto_corto |
| `localidad` | Localidad / Centro poblado | texto_corto |
| `coordenadas` | Coordenadas geográficas en decimales Latitud y Longitud | coordenadas |

### Observación ESTRUCTURA vs EJEMPLO

- El schema declara `coordenadas` como tipo `coordenadas`.
- En el JSON EJEMPLO, `valor[].coordenadas` aparece como **string** (`"  -13.5407619,   -71.923069"`), no como objeto `{lat,lng}`.
- En el JSON EJEMPLO, `departamento`/`provincia`/`distrito` están vacíos.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.03.01",
  "nombre": "Localización geográfica de la Unidad Productora",
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
    "fila_inicial": 20,
    "filas_base": 1,
    "columnas": [
      {
        "id": "numero",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "ubigeo",
        "columna": "C",
        "abarca_columnas": 2
      },
      {
        "id": "departamento",
        "columna": "E",
        "abarca_columnas": 3
      },
      {
        "id": "provincia",
        "columna": "H",
        "abarca_columnas": 2
      },
      {
        "id": "distrito",
        "columna": "J",
        "abarca_columnas": 2
      },
      {
        "id": "localidad",
        "columna": "L",
        "abarca_columnas": 2
      },
      {
        "id": "coordenadas",
        "columna": "N",
        "abarca_columnas": 4
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "numero",
      "nombre": "N°",
      "tipo": "numero"
    },
    {
      "id": "ubigeo",
      "nombre": "UBIGEO",
      "tipo": "texto_corto"
    },
    {
      "id": "departamento",
      "nombre": "Departamento",
      "tipo": "texto_corto"
    },
    {
      "id": "provincia",
      "nombre": "Provincia",
      "tipo": "texto_corto"
    },
    {
      "id": "distrito",
      "nombre": "Distrito",
      "tipo": "texto_corto"
    },
    {
      "id": "localidad",
      "nombre": "Localidad / Centro poblado",
      "tipo": "texto_corto"
    },
    {
      "id": "coordenadas",
      "nombre": "Coordenadas geográficas en decimales Latitud y Longitud",
      "tipo": "coordenadas"
    }
  ],
  "valor": [
    {
      "numero": 1,
      "ubigeo": "080105",
      "departamento": "",
      "provincia": "",
      "distrito": "",
      "localidad": "San Antonio",
      "coordenadas": "  -13.5407619,   -71.923069"
    }
  ]
}
```

---

# 3.04 Diagnóstico de procesos de la Unidad Productora

Según el instructivo, analizar los cuatro procesos del Servicio de Cuidado Diurno. En la plantilla, `proceso` y `descripcion` vienen precargados; la captura clave es `situacion`.

**Nota del JSON:** Incluir informe de situación actual de las condiciones en las que se brinda el servicio.

---

## Campo 3.04.01 — Caracterización de los procesos de producción del CIAI

**Tipo:** tabla

**Editable:** Sí

**Configuración:** `filas` planas, `columnas` fijas, `agrupador` false, `filas_base` 4.

### Cabecera

```
Caracterización de los procesos de producción del CIAI
├── proceso
├── descripcion
└── situacion
```

### Columnas

| id | Nombre | Tipo |
|---|---|---|
| `numero` | N° | numero |
| `proceso` | Servicio y sus procesos de producción | texto_corto |
| `descripcion` | Descripción ¿En qué consiste el proceso? | texto_largo |
| `situacion` | Situación actual | texto_largo |

**Regla de llenado:** completar `situacion` por proceso con evidencia del CIAI analizado.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.04.01",
  "nombre": "Caracterización de los procesos de producción del CIAI",
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
    "fila_inicial": 28,
    "filas_base": 4,
    "columnas": [
      {
        "id": "numero",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "proceso",
        "columna": "D",
        "abarca_columnas": 2
      },
      {
        "id": "descripcion",
        "columna": "F",
        "abarca_columnas": 3
      },
      {
        "id": "situacion",
        "columna": "I",
        "abarca_columnas": 9
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Caracterización de los procesos de producción del CIAI",
      "hijos": [
        "proceso",
        "descripcion",
        "situacion"
      ]
    }
  ],
  "columnas": [
    {
      "id": "numero",
      "nombre": "N°",
      "tipo": "numero"
    },
    {
      "id": "proceso",
      "nombre": "Servicio y sus procesos de producción",
      "tipo": "texto_corto"
    },
    {
      "id": "descripcion",
      "nombre": "Descripción ¿En qué consiste el proceso?",
      "tipo": "texto_largo"
    },
    {
      "id": "situacion",
      "nombre": "Situación actual",
      "tipo": "texto_largo"
    }
  ],
  "valor": [
    {
      "numero": 1,
      "proceso": "Aprendizaje Infantil: Cuidado y Juego",
      "descripcion": "Consiste en fortalecer las habilidades sensoriales, motrices, comunicativas, cognitivas y socioemocionales de las niñas y los niños, que les permite el desarrollo de su identidad, autonomía, lenguaje, pensamiento y adaptación al medio, protegiéndoles de los posibles efectos de entornos adversos a su desarrollo.",
      "situacion": "En el CIAI San Antonio, actualmente, no existen las condiciones adecuadas para desarrollar las actividades de cuidado y juego, pues las malas condiciones de la infraestructura, mobiliario y equipo limitan el adecuado desarrollo de las tareas previstas. No se cuenta con ambientes suficientemente equipados, con todos los materiales necesarios. Esto ocasiona que sea muy limitado el aprendizaje infantil."
    },
    {
      "numero": 2,
      "proceso": "Cuidado de la salud infantil en el CIAI",
      "descripcion": "Consiste en brindar cuidados a las niñas/niños en el CIAI como el lavado de manos, la higiene bucal, el uso y consumo de agua segura, el suministro de suplementos con hierro, la lactancia materna, como estrategias de prevención de la enfermedad y promoción de la salud. Asimismo, fortalece en las niñas y los niños la autonomía como en el control de esfínteres, descanso y sueño, cambio de ropa, orden del ambiente, entre otros, según el desarrollo y la edad de la niña y el niño.",
      "situacion": "En el CIAI San Antonio, actualmente, se brinda suplemento alimenticio de hierro, a los niños y niñas que presentan cuadros de anemia, y reforzamiento a los niños que vienen superando ese cuadro. Sin embargo, dadas las condiciones de los servicios higiénicos, que están en estado regular y presentan fallas en su diseño, las actividades orientadas al autocuidado e independencia en cuanto al manejo de la salubridad (lavado de manos, control de esfínteres, entre otros) se ve muy limitada. "
    },
    {
      "numero": 3,
      "proceso": "Atención alimentaria y nutricional",
      "descripcion": "Consiste en atender las necesidades básicas de alimentación y nutrición mediante la dotación de una alimentación balanceada, saludable e inocua, de acuerdo a sus necesidades nutricionales según grupo etario de las niñas y los niños, que contribuyen con un adecuado estado nutricional para favorecer su óptimo crecimiento y desarrollo.",
      "situacion": "Actualmente, no se brinda la atención alimentaria en el centro de manera adecuada. Los alimentos los traen tres veces al día, del CIAI Antonio Lorena, que está ubicado a una distancia de 6KM del CIAI San Antonio. Los alimentos no conservan el 100% de su valor nutricional."
    },
    {
      "numero": 4,
      "proceso": "Fortalecimiento de Prácticas de cuidado saludable y aprendizaje en la familia usuaria",
      "descripcion": "Consiste en fortalecer las prácticas de cuidado saludable y aprendizaje (interacción, juego y comunicación) de la madre, padre u otro/a cuidador/a principal que permita dar una respuesta flexible, adecuada y pertinente de acuerdo a las necesidades e intereses de las niñas y los niños, a fin de favorecer su desarrollo integral. En las actividades se promueve la participación de uno o más miembros de la familia se encargan o son responsables del cuidado de la niña o el niño.",
      "situacion": "Las reuniones con los padres de familia de los niños y niñas que asisten al CIAI se realiza de manera trimestral y cuenta con una participación masiva. El equipo técnico del PNCM brinda las asesorías adecuadas a las madres cuidadoras."
    }
  ]
}
```

---

# 3.05 Diagnóstico de los activos de la UP

Según el instructivo: evaluar infraestructura, mobiliario, equipos y capacidades; indicar norma PNCM, cumplimiento Sí/No y estado situacional. Identificar factores limitantes.

Tabla **jerárquica** (`config.filas = "jerarquicas"`).

### Cabecera

```
Cumple con los estándares de calidad del PNCM
├── norma
└── cumple_sino
```

### Niveles

| id | Nombre | Tipo |
|---|---|---|
| `servicio` | Servicio y procesos de producción | texto_largo |
| `factor` | Tipo de Factor productivo | texto_corto |
| `activo` | Activos estratégicos | texto_corto |
| `norma` | Norma técnica | texto_largo |
| `cumple_sino` | Sí / No | booleano (`true`=`Sí`, `false`=`No`) |
| `estado` | Estado Situacional | texto_largo |

**Observación:** en el JSON EJEMPLO, `cumple_sino` aparece como string `"Sí"`/`"No"`, no como booleano nativo.

---

## Campo 3.05.01 — Diagnóstico de los activos de la UP

**Tipo:** tabla (jerárquica)

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.05.01",
  "nombre": "Diagnóstico de los activos de la UP",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "jerarquicas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "fila_inicial": 41,
    "filas_base": 11,
    "columnas": [
      {
        "id": "servicio",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "factor",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "activo",
        "columna": "E",
        "abarca_columnas": 2
      },
      {
        "id": "norma",
        "columna": "G",
        "abarca_columnas": 2
      },
      {
        "id": "cumple_sino",
        "columna": "I",
        "abarca_columnas": 1
      },
      {
        "id": "estado",
        "columna": "J",
        "abarca_columnas": 8
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Cumple con los estándares de calidad del PNCM",
      "hijos": [
        "norma",
        "cumple_sino"
      ]
    }
  ],
  "niveles": [
    {
      "id": "servicio",
      "nombre": "Servicio y procesos de producción",
      "tipo": "texto_largo",
      "combina_vertical": true
    },
    {
      "id": "factor",
      "nombre": "Tipo de Factor productivo",
      "tipo": "texto_corto",
      "combina_vertical": true
    },
    {
      "id": "activo",
      "nombre": "Activos estratégicos",
      "tipo": "texto_corto"
    },
    {
      "id": "norma",
      "nombre": "Norma técnica",
      "tipo": "texto_largo"
    },
    {
      "id": "cumple_sino",
      "nombre": "Sí / No",
      "tipo": "booleano",
      "etiquetas": {
        "true": "Sí",
        "false": "No"
      }
    },
    {
      "id": "estado",
      "nombre": "Estado Situacional",
      "tipo": "texto_largo"
    }
  ],
  "valor": [
    {
      "servicio": "Servicio de cuidado diurno:\n\nProcesos:\n\n1. Aprendizaje Infantil\n\n2. Cuidado y JuegoCuidado de la salud infantil en el CIAI\n\n3. Atención alimentaria y nutricional\n\n4. Fortalecimiento de Prácticas de cuidado saludable y aprendizaje en la familia usuaria",
      "hijos": [
        {
          "factor": "Infraestructura",
          "hijos": [
            {
              "activo": "Sala de cuidado diurno",
              "hijos": [
                {
                  "norma": "Directiva “Intervención en la infraestructura de locales del servicio de cuidado diurno del Programa Nacional Cuna Más”",
                  "hijos": [
                    {
                      "cumple_sino": "No",
                      "hijos": [
                        {
                          "estado": "Las salas existentes no cumplen con la funcionalidad para brindar el servicio de cuidado diurno. Son amnbientes habilitados, que no cumplen las normas del PNCM"
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "activo": "Sala de usos múltiples",
              "hijos": [
                {
                  "norma": "Directiva “Intervención en la infraestructura de locales del servicio de cuidado diurno del Programa Nacional Cuna Más”",
                  "hijos": [
                    {
                      "cumple_sino": "No",
                      "hijos": [
                        {
                          "estado": "No cuenta con sala de uso múltiples"
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "activo": "Ambiente de recreación activa",
              "hijos": [
                {
                  "norma": "Directiva “Intervención en la infraestructura de locales del servicio de cuidado diurno del Programa Nacional Cuna Más”",
                  "hijos": [
                    {
                      "cumple_sino": "No",
                      "hijos": [
                        {
                          "estado": "Se encuentra en regular estado, aunque no cumple con las áreas mínimas definidas para este tipo de ambiente"
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "activo": "Ambiente de servicios generales",
              "hijos": [
                {
                  "norma": "Directiva “Intervención en la infraestructura de locales del servicio de cuidado diurno del Programa Nacional Cuna Más”",
                  "hijos": [
                    {
                      "cumple_sino": "Sí",
                      "hijos": [
                        {
                          "estado": "Se encuentra en buen estado"
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "activo": "Ambiente de preparación y expendio de alimentos",
              "hijos": [
                {
                  "norma": "Directiva “Intervención en la infraestructura de locales del servicio de cuidado diurno del Programa Nacional Cuna Más”",
                  "hijos": [
                    {
                      "cumple_sino": "No",
                      "hijos": [
                        {
                          "estado": "No cuenta con ambientes para preparación y expendio de alimentos"
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "activo": "Almacén",
              "hijos": [
                {
                  "norma": "Directiva “Intervención en la infraestructura de locales del servicio de cuidado diurno del Programa Nacional Cuna Más”",
                  "hijos": [
                    {
                      "cumple_sino": "No",
                      "hijos": [
                        {
                          "estado": "No cuenta con almacén"
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "activo": "Cerco perimétrico",
              "hijos": [
                {
                  "norma": "",
                  "hijos": [
                    {
                      "cumple_sino": "No",
                      "hijos": [
                        {
                          "estado": "Se tiene un cerco perimétrico, a punto de colapsar"
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "activo": "Muro de contención",
              "hijos": [
                {
                  "norma": "Directiva “Intervención en la infraestructura de locales del servicio de cuidado diurno del Programa Nacional Cuna Más”",
                  "hijos": [
                    {
                      "cumple_sino": "No",
                      "hijos": [
                        {
                          "estado": "No es necesario"
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "factor": "Mobiliario",
          "hijos": [
            {
              "activo": "Mobiliario de sala de cuidado diurno",
              "hijos": [
                {
                  "norma": "Directiva “Equipamiento y Dotación de Materiales para la Prestación de los Servicios en el marco del Modelo de Cogestión Comunal del Programa Nacional Cuna Más” .",
                  "hijos": [
                    {
                      "cumple_sino": "No",
                      "hijos": [
                        {
                          "estado": "Cuenta con mobiliario en regular estado. Sin embargo, ya venció su vida útil"
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "activo": "Mobiliario de ambiente de preparación y expendio de alimentos- Cuna Más",
              "hijos": [
                {
                  "norma": "Directiva “Equipamiento y Dotación de Materiales para la Prestación de los Servicios en el marco del Modelo de Cogestión Comunal del Programa Nacional Cuna Más” .",
                  "hijos": [
                    {
                      "cumple_sino": "No",
                      "hijos": [
                        {
                          "estado": "Cuenta con mobiliario en regular estado. Sin embargo, ya venció su vida útil"
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "factor": "Equipo",
          "hijos": [
            {
              "activo": "Equipo de ambiente de preparación y expendio de alimentos - Cuna Más",
              "hijos": [
                {
                  "norma": "Directiva “Equipamiento y Dotación de Materiales para la Prestación de los Servicios en el marco del Modelo de Cogestión Comunal del Programa Nacional Cuna Más” .",
                  "hijos": [
                    {
                      "cumple_sino": "No",
                      "hijos": [
                        {
                          "estado": "No cuenta con equipos para preparación y expendio de alimentos"
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

---

# 3.06 Condiciones técnicas del local del CIAI

Según el instructivo, verificar condiciones del entorno, servicios básicos y saneamiento físico-legal frente a normativa PNCM vigente.

- **A.** Condiciones técnicas del local
  - Entorno: `3.06.1` … `3.06.06`
  - Servicios del CIAI: `3.06.07` … `3.06.09`
- **B.** Saneamiento físico legal: `3.06.10`, `3.06.11`

### Observación sobre booleanos del JSON EJEMPLO

En el JSON EJEMPLO, `3.06.1`–`3.06.09` tienen `valor: false`. El instructivo muestra otro juego de respuestas en su ejemplo impreso (Sí/No distintos). Aquí se prioriza el objeto del JSON EJEMPLO.

---

## Campo 3.06.1 — ¿El CIAI está alejado de agentes contaminantes o peligros que no puedan ser mitigados?

**Tipo:** booleano (`true`=`Sí`, `false`=`No`)

**Editable:** Sí

**Nota:** el ID es `3.06.1` (no `3.06.01`).

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.1",
  "nombre": "¿El CIAI está alejado de agentes contaminantes o peligros que no puedan ser mitigados?",
  "tipo_nodo": "campo",
  "tipo": "booleano",
  "editable": true,
  "etiquetas": {
    "true": "Sí",
    "false": "No"
  },
  "captura": {
    "columna": "I",
    "fila": 60,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": false
}
```

---

## Campo 3.06.02 — ¿El CIAI está a una distancia no menor de 50 metros de estaciones de expendio de combustible?

**Tipo:** booleano

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.02",
  "nombre": "¿El CIAI está a una distancia no menor de 50 metros de estaciones de expendio de combustible?",
  "tipo_nodo": "campo",
  "tipo": "booleano",
  "editable": true,
  "etiquetas": {
    "true": "Sí",
    "false": "No"
  },
  "captura": {
    "columna": "I",
    "fila": 62,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": false
}
```

---

## Campo 3.06.03 — ¿El CIAI está a una distancia no menor a 25 metros de una línea de alta tensión eléctrica?

**Tipo:** booleano

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.03",
  "nombre": "¿El CIAI está a una distancia no menor a 25 metros de una línea de alta tensión eléctrica?",
  "tipo_nodo": "campo",
  "tipo": "booleano",
  "editable": true,
  "etiquetas": {
    "true": "Sí",
    "false": "No"
  },
  "captura": {
    "columna": "I",
    "fila": 64,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": false
}
```

---

## Campo 3.06.04 — ¿El CIAI es colindante con hospitales, centros médicos, centros de atención de salud, o similares, de categoría I-3, categoría I-4, categoría II o categoría III?

**Tipo:** booleano

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.04",
  "nombre": "¿El CIAI es colindante con hospitales, centros médicos, centros de atención de salud, o similares, de categoría I-3, categoría I-4, categoría II o categoría III?",
  "tipo_nodo": "campo",
  "tipo": "booleano",
  "editable": true,
  "etiquetas": {
    "true": "Sí",
    "false": "No"
  },
  "captura": {
    "columna": "I",
    "fila": 66,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": false
}
```

---

## Campo 3.06.05 — ¿Las salas del CIAI se ubican en el primer piso?

**Tipo:** booleano

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.05",
  "nombre": "¿Las salas del CIAI se ubican en el primer piso?",
  "tipo_nodo": "campo",
  "tipo": "booleano",
  "editable": true,
  "etiquetas": {
    "true": "Sí",
    "false": "No"
  },
  "captura": {
    "columna": "I",
    "fila": 68,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": false
}
```

---

## Campo 3.06.06 — ¿Las puertas de ingreso y salida del CIAI estan orientados directamente a una vía de alto tránsito vehicular?

**Tipo:** booleano

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.06",
  "nombre": "¿Las puertas de ingreso y salida del CIAI están orientados directamente a una vía de alto tránsito vehicular?",
  "tipo_nodo": "campo",
  "tipo": "booleano",
  "editable": true,
  "etiquetas": {
    "true": "Sí",
    "false": "No"
  },
  "captura": {
    "columna": "I",
    "fila": 70,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": false
}
```

---

## Campo 3.06.07 — Abastecimiento de Agua: El CIAI cuenta con conexión a una red pública

**Tipo:** booleano

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.07",
  "nombre": "Abastecimiento de Agua: El CIAI cuenta con conexión a una red pública",
  "tipo_nodo": "campo",
  "tipo": "booleano",
  "editable": true,
  "etiquetas": {
    "true": "Sí",
    "false": "No"
  },
  "captura": {
    "columna": "I",
    "fila": 74,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": false
}
```

---

## Campo 3.06.08 — Desagüe: El CIAI cuenta con conexión a una red pública de alcantarillado

**Tipo:** booleano

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.08",
  "nombre": "Desagüe: El CIAI cuenta con conexión a una red pública de alcantarillado",
  "tipo_nodo": "campo",
  "tipo": "booleano",
  "editable": true,
  "etiquetas": {
    "true": "Sí",
    "false": "No"
  },
  "captura": {
    "columna": "I",
    "fila": 76,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": false
}
```

---

## Campo 3.06.09 — Energía eléctrica: El CIAI cuenta con conexión a la red pública de abastecimiento de energía eléctrica

**Tipo:** booleano

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.09",
  "nombre": "Energía eléctrica: El CIAI cuenta con conexión a la red pública de abastecimiento de energía eléctrica",
  "tipo_nodo": "campo",
  "tipo": "booleano",
  "editable": true,
  "etiquetas": {
    "true": "Sí",
    "false": "No"
  },
  "captura": {
    "columna": "I",
    "fila": 78,
    "abarca_columnas": 1,
    "abarca_filas": 1
  },
  "valor": false
}
```

---

## Campo 3.06.10 — Titularidad del local donde funciona el CIAI

**Tipo:** texto_corto

**Editable:** Sí

**Valores permitidos (`etiquetas`):**

- Propiedad del Estado
- Propiedad de particulares (privados)

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.10",
  "nombre": "Titularidad del local donde funciona el CIAI",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "etiquetas": [
    "Propiedad del Estado",
    "Propiedad de particulares (privados)"
  ],
  "captura": {
    "columna": "G",
    "fila": 83,
    "abarca_columnas": 3,
    "abarca_filas": 1
  },
  "valor": "Propiedad del Estado"
}
```

---

## Campo 3.06.11 — Situación actual del saneamiento físico legal o arreglo institucional

**Tipo:** texto_corto

**Editable:** Sí

**Valores permitidos:** ver arreglo `etiquetas` dentro del objeto de ejemplo (catálogo cerrado).

**Regla de llenado:** elegir exactamente una opción del catálogo.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.06.11",
  "nombre": "Situación actual del saneamiento físico legal o arreglo institucional",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "etiquetas": [
    "Inscrito en Registros Públicos a favor del PNCM",
    "Caso Gobierno Regional: Resolución y/o acuerdo de consejo que aprueba la afectación a favor o transferencia a favor del PNCM",
    "Caso Municipalidad: Resolución de alcaldía y/o acuerdo de consejo que aprueba la transferencia o afectación en uso a favor del PNCM",
    "Caso Comunidad: Acta de trasferencia o cesión en uso a favor del PNCM, o a favor de una municipalidad, o Gobierno Regional que posteriormente se comprometan a transferirlo a favor de PNCM.",
    "Carta de intención del propietario, declarando su voluntad de vender y se especifique el área y costo por m2, o una intención de donación",
    "Acta de compromiso de la UEI de gestionar el Saneamiento Físico Legal del Terreno antes del inicio de la elaboración del Expediente Técnico"
  ],
  "captura": {
    "columna": "G",
    "fila": 85,
    "abarca_columnas": 3,
    "abarca_filas": 1
  },
  "valor": "Caso Gobierno Regional: Resolución y/o acuerdo de consejo que aprueba la afectación a favor o transferencia a favor del PNCM "
}
```

---

# 3.07 Detallar las prácticas de operación y mantenimiento del CIAI, en la situación actual

Según el instructivo: describir operación sin proyecto (actores comunales + equipo PNCM) y costos anuales de personal, servicios y mantenimiento.

Ambas tablas son **jerárquicas** con `agrupador: true`. La columna `total` es **calculada** (`formula: cantidad*costo`) → no llenar el total manualmente.

---

## Campo 3.07.01 — a. Personal

**Tipo:** tabla (jerárquica)

**Editable:** Sí

**Configuración:** `filas` jerarquicas, `agrupador` true, `agrupador_abarca_columnas` 4, `filas_base` 11.

### Niveles

| id | Nombre | Tipo |
|---|---|---|
| `descripcion` | Descripción de las prácticas de operación, en la situación sin proyecto | texto_largo |
| `detalle` | Detalle | texto_corto |
| `cantidad` | Cantidad | decimal |
| `costo` | Costo | decimal |
| `total` | Total | calculado (`cantidad*costo`) |

**Observación:** en el JSON EJEMPLO, `cantidad`/`costo` aparecen como strings aunque el schema declare decimal; `total` queda `""`.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.07.01",
  "nombre": "a. Personal",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "jerarquicas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1,
    "agrupador_abarca_columnas": 4,
    "agrupador_nivel": 1
  },
  "captura": {
    "fila_inicial": 94,
    "filas_base": 11,
    "columnas": [
      {
        "id": "descripcion",
        "columna": "I",
        "abarca_columnas": 9
      },
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
  "niveles": [
    {
      "id": "descripcion",
      "nombre": "Descripción de las prácticas de operación, en la situación sin proyecto",
      "tipo": "texto_largo",
      "combina_vertical": true
    },
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
      "tipo": "calculado",
      "formula": "cantidad*costo"
    }
  ],
  "valor": [
    {
      "descripcion": "La gestión del CIAI San Antonio está a cargo del comité Mamitas Cuidadoras, que pertenece a la UT Cusco. Se tiene 11 actores comunales que participan en el servicio de cuidado diurno. \nAdemás, el PNCM acopa;a y asiste al comité de gestión y a los actores comunales, con un equipo conformado por tres profesionales.",
      "hijos": [
        {
          "detalle": "·       Actores comunales:",
          "descripcion": "La gestión del CIAI San Antonio está a cargo del comité Mamitas Cuidadoras, que pertenece a la UT Cusco. Se tiene 11 actores comunales que participan en el servicio de cuidado diurno. \nAdemás, el PNCM acopa;a y asiste al comité de gestión y a los actores comunales, con un equipo conformado por tres profesionales.",
          "hijos": [
            {
              "detalle": "o   Madre cuidadora",
              "hijos": [
                {
                  "cantidad": "2",
                  "hijos": [
                    {
                      "costo": "7800",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Madre guía",
              "hijos": [
                {
                  "cantidad": "6",
                  "hijos": [
                    {
                      "costo": "7800",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Guía de Familia",
              "hijos": [
                {
                  "cantidad": "1",
                  "hijos": [
                    {
                      "costo": "7800",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Socia de cocina",
              "hijos": [
                {
                  "cantidad": "2",
                  "hijos": [
                    {
                      "costo": "7800",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Repartidor",
              "hijos": [
                {
                  "cantidad": "",
                  "hijos": [
                    {
                      "costo": "",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Apoyo de Limpieza y Vigilancia",
              "hijos": [
                {
                  "cantidad": "1",
                  "hijos": [
                    {
                      "costo": "14400",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "detalle": "·       Equipo técnico profesional del PNCM: ",
          "hijos": [
            {
              "detalle": "o   Acompañantes técnicos (AT)",
              "hijos": [
                {
                  "cantidad": "1",
                  "hijos": [
                    {
                      "costo": "54000",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Especialistas integrales",
              "hijos": [
                {
                  "cantidad": "1",
                  "hijos": [
                    {
                      "costo": "54000",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Especialistas en nutrición",
              "hijos": [
                {
                  "cantidad": "1",
                  "hijos": [
                    {
                      "costo": "54000",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

---

## Campo 3.07.02 — b. Servicios y mantenimiento

**Tipo:** tabla (jerárquica)

**Editable:** Sí

**Configuración:** mismo patrón que `3.07.01`; `filas_base` 6.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.07.02",
  "nombre": "b. Servicios y mantenimiento",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "jerarquicas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1,
    "agrupador_abarca_columnas": 4,
    "agrupador_nivel": 1
  },
  "captura": {
    "fila_inicial": 110,
    "filas_base": 6,
    "columnas": [
      {
        "id": "descripcion",
        "columna": "I",
        "abarca_columnas": 9
      },
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
  "niveles": [
    {
      "id": "descripcion",
      "nombre": "Descripción de las condiciones del mantenimiento y los servicios del CIAI, en la situación sin proyecto",
      "tipo": "texto_largo",
      "combina_vertical": true
    },
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
      "tipo": "calculado",
      "formula": "cantidad*costo"
    }
  ],
  "valor": [
    {
      "descripcion": "El pago de los servicios lo realiza el PNCM, a través del comité de gestión del CIAI. Entre las actividades de mantenimeinto anual realizadas, se tiene el pintado de paredes y techos, reposición de cobertura dañana del techo, resane de baños, entre otros. Las actividades de mantenimiento se rigen de acuerdo a lo estipulado en las normas del PNCM.",
      "hijos": [
        {
          "detalle": "·       Servicios",
          "descripcion": "El pago de los servicios lo realiza el PNCM, a través del comité de gestión del CIAI. Entre las actividades de mantenimeinto anual realizadas, se tiene el pintado de paredes y techos, reposición de cobertura dañana del techo, resane de baños, entre otros. Las actividades de mantenimiento se rigen de acuerdo a lo estipulado en las normas del PNCM.",
          "hijos": [
            {
              "detalle": "o   Agua y alcantarillado",
              "hijos": [
                {
                  "cantidad": "1",
                  "hijos": [
                    {
                      "costo": "1440",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Energía eléctirca",
              "hijos": [
                {
                  "cantidad": "1",
                  "hijos": [
                    {
                      "costo": "2640",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Internet",
              "hijos": [
                {
                  "cantidad": "",
                  "hijos": [
                    {
                      "costo": "",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Seguridad",
              "hijos": [
                {
                  "cantidad": "",
                  "hijos": [
                    {
                      "costo": "",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "detalle": "o   Mantenimiento",
              "hijos": [
                {
                  "cantidad": "1",
                  "hijos": [
                    {
                      "costo": "8500",
                      "hijos": [
                        {
                          "total": ""
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

---

# 3.08 Evolución del nivel de producción de servicio de cuidado diurno provisto en el CIAI

Según el instructivo: número de niñas y niños atendidos en los últimos cinco años (`Año -5` … `Año -1`), según registros del Sistema Integrado Cuna Más.

---

## Campo 3.08.01 — Evolución del nivel de producción de servicio de cuidado diurno provisto en el CIAI

**Tipo:** tabla

**Editable:** Sí

**Configuración:** `filas` planas, `columnas` fijas, `filas_base` 1.

### Columnas

| id | Nombre | Tipo |
|---|---|---|
| `servicio` | Servicios | texto_corto |
| `unidad` | Unidad de Medida | texto_corto |
| `anio_m5` | Año -5 | numero |
| `anio_m4` | Año -4 | numero |
| `anio_m3` | Año -3 | numero |
| `anio_m2` | Año -2 | numero |
| `anio_m1` | Año -1 | numero |

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.08.01",
  "nombre": "Evolución del nivel de producción de servicio de cuidado diurno provisto en el CIAI",
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
    "fila_inicial": 121,
    "filas_base": 1,
    "columnas": [
      {
        "id": "servicio",
        "columna": "B",
        "abarca_columnas": 3
      },
      {
        "id": "unidad",
        "columna": "E",
        "abarca_columnas": 1
      },
      {
        "id": "anio_m5",
        "columna": "F",
        "abarca_columnas": 1
      },
      {
        "id": "anio_m4",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "anio_m3",
        "columna": "H",
        "abarca_columnas": 1
      },
      {
        "id": "anio_m2",
        "columna": "I",
        "abarca_columnas": 1
      },
      {
        "id": "anio_m1",
        "columna": "J",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "servicio",
      "nombre": "Servicios",
      "tipo": "texto_corto"
    },
    {
      "id": "unidad",
      "nombre": "Unidad de Medida",
      "tipo": "texto_corto"
    },
    {
      "id": "anio_m5",
      "nombre": "Año -5",
      "tipo": "numero"
    },
    {
      "id": "anio_m4",
      "nombre": "Año -4",
      "tipo": "numero"
    },
    {
      "id": "anio_m3",
      "nombre": "Año -3",
      "tipo": "numero"
    },
    {
      "id": "anio_m2",
      "nombre": "Año -2",
      "tipo": "numero"
    },
    {
      "id": "anio_m1",
      "nombre": "Año -1",
      "tipo": "numero"
    }
  ],
  "valor": [
    {
      "servicio": "Servicio de cuidado diurno",
      "unidad": "Niñas y niños",
      "anio_m5": 60,
      "anio_m4": 60,
      "anio_m3": 60,
      "anio_m2": 60,
      "anio_m1": 60
    }
  ]
}
```

---

# 3.09 Estimar la exposición de la UP frente a los peligros identificados en el diagnóstico del área de estudio

Según el instructivo: para cada peligro del área de estudio, indicar exposición (Alto/Medio/Bajo), fragilidad (Alto/Medio/Bajo) y plan de contingencia (Sí/No).

### Relación con 2.05

Debe alinearse con los peligros identificados en la sección 02. En el JSON EJEMPLO, varias filas tienen `peligro` vacío pero sí traen `exposicion`/`fragilidad`/`plan_sino`.

---

## Campo 3.09.01 — Estimar la exposición de la UP frente a los peligros identificados en el diagnóstico del área de estudio

**Tipo:** tabla

**Editable:** Sí

**Configuración:** `filas` planas, `columnas` fijas, `filas_base` 16.

### Columnas

| id | Nombre | Tipo |
|---|---|---|
| `peligro` | Peligros | texto_corto |
| `exposicion` | ¿Cuál es el nivel de exposición del CIAI al peligro? | texto_corto (etiquetas: Bajo, Medio, Alto) |
| `fragilidad` | ¿Cuál es el nivel de fragilidad del CIAI ante la ocurrencia del peligro? | texto_corto (etiquetas: Bajo, Medio, Alto) |
| `plan_sino` | ¿Se cuenta con un plan de contingencia…? | booleano (`true`=`Sí`, `false`=`No`) |

**Observación:** en el JSON EJEMPLO, `plan_sino` aparece como string `"No"` en las filas llenas.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "3.09.01",
  "nombre": "Estimar la exposición de la UP frente a los peligros identificados en el diagnóstico del área de estudio",
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
    "fila_inicial": 128,
    "filas_base": 16,
    "columnas": [
      {
        "id": "peligro",
        "columna": "B",
        "abarca_columnas": 5
      },
      {
        "id": "exposicion",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "fragilidad",
        "columna": "H",
        "abarca_columnas": 1
      },
      {
        "id": "plan_sino",
        "columna": "I",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "peligro",
      "nombre": "Peligros",
      "tipo": "texto_corto"
    },
    {
      "id": "exposicion",
      "nombre": "¿Cuál es el nivel de exposición del CIAI al peligro?",
      "tipo": "texto_corto",
      "etiquetas": [
        "Bajo",
        "Medio",
        "Alto"
      ]
    },
    {
      "id": "fragilidad",
      "nombre": "¿Cuál es el nivel de fragilidad del CIAI ante la ocurrencia del peligro?",
      "tipo": "texto_corto",
      "etiquetas": [
        "Bajo",
        "Medio",
        "Alto"
      ]
    },
    {
      "id": "plan_sino",
      "nombre": "¿Se cuenta con un plan de contingencia ante la interrupción o alteración del servicio ocasionado por el peligro?",
      "tipo": "booleano",
      "etiquetas": {
        "true": "Sí",
        "false": "No"
      }
    }
  ],
  "valor": [
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "Alto",
      "fragilidad": "Medio",
      "plan_sino": "No"
    },
    {
      "peligro": "",
      "exposicion": "Alto",
      "fragilidad": "Bajo",
      "plan_sino": "No"
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "Alto",
      "fragilidad": "Medio",
      "plan_sino": "No"
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "Alto",
      "fragilidad": "Bajo",
      "plan_sino": "No"
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    },
    {
      "peligro": "",
      "exposicion": "",
      "fragilidad": "",
      "plan_sino": ""
    }
  ]
}
```

---

## Resumen de acción para autollenado (Sección 03)

| ID | Nombre | Tipo | Editable | Acción sugerida |
|---|---|---|---|---|
| 3.01.01 | Nombre de la Unidad Productora | texto_largo | Sí | Llenar |
| 3.02.01 | Código de CIAI | numero | Sí | Llenar |
| 3.03.01 | Localización geográfica de la UP | tabla | Sí | Llenar |
| 3.04.01 | Caracterización de procesos | tabla | Sí | Llenar sobre todo `situacion` |
| 3.05.01 | Diagnóstico de activos | tabla jerárquica | Sí | Llenar norma / cumple / estado |
| 3.06.1–3.06.09 | Condiciones técnicas | booleano | Sí | Llenar |
| 3.06.10 | Titularidad | texto_corto | Sí | Catálogo |
| 3.06.11 | Saneamiento físico legal | texto_corto | Sí | Catálogo |
| 3.07.01 | a. Personal | tabla jerárquica | Sí | descripcion + cantidad/costo; NO total |
| 3.07.02 | b. Servicios y mantenimiento | tabla jerárquica | Sí | idem |
| 3.08.01 | Evolución del nivel de producción | tabla | Sí | Llenar años |
| 3.09.01 | Exposición frente a peligros | tabla | Sí | Llenar |
