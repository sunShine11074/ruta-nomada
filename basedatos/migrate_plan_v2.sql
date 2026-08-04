-- ============================================================
--   migrate_plan_v2.sql — Ajustes para la Vista del Plan
--   (plan.php) | Ruta Nómada
--   Ejecutar DESPUÉS de migrate_plan.sql.
--   Nota: `planes.presupuesto` ya existía; no se agrega aquí.
-- ============================================================

-- El plan necesita destino geocodificado, privacidad y portada
ALTER TABLE planes
  ADD COLUMN IF NOT EXISTS destino     VARCHAR(120) DEFAULT NULL AFTER nombre,
  ADD COLUMN IF NOT EXISTS lat         DECIMAL(10,7) DEFAULT NULL AFTER destino,
  ADD COLUMN IF NOT EXISTS lng         DECIMAL(10,7) DEFAULT NULL AFTER lat,
  ADD COLUMN IF NOT EXISTS privacidad  ENUM('solo','amigos','publico') NOT NULL DEFAULT 'solo' AFTER fecha_fin,
  ADD COLUMN IF NOT EXISTS portada_url VARCHAR(500) DEFAULT NULL AFTER privacidad,
  ADD COLUMN IF NOT EXISTS updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Subtítulos por día del itinerario (JSON serializado, uno por día)
ALTER TABLE planes
  ADD COLUMN IF NOT EXISTS dia_subtitulos TEXT DEFAULT NULL;

-- Los items del prototipo tienen hora de inicio Y fin ("5:00 - 6:30")
ALTER TABLE plan_items
  ADD COLUMN IF NOT EXISTS hora_fin TIME DEFAULT NULL AFTER hora;

-- Los gastos muestran fecha visible y el demo usa 'Gasolina'
ALTER TABLE plan_gastos
  ADD COLUMN IF NOT EXISTS fecha DATE DEFAULT NULL AFTER dia;
ALTER TABLE plan_gastos
  MODIFY categoria ENUM('Alojamiento','Comida','Actividades','Transporte',
                        'Compras','Gasolina','Otro') NOT NULL DEFAULT 'Otro';

-- Listas del Resumen (notas y checklists)
CREATE TABLE IF NOT EXISTS plan_listas (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  plan_id  INT NOT NULL,
  titulo   VARCHAR(255) NOT NULL DEFAULT '',
  tipo     ENUM('nota','check') NOT NULL,
  texto    TEXT DEFAULT NULL,           -- cuerpo de la nota (tipo 'nota')
  orden    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_planlistas FOREIGN KEY (plan_id) REFERENCES planes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plan_lista_items (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  lista_id INT NOT NULL,
  texto    VARCHAR(500) NOT NULL,
  hecho    TINYINT(1) NOT NULL DEFAULT 0,
  orden    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_planlistaitems FOREIGN KEY (lista_id) REFERENCES plan_listas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
