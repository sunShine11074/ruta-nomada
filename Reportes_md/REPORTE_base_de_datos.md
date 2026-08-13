# La base de datos de Ruta Nómada

Guía de las tablas del sistema: qué guarda cada una, por qué existe y
cómo se relacionan entre sí.

- **Motor:** MariaDB 10.4 (XAMPP) · todas las tablas en **InnoDB**
- **Base:** `ruta_nomada`
- **21 tablas** + 36 rutinas (documentadas aparte en
  `REPORTE_rutinas_bd.md`)
- **Script de creación:** `basedatos/instalar.sql`

InnoDB en todas no es casualidad: es el único motor de MySQL/MariaDB que
respeta **claves foráneas y transacciones**, y este sistema depende de
ambas. Sin claves foráneas, borrar un viaje dejaría sus lugares, gastos y
listas flotando como basura invisible.

---

## 1. Las tablas de un vistazo

Las 21 tablas se agrupan en **seis familias** según para qué sirven:

| Familia | Tablas | Para qué |
|---------|--------|----------|
| Personas | `usuarios`, `password_resets`, `intentos_login` | Quién entra al sistema |
| Catálogo | `destinos`, `favoritos`, `viajes_usuario` | Destinos que ofrece la app |
| El viaje | `planes`, `plan_items`, `plan_gastos` | El corazón del sistema |
| Compartir | `plan_miembros`, `plan_invitaciones`, `plan_item_reacciones`, `plan_item_gasto` | Viajar acompañado |
| Organizar | `plan_listas`, `plan_lista_items`, `plan_destinos`, `usuario_fotos` | Notas, pendientes y fotos propias |
| Servicio | `tramo_cache`, `ruta_uso`, `ai_uso`, `planes_borrados` | Ahorro, control y auditoría |

Con el contenido actual de la base:

| Tabla | Filas | Familia |
|-------|-------|---------|
| `usuarios` | 4 | Personas |
| `password_resets` | 8 | Personas |
| `destinos` | 4 | Catálogo |
| `favoritos` | 0 | Catálogo |
| `viajes_usuario` | 0 | Catálogo |
| `planes` | 6 | El viaje |
| `plan_items` | 36 | El viaje |
| `plan_gastos` | 4 | El viaje |
| `plan_miembros` | 7 | Compartir |
| `plan_invitaciones` | 0 | Compartir |
| `plan_item_reacciones` | 4 | Compartir |
| `plan_item_gasto` | 2 | Compartir |
| `plan_listas` | 11 | Organizar |
| `plan_lista_items` | 91 | Organizar |
| `plan_destinos` | 0 | Organizar |
| `tramo_cache` | 74 | Servicio |
| `ruta_uso` | 0 | Servicio |
| `ai_uso` | 18 | Servicio |
| `planes_borrados` | 1 | Servicio |

---

## 2. Cómo se relacionan

Casi todo el sistema cuelga de dos tablas: **`usuarios`** y **`planes`**.

```
usuarios ──┬─→ planes ──┬─→ plan_items ──┬─→ plan_item_gasto
           │            │                └─→ plan_item_reacciones
           │            ├─→ plan_gastos
           │            ├─→ plan_listas ──→ plan_lista_items
           │            ├─→ plan_miembros ←── usuarios
           │            ├─→ plan_invitaciones
           │            └─→ plan_destinos ──→ destinos
           ├─→ password_resets
           ├─→ favoritos ──→ destinos
           └─→ viajes_usuario ──→ destinos
```

Hay **22 claves foráneas** declaradas. La regla que siguen casi todas es
`ON DELETE CASCADE`: al borrar un plan, desaparecen con él sus lugares,
gastos, listas, miembros e invitaciones. Es lo correcto — un gasto sin
viaje al que pertenecer no significa nada.

Tres relaciones se salen de esa regla **a propósito**:

| Relación | Regla | Por qué |
|----------|-------|---------|
| `ai_uso` → `planes` | `SET NULL` | El contador de consumo de la IA no debe borrarse al borrar un viaje: sirve para controlar el gasto del usuario a lo largo del tiempo. |
| `viajes_usuario` → `planes` | `SET NULL`* | Es el historial de destinos visitados por el usuario. Al borrar un plan se rompe el vínculo, pero el recuerdo del destino se conserva. |
| `planes_borrados` | *(sin clave foránea)* | Es un archivo de auditoría: su fila **debe sobrevivir** al plan que registra. Una clave foránea la borraría en cascada, justo lo contrario de lo que se busca. |

\* Ver el apartado 9: en esta computadora esa regla todavía está en
`RESTRICT`.

---

## 3. Personas

### `usuarios`

**Qué guarda.** La cuenta de cada persona registrada: nombre, apellidos,
correo, contraseña, y su perfil (teléfono, fecha de nacimiento,
nacionalidad, país, estado, ciudad, idioma, divisa, foto de perfil y
banner).

**Detalles importantes:**

- `email` tiene **índice único**. Es la garantía real contra cuentas
  duplicadas: aunque dos personas se registren en el mismo instante, la
  base sólo acepta una.
- `password_hash` guarda un **hash bcrypt**, nunca la contraseña. El
  cifrado se hace en PHP y la contraseña en texto plano jamás llega a
  MySQL.
- `rol` (`viajero` / `admin`) separa a los usuarios normales del panel de
  administración de destinos.

**Por qué existe.** Es la raíz del sistema: sin usuario no hay viajes, ni
favoritos, ni nada.

### `password_resets`

**Qué guarda.** Los tokens de «olvidé mi contraseña»: a quién pertenecen,
cuándo caducan y si ya se usaron.

**El detalle que importa:** la columna se llama `token_hash`, no `token`.
El enlace que se envía por correo lleva el token en claro, pero en la base
se guarda **sólo su huella SHA-256**. Si alguien robara la tabla, no
podría reconstruir ningún enlace válido. Las columnas `expira_en` y
`usado` cierran las otras dos vías: un token caducado o ya utilizado no
sirve.

**Por qué existe.** Permite recuperar una cuenta sin que nadie —ni los
propios desarrolladores— pueda suplantar a un usuario.

---

## 4. Catálogo de destinos

### `destinos`

**Qué guarda.** Los destinos que la aplicación ofrece en su portada:
nombre, descripción, ciudad, país, categoría (`Cultura`, `Romance`,
`Aventura`, `Descubrimiento`), precio desde, valoración e imagen.

**Por qué existe.** Es contenido editorial, curado por el administrador
desde `admin_prin.php`. No lo crean los usuarios. Alimenta `inicio.php`,
que agrupa los destinos por categoría.

### `favoritos`

**Qué guarda.** Qué destinos marcó cada usuario. Sólo dos columnas —
`usuario_id` y `destino_id` — porque no hace falta nada más.

**Por qué existe.** Es una **tabla puente** clásica: un usuario puede
tener muchos destinos favoritos y un destino puede gustarle a muchos
usuarios. Una relación de muchos a muchos no se puede representar sin una
tabla intermedia.

### `viajes_usuario`

**Qué guarda.** El historial de destinos que un usuario consultó o
guardó, con la fecha y, opcionalmente, el plan que creó a partir de ahí.

**Por qué existe.** Conecta la parte de *inspiración* (mirar destinos) con
la de *planificación* (crear un viaje). Es la única tabla que apunta a la
vez a `usuarios`, `destinos` y `planes`.

---

## 5. El viaje: el corazón del sistema

### `planes`

**Qué guarda.** Cada viaje que un usuario planifica.

| Columna | Tipo | Para qué |
|---------|------|----------|
| `usuario_id` | `int` | Quién lo creó |
| `nombre` | `varchar(200)` | «Nuestro viaje a Ensenada» |
| `destino` | `varchar(120)` | Ciudad de destino |
| `lat` · `lng` | `decimal(10,7)` | Coordenadas, para el mapa |
| `fecha_inicio` · `fecha_fin` | `date` | Cuándo se viaja |
| `privacidad` | `enum` | `solo` · `amigos` · `publico` |
| `presupuesto` | `decimal(12,2)` | Cuánto se piensa gastar |
| `portada_url` | `varchar(500)` | Imagen de la tarjeta |
| `estado` | `enum` | `borrador` · `activo` · `completado` |
| `creado_en` · `updated_at` | fechas | Cuándo se creó y se tocó por última vez |
| `dia_subtitulos` | `text` | Títulos que el usuario pone a cada día (en JSON) |

**Dos decisiones de diseño:**

- **`lat` y `lng` como `decimal(10,7)`, no `float`.** Siete decimales dan
  precisión de ~1 cm, y `decimal` no arrastra los errores de redondeo del
  punto flotante. Una coordenada mal redondeada mueve un pin de sitio.
- **`updated_at` la mantiene un disparador.** Tres disparadores sobre
  `plan_items` la actualizan cuando el itinerario cambia (ver
  `REPORTE_rutinas_bd.md`). Esa columna alimenta el «Última
  modificación» y el orden «Más nuevos» de `mis_planes.php`.

**Por qué existe.** Es la entidad principal del sistema. Diez de las
diecinueve tablas apuntan directa o indirectamente a ella.

### `plan_items`

**Qué guarda.** Cada lugar del itinerario: el museo del día 2, el
restaurante del día 3, el hotel.

Es la tabla con **más columnas** del sistema, y se entiende mejor en
cuatro bloques:

| Bloque | Columnas | Para qué |
|--------|----------|----------|
| Posición | `dia`, `orden` | En qué día va y en qué lugar de la lista |
| Identidad | `nombre`, `categoria`, `nota`, `imagen_url` | Qué es |
| Mapa | `place_id`, `lat`, `lng`, `modo_viaje` | Dónde está y cómo se llega al siguiente |
| Dinero | `precio`, `moneda`, `gasto_cat`, `gasto_desc`, `gasto_modo` | Cuánto cuesta y cómo se reparte |

**Detalles importantes:**

- **`dia = 0` significa «sin asignar»** — un lugar guardado que aún no se
  ha metido en ningún día concreto. Está documentado como comentario en
  la propia columna.
- **`orden`** permite reordenar los lugares dentro de un día arrastrando,
  sin que el `id` importe.
- **`categoria`** (`hacer`, `rest`, `hotel`, `custom`) decide el color e
  icono del pin en el mapa.
- **`place_id`** es el identificador de Google Places. Se guarda para
  poder volver a pedir los datos del lugar sin repetir la búsqueda.

**Por qué existe.** Es el itinerario propiamente dicho. Un plan sin
`plan_items` es un viaje sin plan.

### `plan_gastos`

**Qué guarda.** Los gastos del viaje que no están atados a un lugar
concreto: el vuelo, el seguro, la gasolina. Cada uno con concepto, monto,
categoría y fecha.

**Por qué existe separada de `plan_items`.** Son dos cosas distintas: un
`plan_item` es un **sitio** que además puede costar dinero; un
`plan_gasto` es **sólo** dinero. Mezclarlas obligaría a llenar de `NULL`
la mitad de las columnas en cada fila.

Un **disparador** (`trg_gasto_valido`) rechaza importes negativos, para
que ningún error de programación pueda corromper el total del viaje.

---

## 6. Compartir el viaje

### `plan_miembros`

**Qué guarda.** Quién participa en cada plan y con qué permiso:
`propietario`, `editor` o `lector`.

**Detalles importantes:**

- La pareja (`plan_id`, `usuario_id`) es **única**: nadie puede estar dos
  veces en el mismo viaje.
- El `rol` es la base de toda la seguridad del sistema. Un `lector` ve el
  viaje pero no lo edita; un `editor` puede modificarlo; sólo el
  `propietario` puede borrarlo.
- La fila del propietario se crea **en la misma transacción** que el plan
  (procedimiento `sp_crear_plan`), de modo que **no puede existir un plan
  sin dueño**.

**Por qué existe.** Es la segunda tabla puente del sistema, y la que
convierte Ruta Nómada de una app individual en una colaborativa.

### `plan_invitaciones`

**Qué guarda.** Las invitaciones enviadas por correo para unirse a un
viaje: a qué correo, con qué rol, si ya se usó y cuándo caduca.

Igual que en `password_resets`, se guarda **`token_hash`** y no el token.
Mismo razonamiento: el enlace viaja por correo, la base sólo conserva su
huella.

### `plan_item_reacciones`

**Qué guarda.** Los emojis con los que los compañeros reaccionan a un
lugar del itinerario: quién, a qué lugar, qué emoji.

**Por qué existe.** Es la forma ligera de opinar sin abrir una discusión:
«👍 a este restaurante». Sin ella, decidir en grupo exigiría hablarlo
fuera de la aplicación.

### `plan_item_gasto`

**Qué guarda.** El reparto del coste de un lugar entre los compañeros:
cuánto le toca a cada uno, y el color que lo identifica en la gráfica de
distribución.

**Por qué existe.** La columna `gasto_modo` de `plan_items` dice *cómo* se
divide un gasto (no dividir, entre todos, o entre algunos); esta tabla
guarda el resultado *concreto* cuando es «entre algunos». Es una relación
de muchos a muchos entre lugares y usuarios, con un dato extra —el
importe— en medio.

---

## 7. Organizar

### `plan_listas` y `plan_lista_items`

**Qué guardan.** Las notas y listas de pendientes del viaje. La primera
guarda la lista (título, tipo `nota` o `check`, orden); la segunda, cada
punto de una lista de verificación, con su texto, si está hecho y su
orden.

**Por qué son dos tablas.** Una lista de «qué llevar en la maleta» tiene
muchos puntos, y cada punto necesita su propio estado (marcado o no) y su
propia posición. Meterlo todo en una columna de texto haría imposible
marcar un solo elemento sin reescribir la lista entera.

Con 11 listas y 91 puntos, es la relación con más filas del sistema.

### `plan_destinos`

**Qué guarda.** Qué destinos del catálogo se asociaron a un plan.

**Por qué existe.** Es la tercera tabla puente, entre `planes` y
`destinos`. Está vacía porque hoy los planes se crean escribiendo el
destino a mano o eligiéndolo en Google Maps, sin pasar por el catálogo
editorial. Se conserva porque el enlace entre ambos mundos está previsto.

---

## 8. Tablas de servicio

Estas cuatro no guardan contenido del usuario: sostienen el
funcionamiento del sistema.

### `tramo_cache` — ahorrar dinero

**Qué guarda.** El resultado de cada ruta ya calculada por la API de
Google: la geometría del trazado (`pts`), los metros, los segundos y si
la petición salió bien.

**Cómo funciona.** La clave primaria es `hash`, un `char(32)` que resume
el tramo (origen, destino y modo de viaje). Antes de preguntarle a
Google, el sistema busca ese hash: si ya está, usa el resultado guardado.

**Por qué existe.** La API de rutas de Google **se cobra por petición**.
Sin esta tabla, abrir el mismo viaje diez veces costaría diez veces. Con
74 tramos guardados, son 74 llamadas que ya no se pagan cada vez que
alguien abre su itinerario.

### `ruta_uso` — no llevarse una sorpresa

**Qué guarda.** Cuántas rutas se pidieron cada mes. Sólo dos columnas:
`mes` (`AAAA-MM`) y `n`.

**Por qué existe.** Es el contador de consumo. Permite ver si el gasto en
la API se está disparando **antes** de que llegue la factura.

### `ai_uso` — controlar al asistente

**Qué guarda.** Cada consulta al asistente de inteligencia artificial:
qué usuario, en qué plan, cuántos tokens de entrada y salida, y con qué
modelo.

**Por qué existe.** Doble función: llevar la cuenta del gasto y **limitar
el uso por usuario**, para que nadie —ni por error ni a propósito— agote
la cuota del proyecto. Su clave foránea a `planes` es `SET NULL`: si se
borra un viaje, el registro de consumo se conserva.

### `planes_borrados` — auditoría

**Qué guarda.** Constancia de cada plan eliminado: qué id tenía, su
nombre, su destino, de quién era y cuándo se borró.

**Cómo se llena.** Sola. Un disparador `BEFORE DELETE` sobre `planes`
copia los datos justo antes de que la fila desaparezca.

**Por qué existe.** Borrar un viaje es **irreversible** y no hay
papelera — por eso la interfaz obliga a mantener pulsado el botón cinco
segundos. Esta tabla es la última red: si alguien reclama que su viaje
desapareció, queda constancia de qué se borró y cuándo.

---

## 9. Estado actual: dos observaciones honestas

El análisis de la base encontró dos diferencias entre lo que dicen los
archivos del proyecto y lo que hay instalado en esta computadora. Ninguna
está causando fallos hoy, pero conviene dejarlas escritas.

### 9.1 Una clave foránea quedó atrás

`basedatos/instalar.sql` declara:

```sql
CONSTRAINT `viajes_usuario_ibfk_3` FOREIGN KEY (`plan_id`)
    REFERENCES `planes` (`id`) ON DELETE SET NULL
```

pero en esta base la regla sigue siendo **`RESTRICT`**. Falta ejecutar la
migración `basedatos/migrate_borrar_plan.sql`, que es idempotente:

```
mysql -u root ruta_nomada -e "source basedatos/migrate_borrar_plan.sql"
```

**Consecuencia práctica: ninguna hoy.** `viajes_usuario` está vacía, y
además el procedimiento `sp_borrar_plan` desvincula esas filas
(`SET plan_id = NULL`) antes de borrar el plan. Es decir, la protección
existe por partida doble y sólo falta una de las dos capas. Pero una
instalación nueva desde `instalar.sql` **sí** tendrá `SET NULL`, así que
esta computadora y la de un compañero recién clonado tendrían esquemas
ligeramente distintos.

### 9.2 Dos cotejamientos conviviendo

Doce tablas usan `utf8mb4_unicode_ci` y siete —`usuarios`, `planes`,
`destinos`, `favoritos`, `password_resets`, `plan_destinos` y
`viajes_usuario`— siguen en `utf8mb4_general_ci`, el que MySQL ponía por
omisión cuando se creó la primera versión del proyecto.

Los dos guardan acentos y emojis igual de bien; se diferencian en **cómo
comparan y ordenan** el texto. `unicode_ci` sigue las reglas del estándar
Unicode y ordena mejor palabras con acentos; `general_ci` es más antiguo y
más rápido, pero menos correcto.

**Consecuencia práctica hoy:** ninguna visible, porque las consultas que
comparan texto entre tablas de distinto cotejamiento son las que darían
problemas, y el sistema no hace ninguna. Unificarlo es recomendable
cuando haya tiempo, no urgente.

---

## 9 bis. Lo que se añadió después de este reporte

**Dos tablas:**

| Tabla | Para qué |
|---|---|
| `intentos_login` | El freno a la fuerza bruta del inicio de sesión. Guarda cada intento con su correo, su IP y si acertó |
| `usuario_fotos` | La galería de «Tus fotos» de la ventana Cambiar foto. Guarda la **ruta** relativa, no la imagen: el archivo vive en `img/portadas/` |

**Y seis columnas para la colaboración en tiempo real:**

| Columna | Para qué |
|---|---|
| `planes.rev` | Testigo de cambio del viaje: sube con cualquier cambio y lo mueven 20 disparadores |
| `planes.ver` | Versión del **nombre** del viaje, para el bloqueo optimista |
| `plan_items.ver` | Versión de un lugar, para el bloqueo optimista |
| `plan_miembros.visto_en` | Último latido de esa persona: el punto verde de los avatares |
| `plan_invitaciones.usos` / `usos_max` | Para que el enlace de invitación sirva a varias personas |
| `plan_invitaciones.token_claro` | El token legible del enlace de compartir, para poder volver a enseñarlo |

El detalle está en `REPORTE_colaboracion.md`.

---

## 10. Resumen para la evaluación

**21 tablas, 23 claves foráneas, todas en InnoDB.** El diseño sigue las
formas normales: no hay datos repetidos entre tablas, cada dato vive en
un solo sitio, y las relaciones de muchos a muchos se resuelven con
tablas puente (`favoritos`, `plan_miembros`, `plan_destinos`,
`plan_item_gasto`).

Tres decisiones que vale la pena señalar:

1. **Ninguna contraseña ni token se guarda en claro.** `usuarios` guarda
   hash bcrypt; `password_resets` y `plan_invitaciones` guardan SHA-256
   del token, nunca el token.
2. **El borrado en cascada está pensado caso por caso**, no aplicado a
   ciegas: la mayoría de relaciones borran en cascada, pero el historial
   del usuario y el contador de la IA se conservan con `SET NULL`, y la
   tabla de auditoría no tiene clave foránea precisamente para
   sobrevivir.
3. **Cuatro tablas no guardan contenido sino control**: caché para no
   pagar dos veces por la misma ruta, dos contadores de consumo y un
   archivo de auditoría del borrado.

La base se instala completa con un solo archivo —`basedatos/instalar.sql`,
que incluye tablas, datos de ejemplo y las 14 rutinas— y su estado se
puede verificar en cualquier momento con
`herramientas/diagnostico.php`.

---

*Reporte generado a partir del estado real de la base `ruta_nomada`,
consultando `information_schema`. Las rutinas —funciones, procedimientos
y disparadores— se documentan en `REPORTE_rutinas_bd.md`.*
