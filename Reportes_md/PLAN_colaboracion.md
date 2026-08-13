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

> **✅ HECHA el 13/08/2026.** Cuatro cosas salieron distintas de lo escrito
> aquí abajo, y están explicadas al final de la sección, en «Lo que cambió al
> implementarla». La más importante: **`rev` NO sube exactamente una vez por
> acción**, y la verificación de más abajo estaba mal planteada.

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

### Lo que cambió al implementarla

**1 · No son 15 disparadores, son 17. Y el recuento de partida estaba mal.**

Tres por cada una de `plan_gastos`, `plan_listas`, `plan_lista_items`,
`plan_item_gasto` y `plan_item_reacciones`, y sólo dos en `plan_miembros`.
El proyecto pasa de **5 a 22 disparadores**, de **5 a 7 funciones** y de **6 a
7 procedimientos** — no de «4 a 6 funciones y 5 a 6 procedimientos» como decía
la nota académica, que se escribió con un recuento ya desfasado.

**2 · ⚠️ `rev` es un TESTIGO DE CAMBIO, no una cuenta de acciones.**

La verificación de arriba pide que suba «exactamente una vez» por cada cambio,
y con los nueve casos de esa lista se cumple. Pero **hay una acción que la
rompe**: guardar el reparto de un gasto. `api/plan_items.php` borra todas las
filas de `plan_item_gasto` del lugar y vuelve a insertarlas, así que repartir
entre cuatro personas sube `rev` **ocho veces**, no una.

No es un fallo, pero sí una expectativa mal escrita. Lo único que se le pide a
`rev` es que **cambie cuando algo cambió** y **no cambie cuando no cambió
nada**; nadie va a interpretar el salto. Como todo eso ocurre dentro de una
transacción, quien sondea ve un solo cambio neto.

**3 · Las reacciones necesitaban también el disparador de UPDATE, y no era
evidente.**

`api/plan_reacciones.php` cambia de emoji con `INSERT ... ON DUPLICATE KEY
UPDATE`. Comprobado en MariaDB 10.4: sobre una fila que ya existe eso dispara
**sólo el `AFTER UPDATE`**, no el `AFTER INSERT`, así que no hay doble conteo.
Y de regalo, reenviar el **mismo** emoji no dispara nada: `rev` no se mueve
cuando en realidad no cambió nada.

**4 · Los borrados en cascada no disparan nada, y de eso depende el diseño.**

Comprobado antes de escribir una línea. Gracias a eso, borrar un lugar sube
`rev` **una sola vez** aunque arrastre su reparto y sus reacciones, y borrar un
viaje entero no intenta tocar una fila de `planes` que está desapareciendo.

### Cómo se comprobó

Doce casos contra la base real, con un usuario y un plan de usar y tirar:
los nueve tipos de cambio de la lista, más cambiar de emoji, más los dos que
**no** deben mover `rev` —reenviar el mismo emoji y el `UPDATE` sobre
`plan_miembros` que hará el sondeo de la fase 6—. **12 de 12.** El plan se
borró después sin error y no quedó nada de ensayo.

---

## 5. Fase 3 · El latido

> **✅ HECHA el 13/08/2026**, con **una desviación deliberada** y **un agujero
> que este plan no vio**. Los dos, al final de la sección.

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

### Lo que cambió al implementarla

**1 · ⚠️ EL AGUJERO QUE ESTE PLAN NO VIO: quien edita se avisa a sí mismo.**

Los disparadores de la fase 2 suben `rev` con **cualquier** cambio, y eso
incluye los tuyos. Tal como estaba escrita esta fase, en cuanto alguien añadía
un lugar su propio pulso veía el número distinto y le anunciaba «hay
novedades» por algo que acababa de hacer él. El latido habría sido inútil
desde el primer minuto.

Se cierra en el servidor, no en el cliente: **`apiJson()` adjunta el `rev`
resultante a toda respuesta correcta de un endpoint que trabaje sobre un
plan**, y `_sync()` lo adopta en el mismo momento de escribir. Como el número
viaja en la misma respuesta, **no queda ninguna ventana de carrera**: no hace
falta un segundo viaje en el que otra persona pudiera colarse.

Va en **un solo sitio** a propósito. Hacerlo endpoint por endpoint serían más
de veinte llamadas a `apiJson()` repartidas por seis archivos, y bastaría
olvidar una para que reapareciera el falso aviso justo en la acción olvidada.

Detalle que importa: adoptar el número **no** borra un aviso que ya estuviera
puesto. Si alguien cambió algo y todavía no lo has traído, ponerte a editar no
debe hacer que ese aviso desaparezca.

**2 · Esta fase AVISA; no trae los cambios. Y es a propósito.**

El plan decía «si `rev` cambió → `fetch(plan_get.php)` → `_fusionar(j)`». Pero
`_fusionar()` **es la fase 4**. Sustituir el estado sin ella se llevaría por
delante el título a medio teclear o la nota a medio redactar — exactamente lo
contrario de «nadie pierde trabajo sin enterarse».

Así que la fase 3 entrega un aviso —«Alguien cambió algo en este viaje», con
un botón **Actualizar** y otro para descartarlo— y deja que cada quien decida
cuándo recargar. Ese botón recarga la página entera, que es lo único honesto
que se puede hacer todavía; la fase 4 lo cambiará por la fusión selectiva.

**3 · `session_write_close()` obligó a no usar `planAccess()`.**

`planAccess()` consulta la sesión **y** la base en la misma llamada, así que
usarlo obligaría a mantener el bloqueo del fichero de sesión durante la
consulta, que es justo lo que había que evitar. El pulso lee de la sesión sólo
el id de quien pregunta, la suelta, y **mete la comprobación de acceso dentro
de la misma consulta que trae el número**: el `JOIN` con `plan_miembros` hace
de guardián. Una sola consulta, y el bloqueo liberado antes de tocar la base.

**4 · La respuesta pesa 19 bytes, no 25.**

### Cómo se comprobó

| Prueba | Resultado |
|---|---|
| Pestaña oculta, 14 s | **0 peticiones** en el registro de Apache (habrían sido ~3) |
| Pestaña visible, 12 s | 2 pulsos, HTTP 200, **19 bytes** cada uno |
| Otra sesión añade un lugar | Aviso en pantalla en **menos de 6 s** |
| **Cambio propio** | `rev` sube 3→4, el cliente lo adopta y tras 8 s **cero avisos** |
| 12 pulsos sin novedad | El ritmo pasa a 15 s |
| Cualquier tecla o clic | Vuelve a 5 s |
| Tres fallos seguidos | Se apaga y sale «Sin conexión con el plan» |
| 12 s después de apagarse | **0 peticiones**: se apagó de verdad, no siguió insistiendo |

> **Cómo se probó lo de la pestaña oculta.** El panel de navegador integrado se
> declara **siempre** `document.hidden = true`, así que el latido no arrancaba
> nunca. Eso verificó gratis el primer caso, y para el resto se sustituyó
> **sólo esa propiedad del navegador** — el código del latido no se tocó.

---

## 6. Fase 4 · Fusión selectiva

> **✅ HECHA el 13/08/2026.** El botón «Actualizar» de la fase 3 desaparece:
> los cambios entran solos. Lo que salió distinto, al final de la sección.

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

### Lo que cambió al implementarla

**1 · Lo primero fue borrar código, no escribirlo.**

`_boot()` traía el mapeo del JSON del servidor al estado escrito a mano dentro
de sí mismo. La fusión necesita **exactamente el mismo mapeo**, y copiarlo
habría dejado dos versiones que acabarían divergiendo. El día que divergieran,
lo que se vería es que «a veces» un campo se pierde al llegar un cambio de
otra persona — imposible de encontrar.

Así que primero se extrajeron `_diasDe()`, `_mapearItems()`, `_mapearGastos()`
y `_mapearListas()`. `_boot()` pasó a ser cuatro líneas y la fusión usa los
mismos cuatro métodos. Es la misma lección que dejó `_mapMiembros()` en la
fase 1.

**2 · Lo que está en vuelo también hay que protegerlo, y el plan no lo decía.**

La tabla de arriba cubre lo que se está *editando*, pero no lo que se acaba de
crear y **el servidor todavía no conoce**: un lugar recién añadido, una lista
recién creada, un gasto recién apuntado. Como no vienen en la respuesta del
servidor, una fusión ingenua **los borraría de la pantalla** justo entre que se
crean y se confirman.

Se reconocen sin ambigüedad: los ids temporales son **cadenas** (`'l'+fecha`,
`'g'+fecha`) frente a los números del servidor, y los lugares en vuelo no
tienen `sid`.

**3 · Si cambia el NÚMERO de días no se fusiona: se avisa.**

`dayOpen`, `dayRecsOpen`, `placeTxts` y `daySubs` van indexados por día.
Rehacerlos con menús abiertos por medio es pedir un fallo raro, así que ese
caso —y sólo ese— conserva el aviso con botón **Actualizar** de la fase 3.

**4 · Si alguien borra el lugar que tú tienes abierto, se cierran sus menús.**

Proteger lo abierto es no *sobrescribir* lo que estás editando, no resucitar
una fila que ya no existe. Si el lugar desaparece del servidor, desaparece — y
`itemOpen`, `horaMenu` y `gastoMenu` que apuntaban a él se ponen a `null` en
vez de dejar una ventana flotando sobre la nada.

**5 · El reintento tras un arrastre lo hace el propio latido.**

Enganchar «al soltar» habría significado tocar los **seis** sitios donde
termina un arrastre, y bastaría olvidar uno para que un cambio se quedara
guardado y sin aplicar para siempre. En vez de eso, cada pulso comprueba si
quedó algo pendiente. Se aplica hasta 5 s después de soltar, y a cambio no hay
ningún sitio que se pueda olvidar.

### Cómo se comprobó

Dos sesiones sobre el mismo viaje. B tenía una nota de 257 caracteres en
edición **y** un lugar abierto; A hizo cinco cambios de golpe: tres lugares
nuevos, uno borrado, y **renombró justo el lugar que B tenía abierto**.

| Qué se miró | Resultado |
|---|---|
| Los tres lugares nuevos | Aparecieron, cada uno en su día |
| El lugar borrado | Desapareció |
| La nota de B | **257 caracteres, ni uno menos**, y seguía en edición |
| El lugar abierto de B | Conservó **su** nombre; el de A no lo pisó |
| Al recargar después | Sale el nombre de A: el servidor siempre lo tuvo bien |
| El aviso | Apareció junto con el cambio y se fue **a los 4501 ms** |
| **Arrastrando** | Durante: 6 lugares y fusión aparcada. Al soltar: 7, y `rev` adoptado |
| Arranque normal tras el refactor | 3 días, 7 lugares en su sitio, lista, miembro y título |

Un detalle que salió en la medición y no es un fallo: el aviso tardó 18,5 s en
llegar porque el latido había retrocedido a 15 s tras doce pulsos sin novedad.
Es el retroceso de la fase 3 haciendo su trabajo.

---

## 7. Fase 5 · Bloqueo optimista y el 409

> **✅ HECHA el 13/08/2026**, con **una corrección al plan**: el candado del
> nombre del viaje **no** puede ir contra `rev`. Explicado al final.

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

### Lo que cambió al implementarla

**1 · ⚠️ CORRECCIÓN: el nombre del viaje no puede ir contra `rev`.**

El plan pedía «el mismo tratamiento contra `planes.rev`». **No funciona.**
`rev` se mueve con **cualquier** cambio del viaje: si alguien añade un lugar
mientras tú escribes el título, tu guardado se rechazaría por algo que no
tiene nada que ver con lo que tocaste. Serían conflictos falsos a todas horas,
y con un latido cada 5 segundos y dos personas trabajando, casi siempre.

Se resuelve con una columna aparte, **`planes.ver`**, que sólo mueve y sólo
comprueba el cambio de nombre.

**2 · Y ese candado NO se puede extender al resto de campos del plan.**

Los subtítulos de los días y el presupuesto se guardan **con retardo de
800 ms** desde el mismo navegador, así que dos escrituras propias se solapan
con facilidad. La segunda llegaría con una versión ya vieja y **el cliente se
daría un 409 a sí mismo**. Cerrar eso pide antes poner en cola las escrituras
del plan, y eso no es de esta fase. Queda anotado en `api/plan_update.php`.

**3 · La trampa del `rowCount()` era real, y ahora hay prueba.**

Medido antes de escribir el candado, contra esta misma base:

| | `rowCount()` |
|---|---|
| Sin `ver = ver + 1`, guardando el **mismo** valor | **0** → conflicto FALSO |
| Con `ver = ver + 1`, guardando el **mismo** valor | 1 → bien |
| Con `ver = ver + 1`, versión que ya no casa | 0 → conflicto DE VERDAD |

**4 · La versión viaja sola, y vuelve sola.**

`_sync()` adjunta el `ver` sin que los quince sitios que lo llaman tengan que
acordarse — si hubiera que hacerlo en cada uno, el candado se quedaría fuera
justo en la acción que se olvidara. Y el servidor **devuelve la versión
nueva**, que el cliente adopta: sin eso, la siguiente edición del mismo lugar
llegaría con la versión vieja y **chocaría consigo misma**.

**5 · En un 409 hay que hacer lo CONTRARIO que en la fase 4.**

La fusión protege lo que tienes abierto para no pisar lo que escribes. Pero
cuando lo que escribiste **ya fue rechazado**, protegerlo deja en pantalla un
valor que no existe en ningún sitio. Por eso `_conflicto()` marca ese lugar
para que la siguiente fusión lo sustituya **saltándose la protección**.

**6 · Falta poder decir QUIÉN se adelantó.**

El aviso del plan decía «*Ana* cambió…». Hoy dice «*Alguien* cambió…»: ni
`plan_items` ni `planes` guardan quién tocó la fila por última vez. Añadirlo
es una columna `editado_por` y escribirla en cada guardado.

### Cómo se comprobó

Once casos contra el servidor real, y después el cliente en el navegador.

| Caso | Resultado |
|---|---|
| A guarda con la versión buena | 200, `ver` 1 → 2 |
| B guarda después con la versión vieja | **409** con la fila fresca y el nombre de A |
| B reintenta con la versión buena | 200 |
| **Guardar el mismo valor otra vez** | **200** — el conflicto falso no aparece |
| `move` con versión vieja / buena | 409 / 200 |
| `del` con versión vieja / buena | 409 / 200 |
| **`del` de algo ya borrado** | **200 `ya_no_estaba`**, sin error rojo |
| Renombrar el viaje, segunda vez con versión vieja | 200 / **409** |
| **Dos subtítulos seguidos** | **200 y 200** — sin candado, no hay 409 contra uno mismo |
| En el navegador, con el lugar **abierto** | Sale el aviso con el valor de A, **y el lugar pasa a mostrar el de A pese a estar abierto** |

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
