# 🔌 Plan de cableado — Vista del Plan de Viaje (`plan.php`)

> **Objetivo:** convertir el prototipo `Trip Planning Feature Design/Vista del plan (Ensenada).dc.html`
> (Claude Design, corre sobre React) en una página real de Ruta Nómada — **PHP + JS vanilla,
> Google Maps, y persistencia en MariaDB** — integrada con el flujo Crear → Invitar existente.
>
> **Patrón a seguir:** el mismo que ya funcionó dos veces: el `.dc.html` es la *especificación*
> visual/funcional; la implementación se reescribe a mano como `resultados.php`/`js/resultados.js`
> y `crear_plan.php`/`js/crear_plan.js`.

---

## 0. Decisiones de arquitectura

| Decisión | Elección | Por qué |
|---|---|---|
| ¿Página nueva o ampliar `crear_plan.php`? | **Página nueva `plan.php?id=N`** | `crear_plan.php` conserva las pantallas Crear → Invitar; al confirmar, **crea el plan en BD y redirige** a `plan.php?id=N`. Separa "asistente de creación" de "espacio de trabajo del plan" |
| Persistencia | **BD desde el día 1** (adiós `localStorage`) | El plan es colaborativo; `localStorage` muere con el navegador |
| Backend | **Carpeta `api/` con endpoints JSON** (`api/plan_*.php`) | El front es una SPA que guarda cambio por cambio (autosave); el patrón `geo.php` (PHP que responde JSON) ya existe |
| Guardado | **Autosave optimista**: la UI aplica el cambio y un `fetch` lo persiste; si falla, revierte y avisa | Es como se siente el prototipo (sin botón "Guardar") |
| Colaboración en vivo | **Fase posterior** con *polling* ligero (cada 20–30 s, `updated_at`) | WebSockets no es viable en XAMPP/Apache sin infra extra |
| Mapa | **Google Maps JS + Places** (key actual) | Igual que `resultados.php`; el SVG del prototipo es decorativo |
| Asistente de IA | **Fase A:** respuestas enlatadas portadas del prototipo · **Fase B:** proxy PHP → API de Claude | La UI completa se cablea desde el inicio; la inteligencia llega después sin tocar el front |

---

## 1. FASE BD — Migraciones (½ día)

**1.1** Ejecutar `basedatos/migrate_plan.sql` (ya escrito): `plan_items`, `plan_miembros`,
`plan_invitaciones`, `plan_gastos`.

**1.2** Crear `basedatos/migrate_plan_v2.sql` con los ajustes que el prototipo exige:

```sql
-- El plan necesita destino, privacidad, portada y presupuesto
ALTER TABLE planes
  ADD COLUMN destino     VARCHAR(120) DEFAULT NULL,
  ADD COLUMN lat         DECIMAL(10,7) DEFAULT NULL,
  ADD COLUMN lng         DECIMAL(10,7) DEFAULT NULL,
  ADD COLUMN privacidad  ENUM('solo','amigos','publico') NOT NULL DEFAULT 'solo',
  ADD COLUMN portada_url VARCHAR(500) DEFAULT NULL,
  ADD COLUMN presupuesto DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Los items del prototipo tienen hora de inicio Y fin ("5:00 - 6:30")
ALTER TABLE plan_items ADD COLUMN hora_fin TIME DEFAULT NULL;

-- Los gastos muestran fecha visible ("30 jul.") y el demo usa 'Gasolina'
ALTER TABLE plan_gastos
  ADD COLUMN fecha DATE DEFAULT NULL,
  MODIFY categoria ENUM('Alojamiento','Comida','Actividades','Transporte',
                        'Compras','Gasolina','Otro') NOT NULL DEFAULT 'Otro';

-- Listas del Resumen (notas y checklists)
CREATE TABLE plan_listas (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plan_id  INT UNSIGNED NOT NULL,
  titulo   VARCHAR(255) NOT NULL DEFAULT '',
  tipo     ENUM('nota','check') NOT NULL,
  texto    TEXT DEFAULT NULL,           -- cuerpo de la nota
  orden    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_planlistas FOREIGN KEY (plan_id) REFERENCES planes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plan_lista_items (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lista_id INT UNSIGNED NOT NULL,
  texto    VARCHAR(500) NOT NULL,
  hecho    TINYINT(1) NOT NULL DEFAULT 0,
  orden    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_planlistaitems FOREIGN KEY (lista_id) REFERENCES plan_listas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Se decide NO persistir (v1):** tramos de traslado (se calculan al vuelo, §4.4) y
reacciones emoji (nice-to-have; tabla `plan_reacciones` queda anotada para v2).

---

## 2. FASE BACKEND — API JSON (1–1.5 días)

Carpeta nueva **`api/`**. Todos los endpoints comparten un guardián común:

**2.1 `includes/plan_auth.php`** — `planAccess(int $planId): array`
1. Exige sesión (`$_SESSION['user']`), si no → `401`.
2. Busca el rol en `plan_miembros` (`propietario|editor|lector`); sin fila → `403`.
3. Devuelve `['rol' => …, 'plan' => fila de planes]`.
4. Mutaciones exigen `editor|propietario`; borrar plan / invitar exige `propietario`.
5. **CSRF:** token de sesión emitido en `plan.php`, verificado en todo POST (primer uso del
   patrón en el proyecto; queda listo para replicarlo en perfil/admin).

**2.2 Endpoints** (POST JSON salvo el primero; respuesta `{ok:true, …}` o `{ok:false, error}`):

| Endpoint | Acciones |
|---|---|
| `api/plan_get.php?id=N` | **Todo el plan en un JSON**: plan + miembros + items por día + gastos + listas (una sola carga inicial, como `state` del prototipo) |
| `api/plan_update.php` | Campos sueltos del plan: título, fechas, privacidad, presupuesto, portada, subtítulos de día |
| `api/plan_items.php` | `add` (con `place_id`/lat/lng si viene de Explorar), `update` (hora, hora_fin, precio, nota), `move` (día+orden destino → **renumera en transacción**), `del` |
| `api/plan_gastos.php` | `add`, `update`, `del` (validar categoría contra el ENUM) |
| `api/plan_listas.php` | `add` (nota/check), `rename`, `del`, `item_add`, `item_toggle`, `item_del`, `import_plantilla` (recibe los textos elegidos del modal de plantillas) |
| `api/plan_invitar.php` | Genera token (`bin2hex(random_bytes(32))`, guarda **SHA-256**, expira 7 días — patrón `password_resets`), opcionalmente envía correo con `mailer.php` |
| `api/plan_ai.php` | **Fase A:** el `aiReply` enlatado portado a PHP · **Fase B:** proxy a la API de Claude (clave en `includes/ai_config.php`, **gitignored**) |

**2.3 `plan_invitacion.php`** (página): valida el token → si hay sesión, inserta en
`plan_miembros` con el rol de la invitación y redirige a `plan.php?id=N`; si no, manda a
`login.php` con retorno.

---

## 3. FASE FRONT — `plan.php` + `plan.css` + `js/plan.js` (3–4 días, el grueso)

**3.1 `plan.php`** — igual patrón que las demás páginas: sesión → `planAccess()` → topbar
(`$topbar_active=null`) → esqueleto HTML de las 8 zonas → `PLAN_BOOT = <?= json_encode(...) ?>`
con el JSON inicial (evita el fetch de arranque) → scripts.

**3.2 Portar el componente React a vanilla** (mismo estilo que `crear_plan.js`: objeto `S` de
estado + funciones `render*()` por zona + delegación de eventos). Orden de porteo:

| Paso | Zona (del prototipo) | Notas |
|---|---|---|
| a | Rejilla 3 columnas + scroll propio | `auto 695px 1fr`; contenido con scroll interno; mapa fijo |
| b | Barra lateral expandida/rail (192↔44 px) | Animación 280 ms; grupos Resumen/Itinerario/Presupuesto; días |
| c | Resumen: hero + tarjeta + guías + **listas** | Listas nota/check con autosave; modal de plantillas leyendo `plantillas.js` |
| d | Itinerario: días colapsables + tarjetas + **drag & drop** | Reusar el patrón de huecos (`gap`) de `crear_plan.js`; al soltar → `plan_items.php action=move` |
| e | Presupuesto: tarjeta, barra, gastos, **modal desglose** | Totales client-side; montos `es-MX` + `tabular-nums`; conversión con `currency.php` si la divisa del usuario ≠ MXN |
| f | Explorar (overlay 50%): ficha ciudad + categorías + filas de lugar | Datos reales de Places (§4.3); botón Añadir → split-button con menú de día |
| g | Panel de detalle sobre el mapa + capas + buscador del mapa | Pestañas Acerca de/Reseñas/Fotos con Places Details |
| h | Asistente: ventana ↔ panel ↔ conversación | Streaming visual; respuestas de `api/plan_ai.php`; enlaces [lugar] → resaltar pin |
| i | Responsive (≤1024 mapa modal "Ver mapa", ≤640 columna única) + Esc/foco | Ya especificado en el prototipo; portar los umbrales |

**3.3 Assets:** copiar `img/asistente.png`, `img/expl/*`, `img/google-*.svg` y `plantillas.js`
al proyecto (`img/`, `js/plantillas.js`). **No copiar** los textos demo de La Paz (notas
personales en inglés) — los estados iniciales vienen vacíos de BD.

---

## 4. FASE MAPA — Google Maps real (1 día)

**4.1** Mapa JS centrado en `planes.lat/lng` (geocodificados al crear el plan).
**4.2** Pines = SVG custom actual (32×41) con número y color por categoría
(morado atracciones / coral comida / teal guardados / **dorado activo**), sincronía
tarjeta↔pin (patrón ya probado en `resultados.js`).
**4.3** Explorar: `nearbySearch`/`textSearch` por categoría + `getDetails` (fotos, rating,
horarios, teléfono, web, reseñas) — **reutilizar extrayendo a `js/places_lib.js`** lo que hoy
vive en `resultados.js`, para no duplicarlo.
**4.4** Ruta del día: `DirectionsService` con waypoints del día activo → polyline dorada +
**tramos de traslado** (tiempo/distancia caminando o auto) pintados entre tarjetas. *Cache en
memoria por sesión; una sola llamada por reordenamiento.* Si se quiere evitar Directions:
fallback haversine × velocidad media (sin costo).
**4.5** Capas: checkboxes que filtran pines por lista/día + "líneas de ruta siempre".

---

## 5. FASE FLUJO — Integración con lo existente (½ día)

1. `crear_plan.php` (pantalla Crear → Invitar): al confirmar, `INSERT` en `planes` +
   propietario en `plan_miembros` (+ invitaciones si capturó correos) → **redirect a
   `plan.php?id=N`**. La pantalla 3 vieja (planner con `localStorage`) se retira; si existe un
   borrador en `rn_crear_plan_v1`, ofrecer **importarlo una vez** al nuevo plan y limpiarlo.
2. `mis_planes.php`: listar los planes donde el usuario es miembro (`plan_miembros`), cada
   tarjeta → `plan.php?id=N`; corregir de paso el encabezado "Mis viajes" → "Mis planes".
3. Botón "Añadir al plan de viaje" en `resultados.php`: menú con los planes del usuario →
   `plan_items.php action=add` (conecta las dos mitades del producto).

---

## 6. FASE SEGURIDAD Y CALIDAD (transversal)

- Todo endpoint: sesión + membresía + rol + CSRF + prepared statements + `htmlspecialchars` en
  la salida PHP; JSON con `Content-Type: application/json`.
- El rol **lector** ve todo pero la UI oculta controles de edición y la API rechaza sus POST
  (equivale al "Modo solo lectura" del menú •••).
- Tokens de invitación: un solo uso, expiración, hash en BD — nunca el token en claro.
- `ai_config.php` (Fase B) en `.gitignore` junto a `mail_config.php` y `geo_config.php`.
- **Casos de prueba nuevos** para el Excel (módulo "Planificador", PLAN-01…): crear plan,
  añadir/mover/borrar item (drag), presupuesto y gastos, listas y plantillas, invitación
  válida/expirada/manipulada, lector no puede editar, responsive, plan ajeno → 403.

---

## 7. Orden de entrega (hitos verificables)

| Hito | Alcance | Demo |
|---|---|---|
| **H1 — MVP navegable** (BD + api lectura + front a,b,c,d) | Crear plan → verlo, itinerario editable con drag, autosave | Crear "Viaje a Ensenada", arrastrar 3 lugares entre días, recargar y persiste |
| **H2 — Mapa y Explorar** (§4 + front f,g) | Places reales, pines sincronizados, añadir desde Explorar | Buscar restaurantes, añadirlos a un día, ver la ruta |
| **H3 — Presupuesto y listas** (front e + plantillas + desglose) | Gastos, barra, desglose, checklists | Registrar 3 gastos y ver el desglose |
| **H4 — Colaboración** (invitaciones + roles + correo) | Invitar por enlace/correo, lector vs editor | Segundo usuario entra por el enlace y edita |
| **H5 — Asistente** (Fase A enlatada → Fase B Claude) | Chat con la UI completa | Pedir "itinerario de 2 días" y añadir sugerencias |
| **H6 — Pulido** | Responsive, impresión, polling colaborativo, casos de prueba ejecutados | Checklist PLAN-xx en verde |

**Estimación total:** ~7–9 días de trabajo efectivo (H1–H3 ≈ 5; H4–H6 ≈ 3–4).
**Prerrequisito operativo:** MariaDB de XAMPP arriba (el servicio MySQL80 de Windows debe
estar detenido/manual — hoy ocupa el puerto 3306).

---

## 8. Fuera de alcance (v1) — anotado para no perderlo

- Tiempo real con WebSockets (v1: polling; suficiente para la demo).
- Reacciones emoji por lugar (`plan_reacciones`), chat entre miembros del plan.
- Exportar CSV de gastos (botón queda; se implementa con un endpoint trivial después).
- Precios de hoteles vía Booking/Expedia (sin API pública accesible para el proyecto).
- Modo offline / PWA.
