# GUÍA DE LLENADO — I. ASPECTOS GENERALES

*(FTE Saneamiento Urbano — numerales 1.1 a 1.4, 18 campos)*

Esta sección fija la identidad del proyecto: quién lo formula, bajo qué cadena funcional, cómo se
llama y a qué brecha se alinea. Tres de sus datos **no se deducen de la evidencia**: la cadena
funcional, el texto de los indicadores y la fórmula del nombre. Eso es lo que más se equivoca.

---

## 1.1 Institucionalidad

Dos bloques: **Unidad Formuladora** (`1.01.x`) y **Unidad Ejecutora de Inversiones recomendada**
(`1.02.x`). Cada uno con nivel de gobierno, entidad, nombre del órgano y responsable.

- La **UF** es el órgano que formula y evalúa el PI mediante la FTE. Debe estar registrada en el
  Banco de Inversiones y tener competencia legal para formular.
- La **UEI** es el órgano que ejecuta. Si hay más de una UEI a cargo, se consignan los responsables
  de cada una.
- `1.01.5` **Responsable de formular el proyecto** no es lo mismo que `1.01.4` **Responsable de la
  UF**: el primero es quien firma la formulación (a menudo un consultor o un profesional del equipo),
  el segundo es el titular del órgano. Si el expediente solo da uno, llena el que corresponda y deja
  el otro vacío — no lo dupliques por simetría.
- **Nivel de gobierno** es Nacional, Regional o Local. **Entidad** es el pliego (la municipalidad, el
  gobierno regional, la EPS según corresponda).

---

## 1.2 Responsabilidad funcional — se copia, no se deduce

Los cuatro campos son **siempre los mismos** para toda la tipología de saneamiento urbano:

| Campo | Valor |
|---|---|
| `1.03.1` Función | **18 SANEAMIENTO** |
| `1.03.2` División funcional | **040 SANEAMIENTO** |
| `1.03.3` Grupo funcional | **0088 SANEAMIENTO URBANO** |
| `1.03.4` Sector responsable | **VIVIENDA, CONSTRUCCIÓN Y SANEAMIENTO** |

Sale del Anexo N.° 02 de la Directiva N.° 001-2019-EF/63.01. Si el expediente del cliente dice otra
cosa, el expediente está mal: van estos valores.

---

## 1.3 Nombre del proyecto

### `1.04.1` — el nombre, con fórmula

> **naturaleza de intervención + objeto de intervención (servicios) + sistemas + localización**

La localización es el centro poblado urbano, el distrito, la provincia y la región. Ejemplo del
instructivo:

> "MEJORAMIENTO Y AMPLIACIÓN DE LOS SERVICIOS DE AGUA POTABLE Y SANEAMIENTO DE LOS SISTEMAS DE AGUA
> POTABLE, ALCANTARILLADO Y TRATAMIENTO DE AGUAS RESIDUALES DEL CCPP CHINCHA ALTA, DISTRITO DE
> CHINCHA ALTA, PROVINCIA DE CHINCHA, REGIÓN ICA"

Se escribe en mayúsculas y sin abreviar los servicios. Si falta alguno de los cuatro elementos, el
nombre está mal formado.

### `1.04.2` — naturaleza y objeto de intervención

**Una fila por cada par servicio/sistema que interviene el proyecto.** Si el proyecto toca agua
potable, alcantarillado y tratamiento, son **tres filas**, no una con todo junto. Cada fila repite la
naturaleza que le corresponde.

| naturaleza | servicio | sistema |
|---|---|---|
| AMPLIACIÓN Y MEJORAMIENTO | SERVICIO DE AGUA POTABLE | SISTEMA DE AGUA POTABLE |
| AMPLIACIÓN Y MEJORAMIENTO | SERVICIO DE SANEAMIENTO | SISTEMA DE ALCANTARILLADO SANITARIO |
| AMPLIACIÓN Y MEJORAMIENTO | SERVICIO DE SANEAMIENTO | SISTEMA DE TRATAMIENTO DE AGUAS RESIDUALES |

Las cuatro naturalezas posibles son **creación**, **ampliación**, **mejoramiento** y
**recuperación**, y pueden combinarse. Ojo con la diferencia: *ampliación* es capacidad para nuevos
usuarios; *mejoramiento* es más calidad para los que ya tienen el servicio; *recuperación* es que los
activos colapsaron o fueron dañados; *creación* es que la UP no existe.

### `1.04.3` — localización

Una fila por centro poblado urbano (AA.HH. / Urbanización / Sector / Localidad), con departamento,
provincia, distrito y **Ubigeo**. Si el centro poblado no tiene Ubigeo propio, se usa el del distrito.

### `1.04.4`

Solo se llena si el proyecto interviene en **más distritos o localidades** de las que caben en la
tabla anterior. Si la tabla ya los cubre, va vacío.

---

## 1.4 Alineamiento y contribución al cierre de brecha

### `1.05.1` — los indicadores

La lista es **cerrada**: cinco indicadores, tres de cobertura y dos de calidad. Se copia el texto
exacto; no se redacta una variante ni se mezclan dos.

| Servicio | Tipo | Indicador | Unidad |
|---|---|---|---|
| AGUA POTABLE | COBERTURA | PORCENTAJE DE LA POBLACIÓN URBANA SIN ACCESO AL SERVICIO DE AGUA POTABLE MEDIANTE RED PÚBLICA O PILETA PÚBLICA | Personas |
| AGUA POTABLE | CALIDAD | PORCENTAJE DE VIVIENDAS URBANAS CON SERVICIO DE AGUA CON CLORO RESIDUAL MENOR AL LÍMITE PERMISIBLE (0.5 MG/L) | Viviendas |
| AGUA POTABLE | CALIDAD | PORCENTAJE DE POBLACIÓN URBANA QUE NO TIENE CONTINUIDAD DEL SERVICIO DE AGUA POTABLE | Personas |
| SANEAMIENTO | COBERTURA | PORCENTAJE DE LA POBLACIÓN URBANA SIN ACCESO A SERVICIOS DE SANEAMIENTO MEDIANTE ALCANTARILLADO U OTRAS FORMAS DE DISPOSICIÓN SANITARIA DE EXCRETAS | Personas |
| SANEAMIENTO | COBERTURA | PORCENTAJE DE VOLUMEN DE AGUAS RESIDUALES NO TRATADAS | M3 |

**Cuáles van:** solo los que corresponden a lo que el proyecto efectivamente hace.
Creación y ampliación aportan a **cobertura**; mejoramiento y recuperación, a **calidad**. Un
proyecto de "ampliación y mejoramiento" de los tres sistemas puede alinearse a los cinco.

**Nivel de desagregación** suele ser *Distrital*.

**`anio` y `valor` son columnas CALCULADAS** — el Excel las trae del numeral 2.6. No las escribas:
el valor de contribución sale del cálculo de aporte al cierre de brecha, no al revés.

---

## Dónde está la evidencia

- **UF / UEI / responsables**: resolución o documento de designación, ficha institucional del
  expediente.
- **Nombre del proyecto**: se construye; si el expediente trae un nombre que no sigue la fórmula,
  reconstrúyelo con la fórmula.
- **Localización y Ubigeo**: INEI (códigos de centro poblado); padrón del prestador.
- **Indicadores y contribución**: numeral 2.6 de la propia ficha; diagnóstico de brechas del MVCS.

---

## Errores que hay que evitar

- Deducir la cadena funcional del expediente en vez de usar la fija.
- Poner los tres sistemas en una sola fila de `1.04.2` separados por comas o ";".
- Redactar un indicador propio, o recortar el texto oficial.
- Escribir a mano el año o el valor de contribución en `1.05.1` (son calculados).
- Repetir el mismo nombre en `1.01.4` y `1.01.5` solo para no dejar un campo vacío.
- Alinear a un indicador de calidad un proyecto que solo amplía cobertura, o al revés.
