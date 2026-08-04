# 🗺️ Plan — Integrar Google Maps y Google Places en `plan.php`

> **Objetivo:** sustituir el contenido *demo* del prototipo (mapa SVG dibujado de Ensenada,
> lugares enlatados de Explorar, panel de detalle con datos fijos) por **Google Maps JS +
> Places reales**, conservando el aspecto del prototipo.
>
> **Principio rector:** los overlays del prototipo (pines con etiqueta, controles de vidrio,
> capas, buscador del mapa, panel de detalle) se conservan tal cual; lo que cambia es *de
> dónde salen los datos*. La plantilla se toca solo en un **paquete acotado de ~9 puntos
> quirúrgicos** (inventariados en §5 — una auditoría adversarial del plan contra el código
> confirmó que "2 ediciones" no alcanzaban: el detalle tiene datos como literales y los
> controles del mapa están cableados a `{{ noop }}`).

---

## 1. Qué es demo hoy (inventario verificado en el código)

| Pieza | Estado actual | Fuente |
|---|---|---|
| Fondo del mapa | SVG dibujado a mano de Ensenada | plantilla, `<svg viewBox="0 0 1000 900">` |
| Pines | `this.PLACES` (5 lugares) posicionados por **`x/y` %** | `V.mapPins` |
| POIs de fondo | `this.POIS` decorativos | `V.mapPois` |
| Ruta | Polilínea fija `'340,860 490,340 465,270'` **dentro del SVG** | `V.routePts` |
| Lugares de Explorar | Los mismos 5 `PLACES` con reseñas/fotos picsum | `rowVM` |
| **Recomendados por día** | `RECS` enlatado ¡con lugares de **La Paz**! (Serpentario, Casa del Artesano, Marina Cortez) | `V.planDays[].recs` |
| Panel de detalle | Dirección/horario/teléfono/web/histograma **como literales de plantilla** | plantilla L1072-1108 |
| Paginador | `{{ resIdx }} de 20` — el "de 20" es literal | plantilla L1007 |
| Buscador del mapa | Abre/escribe; categorías solo cierran | `V.mapSearch*` |
| Zoom/Acércate | Botones con `{{ noop }}` | plantilla L989-1010 |
| Capas de itinerario | `layersIt` fijo a 'Jue. 30/7'/'Vie. 31/7' (`d0/d1`) | plan_logic ~L1103 |

**Ya listo para la integración:** items con `place_id/lat/lng/imagen_url` en BD; `toggleAdd`
persistiendo; patrón de arranque de Maps (`__plMapReady` + promesa) probado en 2 páginas;
geocodificación con persistencia (código de la v2 en respaldo).

---

## 2. Arquitectura: el "proyector"

Los pines del prototipo son `<div>` absolutos sobre el contenedor del mapa. Hoy reciben `%`;
con el mapa real recibirán **píxeles del contenedor**:

```js
class Proyector extends google.maps.OverlayView {
  constructor(comp, map) { super(); this.comp = comp; this.setMap(map); }   // setMap ¡obligatorio!
  onAdd() {} onRemove() {}
  draw() { this.comp._reproject(); }
}
// _reproject(): para cada {id, lat, lng} visible:
//   const pt = proj.fromLatLngToContainerPixel(new google.maps.LatLng(lat, lng));
//   px[id] = { left: pt.x + 'px', top: pt.y + 'px' };
// → setState con throttle de requestAnimationFrame.
```

Decisiones técnicas (corregidas tras la auditoría):

- **`fromLatLngToContainerPixel`**, no `DivPixel`: los pines viven en el contenedor exterior,
  no en los panes internos que Google traslada al arrastrar (con DivPixel se corren tras el
  primer pan).
- `draw()` solo dispara al final del gesto → **re-proyectar también en `bounds_changed` y
  `drag`** del mapa (listeners → `_reproject()` con rAF).
- **Limitación aceptada y documentada:** durante la *animación* de zoom no hay eventos por
  frame; los pines "saltan" a su posición final mientras los tiles animan (~200 ms). Si
  molesta, plan B: bucle rAF durante el gesto o actualización por DOM directo sin `setState`.
- El runtime dc es React con reconciliación real: el `<div>` del mapa **sobrevive a los
  re-renders** mientras esté montado. El peligro es otro (§3.A.7): el `sc-if` de
  `mapVisible` lo **desmonta** en pantallas angostas.
- El div del mapa debe llevar **`z-index:0`** (crear stacking context) para que la
  atribución de Google (z-index ~10⁶) no pinte encima de los pines del prototipo.

---

## 3. Fases de trabajo

### FASE A — Mapa real bajo los overlays (≈ 1 día)

1. **Plantilla (edición 1):** `<svg viewBox="0 0 1000 900">…</svg>` → `<div id="rnGmap"
   style="position:absolute;inset:0;z-index:0"></div>`. Mueren con el SVG: las calles
   dibujadas, las etiquetas de zonas y el `<polyline>` de ruta (se sustituye por Polyline
   nativa). Pines/controles hermanos quedan intactos.
2. **Plantilla (edición 2):** los botones del mapa cambian su binding `{{ noop }}` por
   handlers reales: zoom `+`/`−` → `{{ zoomIn }}`/`{{ zoomOut }}`, "Acércate…" →
   `{{ fitAll }}`, "Acércate a {{ detName }}" → `{{ fitDetail }}` (4-6 sustituciones de
   atributo; **no** se redefine `V.noop`, que usan ~30 botones no relacionados).
3. `plan.php`: script de Maps (`libraries=places&loading=async&callback=__plMapReady`).
4. `plan_logic.js` → `componentDidMount`: `gmapsReady.then(() => this._initMap())` —
   `new google.maps.Map` (centro `PLAN_BOOT.plan.lat/lng`, zoom 13, `disableDefaultUI`,
   `clickableIcons:false`); si el plan no tiene coordenadas, geocodificar `destino` y
   persistir vía `plan_update.php` (código v2). Instanciar el Proyector.
5. **Pines reales:** `_pinData` = items del itinerario con `lat/lng` (color por día) +
   lugares de Explorar al cargarse (Fase B). `V.mapPois = []` (los POIs los pinta el basemap;
   el `sc-for` con lista vacía no requiere tocar plantilla). Dos pines en la misma
   coordenada → offset visual de 6-8 px.
6. **Ruta:** `google.maps.Polyline` nativa por día (color del día), regenerada al cambiar
   `dayItems`, visible según el toggle de capas existente.
7. **Remonte del mapa (hallazgo crítico de la auditoría):** `sc-if value="{{ mapVisible }}"`
   **desmonta** el panel cuando `narrow && !mapModal` — al volver, React crea un `#rnGmap`
   nuevo y la instancia vieja de `Map` queda atada a un nodo muerto (`trigger('resize')` no
   lo repara). Solución: en cada render tras `_initMap`, si `document.getElementById('rnGmap')`
   existe y no es el nodo del mapa actual → **re-crear el mapa** reutilizando centro/zoom
   guardados (`componentDidUpdate` o verificación perezosa en `_reproject`).
8. **Capas por día reales:** `V.layersIt` y `layersNone` se generan desde `this.DAYS`
   (etiqueta/color/clave por índice) en lugar de `d0/d1` fijos.

**Demo M1:** mapa real con pines del itinerario que siguen drag/zoom, ruta por día, zoom y
"Acércate" funcionando, y sin regresión al pasar por ≤1024 px (desmontar/remontar).

### FASE B — Explorar con Places reales (≈ 1 día)

1. `this.PLACES` inicial vacío; al abrir Explorar por primera vez, **2 `textSearch`**
   cacheados (memoria + `sessionStorage` por destino, TTL 1 h):
   `"atracciones turísticas en {destino}"` → sec `top`/cat `atr` (6);
   `"mejores restaurantes en {destino}"` → sec `eat`/cat `com` (6).
2. **Mapeo de campos** (corregido según lo que la API JS clásica devuelve de verdad):

   | Campo del prototipo | Fuente real | Nota |
   |---|---|---|
   | `name` / `rating` / `rev` | `textSearch`: `name`, `rating`, `user_ratings_total` | `starRow` ya soporta fracciones |
   | `chips` | `types` traducidos (diccionario local), filtrando genéricos | |
   | `price` (`$$`) | `price_level` | si falta, `hasPrice` ya lo oculta |
   | foto | `photos[0].getUrl({maxWidth:300})` | fallback picsum |
   | `x/y` | `geometry.location` → Proyector | |
   | `id` | `place_id` real (`p.gpid`) → `toggleAdd` ya lo persiste | |
   | `desc` | ⚠️ `editorial_summary` **NO existe en la API JS clásica** — camino principal: párrafo omitido u opcionalmente 1ª reseña (getDetails, lazy); alternativa: `Place.fetchFields` (API nueva) solo para este campo | corregido por auditoría |
   | `rvw` (reseña de fila) | ⚠️ la plantilla la pinta **incondicionalmente** y `rowVM` haría `p.rvw.t` → TypeError con datos async. Solución: `hasRvw` + `sc-if` (edición de plantilla) y poblarla **lazy** con `getDetails.reviews[0]` solo para filas visibles — o aceptar el costo de ~12 getDetails por destino (decisión de presupuesto) | corregido |
   | `added` | cruce `place_id` ↔ `PLAN_BOOT.items[].place_id`, **sembrando también `_addedSid[place_id] = item.id`** para que des-añadir sí borre en BD | corregido |

3. **Plantilla (edición 3):** `<img src="https://picsum.photos/seed/{{ r.seed }}/…">` →
   `src="{{ r.foto }}"` en: filas de Explorar, tarjetas de guía (`{{ c.foto }}`) y
   **recomendados por día** (`{{ rc.foto }}`) — este último faltaba en el inventario inicial.
4. **`RECS` reales:** los "Lugares recomendados" por día (hoy La Paz enlatada) se alimentan
   con los sobrantes del textSearch (posiciones 7-12), con su foto.
5. `skelPlaces` encendido hasta que responda el textSearch (sustituye el timeout de 700 ms).
6. Hero del plan: se mantiene `portada_url`/picsum (fuera de alcance; anotado como mejora:
   primera foto de Places del destino).

**Demo M2:** Explorar con lugares reales (estrellas fraccionarias, conteos, chips, fotos),
"Añadir" persiste el `place_id` real, el pin aparece proyectado, y quitar un lugar añadido
en una sesión anterior **sí** lo borra de la BD.

### FASE C — Panel de detalle real (≈ 1 día)

1. **Plantilla (edición 4 — la más extensa):** los literales del panel se convierten en
   bindings con flags de presencia: dirección → `{{ detAddress }}`, horario de hoy →
   `{{ detHoursToday }}` (+ día actual resaltado en los círculos `do…sá`), teléfono →
   `{{ detPhone }}`, sitio web → `{{ detWebsite }}`, botones "Abrir en Google / Google maps"
   → `{{ detOpenUrl }}`, encabezado de Reseñas → `{{ detRvwAvg }}/{{ detRvwLabel }}/{{ detRvwCount }}`,
   paginador → `{{ resIdx }} de {{ resTotal }}`, y las 3 fotos fijas del tab Fotos →
   `{{ detFoto1..3 }}` (son 3 `<img>` estáticas, no un bucle — cambio mínimo).
2. `openDetail(placeId)`: `getDetails` con fields mínimos (`name, rating, user_ratings_total,
   formatted_address, formatted_phone_number, website, opening_hours, photos, geometry,
   reviews, url`), **caché por `place_id`**, y `detailLoading` se apaga en el callback
   (sustituye el timeout de 550 ms; el skeleton del prototipo se conserva).
3. **El detalle se resuelve contra la caché de getDetails, no contra `PLACES`** — hallazgo de
   auditoría: hoy un clic en un pin del itinerario deja `detailLoading` colgado porque
   `place(id)` no lo encuentra. Items custom sin `place_id` → mini-detalle local (nombre +
   nota) o no abren panel.
4. `resIdx`/`resTotal` reales: índice del lugar dentro de su sección; `resPrev/resNext`
   navegan entre lugares cargados y sincronizan pin/detalle.
5. **Sin equivalente en la API** (se ocultan con flags, no se inventan): histograma de
   reseñas, "Mencionado por +N listas", "La gente suele pasar X min aquí", tips "Saber antes
   de ir".

### FASE D — Buscador del mapa (≈ ½ día, opcional para la entrega)

- Input → `textSearch({ query, bounds: map.getBounds() })`; categorías → `type`
  (`restaurant`, `tourist_attraction`, `gas_station`, `electric_vehicle_charging_station`;
  "Paradas de descanso" no tiene type en la API clásica → query textual). Requiere cambiar
  el binding de las 5 categorías (hoy solo cierran el panel) — se suma a la edición 2.
- Resultados → pines temporales teal; clic → detalle (vía caché de Fase C).

---

## 4. Costos y cuotas

| Llamada | Cuándo | Mitigación |
|---|---|---|
| Maps JS load | 1 por visita | — |
| Geocoding | 1 vez por plan (se persiste) | ya implementado así |
| textSearch ×2 | 1ª apertura de Explorar | caché memoria + sessionStorage (TTL 1 h) |
| getDetails | al abrir un detalle (cacheado) | fields mínimos; `reviews`/`photos` solo aquí |
| getDetails para `rvw` de filas | **decisión pendiente** (§B.2): lazy por visibilidad o omitir | el plan por defecto: omitir en v1 (flag `hasRvw`) |
| Directions | **no se usa** (polyline geodésica gratis) | — |

**Urgente al integrar:** restringir la API key en Google Cloud Console por **referrer**
(`http://localhost/*` + dominio futuro) y por API (Maps JS, Places, Geocoding).

---

## 5. Cambios por archivo (inventario honesto)

| Archivo | Cambios |
|---|---|
| `plan_template.html` | **~9 puntos quirúrgicos**: (1) SVG→div con z-index:0; (2) bindings de zoom/Acércate/categorías del buscador; (3) picsum→`{{ foto }}` en filas/guías/recomendados; (4) literales del panel de detalle→`{{ det* }}` con flags + "de {{ resTotal }}" + 3 fotos; (5) `sc-if hasRvw` en la reseña de fila |
| `plan.php` | + script de Google Maps con promesa `gmapsReady` |
| `js/plan_logic.js` | + `_initMap` (con manejo de **remonte** por `mapVisible`), Proyector (`ContainerPixel` + `bounds_changed`/`drag`), `_loadPlaces` (textSearch+mapeo+RECS), `_details` cacheado, handlers zoom/fit, `V.mapPins` a px, `V.mapPois=[]`, `layersIt` desde `DAYS`, Polyline nativa, siembra de `_addedSid` desde boot |
| BD / API PHP | **Sin cambios** |

## 6. Riesgos (ampliados por la auditoría)

1. Items custom sin `lat/lng`: no se proyectan (mejora futura: geocodificarlos al crear).
2. Places deshabilitada/cuota: aviso en Explorar; el mapa sigue.
3. Jitter del proyector: throttle rAF; **snap visual durante la animación de zoom** (aceptado
   y documentado); plan B por DOM directo.
4. `fitBounds` con 1 pin → centro + zoom 14 (lección de la v2).
5. Destino no geocodificable → centro México zoom 5 + aviso.
6. **Desmonte del mapa en ≤1024 px** → re-creación controlada (§A.7) — probar el ciclo
   angosto↔ancho y modal abierto/cerrado explícitamente.
7. Pines duplicados en la misma coordenada → offset 6-8 px.
8. TypeError por `rvw` ausente en datos async → flags `has*` en TODO campo opcional del
   view-model antes de conectar datos reales.

## 7. Orden e hitos

| Hito | Contenido | Verificación |
|---|---|---|
| **M1** | Fase A | Mapa real; pines siguen drag/zoom; ruta y controles vivos; ciclo narrow sin romper |
| **M2** | Fase B | Lugares reales; añadir→pin+BD; des-añadir borra en BD; RECS reales |
| **M3** | Fase C | Detalle con datos de Google; pin de itinerario abre detalle; paginador real |
| **M4** | Fase D + restringir key + QA (destino chico, sin cuota, 1 solo pin, N días) | Checklist |

**Estimación total: 3.5–4 días efectivos** (la auditoría subió ~1 día respecto al borrador:
el panel de detalle y el manejo del desmonte no estaban presupuestados).
