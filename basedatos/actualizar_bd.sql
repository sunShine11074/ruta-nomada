-- ============================================================
--   actualizar_bd.sql — Pon tu base al día | Ruta Nómada
--
--   PARA QUIÉN ES
--   Para quien ya tiene la base `ruta_nomada` instalada de una
--   versión anterior y NO quiere perder sus usuarios ni sus planes.
--   Si vas a empezar de cero, no uses este archivo: importa
--   basedatos/instalar.sql, que ya lo trae todo.
--
--   CÓMO USARLO — un solo comando en la terminal de VS Code
--
--     mysql -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"
--
--   Si `mysql` no se reconoce, usa la ruta completa de XAMPP:
--
--     & "C:\xampp\mysql\bin\mysql.exe" -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"
--
--   (Se usa -e "source ..." y no  < archivo  a propósito: la
--   terminal de VS Code en Windows suele ser PowerShell, y PowerShell
--   NO admite el operador `<`. Con "source" funciona en PowerShell,
--   en CMD y en Git Bash por igual.)
--
--   ES SEGURO EJECUTARLO DOS VECES
--   Todo va con IF NOT EXISTS: lo que ya esté, se queda como está.
--   No borra ni vacía ninguna tabla. Tus datos no se tocan.
--
--   QUÉ CAMBIA (comparado columna por columna con la versión vieja)
--   · plan_items gana 5 columnas: modo_viaje, moneda, gasto_cat,
--     gasto_desc y gasto_modo.
--   · Se crea la tabla plan_item_gasto.
--   · Se crean tramo_cache y ruta_uso (la caché de rutas del mapa).
--   · Se crea intentos_login (el freno a la fuerza bruta del login).
--   · Se crea usuario_fotos (la galería de «Cambiar foto»).
--   · plan_invitaciones gana 3 columnas: usos, usos_max y token_claro,
--     para que el enlace de invitación se pueda compartir con varias
--     personas en vez de morir con la primera.
--   · planes gana `rev` y plan_items gana `ver`: el testigo de cambio
--     con el que la colaboración detecta novedades sin recargar.
--   Nada más: ni tipos distintos, ni índices nuevos, ni cambios en
--   el procedimiento sp_registrar_usuario.
-- ============================================================

SET NAMES utf8mb4;

-- ── 1. plan_items: transporte y gasto del lugar ─────────────
--
-- modo_viaje  → cómo se va de este lugar al siguiente. Lo usa el
--               cálculo de rutas y el selector a pie / coche / bici
--               que aparece entre las tarjetas del itinerario.
--               OJO: esta columna faltaba en el instalar.sql viejo
--               pero el código YA la consultaba, así que sin ella la
--               vista del plan fallaba al abrirse.
-- moneda      → divisa del importe (códigos de includes/currency.php).
-- gasto_cat   → categoría del gasto: actividad, comida, compras...
-- gasto_desc  → descripción libre que escribe la persona.
-- gasto_modo  → cómo se reparte: no dividir / todos / individuos.

ALTER TABLE `plan_items`
  ADD COLUMN IF NOT EXISTS `modo_viaje` enum('DRIVE','WALK','BICYCLE') DEFAULT NULL
      COMMENT 'Cómo se va de este lugar al siguiente' AFTER `duracion`;

ALTER TABLE `plan_items`
  ADD COLUMN IF NOT EXISTS `moneda` char(3) NOT NULL DEFAULT 'MXN'
      COMMENT 'Divisa del importe; códigos de includes/currency.php' AFTER `precio`;

ALTER TABLE `plan_items`
  ADD COLUMN IF NOT EXISTS `gasto_cat` varchar(24) DEFAULT NULL
      COMMENT 'Categoría del gasto (actividad, comida, ...)' AFTER `moneda`;

ALTER TABLE `plan_items`
  ADD COLUMN IF NOT EXISTS `gasto_desc` varchar(500) DEFAULT NULL
      COMMENT 'Descripción libre del gasto' AFTER `gasto_cat`;

ALTER TABLE `plan_items`
  ADD COLUMN IF NOT EXISTS `gasto_modo` enum('no','todos','individuos') NOT NULL DEFAULT 'no'
      COMMENT 'Cómo se divide el coste' AFTER `gasto_desc`;


-- ── 2. plan_item_gasto: quién paga qué parte ────────────────
--
-- Una fila por persona a la que le toca parte del coste de un lugar.
-- El color es el de su porción en la gráfica de dona del modal
-- "Añadir gasto".
--
-- Las dos claves foráneas van con ON DELETE CASCADE: si se borra el
-- lugar o la persona, su reparto se va con él y no quedan filas
-- huérfanas.

CREATE TABLE IF NOT EXISTS `plan_item_gasto` (
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


-- ── 3. Caché de rutas: por qué salen punteadas ──────────────
--
-- api/ruta.php consulta estas dos tablas ANTES de llamar a Google. Si no
-- existen, la consulta falla, el navegador no recibe geometría y dibuja
-- una recta PUNTEADA entre cada par de lugares en vez de la ruta real
-- por carretera. Es exactamente el síntoma "las rutas están punteadas".
--
-- Estaban sueltas en migrate_rutas.sql y no entraban ni por instalar.sql
-- ni por aquí, así que faltaban en TODA instalación que no fuera la
-- original. Ya están en los dos sitios.

CREATE TABLE IF NOT EXISTS `tramo_cache` (
  `hash` char(32) NOT NULL COMMENT 'md5(modo|origen>destino)',
  `pts` mediumtext DEFAULT NULL COMMENT 'JSON [[lat,lng],...]; NULL si no hubo ruta',
  `ok` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = Google dijo que no hay ruta',
  `metros` int(11) DEFAULT NULL,
  `segundos` int(11) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`hash`),
  KEY `idx_creado` (`creado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ruta_uso` (
  `mes` char(7) NOT NULL COMMENT 'AAAA-MM',
  `n` int(11) NOT NULL DEFAULT 0 COMMENT 'peticiones enviadas a Google ese mes',
  PRIMARY KEY (`mes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 3b. El freno a la fuerza bruta en el login ──────────────
--
-- POR QUÉ ESTÁ AQUÍ, SI TAMBIÉN LA CREA rutinas.sql
-- Porque el diagnóstico, al echarla en falta, manda a ejecutar ESTE
-- archivo, y hasta hoy no la creaba: quien seguía la pista corría el
-- comando, volvía a comprobar y la tabla seguía sin aparecer. Le pasó
-- a una compañera el 13/08/2026. La instrucción y el archivo tienen
-- que decir lo mismo.
--
-- Sin ella, includes/intentos.php falla al consultar y -como falla en
-- abierto a propósito- el límite de intentos queda DESACTIVADO EN
-- SILENCIO: se puede probar contraseñas sin freno y nada avisa.
--
-- ip admite 45 caracteres porque ese es el largo de una IPv6 escrita
-- del todo, y en localhost la de siempre es ::1.

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

-- ── 5. El enlace de invitación se puede compartir ───────────
--
-- La ventana «Invita a compañeros de viaje» enseña un enlace para
-- repartir entre varias personas, pero el token era de UN SOLO USO:
-- la segunda persona que lo abría recibía «inválido, ya fue usado».
--
-- usos / usos_max separan los dos tipos de invitación que ya convivían
-- en la tabla: la de correo (un uso, dirigida a alguien) y la de
-- compartir (email NULL, usos_max NULL = sin límite).
--
-- token_claro guarda el token legible SÓLO en la de compartir, porque
-- la ventana tiene que poder volver a enseñar el enlace y de un
-- SHA-256 no se saca. Las de correo siguen siendo únicamente hash.
-- El razonamiento entero está en basedatos/migrate_invitar.sql.

ALTER TABLE `plan_invitaciones`
  ADD COLUMN IF NOT EXISTS `usos` smallint(5) unsigned NOT NULL DEFAULT 0
      COMMENT 'Cuánta gente ha entrado ya por esta invitación' AFTER `usada`;

ALTER TABLE `plan_invitaciones`
  ADD COLUMN IF NOT EXISTS `usos_max` smallint(5) unsigned DEFAULT 1
      COMMENT 'NULL = sin límite (enlace para compartir)' AFTER `usos`;

ALTER TABLE `plan_invitaciones`
  ADD COLUMN IF NOT EXISTS `token_claro` varchar(64) DEFAULT NULL
      COMMENT 'Token legible; SÓLO para el enlace de compartir (email IS NULL)' AFTER `token_hash`;

UPDATE `plan_invitaciones` SET `usos` = 1 WHERE `usada` = 1 AND `usos` = 0;


-- ── 6. El testigo de cambio del viaje (colaboración) ────────
--
-- Sin estas dos columnas no hay forma barata de preguntar «¿hay
-- novedades en este viaje?»: habría que traerse el plan entero cada
-- pocos segundos.
--
-- planes.rev     cambia cuando cambia CUALQUIER COSA del viaje. Lo
--                mueven 20 disparadores; nadie lo escribe desde PHP.
--                No sirve updated_at: `timestamp` tiene resolución de
--                UN SEGUNDO, y dos ediciones dentro del mismo segundo
--                serían indistinguibles.
-- plan_items.ver la versión de un lugar concreto, para el bloqueo
--                optimista. La sube la sentencia UPDATE del endpoint,
--                nunca un disparador.
--
-- ⚠ Esto sólo trae las COLUMNAS. Los disparadores que mueven `rev`
-- están en basedatos/rutinas.sql: hay que ejecutarlo también, o se
-- quedan quietas para siempre. herramientas/actualizar.bat hace los dos.

ALTER TABLE `planes`
  ADD COLUMN IF NOT EXISTS `rev` bigint(20) unsigned NOT NULL DEFAULT 0
      COMMENT 'Testigo de cambio del viaje; lo mueven los disparadores' AFTER `updated_at`;

ALTER TABLE `plan_items`
  ADD COLUMN IF NOT EXISTS `ver` int(10) unsigned NOT NULL DEFAULT 1
      COMMENT 'Version del lugar para el bloqueo optimista' AFTER `plan_id`;

ALTER TABLE `planes`
  ADD COLUMN IF NOT EXISTS `ver` int(10) unsigned NOT NULL DEFAULT 1
      COMMENT 'Version del NOMBRE del viaje para el bloqueo optimista' AFTER `rev`;


-- ── 7. Comprobación ─────────────────────────────────────────
-- Si todo salió bien, esto imprime 5 columnas nuevas, 5 tablas nuevas
-- y 3 columnas nuevas en plan_invitaciones.
--
-- OJO: esto NO comprueba las rutinas (funciones, procedimientos y
-- disparadores). Ésas viven en basedatos/rutinas.sql y se instalan
-- aparte. Quien use herramientas/actualizar.bat las tiene cubiertas:
-- ese archivo ejecuta los dos.

SELECT
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plan_items'
       AND COLUMN_NAME IN ('modo_viaje','moneda','gasto_cat','gasto_desc','gasto_modo'))
    AS `columnas_nuevas_en_plan_items (deben ser 5)`,
  (SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME IN ('plan_item_gasto','tramo_cache','ruta_uso','intentos_login','usuario_fotos'))
    AS `tablas_nuevas (deben ser 5)`,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plan_invitaciones'
       AND COLUMN_NAME IN ('usos','usos_max','token_claro'))
    AS `columnas_nuevas_en_plan_invitaciones (deben ser 3)`,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND ((TABLE_NAME = 'planes' AND COLUMN_NAME = 'rev')
         OR (TABLE_NAME = 'plan_items' AND COLUMN_NAME = 'ver')))
    AS `testigo_de_cambio (deben ser 2)`,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'planes' AND COLUMN_NAME = 'ver')
    AS `candado_del_nombre (debe ser 1)`;
