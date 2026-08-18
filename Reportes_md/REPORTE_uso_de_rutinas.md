# Uso de las rutinas en Ruta Nómada

Quién llama a qué. Este informe **no repite** lo que ya hace
[`REPORTE_rutinas_bd.md`](REPORTE_rutinas_bd.md), que describe el cuerpo
de cada rutina una por una; aquí se mira desde fuera: desde qué archivo
del proyecto se invoca cada una, cuáles no se llaman nunca desde PHP y
por qué eso está bien, y qué tablas se quedaron sin cubrir.

- **Motor:** MariaDB 10.4 (XAMPP) · base `ruta_nomada`
- **Inventario vivo:** 7 funciones + 7 procedimientos + 22 disparadores = **36 rutinas**
- **Levantado de la base el 17/08/2026**, no del `.sql`

---

## 1. El mapa en una frase

Las 36 rutinas se reparten en **tres grupos que no se parecen en nada**:

| Grupo | Cuántas | Para qué |
|---|---|---|
| CRUD de planes y usuarios | 11 | Lo que la aplicación llama a mano desde PHP |
| Aviso de cambios a los colaboradores | 23 | 20 disparadores + `sp_tocar_plan` + sus dos funciones puente |
| Reglas de la propia base | 2 | Validación de gasto y auditoría de borrado |

El segundo grupo es el que sorprende al leer el listado: **veinte de los
veintidós disparadores hacen exactamente lo mismo**, y sólo dos tienen
lógica propia.

---

## 2. Lo que llama la aplicación

Once de las catorce rutinas se invocan desde PHP. Verificado con `grep`
sobre todos los `.php` del proyecto, excluyendo `herramientas/`:

| Rutina | Se llama desde | Para qué |
|---|---|---|
| `sp_registrar_usuario` | `register.php` | Alta de cuenta |
| `sp_crear_plan` | `api/plan_create.php` | Crear viaje |
| `sp_planes_usuario` | `includes/planes_lib.php` | La rejilla de «Mis planes» |
| `sp_actualizar_plan` | `api/plan_update.php` | Editar viaje |
| `sp_borrar_plan` | `api/plan_delete.php`, `api/plan_miembros.php` | Borrar, y salir de un plan |
| `sp_registrar_intento` | `includes/intentos.php` | Freno a la fuerza bruta |
| `fn_login_bloqueado` | `includes/intentos.php` | ¿Está bloqueada esta cuenta? |
| `fn_rol_en_plan` | `api/plan_delete.php` | ¿Puede borrar quien lo pide? |
| `fn_estado_plan` | `includes/planes_lib.php` | Por suceder / sucediendo / expirado |
| `fn_sitios_plan` | `includes/planes_lib.php` | Cuántos lugares tiene |
| `fn_total_gastos` | `includes/planes_lib.php` | Cuánto se lleva gastado |

`sp_borrar_plan` sirviendo a dos endpoints distintos no es un descuido:
salir de un plan del que eres el único miembro **es** borrarlo, y se
resuelve con la misma rutina en vez de duplicar la lógica de limpieza.

---

## 3. Las tres que PHP no llama nunca

`fn_plan_de_item`, `fn_plan_de_lista` y `sp_tocar_plan` no aparecen en
ningún `.php`. **No están muertas: son infraestructura interna**, y quien
las llama son los disparadores.

`sp_tocar_plan` es el eje de todo el sistema de colaboración:

```sql
UPDATE planes SET rev = rev + 1, updated_at = CURRENT_TIMESTAMP
 WHERE id = p_plan_id;
```

Cada vez que algo del viaje cambia, `rev` sube. El cliente pregunta por
esa `rev` cada cinco segundos (`api/plan_pulso.php`) y, si subió sin que
la moviera él, enseña el aviso de novedades. **Veinte disparadores**
existen sólo para llamar a esa rutina.

Las dos funciones puente resuelven un problema concreto: hay tablas que
**no tienen `plan_id`**. Una reacción cuelga de un ítem, y un ítem de
lista cuelga de una lista, así que el disparador no sabe a qué plan
avisar. De ahí:

```sql
CALL sp_tocar_plan(fn_plan_de_item(NEW.item_id));    -- reacciones, reparto
CALL sp_tocar_plan(fn_plan_de_lista(NEW.lista_id));  -- ítems de lista
```

---

## 4. Cobertura por tabla

Qué tablas avisan de sus cambios y cuáles no:

| Tabla | Disparadores | Cubre |
|---|---|---|
| `plan_gastos` | 4 | INSERT · UPDATE · DELETE + validación |
| `plan_items` | 3 | INSERT · UPDATE · DELETE |
| `plan_listas` | 3 | INSERT · UPDATE · DELETE |
| `plan_lista_items` | 3 | INSERT · UPDATE · DELETE |
| `plan_item_gasto` | 3 | INSERT · UPDATE · DELETE |
| `plan_item_reacciones` | 3 | INSERT · UPDATE · DELETE |
| `plan_miembros` | 2 | INSERT · DELETE — **falta UPDATE** |
| `planes` | 1 | Sólo la auditoría de borrado |
| `plan_gasto_reparto` | **0** | **nada** |
| `plan_invitaciones` | 0 | nada (decisión razonable) |
| `plan_destinos` | 0 | tabla sin usar, 0 filas |
| `planes_borrados` | 0 | es el destino de la auditoría |

---

## 5. Tres huecos

### 5.1 `plan_gasto_reparto` no avisa a nadie

Es el hueco más claro. Los repartos de un gasto viven en **dos tablas
distintas** según de dónde venga el gasto:

- `plan_item_gasto` — reparto de un gasto puesto a un **sitio del
  itinerario**. Tiene sus tres disparadores.
- `plan_gasto_reparto` — reparto de un **gasto suelto** del libro.
  **No tiene ninguno.**

Consecuencia: si alguien cambia entre quiénes se divide un gasto suelto,
los demás **no se enteran** hasta que recarguen. El mismo cambio sobre un
gasto de sitio sí avisa. Es una asimetría, no una decisión.

Se arregla con tres disparadores calcados de los de `plan_item_gasto`,
usando `gasto_id` en vez de `item_id` y una función puente equivalente a
`fn_plan_de_item`.

### 5.2 Cambiar el rol de un miembro tampoco avisa

`plan_miembros` tiene disparador de INSERT y de DELETE, pero **no de
UPDATE**. Entrar y salir de un plan avisa; pasar a alguien de lector a
editor, no. Quien esté mirando la pantalla en ese momento seguirá viendo
los controles de antes hasta que recargue.

### 5.3 `plan_destinos` es esquema muerto

Cero filas, cero disparadores, y ningún `.php` la menciona. O se usa o se
quita, pero mientras siga ahí, cualquiera que lea el esquema perderá el
rato preguntándose para qué sirve.

---

## 6. Cambio del 17/08/2026

`fn_total_gastos` y el `COUNT` de `n_gastos` dentro de `sp_planes_usuario`
**miraban sólo `plan_gastos`** e ignoraban el dinero puesto a un lugar del
itinerario (`plan_items.precio`).

Por eso el panel derecho de «Mis planes» decía «Sin gastos registrados»
en seis de los once planes, alguno con nueve mil pesos puestos. Ahora las
dos suman las **dos** fuentes, con `precio > 0` para dejar fuera los
lugares sin precio. Los planes con gastos pasaron de 2 a 8.

> ⚠ La sección 3.2 de `REPORTE_rutinas_bd.md` todavía enseña el cuerpo
> antiguo de `fn_total_gastos`. Lleva una nota que remite aquí, pero si
> algún día se reescribe ese informe, hay que actualizar el bloque `sql`.

---

## 7. Cómo comprobar que están instaladas

```sql
SELECT ROUTINE_TYPE, COUNT(*) FROM information_schema.ROUTINES
 WHERE ROUTINE_SCHEMA = 'ruta_nomada' GROUP BY ROUTINE_TYPE;
-- FUNCTION 7 · PROCEDURE 7

SELECT COUNT(*) FROM information_schema.TRIGGERS
 WHERE EVENT_OBJECT_SCHEMA = 'ruta_nomada';
-- 22
```

`herramientas/diagnostico.php` hace esta misma comprobación y avisa si
falta alguna.

Las definiciones canónicas viven en **`basedatos/rutinas.sql`**, que es
idempotente (`DROP ... IF EXISTS` + `CREATE`). `instalar.sql` lleva una
copia para la instalación desde cero, y **hay que mantener las dos a la
vez**. `actualizar_bd.sql` no define rutinas: remite a `rutinas.sql`.

---

## 8. Balance

Lo que las rutinas aportan de verdad en este proyecto:

**Una sola definición de cada regla.** Cómo se calcula el total de un
plan, qué es un plan «expirado» o quién puede borrarlo se responde en un
sitio. El fallo de la sección 6 lo demuestra por la vía negativa: al
estar el total en una función, arreglarlo fueron dos líneas y quedó
arreglado para todas las pantallas a la vez.

**El aviso de cambios es del motor, no de la aplicación.** Ningún
endpoint tiene que acordarse de subir `rev`. Da igual por dónde entre el
cambio —la API, phpMyAdmin, un `UPDATE` a mano—: el disparador salta.

Eso tiene una cara incómoda que conviene conocer: **un `UPDATE` de
mantenimiento marca los planes como modificados**. Pasó el 17/08 al
limpiar las URL de foto caducadas: se tocaron 57 filas de `plan_items` y
los ocho planes afectados subieron su `rev` y su `updated_at`. Hubo que
restaurarlos a mano después.

**Y el coste:** la lógica queda repartida entre PHP y SQL, y quien lea
sólo el PHP no ve la mitad. Por eso existen estos dos informes.
