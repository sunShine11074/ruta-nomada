# Colaboración en tiempo real en Ruta Nómada

Dos o más personas editan el mismo viaje a la vez. Los cambios de una
aparecen en la pantalla de la otra en segundos, **sin recargar**, y sin
llevarse por delante lo que esa otra persona está escribiendo.

- **Fechas:** 12–13 de agosto de 2026
- **Plan que lo guió:** `PLAN_colaboracion.md` (7 fases)
- **Estado:** las 7 fases hechas y verificadas

---

## 1. El problema, en una frase

> **«Nadie pierde trabajo sin enterarse.»**

Se descompone en tres capacidades independientes, y cada una es una pieza
distinta del sistema:

| Capacidad | Pieza |
|---|---|
| **Detectar** que algo cambió | `planes.rev` + el latido |
| **Traerlo** sin pisar lo que estás escribiendo | La fusión selectiva |
| **Avisar** cuando dos personas tocan lo mismo | El bloqueo optimista y el 409 |

---

## 2. Por qué sondeo y no WebSockets

No es una elección de comodidad: **no hay alternativa aquí**.

XAMPP sirve PHP a través de Apache, y Apache atiende cada petición con un
proceso que muere al terminar. **No existe ningún proceso persistente donde
alojar una conexión abierta**, así que un servidor de WebSockets está
descartado. SSE sí es técnicamente posible, pero ocupa un *worker* de Apache
por persona conectada y XAMPP se queda sin *workers* con muy poca gente.

Queda el sondeo. Y sondear sólo es viable si preguntar cuesta casi nada — de
ahí todo lo que sigue.

---

## 3. `planes.rev`, el testigo de cambio

Una columna `BIGINT` que sube cada vez que cambia **cualquier cosa** del viaje:
sus lugares, sus gastos, sus listas, sus reacciones, el reparto de un coste o
quién es miembro. La mueven **20 disparadores**; ningún PHP la escribe.

**Por qué un contador y no `updated_at`:** `timestamp` tiene resolución de **un
segundo**. Dos ediciones dentro del mismo segundo dan la misma marca y son
indistinguibles: quien sondea se pierde la segunda. Un entero que sube de uno
en uno se compara con `!==` y no tiene ambigüedad.

> **`rev` es un testigo de cambio, no una cuenta de acciones.** Guardar el
> reparto de un gasto entre cuatro personas borra cuatro filas e inserta
> cuatro, así que sube ocho de golpe. Da igual: lo único que se le pide es que
> **cambie cuando algo cambió** y que **no cambie cuando no cambió nada**.

---

## 4. El latido

`api/plan_pulso.php` responde `{ok, rev, aqui}` en **19 bytes**, desde una
consulta por clave primaria. El navegador pregunta cada **5 segundos**.

Lo que hace que no moleste:

- **Se para con la pestaña oculta.** Medido: 14 segundos en segundo plano,
  **cero peticiones** en el registro de Apache.
- **Retrocede a 15 s** tras doce pulsos sin novedad, y **vuelve a 5 s** con
  cualquier tecla o clic: si alguien está trabajando, es cuando más importa
  enterarse de los demás.
- **Se apaga solo** tras tres fallos seguidos, con un aviso. Insistir contra un
  servidor que no responde no arregla nada.

### ⚠️ `session_write_close()` antes de tocar la base

PHP bloquea el fichero de sesión desde `session_start()` hasta que acaba el
script. Con un sondeo cada 5 segundos, ese bloqueo **serializa todas las demás
peticiones de la misma persona**: mientras el pulso corre, guardar un lugar o
buscar una foto esperan en la puerta. La aplicación se arrastra **sin que
aparezca ningún error en ningún sitio**.

Por eso el pulso lee de la sesión sólo el id de quien pregunta, la suelta, y
mete la comprobación de acceso **dentro** de la misma consulta que trae el
número: el `JOIN` con `plan_miembros` hace de guardián.

### ⚠️ Quien edita se avisaba a sí mismo

Los disparadores suben `rev` con cualquier cambio, **incluidos los tuyos**. Sin
más, en cuanto alguien añadía un lugar su propio pulso veía el número distinto
y le anunciaba «hay novedades» por algo que acababa de hacer él.

Se cierra en el servidor: **`apiJson()` adjunta el `rev` resultante a toda
respuesta correcta**, y el cliente lo adopta en el mismo momento de escribir.
Como viaja en la misma respuesta, **no queda ninguna ventana de carrera**.

Va en **un solo sitio** a propósito: hacerlo endpoint por endpoint serían más
de veinte llamadas repartidas por seis archivos, y bastaría olvidar una para
que el falso aviso reapareciera justo en la acción olvidada.

---

## 5. La fusión selectiva

Cuando `rev` cambia, el cliente se trae el plan y lo funde. La regla es una
sola:

> **Lo local gana mientras esté vivo.**

| Si está activo | Se respeta |
|---|---|
| Un arrastre | **La fusión se pospone entera** hasta que suelte |
| Un lugar abierto, su horario o su gasto | Ese lugar no se sustituye |
| Una nota en edición | Su texto local gana |
| El título en edición | El título local gana |

Y además **lo que está en vuelo**: un lugar recién añadido, una lista recién
creada o un gasto recién apuntado que el servidor **todavía no conoce**. No
vienen en su respuesta, así que una fusión ingenua los borraría de la pantalla
justo entre que se crean y se confirman. Se reconocen porque sus ids
temporales son **cadenas** frente a los números del servidor.

**Si cambia el número de días no se fusiona**: medio estado va indexado por
día. Ese caso avisa y ofrece recargar.

Al terminar, `_reproject()`. **Con eso el mapa en vivo sale gratis**: los pines
son un overlay propio calculado con `MERC()`, así que se recolocan solos sin
tocar nada de Google Maps.

---

## 6. El bloqueo optimista y el 409

Cada lugar lleva una versión (`plan_items.ver`). Al guardar se manda la que se
tenía:

```sql
UPDATE plan_items SET …, ver = ver + 1 WHERE id = ? AND ver = ?
```

Si no cambió ninguna fila, alguien se adelantó → **HTTP 409** con su versión.

### ⚠️ `ver = ver + 1` no es cosmético

`db.php` **no** activa `PDO::MYSQL_ATTR_FOUND_ROWS`, así que `rowCount()`
cuenta filas *modificadas*, no filas que casaron con el `WHERE`. Medido:

| | `rowCount()` |
|---|---|
| Sin `ver = ver + 1`, guardando el **mismo** valor | **0** → conflicto FALSO |
| Con `ver = ver + 1`, guardando el **mismo** valor | 1 → bien |
| Con `ver = ver + 1`, versión que ya no casa | 0 → conflicto DE VERDAD |

### En un 409 hay que hacer lo contrario que en la fusión

La fusión protege lo que tienes abierto para no pisar lo que escribes. Pero
cuando lo que escribiste **ya fue rechazado**, protegerlo deja en pantalla un
valor que no existe en ningún sitio. Así que ese lugar se refresca **saltándose
la protección**.

### El nombre del viaje tiene su propio candado

`planes.ver`, aparte de `rev`. **`rev` no sirve**: se mueve con cualquier
cambio, así que añadir un lugar rechazaría el título de otra persona. Y ese
candado **no se extiende** al resto de campos del plan, porque los subtítulos y
el presupuesto se guardan con 800 ms de retardo y dos escrituras propias se
solapan: el cliente **se daría un 409 a sí mismo**.

### Borrar lo ya borrado no es un error

Pasa de verdad —dos personas borrando a la vez, o un reintento tras una
conexión mala— y sacar un aviso rojo por ello sólo asusta sin motivo. Responde
`ok` con `ya_no_estaba`.

---

## 7. Presencia

`plan_miembros.visto_en`, que el pulso marca en cada latido. Los avatares de la
cabecera llevan un **punto verde** si su dueño estuvo activo en los últimos
**45 segundos** — con el latido a 5 s eso deja margen para tres fallos seguidos
antes de que a alguien se le apague el punto.

Es media jornada de trabajo, y es lo que hace que la aplicación **se vea**
colaborativa en lugar de tener que explicarse con palabras.

> ### ⚠️ Este `UPDATE` no lleva disparador
>
> Ocurre en cada sondeo de cada persona. Si moviera `rev`, cada sondeo contaría
> como una novedad, cada cliente se traería el plan entero, ese trabajo
> generaría más sondeos, y se realimenta hasta fundir el servidor.

---

## 8. Qué se comprobó

### La matriz de concurrencia

Dos cuentas reales, los nueve pares de operaciones simultáneas sobre el mismo
lugar:

| A hace \ B hace después | `update` | `move` | `del` |
|---|---|---|---|
| **`update`** | 409 | 409 | 409 |
| **`move`** | 409 | 409 | 409 |
| **`del`** | 409 | 409 | **200 en silencio** |

### Lo demás

| Prueba | Resultado |
|---|---|
| Pestaña oculta 14 s | 0 peticiones |
| Otra persona cambia algo | Llega en menos de 6 s |
| **Cambio propio** | No dispara la alarma |
| **Nota de 257 caracteres en edición** mientras llegan 5 cambios | **Ni un carácter perdido** |
| Un lugar abierto que la otra persona renombra | Conserva el valor local; al recargar sale el del servidor |
| Arrastrando | La fusión se aparca; al soltar, entra |
| Guardar el mismo valor otra vez | 200 — sin conflicto falso |
| Seis sondeos de dos personas | **`rev` no se movió** |
| Presencia | Los dos se ven; a los 60 s sin latir, el punto se apaga |

### Una instalación limpia queda idéntica a una migrada

Se creó una base desde cero con `instalar.sql` + `rutinas.sql` y se comparó
contra la que se fue migrando, **bloque a bloque**:

```
  [ok]  TABLAS           21 iguales
  [ok]  COLUMNAS        160 iguales
  [ok]  INDICES          50 iguales
  [ok]  CLAVES FORANEAS  23 iguales
  [ok]  RUTINAS          14 iguales
  [ok]  DISPARADORES     22 iguales
  [ok]  COLACIONES       21 iguales
```

La primera vez **no** salieron iguales, y las dos diferencias eran reales:

1. **Siete tablas se habían quedado en `utf8mb4_general_ci`.** Mezclar
   colaciones hace fallar comparaciones con
   `"Illegal mix of collations"`, y ese error parece un fallo del PHP cuando no
   lo es.
2. **El índice de `viajes_usuario.plan_id` se llamaba distinto** en cada vía.

Las dos se cierran en `actualizar_bd.sql`, y `diagnostico.php` vigila la
primera para que no vuelva a pasar.

---

## 9. Lo que esto **no** hace

- **No dice quién se adelantó.** El aviso dice «Alguien cambió…»; ni
  `plan_items` ni `planes` guardan quién tocó la fila por última vez. Es una
  columna `editado_por` y escribirla en cada guardado.
- **No hay edición simultánea del mismo texto.** Dos personas escribiendo la
  misma nota a la vez siguen pisándose: eso pide un algoritmo de fusión de
  texto, que es otro proyecto.
- **El resto de campos del plan sigue sin candado** (subtítulos, presupuesto,
  portada). Cerrarlo pide antes poner en cola las escrituras del plan.
- **El enlace de invitación caduca a los 7 días** y no hay botón para
  rehacerlo.

---

## 10. Dónde está cada cosa

| Pieza | Archivo |
|---|---|
| El testigo y los 20 disparadores | `basedatos/rutinas.sql`, `basedatos/migrate_colaboracion.sql` |
| El latido | `api/plan_pulso.php` · `_latir()` en `js/plan_logic.js` |
| El `rev` en cada respuesta | `apiJson()` en `includes/plan_auth.php` |
| La fusión | `_fusionar()` en `js/plan_logic.js` |
| El bloqueo optimista | `api/plan_items.php`, `api/plan_update.php` |
| La presencia | `api/plan_pulso.php` + los avatares de `plan_template.html` |

El porqué de cada decisión, fase por fase y con lo que salió distinto de lo
planeado, está en `PLAN_colaboracion.md`.
