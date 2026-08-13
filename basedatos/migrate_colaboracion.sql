-- ============================================================
--  migrate_colaboracion.sql — Que la base sepa cuándo cambió algo
--
--  Fase 2 de Reportes_md/PLAN_colaboracion.md.
--
--  PARA QUÉ SIRVE
--  Para poder preguntar «¿hay novedades en este viaje?» con UNA
--  consulta de una fila, en vez de traerse el plan entero cada pocos
--  segundos. Sin esto no hay forma barata de que dos personas que
--  editan a la vez se enteren la una de la otra.
--
--  POR QUÉ UN CONTADOR Y NO updated_at
--  Porque `timestamp` tiene resolución de UN SEGUNDO. Dos ediciones
--  dentro del mismo segundo dan la misma marca de tiempo y son
--  indistinguibles: quien sondea se pierde la segunda. Un entero que
--  sube de uno en uno se compara con !== y no tiene ambigüedad.
--
--  QUÉ SIGNIFICA CADA COLUMNA
--
--    planes.rev      Cambia cada vez que cambia CUALQUIER COSA del
--                    viaje: sus lugares, sus gastos, sus listas, sus
--                    reacciones, el reparto de un coste o quién es
--                    miembro. Lo mueven 20 disparadores; nadie lo
--                    escribe a mano desde PHP.
--
--                    OJO: es un TESTIGO DE CAMBIO, no una cuenta de
--                    acciones. Guardar el reparto de un gasto entre
--                    cuatro personas borra cuatro filas e inserta
--                    cuatro, así que sube ocho de golpe. Da igual: lo
--                    único que se le pide es que CAMBIE cuando algo
--                    cambió, y que NO cambie cuando no cambió nada.
--
--    plan_items.ver  La versión de UN lugar concreto, para el bloqueo
--                    optimista de la fase 5. Empieza en 1 y la sube
--                    la propia sentencia UPDATE del endpoint
--                    (`SET ... ver = ver + 1 WHERE id = ? AND ver = ?`).
--                    A propósito NO la mueve ningún disparador: si lo
--                    hiciera, subiría dos veces por edición y el
--                    número que el cliente tiene en la mano nunca
--                    volvería a coincidir.
--
--  Ejecutar una sola vez sobre una base existente:
--     mysql -u root ruta_nomada -e "source basedatos/migrate_colaboracion.sql"
--
--  Es seguro ejecutarlo dos veces: va con IF NOT EXISTS.
--
--  ⚠ ESTE ARCHIVO SOLO TRAE LAS COLUMNAS. Los disparadores que mueven
--  `rev` viven en basedatos/rutinas.sql y hay que ejecutarlo también,
--  o las columnas se quedan quietas para siempre.
-- ============================================================

SET NAMES utf8mb4;

ALTER TABLE `planes`
  ADD COLUMN IF NOT EXISTS `rev` bigint(20) unsigned NOT NULL DEFAULT 0
      COMMENT 'Testigo de cambio del viaje; lo mueven los disparadores' AFTER `updated_at`;

ALTER TABLE `plan_items`
  ADD COLUMN IF NOT EXISTS `ver` int(10) unsigned NOT NULL DEFAULT 1
      COMMENT 'Version del lugar para el bloqueo optimista; la sube el UPDATE del endpoint' AFTER `plan_id`;

-- planes.ver — la version del NOMBRE del viaje, y sólo del nombre.
--
-- ⚠ NO SE PUEDE USAR `rev` PARA ESTO, aunque sea tentador. `rev` se
-- mueve con cualquier cambio del viaje: si alguien añade un lugar
-- mientras tú escribes el título, tu guardado se rechazaría por un
-- cambio que no tiene nada que ver con el título. Serían conflictos
-- falsos a todas horas.
--
-- Y tampoco vale usar esta columna para el resto de campos del plan.
-- Los subtítulos de los días y el presupuesto se guardan CON RETARDO
-- (800 ms) desde el mismo navegador, así que dos escrituras propias se
-- solapan a menudo: la segunda llegaría con una versión ya vieja y el
-- cliente se daría un 409 A SÍ MISMO. Por eso esta versión la mueve y
-- la comprueba únicamente el cambio de nombre, que es el caso que de
-- verdad se pisa entre dos personas.

ALTER TABLE `planes`
  ADD COLUMN IF NOT EXISTS `ver` int(10) unsigned NOT NULL DEFAULT 1
      COMMENT 'Version del NOMBRE del viaje para el bloqueo optimista' AFTER `rev`;

-- ── Comprobación ────────────────────────────────────────────
-- Si todo salió bien, imprime 2.

SELECT COUNT(*) AS `columnas_nuevas (deben ser 3)`
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE()
   AND ((TABLE_NAME = 'planes'     AND COLUMN_NAME IN ('rev','ver'))
     OR (TABLE_NAME = 'plan_items' AND COLUMN_NAME = 'ver'));
