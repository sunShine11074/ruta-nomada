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
  CONSTRAINT `viajes_usuario_ibfk_3` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`)
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

CREATE TABLE `plan_invitaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'SHA-256 del token de invitación',
  `rol` enum('editor','lector') NOT NULL DEFAULT 'editor',
  `email` varchar(255) DEFAULT NULL,
  `usada` tinyint(1) NOT NULL DEFAULT 0,
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

-- ── Procedimiento almacenado que usa register.php ──

-- Índice único sobre el correo: es la verdadera garantía contra
-- registros duplicados simultáneos (el chequeo del SP da el mensaje
-- amigable; el índice cierra la condición de carrera).
ALTER TABLE usuarios ADD UNIQUE INDEX IF NOT EXISTS idx_usuarios_email (email);

DELIMITER $$

-- ------------------------------------------------------------
-- sp_registrar_usuario
--   Verifica que el correo no exista e inserta al usuario en una
--   sola operación. Devuelve el id generado como result set.
--   La contraseña llega YA hasheada (bcrypt se calcula en PHP;
--   la contraseña en texto plano nunca viaja a MySQL).
--   Errores: SQLSTATE 45000 con mensaje 'EMAIL_DUPLICADO'.
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

DELIMITER ;
