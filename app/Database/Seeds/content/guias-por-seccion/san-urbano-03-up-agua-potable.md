# GUÍA DE LLENADO — II. IDENTIFICACIÓN (2.2.1 y 2.2.2) — UP DE AGUA POTABLE

*(FTE Saneamiento Urbano — 3 campos, los tres tablas)*

Acá empieza el diagnóstico de las Unidades Productoras. Primero se identifican **todas** las UP del
proyecto; después se entra al detalle del sistema de agua potable.

---

## `3.01.1` — identificación de las unidades productoras

**Una fila por UP existente**, es decir por sistema. Un proyecto que toca los tres sistemas lleva
tres filas:

| servicio | nombre de la UP |
|---|---|
| Servicio de agua potable | Sistema de agua potable |
| Servicio de saneamiento | Sistema de alcantarillado sanitario |
| Servicio de saneamiento | Sistema de tratamiento de aguas residuales |

Por cada una:

- **código de la UP**, solo si el Sector lo definió; si no existe, se deja vacío (no se inventa);
- **coordenadas WGS84** (Este / Norte) del punto referencial donde está la UP;
- **nombre del centro poblado urbano** donde está la población atendida por esa UP;
- tres respuestas Sí/No que condicionan permisos más adelante: si está **ubicada en área natural
  protegida o zona de amortiguamiento** (se consulta a **SERNANP**), si está en **zona de restos
  arqueológicos** (deriva en CIRA) y si está en **zona inundable**.

Se adjunta en anexos un plano, croquis o esquema de la UP, y se genera el **archivo KML** de las
Unidades Productoras: lo pide el Banco de Inversiones al registrar el Formato N.° 07-A.

---

## `3.02.1` — fuentes hídricas de la UP de agua potable

Una fila por UP de agua potable. Se declara:

- **número de fuentes hídricas** de esa UP;
- **tipo de fuente**: superficial, subterránea o **mixto**;
- **nombre de las fuentes** (las mismas que se listaron en el numeral 2.1.4 — deben ser coherentes);
- **tipo de sistema de agua potable existente**: por gravedad, por bombeo, o mixto; con o sin
  tratamiento según corresponda.

---

## `3.03.1` — situación actual de la UP de agua potable

Es la tabla más detallada del diagnóstico: **una fila por activo estratégico** del sistema. Los
componentes típicos son captación, estación de bombeo, línea de impulsión, línea de conducción,
planta de tratamiento de agua potable, reservorio, línea de aducción, redes de distribución
(primarias y secundarias) y conexiones domiciliarias.

Por cada activo se declara:

- **unidad física** (U.M. y cantidad) y **dimensión física** (U.M. y cantidad). Para tuberías la
  dimensión suele ir en metros y el diámetro en milímetros — revisa qué pide cada columna y no
  mezcles unidades;
- **tipo de suelo**, **presencia de nivel freático** y **presencia de falla geológica**;
- **antigüedad en años**, **operativo (SÍ/NO)** y **estado de conservación** (BUENO / REGULAR /
  MALO);
- **lugar** donde se ubica y si **se cuenta con documento que acredite la disponibilidad del
  terreno**;
- **coordenadas** referenciales;
- una **breve descripción del estado actual**, concreta: qué falla, desde cuándo, con qué
  consecuencia ("redes con continuos colapsos y roturas que causan aniegos frecuentes"), no
  adjetivos sueltos.

La lista de activos estratégicos válidos la publica el **MVCS**; conviene ceñirse a esa nomenclatura.

---

## Dónde está la evidencia

- **Inventario y características de los activos**: informe de diagnóstico de la UP, inventario del
  prestador (EPS / ATM), fichas de campo.
- **Antigüedad y estado**: inventario del prestador, actas de inspección.
- **Coordenadas**: levantamiento georreferenciado del expediente.
- **Área protegida / amortiguamiento**: SERNANP. **Restos arqueológicos**: Ministerio de Cultura.
- **Disponibilidad de terreno**: partida registral, acta de cesión o documento de saneamiento.

---

## Errores que hay que evitar

- Poner los tres sistemas en una sola fila de `3.01.1`.
- Inventar un código de UP cuando el Sector no lo ha definido.
- Declarar en `3.02.1` fuentes que no aparecen en el numeral 2.1.4, o al revés.
- Confundir *unidad física* con *dimensión física*, o mezclar metros con milímetros en la misma
  columna.
- Describir el estado actual con un adjetivo ("malo") sin decir qué falla ni desde cuándo.
- Marcar un activo como operativo y a la vez describirlo como colapsado.
