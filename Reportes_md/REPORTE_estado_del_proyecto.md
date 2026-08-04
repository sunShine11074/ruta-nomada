# 🧭 Reporte — Estado actual del proyecto Ruta Nómada (v1)

> **Fecha:** 15 de julio de 2026
> **Ubicación del proyecto:** `C:\xampp\htdocs\Ruta Nómada (v1)` → http://localhost/Ruta%20N%C3%B3mada%20(v1)/login.php
> **Propósito de este documento:** dar una fotografía completa y honesta del proyecto —qué es,
> cómo está construido, qué funciona, qué está a medias y qué falta— para compañeros del equipo,
> profesores y como referencia propia.

---

## 1. ¿Qué es Ruta Nómada?

**Ruta Nómada** es una aplicación web de **planeación de viajes**: el usuario se registra, explora
destinos, busca ciudades reales (con mapa, clima y lugares de Google), guarda favoritos y arma
**itinerarios día por día** con presupuesto, al estilo de Wanderlog/TripAdvisor.

### Stack tecnológico

| Capa | Tecnología |
|---|---|
| Servidor | PHP 8.2 sobre XAMPP (Apache) |
| Base de datos | MariaDB 10.4 (phpMyAdmin como cliente), acceso con **PDO + consultas preparadas** |
| Front-end | HTML + CSS puro (fuente Poppins) + JavaScript vanilla (sin frameworks) |
| Correo | PHPMailer 6.9.3 (instalación manual en `libs/`, sin Composer) vía Gmail SMTP |
| Librería externa JS | SortableJS (drag & drop del planificador, vía CDN) |
| APIs externas | Google Maps/Places, Open-Meteo, Wikipedia, CountryStateCity, tipos de cambio (ver §6) |

No se usa ningún framework PHP ni JS: todo el enrutamiento es **una página = un archivo `.php`**,
con componentes compartidos en `includes/`.

---

## 2. Arquitectura y estructura de carpetas

```
Ruta Nómada (v1)/
├── login.php · register.php              ← autenticación
├── forgot-password.php · reset-password.php  ← recuperación de contraseña por correo
├── inicio.php                            ← panel principal (antes dashboard.php)
├── resultados.php                        ← resultados de búsqueda de ciudad (mapa + lugares)
├── destino.php                           ← detalle de un destino de la BD
├── crear_plan.php                        ← planificador de itinerarios (SPA de 3 pantallas)
├── mis_planes.php                        ← viajes/planes del usuario
├── profile.php · configuracion.php       ← perfil (validado) y configuración
├── mis_guardados.php · guias.php         ← placeholders ("Próximamente")
├── admin_prin.php                        ← panel de administración de destinos
├── db.php                                ← conexión PDO (singleton, variables de entorno)
├── mailer.php                            ← envío de correos con PHPMailer
├── geo.php                               ← proxy PHP hacia la API CountryStateCity
├── includes/
│   ├── topbar.php                        ← topbar global (2 variantes) + menú de avatar + burbuja chatbot
│   ├── user_topbar.php                   ← datos del usuario para el topbar (foto/inicial)
│   ├── currency.php                      ← divisas, tipos de cambio y conversión de precios
│   ├── geo_lib.php                       ← lógica del proxy geográfico + validación servidor
│   ├── mail_config.php  🔒               ← credenciales SMTP (NO se versiona)
│   └── geo_config.php   🔒               ← API key de CountryStateCity (NO se versiona)
├── js/  (topbar.js · profile.js · resultados.js · crear_plan.js · sidebar.js†)
├── css  (style.css · topbar.css · resultados.css · crear_plan.css)
├── basedatos/  (migrate_profile.sql · migrate_plan.sql · server.js‡ · package.json‡)
├── libs/PHPMailer/                       ← PHPMailer manual (3 archivos)
├── cache/                                ← caché en disco: tipos de cambio y geografía (NO se versiona)
├── img/  (logo.png · profiles/ = fotos subidas por usuarios)
├── Reportes_md/                          ← documentación del equipo (este archivo y 5 más)
└── given_context/ · brief_resultados_nuevo.md  ← material de diseño/handoff (referencia)
```

† `js/sidebar.js` es **legado**: el sidebar se eliminó y lo reemplazó el menú del avatar; el archivo quedó sin uso.
‡ `basedatos/server.js` + `package.json` son un **experimento Node/Express ajeno al proyecto** (apunta a otra BD
y trae credenciales en texto plano); se recomienda **eliminarlo** (ver §8).

**Patrón general:** cada página protegida empieza con `session_start()` → redirige a `login.php`
si no hay sesión → incluye `includes/topbar.php` → consulta la BD con PDO → imprime HTML escapado
con `htmlspecialchars()`.

---

## 3. Estado de cada página

| Página | Estado | Qué hace hoy |
|---|---|---|
| `login.php` | ✅ Funcional | Inicio de sesión con `password_verify`, "recordar correo" (cookie), enlaces a registro y recuperación |
| `register.php` | ✅ Funcional | Alta de usuario con `password_hash`, validaciones servidor |
| `forgot-password.php` | ✅ Funcional | Envía correo real (PHPMailer + Gmail) con enlace de restablecimiento; diseño fiel al mockup |
| `reset-password.php` | ✅ Funcional | Valida token (hash SHA-256, expira en 1 h, un solo uso) y cambia la contraseña |
| `inicio.php` | 🟡 Parcial | Panel principal: destinos de la BD por categoría (Cultura/Romance/Aventura/Descubrimiento), precios convertidos a la divisa del usuario. El **buscador propio de la página** aún no existe (por eso su topbar no trae buscador) y la sección "Recientes" usa datos de ejemplo |
| `resultados.php` | ✅ Funcional | La página más completa: búsqueda de ciudad → carrusel de fotos (Google Places), descripción (Wikipedia), clima y calidad del aire (Open-Meteo) con SVGs animados, pestañas Hoteles/Cosas que hacer/Restaurantes (9 tarjetas con rating real), favoritos, y **mapa fijo de Google** con pines sincronizados y modo expandido |
| `destino.php` | ✅ Funcional | Detalle de un destino de la BD + conversor de divisas |
| `profile.php` | ✅ Funcional | Edición de perfil con **validación campo por campo** (servidor + cliente): correo con dominio real (registro MX), teléfono MX 10/12 dígitos, fecha de nacimiento en rango, cascada real País→Estado→Ciudad con banderas, selección de divisa que afecta **todo el sitio**; subida de foto de perfil y banner |
| `crear_plan.php` | 🟡 Parcial | **Planificador de itinerarios** (SPA de 3 pantallas: Crear → Invitar → Planificador). Ya tiene: calendario doble de fechas, privacidad (Solo yo/Amigos/Público), UI de invitaciones, itinerario día por día con **drag & drop** (SortableJS), lugares guardados, presupuesto y gastos por categoría, **ruta en el mapa de Google** (pines numerados + polilínea), impresión. **Limitación:** todo se guarda en `localStorage` con datos demo — todavía **no persiste en la BD** ni es colaborativo en tiempo real |
| `mis_planes.php` | 🟡 Parcial | Lista los viajes del usuario (`viajes_usuario` + `planes`). El encabezado interno aún dice "Mis viajes" y las estadísticas (confirmados/presupuesto) son de relleno |
| `configuracion.php` | 🟡 Maqueta | Interfaz de notificaciones/preferencias **sin lógica de guardado** |
| `mis_guardados.php` | ⬜ Placeholder | "Próximamente" — mostrará los favoritos (♥) |
| `guias.php` | ⬜ Placeholder | "Próximamente" — guías de viaje |
| `admin_prin.php` | 🟠 Funcional con riesgo | CRUD de destinos (buscar, filtrar, estadísticas). **Cualquier usuario logueado puede entrar** (no valida rol de administrador) y conserva la interfaz vieja |
| `geo.php` | ✅ Funcional | Proxy servidor para CountryStateCity: la API key nunca llega al navegador; respuestas cacheadas en `cache/geo/` |

---

## 4. Componentes compartidos

### Topbar global (`includes/topbar.php` + `topbar.css` + `js/topbar.js`)
- Reemplazó por completo al sidebar antiguo (documentado en `REPORTE_topbar_y_sidebar.md`).
- **Dos variantes:** con buscador (todas las páginas) y sin buscador (`$topbar_search = false` en Inicio).
- Navegación de 3 opciones: **Inicio · Mis planes · Guías de viaje** (+ botón "Crear plan de viaje").
- Buscador con selector de categoría (Ciudad/Hoteles/Restaurantes/Cosas que hacer) → envía a `resultados.php?q=…&tab=…`.
- Menú del avatar: Mi perfil · Mis guardados · Configuración · Cerrar sesión.
- Burbuja flotante del **chatbot** (por ahora solo panel informativo "Próximamente").

### Divisas (`includes/currency.php`)
- 9 divisas soportadas (MXN, USD, EUR, GBP, CAD, JPY, BRL, COP, ARS).
- Tipos de cambio desde `open.er-api.com` con **caché diaria** en `cache/rates.json`.
- `priceInUserCurrency()` convierte los precios (guardados en MXN) a la divisa que el usuario eligió en su perfil; si la API falla, muestra MXN.

### Geografía (`includes/geo_lib.php` + `geo.php`)
- Cascada Nacionalidad → Estado → Ciudad con datos reales de CountryStateCity.
- `geoValidate()` re-verifica **en el servidor** que el país/estado/ciudad enviados existan de verdad (no basta con manipular el HTML).
- Banderas vía flagcdn.com.

### Correo (`mailer.php` + `includes/mail_config.php`)
- PHPMailer con Gmail SMTP (contraseña de aplicación) y logo embebido (CID).
- `mail_config.php` define remitente, credenciales y `base_url` — **cada integrante debe crear su copia local** (está en `.gitignore`).

---

## 5. Base de datos (`ruta_nomada`)

### Tablas en uso

| Tabla | Propósito |
|---|---|
| `usuarios` | Cuentas + 11 columnas de perfil (apellidos, género, teléfono, fecha_nacimiento, nacionalidad, estado, ciudad, lenguaje, **divisa**, foto_perfil, foto_banner) — agregadas con `basedatos/migrate_profile.sql` |
| `destinos` | Catálogo de destinos (nombre, país, categoría, precio_desde en MXN, valoración, estado, descripción, imagen) — lo administra `admin_prin.php` |
| `favoritos` | Destinos marcados con ♥ por usuario |
| `planes` | Planes de viaje (nombre, fecha_inicio, fecha_fin) |
| `plan_destinos` | Relación plan ↔ destinos |
| `viajes_usuario` | Viajes del usuario, con `plan_id` hacia `planes` |
| `password_resets` | Tokens de recuperación: **hash SHA-256** del token, expiración 1 h, un solo uso |

### Migración pendiente de conectar: `basedatos/migrate_plan.sql`
Ya está escrito el esquema para que el planificador persista en BD (hoy usa `localStorage`):

- `plan_items` — tarjetas del itinerario (día, orden, categoría, hora, precio, `place_id` de Google, lat/lng…).
- `plan_miembros` — colaboradores con rol (propietario/editor/lector).
- `plan_invitaciones` — invitaciones por enlace con token hasheado (mismo patrón que `password_resets`).
- `plan_gastos` — gastos por categoría para el presupuesto.

**Siguiente paso natural del proyecto:** ejecutar esta migración y crear los endpoints PHP para que
`crear_plan.php` lea/escriba en estas tablas en lugar de `localStorage`.

---

## 6. APIs externas

| API | ¿Clave? | Dónde se usa | Para qué |
|---|---|---|---|
| **Google Maps JavaScript + Places + Geocoding** | Sí (visible en el cliente, ver §8) | `resultados.php`, `crear_plan.php` | Mapa interactivo, fotos y fichas de lugares, ratings, geocodificación de la ciudad buscada |
| **Open-Meteo** (forecast + air quality) | No | `resultados.php` | Clima actual, UV, humedad, viento, calidad del aire, hora local |
| **Wikipedia REST (es)** | No | `resultados.php` | Descripción de la ciudad |
| **open.er-api.com** | No | Todo el sitio (vía `currency.php`) | Tipos de cambio MXN → divisa del usuario (caché diaria) |
| **Frankfurter** | No | `destino.php` | Conversor de divisas de la página de destino |
| **CountryStateCity** | Sí (**solo en servidor**, vía `geo.php`) | `profile.php` | Países, estados y ciudades reales |
| **flagcdn.com** | No | `profile.php` | Banderas de países |
| **SortableJS (CDN)** | No | `crear_plan.php` | Drag & drop del itinerario |

El detalle completo de cada API está en `Reportes_md/APIS_ruta_nomada.md`.

---

## 7. Seguridad implementada (lo que sí se hizo bien)

1. **Contraseñas:** `password_hash()` / `password_verify()` (bcrypt); nunca en texto plano.
2. **SQL:** 100 % consultas preparadas con PDO (`ATTR_EMULATE_PREPARES = false`) — sin concatenación de SQL.
3. **XSS:** salida escapada con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
4. **Recuperación de contraseña:** token aleatorio de 64 hex (`random_bytes`), en BD solo su **hash SHA-256**, expira en 1 hora y se invalida al usarse; el correo no revela si la cuenta existe.
5. **Secretos fuera de Git:** `mail_config.php`, `geo_config.php`, `cache/`, `libs/` en `.gitignore`.
6. **Claves de servidor protegidas:** la key de CountryStateCity jamás viaja al navegador (proxy `geo.php`).
7. **Validación doble:** todo lo que valida JavaScript se re-valida en PHP (correo con `checkdnsrr` MX, teléfono, fechas, geografía con `geoValidate`).
8. **Errores de BD:** el detalle se manda a `error_log`, al usuario solo un mensaje genérico.

---

## 8. Debilidades y riesgos conocidos (pendientes honestos)

| # | Riesgo | Detalle | Acción recomendada |
|---|---|---|---|
| 1 | 🔴 `admin_prin.php` sin control de rol | Cualquier usuario con sesión puede administrar destinos | Agregar columna/campo de rol y verificarlo al inicio de la página |
| 2 | 🔴 API key de Google **sin restricciones** | La key es visible en el HTML (eso es normal en Maps JS), pero al no estar restringida cualquiera podría usarla y generar cargos | En Google Cloud Console: restringir por **referrer** (`localhost/*` y el dominio futuro) y limitar a las 3 APIs usadas |
| 3 | 🔴 `basedatos/server.js` ajeno al proyecto | Servidor Express de otra práctica con usuario/contraseña de MySQL **en texto plano** y apuntando a otra BD | Eliminar `server.js` y `package.json` de la carpeta |
| 4 | 🟠 Dos servidores MySQL en la misma máquina | Conviven el servicio **MySQL80** (de Windows) y **MariaDB de XAMPP**; si ambos corren, pelean por el puerto 3306 — esto ya causó corrupción de datos antes | Deshabilitar el servicio MySQL80 (o ponerlo en manual) y arrancar/parar MariaDB **solo** desde el panel de XAMPP |
| 5 | 🟠 Sin tokens CSRF | Los formularios (perfil, admin, login) no llevan token anti-CSRF | Añadir token de sesión en formularios que modifican datos |
| 6 | 🟡 Planificador sin persistencia | `crear_plan.php` guarda en `localStorage`: se pierde al cambiar de navegador y no permite colaboración real | Aplicar `migrate_plan.sql` + endpoints PHP (ver §5) |
| 7 | 🟡 Usuario `root` sin contraseña | Configuración por defecto de XAMPP; aceptable en desarrollo local, inaceptable en producción | Definir usuario dedicado con contraseña vía variables de entorno (db.php ya las soporta) |
| 8 | 🟡 Páginas maqueta | `configuracion.php` no guarda; `mis_guardados.php` y `guias.php` son placeholders; estadísticas de `mis_planes.php` de relleno | Priorizar según la rúbrica de la materia |
| 9 | ⚪ Detalles menores | Encabezado "Mis viajes" en `mis_planes.php`; `js/sidebar.js` sin uso; los 4 `if` de categoría en `inicio.php` ejecutan la misma consulta (se puede reducir a una con lista blanca) | Limpieza rápida |

---

## 9. Recorrido del usuario (flujo actual)

1. **Registro** (`register.php`) → **Login** (`login.php`). Si olvidó su contraseña: correo real de recuperación.
2. **Inicio** (`inicio.php`): destinos por categoría con precios en su divisa.
3. **Buscar una ciudad** desde el topbar → **`resultados.php`**: fotos, clima, descripción,
   hoteles/actividades/restaurantes reales y mapa fijo con pines.
4. **Guardar** favoritos (♥) — se verán en *Mis guardados* cuando se construya.
5. **Crear plan de viaje** (`crear_plan.php`): destino + fechas + privacidad → invitar compañeros →
   armar el itinerario día por día arrastrando lugares, con presupuesto y ruta en el mapa.
6. **Perfil** (`profile.php`): datos personales validados y elección de divisa que afecta todo el sitio.

---

## 10. Documentación existente en `Reportes_md/`

| Archivo | Tema |
|---|---|
| `APIS_ruta_nomada.md` | Todas las APIs externas, claves y cachés |
| `REPORTE_topbar_y_sidebar.md` | Rediseño del topbar y retiro del sidebar |
| `REPORTE_navegacion_y_renombres.md` | Navegación de 3 opciones y renombres de archivos |
| `REPORTE_resultados_nuevo.md` | Reconstrucción de `resultados.php` desde el handoff de diseño |
| `FUNCIONALIDADES_itinerario_wanderlog.md` | Funcionalidades de Wanderlog como guía para `crear_plan.php` |
| `REPORTE_estado_del_proyecto.md` | **Este documento** |

---

## 11. Cómo ejecutar el proyecto (resumen para nuevos integrantes)

1. Instalar **XAMPP** y colocar la carpeta en `C:\xampp\htdocs\Ruta Nómada (v1)`.
2. Arrancar **Apache** y **MySQL solo desde el panel de XAMPP** (nunca a mano; ver riesgo #4).
3. Crear la BD `ruta_nomada` e importar el dump del equipo; ejecutar `basedatos/migrate_profile.sql`
   si la tabla `usuarios` no tiene las columnas de perfil.
4. Crear los archivos de configuración locales (no vienen en el repositorio):
   - `includes/mail_config.php` — credenciales SMTP de Gmail (contraseña de aplicación) y `base_url`.
   - `includes/geo_config.php` — API key de CountryStateCity.
5. Verificar que `libs/PHPMailer/` tenga los 3 archivos (PHPMailer.php, SMTP.php, Exception.php).
6. Abrir **http://localhost/Ruta%20N%C3%B3mada%20(v1)/login.php**.

---

## 12. Próximos pasos sugeridos (en orden de impacto)

1. **Persistencia del planificador:** aplicar `migrate_plan.sql` y conectar `crear_plan.php` a la BD
   (guardar/cargar plan, items, gastos). Es el corazón del producto.
2. **Invitaciones reales:** generar enlaces con token (la tabla `plan_invitaciones` ya está diseñada)
   y reutilizar `mailer.php` para enviarlas.
3. **Control de rol** en `admin_prin.php` + su migración al topbar nuevo.
4. **Restringir la API key de Google** (referrer + APIs específicas).
5. **Mis guardados:** listar los favoritos existentes (la tabla `favoritos` y los ♥ ya funcionan).
6. **Buscador interno de `inicio.php`** (razón de ser de la variante de topbar sin buscador).
7. Persistir `configuracion.php` y limpiar los detalles menores del riesgo #9.
