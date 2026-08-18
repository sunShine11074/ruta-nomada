-- ============================================================
--  basedatos/rutinas.sql — Funciones, procedimientos y triggers
--  Ruta Nómada
--
--  LAS 16 RUTINAS DEL PROYECTO, EN UN SOLO ARCHIVO:
--    · 5 funciones      (fn_estado_plan, fn_total_gastos,
--                        fn_sitios_plan, fn_rol_en_plan,
--                        fn_login_bloqueado)
--    · 6 procedimientos (sp_registrar_usuario, sp_crear_plan,
--                        sp_planes_usuario, sp_actualizar_plan,
--                        sp_borrar_plan, sp_registrar_intento)
--    · 5 triggers       (3 de updated_at sobre plan_items,
--                        1 de auditoría, 1 de validación)
--
--  Este archivo es el CANÓNICO: es el que se lee para calificar y el
--  que ejecuta herramientas/actualizar.bat. instalar.sql lleva una
--  copia de estas mismas definiciones porque la instalación limpia es
--  UN solo archivo importado por phpMyAdmin y debe seguir siéndolo.
--  Si se toca una rutina hay que tocarla en los dos; es el mismo pacto
--  que ya existe entre instalar.sql y actualizar_bd.sql.
--
--  Es IDEMPOTENTE: cada objeto lleva su DROP ... IF EXISTS delante,
--  así que ejecutarlo dos veces no rompe nada.
--
--     mysql -u root ruta_nomada < basedatos/rutinas.sql
--  o importándolo desde phpMyAdmin (entiende DELIMITER sin problema).
--
--  El orden interno lo dictan las dependencias:
--    1. la tabla planes_borrados   (la necesita el trigger de auditoría)
--    2. las funciones              (sp_borrar_plan usa fn_rol_en_plan y
--                                   sp_planes_usuario usa las otras tres)
--    3. los procedimientos
--    4. los triggers
-- ============================================================


-- ────────────────────────────────────────────────────────────
--  0. planes_borrados — archivo de auditoría del borrado
--
--  A propósito SIN clave foránea a planes: es un archivo histórico y
--  su fila debe sobrevivir al plan que registra. Nadie escribe aquí
--  directamente; lo hace el trigger trg_plan_borrado.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `planes_borrados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL COMMENT 'id que tenía el plan',
  `nombre` varchar(200) NOT NULL,
  `destino` varchar(120) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'dueño del plan al borrarse',
  `borrado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ────────────────────────────────────────────────────────────
--  0.b intentos_login — freno a la fuerza bruta en login.php
--
--  Sin esto, probar contraseñas contra una cuenta sale gratis: el
--  formulario acepta tantos intentos por segundo como aguante Apache.
--  Aquí queda constancia de cada intento y fn_login_bloqueado() decide
--  cuándo hay que parar.
--
--  Guarda el correo TECLEADO, exista o no la cuenta. Es a propósito:
--  si sólo se anotaran los correos reales, la tabla misma sería una
--  lista de quién está registrado.
--
--  Nunca guarda la contraseña probada, ni siquiera su hash.
--
--  ip admite 45 caracteres porque ese es el largo de una IPv6 escrita
--  del todo, y en localhost la de siempre es ::1.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `intentos_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL COMMENT 'el que se tecleó, exista o no',
  `ip` varchar(45) NOT NULL DEFAULT '' COMMENT 'IPv4 o IPv6',
  `exito` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_fecha` (`email`, `creado_en`),
  KEY `idx_ip_fecha` (`ip`, `creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- El juego de caracteres de la conexion, fijado a proposito.
-- Sin esta linea las rutinas nacen con el cotejamiento que traiga el
-- cliente: la consola de Windows entrega cp850_general_ci, y entonces
-- comparar un parametro contra una columna utf8mb4 revienta con
-- "Illegal mix of collations". Paso el 11/08/2026 con fn_login_bloqueado.
SET NAMES utf8mb4;

DELIMITER $$

-- ════════════════════════════════════════════════════════════
--  FUNCIONES
-- ════════════════════════════════════════════════════════════

-- ------------------------------------------------------------
-- fn_estado_plan(inicio, fin) → 'por' | 'sucediendo' | 'expirado'
--   Estado temporal del viaje respecto a hoy. Espejo exacto de
--   planEstadoTemporal() en includes/planes_lib.php: sin fecha de
--   inicio el viaje no se puede situar en el tiempo y se trata como
--   pendiente; sin fecha de fin se asume viaje de un día.
--   El texto y el color siguen siendo cosa de PHP: son presentación.
--   NOT DETERMINISTIC porque depende de CURDATE().
-- ------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_estado_plan $$
CREATE FUNCTION fn_estado_plan(p_ini DATE, p_fin DATE)
RETURNS VARCHAR(10)
NOT DETERMINISTIC
NO SQL
BEGIN
    IF p_ini IS NULL THEN RETURN 'por'; END IF;
    IF CURDATE() < p_ini THEN RETURN 'por'; END IF;
    IF CURDATE() > COALESCE(p_fin, p_ini) THEN RETURN 'expirado'; END IF;
    RETURN 'sucediendo';
END $$

-- ------------------------------------------------------------
-- fn_total_gastos(plan_id) → DECIMAL(12,2)
--   Suma de LOS DOS SITIOS donde vive el dinero de un viaje: los gastos
--   sueltos del libro (plan_gastos) y el precio puesto a un lugar del
--   itinerario (plan_items.precio). 0 si no hay ninguno.
--
--   ⚠ ANTES SOLO MIRABA plan_gastos, y por eso «Mis planes» decia «Sin
--   gastos registrados» en seis planes que si tenian dinero puesto -uno
--   con 9.000 MXN-. Es el mismo hueco que ya se tapo dentro de plan.php
--   (_gastosDelPlan), pero esta funcion se quedo atras y seguia contando
--   solo la mitad del sistema.
--
--   El filtro precio > 0 descarta los NULL de los lugares a los que
--   nadie ha puesto precio, que son la mayoria.
-- ------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_total_gastos $$
CREATE FUNCTION fn_total_gastos(p_plan_id INT)
RETURNS DECIMAL(12,2)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN (SELECT COALESCE(SUM(monto), 0)
              FROM plan_gastos
             WHERE plan_id = p_plan_id)
         + (SELECT COALESCE(SUM(precio), 0)
              FROM plan_items
             WHERE plan_id = p_plan_id AND precio > 0);
END $$

-- ------------------------------------------------------------
-- fn_sitios_plan(plan_id) → INT
--   Cuántos lugares tiene el plan; es el «9 sitios añadidos» del
--   panel de mis_planes.php.
-- ------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_sitios_plan $$
CREATE FUNCTION fn_sitios_plan(p_plan_id INT)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN (SELECT COUNT(*)
              FROM plan_items
             WHERE plan_id = p_plan_id);
END $$

-- ------------------------------------------------------------
-- fn_rol_en_plan(plan_id, usuario_id) → 'propietario' | 'editor'
--                                        | 'lector' | NULL
--   El rol del usuario en el plan, o NULL si no es miembro. Calca la
--   consulta de planAccess() en includes/plan_auth.php y la usa
--   sp_borrar_plan para decidir entre borrar y salir.
-- ------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_rol_en_plan $$
CREATE FUNCTION fn_rol_en_plan(p_plan_id INT, p_usuario_id INT)
RETURNS VARCHAR(12)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN (SELECT rol
              FROM plan_miembros
             WHERE plan_id = p_plan_id AND usuario_id = p_usuario_id
             LIMIT 1);
END $$


-- ------------------------------------------------------------
-- fn_login_bloqueado(email, ip) → 1 si hay que frenar, 0 si no
--   La política de fuerza bruta, en un solo sitio. Frena por dos
--   caminos a la vez, y basta con que uno se pase:
--
--     · 5 fallos sobre el MISMO CORREO en 15 minutos
--       Protege una cuenta concreta de quien prueba contraseñas.
--     · 20 fallos desde la MISMA IP en 15 minutos
--       Protege al resto de quien barre muchas cuentas a la vez.
--       El tope es más alto porque una casa o un campus comparten IP
--       y no queremos bloquear a gente que sólo se equivocó.
--
--   Es una ventana deslizante, no un castigo fijo: en cuanto los
--   intentos viejos salen de los 15 minutos, se puede volver a probar.
--   Nadie tiene que desbloquear nada a mano.
--
--   Un acierto borra los fallos de ese correo (ver sp_registrar_intento),
--   así que quien acaba recordando su contraseña empieza de cero.
--
--   Lo llama login.php antes de comprobar la contraseña.
-- ------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_login_bloqueado $$
CREATE FUNCTION fn_login_bloqueado(
    p_email VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    p_ip    VARCHAR(45)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_correo INT DEFAULT 0;
    DECLARE v_ip     INT DEFAULT 0;

    SELECT COUNT(*) INTO v_correo
      FROM intentos_login
     WHERE email = p_email
       AND exito = 0
       AND creado_en > DATE_SUB(NOW(), INTERVAL 15 MINUTE);
    IF v_correo >= 5 THEN RETURN 1; END IF;

    SELECT COUNT(*) INTO v_ip
      FROM intentos_login
     WHERE ip = p_ip
       AND exito = 0
       AND creado_en > DATE_SUB(NOW(), INTERVAL 15 MINUTE);
    IF v_ip >= 20 THEN RETURN 1; END IF;

    RETURN 0;
END $$


-- ════════════════════════════════════════════════════════════
--  PROCEDIMIENTOS — el CRUD del plan
-- ════════════════════════════════════════════════════════════

-- ------------------------------------------------------------
-- sp_registrar_usuario  (el CREATE de usuarios)
--   Verifica que el correo no exista e inserta al usuario en una
--   sola operación. Devuelve el id generado como result set.
--   La contraseña llega YA hasheada (bcrypt se calcula en PHP;
--   la contraseña en texto plano nunca viaja a MySQL).
--   Errores: SQLSTATE 45000 con mensaje 'EMAIL_DUPLICADO'.
--   Lo llama register.php.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_registrar_usuario $$
CREATE PROCEDURE sp_registrar_usuario (
    IN p_nombre        VARCHAR(120),
    IN p_email         VARCHAR(255),
    IN p_password_hash VARCHAR(255)
)
BEGIN
    -- Si el índice único detecta un duplicado que se coló entre el
    -- chequeo y el INSERT, lo traducimos al mismo error amigable.
    DECLARE EXIT HANDLER FOR 1062
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'EMAIL_DUPLICADO';

    IF EXISTS (SELECT 1 FROM usuarios WHERE email = p_email LIMIT 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'EMAIL_DUPLICADO';
    END IF;

    INSERT INTO usuarios (nombre, email, password_hash)
    VALUES (p_nombre, p_email, p_password_hash);

    SELECT LAST_INSERT_ID() AS id;
END $$

-- ------------------------------------------------------------
-- sp_crear_plan  (el CREATE de planes)
--   Crea el plan Y su fila de propietario en plan_miembros dentro de
--   una transacción propia: no puede existir un plan sin dueño, ni
--   llamando al SQL a mano. Devuelve el id como result set.
--   La validación de entradas (fechas coherentes, privacidad válida)
--   se queda en api/plan_create.php; la importación del borrador del
--   planificador viejo también, porque es un bucle sobre JSON
--   arbitrario y eso no es asunto de la base.
--   CONTRATO: no llamar dentro de otra transacción (hace COMMIT).
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_crear_plan $$
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
END $$

-- ------------------------------------------------------------
-- sp_planes_usuario  (el READ de mis_planes.php)
--   Devuelve DOS result sets:
--     1. los planes del usuario con su rol, estado temporal, número
--        de sitios y total de gastos — aquí trabajan tres de las
--        cuatro funciones;
--     2. los miembros de esos planes con su foto, para los avatares,
--        con el propietario primero.
--   El orden de presentación (próximos / recientes / alfabético) y
--   los colores se quedan en PHP: son presentación.
--   Nota honesta sobre rendimiento: las funciones por fila son
--   subconsultas correlacionadas. Con las decenas de planes de un
--   usuario real da exactamente igual; si algún día la lista fuera
--   de miles, el camino es volver al GROUP BY que ya está escrito en
--   el historial de includes/planes_lib.php.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_planes_usuario $$
CREATE PROCEDURE sp_planes_usuario (
    IN p_usuario_id INT
)
BEGIN
    SELECT p.id, p.nombre, p.destino, p.lat, p.lng,
           p.fecha_inicio, p.fecha_fin, p.portada_url, p.presupuesto,
           p.creado_en, p.updated_at, m.rol,
           fn_estado_plan(p.fecha_inicio, p.fecha_fin) AS est_clave,
           fn_sitios_plan(p.id)                        AS lugares,
           -- Las dos fuentes, igual que fn_total_gastos: si el total
           -- suma el precio de los lugares, el numero tiene que
           -- contarlos, o la ficha diria «0 gastos» junto a un total
           -- que no es cero.
           ((SELECT COUNT(*) FROM plan_gastos g
              WHERE g.plan_id = p.id)
          + (SELECT COUNT(*) FROM plan_items i
              WHERE i.plan_id = p.id AND i.precio > 0)) AS n_gastos,
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
END $$

-- ------------------------------------------------------------
-- sp_actualizar_plan  (el UPDATE de planes)
--   Escribe la fila COMPLETA. Quien llama (api/plan_update.php) ya
--   tiene la fila actual —planAccess() la devuelve entera— y fusiona
--   en PHP los campos que cambiaron sobre los que no. Así NULL
--   significa NULL de verdad: se puede quitar una fecha o una
--   portada, cosa que un COALESCE(parámetro, columna) no permitiría
--   distinguir de «no tocar».
--   destino no está: la pantalla de edición no lo cambia, igual que
--   hoy no está entre los campos permitidos del endpoint.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_actualizar_plan $$
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
END $$

-- ------------------------------------------------------------
-- sp_borrar_plan  (el DELETE de planes… o la salida de un miembro)
--   El mismo botón hace dos cosas según el rol REAL, que se resuelve
--   aquí con fn_rol_en_plan y no con lo que diga el navegador:
--     · propietario  → borra el plan entero (las FK en cascada
--       arrastran items, gastos, listas, miembros e invitaciones, y
--       viajes_usuario conserva su historial con plan_id a NULL);
--     · editor/lector → sólo quita su propia fila de plan_miembros.
--   Devuelve la acción ('borrado' | 'salida') como result set.
--   Si el usuario no es miembro: SQLSTATE 45000 'SIN_ACCESO'.
--   El borrado dispara trg_plan_borrado, que deja constancia en
--   planes_borrados ANTES de que la fila desaparezca.
--   CONTRATO: no llamar dentro de otra transacción (hace COMMIT).
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_borrar_plan $$
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
        -- viajes_usuario es historial: se desvincula, no se borra.
        UPDATE viajes_usuario SET plan_id = NULL WHERE plan_id = p_plan_id;
        DELETE FROM planes WHERE id = p_plan_id;
        COMMIT;
        SELECT 'borrado' AS accion;
    ELSE
        DELETE FROM plan_miembros
         WHERE plan_id = p_plan_id AND usuario_id = p_usuario_id;
        SELECT 'salida' AS accion;
    END IF;
END $$


-- ------------------------------------------------------------
-- sp_registrar_intento(email, ip, exito)
--   Deja constancia de un intento de inicio de sesión y mantiene
--   limpia la tabla. Hace tres cosas:
--
--     1. Anota el intento.
--     2. Si acertó, borra los fallos anteriores de ese correo. Quien
--        se equivoca dos veces y a la tercera entra no debe quedarse
--        con dos fallos colgando hasta que caduquen.
--     3. Purga lo anterior a un día. La ventana que mira
--        fn_login_bloqueado() es de 15 minutos, así que un día sobra
--        y la tabla no crece sin fin.
--
--   La purga va aquí y no en un evento programado porque el planificador
--   de eventos de MariaDB viene apagado en XAMPP, y una tarea que hay
--   que acordarse de encender es una tarea que no se ejecuta.
--
--   Lo llama login.php después de comprobar la contraseña.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_registrar_intento $$
CREATE PROCEDURE sp_registrar_intento (
    IN p_email VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_ip    VARCHAR(45)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_exito TINYINT(1)
)
BEGIN
    INSERT INTO intentos_login (email, ip, exito)
    VALUES (p_email, p_ip, IF(p_exito = 1, 1, 0));

    IF p_exito = 1 THEN
        DELETE FROM intentos_login
         WHERE email = p_email AND exito = 0;
    END IF;

    DELETE FROM intentos_login
     WHERE creado_en < DATE_SUB(NOW(), INTERVAL 1 DAY);
END $$


-- ════════════════════════════════════════════════════════════
--  TRIGGERS
-- ════════════════════════════════════════════════════════════

-- ════════════════════════════════════════════════════════════
--  COLABORACIÓN — el testigo de cambio del viaje
--  Fase 2 de Reportes_md/PLAN_colaboracion.md
-- ════════════════════════════════════════════════════════════

-- ------------------------------------------------------------
-- sp_tocar_plan(plan_id)
--   Sube planes.rev y mueve planes.updated_at. Es la única línea que
--   escriben los veinte disparadores de más abajo, y por eso vive
--   aquí: si mañana hay que cambiar cómo se marca un plan como
--   modificado, se cambia en UN sitio y no en veinte.
--
--   `updated_at` se pone explícitamente aunque la columna ya lleve
--   ON UPDATE current_timestamp: así se lee la intención sin tener
--   que ir a mirar la definición de la tabla.
--
--   No hay riesgo de recursión: `planes` no tiene ningún disparador
--   de UPDATE, sólo el BEFORE DELETE de auditoría.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_tocar_plan $$
CREATE PROCEDURE sp_tocar_plan (IN p_plan_id INT)
BEGIN
    IF p_plan_id IS NOT NULL THEN
        UPDATE planes
           SET rev = rev + 1,
               updated_at = CURRENT_TIMESTAMP
         WHERE id = p_plan_id;
    END IF;
END $$

-- ------------------------------------------------------------
-- fn_plan_de_item(item_id) → INT
-- fn_plan_de_lista(lista_id) → INT
--   Las tablas NIETAS no saben a qué plan pertenecen: plan_item_gasto
--   y plan_item_reacciones sólo guardan item_id, y plan_lista_items
--   sólo lista_id. Sus disparadores necesitan el plan_id para llamar
--   a sp_tocar_plan, y estas dos funciones lo resuelven.
--
--   Devuelven NULL si la fila madre ya no está; sp_tocar_plan sabe
--   tragarse un NULL sin hacer nada.
-- ------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_plan_de_item $$
CREATE FUNCTION fn_plan_de_item(p_item_id INT)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN (SELECT plan_id FROM plan_items WHERE id = p_item_id);
END $$

DROP FUNCTION IF EXISTS fn_plan_de_lista $$
CREATE FUNCTION fn_plan_de_lista(p_lista_id INT)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN (SELECT plan_id FROM plan_listas WHERE id = p_lista_id);
END $$


-- ------------------------------------------------------------
-- trg_item_ins / trg_item_upd / trg_item_del  (datos derivados)
--   «Última modificación» en mis_planes.php lee planes.updated_at, y
--   el orden «Más nuevos» ordena por esa columna. Pero NINGÚN
--   endpoint de contenido toca planes: añadir diez sitios a un plan
--   no movía su fecha. Estos tres triggers lo arreglan en la única
--   capa que ve todas las escrituras, vengan del endpoint que vengan.
--   Son tres porque en MariaDB un trigger es por evento y por tabla.
--   Ojo: los borrados EN CASCADA (al borrar el plan entero) no
--   disparan triggers en MariaDB, y aquí eso es exactamente lo que
--   conviene: el plan que muere no necesita que le toquen la fecha.
-- ------------------------------------------------------------
--   Desde la fase 2 hacen ADEMÁS lo suyo con planes.rev, llamando a
--   sp_tocar_plan en vez de escribir el UPDATE a mano.
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_item_ins_toca_plan $$
CREATE TRIGGER trg_item_ins_toca_plan
AFTER INSERT ON plan_items
FOR EACH ROW
BEGIN
    CALL sp_tocar_plan(NEW.plan_id);
END $$

DROP TRIGGER IF EXISTS trg_item_upd_toca_plan $$
CREATE TRIGGER trg_item_upd_toca_plan
AFTER UPDATE ON plan_items
FOR EACH ROW
BEGIN
    CALL sp_tocar_plan(NEW.plan_id);
END $$

DROP TRIGGER IF EXISTS trg_item_del_toca_plan $$
CREATE TRIGGER trg_item_del_toca_plan
AFTER DELETE ON plan_items
FOR EACH ROW
BEGIN
    CALL sp_tocar_plan(OLD.plan_id);
END $$

-- ------------------------------------------------------------
-- Los otros diecisiete: todo lo demás que puede cambiar en un viaje
--
--   Son tantos porque en MariaDB un disparador es POR TABLA Y POR
--   EVENTO: no existe «uno para insertar, borrar y actualizar». Cada
--   uno es de tres líneas y todos hacen lo mismo, así que la lista es
--   larga pero no tiene nada que entender.
--
--   Los borrados EN CASCADA no disparan nada (comprobado en MariaDB
--   10.4), y aquí eso es justo lo que conviene: al borrar un viaje
--   entero, sus hijas y nietas se van sin intentar tocar una fila de
--   `planes` que está desapareciendo. Y al borrar un LUGAR, su reparto
--   y sus reacciones caen con él sin sumar de más: basta con el
--   disparador del propio lugar.
-- ------------------------------------------------------------

-- ── plan_gastos ──
-- El BEFORE INSERT de validación (trg_gasto_valido) sigue donde
-- estaba; éste es AFTER, así que conviven sin estorbarse.
DROP TRIGGER IF EXISTS trg_gasto_ins_toca_plan $$
CREATE TRIGGER trg_gasto_ins_toca_plan
AFTER INSERT ON plan_gastos FOR EACH ROW
BEGIN CALL sp_tocar_plan(NEW.plan_id); END $$

DROP TRIGGER IF EXISTS trg_gasto_upd_toca_plan $$
CREATE TRIGGER trg_gasto_upd_toca_plan
AFTER UPDATE ON plan_gastos FOR EACH ROW
BEGIN CALL sp_tocar_plan(NEW.plan_id); END $$

DROP TRIGGER IF EXISTS trg_gasto_del_toca_plan $$
CREATE TRIGGER trg_gasto_del_toca_plan
AFTER DELETE ON plan_gastos FOR EACH ROW
BEGIN CALL sp_tocar_plan(OLD.plan_id); END $$

-- ── plan_listas ──
DROP TRIGGER IF EXISTS trg_lista_ins_toca_plan $$
CREATE TRIGGER trg_lista_ins_toca_plan
AFTER INSERT ON plan_listas FOR EACH ROW
BEGIN CALL sp_tocar_plan(NEW.plan_id); END $$

DROP TRIGGER IF EXISTS trg_lista_upd_toca_plan $$
CREATE TRIGGER trg_lista_upd_toca_plan
AFTER UPDATE ON plan_listas FOR EACH ROW
BEGIN CALL sp_tocar_plan(NEW.plan_id); END $$

DROP TRIGGER IF EXISTS trg_lista_del_toca_plan $$
CREATE TRIGGER trg_lista_del_toca_plan
AFTER DELETE ON plan_listas FOR EACH ROW
BEGIN CALL sp_tocar_plan(OLD.plan_id); END $$

-- ── plan_lista_items (nieta: pasa por fn_plan_de_lista) ──
DROP TRIGGER IF EXISTS trg_litem_ins_toca_plan $$
CREATE TRIGGER trg_litem_ins_toca_plan
AFTER INSERT ON plan_lista_items FOR EACH ROW
BEGIN CALL sp_tocar_plan(fn_plan_de_lista(NEW.lista_id)); END $$

DROP TRIGGER IF EXISTS trg_litem_upd_toca_plan $$
CREATE TRIGGER trg_litem_upd_toca_plan
AFTER UPDATE ON plan_lista_items FOR EACH ROW
BEGIN CALL sp_tocar_plan(fn_plan_de_lista(NEW.lista_id)); END $$

DROP TRIGGER IF EXISTS trg_litem_del_toca_plan $$
CREATE TRIGGER trg_litem_del_toca_plan
AFTER DELETE ON plan_lista_items FOR EACH ROW
BEGIN CALL sp_tocar_plan(fn_plan_de_lista(OLD.lista_id)); END $$

-- ── plan_item_gasto (nieta: pasa por fn_plan_de_item) ──
DROP TRIGGER IF EXISTS trg_reparto_ins_toca_plan $$
CREATE TRIGGER trg_reparto_ins_toca_plan
AFTER INSERT ON plan_item_gasto FOR EACH ROW
BEGIN CALL sp_tocar_plan(fn_plan_de_item(NEW.item_id)); END $$

DROP TRIGGER IF EXISTS trg_reparto_upd_toca_plan $$
CREATE TRIGGER trg_reparto_upd_toca_plan
AFTER UPDATE ON plan_item_gasto FOR EACH ROW
BEGIN CALL sp_tocar_plan(fn_plan_de_item(NEW.item_id)); END $$

DROP TRIGGER IF EXISTS trg_reparto_del_toca_plan $$
CREATE TRIGGER trg_reparto_del_toca_plan
AFTER DELETE ON plan_item_gasto FOR EACH ROW
BEGIN CALL sp_tocar_plan(fn_plan_de_item(OLD.item_id)); END $$

-- ── plan_item_reacciones (nieta: pasa por fn_plan_de_item) ──
-- Hace falta el de UPDATE, y no es evidente: api/plan_reacciones.php
-- cambia de emoji con INSERT ... ON DUPLICATE KEY UPDATE, que sobre
-- una fila que ya existe dispara SÓLO el AFTER UPDATE. Comprobado, y
-- de paso: reenviar el MISMO emoji no dispara nada, así que `rev` no
-- se mueve cuando en realidad no cambió nada.
DROP TRIGGER IF EXISTS trg_react_ins_toca_plan $$
CREATE TRIGGER trg_react_ins_toca_plan
AFTER INSERT ON plan_item_reacciones FOR EACH ROW
BEGIN CALL sp_tocar_plan(fn_plan_de_item(NEW.item_id)); END $$

DROP TRIGGER IF EXISTS trg_react_upd_toca_plan $$
CREATE TRIGGER trg_react_upd_toca_plan
AFTER UPDATE ON plan_item_reacciones FOR EACH ROW
BEGIN CALL sp_tocar_plan(fn_plan_de_item(NEW.item_id)); END $$

DROP TRIGGER IF EXISTS trg_react_del_toca_plan $$
CREATE TRIGGER trg_react_del_toca_plan
AFTER DELETE ON plan_item_reacciones FOR EACH ROW
BEGIN CALL sp_tocar_plan(fn_plan_de_item(OLD.item_id)); END $$

-- ── plan_miembros: SÓLO al entrar y al salir ──
--
-- ⚠ AQUÍ NO VA UN DISPARADOR DE UPDATE, Y ES A PROPÓSITO.
-- La fase 6 (presencia: «quién está mirando ahora») escribirá en
-- plan_miembros EN CADA SONDEO. Si ese UPDATE subiera `rev`, cada
-- sondeo contaría como una novedad, cada cliente se recargaría el
-- plan entero, ese recargado generaría más sondeos... y se realimenta
-- hasta fundir el servidor. Quien añada aquí un trg_miembro_upd
-- estará encendiendo esa mecha.
DROP TRIGGER IF EXISTS trg_miembro_ins_toca_plan $$
CREATE TRIGGER trg_miembro_ins_toca_plan
AFTER INSERT ON plan_miembros FOR EACH ROW
BEGIN CALL sp_tocar_plan(NEW.plan_id); END $$

DROP TRIGGER IF EXISTS trg_miembro_del_toca_plan $$
CREATE TRIGGER trg_miembro_del_toca_plan
AFTER DELETE ON plan_miembros FOR EACH ROW
BEGIN CALL sp_tocar_plan(OLD.plan_id); END $$

-- ------------------------------------------------------------
-- trg_plan_borrado  (auditoría)
--   Copia los datos esenciales del plan a planes_borrados ANTES de
--   que el DELETE lo haga desaparecer. Es la única constancia que
--   queda de la acción más destructiva de la aplicación —por algo la
--   interfaz obliga a mantener pulsado cinco segundos—.
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_plan_borrado $$
CREATE TRIGGER trg_plan_borrado
BEFORE DELETE ON planes
FOR EACH ROW
BEGIN
    INSERT INTO planes_borrados (plan_id, nombre, destino, usuario_id)
    VALUES (OLD.id, OLD.nombre, OLD.destino, OLD.usuario_id);
END $$

-- ------------------------------------------------------------
-- trg_gasto_valido  (validación)
--   Rechaza importes NEGATIVOS con SQLSTATE 45000. Ningún cliente
--   puede saltárselo, valide PHP lo que valide.
--   El cero se permite a propósito: api/plan_gastos.php acepta hoy
--   un gasto de 0 (una actividad gratuita que se quiere apuntar) y
--   la base no debe volverse más estricta que la aplicación.
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_gasto_valido $$
CREATE TRIGGER trg_gasto_valido
BEFORE INSERT ON plan_gastos
FOR EACH ROW
BEGIN
    IF NEW.monto < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'GASTO_NEGATIVO';
    END IF;
END $$

DELIMITER ;


-- ────────────────────────────────────────────────────────────
--  Verificación: debe imprimir 7 funciones, 7 procedimientos y
--  22 triggers. Si algún número no cuadra, algo de arriba falló.
--
--  Los 22 salen de: 5 de antes (3 sobre plan_items, la auditoría del
--  borrado y la validación del gasto) + 17 de la fase 2 — tres por
--  cada una de plan_gastos, plan_listas, plan_lista_items,
--  plan_item_gasto y plan_item_reacciones, y sólo dos en
--  plan_miembros, que no lleva el de UPDATE a propósito.
-- ────────────────────────────────────────────────────────────
SELECT ROUTINE_TYPE AS tipo, COUNT(*) AS cuantas
  FROM information_schema.ROUTINES
 WHERE ROUTINE_SCHEMA = DATABASE()
 GROUP BY ROUTINE_TYPE;

SELECT COUNT(*) AS triggers
  FROM information_schema.TRIGGERS
 WHERE TRIGGER_SCHEMA = DATABASE();
