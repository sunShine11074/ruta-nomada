# Funciones, procedimientos almacenados y disparadores en Ruta Nómada

Reporte del trabajo hecho del lado de la base de datos: qué rutinas
existen, qué hace cada una, desde qué archivo del proyecto se usan y qué
aporta cada una. Todas están **en producción y en uso**: ninguna se
escribió sólo para cumplir un requisito.

- **Motor:** MariaDB 10.4 (XAMPP)
- **Base:** `ruta_nomada`
- **Total:** 14 rutinas — 4 funciones, 5 procedimientos, 5 disparadores

---

## 1. Resumen

| # | Tipo | Nombre | Se invoca desde |
|---|------|--------|-----------------|
| 1 | Función | `fn_estado_plan` | `sp_planes_usuario` |
| 2 | Función | `fn_total_gastos` | `sp_planes_usuario` |
| 3 | Función | `fn_sitios_plan` | `sp_planes_usuario` |
| 4 | Función | `fn_rol_en_plan` | `sp_borrar_plan` |
| 5 | Procedimiento | `sp_registrar_usuario` | `register.php` |
| 6 | Procedimiento | `sp_crear_plan` | `api/plan_create.php` |
| 7 | Procedimiento | `sp_planes_usuario` | `includes/planes_lib.php` |
| 8 | Procedimiento | `sp_actualizar_plan` | `api/plan_update.php` |
| 9 | Procedimiento | `sp_borrar_plan` | `api/plan_delete.php` |
| 10 | Disparador | `trg_item_ins_toca_plan` | automático · `plan_items` |
| 11 | Disparador | `trg_item_upd_toca_plan` | automático · `plan_items` |
| 12 | Disparador | `trg_item_del_toca_plan` | automático · `plan_items` |
| 13 | Disparador | `trg_plan_borrado` | automático · `planes` |
| 14 | Disparador | `trg_gasto_valido` | automático · `plan_gastos` |

Los cinco procedimientos cubren el **CRUD completo** de la entidad
principal del sistema, el plan de viaje:

| Operación | Procedimiento |
|-----------|---------------|
| **C**reate | `sp_crear_plan` |
| **R**ead | `sp_planes_usuario` |
| **U**pdate | `sp_actualizar_plan` |
| **D**elete | `sp_borrar_plan` |

Más `sp_registrar_usuario`, que es el *Create* de la tabla `usuarios`.

---

## 2. Dónde viven las definiciones

| Archivo | Papel |
|---------|-------|
| `basedatos/rutinas.sql` | **Canónico.** Las 14 juntas y documentadas. Es el archivo de referencia. |
| `basedatos/instalar.sql` | Copia de las mismas definiciones, para que la instalación limpia siga siendo **un solo archivo** importable desde phpMyAdmin. |
| `herramientas/actualizar.bat` | Ejecuta `rutinas.sql` sobre bases ya existentes (doble clic). |
| `herramientas/diagnostico.php` | Verifica que las 14 estén instaladas. |
| `basedatos/procedures.sql` | Puntero histórico a `rutinas.sql`. |

`rutinas.sql` es **idempotente**: cada objeto lleva su `DROP ... IF
EXISTS` delante, así que ejecutarlo dos veces no rompe nada.

> **Nota de mantenimiento:** las definiciones están duplicadas en
> `rutinas.sql` e `instalar.sql` a propósito. Si se modifica una rutina
> hay que tocarla en los dos archivos. Es el mismo pacto que ya existía
> entre `instalar.sql` y `actualizar_bd.sql`.

---

## 3. Funciones (4)

### 3.1 `fn_estado_plan`

```sql
CREATE FUNCTION fn_estado_plan(p_ini DATE, p_fin DATE)
RETURNS VARCHAR(10)
NOT DETERMINISTIC
NO SQL
BEGIN
    IF p_ini IS NULL THEN RETURN 'por'; END IF;
    IF CURDATE() < p_ini THEN RETURN 'por'; END IF;
    IF CURDATE() > COALESCE(p_fin, p_ini) THEN RETURN 'expirado'; END IF;
    RETURN 'sucediendo';
END
```

**Qué hace.** Sitúa un viaje en el tiempo respecto a hoy y devuelve una
de tres claves: `por` (aún no empieza), `sucediendo` (está en curso) o
`expirado` (ya terminó).

Dos decisiones de diseño están dentro de la función:

- **Sin fecha de inicio → `por`.** Un viaje al que todavía no le han
  puesto fechas no se puede situar en el tiempo; tratarlo como pendiente
  es lo que espera el usuario.
- **Sin fecha de fin → se asume igual a la de inicio**, es decir, un
  viaje de un solo día.

Se marca `NOT DETERMINISTIC` porque depende de `CURDATE()`: el mismo plan
devuelve distinto valor según el día en que se consulte.

**Dónde se usa.** Dentro de `sp_planes_usuario`, que la aplica a cada
fila. El resultado llega a `includes/planes_lib.php` en la columna
`est_clave`, y de ahí a `mis_planes.php`.

**Qué aporta.** El estado deja de ser un cálculo exclusivo de PHP y pasa
a poder usarse **dentro de SQL**: permite filtrar, agrupar o contar por
estado sin traer todas las filas a la aplicación. Por ejemplo, «cuántos
viajes activos tiene este usuario» es ahora una sola consulta agregada en
vez de un bucle en PHP. El texto visible («Por suceder») y el color de la
etiqueta siguen en PHP, en `planEstadoDatos()`, porque eso es
presentación y no le corresponde a la base.

---

### 3.2 `fn_total_gastos`

```sql
CREATE FUNCTION fn_total_gastos(p_plan_id INT)
RETURNS DECIMAL(12,2)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN (SELECT COALESCE(SUM(monto), 0)
              FROM plan_gastos
             WHERE plan_id = p_plan_id);
END
```

**Qué hace.** Devuelve el total gastado en un plan. El `COALESCE`
garantiza `0` —no `NULL`— cuando el plan no tiene ningún gasto, que es
el caso mayoritario de un viaje recién creado.

**Dónde se usa.** En `sp_planes_usuario`, columna `total_gastos`. Se
muestra en el panel derecho de `mis_planes.php` como «Total de gastos».

**Qué aporta.** Encapsula una regla de negocio —cómo se calcula el total
de un plan— en un solo lugar. Antes vivía como un `SUM` suelto dentro de
una consulta de `planes_lib.php`; cualquier pantalla nueva que necesitara
ese total tendría que reescribirlo y podría equivocarse (olvidar el
`COALESCE` y mostrar «NULL MXN», por ejemplo). Ahora se pide por nombre.

---

### 3.3 `fn_sitios_plan`

```sql
CREATE FUNCTION fn_sitios_plan(p_plan_id INT)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN (SELECT COUNT(*)
              FROM plan_items
             WHERE plan_id = p_plan_id);
END
```

**Qué hace.** Cuenta los lugares añadidos al itinerario de un plan.

**Dónde se usa.** En `sp_planes_usuario`, columna `lugares`. Es el
«9 sitios añadidos» que aparece en el panel derecho y en las tarjetas de
`mis_planes.php`.

**Qué aporta.** Lo mismo que la anterior: una definición única de «cuántos
sitios tiene un plan». Además hace legible la consulta —
`fn_sitios_plan(p.id)` se entiende de un vistazo, mientras que una
subconsulta con `COUNT(*)` incrustada obliga a leerla entera.

---

### 3.4 `fn_rol_en_plan`

```sql
CREATE FUNCTION fn_rol_en_plan(p_plan_id INT, p_usuario_id INT)
RETURNS VARCHAR(12)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN (SELECT rol
              FROM plan_miembros
             WHERE plan_id = p_plan_id AND usuario_id = p_usuario_id
             LIMIT 1);
END
```

**Qué hace.** Devuelve el rol del usuario en el plan —`propietario`,
`editor` o `lector`— o `NULL` si no es miembro. Calca la consulta que
`planAccess()` hace en `includes/plan_auth.php`.

**Dónde se usa.** En `sp_borrar_plan`, para decidir entre borrar el plan
y sacar al miembro.

**Qué aporta.** Es la pieza de **seguridad** del conjunto. Gracias a
ella, la autorización del borrado no depende únicamente de PHP: aunque
alguien invocara el procedimiento directamente desde la consola de MySQL,
el rol se vuelve a resolver dentro de la base y un invitado no puede
borrar el viaje de otra persona. `NULL` (no es miembro) provoca un error
explícito. Son **dos capas de autorización** —PHP y base de datos— y
ninguna se fía del navegador.

---

## 4. Procedimientos almacenados (5)

### 4.1 `sp_registrar_usuario` — alta de usuario

```sql
CREATE PROCEDURE sp_registrar_usuario (
    IN p_nombre        VARCHAR(120),
    IN p_email         VARCHAR(255),
    IN p_password_hash VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR 1062
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'EMAIL_DUPLICADO';

    IF EXISTS (SELECT 1 FROM usuarios WHERE email = p_email LIMIT 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'EMAIL_DUPLICADO';
    END IF;

    INSERT INTO usuarios (nombre, email, password_hash)
    VALUES (p_nombre, p_email, p_password_hash);

    SELECT LAST_INSERT_ID() AS id;
END
```

**Qué hace.** Comprueba que el correo no exista, inserta el usuario y
devuelve el `id` generado.

Dos detalles importantes:

- **La contraseña llega ya cifrada.** El `password_hash()` de bcrypt se
  calcula en PHP; la contraseña en texto plano nunca viaja a MySQL ni
  aparece en los registros del servidor.
- **Doble red contra el duplicado.** El `IF EXISTS` da el mensaje
  amigable en el caso normal, pero entre esa comprobación y el `INSERT`
  cabe una condición de carrera: dos registros simultáneos con el mismo
  correo. Quien cierra esa rendija es el índice único
  `idx_usuarios_email`, y el `EXIT HANDLER FOR 1062` traduce el error del
  índice **al mismo mensaje** para que la aplicación no tenga que
  distinguir los dos caminos.

**Dónde se usa.** `register.php`, línea 60:

```php
$stmt = $db->prepare('CALL sp_registrar_usuario(?, ?, ?)');
$stmt->execute([$nombre, $email, $password_hash]);
$userId = (int) $stmt->fetchColumn();
$stmt->closeCursor();
```

Y el error se traduce a lenguaje humano justo debajo:

```php
if (strpos($e->getMessage(), 'EMAIL_DUPLICADO') !== false) {
    $error_form = 'El correo electrónico ya está registrado. Intenta iniciar sesión.';
}
```

**Qué aporta.** Convierte «comprobar, insertar y recuperar el id» en una
sola llamada, y hace **imposible** que se cuelen dos cuentas con el mismo
correo, pase lo que pase en la capa PHP.

---

### 4.2 `sp_crear_plan` — la **C** del CRUD

```sql
CREATE PROCEDURE sp_crear_plan (
    IN p_usuario_id   INT,
    IN p_nombre       VARCHAR(200),
    IN p_destino      VARCHAR(120),
    IN p_lat          DECIMAL(10,7),
    IN p_lng          DECIMAL(10,7),
    IN p_fecha_inicio DATE,
    IN p_fecha_fin    DATE,
    IN p_privacidad   VARCHAR(10)
)
BEGIN
    DECLARE v_id INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    INSERT INTO planes
        (usuario_id, nombre, destino, lat, lng,
         fecha_inicio, fecha_fin, privacidad, estado)
    VALUES
        (p_usuario_id, p_nombre, p_destino, p_lat, p_lng,
         p_fecha_inicio, p_fecha_fin, p_privacidad, 'activo');

    SET v_id = LAST_INSERT_ID();

    INSERT INTO plan_miembros (plan_id, usuario_id, rol)
    VALUES (v_id, p_usuario_id, 'propietario');

    COMMIT;

    SELECT v_id AS id;
END
```

**Qué hace.** Crea el plan **y** su fila de propietario en
`plan_miembros`, las dos dentro de una misma transacción. Devuelve el
`id` del plan nuevo.

El `EXIT HANDLER FOR SQLEXCEPTION` con `ROLLBACK` + `RESIGNAL` garantiza
que si el segundo `INSERT` falla, el primero se deshace y el error llega
al llamador sin enmascararse.

**Dónde se usa.** `api/plan_create.php`, línea 41:

```php
$stmt = $db->prepare('CALL sp_crear_plan(?,?,?,?,?,?,?,?)');
$stmt->execute([$userId, $nombre, $destino, $lat, $lng, $fi, $ff, $priv]);
$planId = (int)($stmt->fetch()['id'] ?? 0);
$stmt->closeCursor();
```

**Qué aporta.** Blinda un **invariante del modelo de datos**: no puede
existir un plan sin dueño. Antes la garantía dependía de que el
programador recordara escribir las dos inserciones dentro de la misma
transacción en PHP; ahora es imposible saltársela, ni siquiera invocando
el SQL a mano.

Como efecto secundario mejoró el manejo de errores de la importación del
borrador antiguo: al salir de la transacción del plan, esa importación
quedó en **su propia transacción**. Si el borrador viene corrupto, el
plan sobrevive vacío y el error queda en el log — antes se perdía todo
porque compartían transacción.

---

### 4.3 `sp_planes_usuario` — la **R** del CRUD

```sql
CREATE PROCEDURE sp_planes_usuario (
    IN p_usuario_id INT
)
BEGIN
    SELECT p.id, p.nombre, p.destino, p.lat, p.lng,
           p.fecha_inicio, p.fecha_fin, p.portada_url, p.presupuesto,
           p.creado_en, p.updated_at, m.rol,
           fn_estado_plan(p.fecha_inicio, p.fecha_fin) AS est_clave,
           fn_sitios_plan(p.id)                        AS lugares,
           (SELECT COUNT(*) FROM plan_gastos g
             WHERE g.plan_id = p.id)                   AS n_gastos,
           fn_total_gastos(p.id)                       AS total_gastos
      FROM planes p
      JOIN plan_miembros m
        ON m.plan_id = p.id AND m.usuario_id = p_usuario_id;

    SELECT mm.plan_id, mm.rol, u.id AS uid, u.nombre, u.foto_perfil
      FROM plan_miembros mm
      JOIN plan_miembros yo
        ON yo.plan_id = mm.plan_id AND yo.usuario_id = p_usuario_id
      JOIN usuarios u ON u.id = mm.usuario_id
     ORDER BY mm.plan_id,
              FIELD(mm.rol, 'propietario', 'editor', 'lector'),
              u.nombre;
END
```

**Qué hace.** Devuelve **dos conjuntos de resultados** en una sola
llamada:

1. Los planes del usuario con su rol y sus agregados ya calculados —aquí
   trabajan tres de las cuatro funciones.
2. Los miembros de esos planes con su foto, para los avatares, con el
   propietario primero (el `FIELD(...)` fuerza ese orden).

El segundo `SELECT` se une consigo mismo (`yo`) para traer sólo los
miembros de planes donde el usuario que consulta participa: nadie ve la
lista de participantes de un viaje ajeno.

**Dónde se usa.** `includes/planes_lib.php`, línea 79, dentro de
`planesDeUsuario()`:

```php
$st = $db->prepare('CALL sp_planes_usuario(?)');
$st->execute([$userId]);
$planes = $st->fetchAll();
$st->nextRowset();               // salta al segundo conjunto
$filasMiembros = $st->fetchAll();
$st->closeCursor();
```

Y esa función alimenta `mis_planes.php` (línea 42), con sus tres vistas
—tabla, tarjetas y mapa— y el panel derecho.

**Qué aporta.** Reduce **cuatro consultas a una llamada**. La versión
anterior lanzaba una consulta para los planes, otra para contar lugares,
otra para los gastos y otra para los miembros, y las cruzaba en PHP.
Ahora la base entrega el material ya cruzado y PHP se queda sólo con lo
que le toca: los colores, el orden y el formato.

> **Honestidad sobre el rendimiento.** Las funciones aplicadas por fila
> son subconsultas correlacionadas. Con las decenas de planes de un
> usuario real la diferencia es imperceptible; si algún día la lista
> creciera a miles, el camino sería volver al `GROUP BY` que quedó
> registrado en el historial de `planes_lib.php`.

---

### 4.4 `sp_actualizar_plan` — la **U** del CRUD

```sql
CREATE PROCEDURE sp_actualizar_plan (
    IN p_id             INT,
    IN p_nombre         VARCHAR(200),
    IN p_fecha_inicio   DATE,
    IN p_fecha_fin      DATE,
    IN p_privacidad     VARCHAR(10),
    IN p_presupuesto    DECIMAL(12,2),
    IN p_lat            DECIMAL(10,7),
    IN p_lng            DECIMAL(10,7),
    IN p_portada_url    VARCHAR(500),
    IN p_dia_subtitulos TEXT
)
BEGIN
    UPDATE planes
       SET nombre         = p_nombre,
           fecha_inicio   = p_fecha_inicio,
           fecha_fin      = p_fecha_fin,
           privacidad     = p_privacidad,
           presupuesto    = p_presupuesto,
           lat            = p_lat,
           lng            = p_lng,
           portada_url    = p_portada_url,
           dia_subtitulos = p_dia_subtitulos
     WHERE id = p_id;
END
```

**Qué hace.** Escribe la fila **completa** del plan.

**Por qué la fila completa y no sólo los campos que cambiaron.** Es la
decisión de diseño más discutida del conjunto. La alternativa natural
sería `COALESCE(p_nombre, nombre)` en cada columna, de modo que `NULL`
significara «no tocar este campo». El problema es que entonces se vuelve
**imposible borrar un dato**: quitar la fecha de fin de un viaje, o
quitarle la portada, exige escribir `NULL` de verdad, y `COALESCE` no
puede distinguir «no me lo mandes» de «ponlo a NULL».

La solución fue repartir el trabajo: **PHP fusiona, la base escribe.**
`planAccess()` ya devuelve la fila entera del plan, así que
`api/plan_update.php` toma esa fila, sobreescribe encima sólo los campos
que llegaron en la petición —validándolos— y manda el resultado completo.
`NULL` significa `NULL` sin ambigüedad, y el procedimiento se queda
simple.

**Dónde se usa.** `api/plan_update.php`, línea 78:

```php
$st = getDB()->prepare('CALL sp_actualizar_plan(?,?,?,?,?,?,?,?,?,?)');
$st->execute([
    $planId, $fila['nombre'], $fila['fecha_inicio'], $fila['fecha_fin'],
    $fila['privacidad'], $fila['presupuesto'], $fila['lat'], $fila['lng'],
    $fila['portada_url'], $fila['dia_subtitulos'],
]);
$st->closeCursor();
```

**Qué aporta.** Elimina el `UPDATE` de SQL **construido dinámicamente**
que había antes (`'UPDATE planes SET ' . implode(', ', $sets)`). Aunque
aquel código era seguro —los nombres de columna salían de una lista
blanca, no de la petición—, armar sentencias concatenando texto es
justamente el patrón que la inyección de SQL aprovecha, y basta un
descuido futuro para abrir el agujero. Ahora la sentencia es fija y está
en la base; PHP sólo aporta valores.

---

### 4.5 `sp_borrar_plan` — la **D** del CRUD

```sql
CREATE PROCEDURE sp_borrar_plan (
    IN p_plan_id    INT,
    IN p_usuario_id INT
)
BEGIN
    DECLARE v_rol VARCHAR(12);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SET v_rol = fn_rol_en_plan(p_plan_id, p_usuario_id);

    IF v_rol IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'SIN_ACCESO';
    END IF;

    IF v_rol = 'propietario' THEN
        START TRANSACTION;
        UPDATE viajes_usuario SET plan_id = NULL WHERE plan_id = p_plan_id;
        DELETE FROM planes WHERE id = p_plan_id;
        COMMIT;
        SELECT 'borrado' AS accion;
    ELSE
        DELETE FROM plan_miembros
         WHERE plan_id = p_plan_id AND usuario_id = p_usuario_id;
        SELECT 'salida' AS accion;
    END IF;
END
```

**Qué hace.** Un mismo botón, dos operaciones distintas según **el rol
real** del usuario:

- **Propietario** → borra el viaje entero. Las claves foráneas en cascada
  arrastran `plan_items` (y con ellos `plan_item_gasto` y
  `plan_item_reacciones`), `plan_gastos`, `plan_listas`,
  `plan_miembros`, `plan_invitaciones` y `plan_destinos`.
- **Editor o lector** → sólo se quita a sí mismo de `plan_miembros`. El
  viaje sigue existiendo para los demás.
- **No es miembro** → error `SIN_ACCESO`.

El `UPDATE viajes_usuario SET plan_id = NULL` desvincula el historial de
destinos antes de borrar: ese historial es del usuario y debe sobrevivir
al plan.

Devuelve la acción realizada (`borrado` o `salida`) como conjunto de
resultados, para que la interfaz sepa qué mensaje mostrar.

**Dónde se usa.** `api/plan_delete.php`, línea 46:

```php
$st = getDB()->prepare('CALL sp_borrar_plan(?,?)');
$st->execute([$planId, $userId]);
$accion = (string)($st->fetch()['accion'] ?? '');
$st->closeCursor();
```

Y en la interfaz, `js/mis_planes.js` cambia la etiqueta del botón entre
«Eliminar plan» y «Salir del plan» según el rol, con la pulsación
sostenida de 5 segundos.

**Qué aporta.** Es el procedimiento con **más valor de seguridad** del
conjunto. La regla «un invitado no puede borrar el viaje de otra persona»
deja de estar sólo en PHP y pasa a estar grabada en la base. Y la
operación entera —desvincular, borrar, decidir la rama— se vuelve
atómica: no hay ventana en la que el plan esté medio borrado.

---

## 5. Disparadores (5)

### 5.1–5.3 `trg_item_ins/upd/del_toca_plan` — fecha de modificación

```sql
CREATE TRIGGER trg_item_ins_toca_plan
AFTER INSERT ON plan_items
FOR EACH ROW
BEGIN
    UPDATE planes SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.plan_id;
END

CREATE TRIGGER trg_item_upd_toca_plan
AFTER UPDATE ON plan_items
FOR EACH ROW
BEGIN
    UPDATE planes SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.plan_id;
END

CREATE TRIGGER trg_item_del_toca_plan
AFTER DELETE ON plan_items
FOR EACH ROW
BEGIN
    UPDATE planes SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.plan_id;
END
```

**Qué hacen.** Cada vez que se añade, modifica o elimina un lugar del
itinerario, actualizan la fecha de modificación del plan al que pertenece.
Son tres y no uno porque en MariaDB **un disparador es por evento y por
tabla**: no existe un `AFTER INSERT OR UPDATE OR DELETE` como en otros
motores. Los dos primeros usan `NEW.plan_id`; el de borrado usa
`OLD.plan_id`, porque la fila ya no existe como «nueva».

**Dónde se usan.** No se invocan: se disparan solos. El resultado se lee
en `includes/planes_lib.php` (columna `updated_at`) y se muestra en
`mis_planes.php` como «Última modificación», tanto en el panel derecho
como en las tarjetas y la tabla. También es el criterio del orden «Más
nuevos» en `planesOrdenar()`.

**Qué aportan — arreglaron un error real.** Este es el caso donde un
disparador no fue un adorno académico sino la solución correcta a un
fallo que llevaba tiempo en el proyecto.

La pantalla mostraba «Última modificación» leyendo `planes.updated_at`, y
ordenaba por esa columna. Pero **ningún endpoint de contenido tocaba la
tabla `planes`**: se revisaron `api/plan_items.php`, `api/plan_gastos.php`
y `api/plan_listas.php` y ninguno hacía `UPDATE planes`. Sólo lo hacía
`api/plan_update.php`, que cambia el nombre o las fechas. El resultado:
un usuario podía añadir diez lugares a su itinerario y la pantalla seguía
diciendo que el plan no se tocaba desde hacía semanas, y el orden «Más
nuevos» lo mandaba al fondo de la lista.

La alternativa era añadir un `UPDATE planes` a mano en cada endpoint que
escribe contenido — tres archivos hoy, y uno más cada vez que el proyecto
crezca, con el riesgo permanente de olvidar alguno. El disparador lo
resuelve en **la única capa que ve todas las escrituras**, venga la
operación de donde venga.

> **Detalle técnico.** Los borrados en cascada **no** disparan
> disparadores en MariaDB. Aquí eso es justamente lo deseable: cuando se
> borra un plan entero, sus `plan_items` desaparecen en cascada y sería
> absurdo intentar actualizar la fecha de un plan que ya no existe.

---

### 5.4 `trg_plan_borrado` — auditoría

```sql
CREATE TRIGGER trg_plan_borrado
BEFORE DELETE ON planes
FOR EACH ROW
BEGIN
    INSERT INTO planes_borrados (plan_id, nombre, destino, usuario_id)
    VALUES (OLD.id, OLD.nombre, OLD.destino, OLD.usuario_id);
END
```

Con su tabla de apoyo:

```sql
CREATE TABLE IF NOT EXISTS `planes_borrados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL COMMENT 'id que tenía el plan',
  `nombre` varchar(200) NOT NULL,
  `destino` varchar(120) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'dueño del plan al borrarse',
  `borrado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
```

**Qué hace.** Antes de que un plan desaparezca, copia sus datos
esenciales —id, nombre, destino, dueño y el momento exacto— a una tabla
de archivo. Tiene que ser `BEFORE DELETE`: en `AFTER DELETE` los datos ya
no estarían disponibles para copiarlos.

**Nota de diseño:** `planes_borrados` **no tiene clave foránea** a
`planes`, y es deliberado. Es un archivo histórico y su fila debe
sobrevivir al plan que registra; una clave foránea la borraría en
cascada, que es exactamente lo contrario de lo que se busca.

**Dónde se usa.** Se dispara solo, desde `sp_borrar_plan` (que a su vez
llama `api/plan_delete.php`) o desde cualquier borrado manual.

**Qué aporta.** Trazabilidad de la acción más destructiva del sistema.
El borrado de un plan es irreversible y no hay papelera — por eso la
interfaz obliga a **mantener pulsado el botón cinco segundos** en vez de
un clic suelto. Este disparador añade la última red: si un usuario
reclama que «desapareció su viaje», queda constancia de qué se borró,
de quién era y cuándo. El coste es una fila diminuta por borrado.

---

### 5.5 `trg_gasto_valido` — validación

```sql
CREATE TRIGGER trg_gasto_valido
BEFORE INSERT ON plan_gastos
FOR EACH ROW
BEGIN
    IF NEW.monto < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'GASTO_NEGATIVO';
    END IF;
END
```

**Qué hace.** Rechaza cualquier gasto con importe negativo, aborte quien
aborte la operación.

**Por qué se permite el cero.** `api/plan_gastos.php` acepta hoy un gasto
de `0` —una actividad gratuita que el usuario quiere dejar apuntada— y
**la base no debe volverse más estricta que la aplicación**. Si el
disparador rechazara el cero, rompería un flujo que hoy es legal. Por eso
la condición es `< 0` y no `<= 0`.

**Dónde se usa.** Se dispara solo en cualquier `INSERT` sobre
`plan_gastos`, venga de `api/plan_gastos.php` o de donde sea.

**Qué aporta.** Es una **regla de integridad que ningún cliente puede
saltarse**. La validación de PHP protege de errores del usuario; el
disparador protege del propio sistema: un script de importación mal
escrito, una prueba, una consulta manual en phpMyAdmin. Un importe
negativo corrompería el total del viaje y las gráficas de distribución de
gastos sin que nadie se diera cuenta hasta mucho después.

---

## 6. Cómo se probaron

Las rutinas se validaron **antes** de tocar la base real, en dos etapas.

**Etapa 1 — base de ensayo desechable.** Se creó una base `rn_rutinas_test`
y se comprobó:

| Prueba | Resultado |
|--------|-----------|
| Instalación limpia de `instalar.sql` | 4 funciones, 5 procedimientos, 5 disparadores |
| `rutinas.sql` ejecutado dos veces seguidas | sin error (idempotencia) |
| `fn_estado_plan` en sus 3 ramas + sin fechas + viaje de un día | 6/6 correctas |
| Correo duplicado en `sp_registrar_usuario` | rechazado con `45000 EMAIL_DUPLICADO` |
| `sp_crear_plan` | plan + propietario creados juntos |
| `fn_rol_en_plan` con propietario / editor / no miembro | `propietario` / `editor` / `NULL` |
| Los tres disparadores de `updated_at` | los tres movieron la fecha |
| Gasto negativo | rechazado con `45000 GASTO_NEGATIVO`; el `0` se aceptó |
| `fn_total_gastos` contra un `SUM` manual | idénticos |
| `sp_actualizar_plan` con `NULL` en fecha de fin | la fecha se borró de verdad |
| Borrado por un editor | `salida`; el plan sobrevivió |
| Borrado por el propietario | `borrado` + fila de auditoría + cascada + `viajes_usuario` desvinculado |
| Borrado por un no miembro | rechazado con `45000 SIN_ACCESO` |

La base de ensayo se eliminó al terminar.

**Etapa 2 — ciclo completo sobre la base real**, usando los endpoints
reales desde el navegador y un plan desechable llamado
`PRUEBA-RUTINAS`: crear → añadir un lugar → renombrar y quitar la fecha
de fin → gasto válido → gasto negativo (rechazado) → borrar.

Para comprobar el disparador de `updated_at` se retrasó a propósito el
reloj del plan a `2020-01-01` y se añadió un lugar por el endpoint real:
la fecha saltó al momento actual. **El error de «Última modificación»
quedó demostrado como resuelto.**

Los conteos de la base real fueron idénticos antes y después —6 planes,
38 lugares, 7 miembros, 4 gastos— con la única diferencia deliberada de
la fila en `planes_borrados`, que es precisamente la prueba de que la
auditoría funciona.

---

## 7. Cómo comprobar que están instaladas

**Opción 1 — el diagnóstico del proyecto** (recomendada):

```
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\<carpeta>\herramientas\diagnostico.php" --sin-red
```

Debe imprimir:

```
[ok]  Están las 19 tablas que crea instalar.sql
[ok]  Están las 14 rutinas de rutinas.sql (funciones, procedimientos y triggers)
```

Si falta alguna, la nombra y da la orden exacta para reponerla. La lista
de rutinas esperadas **no está escrita a mano**: se lee de
`rutinas.sql`, de modo que si mañana se añade una, la comprobación se
entera sola.

**Opción 2 — consulta directa:**

```sql
SELECT ROUTINE_TYPE, ROUTINE_NAME
  FROM information_schema.ROUTINES
 WHERE ROUTINE_SCHEMA = 'ruta_nomada';

SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE
  FROM information_schema.TRIGGERS
 WHERE TRIGGER_SCHEMA = 'ruta_nomada';
```

**Si faltan**, se instalan sin perder datos con doble clic en
`herramientas/actualizar.bat`, o con:

```
mysql -u root ruta_nomada -e "source basedatos/rutinas.sql"
```

---

## 8. Balance

| Requisito | Mínimo pedido | Entregado |
|-----------|---------------|-----------|
| Procedimientos almacenados (CRUD) | 4 | **5** |
| Funciones | 4 | **4** |
| Disparadores | 3 | **5** |

Más allá del conteo, lo que aportan al proyecto:

1. **Un error corregido.** Los disparadores de `updated_at` arreglaron
   la «Última modificación» de `mis_planes.php`, que llevaba tiempo
   mostrando una fecha falsa.
2. **Dos invariantes garantizados por la base**, no por la disciplina del
   programador: ningún plan sin propietario, ningún gasto negativo.
3. **Autorización en dos capas.** `fn_rol_en_plan` dentro de
   `sp_borrar_plan` repite en la base la comprobación de permisos que
   hace PHP.
4. **Menos SQL construido con concatenación de texto.** El `UPDATE`
   dinámico de `plan_update.php` desapareció.
5. **Menos viajes a la base.** `mis_planes.php` pasó de cuatro consultas
   a una sola llamada.
6. **Trazabilidad** del borrado de planes, que es irreversible.

---

*Reporte generado sobre el estado del repositorio en el commit `0bee7d2`
(«feat: 14 rutinas de base de datos»).*
