# Plan de implementación — «Invita a compañeros de viaje»

> ## ✅ LAS TRES FASES HECHAS — 12/08/2026
>
> Las cinco decisiones de la §2 se confirmaron y se implementaron tal cual.
> Lo verificado, y las dos correcciones que hubo que hacer sobre la marcha,
> están al final, en la §10.

**Fecha:** 12/08/2026
**Frames:** `screens_ref/Invita a compañeros de viaje.png`,
`screens_ref/Invita a compañeros de viaje (enlace copiado).png`,
`screens_ref/Invita a compañeros de viaje (Gestiona compañeros de viaje).png`

Este documento **sustituye a la §3 «Fase 1 · El botón de invitar cobra vida»**
de `PLAN_colaboracion.md`. Aquel esbozo se escribió sin los frames y suponía
cosas que los frames desmienten (había un selector de rol; la lista de
miembros vivía en la misma pantalla). Las fases 2 a 7 de aquel plan siguen
siendo válidas y no se tocan aquí.

---

## 0. Qué piden los frames, en una frase

Un solo botón —el círculo de línea discontinua con la persona y el `+` que hay
junto a los avatares en la cabecera del viaje— abre **una ventana con dos
pantallas**: la de invitar (enlace para copiar + campo de correo) y la de
gestionar (lista de compañeros con una ✕ para quitar a cada uno).

---

## 1. De dónde se parte

### El backend de invitaciones ya está terminado

| Pieza | Estado |
|---|---|
| `plan_invitaciones` | Tabla creada. Token de 64 hex; en la base **sólo su SHA-256**; rol, correo, caducidad y `usada` |
| `includes/plan_invite_lib.php` | `planInviteCreate()` genera el enlace, deduce la URL base de la petición y manda el correo |
| `api/plan_invitar.php` | CSRF + `planAccess(..., 'propietario')` → `{ok, link, correo_enviado}` |
| `plan_invitacion.php` | Valida hash, vigencia y un solo uso; inserta en `plan_miembros` sin duplicar ni degradar un rol |
| `login.php` | Respeta `$_SESSION['despues_de_login']`: quien no tiene cuenta se registra y vuelve al enlace |
| `planFullJson()` | **Ya devuelve `miembros`** con `usuario_id`, `rol`, `nombre`, `apellidos`, `foto_perfil` |

### Lo único que falta es quien lo llame

| Hueco | Dónde |
|---|---|
| El botón de la cabecera está muerto | `plan_template.html:270` → `onClick="{{ noop }}"` |
| El de «Añadir compañero de viaje» también | `plan_template.html:827` → `onClick="{{ noop }}"` |
| No existe la ventana | Nada en `plan_template.html` ni en `js/plan_logic.js` menciona `plan_invitar` |
| No se puede quitar a nadie | No hay endpoint de miembros |

---

## 2. Las cinco cosas que hay que decidir antes de escribir código

Ninguna es de maquetación. Todas cambian lo que se guarda en la base o lo que
le pasa a los datos de alguien.

### 2.1 ⚠️ El enlace del frame es para compartir; el token de hoy muere al primer uso

El marcador de posición del frame dice literalmente
`https://urlqueseracompartida/…` y el título está en **plural**. Pero
`plan_invitacion.php` exige `usada = 0` y acto seguido hace `usada = 1`: **la
segunda persona que abra ese enlace recibe «inválido, ya fue usado o
expiró»**. Pegar el enlace en un grupo de WhatsApp con cuatro compañeros
metería a uno y dejaría fuera a tres, sin explicación.

**Propuesta:** separar los dos tipos de invitación que ya conviven en la tabla.

| Tipo | `email` | Usos | Quién lo crea |
|---|---|---|---|
| Invitación por correo | el destinatario | **1** | el campo de correo del frame, y `api/plan_create.php` |
| Enlace para compartir | `NULL` | **sin límite** | la ventana, al abrirse |

Se añaden dos columnas: `usos` (contador) y `usos_max` (`NULL` = sin límite).
`plan_invitacion.php` pasa a comprobar `usos < usos_max OR usos_max IS NULL` y
a hacer `usos = usos + 1`.

### 2.2 ⚠️ Sólo se guarda el hash, así que el enlace **no se puede volver a enseñar**

Consecuencia directa del diseño actual, y es la trampa que más fácil se pasa
por alto: si la ventana intenta «recuperar el enlace de este plan» para
pintarlo, no puede — en la base sólo está su SHA-256. Las salidas son tres:

- **(a) Guardar el token en claro sólo para el enlace de compartir.** Columna
  `token_claro` rellenada únicamente cuando `email IS NULL`. Las invitaciones
  por correo siguen siendo sólo hash.
- **(b) Rotar el token cada vez que se abre la ventana.** Inaceptable: el
  enlace que ya mandaste por WhatsApp muere en cuanto vuelves a abrir la
  ventana.
- **(c) Crear uno nuevo en cada apertura y dejar vivos los viejos.** Cada
  apertura deja una credencial permanente más. Peor que (a) y sin límite.

**Recomiendo (a).** El enlace es, por definición, algo que se reparte; su
secreto *es* el control de acceso al plan, y la base ya contiene todo lo que
ese enlace protege. Guardarlo en claro no añade ninguna exposición que el
propio reparto no tenga. Se documenta en la migración para que quede claro que
es deliberado y no un olvido.

*(Alternativa elegante descartada: derivar el token con HMAC de un secreto de
la aplicación. Obliga a un archivo de configuración más —ya hay cinco
ignorados por git— y los compañeros ya penan con la instalación.)*

### 2.3 ⚠️ Quitar a alguien deja atrás su dinero y sus votos

`plan_item_gasto` y `plan_item_reacciones` apuntan a `usuarios`, **no** a
`plan_miembros`. Al borrar la fila de `plan_miembros` no se dispara ninguna
cascada: sus filas se quedan.

Y no se quedan quietas. `js/plan_logic.js:1934` construye el reparto
**recorriendo `this.MIEMBROS`**, no las filas guardadas: la próxima vez que
alguien edite el reparto de ese lugar, **la parte del que ya no está
desaparece en silencio** y las cuentas de los demás cambian sin que nadie lo
pida.

**Recomiendo:**

- **Borrar sus reacciones.** Un voto de quien ya no está en el viaje no
  significa nada y el contador quedaría mal.
- **Conservar su reparto de gastos.** El dinero ya gastado es un hecho;
  borrarlo reescribiría en silencio el saldo de todos. Se conserva y la dona
  etiqueta ese trozo como «Alguien que ya no está».

Es una decisión de producto, no técnica: **conviene confirmarla antes de
implementarla.**

### 2.4 Sólo el propietario invita

`api/plan_invitar.php` ya exige rol `propietario`. Así que el botón **se dibuja
sólo para el propietario**: un botón que siempre falla es peor que ningún
botón. Hoy `plan_logic.js` expone `puedeEditar` pero no si eres el dueño; hace
falta un `esProp` nuevo (`this.ROL === 'propietario'`).

Con más razón en la pantalla de gestionar, que borra membresías.

### 2.5 El propietario no puede quitarse a sí mismo

Dejaría un plan sin dueño, y `planes.usuario_id` y `sp_borrar_plan` cuentan con
que exista. Su fila se pinta **sin ✕**.

---

## 3. Las medidas, sacadas de los frames

Los tres frames son de 1917 × 1078. La escala se calibró contra el modal
«Cambiar foto», que está implementado a **750 px CSS de ancho** y mide **1000
px en su frame**: **escala 4/3**, o sea **1 px de frame = 0,75 px CSS**.
Todo lo que sigue ya está convertido a px CSS.

### El panel (idéntico en las tres pantallas)

| Medida | Valor |
|---|---|
| Ancho | **450** (600 px de frame, exacto) |
| Alto | automático — 264 al invitar, 266 con cuatro compañeros |
| Posición | centrado en los **dos** ejes |
| Radio | **18** |
| Relleno lateral | **30** (el contenido va de 30 a 420) |
| Fondo y sombra | reutilizar los de «Cambiar foto»: `#ffffff`, `0 20px 60px rgba(14,42,51,.32)`, velo `rgba(13,31,39,.45)` |

### La cabecera (idéntica en las dos pantallas)

| Elemento | Medida |
|---|---|
| Título | centrado en el ancho del panel; tinta **#212529**; peso 700 |
| Título — altura de mayúscula | **14,25** → ≈ **23 px** de tamaño (ver nota) |
| Título — tinta arriba | **28,5** desde el borde superior del panel |
| Botón ✕ | círculo **30 × 30**, a **9** del borde superior y **9** del derecho |
| Botón ✕ — fondo | **#F3F4F5**, *siempre visible* (en «Cambiar foto» sólo sale al pasar el ratón) |
| Botón ✕ — aspa | **10,5 × 10,5**, gris ≈ **#7F7F7F** |
| Flecha ← (sólo pantalla 2) | **18 × 13,5**, tinta arriba a **17,25**, izquierda a **21,75** del panel; tinta **#212529** |

> **Nota sobre los tamaños de letra — corregida al implementar.** El primer
> intento los dedujo manteniendo la proporción del título de «Cambiar foto»,
> y salieron **demasiado grandes**. El método que sí funciona es medir la
> **altura de mayúscula** en el frame y dividir por la de Poppins (0,70 em).
> Los dos métodos se comprobaron contra el ancho de tinta del texto y sólo el
> segundo cuadra: «Gestiona tus compañeros de viaje» mide 212,25 de ancho en
> el frame, y Poppins 700 a 12 px da 210,5.

| Texto | Mayúscula en el frame | Tamaño en Poppins |
|---|---|---|
| Los dos títulos | 14,25 | **20 px** / 700 |
| La URL | 9,75 (ascendente de la `h`) | **13,5 px** / 400 |
| El texto de ayuda del correo | 9,0 | **13,5 px** / 400 |
| «Copiar enlace» | 9,75 | **14 px** / 700 |
| «Gestiona tus compañeros de viaje» | 8,25 | **12 px** / 700 |
| Los nombres de la lista | 8,25 | **12 px** / 400 |

### Pantalla 1 — Invitar

| Elemento | Medida |
|---|---|
| **Caja del enlace** | **390 × 51,75**, arriba a **70,5** del borde del panel |
| — fondo / borde / radio | `#FFFFFF` · 1 px `#DEE2E6` · radio **6** |
| — icono de cadena | **18,75 × 12**, a **12** del borde interior; tinta **#6C757D** |
| — URL | a **9,75** del icono; **subrayada**; tinta **#212529**; ≈ 15,5 px; cortada con `…` |
| — botón azul | **128,25 × 37,5**, a **7,5** del borde derecho interior, centrado en vertical |
| — botón azul, forma | píldora (radio 18,75); fondo **#3F52E3** —el mismo azul que «Selecciona»—; texto blanco 700 |
| **Caja del correo** | **390 × 51,75**, a **15** por debajo de la anterior |
| — fondo / borde / radio | `#F6F6F6` · **sin borde** · radio **6** |
| — icono de sobre | **18,75 × 13,5**, a **12** del borde interior; tinta **#6C757D** |
| — texto de ayuda | «Invita a tus amigos por email», tinta **#6C757D**, ≈ 15,5 px |
| **Divisor** | 1 px **#DEE2E6**, ancho del contenido (390), a **15** de la caja del correo |
| **Fila «Gestiona…»** | tinta arriba a **15,75** del divisor |
| — icono persona + rueda | **18** de ancho, a **12,75** del borde del contenido — *alineado con los iconos de las dos cajas* |
| — texto | a **10,5** del icono; peso 700; tinta **#6C757D**; ≈ 13,5 px |
| — hasta el borde inferior | **28,5** |

### Frame 2 — «Copiado»

El botón mantiene **exactamente el mismo rectángulo** (medido: idéntico al px
en los dos frames). Sólo cambian los colores:

| | Reposo | Copiado |
|---|---|---|
| Fondo | `#3F52E3` | **`#D4EDFF`** |
| Texto | `#ffffff` | **`#212529`** |
| Etiqueta | «Copiar enlace» | «Copiado» |

⚠️ «Copiado» es más corto que «Copiar enlace»: **sin un ancho fijo de 128 el
botón encogería** y la caja daría un salto. Hay que fijarlo.

### Pantalla 2 — Gestiona compañeros de viaje

| Elemento | Medida |
|---|---|
| Primera fila | tinta arriba a **68,25** del borde del panel |
| Paso entre filas | **42** (medido separador a separador, tres veces) |
| Avatar | círculo **30**, pegado al borde izquierdo del contenido |
| Nombre | a **15,75** del avatar; tinta **#212529**; ≈ 15,5 px; peso normal |
| Botón ✕ | círculo **27 × 27**, borde gris claro, aspa ≈ **#7F7F7F**, a **30** del borde derecho del panel |
| Separadores | 1 px **#DEE2E6**, ancho del contenido; **entre** filas — no hay uno tras la última |
| Bajo la última fila | **37,5** hasta el borde |

---

## 4. Fase 1 · La ventana, con el enlace y el correo

**Medio día.**

### Base de datos — `basedatos/migrate_invitar.sql` (nuevo) + `actualizar_bd.sql` + `instalar.sql`

Idempotente con `ADD COLUMN IF NOT EXISTS`, como el resto:

```sql
ALTER TABLE plan_invitaciones
  ADD COLUMN IF NOT EXISTS usos      smallint unsigned NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS usos_max  smallint unsigned NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS token_claro varchar(64) NULL;
UPDATE plan_invitaciones SET usos = usada WHERE usos = 0 AND usada = 1;
```

`usada` se deja en la tabla y se sigue manteniendo, para no romper nada que la
lea; la verdad pasa a estar en `usos`.

### `includes/plan_invite_lib.php`

- `planInviteCreate()` gana un parámetro de usos máximos (por omisión 1, que es
  lo de hoy) y guarda `token_claro` sólo cuando no hay correo.
- Nueva `planEnlaceDePlan(int $planId): ?string` — devuelve el enlace
  reutilizable del plan: busca uno vigente sin correo y sin límite de usos, y
  si no lo hay lo crea. **Este es el único sitio que decide si se reutiliza o
  se crea**, para que la ventana no acabe sembrando filas al abrirse.

### `api/plan_invitar.php`

- Nueva acción `enlace` → `planEnlaceDePlan()`, sin efectos secundarios en
  aperturas sucesivas.
- La invitación por correo pasa a validar con **`correoConDominioReal()`**
  (`includes/email_dominio.php`, el que ya usa `register.php`) y no sólo con
  `filter_var`: hoy una invitación a `@fake.com` se da por enviada y no llega.
- **Límite de envíos**: tope por plan y hora. Hoy no hay ninguno, y cada POST
  con correo manda un mensaje real por Gmail. Sin tope, un bucle deja la cuenta
  marcada como spam.

### `plan_invitacion.php`

`usada = 0` → `(usos_max IS NULL OR usos < usos_max)`, y `usada = 1` →
`usos = usos + 1` (más `usada = 1` cuando se alcanza el tope).

### `plan_template.html`

Ventana nueva bajo un `sc-if value="{{ invModal }}"`, calcada en estructura a
la de «Cambiar foto»: velo + contenedor centrador con `pointer-events:none` +
panel con `pointer-events:auto`, `animation:rnSube .3s cubic-bezier(.22,1,.36,1)`.

Y los dos botones muertos pasan a `onClick="{{ invAbrir }}"`:
`plan_template.html:270` (cabecera) y `:827` («Añadir compañero de viaje»).

### `js/plan_logic.js`

Estado `invModal`, `invPantalla` (`'invitar' | 'gestiona'`), `invLink`,
`invCopiado`, `invEmail`, `invMsg`. Métodos `_invAbrir`, `_invCerrar`,
`_invCopiar`, `_invEnviar`. Y `V.esProp` para que el botón sólo se dibuje al
propietario.

### ⚠️ Trampas conocidas del runtime `dc`

Todas ya nos han mordido antes; están en `PLAN_cambiar_foto.md`:

- Sólo existen `sc-if`, `sc-for`, `sc-host`, `sc-comp`, `style-hover` y
  `style-focus`. **Un atributo inventado como `style-if` se ignora sin dar
  ningún error.**
- `setState` repinta el árbol entero: nada de guardar referencias a nodos.
- La trampa de foco tiene que **volver a buscar** los elementos enfocables en
  cada pulsación de Tab, por lo mismo.
- Escape: `invModal` es capa superior igual que `fotoModal`. Va junto a él, al
  principio de la cascada de `this._esc`.
- El clic fuera **no cierra** —igual que en «Cambiar foto»—, sólo la ✕.

### ⚠️ Trampa: `navigator.clipboard` no siempre existe

Necesita contexto seguro. `http://localhost` lo es; **`http://192.168.x.x` no**,
y ahí `navigator.clipboard` es `undefined`. Si un compañero abre el proyecto
por IP de la red local, copiar falla en silencio y el botón diría «Copiado»
sin haber copiado nada. Hace falta el respaldo con `document.execCommand`
sobre un `<textarea>` temporal, y **pintar «Copiado» sólo cuando la copia
haya salido bien de verdad**.

### Verificación

1. Cuenta A abre la ventana → sale un enlace. Cerrar y reabrir **cinco veces**
   → `SELECT COUNT(*) FROM plan_invitaciones WHERE plan_id = N` no sube.
2. Cuenta B abre el enlace en incógnito → aterriza en `plan.php?id=N` como
   editor y su avatar sale en la cabecera de A al recargar.
3. **Cuenta C abre el mismo enlace** → entra también. *(Esta es la prueba que
   falla hoy y la razón de la §2.1.)*
4. Copiar → el botón cambia a «Copiado» **sin cambiar de tamaño**; el
   portapapeles trae la URL.
5. Correo a un dominio inexistente → lo rechaza antes de intentar el envío.

---

## 5. Fase 2 · La pantalla de gestionar

**Medio día.**

### `api/plan_miembros.php` (nuevo)

Una sola acción: `quitar`. Listar no hace falta —`planFullJson()` ya manda
`miembros` en el arranque—; cambiar de rol no lo piden los frames.

- CSRF + `planAccess(..., 'propietario')`.
- **Rechaza quitar al propietario**, incluido a sí mismo.
- Dentro de una transacción: borra sus `plan_item_reacciones` de los lugares de
  ese plan, borra su fila de `plan_miembros`, y **conserva** su
  `plan_item_gasto` (§2.3).
- Devuelve la lista de miembros ya actualizada, para no obligar a recargar.

### Plantilla y cliente

La segunda pantalla es el **mismo panel** con otro cuerpo: cabecera con flecha
← y título distinto, y la lista. `max-height` con desplazamiento en el cuerpo,
como en «Cambiar foto», porque con doce compañeros el panel se saldría.

El avatar reutiliza el mismo apaño que la cabecera: foto si la hay, y si no la
inicial sobre el círculo dorado.

### Verificación

Quitar a B desde A → su avatar desaparece de la cabecera de A; B recarga
`plan.php?id=N` y recibe «No tienes acceso a este plan»; en la base su fila de
`plan_miembros` no está y sus filas de `plan_item_gasto` **sí**; la ✕ del
propietario no se dibuja.

---

## 6. Fase 3 · Repaso contra los frames

**Medio día.** Lo mismo que se hizo con «Cambiar foto»: capturar las dos
pantallas del navegador, medirlas con el mismo guion y comparar contra la
tabla de la §3. La experiencia dice que los tamaños de letra y los huecos
verticales son lo que se desvía.

Actualizar después `REPORTE_base_de_datos.md` y `REPORTE_rutinas_bd.md`, que ya
van desfasados (dicen 19 tablas y 14 rutinas; hay 21 y 16).

---

## 7. Calendario

| Fase | Coste |
|---|---|
| 1 · La ventana, el enlace y el correo | 0,5 día |
| 2 · La pantalla de gestionar | 0,5 día |
| 3 · Repaso contra los frames | 0,5 día |
| **Total** | **1,5 días** |

---

## 8. Lo que este plan **no** entrega

- **Cambiar el rol de alguien.** Los frames no lo piden. Todo el que entra por
  el enlace es `editor`.
- **Caducar o revocar el enlace.** Sigue el vencimiento de 7 días que ya
  aplica `planInviteCreate()`. Un botón de «rehacer enlace» sería lo siguiente.
- **Ver invitaciones pendientes.** La pantalla de gestionar lista miembros, no
  invitados que aún no han entrado.
- **Tiempo real.** Que el avatar del recién llegado aparezca sin recargar es la
  Fase 3 de `PLAN_colaboracion.md` (el sondeo), no ésta.

---

## 9. Antes de retomarlo

1. ~~Confirmar la decisión de la §2.3~~ — confirmada el 12/08/2026.
2. ~~Confirmar la de la §2.2~~ — confirmada el 12/08/2026.
3. Recordar que **la contraseña de aplicación de Gmail sigue sin rotar** y que
   esta función la usa para enviar de verdad.

---

## 10. Lo que se comprobó, y contra qué

### Contra el servidor y la base de verdad

| Prueba | Resultado |
|---|---|
| Pedir el enlace **cinco veces** seguidas | 1 sola fila nueva, y el mismo enlace las cinco veces |
| Una segunda persona abre el enlace | Entra. **Esto es lo que fallaba antes** |
| Una tercera lo abre | Entra |
| Alguien que ya está dentro lo reabre | Va al plan y **no gasta otro uso** (`usos` = 2 con tres aperturas) |
| Invitación de un solo uso | El primero entra (`usos` 1/1, `usada` 1); el segundo recibe «inválido, ya fue usado o expiró» |
| Correo con formato malo | Rechazado |
| Correo con dominio inexistente | Rechazado **antes** de intentar el envío |
| Correo de alguien que ya es miembro | «Esa persona ya está en el viaje» |
| El 11.º correo en una hora | Cortado, y también **antes** de enviar |
| Un editor pide el enlace o quita a alguien | 403 en los dos casos |
| El propietario intenta quitarse | «No puedes quitar a quien creó el viaje» |
| Quitar a alguien | Su fila de `plan_miembros` y sus reacciones desaparecen; **su reparto del gasto se queda**, tal como se decidió |
| El expulsado abre el plan | Lo manda a `mis_planes.php` |

El orden de los `SET` del `UPDATE` que gasta un uso se comprobó aparte contra
MariaDB: las asignaciones **ven el valor ya cambiado** de las anteriores, y por
eso `usada` va escrito primero.

Cuentas y viaje de prueba borrados al terminar. Los 7 planes y las 2 fotos de
la cuenta real quedaron intactos.

### Contra los frames, en el navegador

Panel **450 de ancho, centrado exacto en los dos ejes**, radio 18; ✕ de 30 a 9
del borde superior y 9 del derecho; caja del enlace arriba a 70,5; caja del
correo a 137,5; alto total **263,9** frente a los 264 del frame. En la segunda
pantalla: paso entre filas **42** clavado, avatar de 30 pegado al borde del
contenido, ✕ de 27 a 30 del borde derecho, separadores entre filas y **ninguno
tras la última**, y el propietario **sin** ✕.

### ⚠️ Dos cosas que el panel del navegador integrado NO deja comprobar

Y las dos parecen fallos si uno se fía de lo que mide:

- **Las animaciones y las transiciones se quedan congeladas en el instante 0**,
  porque ese panel no compone fotogramas. La ventana parecía 20 px baja (es el
  `translateY` de `rnSube` sin arrancar) y el botón parecía no cambiar de color
  al copiar. Terminando las animaciones a mano, la ventana queda centrada y el
  botón pasa a `#D4EDFF` con texto `#212529`, que es lo que dice el frame.
- **El portapapeles está denegado en ese panel**: `writeText` responde
  «Write permission denied» y `execCommand('copy')` devuelve `false`, incluso
  con el documento enfocado y el permiso concedido. Sustituyendo sólo esa
  llamada del sistema se comprobó que el código propio manda **el enlace
  completo**, que el botón pasa a «Copiado» **sin cambiar de ancho** y que
  vuelve solo a los 2 s. **Que la copia funcione de verdad es lo único que
  queda por ver en un navegador normal.**

### La trampa que se llevó más tiempo

`V` se construye **clave a clave**; no es una copia del estado. Faltaba
`V.invModal = !!s.invModal` y el `sc-if` de la plantilla leía `undefined`: el
estado cambiaba, el enlace llegaba del servidor, y la ventana **no se pintaba
nunca, sin dar ningún error**. Es la misma familia de fallo silencioso que el
`style-if` inventado de la ventana anterior.
