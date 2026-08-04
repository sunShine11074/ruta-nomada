# 📋 Reporte — Navegación del topbar y renombres de archivos · Ruta Nómada

> Cambios recientes en la barra de navegación superior y en los nombres de archivos.
> Complementa a `REPORTE_topbar_y_sidebar.md` (donde se documentó el rediseño del topbar
> y el retiro del sidebar). Aquí se registran los ajustes posteriores.

---

## 1. Navegación reducida a 3 opciones

El menú del topbar pasó de 4 a **3 opciones**, alineadas al enfoque de itinerarios y guías:

| Antes (4) | Ahora (3) | Destino |
|---|---|---|
| Inicio · Explorar · Mis viajes · Comunidad | **Inicio · Mis planes · Guías de viaje** | `inicio.php` · `mis_planes.php` · `guias.php` |

- Se **quitaron** "Explorar" y "Comunidad" de la barra.
- Las etiquetas **"Ruta Nómada"** (junto al logo), **"Inicio"**, **"Mis planes"** y **"Guías de viaje"**
  aumentaron **+1px** de tamaño (según Figma).

## 2. Dos variantes del topbar

El topbar (`includes/topbar.php`) ahora se renderiza en dos formas según la página:

| Variante | Cuándo | Diferencia |
|---|---|---|
| **Con buscador** (topbar 1) | Todas las páginas **excepto Inicio** | Muestra el buscador con selector de categoría |
| **Sin buscador** (topbar 2) | En **Inicio** (`inicio.php`) | Oculta el buscador (vivirá dentro de la propia página de Inicio) |

- Se activa la variante sin buscador con `$topbar_search = false;` antes del `include`.
- En la variante de **Inicio**, las 3 opciones de nav se **agrupan y se pegan a la derecha**,
  junto al botón **"Crear plan de viaje"** (fiel al mockup). Esto se logró con:
  ```css
  .tb--nosearch .tb-nav   { margin-left: auto; }
  .tb--nosearch .tb-right { margin-left: 0; }
  ```
  (Antes ambos grupos tenían `margin-left:auto` y la nav quedaba flotando al centro.)

## 3. Menú desplegable del avatar (dropdown)

El menú bajo el avatar (que reemplazó al sidebar) quedó así:

| Ítem | Destino |
|---|---|
| Mi perfil | `profile.php` |
| **Mis guardados** *(antes "Mis planes")* | `mis_guardados.php` *(nuevo)* |
| Configuración | `configuracion.php` |
| Cerrar sesión | `inicio.php?logout=1` |

> Se diferenció de la nav: el topbar tiene **"Mis planes"** (los itinerarios) y el dropdown
> tiene **"Mis guardados"** (lugares/destinos guardados con ♥).

---

## 4. Renombres y archivos nuevos / eliminados

### Renombrados
| Archivo anterior | Archivo nuevo |
|---|---|
| `dashboard.php` | **`inicio.php`** |
| `mis_viajes.php` | **`mis_planes.php`** |

Al renombrar `dashboard.php` se actualizaron **todas** sus referencias a `inicio.php`:
marca y nav del topbar, enlace de **cerrar sesión** (`inicio.php?logout=1`), y las
redirecciones de `login.php`, `register.php`, `forgot-password.php`, `reset-password.php`
y `admin_prin.php`.

### Nuevos
| Archivo | Qué es |
|---|---|
| `inicio.php` | El antiguo dashboard (página de Inicio) |
| `mis_planes.php` | Los planes/itinerarios del usuario (antes "Mis viajes") |
| `mis_guardados.php` | **Placeholder** — lugares/destinos guardados (dropdown) |
| `guias.php` | **Placeholder** — guías de viaje (nav) |
| `crear_plan.php` | **Placeholder** — futuro planificador colaborativo (botón "Crear plan de viaje") |

### Eliminados
| Archivo | Motivo |
|---|---|
| `explore.php` | Su propósito lo cubre `inicio.php` (se fusionaron) |
| `comunidad.php` | "Comunidad" salió de la navegación |

---

## 5. Verificación realizada
- Topbar de **Inicio**: sin buscador, 3 opciones pegadas a "Crear plan de viaje", "Inicio" resaltado.
- Topbar del resto: con buscador + categoría, opción activa correcta.
- Enlaces del nav y del dropdown resuelven a los archivos nuevos; **cerrar sesión** funciona.
- Sin referencias colgantes a `dashboard.php`, `mis_viajes.php`, `explore.php` ni `comunidad.php`.
- Sintaxis PHP validada en todas las páginas afectadas.

## 6. Pendientes conocidos
- `mis_guardados.php`, `guias.php` y `crear_plan.php` son **placeholders**; falta su funcionalidad.
- El **encabezado interno** de `mis_planes.php` aún dice "Mis viajes" (solo se renombró el archivo y los enlaces).
- `admin_prin.php` conserva el topbar/sidebar antiguos (migración pendiente).
- El **buscador dentro de `inicio.php`** (que reemplaza al del topbar en esa página) está por conectarse.
