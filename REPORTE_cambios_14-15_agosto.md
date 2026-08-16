# Cambios del 14 y 15 de agosto de 2026

Seis commits, del `728befc` al `e79c00d`. Tres días de arreglos al asistente de
IA y tres mejoras al planificador.

**Lee primero la sección «Lo que tienes que hacer».** Hay una migración de base
de datos que puede fallarte, y el asistente no te va a funcionar hasta que
pongas la clave nueva.

---

## Lo que tienes que hacer

### 1. Traerte el código

```bash
git pull
```

### 2. Actualizar tu base de datos ⚠️ obligatorio

El commit `155f9cb` añade una restricción a la tabla `planes`. Sin esto tu copia
acepta viajes de 40 días y la de los demás no, y acabaríamos con datos que en
unas máquinas se guardan y en otras no.

```bash
/c/xampp/mysql/bin/mysql.exe -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"
```

En PowerShell:

```bash
& "C:\xampp\mysql\bin\mysql.exe" -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"
```

Al final tiene que salir `tope_de_30_dias (debe ser 1)` con un **1**. Si sale 0,
la restricción no se puso.

> **Si eres nuevo y aún no tienes la base**, no uses `actualizar_bd.sql`: usa
> `basedatos/instalar.sql`, que ya la trae incluida.

#### Si la migración te da error 4025

```
ERROR 4025 (23000): CONSTRAINT `chk_planes_dias` failed for `ruta_nomada`.`planes`
```

No es un fallo del script. Significa que **ya tienes guardado un plan de más de
30 días** y MariaDB se niega a poner una regla que tus propios datos incumplen.
Lo probé a propósito para poder avisarte.

Busca cuál es:

```sql
SELECT id, nombre, fecha_inicio, fecha_fin,
       DATEDIFF(fecha_fin, fecha_inicio) + 1 AS dias
FROM planes
WHERE fecha_inicio IS NOT NULL AND fecha_fin IS NOT NULL
  AND DATEDIFF(fecha_fin, fecha_inicio) NOT BETWEEN 0 AND 29;
```

Acorta o borra ese plan y vuelve a correr la migración. En la base real no había
ninguno —el viaje más largo eran 6 días—, así que lo más probable es que a ti
tampoco te pase.

### 3. Poner la clave nueva de Gemini

**El asistente de IA no te va a responder hasta que hagas esto.** La clave que
teníamos la revocó Google (ver el 14 de agosto, más abajo).

Pídele la clave nueva a Ernesto **por privado**: WhatsApp, mensaje directo, en
persona. Nunca por el repositorio, ni en un `.zip`, ni en un documento
compartido. Después ábrela en `includes/ai_config.php` y sustituye la vieja.

Ese archivo está en `.gitignore`, así que es tuyo y no viaja en los commits: por
eso `git pull` no te la trae y tienes que ponerla a mano.

### 4. Comprobar que todo quedó bien

Abre `herramientas/diagnostico.php` en el navegador (pide sesión iniciada). Lo
que importa es que salga **0 avisos · 0 fallos**; el total de comprobaciones
rondará las 29. Si sale algún fallo, el propio diagnóstico te dice cuál es y
cómo se arregla.

> El diagnóstico **no** comprueba la restricción nueva de los 30 días. Para eso
> está el `tope_de_30_dias` del paso 2.

---

## 14 de agosto — el asistente de IA estaba muerto

### El resumen

El asistente llevaba días sin funcionar **en todas las páginas**, y no nos
habíamos enterado porque la herramienta cuya función es avisarnos decía
«0 fallos». Tres commits para arreglar las tres cosas que fallaban a la vez.

### `728befc` — el diagnóstico mentía

Hasta ese día la única comprobación de Gemini era que el archivo
`includes/ai_config.php` existiera y trajera una clave de algún tamaño.

**Que un archivo exista no es que la clave sirva**, y esa diferencia nos costó
días. El diagnóstico firmaba «28 correctos · 0 avisos · 0 fallos» con el
asistente completamente roto.

La causa real, confirmada llamando a la API de verdad:

```
HTTP 403 · PERMISSION_DENIED
Your API key was reported as leaked. Please use another API key.
```

Google la revocó al encontrarla publicada. **Viajaba dentro de `RUTA
NOMADA.zip`**, que llegó a estar en el repositorio público.

> ⚠️ **La lección, que nos afecta a los cuatro:** el `.gitignore` protege
> `includes/ai_config.php` cuando está suelto, pero **no mira dentro de un
> comprimido**. Nunca compartas el proyecto como `.zip`.

Ahora el diagnóstico hace una llamada real a Gemini, con `maxOutputTokens=1`
para que salga lo más barata posible —lo que se prueba es la clave, no lo bien
que escribe el modelo—, y distingue seis casos en vez de uno:

| Respuesta | Qué significa |
|---|---|
| `200` | funciona, y dice con qué modelo |
| `403` con «leaked» | revocada por haberse publicado |
| `403` otro | la API no está habilitada, o hay una restricción que no encaja |
| `400` | clave mal copiada (el salto de línea al final) |
| `404` | **el modelo ya no existe** — Google los retira cada pocos meses |
| `429` | la clave sirve, es la cuota |

Ese `404` importa más de lo que parece: sin él buscaríamos el fallo en la clave,
que estaría perfecta.

### `2f32d66` — parámetros deprecados y errores legibles

Salen `temperature` y `topP` del `generationConfig`: Google los marcó como
deprecados para los Gemini recientes. No rompían nada, pero dejarlos escritos
daba la falsa impresión de controlar algo que el modelo ya ignora.

En su lugar entra `thinkingConfig.thinkingLevel = minimal`, porque esto es un
panel de chat que debe contestar rápido y los tokens de razonamiento gastan
cuota aunque no se vean.

**Ojo con esto si algún día lo tocas:** el nombre del campo no se copió de la
documentación, se midió contra la API. La página oficial del pensamiento enseña
`"thinking_level"`, pero eso es de otra API distinta:

```
thinkingConfig.thinkingLevel = minimal  ->  HTTP 200
thinking_level               = minimal  ->  HTTP 400  Unknown name "thinking_level"
```

Seguir el ejemplo oficial habría devuelto 400 en **cada** mensaje del asistente.
Las dos salidas están apuntadas en el comentario del código para que nadie lo
«corrija» según el manual.

El registro de errores ahora traduce cada código a su causa y su arreglo, en vez
de escribir «[ai] HTTP 404» y dejarte buscando.

### `71d45a6` — el asistente hablaba del destino viejo

Un fallo real que se veía así: creabas un viaje a Ensenada, preguntabas por
Ensenada, le cambiabas el destino a Cartagena… y el asistente seguía
recomendándote La Bufadora.

El historial de conversación iba separado por plan, así que no se colaba de un
viaje a otro. Lo que no se contemplaba es que cambiara el destino **del mismo
plan**: el contexto decía Cartagena y la conversación seguía llena de Ensenada,
y el modelo le hace caso a la conversación. Llegaba a contradecirse solo — «tu
viaje registrado es a Cartagena, pero preguntas por Ensenada».

Ahora se guarda una huella de `nombre + destino` junto al historial. Si cambia,
el historial se tira.

La huella es **sólo** nombre y destino, a propósito: añadir un lugar o mover un
día tiene que dejar la conversación intacta, que es justo para lo que sirve.

En el mismo commit, el asistente global: la consulta lleva `LIMIT 8`, así que a
quien tuviera veinte viajes le contestaba «tienes 8». Contaba lo que veía. Ahora
manda el total y avisa de que la lista está recortada.

**Cada asistente sabe lo suyo:** el del plan conoce sólo el itinerario que tiene
delante; el global conoce al usuario y su cuenta.

---

## 15 de agosto — el planificador

### `6d53bb0` — los hovers del mapa por fin se ven

En `plan_template.html` hay 146 `style-hover` y 24 `style-focus`. **Ninguno tenía
efecto.** Hubo que descartar dos explicaciones equivocadas antes de dar con la
causa, y las dejo escritas porque son fáciles de repetir:

1. *«El runtime no conoce `style-hover`»* — **falso**. `libs/dc/support.js` sí lo
   implementa. Buscar la cadena literal `style-hover` daba cero porque el prefijo
   se construye en el código, no se escribe entero.
2. *«El efecto es demasiado sutil»* — **falso también**.

La causa real es la cascada de CSS. Esos botones llevan el fondo en el atributo
`style`, **en línea**, y lo que va en línea le gana a cualquier selector. La regla
se generaba, se insertaba en la hoja, y perdía siempre:

| | resultado |
|---|---|
| en línea + regla de clase | `rgba(255,255,255,0.6)` — gana el de la línea |
| en línea + regla con `!important` | `rgba(255,255,255,0.92)` — gana la regla |
| sin en línea + regla de clase | `rgba(255,255,255,0.92)` — el caso de `mis_planes` |

`mis_planes.php` nunca tuvo el problema porque allí el estilo viene de la hoja.

El arreglo son 206 `!important`. De paso se encienden 18 `outline` y 16
`outline-offset`, que son los anillos de foco del teclado: tampoco funcionaban.

**El zoom** — el `+` y el `−` eran caracteres de texto y se veían cuadrados.
Ahora son iconos de Font Awesome 7, con las cajas medidas rasterizando. El
problema de verdad no eran las esquinas: los caracteres iban descentrados en
vertical y el guion era mucho más corto que el brazo del `+`. La equis del panel
de Acerca de / Reseñas / Fotos pasa también a icono.

**El cristal** — «Frost» del efecto Glass de Figma **no es un porcentaje**: es un
radio de desenfoque. El diseño pedía Frost 1 —un píxel— y estábamos aplicando 6 y
8. Y «Depth: 12» no es un borde de 1 px, sino cuánto se mete el canto curvo hacia
adentro.

### `155f9cb` — un viaje dura de 1 a 30 días

**Antes no había regla en ninguna parte, y el cliente truncaba en silencio.** Un
viaje de 40 días se guardaba entero en la base y la pantalla dibujaba 30. Los
días 31 al 40 existían para la base y no para la persona: sin aviso, sin error, y
cualquier lugar puesto ahí habría quedado invisible.

La regla vive en un solo sitio, `includes/plan_auth.php`, y se aplica en cuatro
capas:

| Capa | Dónde |
|---|---|
| Al crear | `api/plan_create.php` — después de ordenar las fechas |
| Al editar | `api/plan_update.php` — valida el par resultante, no lo que llega |
| **La base** | `chk_planes_dias` — la única que no se puede saltar |
| El calendario | `js/plan_nuevo.js` — avisa mientras eliges |

La capa de la base es la importante: cubre los dos endpoints, phpMyAdmin y
cualquier consulta a mano.

La de `plan_update.php` tapa un agujero concreto: la pantalla manda las dos
fechas por separado, así que se podía tocar sólo la de fin y convertir un viaje
de tres días en uno de cincuenta sin que la petición mencionara la de inicio.

Un plan **sin fechas sigue siendo válido** — se crean así y se rellenan después.

### `e79c00d` — un color por día y la sombra del mapa

Antes eran siete colores en ciclo, así que el día 8 repetía el del 1 y el color
dejaba de identificar el día. Ahora son 30, uno por día, elegidos por diseño y
comprobados:

- Contra el número blanco del pin **pasan los 30** el mínimo de 3.1:1. El más
  justo da 3.13:1 y el más holgado 11.51:1.
- Entre días consecutivos la distancia perceptiva más corta es 20.0. Por debajo
  de 10 dos colores se confunden de un vistazo, así que hay margen de sobra.
- Ni grises ni negros.

Queda **una pareja corta**: los días 1 y 9 son los dos rosas de la lista y están
a distancia 8.6. De las 435 parejas es la única por debajo de 11.7. Está
pendiente de sustituto.

> Si vas a cambiar un color, **mídelo antes**. El sitio se acabó: el techo
> teórico para 30 colores vivos que pasen 3.1:1 contra blanco es 15.8, y la
> lista ya está en 11.7. Cualquier color nuevo cae encima de alguno de los
> otros 29.

Y el mapa de `plan.php` lleva ahora una sombra interior en el borde izquierdo,
que marca el corte con el panel del itinerario.

---

## Qué probar cuando termines

1. **El asistente** — ábrelo desde la barra superior y pregúntale cuántos planes
   tienes. Tiene que acertar el número.
2. **El asistente de un plan** — entra a un viaje, pregúntale por el destino, y
   comprueba que habla de tu destino y no de otro.
3. **El tope de 30 días** — intenta crear un viaje de 40 días. Te lo tiene que
   impedir el calendario, y si te lo saltas, el servidor.
4. **Los botones del mapa** — pasa el cursor por encima de los 6 botones de
   `plan.php`. Se tienen que aclarar.
5. **Los colores de los días** — crea un viaje de 10 días o más y mira los pines
   del mapa: cada día con su color, ninguno repetido.

## Lo que sigue pendiente

- Sustituir el color del día 9 (`#FF4082`) por uno que no se confunda con el
  día 1. El candidato medido es `#E46878`.
- Rotar las otras claves que también estaban en el `.zip`: Gmail,
  CountryStateCity, Google Maps y Pexels.
- **Copia de seguridad de la base de datos.** Sigue siendo lo más urgente que
  tenemos sin hacer: si alguien pierde la suya, no hay de dónde recuperarla.
- Ponerle contraseña a MySQL y a phpMyAdmin, coordinado entre las cuatro
  máquinas.

---

*Generado el 15 de agosto de 2026. Commits `728befc`..`e79c00d`.*
