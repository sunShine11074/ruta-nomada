# 📋 Reporte — Rediseño del topbar y retiro del sidebar · Ruta Nómada

> Cambios del **6 de julio de 2026** en la barra de navegación superior y el menú lateral.
> Resumen para el equipo: qué cambió, en qué archivos vive y cómo se usa.

---

## En una frase

La navegación se movió a un **topbar global reutilizable** con **menú desplegable bajo el avatar**
(que reemplaza al sidebar), un **buscador con selector de categoría**, el botón
**"Crear plan de viaje"** y la **burbuja flotante del futuro chatbot** — tal como se acordó
en la reunión del equipo.

## Archivos nuevos

| Archivo | Qué es |
|---|---|
| `includes/topbar.php` | Componente único del topbar. Se incluye en cada página con `$topbar_active = '…'; include 'includes/topbar.php';` |
| `topbar.css` | Estilos del topbar, dropdowns y burbuja del chatbot |
| `js/topbar.js` | Lógica: abrir/cerrar menús, selector de categoría, envío del buscador, chatbot |
| `crear_plan.php` | Página **placeholder** donde vivirá el planificador colaborativo (itinerario/Kanban/ruta) |

## Qué contiene el nuevo topbar (de izquierda a derecha)

1. **Logo + "Ruta Nómada"** — ahora es un enlace al `dashboard.php`.
2. **Buscador con categoría** — campo de texto + desplegable
   (`Ciudad · Hoteles · Restaurantes · Cosas que hacer`) + botón de lupa.
   Al buscar navega a `resultados.php?q=CIUDAD&tab=CATEGORÍA`; la página de resultados
   **ya lee el parámetro `tab`** y abre directamente esa pestaña.
3. **Navegación** — Inicio · Explorar · Mis viajes · Comunidad. La página activa se marca
   con la variable `$topbar_active` que define cada página antes del `include`.
4. **Botón "Crear plan de viaje"** — lleva a `crear_plan.php` (por ahora placeholder;
   ahí se construirá el planner colaborativo).
5. **Campana** de notificaciones (visual).
6. **Avatar con menú desplegable** — al hacer clic abre: *Mi perfil · Mis planes ·
   Configuración · Cerrar sesión*. **Este menú reemplaza al sidebar**: son las mismas
   opciones que antes vivían en el panel lateral.

Además, fuera del header:

7. **Burbuja flotante del chatbot** (esquina de la pantalla) — sustituye al antiguo botón
   flotante del sidebar. Por ahora abre un panel de "Próximamente"; es el espacio reservado
   para el asistente conversacional que se planea.

## Comportamiento (UX)

- Los tres desplegables (menú del avatar, categoría del buscador, panel del chatbot) se
  **cierran al hacer clic fuera o con `Esc`**, y abrir uno cierra los demás.
- El menú del avatar usa atributos de accesibilidad (`aria-haspopup`, `aria-expanded`, `role="menu"`).
- El buscador conserva lo escrito (se pre-llena con `?q=` en la página de resultados).

## Qué se quitó

- **El sidebar (menú lateral) desapareció de todas las páginas de usuario**, junto con su
  botón flotante ☰. Sus opciones ahora están en el menú del avatar.
- `js/sidebar.js` ya solo lo usa **`admin_prin.php`** (el panel de administración conserva
  el sidebar antiguo por ahora).

## Integración por página

El nuevo topbar quedó incluido en **9 páginas**: `dashboard.php`, `explore.php`,
`mis_viajes.php`, `comunidad.php`, `configuracion.php`, `profile.php`, `destino.php`,
`resultados.php` y `crear_plan.php`. Todas cargan `topbar.css` y `js/topbar.js`.

Patrón para cualquier página nueva:

```php
<link rel="stylesheet" href="topbar.css">          <!-- en el <head> -->
...
<?php $topbar_active = 'inicio'; // o 'explorar' | 'misviajes' | 'comunidad' | null
include __DIR__ . '/includes/topbar.php'; ?>
...
<script src="js/topbar.js"></script>               <!-- antes de </body> -->
```

Requisitos del include: sesión iniciada, `db.php` cargado y `$user = $_SESSION['user']`
(el componente usa `includes/user_topbar.php` para foto de perfil e inicial del avatar).

## Pendientes conocidos

- **Chatbot**: la burbuja es un placeholder — falta decidir si será un asistente de FAQ
  (gratis) o IA real vía API (con costo) y construirlo.
- **`crear_plan.php`**: placeholder — aquí va el planificador colaborativo (tarjetas,
  Kanban, ruta en mapa, invitaciones), según el análisis de viabilidad del equipo.
- **Campana**: aún sin notificaciones reales.
- **`admin_prin.php`**: conserva topbar y sidebar antiguos; migrarlo al nuevo topbar
  queda como tarea aparte.
