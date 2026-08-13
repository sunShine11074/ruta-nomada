<?php
// ============================================================
//  api/plan_invitar.php — Invitar colaboradores | Ruta Nómada
//
//  POST {plan_id, action:'enlace'}            → {ok, link}
//  POST {plan_id, action:'correo', email, rol?} → {ok, correo_enviado}
//
//  Sólo el propietario. Es lo que ya exigía este endpoint, y por eso
//  la ventana sólo dibuja el botón de invitar para él: un botón que
//  siempre falla es peor que ningún botón.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';
require_once __DIR__ . '/../includes/plan_invite_lib.php';

// Cuántas invitaciones por correo admite un plan en una hora. No
// existía ningún tope, y cada petición manda un mensaje REAL por
// Gmail: sin límite, un bucle deja la cuenta marcada como spam. Se
// cuenta sobre la propia tabla, que ya registra exactamente esto, en
// vez de inventar un contador aparte.
const INVITAR_POR_HORA = 10;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
planAccess((int)($in['plan_id'] ?? 0), 'propietario');
$planId = (int)$in['plan_id'];

$action = (string)($in['action'] ?? 'correo');

// ── El enlace para compartir ─────────────────────────────────
// Sin efectos secundarios al repetirse: planEnlaceDePlan() reutiliza
// el que ya haya vigente. La ventana lo pide cada vez que se abre.
if ($action === 'enlace') {
    $link = planEnlaceDePlan($planId);
    if (!$link) apiFail('No se pudo preparar el enlace.', 500);
    apiJson(['ok' => true, 'link' => $link]);
}

// ── Invitación dirigida a un correo ──────────────────────────
$rol   = in_array($in['rol'] ?? '', ['editor', 'lector'], true) ? $in['rol'] : 'editor';
$email = trim((string)($in['email'] ?? ''));

if ($email === '') apiFail('Escribe el correo de quien quieres invitar.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) apiFail('El correo no es válido.');

// El dominio tiene que existir de verdad. Con sólo filter_var, una
// invitación a @fake.com se daba por enviada y no llegaba nunca.
// Misma comprobación que hace register.php desde su propia fase.
require_once __DIR__ . '/../includes/email_dominio.php';
if (!correoConDominioReal($email)) {
    apiFail('Ese dominio de correo no existe. Revisa la dirección.');
}

// No invitar a quien ya está dentro: la invitación se crearía, el
// correo saldría y al abrirlo no pasaría nada visible.
$db = getDB();
$yaEsta = $db->prepare(
    'SELECT 1 FROM plan_miembros m JOIN usuarios u ON u.id = m.usuario_id
      WHERE m.plan_id = ? AND u.email = ? LIMIT 1'
);
$yaEsta->execute([$planId, $email]);
if ($yaEsta->fetch()) apiFail('Esa persona ya está en el viaje.');

$recientes = $db->prepare(
    'SELECT COUNT(*) FROM plan_invitaciones
      WHERE plan_id = ? AND email IS NOT NULL
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
);
$recientes->execute([$planId]);
if ((int)$recientes->fetchColumn() >= INVITAR_POR_HORA) {
    apiFail('Has enviado muchas invitaciones seguidas. Inténtalo dentro de un rato.', 429);
}

$enviado = false;
$link = planInviteCreate($planId, $rol, $email, 1, $enviado);
if (!$link) apiFail('No se pudo crear la invitación.', 500);

// El enlace NO se devuelve aquí: esta invitación es de un solo uso y
// va dirigida a esa persona. Quien quiera algo para repartir tiene el
// enlace de compartir, que es el que la ventana enseña arriba.
//
// `correo_enviado` dice la verdad. En las máquinas donde no existe
// includes/mail_config.php el envío falla en silencio, y la ventana
// tiene que poder decir «la invitación está creada, pero el correo no
// salió: pásale el enlace» en vez de mentir.
apiJson(['ok' => true, 'correo_enviado' => $enviado]);
