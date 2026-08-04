-- ============================================================
--   migrate_ai.sql — Consumo del asistente de IA | Ruta Nómada
--
--   Un renglón por petición a Gemini. Sirve para dos cosas:
--     1. Límite de uso por usuario (anti-abuso y anti-factura).
--     2. Saber cuántos tokens costó de verdad cada respuesta.
--
--   OJO: el límite tiene que vivir aquí y NO en $_SESSION. Con la
--   sesión basta borrar la cookie para tener cuota nueva; ligado a
--   usuario_id, no. El endpoint es "Gemini gratis" para quien
--   conozca la URL, y cualquiera puede registrarse en la app.
--
--   Ejecutar después de migrate_plan_v2.sql.
-- ============================================================

CREATE TABLE IF NOT EXISTS ai_uso (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT NOT NULL,
  plan_id     INT DEFAULT NULL,
  creado      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- Se rellenan DESPUÉS de la respuesta, con usageMetadata.
  -- Quedan en 0 si la llamada falló: así se distingue un intento
  -- fallido (que igual gastó cuota de peticiones) de uno bueno.
  tokens_in   INT NOT NULL DEFAULT 0,
  tokens_out  INT NOT NULL DEFAULT 0,
  modelo      VARCHAR(60) DEFAULT NULL,

  INDEX idx_usuario_fecha (usuario_id, creado),
  CONSTRAINT fk_aiuso_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_aiuso_plan FOREIGN KEY (plan_id)    REFERENCES planes(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
