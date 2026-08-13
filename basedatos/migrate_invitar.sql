-- ============================================================
--  migrate_invitar.sql — El enlace de invitación se puede compartir
--
--  EL PROBLEMA QUE ARREGLA
--  La ventana «Invita a compañeros de viaje» enseña un enlace para
--  repartir entre varias personas -el marcador del frame dice
--  literalmente «urlqueseracompartida» y el título está en plural-.
--  Pero plan_invitacion.php exigía `usada = 0` y acto seguido ponía
--  `usada = 1`: la SEGUNDA persona que abriera ese enlace recibía
--  «inválido, ya fue usado o expiró». Pegarlo en un grupo de cuatro
--  metía a uno y dejaba fuera a tres, sin explicación.
--
--  LOS DOS TIPOS DE INVITACIÓN QUE YA CONVIVÍAN EN LA TABLA
--    · Invitación por correo  → email = el destinatario, UN uso.
--      La crean el campo de correo de la ventana y plan_create.php.
--    · Enlace para compartir  → email NULL, usos SIN LÍMITE.
--      Lo crea la ventana al abrirse, uno por plan.
--
--  POR QUÉ token_claro, SI TODO EL DISEÑO ERA GUARDAR SÓLO EL HASH
--  Porque la ventana tiene que poder VOLVER A ENSEÑAR el enlace cada
--  vez que se abre, y de un SHA-256 no se saca el token. Las
--  alternativas eran peores: rotar el token en cada apertura mata el
--  enlace que ya mandaste por WhatsApp, y crear uno nuevo cada vez
--  deja una credencial permanente más por apertura.
--
--  Sólo se rellena cuando `email IS NULL`. Las invitaciones por
--  correo siguen siendo únicamente hash. Y es defendible: un enlace
--  para compartir es, por definición, algo que se reparte; su secreto
--  ES el control de acceso al plan, y la base ya contiene todo lo que
--  ese enlace protege.
--
--  Ejecutar una sola vez sobre una base existente:
--     mysql -u root ruta_nomada -e "source basedatos/migrate_invitar.sql"
--
--  Es seguro ejecutarlo dos veces: todo va con IF NOT EXISTS.
-- ============================================================

SET NAMES utf8mb4;

-- usos      → cuánta gente ha entrado ya por esta invitación.
-- usos_max  → NULL significa SIN LÍMITE. El 1 por omisión deja el
--             comportamiento de siempre a quien inserte sin decir nada,
--             que es justo lo que hace api/plan_create.php.
-- token_claro → sólo para el enlace de compartir; ver la cabecera.

ALTER TABLE `plan_invitaciones`
  ADD COLUMN IF NOT EXISTS `usos` smallint(5) unsigned NOT NULL DEFAULT 0
      COMMENT 'Cuánta gente ha entrado ya por esta invitación' AFTER `usada`;

ALTER TABLE `plan_invitaciones`
  ADD COLUMN IF NOT EXISTS `usos_max` smallint(5) unsigned DEFAULT 1
      COMMENT 'NULL = sin límite (enlace para compartir)' AFTER `usos`;

ALTER TABLE `plan_invitaciones`
  ADD COLUMN IF NOT EXISTS `token_claro` varchar(64) DEFAULT NULL
      COMMENT 'Token legible; SÓLO para el enlace de compartir (email IS NULL)' AFTER `token_hash`;

-- Las invitaciones que ya existían y se habían gastado pasan a
-- contar 1 uso, para que el contador nuevo diga la verdad desde el
-- primer momento. `usada` se conserva y se sigue manteniendo por si
-- algo la mira, pero a partir de aquí la verdad está en `usos`.

UPDATE `plan_invitaciones` SET `usos` = 1 WHERE `usada` = 1 AND `usos` = 0;

-- ── Comprobación ────────────────────────────────────────────
-- Si todo salió bien, imprime 3.

SELECT COUNT(*) AS `columnas_nuevas (deben ser 3)`
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE()
   AND TABLE_NAME = 'plan_invitaciones'
   AND COLUMN_NAME IN ('usos', 'usos_max', 'token_claro');
