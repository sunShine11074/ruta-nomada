# 📋 Reporte de cambios — `resultados.php` (rediseño) · Ruta Nómada

> Resumen para el equipo: qué cambió, cómo funciona la nueva página de resultados
> y qué necesita cada quien para correrla en su máquina.

---

## ¿Qué es esta página?

Es la página que ve el usuario **después de buscar una ciudad** en el buscador de la barra superior
(escribir la ciudad y presionar **Enter** en cualquier página). Se rediseñó por completo siguiendo
los mockups de Figma (estilo TripAdvisor): a la izquierda la identidad de la ciudad (fotos, descripción,
clima) y los listados por categoría; a la derecha un **mapa de Google fijo** con pines numerados.

## Archivos modificados

| Archivo | Qué se hizo |
|---|---|
| `resultados.php` | Reescrito completo: nueva estructura de la página |
| `resultados.css` | Reescrito completo: estilos del nuevo diseño |
| `js/resultados.js` | Reescrito completo: toda la lógica e integración con APIs |

*(El buscador del topbar ya estaba conectado desde antes en `js/sidebar.js`: Enter → `resultados.php?q=ciudad`.)*

---

## Secciones de la página y de dónde salen los datos

| Sección | Qué muestra | Fuente de datos |
|---|---|---|
| **Breadcrumb** | `Continente › País › Ciudad` | Google **Geocoding** (el continente se deriva del país) |
| **Hero de fotos** | Carrusel con hasta 10 fotos reales de la ciudad, logo, atribución del autor de la foto, puntos indicadores, contador y flechas que aparecen al pasar el mouse | Google **Places** (fotos del *place* de la ciudad) |
| **Título + Guardar** | "Hermosillo, Sonora" + botón ♡ que se pone rojo y dice "Guardado" | Geocoding + `localStorage` (por usuario) |
| **Descripción** | Resumen real de la ciudad con "Leer más / Leer menos" animado | **Wikipedia** (API REST en español, gratis) |
| **Clima actual** | 8 datos en 2 páginas deslizables: Temperatura, Condiciones, Viento, Humedad · UV, Calidad del aire, Salida y Puesta de sol. Los íconos son SVG **dinámicos** (el termómetro sube/baja, la gota se llena según humedad, el sol del arco se posiciona según la hora local de la ciudad, etc.) | **Open-Meteo** (gratis, **sin API key**) |
| **Pestañas** | Cosas que hacer · Restaurantes · Hoteles (barra *sticky* al hacer scroll) + botón Filtros (visual, pendiente de funcionalidad) | — |
| **Tarjetas** | 9 lugares reales por categoría: foto, ♡ favorito, nombre, calificación con estrellas doradas de relleno parcial, número de reseñas y categoría | Google **Places** (búsqueda cercana al centro de la ciudad) |
| **Mapa** | Mapa real de Google, **fijo mientras haces scroll**, con pines numerados 1–9 que corresponden a las tarjetas; botón de **expandir** (pantalla casi completa, se cierra con `Esc`), zoom +/− y centrar | Google **Maps JavaScript API** |

## Interacciones implementadas

- **Sincronía tarjeta ↔ pin**: pasar el mouse por una tarjeta resalta su pin (se pone dorado y crece) y viceversa; hacer clic en un pin te desplaza a su tarjeta y abre una ventanita de información.
- **Carruseles con swipe** (hero y clima) en pantallas táctiles; flechas ← → del teclado cambian la foto del hero.
- **Skeletons** (placeholders animados) mientras cargan las tarjetas al cambiar de pestaña.
- **Favoritos** por tarjeta y "Guardar ciudad" persisten por usuario (en `localStorage` del navegador — pasarlos a la base de datos queda como mejora futura).
- **Responsive**: en pantallas angostas el mapa se oculta y aparece el botón flotante **"Ver mapa"** que lo abre a pantalla completa; el grid baja a 2 y luego 1 columna.
- El **sidebar** en esta página funciona como overlay (botón azul flotante ☰ arriba a la izquierda).

## Detalles técnicos importantes

1. **La key de Google ahora también usa Places.** El `<script>` de Google Maps carga con `&libraries=places`.
   En Google Cloud deben estar habilitadas **tres APIs** para la key: *Maps JavaScript API*, *Geocoding API* y *Places API*.
   ⚠ Recomendado: restringir la key por **HTTP referrer** (`http://localhost/*`) en Google Cloud Console.
2. **Open-Meteo y Wikipedia no necesitan key** — se consultan directo desde el navegador.
3. Los datos de Google a veces traen rarezas (p. ej. hoteles con restaurante aparecían en "Restaurantes");
   se filtran los hoteles (`lodging`) fuera de su pestaña.
4. La meta de hoteles muestra **tipo + dirección** (Google Places no expone precio por noche ni número
   de estrellas del hotel en la búsqueda cercana).
5. Las flechas del mini-carrusel de cada tarjeta solo aparecen si el lugar tiene más de una foto.

## Cómo probarla

1. Tener **Apache y MySQL** encendidos en XAMPP (se necesita iniciar sesión).
2. Entrar a cualquier página, escribir una ciudad en el buscador de arriba (p. ej. `Hermosillo`, `Tokyo`, `Paris`) y presionar **Enter**.
3. O ir directo a: `http://localhost/intre%20proyecto_viajes%20CORREGIDO/resultados.php?q=Hermosillo`

Prueba: cambiar de pestaña, pasar el mouse por tarjetas/pines, expandir el mapa (y cerrarlo con `Esc`),
la flechita del clima para ver la segunda página, "Leer más", guardar la ciudad y hacer la ventana angosta
para ver el modo móvil con "Ver mapa".

## Referencias

- Los mockups y la especificación de diseño están en `design_handoff_resultados/`
  (el `README.md` de esa carpeta detalla medidas, colores y comportamientos, y el
  `standalone.html` se puede abrir en el navegador para comparar contra el prototipo).
