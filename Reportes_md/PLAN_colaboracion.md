# Plan de implementación — Colaboración robusta en los itinerarios

**Ruta Nómada · escrito el 11 de agosto de 2026 · pendiente de ejecutar**

Este documento es un **plan de trabajo**, no un reporte de algo ya hecho.
Describe cómo convertir el planificador en una herramienta colaborativa de
verdad: enlaces de invitación, edición simultánea sin pisarse, mapa que se
actualiza solo y herramientas de grupo compartidas.

Está escrito para poder retomarse dentro de varias semanas sin necesidad de
recordar la conversación en la que nació. Por eso empieza por el estado del
código en el momento de escribirlo: si algo de lo que aquí se afirma ya no
coincide con el proyecto, el plan hay que revisarlo antes de ejecutarlo.

---

## 0. El objetivo, en las palabras del encargo

Cuatro comportamientos:

1. **Enlaces compartidos** — mandar una invitación a los compañeros de viaje
   para que abran el itinerario y se unan.
2. **Edición simultánea** — varias personas editando el día a día y moviendo
   pines a la vez **sin sobrescribirse entre ellas**.
3. **Mapa en vivo** — si alguien añade o mueve un restaurante, se mueve solo
   en la vista de los demás.
4. **Herramientas de grupo integradas** — registrar gastos, dividir costes,
   comentar y votar lugares en el mismo tablero.

De los cuatro, el difícil es el segundo. Los otros tres son trabajo conocido.

---

## 1. De dónde se parte (estado verificado el 11/08/2026)

### Lo que ya está construido y funciona

| Pieza | Estado |
|---|---|
| Tabla `plan_invitaciones` | Existe. Token de 64 hex, en la base sólo su SHA-256, rol, caducidad y un solo uso. **1 fila** |
| `includes/plan_invite_lib.php` | `planInviteCreate()` completo: genera el enlace, deduce la URL base de la petición, manda el correo si puede |
| `api/plan_invitar.php` | CSRF + `planAccess(..., 'propietario')`. Devuelve `{ok, link, correo_enviado}` |
| `plan_invitacion.php` | Valida hash, vigencia y un solo uso; inserta en `plan_miembros` sin duplicar ni degradar un rol existente |
| Retorno tras iniciar sesión | `login.php:56` respeta `$_SESSION['despues_de_login']`: quien no tiene cuenta se registra y vuelve al enlace |
| Roles | `propietario` / `editor` / `lector` aplicados en **13 llamadas** a `planAccess()` repartidas por `api/` |
| El cliente conoce su rol | `plan_logic.js`: `this.ROL = B.rol \|\| 'lector'` → `puedeEditar` |
| Herramientas de grupo | `plan_gastos`, `plan_item_gasto` (reparto), `plan_item_reacciones` (votar con emoji). Tablas, endpoints e interfaz, todo hecho |

Es decir: **el backend de las invitaciones está terminado**. Ya se usa en
producción — `api/plan_create.php` manda una invitación por correo al crear
un viaje acompañado.

### Lo que falta

| Hueco | Dónde |
|---|---|
| **El botón de invitar está muerto** | `plan_template.html:262` → `onClick="{{ noop }}"`. `plan_logic.js` **no menciona `plan_invitar` ni una sola vez** |
| **No hay panel de miembros** | No se pueden ver, cambiar de rol ni quitar colaboradores desde la interfaz |
| **Nada es en tiempo real** | Cero `EventSource`, cero `WebSocket`, cero sondeo. Los cuatro `setInterval` de `plan_logic.js` son animaciones de 16 ms |
| **Nada impide sobrescribir** | `_sync()` (`plan_logic.js:277`) está documentado en el propio código como *«optimista, fuego-y-olvida»*: manda el cambio sin comprobar qué versión tiene el servidor |
| **No hay columna de versión** | En ninguna tabla |
| **Faltan marcas de tiempo** | `plan_gastos`, `plan_listas` y `plan_lista_items` no tienen ni `updated_at` |

### Tres hallazgos que condicionan el diseño

1. **`plan_items.updated_at` ya existe** con `ON UPDATE current_timestamp`.
   Pero **no sirve como testigo de versión**: `timestamp` tiene resolución de
   un segundo, así que dos ediciones dentro del mismo segundo son
   indistinguibles.
2. **`planes.updated_at` ya lo mueven** los tres disparadores instalados en la
   entrega anterior… **pero sólo cuando cambian los `plan_items`**. Gastos,
   listas, reacciones, reparto y miembros no lo tocan. Como detector de
   cambios está a medias.
3. **No hay servidor de WebSockets, y no puede haberlo.** XAMPP sirve PHP a
   través de Apache: no existe un proceso persistente donde alojar una
   conexión abierta. SSE es posible pero ocupa un *worker* de Apache por
   usuario conectado, y XAMPP se queda sin *workers* con muy poca gente.
   **La única vía razonable aquí es el sondeo.**

---

## 2. El principio de diseño

> **«Nadie pierde trabajo sin enterarse»** se descompone en tres capacidades
> independientes: **detectar** que algo cambió, **traerlo sin pisar lo que
> estás escribiendo**, y **avisar** cuando dos personas tocan lo mismo.

Cada fase deja la aplicación funcionando y entregable. Ninguna depende de que
la siguiente llegue a existir.

**Coste estimado: 6 días de trabajo.**

---

## 3. Fase 1 · El botón de invitar cobra vida

> **✅ HECHA el 12/08/2026, pero no como se cuenta aquí debajo.** Esta sección
> se escribió sin los frames. Lo que se implementó de verdad —y por qué, y con
> qué medidas— está en **`PLAN_invitar.md`**. Tres diferencias que importan:
> los frames **no traen selector de rol** (todo el que entra es `editor`); la
> lista de miembros vive en una **segunda pantalla** de la misma ventana, no
> junto al campo de correo; y hubo que tocar `plan_invitaciones`, porque el
> enlace era de **un solo uso** y el frame lo enseña para compartirlo.

**1 día.** El backend está completo; sólo falta quien lo llame.

### Archivos

| Archivo | Cambio |
|---|---|
| `plan_template.html:262` | `onClick="{{ noop }}"` → `onClick="{{ invitarAbrir }}"` |
| `plan_template.html` | Modal nuevo: campo de correo, selector editor/lector, botón «Copiar enlace», lista de miembros con su rol |
| `js/plan_logic.js` | Estado `invModal, invEmail, invRol, invLink, invMsg` + métodos `invitarAbrir` / `invitarCerrar` / `invitarCrear` / `invitarCopiar` |
| `api/plan_miembros.php` **(nuevo)** | Acciones `list`, `rol`, `quitar`. Sólo propietario |
| `api/plan_invitar.php` | **Sin cambios** — ya devuelve lo que hace falta |

### Detalles que importan

- El botón sólo se dibuja si `ROL === 'propietario'`, igual que exige el
  endpoint. Un botón que siempre falla es peor que ningún botón.
- **El enlace se muestra siempre**, aunque el correo falle. En las máquinas de
  los compañeros `includes/mail_config.php` no existe —está en `.gitignore`—
  y el envío fallará en silencio. Copiar el enlace es el camino que **siempre**
  funciona, y por eso el modal lo enseña primero.
- El propietario no puede quitarse a sí mismo ni degradarse: dejaría un plan
  sin dueño y `sp_borrar_plan` no tendría a quién reconocer.

### Verificación

Cuenta A crea el enlace → cuenta B lo abre en una ventana de incógnito → B
aterriza en `plan.php?id=N` con rol de editor y su avatar aparece en la
cabecera de A al recargar.

---

## 4. Fase 2 · Que la base sepa cuándo cambió algo

**1 día.** Sin esto no hay forma barata de preguntar «¿hay novedades?».

### Migración nueva: `basedatos/migrate_colaboracion.sql`

```sql
ALTER TABLE planes     ADD rev BIGINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE plan_items ADD ver INT    UNSIGNED NOT NULL DEFAULT 1;
```

**Por qué un contador entero y no `updated_at`:** por la resolución de un
segundo del tipo `timestamp` (ver §1). Un `BIGINT` que sube de uno en uno se
compara con `!==` y no tiene ambigüedad posible.

### Rutinas nuevas

- **`sp_tocar_plan(p_plan_id)`** — sube `rev` y `updated_at` de un plan. Una
  línea por disparador.
- **`fn_plan_de_item(p_item_id)`** y **`fn_plan_de_lista(p_lista_id)`** — las
  tablas nietas (`plan_lista_items`, `plan_item_gasto`,
  `plan_item_reacciones`) no conocen su `plan_id`; estas funciones lo
  resuelven.
- **15 disparadores nuevos** sobre `plan_gastos`, `plan_listas`,
  `plan_lista_items`, `plan_item_reacciones`, `plan_item_gasto` y
  `plan_miembros`. Los tres que ya existen sobre `plan_items` se amplían para
  subir `rev` además de `updated_at`.

> **Nota académica.** El proyecto pasaría de **5 a 20 disparadores**, de **4 a
> 6 funciones** y de **5 a 6 procedimientos**, muy por encima del mínimo que
> pidieron (4 procedimientos, 4 funciones, 3 disparadores). Habrá que ampliar
> `REPORTE_rutinas_bd.md` en la fase 7.

### ⚠️ Trampa: ningún disparador en `plan_miembros UPDATE`

La fase 6 escribe en `plan_miembros` **en cada sondeo**. Si ese `UPDATE`
subiera `rev`, cada sondeo generaría un cambio, cada cliente recargaría el
plan entero, y eso se realimenta hasta fundir el servidor. Los disparadores de
`plan_miembros` van **sólo** en `INSERT` y `DELETE`.

### Verificación

`SELECT rev FROM planes WHERE id = N` sube **exactamente una vez** por cada
tipo de cambio: añadir un lugar, moverlo, borrarlo, añadir un gasto, crear una
lista, marcar un pendiente, reaccionar, repartir un coste, entrar un miembro.

---

## 5. Fase 3 · El latido

**1 día.**

### `api/plan_pulso.php` (nuevo)

`GET ?id=N` → `{ok, rev}`. Una lectura por clave primaria; la respuesta pesa
unos 25 bytes.

```php
session_write_close();   // ANTES de cualquier consulta
```

### ⚠️ Trampa: el bloqueo del fichero de sesión

PHP bloquea el fichero de sesión durante toda la petición. Con un sondeo cada
5 segundos, ese bloqueo **serializa todas las demás peticiones del mismo
usuario** y la aplicación se arrastra sin motivo aparente. El pulso sólo *lee*
la sesión, así que puede soltarla de inmediato. Es el fallo más caro de
diagnosticar de todo este plan, porque no produce ningún error: sólo lentitud.

### Cliente — `_latir()` en `js/plan_logic.js`

- Cada **5 s**; **se detiene con `document.hidden`** (una pestaña en segundo
  plano no consume nada).
- Si `rev` cambió → `fetch('api/plan_get.php?id=N')` → `_fusionar(j)`.
- **Retroceso progresivo:** tras 12 pulsos sin novedad pasa a 15 s; vuelve a
  5 s con cualquier interacción del usuario o cambio detectado.
- Tres errores seguidos → se apaga y muestra «Sin conexión con el plan».

### Verificación

Dos navegadores: A añade un lugar, B lo ve aparecer en ≤ 5 s. Con la pestaña
de B oculta, el registro de Apache no muestra peticiones suyas.

---

## 6. Fase 4 · Fusión selectiva

**1½ días.** La parte con más riesgo de romper cosas que ya funcionan.

`_fusionar(j)` reconstruye `dayItems`, `gastos`, `lists` y `MIEMBROS` desde el
servidor **sin tocar lo que la persona está haciendo en ese momento**.

### Qué se conserva siempre local

| Si está activo | Se respeta |
|---|---|
| `drag` o `ckDrag` | **La fusión se pospone entera** hasta que suelte |
| `itemOpen`, `horaMenu`, `gastoMenu` | Ese lugar no se sustituye; sus borradores (`hIni`, `hFin`, `gMonto`, `gCat`, `gDesc`, `gModo`, `gRep`…) quedan intactos |
| `noteEdit` | El texto local de esa nota gana |
| `titleEdit` | El título en edición gana |
| `acKey`, `emoPicker`, `placeFocusId` | El menú abierto no se cierra solo |

Además deben sobrevivir el encuadre del mapa, el día seleccionado, la posición
del desplazamiento, `added`, `exMas` y `rvwIdx`.

Al terminar, `_reproject()`. **Con eso el mapa en vivo sale gratis:** los pines
son un overlay propio calculado con `MERC()`, así que se recolocan solos sin
tocar nada de Google Maps.

> **Recordatorio:** no añadir un `mapId` al mapa mientras los pines se
> posicionen por HTML. Es una restricción anterior de este proyecto y sigue
> vigente.

### Verificación

B escribe una nota larga mientras A añade tres lugares y borra uno. La nota de
B no pierde un carácter y los lugares de A aparecen. Repetir con B arrastrando
un elemento entre días.

---

## 7. Fase 5 · Bloqueo optimista y el 409

**1½ días.** Aquí es donde «sin sobrescribirse» deja de ser una promesa.

### Servidor — `api/plan_items.php`, acciones `update`, `move` y `del`

```sql
UPDATE plan_items SET …, ver = ver + 1 WHERE id = ? AND ver = ?
```

Si `rowCount() === 0` → **HTTP 409** con
`{ok:false, conflicto:true, item:<fila fresca>}`.

### ⚠️ Trampa: `ver = ver + 1` no es cosmético

`db.php` **no** activa `PDO::MYSQL_ATTR_FOUND_ROWS`, así que `rowCount()`
cuenta filas *modificadas*, no filas *coincidentes*. Sin esa columna
incrementándose, guardar un valor idéntico al que ya había devolvería 0 y el
cliente vería un **conflicto falso**. Con `ver = ver + 1` la fila siempre
cambia si el `WHERE` casó, y un 0 significa de verdad «alguien se me adelantó».

### Servidor — `api/plan_update.php`

El mismo tratamiento contra `planes.rev`. Hoy este endpoint **escribe la fila
completa** vía `sp_actualizar_plan` (así se diseñó a propósito, para poder
poner un campo a `NULL`), de modo que dos personas cambiando el título o las
fechas a la vez se pisan sin remedio.

### Cliente — `_sync()` deja de ser fuego-y-olvida

Manda el `ver` que tenía y gana un tercer camino: en 409 revierte su cambio al
valor del servidor, repinta ese lugar y muestra un aviso discreto.

> *Ana cambió «La Bufadora» mientras lo editabas. Se recargó con su versión.*

### Verificación

Dos navegadores con el mismo lugar abierto. A guarda; B guarda después → B
recibe 409, ve el aviso y el valor de A. Repetir con `move` y con `del`
(borrar algo que ya estaba borrado no debe dar error rojo: debe desaparecer en
silencio).

---

## 8. Fase 6 · Presencia

**½ día.**

```sql
ALTER TABLE plan_miembros ADD visto_en TIMESTAMP NULL;
```

El pulso lo actualiza. Los avatares de la cabecera llevan un punto verde si su
dueño estuvo activo en los últimos 45 segundos.

Media jornada de trabajo, y es lo que hace que la demostración **se vea**
colaborativa en lugar de tener que explicarse con palabras.

(Ver la trampa de §4: este `UPDATE` no debe llevar disparador.)

---

## 9. Fase 7 · Verificación, documentación y repositorio

**½ día.**

- Matriz de concurrencia con dos cuentas reales y dos navegadores: los nueve
  pares de operaciones simultáneas sobre el mismo lugar.
- `basedatos/instalar.sql`, `basedatos/actualizar_bd.sql` y
  `basedatos/rutinas.sql` al día. **Una instalación nueva debe quedar idéntica
  a una migrada** — el desajuste de `viajes_usuario_ibfk_3` que apareció en la
  entrega anterior nació exactamente de saltarse esto.
- `herramientas/diagnostico.php` comprueba los 20 disparadores y las columnas
  nuevas.
- Ampliar `REPORTE_rutinas_bd.md` con las rutinas nuevas y escribir
  `REPORTE_colaboracion.md`.
- Copiar al repositorio, commit y push.

---

## 10. Calendario

| Día | Fase |
|---|---|
| 1 | Invitaciones y panel de miembros |
| 2 | Migración, `sp_tocar_plan`, los 15 disparadores |
| 3 | `plan_pulso.php` y el latido |
| 4 | Fusión selectiva |
| 5 | Bloqueo optimista y el 409 |
| 6 | Presencia, verificación cruzada, documentación, push |

---

## 11. Decisiones tomadas, y por qué

| Decisión | Motivo |
|---|---|
| **Sondeo, no WebSocket ni SSE** | XAMPP no tiene proceso persistente. SSE gasta un *worker* de Apache por usuario |
| **Contador `rev` entero, no `updated_at`** | `timestamp` tiene resolución de 1 s; dos cambios en el mismo segundo serían indistinguibles |
| **`ver` entero en `plan_items`** | Lo mismo, más el asunto de `rowCount()` de §7 |
| **El `UPDATE` siempre sube `ver`** | Sin ello, `rowCount()` da 0 al guardar un valor idéntico → conflicto falso |
| **`session_write_close()` en el pulso** | El bloqueo de sesión de PHP serializaría todas las peticiones del usuario |
| **La unidad de conflicto es el lugar** | No la letra. La alternativa son CRDTs y un servidor persistente que no existe |
| **Los 15 disparadores entran completos** | Se podrían recortar a 8 dejando fuera reparto y miembros, pero son mecánicos y suman al requisito de la asignatura |
| **La presencia (fase 6) se queda** | Media jornada a cambio de una demostración que se explica sola |

---

## 12. Lo que este plan **no** entrega

Conviene decirlo antes de empezar, no después:

- **No es edición carácter a carácter** tipo Google Docs. La unidad de
  conflicto es el lugar, la nota o el gasto.
- **Latencia de 5 segundos**, no instantánea.
- **El correo de invitación seguirá fallando** en máquinas sin
  `includes/mail_config.php`. El enlace copiable es el camino fiable.
- **Quien tenga el enlace entra**, durante 7 días y una sola vez. Es el modelo
  que ya eligió el código; este plan no lo cambia.

---

## 13. Antes de retomarlo

Comprobar que estas cuatro afirmaciones siguen siendo ciertas. Si alguna ha
dejado de serlo, el plan necesita una revisión:

1. `plan_template.html:262` sigue siendo el botón de invitar con
   `onClick="{{ noop }}"`.
2. `js/plan_logic.js` sigue sin mencionar `plan_invitar`.
3. `planes` no tiene columna `rev` y `plan_items` no tiene columna `ver`.
4. La base sigue teniendo 5 disparadores, 4 funciones y 5 procedimientos.

Comando para verificar los puntos 3 y 4 de una vez:

```bash
mysql -u root ruta_nomada -e "SELECT COUNT(*) AS disparadores FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='ruta_nomada'; SELECT ROUTINE_TYPE, COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA='ruta_nomada' GROUP BY ROUTINE_TYPE; SHOW COLUMNS FROM planes LIKE 'rev'; SHOW COLUMNS FROM plan_items LIKE 'ver';"
```

---

*Estado de la base al escribir este plan: 6 planes, 38 lugares, 7 miembros,
1 invitación, 5 disparadores, 4 funciones, 5 procedimientos.*
