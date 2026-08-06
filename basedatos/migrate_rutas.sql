-- ============================================================
--   migrate_rutas.sql — Caché de rutas del itinerario | Ruta Nómada
--
--   Guarda la geometría de cada TRAMO (par de lugares consecutivos)
--   para no volver a pedírsela a Google. Al reordenar un día, los
--   tramos que no cambiaron salen de aquí y cuestan cero.
--
--   ⚠ TTL de 25 días, y no es un capricho: los términos de Google
--   Maps Platform permiten cachear coordenadas derivadas un máximo de
--   30 días naturales. Se deja margen. Los place_id sí son permanentes,
--   por eso son la clave; lo que caduca es la geometría.
--
--   Ejecutar después de instalar.sql.
-- ============================================================

CREATE TABLE IF NOT EXISTS tramo_cache (
  hash       CHAR(32) NOT NULL PRIMARY KEY   COMMENT 'md5(modo|origen>destino)',
  pts        MEDIUMTEXT DEFAULT NULL         COMMENT 'JSON [[lat,lng],...]; NULL si no hubo ruta',
  ok         TINYINT(1) NOT NULL DEFAULT 1   COMMENT '0 = Google dijo que no hay ruta; se cachea para no reintentar siempre',
  metros     INT DEFAULT NULL,
  segundos   INT DEFAULT NULL,
  creado     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_creado (creado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contador de gasto del mes. Vive en el servidor y no en el navegador
-- porque localStorage se borra y es por equipo: no protege de nada.
CREATE TABLE IF NOT EXISTS ruta_uso (
  mes  CHAR(7) NOT NULL PRIMARY KEY          COMMENT 'AAAA-MM',
  n    INT NOT NULL DEFAULT 0                COMMENT 'peticiones enviadas a Google ese mes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
