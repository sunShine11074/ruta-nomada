<?php
// ============================================================
//   register.php — Crear cuenta | Ruta Nómada
// ============================================================
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/email_dominio.php';

// Si ya hay sesión activa, redirigir al dashboard
if (!empty($_SESSION['user'])) {
    header('Location: inicio.php');
    exit;
}

$error_db   = null;   // Error de conexión a BD
$error_form = null;   // Error de validación de formulario
$success    = false;
$nombre     = '';
$email      = '';
$password   = '';
$confirm_password = '';
$terms      = false;

// ── Verificar conexión a BD al cargar la página ─────────────
$db_check = checkDBConnection();
if (!$db_check['ok']) {
    $error_db = $db_check['error'];
}

// ── Procesar formulario de registro ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_db) {

    $nombre           = trim($_POST['nombre'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms            = !empty($_POST['terms']);

    // Validación básica del lado del servidor
    if (!csrfValido()) {
        $error_form = 'La sesión del formulario caducó. Vuelve a intentarlo.';
    } elseif (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_form = 'Por favor, completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_form = 'El correo electrónico no tiene un formato válido.';
    } else {
        // La lista de proveedores vive en includes/email_dominio.php, en
        // UN solo sitio: estaba copiada aquí y en login.php, y las dos
        // copias se desincronizaron.
        if (!dominioPermitido($email)) {
            $error_form = mensajeDominioNoPermitido();
        } elseif (!correoConDominioReal($email)) {
            // Caza-erratas, no control de seguridad: sólo dice que ese
            // dominio no tiene por dónde recibir correo. Ver el comentario
            // largo de includes/email_dominio.php.
            $error_form = 'El dominio «' . dominioDeCorreo($email) . '» no puede recibir correo. '
                        . 'Revisa que esté bien escrito.';
        } elseif (strlen($password) < 6) {
            $error_form = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($password !== $confirm_password) {
            $error_form = 'Las contraseñas no coinciden.';
        } elseif (!$terms) {
            $error_form = 'Debes aceptar los Términos de Servicio para registrarte.';
        } else {
            try {
                $db = getDB();

                // Encriptar la contraseña de forma segura (el hash se genera
                // en PHP; la contraseña en texto plano nunca viaja a MySQL)
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Registrar mediante el procedimiento almacenado:
                // verifica el correo duplicado e inserta en una sola operación,
                // y devuelve el id generado (ver basedatos/procedures.sql)
                $stmt = $db->prepare('CALL sp_registrar_usuario(?, ?, ?)');
                $stmt->execute([$nombre, $email, $password_hash]);
                $userId = (int) $stmt->fetchColumn();
                $stmt->closeCursor();

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'     => $userId,
                    'nombre' => $nombre,
                    'email'  => $email,
                ];

                // Redirigir directamente al panel principal tras el éxito
                header('Location: inicio.php');
                exit;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'EMAIL_DUPLICADO') !== false) {
                    $error_form = 'El correo electrónico ya está registrado. Intenta iniciar sesión.';
                } else {
                    // El detalle va al registro del servidor, nunca a la pantalla:
                    // traía el nombre de la tabla, la columna y el motor. db.php ya
                    // aplica este criterio y aquí se estaba deshaciendo.
                    error_log('register.php: ' . $e->getMessage());
                    // $error_form y no $error_db a propósito: la conexión ya se
                    // comprobó al cargar la página, así que lo que falla aquí es
                    // casi siempre el dato. Con $error_db el formulario se
                    // deshabilita y la persona se queda sin poder corregir.
                    $error_form = 'No se pudo crear la cuenta. Revisa los datos e inténtalo de nuevo.';
                }
            }
        }
    }
}

// Conservar valores ingresados para evitar que el usuario vuelva a escribir todo
$prefill_nombre = htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
$prefill_email  = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta — Ruta Nómada</title>
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

        <h2 class="form-title">Crear cuenta</h2>
        <p class="form-subtitle">
            Crea una cuenta para comenzar a planear tus rutas.
        </p>

        <?php if ($error_form): ?>
        <div class="alert alert--form">
            <span class="alert__icon">✕</span>
            <p><?= htmlspecialchars($error_form, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>
            <?= csrfCampo() ?>


            <div class="field">
                <label class="field__label" for="nombre">Nombre</label>
                <div class="field__input-wrap">
                    <span class="field__icon">👤</span>
                    <input
                        class="field__input <?= $error_form && empty($nombre) ? 'field__input--error' : '' ?>"
                        type="text"
                        id="nombre"
                        name="nombre"
                        placeholder="Tu nombre completo"
                        value="<?= $prefill_nombre ?>"
                        autocomplete="name"
                        <?= $error_db ? 'disabled' : '' ?>
                    >
                </div>
            </div>

            <div class="field">
                <label class="field__label" for="email">Email</label>
                <div class="field__input-wrap">
                    <span class="field__icon">✉</span>
                    <input
                        class="field__input <?= $error_form && (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($email)) ? 'field__input--error' : '' ?>"
                        type="email"
                        id="email"
                        name="email"
                        placeholder="tu@email.com"
                        value="<?= $prefill_email ?>"
                        autocomplete="email"
                        <?= $error_db ? 'disabled' : '' ?>
                    >
                </div>
            </div>

            <div class="field">
                <label class="field__label" for="password">Contraseña</label>
                <div class="field__input-wrap">
                    <span class="field__icon">🔒</span>
                    <input
                        class="field__input <?= $error_form && (strlen($password) < 6 || empty($password)) ? 'field__input--error' : '' ?>"
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        <?= $error_db ? 'disabled' : '' ?>
                    >
                    <button type="button" class="field__toggle-pw" aria-label="Mostrar contraseña" onclick="togglePassword('password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <div class="field">
                <label class="field__label" for="confirm_password">Confirmar contraseña</label>
                <div class="field__input-wrap">
                    <span class="field__icon">🔒</span>
                    <input
                        class="field__input <?= $error_form && ($password !== $confirm_password) ? 'field__input--error' : '' ?>"
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        <?= $error_db ? 'disabled' : '' ?>
                    >
                    <button type="button" class="field__toggle-pw" aria-label="Mostrar contraseña" onclick="togglePassword('confirm_password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <div class="form-options field-terms">
                <label class="checkbox-label">
                    <input
                        type="checkbox"
                        name="terms"
                        id="terms"
                        class="checkbox-input"
                        <?= !empty($_POST['terms']) ? 'checked' : '' ?>
                        <?= $error_db ? 'disabled' : '' ?>
                    >
                    <span class="checkbox-custom"></span>
                    He leído y acepto los <a href="#" class="link-primary" style="font-weight: 500; font-size: inherit;">Términos de Servicio</a>
                </label>
            </div>

            <button
                type="submit"
                class="btn-primary"
                <?= $error_db ? 'disabled' : '' ?>
            >
                Registrarse &nbsp;→
            </button>

        </form>

        <p class="form-footer">
            ¿Ya tienes una cuenta? <a href="login.php" class="link-primary">Inicia sesión</a>
        </p>

    </div></main>

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