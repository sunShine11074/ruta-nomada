# Plan de implementación — «Cambiar foto» del itinerario

**Ruta Nómada · 12 de agosto de 2026 · fase 1 en curso**

Ventana para cambiar la foto de portada de un itinerario, con dos
orígenes: las fotos que sube la persona y una búsqueda de imágenes en
la web. Se abre desde el lápiz que hay sobre la imagen de cabecera de
`plan.php`.

**Frames de referencia** en `screens_ref/`:

- `Cambiar foto del itinerario (Tus fotos).png`
- `Cambiar foto del itinerario (Buscar en la web).png`
- `Cambiar foto del itinerario (Buscar en la web 2).png`

---

## 0. Dónde está el disparador

El lápiz **no** está en `mis_planes.php` sino en
`plan_template.html:229`, el botón `aria-label="Editar portada"` que
flota sobre la imagen de cabecera. Antes de este trabajo estaba muerto:
`onClick="{{ noop }}"`, igual que lo estuvo el de invitar colaboradores.

---

## 1. Medidas tomadas de los frames

Las capturas son de 1917×1078 con escala 4/3, es decir **1440 CSS**.
Todo lo que sigue está medido a nivel de píxel, no estimado.

### El contenedor

| | Captura | CSS @1440 |
|---|---|---|
| Ancho del modal | 1000 px | **750** |
| Alto «Tus fotos» | 562 | 422 |
| Alto «Buscar» sin resultados | 202 | 152 |
| Alto «Buscar» con rejilla | 973 | **731** (tope ≈ 90 vh) |
| Padding interior | 40 | **30** |
| Centrado | x=958, y=538 | **centrado en ambos ejes** |

El ancho es constante en los tres estados; sólo cambia el alto. El
tercer frame llega a 731 y ahí se detiene, así que la rejilla scrollea
dentro del modal, no la página.

### La rejilla de resultados

| | Captura | CSS |
|---|---|---|
| Columnas | 3 | 3 |
| Tarjeta | 293 × 177 | **220 × 133** (≈ 5:3) |
| Hueco | 18 | **~15** |

Cuadra exacto: 3 × 220 + 2 × 15 = 690 = 750 − 2 × 30.

### Colores medidos

| Elemento | Color |
|---|---|
| Subrayado de la pestaña activa | `#EDC13F` |
| Botón oscuro y círculo de la lupa | `#0E2A33` |
| Borde y badge de la tarjeta elegida | `#3F52E3` |
| Fondo de tarjeta sin cargar | `#D3D3D3` |

### Piezas sueltas

| Elemento | CSS |
|---|---|
| Botón «Sube tus fotos» | 308 de ancho, centrado |
| Círculo de la lupa | Ø 45 |
| Ilustración de «Tus fotos» | 282 de ancho, centrada |

---

## 2. Recursos

Iconos de Font Awesome Free 7.3.1, `viewBox="0 0 640 640"`, ya
incorporados en la plantilla:

| Uso | Archivo original |
|---|---|
| Cerrar | `xmark-solid-full.svg` |
| Lupa del buscador | `magnifying-glass-solid-full (1).svg` |
| Subir fotos | `upload-solid-full.svg` |
| Abrir el original | `up-right-from-square-solid-full.svg` |

Ilustración: `img/subir-foto.png` (800×508, RGBA).

---

## 3. Lo que ya existía

| Pieza | Estado |
|---|---|
| `planes.portada_url` varchar(500) | ya existía |
| `api/plan_update.php` acepta `portada_url` | ya existía |
| `plan_logic.js` pinta `P.portada_url` como cabecera | ya existía |
| Patrón de subida de archivos | `profile.php` ya sube foto y banner |

**Guardar la URL elegida ya funcionaba.** Lo que falta es todo lo que
ocurre antes de tener esa URL.

---

## 4. La decisión de fondo: de dónde salen las fotos de «la web»

| Proveedor | Coste | URLs | Problema |
|---|---|---|---|
| Google Places Photos | clave ya disponible | **caducan** | Repetiría el bug que ya existe en `plan_items.imagen_url` |
| Google Custom Search | 100/día gratis | permanentes | Clave nueva; derechos de autor turbios |
| **Pexels** *(recomendado)* | gratis, 200/h | **permanentes** | Clave nueva; atribución |
| Unsplash | gratis, 50/h en demo | permanentes | Clave nueva; atribución + ping de descarga |

**Se recomienda Pexels.** Las URLs no caducan, que es lo decisivo para
algo que se guarda en la base y se pinta meses después. El catálogo es
fotografía de viaje y la atribución se resuelve con el icono de enlace
externo que **ya aparece en el frame**.

Places queda descartado justamente porque repetiría un problema
pendiente: las fotos de Google caducan y el itinerario se quedaría sin
portada al cabo de un tiempo.

---

## 5. Fases

### Fase 1 · El armazón del modal — 1 día

- `plan_template.html:229`: el lápiz pasa a `onClick="{{ fotoAbrir }}"`.
- Modal de 750 px centrado, con título, X de cerrar, las dos pestañas
  con subrayado y el cuerpo de cada una.
- `plan_logic.js`: estado `fotoModal`, `fotoTab`, `fotoQ`, `fotoRes`,
  `fotoSel`, `fotoCargando` y los métodos que lo gobiernan.
- Sin backend todavía: el botón de subir y la lupa no hacen nada.

**Verificación:** abre y cierra, las pestañas conmutan, mide 750 y
queda centrado tanto a 1440 como a 1920.

### Fase 2 · «Buscar en la web» — 1½ días

- `includes/pexels_config.php` gitignoreado, con su `.sample` al lado.
- `api/imagenes.php` como proxy — **la clave nunca sale al navegador**,
  mismo criterio que con Gemini.
- Rejilla 3×N, estado de carga, estado de «sin resultados», selección
  con borde `#3F52E3`, badge y enlace al original.
- Al elegir: `_sync('plan_update.php', { portada_url })`.

**Verificación:** buscar «La paz» devuelve fotos reales, elegir una
cambia la portada, y sobrevive a recargar la página.

### Fase 3 · «Tus fotos» — 1½ días

- Migración `usuario_fotos` (id, usuario_id, ruta, subida_en). Hace
  falta porque el frame dice *«Aún no has subido ninguna foto»*: hay
  galería, y el patrón de `profile.php` guarda un archivo fijo por
  usuario, que no sirve para varias.
- `api/foto_subir.php` con CSRF y `planAccess(editor)`; validación con
  `exif_imagetype()` y `finfo`; tope de 5 MB; nombre aleatorio.
- Destino `img/portadas/`, gitignoreado con un `.gitkeep`.

> **GD está desactivada** en este XAMPP (`;extension=gd`, línea 931 de
> `php.ini`). Por eso **el redimensionado se hace en el navegador con
> `<canvas>`** antes de subir: máximo 1600 px de ancho, salida JPEG. Es
> mejor que activar GD, porque no obliga a nadie a tocar su `php.ini` y
> además sube muchos menos bytes. `exif` y `fileinfo` sí están, que es
> lo que se necesita para validar en el servidor.

**Verificación:** un JPEG de 8 MB llega redimensionado por debajo de
1 MB; un `.php` renombrado a `.jpg` se rechaza; la galería lista lo
subido.

### Fase 4 · Detalles y verificación — ½ día

Foco atrapado dentro del modal, `Esc` cierra, clic fuera cierra,
atributos `aria-*`, y el lápiz sólo visible si `puedeEditar`. Medición
final contra los tres frames a 1440.

**Total ≈ 4½ días.**

---

## 6. Decisiones pendientes

1. **¿Pexels?** Si se prefiere otro proveedor cambia la fase 2 entera.
   Hay que darse de alta y generar la clave.
2. **¿Las fotos subidas son del usuario o del plan?** El frame dice
   «Tus fotos», así que se asumen del usuario y reutilizables entre
   planes. Si fueran del plan, la tabla cambia.
3. **¿Se pueden borrar las fotos subidas?** No aparece en los frames.
   Sin ello la galería sólo crece.

---

## 7. Antes de retomarlo

Comprobar que sigue siendo cierto:

1. El lápiz está en `plan_template.html:229` con `aria-label="Editar portada"`.
2. `planes.portada_url` existe y `api/plan_update.php` la acepta.
3. GD sigue desactivada (`php -r "var_dump(extension_loaded('gd'));"`).
4. Los tres frames siguen en `screens_ref/`.
