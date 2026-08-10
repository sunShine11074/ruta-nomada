-- ============================================================
--   migrate_borrar_plan.sql — Que borrar un plan no falle | Ruta Nómada
--
--   PARA QUÉ ES
--   viajes_usuario.plan_id apunta a planes(id) SIN cláusula ON DELETE,
--   es decir RESTRICT: en cuanto una fila de viajes_usuario tenga un
--   plan_id, borrar ese plan fallaría con un error de integridad y la
--   persona vería una tarjeta que "no se deja borrar", sin explicación.
--
--   Hoy está latente: destino.php inserta en viajes_usuario sin plan_id,
--   así que la columna siempre es NULL y el RESTRICT nunca salta. Pero
--   es una mina puesta, y ahora que existe api/plan_delete.php conviene
--   desactivarla.
--
--   SET NULL y no CASCADE a propósito: viajes_usuario es el historial de
--   destinos que alguien guardó. Si borra un plan de viaje no tiene por
--   qué perder el recuerdo de que ese destino le interesó; sólo se
--   rompe el vínculo con el plan.
--
--   CÓMO USARLO
--     Doble clic en herramientas/actualizar.bat  (lo incluye)
--   o a mano:
--     mysql -u root ruta_nomada -e "source basedatos/migrate_borrar_plan.sql"
--
--   ES SEGURO EJECUTARLO DOS VECES: comprueba antes de tocar nada.
-- ============================================================

SET NAMES utf8mb4;

-- MariaDB 10.4 no admite "DROP FOREIGN KEY IF EXISTS" en todas sus
-- versiones, así que se comprueba en information_schema y se ejecuta
-- con SQL preparado. Si la clave ya está como SET NULL, no hace nada.
SET @ya := (
  SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE()
     AND TABLE_NAME = 'viajes_usuario'
     AND CONSTRAINT_NAME = 'viajes_usuario_ibfk_3'
     AND DELETE_RULE = 'SET NULL'
);

SET @existe := (
  SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE()
     AND TABLE_NAME = 'viajes_usuario'
     AND CONSTRAINT_NAME = 'viajes_usuario_ibfk_3'
);

-- 1. Quitar la clave vieja (sólo si existe y todavía no es SET NULL)
SET @sql := IF(@existe > 0 AND @ya = 0,
  'ALTER TABLE `viajes_usuario` DROP FOREIGN KEY `viajes_usuario_ibfk_3`',
  'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2. La columna tiene que admitir NULL para poder desvincular
SET @sql := IF(@ya = 0,
  'ALTER TABLE `viajes_usuario` MODIFY `plan_id` int(11) DEFAULT NULL',
  'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3. Volver a crearla, ahora con ON DELETE SET NULL
SET @sql := IF(@ya = 0,
  'ALTER TABLE `viajes_usuario` ADD CONSTRAINT `viajes_usuario_ibfk_3`
     FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE SET NULL',
  'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ── Comprobación ────────────────────────────────────────────
-- Debe imprimir SET NULL.
SELECT DELETE_RULE AS `viajes_usuario.plan_id al borrar un plan (debe ser SET NULL)`
  FROM information_schema.REFERENTIAL_CONSTRAINTS
 WHERE CONSTRAINT_SCHEMA = DATABASE()
   AND TABLE_NAME = 'viajes_usuario'
   AND CONSTRAINT_NAME = 'viajes_usuario_ibfk_3';
