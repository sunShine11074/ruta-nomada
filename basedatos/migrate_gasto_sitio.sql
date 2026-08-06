-- ============================================================
--  migrate_gasto_sitio.sql — Coste de un lugar y su reparto
--
--  El importe ya vivía en plan_items.precio, pero sin divisa, sin
--  categoría, sin descripción y sin manera de repartirlo entre los
--  compañeros de viaje. Esto añade lo que faltaba.
--
--  De paso arregla modo_viaje: estaba sólo en migrate_modo_viaje.sql
--  y no en instalar.sql, así que una instalación limpia reventaba en
--  el SELECT de includes/plan_auth.php. Aquí se crea si falta.
--
--  Ejecutar una sola vez sobre una base existente:
--     mysql -u root ruta_nomada < basedatos/migrate_gasto_sitio.sql
-- ============================================================

-- ── Columnas nuevas de plan_items ───────────────────────────
-- Se usa un procedimiento porque MariaDB no admite
-- "ADD COLUMN IF NOT EXISTS" en todas las versiones y esto tiene que
-- poder ejecutarse dos veces sin romperse.
DELIMITER $$
DROP PROCEDURE IF EXISTS rn_add_col $$
CREATE PROCEDURE rn_add_col(IN tabla VARCHAR(64), IN col VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tabla AND COLUMN_NAME = col) THEN
    SET @s = CONCAT('ALTER TABLE `', tabla, '` ADD COLUMN `', col, '` ', def);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END $$
DELIMITER ;

CALL rn_add_col('plan_items', 'modo_viaje',
  "ENUM('DRIVE','WALK','BICYCLE') DEFAULT NULL COMMENT 'Cómo se va de este lugar al siguiente'");
CALL rn_add_col('plan_items', 'moneda',
  "CHAR(3) NOT NULL DEFAULT 'MXN' COMMENT 'Divisa del importe; códigos de includes/currency.php'");
CALL rn_add_col('plan_items', 'gasto_cat',
  "VARCHAR(24) DEFAULT NULL COMMENT 'Categoría del gasto (actividad, comida, ...)'");
CALL rn_add_col('plan_items', 'gasto_desc',
  "VARCHAR(500) DEFAULT NULL COMMENT 'Descripción libre del gasto'");
CALL rn_add_col('plan_items', 'gasto_modo',
  "ENUM('no','todos','individuos') NOT NULL DEFAULT 'no' COMMENT 'Cómo se divide: no dividir, todos, o algunos'");

DROP PROCEDURE IF EXISTS rn_add_col;

-- ── Reparto del gasto entre los miembros ────────────────────
-- Una fila por persona a la que le toca parte del coste de un lugar.
-- El color es el de su porción en la gráfica de dona.
CREATE TABLE IF NOT EXISTS `plan_item_gasto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `color` char(7) DEFAULT NULL COMMENT 'Color hex de su porción, p. ej. #41A24D',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_item_usuario` (`item_id`,`usuario_id`),
  KEY `idx_gasto_usuario` (`usuario_id`),
  CONSTRAINT `fk_itemgasto_item` FOREIGN KEY (`item_id`) REFERENCES `plan_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_itemgasto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
