-- ============================================================
--   migrate_reacciones.sql — Reacciones con emoji | Ruta Nómada
--   Cada persona puede reaccionar a un lugar del itinerario con
--   UN solo emoji: el índice UNIQUE (item_id, usuario_id) es lo
--   que impone esa regla en la propia base de datos, así que
--   elegir otro emoji reemplaza el anterior en vez de sumarse.
--   Ejecutar después de migrate_plan.sql.
-- ============================================================

CREATE TABLE IF NOT EXISTS plan_item_reacciones (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  item_id     INT NOT NULL,
  usuario_id  INT NOT NULL,
  -- OJO: la colación tiene que ser binaria. Con utf8mb4_unicode_ci (o
  -- general_ci) MySQL considera IGUALES a todos los emojis, así que un
  -- GROUP BY emoji los fundiría en un solo grupo y '🐙' = '🌮' daría 1.
  emoji       VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE INDEX idx_item_usuario (item_id, usuario_id),
  INDEX idx_item (item_id),
  CONSTRAINT fk_react_item FOREIGN KEY (item_id)    REFERENCES plan_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_react_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
