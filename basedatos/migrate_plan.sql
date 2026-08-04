-- ============================================================
--   migrate_plan.sql — Tablas adicionales para el Planificador
--   de Viajes | Ruta Nómada
--   Ejecutar después de tener la tabla `planes` existente.
-- ============================================================

-- Items del itinerario (tarjetas de lugar)
CREATE TABLE IF NOT EXISTS plan_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  plan_id       INT NOT NULL,
  dia           TINYINT UNSIGNED NOT NULL DEFAULT 0   COMMENT '0 = sin asignar (guardado)',
  orden         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  nombre        VARCHAR(255) NOT NULL,
  categoria     ENUM('hacer','rest','hotel','custom') NOT NULL DEFAULT 'custom',
  hora          TIME DEFAULT NULL,
  duracion      VARCHAR(20) DEFAULT NULL,
  precio        DECIMAL(10,2) DEFAULT NULL,
  nota          TEXT DEFAULT NULL,
  place_id      VARCHAR(60) DEFAULT NULL              COMMENT 'ID de Google Places (opcional)',
  lat           DECIMAL(10,7) DEFAULT NULL,
  lng           DECIMAL(10,7) DEFAULT NULL,
  imagen_url    VARCHAR(500) DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_plan_dia (plan_id, dia, orden),
  CONSTRAINT fk_planitems_plan FOREIGN KEY (plan_id) REFERENCES planes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Miembros del plan (colaboradores)
CREATE TABLE IF NOT EXISTS plan_miembros (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  plan_id       INT NOT NULL,
  usuario_id    INT NOT NULL,
  rol           ENUM('propietario','editor','lector') NOT NULL DEFAULT 'editor',
  joined_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE INDEX idx_plan_user (plan_id, usuario_id),
  CONSTRAINT fk_planmiembros_plan FOREIGN KEY (plan_id) REFERENCES planes(id) ON DELETE CASCADE,
  CONSTRAINT fk_planmiembros_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invitaciones por enlace
CREATE TABLE IF NOT EXISTS plan_invitaciones (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  plan_id       INT NOT NULL,
  token_hash    CHAR(64) NOT NULL                     COMMENT 'SHA-256 del token de invitación',
  rol           ENUM('editor','lector') NOT NULL DEFAULT 'editor',
  email         VARCHAR(255) DEFAULT NULL,
  usada         TINYINT(1) NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expira_en     TIMESTAMP NULL DEFAULT NULL,

  UNIQUE INDEX idx_token (token_hash),
  INDEX idx_plan (plan_id),
  CONSTRAINT fk_planinv_plan FOREIGN KEY (plan_id) REFERENCES planes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gastos del plan
CREATE TABLE IF NOT EXISTS plan_gastos (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  plan_id       INT NOT NULL,
  concepto      VARCHAR(255) NOT NULL,
  monto         DECIMAL(10,2) NOT NULL DEFAULT 0,
  categoria     ENUM('Alojamiento','Comida','Actividades','Transporte','Compras','Otro') NOT NULL DEFAULT 'Otro',
  dia           TINYINT UNSIGNED DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_plan_gastos (plan_id),
  CONSTRAINT fk_plangastos_plan FOREIGN KEY (plan_id) REFERENCES planes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
