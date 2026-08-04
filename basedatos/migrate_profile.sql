-- ============================================================
--   migrate_profile.sql — Ruta Nómada
--   Adds profile fields to the `usuarios` table.
--   Run once via phpMyAdmin or mysql CLI.
-- ============================================================

ALTER TABLE usuarios
  ADD COLUMN apellidos       VARCHAR(120)                     NOT NULL DEFAULT '' AFTER nombre,
  ADD COLUMN genero          ENUM('Hombre','Mujer','Otro')    DEFAULT NULL        AFTER apellidos,
  ADD COLUMN telefono        VARCHAR(30)                      NOT NULL DEFAULT '' AFTER genero,
  ADD COLUMN fecha_nacimiento DATE                            DEFAULT NULL        AFTER telefono,
  ADD COLUMN nacionalidad    VARCHAR(60)                      NOT NULL DEFAULT 'Mexicana' AFTER fecha_nacimiento,
  ADD COLUMN estado          VARCHAR(60)                      NOT NULL DEFAULT '' AFTER nacionalidad,
  ADD COLUMN ciudad          VARCHAR(60)                      NOT NULL DEFAULT '' AFTER estado,
  ADD COLUMN lenguaje        VARCHAR(30)                      NOT NULL DEFAULT 'Español'  AFTER ciudad,
  ADD COLUMN divisa          VARCHAR(10)                      NOT NULL DEFAULT 'MXN'      AFTER lenguaje,
  ADD COLUMN foto_perfil     VARCHAR(500)                     DEFAULT NULL        AFTER divisa,
  ADD COLUMN foto_banner     VARCHAR(500)                     DEFAULT NULL        AFTER foto_perfil;
