# GUÍA DE LLENADO — II. IDENTIFICACIÓN (2.2.3 a 2.2.5) — ALCANTARILLADO, PTAR Y EXCRETAS

*(FTE Saneamiento Urbano — 7 campos, los siete tablas)*

Mismo patrón que el diagnóstico de agua potable, repetido para los otros tres sistemas: primero las
características del sistema como un todo, después activo por activo.

---

## 2.2.3 Alcantarillado sanitario

### `4.01.1` — características del sistema

Una fila por UP de alcantarillado: **capacidad de diseño (l/s)**, **volumen de producción (l/s)**,
**antigüedad**, **operativo (SÍ/NO)**, **estado**, **coordenadas UTM** y una descripción del estado
actual.

La descripción tiene que decir de qué está compuesto el sistema y qué le pasa. Ejemplo del
instructivo: *"Sistema compuesto por redes de alcantarillado de tuberías PVC de 12'', buzones y
conexiones domiciliarias. Presenta redes en mal estado. Afectadas por el terremoto del 15 de agosto
2007."*

Ojo con la relación entre las dos capacidades: el **volumen de producción no puede superar la
capacidad de diseño**. Si en la evidencia lo hace, hay un error.

### `4.01.2` — activo por activo

Una fila por componente. Los principales de un sistema de alcantarillado son: **colector primario,
colector secundario, estación de bombeo, línea de impulsión, emisor y efluente (ingreso y salida de
la PTAR) y conexiones de alcantarillado**.

Por cada uno: unidad física (U.M. y cantidad), dimensión física (U.M. y cantidad), tipo de suelo,
nivel freático, falla geológica, antigüedad, operativo, estado, documento de disponibilidad de
terreno, coordenadas y descripción del estado actual.

---

## 2.2.4 Tratamiento de aguas residuales

### `4.02.1` — características de la PTAR

Una fila por unidad de tratamiento: **tipo de tratamiento** (preliminar, primario, secundario,
terciario), **opción tecnológica** (laguna de estabilización, lodos activados, filtro percolador,
RAFA/UASB, humedal…), centro poblado, **capacidad de diseño (l/s)**, **volumen tratado (l/s)**, tipo
de suelo, nivel freático, falla geológica, antigüedad, operativo, estado, coordenadas y descripción.

### `4.02.2` — cuerpo receptor

Dónde va a parar el efluente: nombre del cuerpo receptor (río, quebrada, mar, canal, suelo),
centro poblado, **volumen vertido**, **volumen tratado**, y si cumple los **LMP** (límites máximos
permisibles del efluente) y los **ECA** (estándares de calidad ambiental del cuerpo receptor).

Son dos cosas distintas y se confunden: el **LMP** aplica a lo que sale de la planta; el **ECA**
aplica al cuerpo de agua que lo recibe. Se declaran por separado.

### `4.02.3` — activo por activo de la PTAR

Una fila por componente: unidad y dimensión física, antigüedad, operatividad, estado, documento de
disponibilidad de terreno, coordenadas y descripción.

---

## 2.2.5 Disposición sanitaria de excretas (UBS)

### `4.03.1` — características del área

Por UP: **presencia de nivel freático**, si está en **zona inundable**, resultado del **test de
percolación** y características del suelo. El test de percolación es el que decide si la opción
técnica con infiltración es viable — si no está en la evidencia, se deja vacío, no se supone.

### `4.03.2` — situación actual

Una fila por tipo de solución existente: **UBS con arrastre hidráulico, UBS sin arrastre hidráulico,
UBS colectivas, letrinas u otros**, con cantidad, material, antigüedad, estado, cuántas están
operativas y un diagnóstico breve.

Recordatorio del contexto general: **alcantarillado sanitario y UBS son Unidades Productoras
distintas**, así que no se plantean como dos alternativas comparables entre sí; una misma alternativa
sí puede contemplar ambas.

---

## Dónde está la evidencia

- **Capacidades, caudales y volúmenes**: informe operacional del prestador (EPS / ATM), registros de
  la PTAR.
- **Opción tecnológica y estado de la PTAR**: memoria descriptiva, informe de inspección.
- **LMP del efluente y ECA del cuerpo receptor**: monitoreos de calidad, informes a la autoridad
  ambiental, D.S. 003-2010-MINAM (LMP para efluentes de PTAR).
- **Test de percolación**: estudio de suelos del expediente.
- **Inventario de UBS**: padrón del prestador o del municipio.

---

## Errores que hay que evitar

- Declarar un volumen de producción o tratado mayor que la capacidad de diseño.
- Poner todos los componentes en una sola fila en vez de uno por fila.
- Mezclar LMP con ECA, o poner el mismo valor en las dos columnas.
- Inventar el resultado del test de percolación.
- Tratar UBS y alcantarillado como dos alternativas comparables entre sí.
- Marcar la PTAR como operativa cuando la descripción dice que está colapsada o fuera de servicio.
