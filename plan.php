<?php
// ============================================================
//  plan.php — Vista del Plan de Viaje | Ruta Nómada
//
//  El front-end ES el prototipo de 'Trip Planning Feature Design/
//  Vista del plan (Ensenada).dc.html', servido VERBATIM:
//    · plan_template.html  → la plantilla <x-dc> del prototipo
//    · js/plan_logic.js    → la clase Component (DCLogic) adaptada
//    · libs/dc/support.js  → runtime de Claude Design (carga React)
//  PHP solo autentica, arma el JSON inicial (PLAN_BOOT) y sirve.
// ============================================================
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/plan_auth.php';
require_once __DIR__ . '/includes/maps_lib.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Sin ?id= la página es el punto de partida de un viaje nuevo: pide
// destino y fechas, crea el plan y vuelve aquí con su id. Con ?id=
// inválido no: ese enlace apuntaba a un plan concreto, y ofrecer
// "crea uno nuevo" en su lugar confunde. Ahí seguimos yendo a la lista.
if (!isset($_GET['id'])) {
    $plan_csrf = csrfToken();
    include __DIR__ . '/includes/plan_nuevo.php';
    exit;
}

$planId = (int)$_GET['id'];

$db = getDB();
$stmt = $db->prepare(
    'SELECT p.*, m.rol FROM planes p
      JOIN plan_miembros m ON m.plan_id = p.id AND m.usuario_id = ?
     WHERE p.id = ? LIMIT 1'
);
$stmt->execute([(int)$_SESSION['user']['id'], $planId]);
$row = $stmt->fetch();
if (!$row) {
    header('Location: mis_planes.php');
    exit;
}
$rol = $row['rol'];
unset($row['rol']);
$acc  = ['rol' => $rol, 'plan' => $row, 'user_id' => (int)$_SESSION['user']['id']];
$boot = planFullJson($planId, $acc);
$csrf = csrfToken();

$tituloPagina = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $tituloPagina ?> — Ruta Nómada</title>
<script>
window.PLAN_BOOT = <?= json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.PLAN_CSRF = <?= json_encode($csrf) ?>;
window.PLAN_USER = <?= json_encode([
    'id'      => (int)$user['id'],
    'nombre'  => $user['nombre'],
    'inicial' => mb_strtoupper(mb_substr(trim($user['nombre']), 0, 1)),
], JSON_UNESCAPED_UNICODE) ?>;
window.gmapsReady = new Promise(function (res) { window.__plMapReady = res; });
// Nivel 1 del "Acerca de": editorialSummary con la clase nueva Place.
// Es una petición aparte que se cobra (1,000 gratis al mes), así que se
// apaga desde aquí sin tocar el JS. En false la ficha sigue funcionando:
// baja a Wikipedia y, si no, a la plantilla armada con los datos de Places.
// Requiere tener habilitada "Places API (New)" en Google Cloud.
window.PLAN_ACERCA_GOOGLE = true;
</script>
<script src="./js/emojis.js"></script>
<script src="./libs/dc/support.js"></script>
<?php $_mapsUrl = mapsScriptUrl('__plMapReady'); ?>
<?php if ($_mapsUrl !== ''): ?>
<script async src="<?= htmlspecialchars($_mapsUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php else: ?>
<script>console.warn('[Ruta Nómada] Falta includes/maps_config.php: el mapa no se va a cargar. Copia includes/maps_config.sample.php y pega tu clave.');</script>
<?php endif; ?>
</head>
<body>
<?php readfile(__DIR__ . '/plan_template.html'); ?>
<script type="text/x-dc" data-dc-script>
<?php readfile(__DIR__ . '/js/plan_logic.js'); ?>
</script>
</body>
</html>
