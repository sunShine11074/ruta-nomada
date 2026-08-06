<?php
// ============================================================
//  includes/plan_invite_lib.php — Invitaciones a planes
//  Patrón de tokens idéntico a password_resets: token aleatorio
//  de 64 hex, en BD solo su hash SHA-256, expira en 7 días.
// ============================================================
require_once __DIR__ . '/../db.php';

// Crea la invitación y (si hay correo) la envía. Devuelve el
// enlace en éxito o null en fallo.
function planInviteCreate(int $planId, string $rol, ?string $email = null): ?string
{
    if (!in_array($rol, ['editor', 'lector'], true)) $rol = 'editor';
    $db = getDB();

    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);

    try {
        $stmt = $db->prepare(
            'INSERT INTO plan_invitaciones (plan_id, token_hash, rol, email, expira_en)
             VALUES (?,?,?,?, DATE_ADD(NOW(), INTERVAL 7 DAY))'
        );
        $stmt->execute([$planId, $hash, $rol, $email ?: null]);
    } catch (PDOException $e) {
        error_log('planInviteCreate: ' . $e->getMessage());
        return null;
    }

    // Enlace absoluto. Se deduce de la petición para que funcione se
    // llame como se llame la carpeta del proyecto: antes iba escrita a
    // mano y quien clonaba el repositorio en otra carpeta recibía las
    // invitaciones con un enlace roto. mail_config.php puede imponer
    // otra dirección con 'base_url', que es lo que haría falta el día
    // que esto viva en un dominio de verdad.
    $base = 'http://localhost';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $esq  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $raiz = str_replace('\\', '/', dirname(__DIR__));
        $doc  = str_replace('\\', '/', rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
        $sub  = ($doc !== '' && strpos($raiz, $doc) === 0) ? trim(substr($raiz, strlen($doc)), '/') : '';
        $ruta = $sub === '' ? '' : '/' . implode('/', array_map('rawurlencode', explode('/', $sub)));
        $base = $esq . '://' . $_SERVER['HTTP_HOST'] . $ruta;
    }
    if (is_file(__DIR__ . '/mail_config.php')) {
        $cfg = @include __DIR__ . '/mail_config.php';
        if (is_array($cfg) && !empty($cfg['base_url'])) $base = rtrim($cfg['base_url'], '/');
    }
    $link = $base . '/plan_invitacion.php?token=' . $token;

    // Envío de correo (best-effort)
    if ($email) {
        $stmt = $db->prepare('SELECT nombre, destino FROM planes WHERE id = ? LIMIT 1');
        $stmt->execute([$planId]);
        $plan = $stmt->fetch() ?: ['nombre' => 'un viaje', 'destino' => ''];
        try {
            require_once __DIR__ . '/../mailer.php';
            $titulo   = htmlspecialchars($plan['nombre'], ENT_QUOTES, 'UTF-8');
            $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            enviarCorreo(
                $email,
                'Te invitaron a un plan de viaje — Ruta Nómada',
                "<h2 style=\"margin-top:0;\">¡Te invitaron a un viaje!</h2>
                 <p>Te invitaron a colaborar en <strong>{$titulo}</strong> en Ruta Nómada.</p>
                 <p style=\"text-align:center;margin:28px 0;\">
                   <a href=\"{$safeLink}\" style=\"display:inline-block;background:#1b3a40;color:#fff;
                   padding:12px 28px;border-radius:8px;font-weight:600;text-decoration:none\">Unirme al plan</a></p>
                 <p style=\"font-size:13px;color:#5b6b68;\">El enlace expira en <strong>7 días</strong>. Si no
                 esperabas esta invitación puedes ignorar este correo.</p>
                 <p style=\"font-size:12px;color:#8a9794;word-break:break-all;\">Si el botón no funciona,
                 copia y pega esta dirección en tu navegador:<br>{$safeLink}</p>",
                "Te invitaron a colaborar en \"{$plan['nombre']}\" en Ruta Nómada.\nAbre este enlace (expira en 7 días):\n{$link}"
            );
        } catch (Throwable $e) {
            error_log('planInviteCreate mail: ' . $e->getMessage());
            // la invitación queda creada aunque el correo falle
        }
    }
    return $link;
}
