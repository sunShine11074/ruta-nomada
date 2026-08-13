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

apiJson(['ok' => true, 'rev' => (int)$rev]);
