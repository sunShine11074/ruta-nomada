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


-- ── 4. Comprobación ─────────────────────────────────────────
-- Si todo salió bien, esto imprime 5 columnas nuevas y 3 tablas nuevas.

SELECT
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plan_items'
       AND COLUMN_NAME IN ('modo_viaje','moneda','gasto_cat','gasto_desc','gasto_modo'))
    AS `columnas_nuevas_en_plan_items (deben ser 5)`,
  (SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME IN ('plan_item_gasto','tramo_cache','ruta_uso'))
    AS `tablas_nuevas (deben ser 3)`;
