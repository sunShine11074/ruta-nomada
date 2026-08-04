# 🗺️ Funcionalidades de creación de itinerarios en Wanderlog

> Inventario de referencia para implementar un **planificador de itinerarios estilo Wanderlog**
> en `crear_plan.php`. Cada funcionalidad lleva una etiqueta orientada a nuestro proyecto.

## Leyenda de etiquetas
- 🟢 **Ya tenemos base** — piezas construidas en Ruta Nómada que se reutilizan.
- 🎯 **MVP** — núcleo diferenciador; primero.
- 🔵 **Fase 2** — deseable, después del MVP.
- 🔴 **Difícil / costoso / fuera de alcance** — evaluar o posponer.

---

## A. Estructura del viaje (el "plan")
| Funcionalidad | Descripción | Nota para `crear_plan.php` |
|---|---|---|
| Crear viaje | Nombre, **fechas (inicio–fin)**, foto de portada, emoji | 🟢 la tabla `planes` ya tiene nombre/fechas/presupuesto |
| Multi-destino | Un viaje con varias ciudades, cada una con su sección | 🔵 |
| Panel de mis viajes | Lista de todos los planes (crear / duplicar / eliminar) | 🎯 encaja con la sección "Mis viajes" |
| Portada y personalización | Imagen, color, ícono del viaje | 🔵 |

## B. Lugares e itinerario (el corazón)
| Funcionalidad | Descripción | Nota |
|---|---|---|
| **Vista día por día** | El itinerario se divide en días; se agregan lugares a cada día | 🎯 tu idea de calendario / Kanban |
| **Lista "Lugares por visitar"** | Bandeja de lugares guardados aún sin agendar; se arrastran a los días | 🎯 columna "por planear" del Kanban |
| **Tarjetas de lugar** | Foto, nombre, categoría, rating, dirección, **hora, duración, precio, notas** | 🟢 esos datos ya salen de Google Places en `resultados.php` |
| **Agregar desde búsqueda** | Autocompletar de Google Places (atracciones, restaurantes, hoteles) | 🟢 ya usamos Places; falta el botón "Agregar al plan" |
| **Lugares personalizados** | Añadir un punto propio (casa de un familiar, etc.) | 🎯 mencionado por el equipo |
| **Drag & drop** | Reordenar tarjetas dentro de un día y entre días | 🎯 con SortableJS (sin framework) |
| **Horario / timeline** | Asignar hora a cada lugar; el día se ve como línea de tiempo | 🎯 / 🔵 |
| **Notas** | Nota general del viaje y nota por lugar / día | 🎯 fácil |
| Color / etiquetas por tipo | Codificar por categoría (hotel / comida / actividad) | 🟢 colores ya definidos |

## C. Mapa y ruta *(clave para "Ruta Nómada")*
| Funcionalidad | Descripción | Nota |
|---|---|---|
| **Todos los lugares en el mapa** | Pines numerados según el orden del itinerario | 🟢 pines + sincronía de `resultados.js` |
| **Ruta trazada** | Línea que conecta los lugares del día en orden | 🎯 empezar con `Polyline` (gratis) |
| **Tiempo / distancia entre paradas** | "12 min en auto" entre lugar 1 y 2 | 🔵 Directions / Distance Matrix (factura en Google) |
| **Optimizar ruta** | Reordenar para el recorrido más corto | 🔴 |
| Filtrar mapa por día | Ver solo los pines de un día | 🔵 |

## D. Colaboración *(idea estilo Figma / Google Docs)*
| Funcionalidad | Descripción | Nota |
|---|---|---|
| **Invitar colaboradores** | Por **enlace**, correo o agregando amigos; roles editar / ver | 🎯 mismo patrón de tokens que `password_resets` |
| **Co-edición en tiempo real** | Varios editando el tablero a la vez | 🔵 polling AJAX o Pusher / Ably (capa gratuita) |
| **Presencia** | Avatares de quién está conectado | 🔵 |
| **Cursores / selecciones en vivo** | Ver el cursor de otros (estilo Figma) | 🔴 *stretch goal* — mucho esfuerzo, poco valor relativo |
| **Comentarios / chat + emojis** | Discutir el plan; comentar lugares | 🎯 / 🔵 |

## E. Presupuesto y gastos
| Funcionalidad | Descripción | Nota |
|---|---|---|
| Presupuesto del viaje | Suma de precios de las tarjetas; total | 🟢 campo `presupuesto` en `planes` + divisa del usuario |
| Registro de gastos | Categorías, quién pagó | 🔵 |
| **Dividir gastos** | Repartir entre viajeros ("split") | 🔵 encaja con el lema "precisión de contador" |

## F. Reservas y logística
| Funcionalidad | Descripción | Nota |
|---|---|---|
| Sección de reservas | Hoteles, restaurantes, autos: número de confirmación, hora | 🔵 |
| Vuelos | Agregar / buscar / rastrear vuelos | 🔴 |
| Adjuntos / documentos | PDF de reservas, boletos | 🔵 |

## G. Organización extra
| Funcionalidad | Descripción | Nota |
|---|---|---|
| Checklist / pendientes | To-dos del viaje | 🔵 |
| Lista de equipaje | Packing list | 🔵 |

## H. Descubrimiento / recomendaciones
| Funcionalidad | Descripción | Nota |
|---|---|---|
| Sugerencias "qué hacer" | Top lugares del destino para agregar | 🟢 ya lo dan las 3 pestañas de `resultados.php` |
| Guías curadas | Listas de expertos | 🔴 |

## I. Exportar / compartir / imprimir
| Funcionalidad | Descripción | Nota |
|---|---|---|
| **Imprimir / PDF** | Itinerario imprimible para compartir | 🎯 `@media print` + `window.print()` (rápido) |
| Enlace público de solo lectura | Compartir el plan | 🔵 |
| App offline | Ver sin internet (móvil) | 🔴 fuera de alcance |

---

## Priorización sugerida para `crear_plan.php`

### MVP (un "Wanderlog mínimo" y diferenciado)
1. Crear plan (nombre + fechas) y listarlos en "Mis viajes". 🟢
2. Botón **"Agregar al plan"** en `resultados.php` (guarda el lugar en el plan). 🟢
3. Tablero **día por día + bandeja "por planear"** con **tarjetas** (lugar, hora, precio, nota) y **drag & drop** (SortableJS). 🎯
4. **Lugares personalizados**. 🎯
5. **Ruta en el mapa** (Polyline conectando los pines en orden). 🎯
6. **Invitar por enlace** (tokens) + roles ver / editar. 🎯
7. **Imprimir / PDF** del itinerario. 🎯

### Fase 2
Chat + emojis · presupuesto / división de gastos · sincronización en vivo (polling → Pusher) · presencia · tiempos entre paradas.

### Fase 3 (opcional)
Cursores en vivo · optimización de ruta · reservas / vuelos.

---

## Modelo de datos propuesto (reutiliza lo existente)

Ya existen 🟢 `planes` y `plan_destinos`. Tablas nuevas sugeridas:

| Tabla | Para qué | Campos clave |
|---|---|---|
| `plan_miembros` | quién colabora y con qué rol | `plan_id`, `usuario_id`, `rol` (dueño/editor/lector) |
| `plan_items` | las **tarjetas** del itinerario | `plan_id`, `dia`, `orden`, `nombre`, `categoria`, `hora`, `precio`, `nota`, `place_id`, `lat`, `lng` |
| `plan_invitaciones` | invitar por enlace | `plan_id`, `token_hash`, `rol`, `expira_en` |
| `plan_mensajes` *(Fase 2)* | chat del grupo | `plan_id`, `usuario_id`, `texto`, `creado_en` |

> Cada endpoint del plan debe verificar la **membresía** del usuario (lección de `admin_prin.php`):
> nunca confiar solo en el cliente para la autorización.

---

## Referencias
- Sitio de referencia analizado: **Wanderlog**.
- Piezas reutilizables ya en el proyecto: Google Places + mapa/pines (`resultados.php`, `js/resultados.js`),
  patrón de tokens (`forgot-password.php` / `password_resets`), tabla `planes`/`plan_destinos`,
  divisa global (`includes/currency.php`), topbar con botón "Crear plan de viaje".
