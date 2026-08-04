<?php
// ============================================================
//  reset-password.php — Definir nueva contraseña | Ruta Nómada
// ============================================================
session_start();
require_once __DIR__ . '/db.php';

// Si ya hay sesión activa, redirigir al dashboard
if (!empty($_SESSION['user'])) {
    header('Location: inicio.php');
    exit;
}

$error_db    = null;   // Error de conexión a BD
$error_form  = null;   // Error de validación del formulario
$token_valid = false;  // ¿El token es válido?
$reset_row   = null;   // Fila de password_resets en uso

// Token y email vienen por GET (enlace del correo) o por POST (envío del form)
$token = $_POST['token'] ?? $_GET['token'] ?? '';
$email = $_POST['email'] ?? $_GET['email'] ?? '';

$db_check = checkDBConnection();
if (!$db_check['ok']) {
    $error_db = $db_check['error'];
} else {
    $db = getDB();

    // ── Validar el token ─────────────────────────────────────
    if ($token !== '' && $email !== '') {
        $token_hash = hash('sha256', $token);
        $stmt = $db->prepare(
            'SELECT pr.id, pr.usuario_id
             FROM password_resets pr
             JOIN usuarios u ON u.id = pr.usuario_id
             WHERE pr.token_hash = ? AND u.email = ?
               AND pr.usado = 0 AND pr.expira_en > NOW()
             LIMIT 1'
        );
        $stmt->execute([$token_hash, $email]);
        $reset_row = $stmt->fetch();
        $token_valid = (bool) $reset_row;
    }

    // ── Procesar nueva contraseña ────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!$token_valid) {
            $error_form = 'El enlace es inválido o ha expirado. Solicita uno nuevo.';
        } elseif (empty($password) || empty($confirm_password)) {
            $error_form = 'Por favor, completa ambos campos.';
        } elseif (strlen($password) < 6) {
            $error_form = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($password !== $confirm_password) {
            $error_form = 'Las contraseñas no coinciden.';
        } else {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Actualizar la contraseña del usuario
                $upd = $db->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
                $upd->execute([$password_hash, $reset_row['usuario_id']]);

                // Marcar el token como usado (un solo uso)
                $mark = $db->prepare('UPDATE password_resets SET usado = 1 WHERE id = ?');
                $mark->execute([$reset_row['id']]);

                // Listo: redirigir al login con aviso de éxito
                header('Location: login.php?reset=ok');
                exit;
            } catch (Exception $e) {
                error_log('[Ruta Nomada] reset-password: ' . $e->getMessage());
                $error_form = 'Ocurrió un error al actualizar la contraseña. Intenta de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña — Ruta Nómada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<aside class="hero-panel">
    <div class="hero-logo">
        <span class="logo-icon">✦</span>
        <span class="logo-text">Ruta Nómada</span>
    </div>

    <div class="hero-content">
        <h1 class="hero-heading">
            Planifica viajes con alma de explorador y precisión de contador.
        </h1>
        <p class="hero-subtext">
            Descubre destinos, cotiza tu ruta<br>
            y reparte gastos con tu grupo — todo en<br>
            un mismo lugar.
        </p>
    </div>

    <div class="hero-stats">
        <div class="stat">
            <span class="stat-value">120+</span>
            <span class="stat-label">DESTINOS</span>
        </div>
        <div class="stat">
            <span class="stat-value">48k</span>
            <span class="stat-label">VIAJEROS</span>
        </div>
        <div class="stat">
            <span class="stat-value">4.9★</span>
            <span class="stat-label">VALORACIÓN</span>
        </div>
    </div>
</aside>

<main class="form-panel">
    <div class="form-wrapper">

        <?php if ($error_db): ?>
        <div class="alert alert--db">
            <span class="alert__icon">⚠</span>
            <div class="alert__body">
                <strong>Error de conexión a la base de datos</strong>
                <p><?= htmlspecialchars($error_db, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <?php endif; ?>

        <h2 class="form-title">Nueva contraseña</h2>
        <p class="form-subtitle">
            Crea una contraseña nueva para tu cuenta.
        </p>

        <?php if (!$error_db && !$token_valid): ?>
            <!-- ── Token inválido / expirado ── -->
            <div class="alert alert--form">
                <span class="alert__icon">✕</span>
                <p>El enlace es inválido o ha expirado. Por seguridad, los enlaces caducan en 1 hora y solo pueden usarse una vez.</p>
            </div>
            <p class="form-footer">
                <a href="forgot-password.php" class="link-primary">Solicitar un nuevo enlace</a>
            </p>
        <?php elseif (!$error_db): ?>

            <?php if ($error_form): ?>
            <div class="alert alert--form">
                <span class="alert__icon">✕</span>
                <p><?= htmlspecialchars($error_form, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" action="reset-password.php" novalidate>

                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">

                <!-- Contraseña -->
                <div class="field">
                    <label class="field__label" for="password">Nueva contraseña</label>
                    <div class="field__input-wrap">
                        <span class="field__icon">🔒</span>
                        <input
                            class="field__input <?= $error_form ? 'field__input--error' : '' ?>"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="new-password"
                        >
                        <button type="button" class="field__toggle-pw" aria-label="Mostrar contraseña" onclick="togglePassword('password', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Confirmar contraseña -->
                <div class="field">
                    <label class="field__label" for="confirm_password">Confirmar contraseña</label>
                    <div class="field__input-wrap">
                        <span class="field__icon">🔒</span>
                        <input
                            class="field__input <?= $error_form ? 'field__input--error' : '' ?>"
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="••••••••"
                            autocomplete="new-password"
                        >
                        <button type="button" class="field__toggle-pw" aria-label="Mostrar contraseña" onclick="togglePassword('confirm_password', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    Cambiar contraseña &nbsp;→
                </button>

            </form>

            <p class="form-footer">
                <a href="login.php" class="link-primary">← Volver a iniciar sesión</a>
            </p>

        <?php endif; ?>

    </div><!-- /.form-wrapper -->
</main>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.classList.add('active');
    } else {
        input.type = 'password';
        btn.classList.remove('active');
    }
}
</script>
</body>
</html>
