<?php
// ============================================================
//  includes/plan_invite_lib.php — Invitaciones a planes
//
//  DOS TIPOS DE INVITACIÓN, UNA SOLA TABLA
//
//    · Invitación POR CORREO — dirigida a alguien concreto, UN uso.
//      La crean el campo de correo de la ventana «Invita a compañeros
//      de viaje» y api/plan_create.php. En la base sólo se guarda el
//      SHA-256 del token, como los restablecimientos de contraseña.
//
//    · Enlace PARA COMPARTIR — sin destinatario, SIN límite de usos,
//      uno por plan. Es el que enseña la ventana al abrirse, para
//      pegarlo en un grupo. De este SÍ se guarda el token legible en
//      `token_claro`, porque la ventana tiene que poder volver a
//      enseñarlo cada vez que se abre y de un hash no se saca.
//      El razonamiento completo, con las alternativas descartadas,
//      está en basedatos/migrate_invitar.sql.
// ============================================================
require_once __DIR__ . '/../db.php';

// ── La dirección base del proyecto ───────────────────────────
// Se deduce de la petición para que funcione se llame como se llame
// la carpeta: antes iba escrita a mano y quien clonaba el repositorio
// en otra carpeta recibía las invitaciones con un enlace roto.
// mail_config.php puede imponer otra con 'base_url', que es lo que
// haría falta el día que esto viva en un dominio de verdad.
function planInviteBase(): string
{
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
    return $base;
}

function planInviteLink(string $token): string
{
    return planInviteBase() . '/plan_invitacion.php?token=' . $token;
}

// ── Crear una invitación ─────────────────────────────────────
// Devuelve el enlace en éxito o null en fallo.
//
// $usosMax: cuánta gente puede entrar por ella. 1 es lo de siempre;
//           null significa SIN LÍMITE y es lo que usa el enlace de
//           compartir.
// $enviado: sale a true SÓLO si el correo salió de verdad. Importa:
//           includes/mail_config.php está en .gitignore, así que en
//           las máquinas de los compañeros el envío falla siempre, y
//           sin este dato la ventana diría «invitación enviada»
//           mientras no ha salido nada.
function planInviteCreate(int $planId, string $rol, ?string $email = null, ?int $usosMax = 1, ?bool &$enviado = null): ?string
{
    $enviado = false;
    if (!in_array($rol, ['editor', 'lector'], true)) $rol = 'editor';
    $db = getDB();

    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);
    // Sólo el enlace de compartir guarda el token legible. Una
    // invitación dirigida a una persona no necesita volver a
    // enseñarse, así que sigue siendo únicamente hash.
    $claro = ($email === null) ? $token : null;

    try {
        $stmt = $db->prepare(
            'INSERT INTO plan_invitaciones (plan_id, token_hash, token_claro, rol, email, usos_max, expira_en)
             VALUES (?,?,?,?,?,?, DATE_ADD(NOW(), INTERVAL 7 DAY))'
        );
        $stmt->execute([$planId, $hash, $claro, $rol, $email ?: null, $usosMax]);
    } catch (PDOException $e) {
        error_log('planInviteCreate: ' . $e->getMessage());
        return null;
    }

    $link = planInviteLink($token);

    // Envío de correo (best-effort)
    if ($email) {
        $stmt = $db->prepare('SELECT nombre, destino FROM planes WHERE id = ? LIMIT 1');
        $stmt->execute([$planId]);
        $plan = $stmt->fetch() ?: ['nombre' => 'un viaje', 'destino' => ''];
        try {
            require_once __DIR__ . '/../mailer.php';
            $titulo   = htmlspecialchars($plan['nombre'], ENT_QUOTES, 'UTF-8');
            $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $enviado = enviarCorreo(
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

// ── El enlace para compartir de un plan ──────────────────────
// Devuelve el que ya existe y sigue vigente, y sólo crea uno si no
// hay ninguno. ESTE ES EL ÚNICO SITIO QUE DECIDE ENTRE REUTILIZAR Y
// CREAR, y por eso importa: la ventana pide el enlace cada vez que se
// abre, así que si aquí se creara uno nuevo sin mirar, cada apertura
// dejaría una credencial permanente más en la tabla.
//
// Caduca a los 7 días como todo lo demás. Cuando eso pase, la
// siguiente apertura crea uno nuevo y el viejo deja de valer: quien
// tenga pegado el enlace antiguo en un chat se queda fuera. Es el
// comportamiento de siempre; el botón de «rehacer enlace» sería el
// paso siguiente.
function planEnlaceDePlan(int $planId): ?string
{
    $db = getDB();
    try {
        $stmt = $db->prepare(
            'SELECT token_claro FROM plan_invitaciones
              WHERE plan_id = ? AND email IS NULL AND usos_max IS NULL
                AND token_claro IS NOT NULL
                AND (expira_en IS NULL OR expira_en > NOW())
              ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$planId]);
        $fila = $stmt->fetch();
        if ($fila && !empty($fila['token_claro'])) {
            return planInviteLink((string)$fila['token_claro']);
        }
    } catch (PDOException $e) {
        error_log('planEnlaceDePlan: ' . $e->getMessage());
        return null;
    }
    return planInviteCreate($planId, 'editor', null, null);
}
