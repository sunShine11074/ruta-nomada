-- ═══════════════════════════════════════════════════════════════
-- Ruta Nómada — Database setup
-- Run this script once in phpMyAdmin or via MySQL CLI:
--   C:\xampp\mysql\bin\mysql.exe -u root < setup_db.sql
-- ═══════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS ruta_nomada
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ruta_nomada;

CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,              -- bcrypt hash
    telefono      VARCHAR(20)   NOT NULL DEFAULT '',
    pais          VARCHAR(60)   NOT NULL DEFAULT 'Mexico',
    idioma        VARCHAR(30)   NOT NULL DEFAULT 'Espanol',
    rol           ENUM('viajero','admin') DEFAULT 'viajero',
    creado_en     DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
