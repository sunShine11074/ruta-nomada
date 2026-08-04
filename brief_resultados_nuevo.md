# Brief de diseño — `resultados.php` (detalle de ciudad) · Ruta Nómada

> Documento para reconstruir fielmente la página de resultados a partir de los mockups de Figma,
> añadiendo refinamientos de front-end para la mejor experiencia posible.

---

## Propósito
Página que ve el usuario **tras buscar una ciudad** en el buscador del topbar. A la izquierda muestra
la identidad y contexto de la ciudad (fotos, descripción, clima) y listados por categoría; a la derecha,
un **mapa de Google fijo** con pines. Estilo tipo TripAdvisor.

## Identidad visual (mantener)
- **Tipografía:** Poppins.
- **Colores:** teal oscuro `#062738` (topbar/acentos), dorado `#f0b429` (logo/estrellas), fondo de página `#E6ECF2`, tarjetas blancas.
- **Formas:** esquinas redondeadas (~12–14px), sombras suaves, pines teal con número blanco.

## Mapa de mockups → secciones
| Mockup | Qué ilustra |
|---|---|
| "Resultados de búsqueda (nuevo) 1" y "…2" | Página tras buscar la ciudad (hero, descripción, clima, submenú, tarjetas, mapa) |
| "…(nuevo). 2" | Comportamiento del hero al hacer **hover** (aparecen flechas ‹ ›) |
| "…(nuevo) 3.1 / 3.2 / 3.3" | Mapa **fijo** al scrollear; pestañas Cosas que hacer / Restaurantes / Hoteles |

---

## 1. Layout global (2 columnas)
- **Izquierda (~58–60%)**: contenido, **scrollea**.
- **Derecha (~40–42%)**: mapa, **fijo/sticky** (permanece mientras la izquierda scrollea).
- **Topbar** (existente, no rediseñar): logo Ruta Nómada, nav (Inicio · Explorar · Mis viajes · Comunidad), buscador con la ciudad, campana, avatar + nombre.
- **Botón circular flotante** (menú ☰) arriba-izquierda que colapsa/expande el sidebar.

---

## 2. Breadcrumb
- `Continente › País › Ciudad`, gris, separadores con chevron. Cada nivel clickable.

## 3. Hero de imágenes (carrusel)
Bloque grande redondeado (≈ 16:9). **Capas superpuestas:**
- **Sup-izq:** insignia del logo (avión).
- **Inf-izq:** avatar + "Nombre del proveedor" en pastilla oscura translúcida (atribución de la foto).
- **Inf-centro:** puntos indicadores (imagen actual resaltada).
- **Inf-der:** pastilla con ícono 🖼 + contador de imágenes ("10,200").
- **Laterales:** flechas circulares ‹ › que **solo aparecen al hacer hover** sobre el hero (fade-in). En touch: swipe.
- **Estados:** cargando = shimmer; sin foto = placeholder de marca.

## 4. Título de ciudad + Guardar
- Misma línea horizontal: **`Hermosillo, Sonora`** (grande, bold, teal) a la izquierda; botón **`♡ Guardar`** (pastilla con borde) a la derecha.
- Estados del botón: normal → hover → **guardado** (corazón relleno + texto "Guardado").

## 5. Descripción + "Leer más"
- Párrafo recortado a ~3 líneas (`line-clamp`).
- Enlace subrayado **"Leer más ⌄"** (con chevron). Al pulsar: expande con **animación de altura suave**; el chevron rota y el texto cambia a **"Leer menos ⌃"**.

## 6. Clima actual (carrusel de datos)
- Título "Clima actual". Tarjeta blanca redondeada con **4 columnas**: ícono + etiqueta + valor.
- **Página 1:** Temperatura · Condiciones meteorológicas · Velocidad del viento · Humedad.
- **Página 2** (chevron ‹/› a los lados): UV · Calidad del aire · Salida de sol · Puesta de sol.
- **Íconos y textos dinámicos** según el valor (soleado / nublado / lluvia; "Buena / Regular / Mala"; etc.).
- Refinamiento: transición deslizante entre páginas + swipe en touch; puntos indicadores opcionales.

## 7. Submenú de categorías + Filtros
- Barra horizontal: **Cosas que hacer · Restaurantes · Hoteles** (el activo con subrayado teal grueso). Botón **`Filtros ⚙`** a la derecha.
- **Sticky**: al scrollear, esta barra se pega bajo el topbar con una sombra sutil.
- Cambiar de pestaña recarga la lista de tarjetas **y** los pines del mapa (transición cross-fade).

## 8. Tarjetas (grid de 3 columnas)
Cada tarjeta (blanca, redondeada, **elevación al hover**):
- **Imagen con mini-carrusel:** flechas ‹ › al hover + puntos; ♡ **favorito** arriba-derecha (relleno rojo al guardar, con *pop* animation).
- **Cuerpo:** "Nombre del sitio" (bold, subrayado al hover), fila de **rating** (número + estrellas doradas + "(657)"), y **meta** según categoría:
  - Hoteles: `MXN 1,397 · Hotel de 4 estrellas`.
  - Cosas que hacer: `Categoría · ♿` (ícono de accesibilidad).
  - Restaurantes: `Tipo de cocina · $$`.
- **Sincronía tarjeta ↔ pin:** hover en la tarjeta resalta su pin (y viceversa); click centra el mapa en el pin y abre su info.
- **Estados:** cargando = *skeleton cards*; categoría vacía = ilustración + mensaje; imágenes con lazy-load y fallback.

---

## 9. Mapa (Google) — columna derecha
- **Dimensiones nuevas:** más alto y ancho; ocupa toda la altura del área de resultados.
- **Fijo/sticky:** no se mueve al scrollear (mockups 3.1–3.3).
- **Botón "expandir"** arriba-derecha (ícono de pantalla completa) → modo mapa grande / fullscreen.
- Controles de Google (zoom, pan) abajo-derecha.
- **Pines numerados** (teardrop teal, número blanco) que corresponden 1:1 con las tarjetas de la categoría activa; pin resaltado al hover de su tarjeta.

---

## 10. Refinamientos de UX (para la mejor experiencia posible)
- **Skeletons / shimmer** en hero, tarjetas y clima mientras cargan (evita saltos de layout).
- **Lazy-load** de imágenes; **precarga** de la siguiente imagen del carrusel.
- **Submenú sticky** con sombra al pegarse; scroll suave (`scroll-behavior:smooth`).
- **Sincronía bidireccional** tarjeta ↔ pin (hover y click).
- **Animaciones sutiles:** fade de las flechas al hover, *height animation* en "Leer más", *pop* del corazón al guardar, cross-fade al cambiar de categoría.
- **Teclado / accesibilidad:** flechas del teclado en carruseles, `Tab`/`Enter` en tarjetas, `aria-label` y `alt` en imágenes, *focus rings* visibles, contraste AA.
- **Touch:** swipe en todos los carruseles (hero, tarjetas, clima).
- **Estados de error / vacío:** API caída → mensaje amable con botón de reintento; sin resultados en la categoría → estado vacío ilustrado.
- **Responsive:**
  - Pantallas angostas: el mapa pasa **debajo** de la lista o se oculta tras un botón flotante **"Ver mapa"**.
  - El grid de tarjetas baja a **2** y luego **1** columna.
  - El submenú de categorías se vuelve **scroll horizontal**.

---

## 11. Notas de datos (no afectan el diseño; para el back-end)
El diseño puede usar **placeholders**. En producción los datos vendrían de:
- **Google Places** → tarjetas (nombre, rating, fotos, precio, categoría) y fotos/contador del hero.
- **Wikipedia (API REST)** → descripción de la ciudad.
- **Clima** → Open-Meteo (sin key) u OpenWeatherMap para los 8 datos.

Las claves de API van **solo en el servidor** (proxies PHP con caché, como el `geo.php` ya existente); nunca en el cliente.

---

## 12. Componentes reutilizables del proyecto actual
- **Mapa de Google** + geocodificación de la ciudad (ya implementados en `resultados.php` / `js/resultados.js`).
- **Sincronía tarjeta ↔ pin** y los chips de categoría (ya existen; re-etiquetar y ampliar).
- **Topbar / sidebar** (`includes/user_topbar.php`) y estilos base (`style.css`, `resultados.css`).
