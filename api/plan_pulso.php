<?php
// ============================================================
//  api/plan_pulso.php — ¿Hay novedades? | Ruta Nómada
//  GET ?id=N  →  {ok: true, rev: 137}
//
//  La respuesta pesa unos 25 bytes y sale de UNA consulta por clave
//  primaria. Es lo que el navegador pregunta cada 5 segundos, así que
//  todo aquí está escrito para costar lo menos posible.
//
//  ⚠ POR QUÉ session_write_close() Y POR QUÉ TAN PRONTO
//  PHP bloquea el fichero de sesión desde session_start() hasta que
//  acaba el script. Con un sondeo cada 5 segundos, ese bloqueo
//  SERIALIZA todas las demás peticiones de la misma persona: mientras
//  el pulso corre, guardar un lugar o buscar una foto se quedan
//  esperando en la puerta. La aplicación se arrastra y no aparece
//  ningún error en ningún sitio, sólo lentitud, y es de lo más caro
//  de diagnosticar que hay.
//
//  El pulso sólo LEE la sesión, así que la suelta en cuanto tiene el
//  id de quien pregunta, ANTES de tocar la base de datos.
//
//  ⚠ POR QUÉ NO USA planAccess()
//  Porque planAccess() consulta la sesión Y la base en la misma
//  llamada, y eso obligaría a mantener el bloqueo durante la consulta.
//  Aquí la comprobación de acceso va incrustada en la misma consulta
//  que trae el número: el JOIN con plan_miembros hace de guardián, así
//  que quien no es miembro no recibe fila y se lleva un 403.
//  Es la misma regla que planAccess() aplica para el rol 'lector'
//  -ser miembro-, escrita en un solo SELECT.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if (empty($_SESSION['user'])) {
    apiFail('Debes iniciar sesión.', 401);
}
$userId = (int)$_SESSION['user']['id'];
$planId = (int)($_GET['id'] ?? 0);

// Aquí se suelta la sesión. A partir de esta línea no se puede volver
// a escribir en $_SESSION, y este endpoint no lo necesita.
session_write_close();

if ($planId <= 0) apiFail('Plan no especificado.');

$db = getDB();
$stmt = $db->prepare(
    'SELECT p.rev
       FROM planes p
       JOIN plan_miembros m ON m.plan_id = p.id AND m.usuario_id = ?
      WHERE p.id = ? LIMIT 1'
);
$stmt->execute([$userId, $planId]);
$rev = $stmt->fetchColumn();

if ($rev === false) {
    apiFail('No tienes acceso a este plan.', 403);
}

// ── Presencia: quién está mirando el viaje ahora ─────────────
//
// Se marca la propia fila y se pregunta quién más dio señales en los
// últimos 45 segundos. Con el latido a 5 s eso deja margen para tres
// fallos seguidos antes de que a alguien se le apague el punto verde;
// con 15 s -el ritmo al que se retrocede cuando no pasa nada- el punto
// parpadearía, y por eso el retroceso vuelve a 5 s en cuanto la
// persona toca algo.
//
// ⚠ ESTE UPDATE NO LLEVA DISPARADOR, Y ES LA TRAMPA MÁS CARA DE TODO
// EL PLAN. Ocurre en CADA sondeo de CADA persona: si moviera `rev`,
// cada sondeo contaría como una novedad, cada cliente se traería el
// plan entero, ese trabajo generaría más sondeos, y se realimenta
// hasta fundir el servidor. plan_miembros sólo tiene disparadores de
// INSERT y DELETE, y así tiene que seguir.
//
// Va dentro de un try porque quien haya traído el código sin poner la
// base al día no tiene la columna: sin esto se le caería el latido
// entero: es mejor que se quede sin puntos verdes y conserve lo demás.
$aqui = [];
try {
    $db->prepare('UPDATE plan_miembros SET visto_en = NOW() WHERE plan_id = ? AND usuario_id = ?')
       ->execute([$planId, $userId]);
    $st = $db->prepare(
        'SELECT usuario_id FROM plan_miembros
          WHERE plan_id = ? AND visto_en > DATE_SUB(NOW(), INTERVAL 45 SECOND)'
    );
    $st->execute([$planId]);
    $aqui = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
} catch (Throwable $e) {
    error_log('plan_pulso presencia (¿falta actualizar_bd.sql?): ' . $e->getMessage());
}

apiJson(['ok' => true, 'rev' => (int)$rev, 'aqui' => $aqui]);
