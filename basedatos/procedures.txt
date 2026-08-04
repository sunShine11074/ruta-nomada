-- ============================================================
--   procedures.sql — Procedimientos almacenados | Ruta Nómada
--   Ejecutar en la BD `ruta_nomada` (phpMyAdmin > pestaña SQL,
--   o: mysql -u root ruta_nomada < basedatos/procedures.sql)
-- ============================================================

-- Índice único sobre el correo: es la verdadera garantía contra
-- registros duplicados simultáneos (el chequeo del SP da el mensaje
-- amigable; el índice cierra la condición de carrera).
ALTER TABLE usuarios ADD UNIQUE INDEX IF NOT EXISTS idx_usuarios_email (email);

DELIMITER $$

-- ------------------------------------------------------------
-- sp_registrar_usuario
--   Verifica que el correo no exista e inserta al usuario en una
--   sola operación. Devuelve el id generado como result set.
--   La contraseña llega YA hasheada (bcrypt se calcula en PHP;
--   la contraseña en texto plano nunca viaja a MySQL).
--   Errores: SQLSTATE 45000 con mensaje 'EMAIL_DUPLICADO'.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_registrar_usuario $$
CREATE PROCEDURE sp_registrar_usuario (
    IN p_nombre        VARCHAR(120),
    IN p_email         VARCHAR(255),
    IN p_password_hash VARCHAR(255)
)
BEGIN
    -- Si el índice único detecta un duplicado que se coló entre el
    -- chequeo y el INSERT, lo traducimos al mismo error amigable.
    DECLARE EXIT HANDLER FOR 1062
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'EMAIL_DUPLICADO';

    IF EXISTS (SELECT 1 FROM usuarios WHERE email = p_email LIMIT 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'EMAIL_DUPLICADO';
    END IF;

    INSERT INTO usuarios (nombre, email, password_hash)
    VALUES (p_nombre, p_email, p_password_hash);

    SELECT LAST_INSERT_ID() AS id;
END $$

DELIMITER ;
