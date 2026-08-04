# 🎨 Handoff de diseño — Vista del Plan de Viaje (Ruta Nómada)

> **Para:** Claude Design
> **De:** equipo Ruta Nómada
> **Fuente:** 13 frames de Figma (adjuntos como PNG, 1440×809 cada uno)
> **Objetivo:** un prototipo hi-fi **fiel a los frames**, con los refinamientos indicados en la §9.

---

## 1. Contexto del producto

**Ruta Nómada** es una app web de planeación de viajes (proyecto escolar, PHP + JS vanilla, sin
frameworks). Estos frames definen su pantalla más importante: **la vista de un plan de viaje**,
inspirada en Wanderlog. El usuario ve su viaje ("Nuestro viaje a Ensenada", 17/7–19/7) con un
**mapa siempre visible a la derecha**, y a la izquierda navega entre cuatro sub-vistas:
**Explorar**, **Itinerario**, **Presupuesto** y un **Asistente de IA** conversacional.

Idioma de toda la interfaz: **español de México**. Los textos de los frames son la fuente de
verdad, salvo las correcciones de la §9.

---

## 2. Estructura global (presente en los 13 frames)

### 2.1 Rejilla de tres columnas

Observación importante medida en los frames: **la columna de contenido conserva un ancho fijo
(~695 px) y es el mapa el que se encoge o crece**; la barra lateral empuja desde la izquierda.

| Frame | Barra lateral | Contenido | Mapa |
|---|---|---|---|
| P1 (lateral expandida) | 0 → 192 px | 192 → 886 px (**694**) | 886 → 1440 (**554**) |
| P2/P3/P4 (lateral colapsada) | 0 → 44 px | 44 → 740 px (**696**) | 740 → 1440 (**700**) |

```css
.plan-layout { display: grid; grid-template-columns: auto 695px 1fr; }
```

- La **columna de contenido tiene su propio scroll** (barra de scroll fina y visible, track claro
  con pulgar gris; ver P2.1–P2.6), no la página.
- El **mapa nunca se desplaza**: es un panel fijo de altura completa bajo el topbar.

### 2.2 Topbar (fijo, altura ~58 px)

Fondo azul petróleo muy oscuro, de borde a borde. De izquierda a derecha:

1. **Logo**: avión de papel dentro de un círculo dorado (~32 px) + palabra **"Ruta Nómada"** en
   dorado, peso bold.
2. **Buscador** (pill blanco, ~345 px): campo de texto → separador vertical fino → selector
   **"Ciudad ⌄"** → botón circular oscuro (~34 px) con lupa blanca, pegado al borde derecho del pill.
3. **Navegación**: `Inicio · Guías de viaje · Mis planes` en blanco, peso medio (ver §9.1 sobre el orden).
4. **Derecha**: botón pill **"🗺 Crear plan de viaje"** (fondo apenas más claro que el topbar,
   borde 1px translúcido, icono de libreta-mapa), **campana** de notificaciones, y **avatar
   circular con anillo dorado** (~34 px).

### 2.3 Panel de mapa (Google Maps, estilo por defecto)

Controles superpuestos, todos en botones/pills blancos con sombra suave:
- Arriba derecha: **lupa** (buscar dentro del mapa) y **capas** (apiladas).
- Abajo derecha: **zoom + / −** en un control vertical redondeado.
- Abajo izquierda: pill **"🔍 Acércate…"**.
- Los POI se dibujan con **pines circulares de color por categoría** (ver §3.4) y etiqueta de
  texto al costado, con el nombre en el color del pin.

---

## 3. Sistema de diseño (tokens deducidos de los frames)

### 3.1 Color

| Rol | Valor aprox. | Uso |
|---|---|---|
| Petróleo oscuro | `#0E2A33` | Topbar, botones primarios oscuros, pills activos, FAB |
| Dorado / ámbar | `#F5B93F` | Logotipo, CTAs secundarios ("Explorar más", "Nueva Lista", "Agregar gasto", "Añadir al plan de viaje" en el panel del mapa), subrayado de pestaña activa |
| Azul del asistente | `#1E86D8` | Cabecera del Asistente de IA, iconos ✨ |
| Azul de enlace | `#1A73C8` | Nombres de lugares y enlaces dentro de las respuestas de IA y reseñas |
| Azul claro de chip | `#DCEBF7` | Chips de sugerencias del chat, chips "Qué hacer / Dónde comer / Dónde alojarse" |
| Gris de superficie | `#F1F5F8` | Tarjetas de categorías, tarjeta de presupuesto, chips de metadatos |
| Banda separadora | `#E9EFF6` | Franjas horizontales entre secciones de la columna |
| Borde | `#DCE5EA` | Bordes 1px de tarjetas y pills claros |
| Texto principal | `#0D1F27` | Títulos y cuerpo |
| Texto secundario | `#6B7A83` | Subtítulos, metadatos, reseñas |
| Verde de progreso | `#41A24D` | Barra del presupuesto |
| Estrella | `#F0A81E` | Estrellas de calificación (dorado) |

### 3.2 Tipografía

- **Familia única: Poppins** (el proyecto ya la carga). Títulos de sección en **700**
  ("Itinerario", "Presupuesto", "Ensenada", "Nuestro viaje a Ensenada"); cuerpo en **400**;
  etiquetas y botones en **500/600**.
- Escala observada: título de plan ~30 px · título de sección ~28 px · título de ciudad ~40 px ·
  título de día ~22 px · nombre de lugar ~15 px bold · cuerpo ~14 px · metadatos ~12–13 px.
- **Monto del presupuesto**: se ve en fuente monoespaciada en el frame; usar **Poppins con
  `font-variant-numeric: tabular-nums`** y tracking ligeramente abierto (ver §9.4).

### 3.3 Forma y elevación

- **Pills**: radio completo (999 px). **Tarjetas y paneles**: 10–12 px. **Imágenes**: 8–10 px.
- Sombras suaves y bajas (`0 2px 8px rgba(13,31,39,.10)`); el panel de detalle sobre el mapa y la
  tarjeta del título del viaje llevan una un poco más marcada.
- Separadores: línea de 1 px `#E4EBEF` a todo el ancho del contenido, con ~24 px de aire.

### 3.4 Pines del mapa (código de color por categoría)

Gota/círculo con número blanco al centro:

| Color | Categoría |
|---|---|
| Morado `#7B61FF` | Atracciones y "Lugares principales a visitar" |
| Rojo coral `#E8365D` | Comida y restaurantes |
| Teal `#12808C` | Guardados / listas del plan |
| Dorado `#F0B429` | **Estado activo** (pin resaltado y más grande) |

---

## 4. Barra lateral del plan (dos estados)

### 4.1 Expandida — 192 px (frame P1)

- **Cabecera azul** de ~48 px: icono de burbuja de chat + **"Asistente de IA"** en blanco bold.
  Ocupa todo el ancho de la lateral y sobresale ligeramente del borde superior del contenido.
- Grupos colapsables con caret `⌄`:
  - **Resumen** → un ítem hijo: **"Explorar"**, en estado activo = **pill oscuro de ancho
    completo, texto blanco**, radio 8 px.
  - **Itinerario** → hijos: `Vie. 12/7`, `Sáb. 12/7`, `Dom. 12/7`.
  - **Presupuesto** → hijo: `Ver`. *(en el frame dice "Itinerario" por error; ver §9.3)*
- Los ítems inactivos: texto gris oscuro, sin fondo; hover = fondo `#F1F5F8`.
- Pie fijo: **"Ocultar barra lateral «"** en gris, alineado a la izquierda.

### 4.2 Colapsada — rail de 44 px (frames P2, P3, P4)

Solo iconos centrados, de arriba a abajo:
1. **Burbuja de chat oscura** del asistente, en una pastilla que sobresale del rail hacia la
   derecha (elemento más prominente). Cuando el chat está abierto cambia a un **✨ azul**
   (comparar P4 vs. P4-small-window).
2. Icono de **documento** (Resumen) con un punto debajo indicando la sub-sección.
3. Icono de **calendario**, y debajo la lista vertical de días en texto diminuto:
   `17 JUL` / `18 JUL` / `19 JUL`.
4. Icono **`$`** con la etiqueta `Ver` debajo.
5. Pie: **`»`** para expandir.

Al colapsar, el mapa gana el ancho liberado (§2.1).

---

## 5. Vista "Resumen" del plan (frames P1 y P2)

De arriba a abajo dentro de la columna de contenido:

1. **Hero fotográfico** de la ciudad, sangrado a los bordes de la columna, ~220 px de alto
   (panorámica con la bandera de México). Arriba a la derecha, dos **botones circulares oscuros
   semitransparentes**: ✏️ (editar) y **•••** (más acciones).
2. **Tarjeta del viaje**, blanca, superpuesta sobre el hero (~50 % encima), inset ~64 px del borde
   izquierdo, radio 12 px, sombra:
   - **"Nuestro viaje a Ensenada"** (~30 px, bold).
   - Debajo: 📅 **"17/7 - 19/7"**.
   - A la derecha, en la misma fila: **avatares de los miembros** (círculos de 30 px con anillo
     blanco, superpuestos) + **botón circular de borde punteado** con icono "añadir persona"
     (= invitar colaboradores).
3. **Sección "⌄ Explorar"**: título de sección con caret a la izquierda, y a la derecha el pill
   ámbar **"🔍 Explorar más"**. (En P2 el cursor está sobre él: el hover oscurece el ámbar y añade
   sombra.)
4. **Tres tarjetas de guía** en fila (grid de 3, gap ~16 px):
   - Imagen 4:3 con radio 10 px.
   - Título a dos líneas: *Mejores restaurantes en Ensenada* · *Mejores Hoteles en Ensenada* ·
     *Atracciones populares en Ensenada*.
   - Pie: logo circular de la marca (20 px) + **"Ruta Nómada"** en gris.
5. **Banda separadora** de ~24 px, y luego el pill ámbar **"+ Nueva Lista"** alineado a la izquierda.

---

## 6. Sub-vista "Explorar" (frames P2.1 → P2.6)

### 6.1 Cabecera de la sub-vista

Barra blanca fija sobre la columna: **`←` + "Explorar"** (título ~20 px bold) y, centrado, un
**buscador pill gris claro** (`#F1F5F8`, sin borde) con lupa y el texto **"Ensenada"**.

### 6.2 Ficha de la ciudad (P2.1)

- **"Ensenada"** en ~40 px bold.
- Párrafo descriptivo de ~6 líneas, gris oscuro, ancho de columna completo. *(el texto del frame
  es incorrecto: ver §9.2)*
- **Tres chips de acción** con borde azul claro e icono: **📍 Qué hacer** · **🍴 Dónde comer** ·
  **🛏 Dónde alojarse**. *(los iconos están cruzados en el frame; ver §9.2)*
- **"Categorías"** + enlace **"Ver todo"** a la derecha. **Grid 3×3** de tarjetas-botón:
  fondo `#F4F7F9`, borde 1px, radio 10 px, altura ~46 px, icono ilustrado circular a la izquierda
  + etiqueta. Contenido exacto: *Restaurantes · Atracciones · Cafés · Comida rápida · Desayuno y
  brunch · Lugares románticos · Restaurantes familiares · Barras · Compras*.
- Separador.
- **"Lugares principales a visitar"**.

### 6.3 Fila de lugar (patrón que se repite; P2.1–P2.3)

Es el componente más reutilizado. Estructura:

```
[pin numerado]  Nombre del lugar (bold)              [ Añadir al plan de viaje ▾ ]
★★★★½  4.4 (12502)  G
[chip] [chip]  Mostrar 2 más
Descripción de 3–5 líneas ……………………………          [ thumbnail 4:3 ]
  ‹  «reseña en cursiva, gris»                              ›
     ★★★★☆   Alejandro L -- Reseña de Google
```

- **Pin numerado**: gota de 24 px con el número en blanco, color según categoría (§3.4).
- **Botón "Añadir al plan de viaje"**: *split button* oscuro (`#0E2A33`), texto blanco, icono de
  marcador 🔖, con un **caret separado por una línea divisoria** a la derecha.
  - **Estado "ya añadido"** (ver "Mercado Negro" en P2.2): el botón pasa a **gris claro con texto
    oscuro** y la etiqueta cambia a **"Añadido"**.
- **Calificación**: estrellas doradas con **relleno parcial** para los decimales, seguido del
  número en bold, el conteo entre paréntesis en gris y el **logotipo "G" de Google**.
- **Chips de metadatos**: gris claro, texto 12 px (`Lonja de pescado`, `Compras`, `Restaurante`).
  El **rango de precios** se muestra como `$$$$` con los primeros signos en negro y el resto en
  gris. Enlace **"Mostrar 2 más"**.
- **Carrusel de reseñas**: flechas circulares grises `‹ ›` a los costados, texto en cursiva gris,
  estrellas pequeñas y **enlace azul "Nombre -- Reseña de Google"**.
- Al final de cada bloque, enlace **"Mostrar más ⌄"**; entre secciones, título de sección
  (p. ej. **"Mejores sitios para comer"**).
- **FAB circular oscuro con flecha ↑** ("volver arriba"), flotante y centrado en la parte baja de
  la columna de contenido.

### 6.4 Panel de detalle del lugar, sobre el mapa (P2.4 → P2.6)

Hoja blanca redondeada (radio 12 px, sombra marcada) anclada **abajo dentro del mapa**, con
~12 px de inset y ~340 px de alto, con **scroll propio**.

- **Fila flotante justo encima del panel**: pill **"‹ 19 de 20 ›"** (navegación entre resultados),
  pill **"🔍 Acércate a Manzanilla"** y, a la derecha, **botón circular ✕** para cerrar.
- **Pestañas**: `Acerca de` · `Reseñas` · `Fotos`. La activa lleva **subrayado ámbar de 2–3 px**.
- **"Acerca de"**: pin numerado + nombre, descripción, thumbnail a la derecha, y el CTA ancho
  **ámbar "🔖 Añadir al plan de viaje ▾"**.
- **Fila de acciones de IA** (scroll horizontal): chip ámbar **"✨ Preguntar a la IA"** y chips
  blancos con borde y flecha `↳`: *Guía turística* · *¿Cuál es el rango de precios?* ·
  *Necesito una reservación…*
- **Datos completos** (P2.5), cada línea con su icono a la izquierda:
  - `★ 4.6 (5874) G · Mencionado por [avatares] +22 otras listas`
  - Dirección completa.
  - Horario: **"Viernes 13:00 – 23:00"** + fila de días en chips (`do lu ma mi ju vi sá`, el día
    actual resaltado) + enlace **"Mostrar horarios"**.
  - **"La gente suele pasar 10 min aquí"**.
  - Teléfono y sitio web como enlaces azules.
  - **"Abrir en:"** + dos botones blancos con logotipo: `Google` y `Google maps`.
  - **"Saber antes de ir"**: lista de 4 bullets, cada uno con icono de pin/bombilla.

### 6.5 Buscador dentro del mapa (P2.5)

Campo blanco expandido arriba a la derecha del mapa, con **borde azul de foco**, y un **dropdown**
de categorías con icono: **🍴 Comida · 📍 Atracciones · ⛽ Gasolinera · 🔌 Carga de vehículos
eléctricos · 🛏 Paradas de descanso**.

### 6.6 Popover "Capas de mapa" (P2.6)

Panel blanco arriba a la derecha (radio 10 px, sombra), con **✕**:
- Título **"Capas de mapa"**.
- Fila de enlaces azules: **"Selecciona todo"** · **"Deseleccionar todo"**.
- Grupo **"Resumen"**: filas con pin de color + etiqueta + **checkbox a la derecha**.
- Grupo **"Itinerario"**, con enlace **"🔀 Mostrar siempre líneas de ruta"** y una fila por día.

---

## 7. Sub-vistas "Itinerario" y "Presupuesto" (frames P3 y P4)

### 7.1 Itinerario

- Título **"Itinerario"** (~28 px bold) y, a la derecha, pill gris **"📅 17/7 - 19/7"**.
- **Lista de días colapsables**, cada uno:
  - Caret `›` + **"Viernes, 17 de Julio"** (~22 px bold).
  - **Subtítulo editable** en gris debajo (placeholder **"Subtítulo añadido"**).
  - **•••** a la derecha (menú del día).
  - Separador de 1 px y ~32 px de aire entre días.
- Estado vacío: los días se ven sin tarjetas de lugares dentro; el prototipo debe mostrar
  **también un día expandido con 2–3 tarjetas de lugar** (reutilizando la fila de la §6.3 en
  versión compacta, con hora y precio) para demostrar el patrón.

### 7.2 Presupuesto

- Banda separadora, luego título **"Presupuesto"** + pill ámbar **"+ Agregar gasto"** a la derecha.
- **Tarjeta gris** (`#EEF2F5`, radio 12 px, padding ~20 px), en dos columnas:
  - **Izquierda**: monto **"MX$5,894.00"** muy grande (~34 px, tabular-nums), debajo en gris
    **"de MX$9,000.00 presupuestado"**, **barra de progreso verde** (~65 %, alto 8 px, radio
    completo, riel gris claro) y botón blanco con borde **"✏️ Editar presupuesto"**.
  - **Derecha**: tres acciones en lista, cada una con icono a la izquierda:
    **📊 Ver desglose de gastos** · **👤+ Añadir compañero de viaje** · **📄 Exportar como CSV**.
- Debajo: **"› Gastos"** con, a la derecha, **"Orden: Fecha (más reciente primero) ▾"**.

---

## 8. Asistente de IA — tres presentaciones

El asistente es el elemento diferenciador. Debe existir en **tres estados**, todos alcanzables
desde la burbuja del rail:

### 8.1 Ventana flotante pequeña (P4-small-window)

Tarjeta blanca (~430 × 455 px, radio 12 px, sombra fuerte) anclada **abajo a la derecha sobre el
mapa**. Contiene:
- **Cabecera**: **"Nuevo chat ⌄"** a la izquierda; a la derecha, icono de **expandir a panel** y
  **minimizar (—)**.
- **Aviso** en tarjeta gris con ⓘ: *"La información generada por el asistente de IA puede no ser
  completamente precisa."*
- **"¿No sabes qué preguntar? Prueba estos ejemplos:"** + **chips azul claro con flecha `↳`**,
  apilados y de ancho variable según el texto:
  *Mejores lugares para comer en Ensenada* · *Itinerarios de 3 días a Ensenada* ·
  *Principales atracciones en Ensenada*.
- **Input** al pie: pill con borde, placeholder *"Pida información relacionada con viajes"* y
  **botón circular oscuro con ↑**.

### 8.2 Panel de columna completa (P4-New-chat)

El asistente **sustituye toda la columna de contenido** (695 px), de arriba a abajo:
- **Cabecera**: icono de chat + **"Nuestro viaje a Ensenada BC"** en bold; a la derecha
  **"Nuevo chat ⌄"**, icono de **contraer a ventana** y **minimizar**.
- Mismo aviso y mismos chips de ejemplo (mayor escala).
- **Input** al pie, con placeholder largo: *«Pida información relacionada con viajes como ‹¿La
  mejor comida en Ensenada?›»*.

### 8.3 Conversación (P4-first-message)

- **Mensaje del usuario**: burbuja **gris claro alineada a la derecha**, radio completo, con el
  **avatar del usuario** a su derecha.
- **Respuesta del asistente**: **avatar de la marca** a la izquierda, texto a ancho completo (sin
  burbuja), con formato rico:
  - Subtítulos en **bold** (*"Día 1: Centro y malecón"*, *"Día 2: Cultura y paseo relajado"*).
  - Viñetas con los momentos del día (Mañana / Almuerzo / Tarde / Cena).
  - **Todos los nombres de lugares como enlaces azules** (al pasar el cursor deberían resaltar su
    pin en el mapa — ver §10).
  - Cierre con opciones en bold: *"más romántico, más barato, familiar o foodie"*.
- **Acciones bajo la respuesta**: iconos de **copiar** y **guardar como nota** (fantasma, se
  refuerzan al hover).
- **Input** al pie con placeholder *"Escribe un mensaje…"*.

---

## 9. Refinamientos solicitados (aplicar sobre los frames)

Los frames son la referencia visual, pero contienen errores de contenido y detalles a pulir.
**Aplicar todos estos cambios:**

### 9.1 Coherencia con el producto real
1. **Orden del menú**: los frames muestran `Inicio · Guías de viaje · Mis planes`, pero el sitio
   implementado usa **`Inicio · Mis planes · Guías de viaje`**. **Usar el orden del sitio**
   (Mis planes antes de Guías de viaje).
2. **Estado del buscador del topbar**: en los frames dice *"San Luis Río Colorado"* mientras el
   plan es de Ensenada. En el prototipo el buscador debe estar **vacío con su placeholder**
   (`Buscar una ciudad…`), no con un valor huérfano.

### 9.2 Errores de contenido (texto de relleno que quedó en los frames)
3. La **descripción de "Ensenada"** en P2.1 describe en realidad a **Escondido, California**
   (menciona el condado de San Diego y el Parque Safari del Zoológico). Escribir una descripción
   real de **Ensenada, Baja California** (puerto, malecón, La Bufadora, valle de Guadalupe).
4. La **descripción de "Mercado Negro"** (P2.2) es un copy-paste de la de *Playa Hermosa*.
   Redactar una propia (mercado de mariscos del puerto).
5. Los **iconos de los tres chips** están cruzados: *Dónde comer* lleva icono de cama y *Dónde
   alojarse* lleva cubiertos. **Intercambiarlos.**
6. **"Recently viewed"** aparece en inglés en una etiqueta del mapa → **"Visto recientemente"**.
7. Subtítulo de día con texto de prueba **"Emotional day. Every day is UNIQUE · Walmart"** →
   usar un ejemplo verosímil en español (p. ej. *"Llegada y malecón al atardecer"*).
8. **Erratas a corregir**: "ugna variedad" → *una variedad* · "com o el chuletón" → *como el
   chuletón* · "sals mole" → *salsa mole* · "Símbolos Historicos" → *Históricos* ·
   "un itinerarios de 2 días" → *un itinerario de 2 días* · "comodidapd" → *comodidad*.
9. **"MX9,000.00"** le falta el signo de pesos → **"MX$9,000.00"**.

### 9.3 Estructura
10. El **segundo grupo de la barra lateral** dice "Itinerario" por duplicado; debe llamarse
    **"Presupuesto"** (es coherente con el ítem "Ver" y con el icono `$` del rail).
11. En el chat, el chip **"Mejores lugares para comer en Ensenada" aparece dos veces**
    (P4-first-message). Dejar tres chips distintos.

### 9.4 Pulido visual
12. **Estrellas de las reseñas**: en los frames son grises y no coinciden con la calificación.
    Deben ser **doradas y reflejar la puntuación real** de esa reseña.
13. **Monto del presupuesto**: sustituir la fuente monoespaciada por **Poppins con
    `tabular-nums`**, conservando el tamaño y la contundencia.
14. **Contraste**: subir el contraste de los metadatos pequeños ("Mostrar 2 más", chips de
    horario) para cumplir **WCAG AA** (mínimo 4.5:1 sobre blanco).
15. **Estados faltantes**: definir y mostrar `hover`, `focus-visible` (anillo de 2 px) y `active`
    para pills, chips, filas de lugar, ítems de la lateral y controles del mapa. Los frames solo
    documentan el hover de "Explorar más".
16. **Truncado**: varias etiquetas del mapa y del panel se cortan a media palabra
    ("Plaza Sendero Ense…", "Necesito una re…"). Usar elipsis limpia con `title` completo.

### 9.5 Cobertura que los frames no muestran (añadir al prototipo)
17. **Estados vacíos** de: itinerario sin lugares, presupuesto sin gastos, y búsqueda sin resultados.
18. **Skeletons de carga** para las tarjetas de guía, las filas de lugar y el panel de detalle.
19. **Un día del itinerario expandido** con 2–3 tarjetas de lugar (hora, duración, precio, nota),
    incluida la **affordance de arrastrar y soltar** (manija de 6 puntos + línea guía de destino).
20. **Responsive**: a ≤ 1024 px el mapa se colapsa y aparece un botón **"Ver mapa"** que lo abre
    como hoja modal; a ≤ 640 px la barra lateral se convierte en menú inferior y las tarjetas se
    apilan en una columna.

---

## 10. Micro-interacciones deseadas

1. **Lugar ↔ pin**: al pasar el cursor por una fila de lugar (o por un enlace azul de la respuesta
   de IA), su **pin crece y se vuelve dorado**; al pasar por el pin, se resalta la fila.
2. **Clic en un pin** → abre el **panel de detalle** de la §6.4 con la pestaña *Acerca de*.
3. **"Añadir al plan de viaje"** → transición del botón a **"Añadido"** (gris) con un breve
   *check* animado; el caret abre un menú para elegir **el día** del itinerario.
4. **Colapsar la lateral** → animación de 200 ms; el mapa se ensancha con ella (§2.1).
5. **Asistente**: transición fluida entre **rail → ventana pequeña → panel completo**; el icono de
   la burbuja cambia a ✨ mientras el chat está abierto.
6. **Respuesta de IA en streaming**, con cursor de escritura.
7. **`Esc`** cierra, en orden: popover de capas → panel de detalle → ventana del asistente.
8. Todos los paneles superpuestos al mapa deben **atrapar el foco** mientras están abiertos.

---

## 11. Restricciones técnicas del proyecto

- **Poppins** (300–700) como única familia tipográfica; ya se carga desde Google Fonts.
- **CSS y JavaScript sin frameworks** (el proyecto es PHP + JS vanilla). Preferir CSS Grid/Flex,
  variables CSS y SVG en línea; sin Tailwind ni React.
- El mapa es **Google Maps JavaScript API** con `libraries=places`; los datos de lugares
  (nombre, rating, conteo de reseñas, fotos, horarios, teléfono, sitio web) vienen de
  **Places Details**, y el logotipo "G" y las atribuciones **deben conservarse** por los términos
  de uso.
- Los **iconos deben ser SVG en línea** con `stroke="currentColor"` (así se estilan por CSS),
  salvo los iconos ilustrados de la rejilla "Categorías", que pueden ser imágenes.
- Entregar **un solo archivo HTML autocontenido** con todo el CSS y JS en línea, que muestre las
  cuatro sub-vistas (Resumen, Explorar, Itinerario+Presupuesto, Asistente) y ambos estados de la
  barra lateral, para poder trocearlo después en componentes PHP.

---

## 12. Índice de los frames adjuntos

| Archivo | Qué documenta |
|---|---|
| `P1.png` | Resumen del plan con la **barra lateral expandida** (Asistente de IA en la cabecera) |
| `P2.png` | El mismo Resumen con la **barra lateral colapsada** (rail) y hover en "Explorar más" |
| `P2.1_ 'Explorar' 1.png` | Explorar: ficha de la ciudad, chips, rejilla de Categorías, primer lugar |
| `P2.2_ 'Explorar' 2.png` | Explorar: filas de lugar, estado **"Añadido"**, sección "Mejores sitios para comer", FAB ↑ |
| `P2.3_ 'Explorar' 3.1.png` | Explorar con **carruseles de reseñas**; mapa reencuadrado |
| `P2.4_ 'Explorar' 3.2.png` | **Panel de detalle del lugar** sobre el mapa: pestañas, CTA ámbar, chips de IA |
| `P2.5_ 'Explorar' 3.3.png` | **Buscador del mapa** desplegado + panel de detalle con datos completos |
| `P2.6_ 'Explorar' 3.3.png` | Popover **"Capas de mapa"** + sección "Saber antes de ir" |
| `P3.png` | **Itinerario** (días colapsables) y **Presupuesto** |
| `P4.png` | Itinerario/Presupuesto con el cursor sobre la burbuja del asistente |
| `P4_ small window for the AI assistant.png` | Asistente como **ventana flotante** sobre el mapa |
| `P4_ New chat with assistant.png` | Asistente como **panel de columna completa** (chat nuevo) |
| `P4_ first message to AI assistant.png` | **Conversación** con respuesta de itinerario formateada |
