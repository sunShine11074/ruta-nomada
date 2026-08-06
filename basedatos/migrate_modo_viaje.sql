-- ============================================================
--   migrate_modo_viaje.sql — Medio de transporte por tramo | Ruta Nómada
--
--   Guarda cómo se va DESDE este lugar HASTA el siguiente del mismo día
--   (a pie, en coche o en bici). Va en el lugar de origen porque un
--   itinerario es una cadena: el último lugar del día no tiene tramo y
--   se queda en NULL.
--
--   NULL significa "usa el modo por defecto", no "sin transporte": así
--   sólo ocupa fila lo que el usuario cambió a mano.
--
--   Ejecutar después de instalar.sql.
-- ============================================================

ALTER TABLE plan_items
  ADD COLUMN IF NOT EXISTS modo_viaje ENUM('DRIVE','WALK','BICYCLE') DEFAULT NULL
  COMMENT 'Cómo se llega al SIGUIENTE lugar del día; NULL = el de por defecto';
