<?php

// hola, esta es la prueba si es que sirve el push

// ============================================================
//  login.php — Iniciar sesión | Ruta Nómada
// ============================================================
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/intentos.php';
// Si ya hay sesión activa, redirigir al dashboard
if (!empty($_SESSION['user'])) {
    header('Location: inicio.php');
    exit;
}

$error_db   = null;   // Error de conexión a BD
$error_form = null;   // Error de credenciales/validación
$success    = false;

// ── Verificar conexión a BD al cargar la página ─────────────
$db_check = checkDBConnection();
if (!$db_check['ok']) {
    $error_db = $db_check['error'];
}

// ── Procesar formulario ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_db) {

    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    // Validación básica
    if (!csrfValido()) {
        // Pasa de verdad cuando la pestaña lleva horas abierta y la
        // sesión de PHP ya caducó. Se pide reintentar, no se acusa a
        // nadie: el formulario recargado ya trae un token nuevo.
        $error_form = 'La sesión del formulario caducó. Vuelve a intentarlo.';
    } elseif (empty($email) || empty($password)) {
        $error_form = 'Por favor, completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_form = 'El correo electrónico no tiene un formato válido.';
    } else {
        // Validar que el email pertenezca a dominios permitidos
        $dominios_permitidos = [
            'gmail.com',
            'outlook.com',
            'outlook.es',
            'outlook.com.ar',
            'outlook.com.br',
            'outlook.fr',
            'outlook.de',
            'outlook.it',
            'outlook.jp',
            'yahoo.com',
            'yahoo.es',
            'yahoo.com.br',
            'yahoo.com.ar',
            'yahoo.fr',
            'yahoo.de',
            'yahoo.it',
            'yahoo.jp',
        ];
        
        $dominio_email = strtolower(substr(strrchr($email, '@'), 1));
        $dominio_valido = in_array($dominio_email, $dominios_permitidos, true);
        
        if (!$dominio_valido) {
            $error_form = 'Solo se permiten direcciones de correo de Gmail, Outlook o Yahoo. '
                        . 'Por favor, usa un email de estos proveedores.';
        } else {
            try {
                $db = getDB();
                $ip = ipCliente();

                // ── Freno a la fuerza bruta ──────────────────────
                // Se comprueba ANTES de tocar la contraseña: si hay que
                // frenar, no se gasta un password_verify ni se revela nada.
                if (loginBloqueado($db, $email, $ip)) {
                    $error_form = 'Demasiados intentos fallidos. Espera unos minutos '
                                . 'antes de volver a probar, o restablece tu contraseña.';
                } else {
                    $stmt = $db->prepare('SELECT id, nombre, email, password_hash, divisa FROM usuarios WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    $acertado = $user && password_verify($password, $user['password_hash']);
                    // Queda constancia SIEMPRE, acierte o falle: los aciertos
                    // son los que limpian el contador de esa cuenta.
                    loginRegistrar($db, $email, $ip, $acertado);

                    if ($acertado) {
                        // Login exitoso
                        session_regenerate_id(true);
                        $_SESSION['user'] = [
                            'id'     => $user['id'],
                            'nombre' => $user['nombre'],
                            'email'  => $user['email'],
                            'divisa' => $user['divisa'] ?: 'MXN',
                        ];
                        if ($remember) {
                            // Cookie de 30 días (simplificado — en producción usa token seguro)
                            setcookie('remember_email', $email, time() + 60 * 60 * 24 * 30, '/', '', true, true);
                        }
                        // Retorno pendiente (p. ej. una invitación a un plan)
                        $destino_login = $_SESSION['despues_de_login'] ?? 'inicio.php';
                        unset($_SESSION['despues_de_login']);
                        // Solo rutas internas simples (sin esquemas ni //)
                        if (!preg_match('/^[a-z_]+\.php(\?[a-zA-Z0-9_=&]*)?$/', $destino_login)) {
                            $destino_login = 'inicio.php';
                        }
                        header('Location: ' . $destino_login);
                        exit;
                    } else {
                        $error_form = 'Correo o contraseña incorrectos. Intenta de nuevo.';
                    }
                }
            } catch (RuntimeException $e) {
                $error_db = $e->getMessage();
            }
        }
    }
}

// Pre-llenar email si venía de cookie
$prefill_email = htmlspecialchars(
    $_POST['email'] ?? ($_COOKIE['remember_email'] ?? ''),
    ENT_QUOTES, 'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — Ruta Nómada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
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

        <h2 class="form-title">Iniciar sesión</h2>
        <p class="form-subtitle">
            Bienvenida de vuelta. Continúa planeando tu próxima aventura.
        </p>

        <!-- ── Aviso de contraseña restablecida ── -->
        <?php if (isset($_GET['reset']) && $_GET['reset'] === 'ok'): ?>
        <div class="alert alert--db">
            <span class="alert__icon">✓</span>
            <div class="alert__body">
                <strong>Contraseña actualizada</strong>
                <p>Tu contraseña se cambió correctamente. Ya puedes iniciar sesión.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Error de credenciales ── -->
        <?php if ($error_form): ?>
        <div class="alert alert--form">
            <span class="alert__icon">✕</span>
            <p><?= htmlspecialchars($error_form, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <?= csrfCampo() ?>


            <!-- Email -->
            <div class="field">
                <label class="field__label" for="email">Email</label>
                <div class="field__input-wrap">
                    <span class="field__icon">✉</span>
                    <input
                        class="field__input <?= $error_form ? 'field__input--error' : '' ?>"
                        type="email"
                        id="email"
                        name="email"
                        placeholder="ana@rutanomada.mx"
                        value="<?= $prefill_email ?>"
                        autocomplete="email"
                        <?= $error_db ? 'disabled' : '' ?>
                    >
                </div>
            </div>

            <!-- Contraseña -->
            <div class="field">
                <label class="field__label" for="password">Contraseña</label>
                <div class="field__input-wrap">
                    <span class="field__icon">🔒</span>
                    <input
                        class="field__input <?= $error_form ? 'field__input--error' : '' ?>"
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ingresa tu contraseña"
                        autocomplete="current-password"
                        <?= $error_db ? 'disabled' : '' ?>
                    >
                    <button type="button" class="field__toggle-pw" aria-label="Mostrar contraseña" onclick="togglePassword('password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <!-- Recordar + olvidé -->
            <div class="form-options">
                <label class="checkbox-label">
                    <input
                        type="checkbox"
                        name="remember"
                        class="checkbox-input"
                        <?= !empty($_POST['remember']) ? 'checked' : '' ?>
                        <?= $error_db ? 'disabled' : '' ?>
                    >
                    <span class="checkbox-custom"></span>
                    Recordar mi contraseña
                </label>
                <a href="forgot-password.php" class="link-secondary">¿Olvidaste tu contraseña?</a>
            </div>

            <!-- Botón -->
            <button
                type="submit"
                class="btn-primary"
                <?= $error_db ? 'disabled' : '' ?>
            >
                Ingresar &nbsp;→
            </button>

        </form>

        <p class="form-footer">
            ¿No tienes cuenta? <a href="register.php" class="link-primary">Regístrate</a>
        </p>

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
