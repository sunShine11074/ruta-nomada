<?php
// ============================================================
//  api/plan_invitar.php — Invitar colaboradores | Ruta Nómada
//  POST {plan_id, rol: editor|lector, email?}
//  Solo el propietario puede invitar. Devuelve {ok, link}.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';
require_once __DIR__ . '/../includes/plan_invite_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
$acc = planAccess((int)($in['plan_id'] ?? 0), 'propietario');
$planId = (int)$in['plan_id'];

$rol = in_array($in['rol'] ?? '', ['editor', 'lector'], true) ? $in['rol'] : 'editor';
$email = trim((string)($in['email'] ?? ''));
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiFail('El correo no es válido.');
}

$link = planInviteCreate($planId, $rol, $email ?: null);
if (!$link) apiFail('No se pudo crear la invitación.', 500);

apiJson(['ok' => true, 'link' => $link, 'correo_enviado' => $email !== '']);
