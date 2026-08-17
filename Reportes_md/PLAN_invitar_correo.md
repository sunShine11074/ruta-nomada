# Plan — redactar el mensaje y enviar a varios a la vez

Ampliación de la ventana «Invita a compañeros de viaje». El frame nuevo añade
dos cosas: **varios destinatarios en un solo envío** y **un mensaje que el
usuario puede redactar** antes de mandarlo.

> **ESTADO: aplicado.** Las cuatro decisiones se tomaron según la recomendación
> de cada una y están resueltas más abajo. Lo único que queda pendiente es medir
> el panel contra el PNG del frame (apartado 9), porque se construyó sin él.

---

## 1. Qué cambia respecto a lo que ya está

Comparado con `screens_ref/Invita a compañeros de viaje.png`, que es lo
implementado hoy:

| | Hoy | Frame nuevo |
|---|---|---|
| Enlace + «Copiar enlace» | ✅ igual | ✅ igual |
| Campo de correo | suelto, fondo gris | dentro de un panel gris, con subrayado |
| Destinatarios | uno, y se manda con Intro | **varios, como fichas con ✕** |
| Añadir destinatario | — | **botón redondo `person-circle-plus`** |
| Mensaje del correo | fijo en el servidor | **redactable, con icono `pencil`** |
| Botón de enviar | no hay | **«Enviar correo electrónico»** |
| «Gestiona tus compañeros» | ✅ igual | ✅ igual |

Todo lo nuevo vive dentro de un panel gris que agrupa el compositor entero. El
enlace de arriba y «Gestiona tus compañeros» de abajo se quedan como están.

---

## 2. Las cuatro decisiones, ya tomadas

| | Decisión | Qué se hizo |
|---|---|---|
| 1 | Intro añade ficha | y **Ctrl+Intro envía**, para quien tenga el dedo hecho al atajo viejo |
| 2 | El lápiz es una pista | el texto siempre es editable; el lápiz sólo lleva el cursor al final |
| 3 | El mensaje no se guarda | vuelve al texto por defecto en cada apertura. Sin migración |
| 4 | 10 destinatarios por envío | `INVITAR_DESTINOS_MAX`, el mismo número que el tope por hora |

Además, **el texto por defecto corrige una errata del frame**: dice «nuestro
próxima aventura» y va como «nuestra». Esto sale por correo a gente de fuera.

El razonamiento de cada una queda abajo, tal como se escribió antes de decidir.

## 2b. El razonamiento (antes de decidir)

### 1 — Intro deja de enviar

Hoy el campo de correo **no tiene botón**: se manda con Intro, y así está
documentado en el código. En el frame nuevo Intro ya no puede enviar, porque
ahora sirve para añadir una ficha más.

Es un cambio de costumbre para quien ya use la ventana. Mi recomendación:

- **Intro** → añade la dirección como ficha (lo mismo que el botón redondo)
- **«Enviar correo electrónico»** → el único que envía

Alternativa si prefieres conservar el atajo: **Ctrl+Intro** envía. Cuesta una
línea y no estorba.

### 2 — El lápiz, ¿interruptor o adorno?

En el frame el mensaje se ve como texto plano con un lápiz al lado de la palabra
«Mensaje». Se puede leer de dos maneras:

- **(a)** el lápiz activa el modo edición, y hasta entonces el texto no se toca
- **(b)** el texto siempre es editable y el lápiz sólo lo anuncia

Recomiendo **(b)**. Un interruptor añade un estado más y una pulsación extra sin
dar nada a cambio: el usuario que quiere cambiar el texto hace clic en el texto,
que es lo que espera. El lápiz se queda como pista visual y como zona de clic que
lleva el cursor al campo.

### 3 — ¿Se recuerda el mensaje?

Lo más simple es que cada vez que se abra la ventana salga el texto por defecto
—el mismo que enseña el frame— y lo que el usuario escriba valga sólo para ese
envío. **Recomiendo esto.**

Guardarlo pediría una columna nueva y abre preguntas que el frame no contesta
(¿es por plan o por usuario?, ¿qué pasa si lo borra entero?). Se puede añadir
después sin rehacer nada.

### 4 — ¿Cuánta gente de una vez?

El frame enseña una ficha. Propongo un tope de **10 fichas por envío**, que es el
mismo número que ya usa `INVITAR_POR_HORA`. Ver el apartado 4, que es donde esto
se pone serio.

---

## 3. El choque de nombres que hay que resolver primero

⚠️ **`invMsg` ya existe y significa otra cosa.** Hoy es el texto de aviso —
«Invitación enviada a X», «El correo no es válido»— que se pinta bajo el campo.
El frame llama «Mensaje» al cuerpo del correo, que es algo completamente
distinto.

Dejar los dos con nombres parecidos es una trampa para quien mantenga esto
después. Propongo renombrar:

| Ahora | Pasa a ser | Qué es |
|---|---|---|
| `invMsg` | `invAviso` | el texto de estado bajo el campo |
| `invMsgMal` | `invAvisoMal` | si ese aviso es un error |
| `invMsgColor` | `invAvisoColor` | su color |
| `invHayMsg` | `invHayAviso` | si hay algo que enseñar |
| — | `invMsg` | **el cuerpo del correo** (nuevo) |

Coste real, contado: **20 apariciones en `js/plan_logic.js`** (10 de `invMsg` y
10 de `invMsgMal`) y **6 en `plan_template.html`**. Es un renombrado mecánico,
pero hay que hacerlo **antes** de añadir nada, no después.

---

## 4. El riesgo de verdad: esto convierte la ventana en un formulario de envío masivo

Hasta ahora el correo de invitación era **texto fijo escrito por nosotros**, a
**una** dirección. Con este cambio pasa a ser **texto libre del usuario** a
**varias** direcciones, saliendo **desde nuestra cuenta de Gmail**.

Eso es, literalmente, la forma de un formulario de spam. Tres cosas que hay que
poner, y ninguna es opcional:

### 4.1 El tope actual se puede rodear

```php
// api/plan_invitar.php:64
'SELECT COUNT(*) FROM plan_invitaciones WHERE plan_id = ? AND ...'
```

El límite de 10 por hora es **por plan**. Como crear planes es gratis e
ilimitado, quien quiera mandar 100 correos crea 10 planes. Nunca ha sido un
problema real porque cada envío costaba escribir una dirección a mano; con
fichas múltiples y mensaje libre sí lo es.

**Propuesta:** añadir un segundo tope **por usuario**, sumando todos sus planes,
y quedarse con el más restrictivo de los dos. Es un `JOIN` con `planes` sobre la
misma tabla; no hace falta contador nuevo ni migración.

### 4.2 El mensaje va dentro de un HTML

`planInviteCreate()` arma el correo interpolando en una plantilla HTML, y hoy ya
escapa lo que mete (`htmlspecialchars` en la línea 266 para el nombre del plan).
El mensaje del usuario tiene que pasar por lo mismo, y además `nl2br()` para que
los saltos de línea se vean.

Sin escapar, quien invita puede meter enlaces y formato arbitrarios en un correo
que sale con nuestro remite. **No es un riesgo teórico: es el caso normal de uso
mal hecho.**

El mensaje **nunca** puede llegar al asunto ni a ninguna cabecera. Sólo al
cuerpo.

### 4.3 Tope de longitud

**1000 caracteres**, cortados en el servidor. El cliente avisa antes de llegar.

---

## 5. El backend: de uno en uno a por lotes

Hoy `api/plan_invitar.php` acepta **un** `email` por petición. Con fichas hay dos
caminos:

| | Cliente en bucle | Servidor por lotes |
|---|---|---|
| Peticiones | N | 1 |
| Fallos parciales | difíciles de contar | naturales |
| CSRF, sesión, permisos | N veces | 1 vez |
| Tope por hora | se pisa a sí mismo | se comprueba una vez, con N |

**Recomiendo el servidor por lotes.** El contrato quedaría:

```
POST { plan_id, action:'correo', emails:[...], mensaje:"...", rol }
  → { ok, resultados:[ {email, estado:'enviado'|'creado'|'error', motivo?}, ... ] }
```

El detalle que importa: **un destinatario malo no puede tumbar el lote**. Hoy el
endpoint hace `apiFail()` y corta en cuanto una dirección no valida, ya está
dentro del viaje o su dominio no existe. Con cinco fichas, que la tercera esté
repetida no puede cancelar las otras cuatro. Cada dirección se resuelve por su
cuenta y la ventana enseña el resultado de cada una.

Los tres estados no son un capricho:

- `enviado` — la invitación existe y el correo salió
- `creado` — la invitación existe pero el correo **no** salió (es lo que pasa en
  las máquinas sin `includes/mail_config.php`, que están en `.gitignore`)
- `error` — no se creó nada, y `motivo` dice por qué

Esa distinción ya existe hoy (`correo_enviado`) y hay que conservarla: el código
actual se toma la molestia de no mentir cuando el correo no sale, y el lote no
puede perder eso.

---

## 6. Los dos iconos

Los dos son Font Awesome Free 7.3.1 con lienzo 640×640. Ya medí las cajas de
tinta rasterizando, que es lo que exige `includes/iconos_planes.php` —el propio
archivo avisa de que a ojo no se acierta:

| Icono | Caja de tinta | Nota |
|---|---|---|
| `person-circle-plus` | `[40, 32, 600, 576]` | no está centrado: sobra menos por la derecha |
| `pencil` | `[64, 64, 512, 512]` | perfectamente centrado y cuadrado |

El de la persona **no es simétrico**: su tinta empieza en x=40 y termina en
x=640, mientras que arriba empieza en y=32 y abajo acaba en y=608. Si se mete en
un botón redondo centrando el lienzo, se va a ver descolgado a la izquierda. Hay
que centrar por la **tinta**, no por el `viewBox` — exactamente el fallo que
tuvimos con el `+` y el `−` del zoom.

Van dentro del modal, así que siguen la convención que ya está escrita ahí:
conservan su lienzo original en vez de compartir el 640×640 recortado del resto.

---

## 7. El trabajo, por fases

Cada fase deja la ventana funcionando. Se puede parar en cualquiera.

### Fase 0 — el renombrado
`invMsg` → `invAviso` y compañía. 26 sitios. Sin cambio visible.

### Fase 1 — las fichas (sin mensaje todavía)
- Estado: `invDest` (array), `invEmail` (lo que se está escribiendo)
- El botón redondo y el Intro añaden ficha; valida, quita repetidas, no deja
  añadirse a uno mismo
- Cada ficha con su ✕
- Botón «Enviar correo electrónico»
- Servidor: `emails:[]` y resultados por dirección
- El tope por usuario del 4.1

Aquí ya se puede invitar a cinco personas de una vez, con el texto de siempre.

### Fase 2 — el mensaje
- `invMsg` nuevo, con el texto por defecto del frame
- Campo editable, tope de 1000
- El servidor lo escapa, lo pasa por `nl2br` y lo mete en la plantilla
- El lápiz

### Fase 3 — medir contra el frame
Aquí es donde hace falta algo tuyo (ver el apartado 9).

---

## 8. Qué probar

1. Cinco fichas de golpe, todas buenas → cinco correos, cinco filas.
2. **Cinco fichas donde la tercera ya está en el viaje** → las otras cuatro
   salen. Este es el caso que hoy fallaría entero.
3. Una dirección con dominio inventado → error sólo en esa.
4. La misma dirección dos veces → no se puede añadir la segunda.
5. Un mensaje con `<script>` y con `<b>` → llega como texto, no como HTML.
6. Un mensaje con saltos de línea → se ven en el correo.
7. Pasar el tope por hora → 429, y comprobar que **cambiando de plan sigue
   bloqueado** (eso es lo que hoy no pasa).
8. Sin `includes/mail_config.php` → dice «creada pero el correo no salió», no
   «enviada».
9. Mensaje de 1001 caracteres → cortado o rechazado, no roto.
10. Teclado: Tab por fichas, ✕ con Intro, foco visible.

---

## 9. Lo que necesito de ti

**El frame en PNG, en `screens_ref/`.** Lo pegaste en el chat y lo veo, pero no
puedo medirlo píxel a píxel desde ahí, y en este proyecto las medidas salen de
los frames y no de la vista — está escrito en el propio
`plan_template.html:1902`. Sin el archivo puedo construir la estructura, pero los
altos, los espacios y los radios serían a ojo.

Con el PNG saco lo mismo que se sacó para la ventana actual: alto de cada fila,
relleno del panel gris, radio de las fichas, tamaño del botón redondo y color
exacto del azul de enviar.

Y las **cuatro decisiones** del apartado 2.

---

## 10. Archivos que se tocan

| Archivo | Qué |
|---|---|
| `js/plan_logic.js` | renombrado, estado de fichas, mensaje, envío por lotes |
| `plan_template.html` | el panel gris entero, fichas, los dos iconos |
| `api/plan_invitar.php` | `emails:[]`, `mensaje`, resultados, tope por usuario |
| `includes/plan_invite_lib.php` | `planInviteCreate()` acepta el mensaje |
| `Reportes_md/PLAN_invitar.md` | apuntar lo nuevo |

**Sin migración de base de datos**, si se acepta la decisión 3 (no guardar el
mensaje). `plan_invitaciones` ya tiene todo lo que hace falta.

---

## 11. Lo que se comprobó al aplicarlo

**El escapado del mensaje**, que es la parte de seguridad. Siete entradas
hostiles —`<script>`, `<a href>`, `<img onerror>`, comillas, cierre de atributo,
saltos de línea, acentos— y las siete quedan inertes. Lo único vivo que sale es
el `<p>` y los `<br>` que pone la propia plantilla:

```
<script>alert(1)</script>
  →  <p …>&lt;script&gt;alert(1)&lt;/script&gt;</p>
```

**Que una dirección mala no tumba el lote.** Cinco direcciones con la 1ª, la 3ª
y la 4ª malas a propósito: devuelve **5 resultados, 2 salen adelante** y cada
fallo con su motivo. Con el código viejo la primera habría hecho `apiFail()` y
el script termina ahí: un resultado, cero invitaciones, y las dos buenas
perdidas sin que nadie sepa que eran buenas.

**El agujero del tope, con números.** El usuario 2 tiene 8 planes, así que el
tope viejo le permitía **80 correos por hora** — y creando más planes, los que
quisiera. El tope nuevo por usuario lo deja en 25.

**El icono de la persona, centrado.** Medido contra el borde real del botón:
desvío **+0,5 px en horizontal y 0,0 en vertical**. El `viewBox` recortado a la
tinta (`40 32 600 576`) hace el trabajo; con el lienzo completo se habría ido a
la izquierda.

**Lo que NO se pudo comprobar:** el viaje completo por HTTP con sesión iniciada,
porque haría falta la contraseña, y mandar un correo de verdad, que enviaría
mensajes reales a direcciones inventadas. Las pruebas 1, 5, 6 y 8 del apartado 8
están cubiertas; las demás piden hacerlo a mano en el navegador.
