<?php
// ============================================================
//  plan_invitacion.php — Aceptar invitación a un plan | Ruta Nómada
//  ?token=... → valida (hash SHA-256, vigencia, un solo uso),
//  agrega al usuario como miembro y redirige a plan.php?id=N.
// ============================================================
session_start();
require_once __DIR__ . '/db.php';

$token = (string)($_GET['token'] ?? '');
$error = null;

// Sin sesión: guardar el destino y mandar a login
if (empty($_SESSION['user'])) {
    if (preg_match('/^[a-f0-9]{64}$/', $token)) {
        $_SESSION['despues_de_login'] = 'plan_invitacion.php?token=' . $token;
    }
    header('Location: login.php');
    exit;
}

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'El enlace de invitación no es válido.';
} else {
    $db = getDB();
    // `usos < usos_max` en vez de `usada = 0`: el enlace que enseña la
    // ventana «Invita a compañeros de viaje» lleva usos_max NULL, o
    // sea SIN LÍMITE, para que se pueda pegar en un grupo. Las
    // invitaciones por correo siguen llevando usos_max = 1.
    $stmt = $db->prepare(
        'SELECT * FROM plan_invitaciones
          WHERE token_hash = ?
            AND (usos_max IS NULL OR usos < usos_max)
            AND (expira_en IS NULL OR expira_en > NOW()) LIMIT 1'
    );
    $stmt->execute([hash('sha256', $token)]);
    $inv = $stmt->fetch();

    if (!$inv) {
        $error = 'Este enlace de invitación es inválido, ya fue usado o expiró.';
    } else {
        $userId = (int)$_SESSION['user']['id'];
        $planId = (int)$inv['plan_id'];
        $db->beginTransaction();
        try {
            // Si ya es miembro no se duplica ni se degrada su rol, y
            // sobre todo NO se gasta un uso: abrir dos veces el mismo
            // enlace no debería agotarle la invitación a otra persona.
            $chk = $db->prepare('SELECT id FROM plan_miembros WHERE plan_id = ? AND usuario_id = ? LIMIT 1');
            $chk->execute([$planId, $userId]);
            if ($chk->fetch()) {
                $db->commit();
                header('Location: plan.php?id=' . $planId);
                exit;
            }

            // Se gasta el uso ANTES de meter a nadie, con el tope
            // repetido en el WHERE: si dos personas abren a la vez un
            // enlace de un solo uso, las dos pasaron el SELECT de
            // arriba, pero sólo una consigue cambiar la fila aquí.
            //
            // El orden de los SET importa. En MariaDB las asignaciones
            // se evalúan de izquierda a derecha y las siguientes ven
            // el valor YA cambiado; por eso `usada` se calcula primero,
            // cuando `usos` todavía vale lo de antes.
            $gastar = $db->prepare(
                'UPDATE plan_invitaciones
                    SET usada = (usos_max IS NOT NULL AND usos + 1 >= usos_max),
                        usos  = usos + 1
                  WHERE id = ? AND (usos_max IS NULL OR usos < usos_max)'
            );
            $gastar->execute([(int)$inv['id']]);
            if ($gastar->rowCount() === 0) {
                $db->rollBack();
                $error = 'Este enlace de invitación es inválido, ya fue usado o expiró.';
            } else {
                $db->prepare('INSERT INTO plan_miembros (plan_id, usuario_id, rol) VALUES (?,?,?)')
                   ->execute([$planId, $userId, $inv['rol']]);
                $db->commit();
                header('Location: plan.php?id=' . $planId);
                exit;
            }
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('plan_invitacion: ' . $e->getMessage());
            $error = 'No se pudo procesar la invitación. Intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación — Ruta Nómada</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-page" style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
    <div style="background:var(--white);border-radius:14px;box-shadow:0 10px 30px rgba(14,42,51,.15);padding:2.5rem 2.2rem;max-width:430px;text-align:center;">
        <div style="font-size:2.6rem;margin-bottom:.6rem;">✉️</div>
        <h2 style="font-family:var(--font-display);margin-bottom:.5rem;">Invitación no disponible</h2>
        <p style="color:var(--gray-500);margin-bottom:1.4rem;"><?= htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <a href="mis_planes.php" style="display:inline-block;background:#F5B93F;color:#0E2A33;font-weight:700;padding:.7rem 1.5rem;border-radius:22px;text-decoration:none;">Ir a mis planes</a>
    </div>
</body>
</html>
