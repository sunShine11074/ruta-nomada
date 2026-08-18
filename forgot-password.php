<?php
// ============================================================
//  forgot-password.php — Solicitar recuperación | Ruta Nómada
// ============================================================
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/includes/csrf.php';
// Por planInviteBase(): el único sitio del proyecto que arma una URL
// absoluta. Antes este archivo se la montaba por su cuenta y le faltaba
// el valor de reserva (ver abajo).
require_once __DIR__ . '/includes/plan_invite_lib.php';

// Si ya hay sesión activa, redirigir al dashboard
if (!empty($_SESSION['user'])) {
    header('Location: inicio.php');
    exit;
}

$error_db   = null;   // Error de conexión a BD
$error_form = null;   // Error de validación
$enviado    = false;  // Mostrar mensaje genérico de éxito

// ── Verificar conexión a BD al cargar la página ─────────────
$db_check = checkDBConnection();
if (!$db_check['ok']) {
    $error_db = $db_check['error'];
}

// ── Procesar formulario ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_db) {

    $email = trim($_POST['email'] ?? '');

    if (!csrfValido()) {
        $error_form = 'La sesión del formulario caducó. Vuelve a intentarlo.';
    } elseif (empty($email)) {
        $error_form = 'Por favor, ingresa tu correo electrónico.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_form = 'El correo electrónico no tiene un formato válido.';
    } else {
        try {
            $db = getDB();

            // Buscar al usuario (sin revelar si existe)
            $stmt = $db->prepare('SELECT id, nombre FROM usuarios WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generar token de un solo uso; guardamos solo su hash
                $token      = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $token);

                $ins = $db->prepare(
                    'INSERT INTO password_resets (usuario_id, token_hash, expira_en)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
                );
                $ins->execute([$user['id'], $token_hash]);

                // Construir el enlace y enviar el correo.
                //
                // Antes esto era rtrim($cfg['base_url'], '/') a pelo, SIN
                // valor de reserva. Como mail_config.sample.php recomienda
                // dejar 'base_url' vacío, cualquiera que siguiera el ejemplo
                // mandaba correos con «/reset-password.php?token=...»: una
                // ruta relativa, que dentro de un correo es texto muerto.
                // Y no se notaba, porque la pantalla dice «te lo enviamos»
                // pase lo que pase, para no delatar qué correos existen.
                $resetLink = planInviteBase()
                    . '/reset-password.php?token=' . $token
                    . '&email=' . urlencode($email);

                enviarCorreoRecuperacion($email, $user['nombre'], $resetLink);
            }

            // Mensaje genérico SIEMPRE (evita enumeración de usuarios)
            $enviado = true;
        } catch (Exception $e) {
            error_log('[Ruta Nomada] forgot-password: ' . $e->getMessage());
            // Aun ante un error interno mostramos el mensaje genérico
            $enviado = true;
        }
    }
}

$prefill_email = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña — Ruta Nómada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?: 1 ?>">
</head>
<body class="auth-page">

<!-- ╔══════════════════════════════╗ -->
<!-- ║  PANEL IZQUIERDO (oscuro)    ║ -->
<!-- ╚══════════════════════════════╝ -->
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

<!-- ╔══════════════════════════════╗ -->
<!-- ║  PANEL DERECHO (formulario)  ║ -->
<!-- ╚══════════════════════════════╝ -->
<main class="form-panel">
    <div class="form-wrapper">

        <!-- ── Error de base de datos ── -->
        <?php if ($error_db): ?>
        <div class="alert alert--db">
            <span class="alert__icon">⚠</span>
            <div class="alert__body">
                <strong>Error de conexión a la base de datos</strong>
                <p><?= htmlspecialchars($error_db, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$enviado): ?>
        <a href="login.php" class="form-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Volver
        </a>
        <?php endif; ?>

        <h2 class="form-title">Recuperar contraseña</h2>
        <p class="form-subtitle">
            Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
        </p>

        <?php if ($enviado): ?>
            <!-- ── Mensaje genérico de éxito ── -->
            <div class="alert alert--db">
                <span class="alert__icon">✓</span>
                <div class="alert__body">
                    <strong>Revisa tu correo</strong>
                    <p>Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña. El enlace caduca en 1 hora.</p>
                </div>
            </div>
            <p class="form-footer">
                <a href="login.php" class="link-primary">← Volver a iniciar sesión</a>
            </p>
        <?php else: ?>

            <!-- ── Error de validación ── -->
            <?php if ($error_form): ?>
            <div class="alert alert--form">
                <span class="alert__icon">✕</span>
                <p><?= htmlspecialchars($error_form, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" action="forgot-password.php" novalidate>
                <?= csrfCampo() ?>


                <!-- Email -->
                <div class="field">
                    <label class="field__label" for="email">Correo electrónico</label>
                    <div class="field__input-wrap">
                        <span class="field__icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
                        </span>
                        <input
                            class="field__input <?= $error_form ? 'field__input--error' : '' ?>"
                            type="email"
                            id="email"
                            name="email"
                            placeholder="ejemplo@correo.com"
                            value="<?= $prefill_email ?>"
                            autocomplete="email"
                            <?= $error_db ? 'disabled' : '' ?>
                        >
                    </div>
                </div>

                <!-- Botón -->
                <button
                    type="submit"
                    class="btn-primary"
                    style="width:100%; display:flex; align-items:center; justify-content:center; gap:.45rem;"
                    <?= $error_db ? 'disabled' : '' ?>
                >
                    Recuperar contraseña
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                </button>

            </form>

            <p class="form-footer">
                <a href="login.php" class="link-primary">Inicio de sesión</a>
                <span style="margin:0 .5rem; color:var(--gray-500);">|</span>
                <a href="register.php" class="link-primary">Regístrate</a>
            </p>

        <?php endif; ?>

    </div><!-- /.form-wrapper -->
</main>

</body>
</html>
