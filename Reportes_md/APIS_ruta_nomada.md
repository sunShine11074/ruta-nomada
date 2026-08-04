# 🔌 APIs y servicios externos en Ruta Nómada

> Inventario para el equipo: qué APIs usa el proyecto hasta ahora, en qué archivos
> están y cómo se usan. Última actualización: julio 2026.

---

## Resumen rápido

| # | API / Servicio | ¿Key? | ¿Dónde vive la key? | Se usa en |
|---|---|---|---|---|
| 1 | Google Maps JavaScript API | Sí | En el cliente (script tag) | `explore.php`, `resultados.php` |
| 2 | Google Geocoding API | Sí (la misma de Maps) | En el cliente | `js/resultados.js` |
| 3 | Google Places API | Sí (la misma de Maps) | En el cliente | `js/resultados.js` |
| 4 | CountryStateCity API | Sí | **Solo en el servidor** (`includes/geo_config.php`) | `geo.php`, `profile.php` |
| 5 | Open-Meteo (clima + aire) | No | — | `js/resultados.js` |
| 6 | Wikipedia REST API | No | — | `js/resultados.js` |
| 7 | ExchangeRate (open.er-api.com) | No | — | `includes/currency.php` |
| 8 | Frankfurter API | No | — | `destino.php` |
| 9 | flagcdn.com (banderas) | No | — | `destino.php`, `profile.php`, `js/profile.js` |
| 10 | Gmail SMTP (PHPMailer) | App Password | **Solo en el servidor** (`includes/mail_config.php`) | `mailer.php`, `forgot-password.php` |

---

## 1. Google Maps JavaScript API
- **Qué hace:** renderiza los mapas interactivos.
- **Dónde:**
  - `explore.php` → mapa simple centrado en un destino.
  - `resultados.php` → mapa fijo de la página de resultados, con pines numerados
    sincronizados con las tarjetas, botón de expandir, zoom y centrar (`js/resultados.js`).
- **Cómo:** se carga con `<script src="https://maps.googleapis.com/maps/api/js?key=…&libraries=places&loading=async&callback=…">`.
- **Nota:** la key de Google es visible en el navegador **por diseño** (así funcionan las keys
  de Maps). Su protección no es esconderla, sino **restringirla por HTTP referrer**
  (`http://localhost/*`) y por API en Google Cloud Console. ⚠ Pendiente de configurar.
- En el proyecto de Google Cloud deben estar habilitadas: **Maps JavaScript API,
  Geocoding API y Places API**.

## 2. Google Geocoding API
- **Qué hace:** convierte el texto de la búsqueda ("Hermosillo") en coordenadas y componentes
  (ciudad, estado, país, ISO del país).
- **Dónde:** `js/resultados.js` → función `geocodeCity()` con `google.maps.Geocoder`.
- **Para qué:** centrar el mapa, armar el **breadcrumb** (Continente › País › Ciudad — el
  continente se deriva del ISO del país) y el **título** ("Hermosillo, Sonora").

## 3. Google Places API
- **Qué hace:** entrega lugares reales con fotos, calificación y reseñas.
- **Dónde:** `js/resultados.js`:
  - `places.getDetails(place_id de la ciudad)` → hasta **10 fotos del hero** con la
    **atribución del autor** y el contador de fotos.
  - `places.nearbySearch({location, radius: 12000, type})` → las **9 tarjetas** por pestaña:
    `tourist_attraction` (Cosas que hacer), `restaurant` (Restaurantes), `lodging` (Hoteles).
- **Detalles:** fuera de la pestaña Hoteles se filtran los resultados tipo `lodging`
  (Google mete hoteles en otras categorías); los tipos se traducen a español con un
  mapa interno (`museum` → "Museo", etc.).

## 4. CountryStateCity API (`api.countrystatecity.in`)
- **Qué hace:** catálogo real de países → estados → ciudades.
- **Dónde:**
  - `includes/geo_config.php` → **la key (NO se versiona**, está en `.gitignore`; cada quien
    genera la suya gratis en https://countrystatecity.in).
  - `includes/geo_lib.php` → funciones `cscCountries/cscStates/cscCities` + `geoValidate`,
    con **caché en disco** (`cache/geo/`) para no gastar cuota.
  - `geo.php` → **proxy** que consume el navegador (la key nunca sale al cliente):
    `geo.php?type=countries`, `?type=states&country=MX`, `?type=cities&country=MX&state=SON`.
- **Para qué:** en `profile.php`, la cascada **Nacionalidad → Estado → Ciudad** (con bandera
  del país) y la **re-validación en el servidor** al guardar (rechaza ciudades/estados inventados).

## 5. Open-Meteo (`api.open-meteo.com` + `air-quality-api.open-meteo.com`)
- **Qué hace:** clima actual y calidad del aire. **Gratis y sin key.**
- **Dónde:** `js/resultados.js` → `loadWeather()` (dos `fetch` en paralelo).
- **Para qué:** la sección "Clima actual" de resultados: temperatura, condiciones (código WMO
  mapeado a 10 condiciones en español), viento, humedad, **UV**, **calidad del aire** (US AQI →
  Buena/Regular/Mala), salida y puesta de sol (con `timezone=auto` para usar la hora local
  de la ciudad en el arco solar).

## 6. Wikipedia REST API (`es.wikipedia.org/api/rest_v1`)
- **Qué hace:** resumen enciclopédico de la ciudad. **Gratis y sin key.**
- **Dónde:** `js/resultados.js` → `wikiSummary()`.
- **Para qué:** la **descripción** de la ciudad con "Leer más / Leer menos". Si el artículo
  no existe se intenta con "Ciudad (Estado)" y, si tampoco, el bloque se oculta.

## 7. ExchangeRate (`open.er-api.com`)
- **Qué hace:** tasas de cambio MXN → todas las divisas. **Gratis y sin key.**
- **Dónde:** `includes/currency.php` → `mxnRates()`, con **caché diaria** en `cache/rates.json`
  (una sola llamada al día).
- **Para qué:** la **divisa global del usuario** (elegida en el perfil): los precios de
  `dashboard.php`, `explore.php` y `destino.php` se muestran convertidos con
  `priceInUserCurrency()`. Se eligió esta API porque cubre COP y ARS (Frankfurter no).

## 8. Frankfurter API (`api.frankfurter.dev`)
- **Qué hace:** tipo de cambio puntual entre dos monedas. Sin key.
- **Dónde:** `destino.php` → el **conversor interactivo** de la ficha de destino
  (dropdown con banderas: elige moneda y convierte el precio en vivo).

## 9. flagcdn.com
- **Qué hace:** imágenes de banderas por código de país (`https://flagcdn.com/16x12/mx.png`).
- **Dónde:** dropdown de divisas de `destino.php`, y en `profile.php`/`js/profile.js`
  (bandera de la nacionalidad y de la divisa elegida).

## 10. Gmail SMTP vía PHPMailer
- **Qué hace:** envía el correo real de **recuperación de contraseña**.
- **Dónde:**
  - `mailer.php` → `enviarCorreoRecuperacion()` (PHPMailer instalado en `libs/PHPMailer/`,
    sin Composer; correo HTML con el logo embebido por CID).
  - `includes/mail_config.php` → host/puerto/credenciales (**App Password de Gmail**,
    NO la contraseña normal) y `base_url` para armar el enlace. **No se versiona.**
- **Flujo:** `forgot-password.php` genera un token de un solo uso (solo se guarda su hash
  en la tabla `password_resets`, caduca en 1 hora) → correo con enlace → `reset-password.php`.

---

## Reglas del equipo sobre keys 🔑

1. **Nunca subir keys al repositorio.** `includes/mail_config.php` y `includes/geo_config.php`
   están en `.gitignore` — cada quien crea su copia local.
2. La key de **Google** es la única que va en el cliente (es inevitable en Maps); por eso debe
   **restringirse por referrer y por API** en Google Cloud Console.
3. Las APIs sin key (Open-Meteo, Wikipedia, er-api, Frankfurter, flagcdn) se consumen
   directo del navegador; las que tienen key secreta (CountryStateCity, Gmail) se consumen
   **solo desde PHP** (proxy/servidor).
4. La carpeta `cache/` (tasas y geo) se genera sola en runtime y también está ignorada.

## Qué necesita cada compañero para que todo funcione

| Necesita | Dónde |
|---|---|
| Key de Google con Maps JS + Geocoding + Places habilitadas | script tags de `explore.php` y `resultados.php` |
| Key gratuita de CountryStateCity | `includes/geo_config.php` |
| Gmail con App Password | `includes/mail_config.php` (+ ajustar `base_url` a su carpeta) |
| Nada (sin key) | Open-Meteo, Wikipedia, er-api, Frankfurter, flagcdn |
