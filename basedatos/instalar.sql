-- ============================================================
--   instalar.sql — Instalación completa | Ruta Nómada
--
--   ESTE ES EL ÚNICO ARCHIVO QUE HACE FALTA para dejar la base de
--   datos lista: crea las 18 tablas, el procedimiento almacenado y
--   los destinos de ejemplo. NO trae usuarios ni planes; cada quien
--   se registra en su propia copia.
--
--   Los migrate_*.sql de esta carpeta son el historial de cómo fue
--   creciendo el esquema. Si partes de cero NO los necesitas:
--   este archivo ya los incluye todos.
--
--   EL ORDEN DE LAS TABLAS NO ES ALFABÉTICO Y ES A PROPÓSITO. Cada
--   tabla se crea DESPUÉS de aquellas a las que apunta con una clave
--   foránea, y los DROP van al principio en el orden contrario. Antes
--   estaban por orden alfabético, así que `ai_uso` —que apunta a
--   `usuarios` y a `planes`— se creaba la primera de todas. Por
--   terminal colaba, porque el SET FOREIGN_KEY_CHECKS = 0 de abajo
--   silencia la comprobación; pero phpMyAdmin trae marcada su casilla
--   "Habilitar la revisión de claves foráneas" e ignora ese SET, y la
--   importación moría con
--       #1005 ... (Error: 150 "Foreign key constraint is incorrectly formed").
--   Con el orden correcto el archivo entra igual de bien por las dos
--   vías, con la comprobación encendida o apagada.
--
--   CÓMO USARLO
--   1. Crea la base (una sola vez):
--        CREATE DATABASE ruta_nomada
--          CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   2. Impórtalo:
--        · phpMyAdmin → base ruta_nomada → Importar → este archivo
--        · o en terminal:
--            mysql -u root ruta_nomada < basedatos/instalar.sql
--
--   Tres detalles que parecen menores y no lo son:
--
--   · CREA LA BASE CON utf8mb4_unicode_ci, tal cual dice arriba. Todas
--     las tablas de este archivo usan esa colación, y el procedimiento
--     almacenado hereda la de la BASE para sus parámetros. Si creas la
--     base con otra (utf8mb4_general_ci, por ejemplo), el parámetro y
--     la columna usuarios.email quedan en colaciones distintas y el
--     registro truena con
--         "Illegal mix of collations ... for operation '='".
--     Es un error confuso porque parece un problema del PHP y no lo es.
--
--   · La columna emoji de plan_item_reacciones va en utf8mb4_bin. Con
--     la colación normal MySQL considera IGUALES a todos los emojis, así
--     que el GROUP BY de las reacciones los fundiría todos en uno solo.
--
--   · El procedimiento va SIN "DEFINER": con él, el import falla en
--     cualquier servidor donde no exista el usuario root@localhost.
--     register.php lo necesita, así que sin él nadie puede registrarse.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ── Se borran todas primero, hijas antes que madres ─────────
--
-- El orden importa: con la revisión de claves foráneas activada
-- (phpMyAdmin la trae marcada por defecto) no se puede borrar una
-- tabla de la que otra depende, ni crear una que apunte a otra que
-- todavía no existe.

DROP TABLE IF EXISTS `plan_lista_items`;
DROP TABLE IF EXISTS `plan_item_reacciones`;
DROP TABLE IF EXISTS `plan_item_gasto`;
DROP TABLE IF EXISTS `plan_miembros`;
DROP TABLE IF EXISTS `plan_listas`;
DROP TABLE IF EXISTS `plan_items`;
DROP TABLE IF EXISTS `plan_invitaciones`;
DROP TABLE IF EXISTS `plan_gastos`;
DROP TABLE IF EXISTS `plan_destinos`;
DROP TABLE IF EXISTS `ai_uso`;
DROP TABLE IF EXISTS `viajes_usuario`;
DROP TABLE IF EXISTS `planes`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `favoritos`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `destinos`;
DROP TABLE IF EXISTS `ruta_uso`;
DROP TABLE IF EXISTS `tramo_cache`;


-- ── Y se crean al revés, cada madre antes que sus hijas ─────


-- ── Caché de rutas del itinerario (api/ruta.php) ─────────────
--
-- Sin estas dos tablas api/ruta.php revienta en su primera consulta y
-- el mapa se queda con líneas rectas PUNTEADAS en vez de la ruta real
-- por carretera. Estaban sólo en migrate_rutas.sql, así que cualquier
-- instalación limpia nacía sin ellas.
--
-- Guarda la geometría de cada TRAMO (par de lugares consecutivos) para
-- no volver a pedírsela a Google. Al reordenar un día, los tramos que
-- no cambiaron salen de aquí y cuestan cero.
--
-- OJO con el TTL de 25 días de api/ruta.php: los términos de Google
-- Maps Platform permiten cachear coordenadas derivadas 30 días como
-- mucho. Los place_id sí son permanentes; lo que caduca es la geometría.
CREATE TABLE `tramo_cache` (
  `hash` char(32) NOT NULL COMMENT 'md5(modo|origen>destino)',
  `pts` mediumtext DEFAULT NULL COMMENT 'JSON [[lat,lng],...]; NULL si no hubo ruta',
  `ok` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = Google dijo que no hay ruta; se cachea para no reintentar siempre',
  `metros` int(11) DEFAULT NULL,
  `segundos` int(11) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`hash`),
  KEY `idx_creado` (`creado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Contador del gasto del mes. Vive en el servidor y no en el navegador
-- porque localStorage se borra y es por equipo: no protege de nada.
CREATE TABLE `ruta_uso` (
  `mes` char(7) NOT NULL COMMENT 'AAAA-MM',
  `n` int(11) NOT NULL DEFAULT 0 COMMENT 'peticiones enviadas a Google ese mes',
  PRIMARY KEY (`mes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `destinos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `categoria` enum('Cultura','Romance','Aventura','Descubrimiento') DEFAULT NULL,
  `precio_desde` decimal(10,2) DEFAULT NULL,
  `valoracion` decimal(2,1) DEFAULT NULL,
  `imagen_url` varchar(500) DEFAULT NULL,
  `estado` enum('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(120) NOT NULL DEFAULT '',
  `genero` enum('Hombre','Mujer','Otro') DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telefono` varchar(20) NOT NULL DEFAULT '',
  `fecha_nacimiento` date DEFAULT NULL,
  `nacionalidad` varchar(60) NOT NULL DEFAULT 'Mexicana',
  `estado` varchar(60) NOT NULL DEFAULT '',
  `ciudad` varchar(60) NOT NULL DEFAULT '',
  `lenguaje` varchar(30) NOT NULL DEFAULT 'Español',
  `divisa` varchar(10) NOT NULL DEFAULT 'MXN',
  `foto_perfil` varchar(500) DEFAULT NULL,
  `foto_banner` varchar(500) DEFAULT NULL,
  `pais` varchar(60) NOT NULL DEFAULT 'Mexico',
  `idioma` varchar(30) NOT NULL DEFAULT 'Espanol',
  `rol` enum('viajero','admin') DEFAULT 'viajero',
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `idx_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `favoritos` (
  `usuario_id` int(11) NOT NULL,
  `destino_id` int(11) NOT NULL,
  PRIMARY KEY (`usuario_id`,`destino_id`),
  KEY `destino_id` (`destino_id`),
  CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`destino_id`) REFERENCES `destinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_en` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `token_hash` (`token_hash`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `planes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `destino` varchar(120) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `privacidad` enum('solo','amigos','publico') NOT NULL DEFAULT 'solo',
  `portada_url` varchar(500) DEFAULT NULL,
  `estado` enum('borrador','activo','completado') DEFAULT 'borrador',
  `presupuesto` decimal(12,2) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dia_subtitulos` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `planes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `viajes_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `destino_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `destino_id` (`destino_id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `viajes_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `viajes_usuario_ibfk_2` FOREIGN KEY (`destino_id`) REFERENCES `destinos` (`id`),
  -- SET NULL y no CASCADE: viajes_usuario es el historial de destinos
  -- guardados. Al borrar un plan sólo se rompe el vínculo, no se pierde
  -- el recuerdo del destino. Sin cláusula era RESTRICT y habría hecho
  -- fallar api/plan_delete.php en cuanto plan_id dejara de ser NULL.
  CONSTRAINT `viajes_usuario_ibfk_3` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_uso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `tokens_in` int(11) NOT NULL DEFAULT 0,
  `tokens_out` int(11) NOT NULL DEFAULT 0,
  `modelo` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_fecha` (`usuario_id`,`creado`),
  KEY `fk_aiuso_plan` (`plan_id`),
  CONSTRAINT `fk_aiuso_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_aiuso_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_destinos` (
  `plan_id` int(11) NOT NULL,
  `destino_id` int(11) NOT NULL,
  PRIMARY KEY (`plan_id`,`destino_id`),
  KEY `destino_id` (`destino_id`),
  CONSTRAINT `plan_destinos_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `plan_destinos_ibfk_2` FOREIGN KEY (`destino_id`) REFERENCES `destinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_gastos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `categoria` enum('Alojamiento','Comida','Actividades','Transporte','Compras','Gasolina','Otro') NOT NULL DEFAULT 'Otro',
  `dia` tinyint(3) unsigned DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_plan_gastos` (`plan_id`),
  CONSTRAINT `fk_plangastos_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dos tipos de invitación conviven en esta tabla:
--   · por correo     → email = el destinatario, usos_max = 1
--   · para compartir → email NULL, usos_max NULL (sin límite), uno por
--     plan, y es el que enseña la ventana «Invita a compañeros».
-- token_claro se rellena SÓLO en el segundo caso, porque la ventana
-- tiene que poder volver a enseñar el enlace cada vez que se abre y de
-- un SHA-256 no se saca el token. El razonamiento entero, con las
-- alternativas descartadas, está en basedatos/migrate_invitar.sql.
CREATE TABLE `plan_invitaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'SHA-256 del token de invitación',
  `token_claro` varchar(64) DEFAULT NULL COMMENT 'Token legible; SÓLO para el enlace de compartir (email IS NULL)',
  `rol` enum('editor','lector') NOT NULL DEFAULT 'editor',
  `email` varchar(255) DEFAULT NULL,
  `usada` tinyint(1) NOT NULL DEFAULT 0,
  `usos` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Cuánta gente ha entrado ya por esta invitación',
  `usos_max` smallint(5) unsigned DEFAULT 1 COMMENT 'NULL = sin límite (enlace para compartir)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expira_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token_hash`),
  KEY `idx_plan` (`plan_id`),
  CONSTRAINT `fk_planinv_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `dia` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '0 = sin asignar (guardado)',
  `orden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `nombre` varchar(255) NOT NULL,
  `categoria` enum('hacer','rest','hotel','custom') NOT NULL DEFAULT 'custom',
  `hora` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `duracion` varchar(20) DEFAULT NULL,
  `modo_viaje` enum('DRIVE','WALK','BICYCLE') DEFAULT NULL COMMENT 'Cómo se va de este lugar al siguiente',
  `precio` decimal(10,2) DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'MXN' COMMENT 'Divisa del importe; códigos de includes/currency.php',
  `gasto_cat` varchar(24) DEFAULT NULL COMMENT 'Categoría del gasto (actividad, comida, ...)',
  `gasto_desc` varchar(500) DEFAULT NULL COMMENT 'Descripción libre del gasto',
  `gasto_modo` enum('no','todos','individuos') NOT NULL DEFAULT 'no' COMMENT 'Cómo se divide el coste',
  `nota` text DEFAULT NULL,
  `place_id` varchar(60) DEFAULT NULL COMMENT 'ID de Google Places (opcional)',
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `imagen_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_plan_dia` (`plan_id`,`dia`,`orden`),
  CONSTRAINT `fk_planitems_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_listas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL DEFAULT '',
  `tipo` enum('nota','check') NOT NULL,
  `texto` text DEFAULT NULL,
  `orden` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_planlistas` (`plan_id`),
  CONSTRAINT `fk_planlistas` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_miembros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `rol` enum('propietario','editor','lector') NOT NULL DEFAULT 'editor',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_plan_user` (`plan_id`,`usuario_id`),
  KEY `fk_planmiembros_user` (`usuario_id`),
  CONSTRAINT `fk_planmiembros_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_planmiembros_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_item_gasto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `color` char(7) DEFAULT NULL COMMENT 'Color de su porción en la dona, p. ej. #41A24D',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_item_usuario` (`item_id`,`usuario_id`),
  KEY `idx_gasto_usuario` (`usuario_id`),
  CONSTRAINT `fk_itemgasto_item` FOREIGN KEY (`item_id`) REFERENCES `plan_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_itemgasto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_item_reacciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `emoji` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_item_usuario` (`item_id`,`usuario_id`),
  KEY `idx_item` (`item_id`),
  KEY `fk_react_user` (`usuario_id`),
  CONSTRAINT `fk_react_item` FOREIGN KEY (`item_id`) REFERENCES `plan_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_react_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_lista_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lista_id` int(11) NOT NULL,
  `texto` varchar(500) NOT NULL,
  `hecho` tinyint(1) NOT NULL DEFAULT 0,
  `orden` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_planlistaitems` (`lista_id`),
  CONSTRAINT `fk_planlistaitems` FOREIGN KEY (`lista_id`) REFERENCES `plan_listas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Destinos de ejemplo (datos de referencia, sin usuarios) ──
INSERT INTO `destinos` (`id`, `nombre`, `descripcion`, `ciudad`, `pais`, `categoria`, `precio_desde`, `valoracion`, `imagen_url`, `estado`) VALUES (1,'Kyoto','Historia antigua y templos serenos.','Kyoto','Japón','Cultura',24800.00,4.9,NULL,'activo'),(2,'Santorini','Vistas espectaculares y puestas de sol.','Santorini','Grecia','Romance',31200.00,4.8,NULL,'activo'),(3,'Patagonia','Naturaleza salvaje en el fin del mundo.','Patagonia','Argentina','Aventura',28900.00,4.9,NULL,'activo'),(4,'Marruecos','Misticismo y colores vibrantes en el desierto.','Marrakech','Marruecos','Descubrimiento',19500.00,4.7,NULL,'activo');

-- ── Rutinas: funciones, procedimientos y triggers ──
--
-- COPIA de basedatos/rutinas.sql, que es el archivo canónico (y el
-- que ejecuta herramientas/actualizar.bat sobre bases existentes).
-- Está duplicado aquí porque la instalación limpia es UN solo archivo
-- importado por phpMyAdmin y debe seguir siéndolo. Si se toca una
-- rutina hay que tocarla en los dos.

-- Índice único sobre el correo: es la verdadera garantía contra
-- registros duplicados simultáneos (el chequeo del SP da el mensaje
-- amigable; el índice cierra la condición de carrera).
ALTER TABLE usuarios ADD UNIQUE INDEX IF NOT EXISTS idx_usuarios_email (email);

-- ── 4. Las fotos que sube cada persona ──────────────────────
-- La galeria de "Tus fotos" de la ventana Cambiar foto. Es del
-- USUARIO y no del plan: el frame dice "Tus fotos", asi que una foto
-- subida para un viaje se puede reutilizar en otro.
--
-- Guarda la RUTA relativa (img/portadas/xxx.jpg), no la imagen. El
-- archivo vive en disco; la base solo apunta.
--
-- ON DELETE CASCADE: si se borra la cuenta, sus fotos dejan de estar
-- listadas. Los archivos sueltos hay que limpiarlos aparte, y por eso
-- api/fotos.php borra el archivo ANTES de borrar la fila.

CREATE TABLE IF NOT EXISTS `usuario_fotos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `ruta` varchar(255) NOT NULL COMMENT 'relativa a la raiz del proyecto',
  `subida_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario_fecha` (`usuario_id`, `subida_en`),
  CONSTRAINT `fk_usuariofotos_user` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
--   Suma de plan_gastos del plan; 0 si no tiene ninguno.
-- ------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_total_gastos $$
CREATE FUNCTION fn_total_gastos(p_plan_id INT)
RETURNS DECIMAL(12,2)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN (SELECT COALESCE(SUM(monto), 0)
              FROM plan_gastos
             WHERE plan_id = p_plan_id);
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
DROP TRIGGER IF EXISTS trg_item_ins_toca_plan $$
CREATE TRIGGER trg_item_ins_toca_plan
AFTER INSERT ON plan_items
FOR EACH ROW
BEGIN
    UPDATE planes SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.plan_id;
END $$

DROP TRIGGER IF EXISTS trg_item_upd_toca_plan $$
CREATE TRIGGER trg_item_upd_toca_plan
AFTER UPDATE ON plan_items
FOR EACH ROW
BEGIN
    UPDATE planes SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.plan_id;
END $$

DROP TRIGGER IF EXISTS trg_item_del_toca_plan $$
CREATE TRIGGER trg_item_del_toca_plan
AFTER DELETE ON plan_items
FOR EACH ROW
BEGIN
    UPDATE planes SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.plan_id;
END $$

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
--  Verificación: debe imprimir 5 funciones, 6 procedimientos y
--  5 triggers. Si algún número no cuadra, algo de arriba falló.
-- ────────────────────────────────────────────────────────────
SELECT ROUTINE_TYPE AS tipo, COUNT(*) AS cuantas
  FROM information_schema.ROUTINES
 WHERE ROUTINE_SCHEMA = DATABASE()
 GROUP BY ROUTINE_TYPE;

SELECT COUNT(*) AS triggers
  FROM information_schema.TRIGGERS
 WHERE TRIGGER_SCHEMA = DATABASE();
