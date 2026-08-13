<?php
// ============================================================
//   profile.php — Perfil de usuario | Ruta Nómada
//   Handles profile edit, password change, image upload.
// ============================================================
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/currency.php';
require_once __DIR__ . '/includes/geo_lib.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// ── Ensure upload directory exists ───────────────────────────
$uploadDir = __DIR__ . '/img/profiles';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$db_check = checkDBConnection();
$user     = $_SESSION['user'];
$user_id  = (int)$user['id'];

// ══════════════════════════════════════════════════════════════
//   AJAX POST handlers (return JSON)
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if (!$db_check['ok']) {
        echo json_encode(['ok' => false, 'error' => 'No hay conexión a la base de datos.']);
        exit;
    }
    $db = getDB();

    $action = $_POST['action'];

    // ── Save profile fields ─────────────────────────────────
    if ($action === 'save_profile') {
        // Helper: responde con error de un campo específico y termina.
        $fail = function (string $field, string $msg): void {
            echo json_encode(['ok' => false, 'field' => $field, 'error' => $msg]);
            exit;
        };

        $nombres      = trim($_POST['nombres']   ?? '');
        $apellidos    = trim($_POST['apellidos'] ?? '');
        $email        = trim($_POST['email']     ?? '');
        $genero       = $_POST['genero']         ?? null;
        $telefonoRaw  = trim($_POST['telefono']  ?? '');
        $fechaNac     = trim($_POST['fecha_nacimiento'] ?? '');
        $nacionalidad = trim($_POST['nacionalidad'] ?? '');
        $paisIso      = trim($_POST['pais_iso']   ?? '');
        $estado       = trim($_POST['estado']     ?? '');
        $estadoIso    = trim($_POST['estado_iso'] ?? '');
        $ciudad       = trim($_POST['ciudad']     ?? '');
        $lenguaje     = trim($_POST['lenguaje']   ?? 'Español');
        $divisa       = trim($_POST['divisa']     ?? 'MXN');

        // ── Nombres / Apellidos: solo letras/espacios/guiones/acentos ──
        $nameRe = "/^[\\p{L}][\\p{L}\\s'\\-]{0,59}$/u";
        if ($nombres === '' || !preg_match($nameRe, $nombres)) {
            $fail('nombres', 'Ingresa un nombre válido (solo letras).');
        }
        if ($apellidos === '' || !preg_match($nameRe, $apellidos)) {
            $fail('apellidos', 'Ingresa apellidos válidos (solo letras).');
        }

        // ── Correo: formato + dominio real (MX o A) + unicidad ──
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fail('email', 'El correo no tiene un formato válido.');
        }
        $domain = substr((string) strrchr($email, '@'), 1);
        if ($domain === '' || !(checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A'))) {
            $fail('email', 'El dominio del correo no existe o no puede recibir correo.');
        }
        $chk = $db->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1');
        $chk->execute([$email, $user_id]);
        if ($chk->fetch()) {
            $fail('email', 'El correo ya está registrado por otro usuario.');
        }

        // ── Género ──
        $validGenders = ['Hombre', 'Mujer', 'Otro'];
        if ($genero === '' || ($genero !== null && !in_array($genero, $validGenders, true))) {
            $genero = null;
        }

        // ── Teléfono: 10 dígitos, o 12 empezando con 52 ──
        $digits = preg_replace('/\D+/', '', $telefonoRaw);
        if (!(strlen($digits) === 10 || (strlen($digits) === 12 && substr($digits, 0, 2) === '52'))) {
            $fail('telefono', 'Teléfono inválido. Usa 10 dígitos (o 12 con la lada 52).');
        }
        $telefono = $digits;

        // ── Fecha de nacimiento: real y entre hoy y hace 100 años ──
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNac)) {
            $fail('fecha', 'Selecciona tu fecha de nacimiento.');
        }
        [$fy, $fm, $fd] = array_map('intval', explode('-', $fechaNac));
        if (!checkdate($fm, $fd, $fy)) {
            $fail('fecha', 'La fecha de nacimiento no es válida.');
        }
        $birth   = DateTime::createFromFormat('Y-m-d', $fechaNac);
        $today   = new DateTime('today');
        $minDate = (clone $today)->modify('-100 years');
        if ($birth > $today) {
            $fail('fecha', 'La fecha de nacimiento no puede ser futura.');
        }
        if ($birth < $minDate) {
            $fail('fecha', 'La fecha no puede ser de hace más de 100 años.');
        }

        // ── Divisa ──
        if (!isValidCurrency($divisa)) {
            $fail('divisa', 'Selecciona una divisa válida.');
        }

        // ── Nacionalidad / Estado / Ciudad: reales y consistentes ──
        if ($nacionalidad === '' || $paisIso === '') {
            $fail('nacionalidad', 'Selecciona tu nacionalidad.');
        }
        if ($estado === '' || $estadoIso === '') {
            $fail('estado', 'Selecciona tu estado.');
        }
        if ($ciudad === '') {
            $fail('ciudad', 'Selecciona tu ciudad.');
        }
        $geo = geoValidate($paisIso, $nacionalidad, $estadoIso, $estado, $ciudad);
        if (empty($geo['ok'])) {
            $fail($geo['field'] ?? 'nacionalidad', $geo['error'] ?? 'Ubicación no válida.');
        }

        // ── Guardar ──
        $fullName = trim($nombres . ' ' . $apellidos);
        $stmt = $db->prepare(
            'UPDATE usuarios SET
                nombre=?, apellidos=?, email=?, genero=?, telefono=?,
                fecha_nacimiento=?, nacionalidad=?, estado=?, ciudad=?, lenguaje=?, divisa=?
             WHERE id = ?'
        );
        $stmt->execute([
            $fullName, $apellidos, $email, $genero, $telefono,
            $fechaNac, $nacionalidad, $estado, $ciudad, $lenguaje, $divisa,
            $user_id,
        ]);

        $_SESSION['user']['nombre'] = $fullName;
        $_SESSION['user']['email']  = $email;
        $_SESSION['user']['divisa'] = $divisa;

        echo json_encode([
            'ok'     => true,
            'nombre' => $fullName,
            'email'  => $email,
            'estado' => $estado,
            'ciudad' => $ciudad,
            'divisa' => $divisa,
        ]);
        exit;
    }

    // ── Change password ─────────────────────────────────────
    if ($action === 'change_password') {
        $actual = $_POST['password_actual'] ?? '';
        $nueva  = $_POST['password_nueva']  ?? '';

        // Fetch current hash
        $stmt = $db->prepare('SELECT password_hash FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($actual, $row['password_hash'])) {
            echo json_encode(['ok' => false, 'error' => 'La contraseña actual es incorrecta.']);
            exit;
        }
        if (strlen($nueva) < 6) {
            echo json_encode(['ok' => false, 'error' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
            exit;
        }

        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $upd = $db->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
        $upd->execute([$hash, $user_id]);

        echo json_encode(['ok' => true]);
        exit;
    }

    // ── Upload profile image ────────────────────────────────
    if ($action === 'upload_profile_img' || $action === 'upload_banner_img') {
        $isProfile = ($action === 'upload_profile_img');
        $file = $_FILES['image'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'error' => 'No se recibió la imagen.']);
            exit;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            echo json_encode(['ok' => false, 'error' => 'Formato de imagen no soportado.']);
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'error' => 'La imagen no puede exceder 5 MB.']);
            exit;
        }

        $ext  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime];
        $name = $user_id . ($isProfile ? '_profile.' : '_banner.') . $ext;
        $dest = $uploadDir . '/' . $name;

        // Delete old file if exists
        $col = $isProfile ? 'foto_perfil' : 'foto_banner';
        $old = $db->prepare("SELECT $col FROM usuarios WHERE id = ?");
        $old->execute([$user_id]);
        $oldPath = $old->fetchColumn();
        if ($oldPath && file_exists(__DIR__ . '/' . $oldPath)) {
            @unlink(__DIR__ . '/' . $oldPath);
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['ok' => false, 'error' => 'Error al guardar la imagen.']);
            exit;
        }

        $relPath = 'img/profiles/' . $name;
        $upd = $db->prepare("UPDATE usuarios SET $col = ? WHERE id = ?");
        $upd->execute([$relPath, $user_id]);

        echo json_encode(['ok' => true, 'url' => $relPath]);
        exit;
    }

    // ── Remove images ───────────────────────────────────────
    if ($action === 'remove_images') {
        $stmt = $db->prepare('SELECT foto_perfil, foto_banner FROM usuarios WHERE id = ?');
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        if ($row) {
            if ($row['foto_perfil'] && file_exists(__DIR__ . '/' . $row['foto_perfil'])) @unlink(__DIR__ . '/' . $row['foto_perfil']);
            if ($row['foto_banner'] && file_exists(__DIR__ . '/' . $row['foto_banner'])) @unlink(__DIR__ . '/' . $row['foto_banner']);
        }
        $upd = $db->prepare('UPDATE usuarios SET foto_perfil = NULL, foto_banner = NULL WHERE id = ?');
        $upd->execute([$user_id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Acción no reconocida.']);
    exit;
}

// ══════════════════════════════════════════════════════════════
//   GET — Fetch user data and render page
// ══════════════════════════════════════════════════════════════
$info = $user;
$error_db = null;
if ($db_check['ok']) {
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT id, nombre, apellidos, email, genero, telefono,
                    fecha_nacimiento, nacionalidad, estado, ciudad,
                    lenguaje, divisa, foto_perfil, foto_banner, creado_en
             FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        if ($row) $info = $row;
    } catch (Exception $e) {
        $error_db = $e->getMessage();
    }
} else {
    $error_db = $db_check['error'];
}

// ── Derived display values ──────────────────────────────────
$fullName   = $info['nombre'] ?? $user['nombre'];
$apellidos  = $info['apellidos'] ?? '';

// Split nombre into nombres part (everything except apellidos at the end)
if ($apellidos && str_ends_with($fullName, $apellidos)) {
    $nombres = trim(substr($fullName, 0, -strlen($apellidos)));
} else {
    // Fallback: try splitting at the middle
    $parts = explode(' ', $fullName);
    if (count($parts) >= 3) {
        $half = (int)ceil(count($parts) / 2);
        $nombres   = implode(' ', array_slice($parts, 0, $half));
        $apellidos = implode(' ', array_slice($parts, $half));
    } else {
        $nombres = $fullName;
    }
}

$email       = $info['email']     ?? $user['email'];
$genero      = $info['genero']    ?? '';
$telefono    = $info['telefono']  ?? '';
$fechaNac    = $info['fecha_nacimiento'] ?? null;
$nacionalidad= $info['nacionalidad'] ?? 'Mexicana';
$estado      = $info['estado']    ?? '';
$ciudad      = $info['ciudad']    ?? '';
$lenguaje    = $info['lenguaje']  ?? 'Español';
$divisa      = $info['divisa']    ?? 'MXN';
$fotoPerfil  = $info['foto_perfil'] ?? null;
$fotoBanner  = $info['foto_banner'] ?? null;
$creadoEn    = $info['creado_en'] ?? date('Y-m-d');

$firstName = explode(' ', $fullName)[0] ?: 'Viajera';
$initial   = mb_strtoupper(mb_substr($firstName, 0, 1));
$ubicacion = implode(', ', array_filter([$ciudad, $estado])) ?: 'Ubicación no definida';

require_once __DIR__ . '/includes/user_topbar.php';

// Month names in Spanish for "Se unió en..."
$mesesEs = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$joinDate = 'Se unió en ' . ucfirst($mesesEs[(int)date('n', strtotime($creadoEn))]) . ' ' . date('Y', strtotime($creadoEn));

// Birth date parts
if ($fechaNac) {
    $bp = explode('-', $fechaNac);
    $birthY = (int)$bp[0]; $birthM = (int)$bp[1] - 1; $birthD = (int)$bp[2];
    $birthISO = $fechaNac;
    $birthDisplay = $birthD . ' de ' . $mesesEs[$birthM + 1] . ' de ' . $birthY;
} else {
    $birthY = 2000; $birthM = 0; $birthD = 1;
    $birthISO = '2000-01-01';
    $birthDisplay = 'Selecciona una fecha';
}

// Avatar/banner styles
$avatarBg   = $fotoPerfil ? "background-image:url('{$fotoPerfil}')" : '';
$bannerBg   = $fotoBanner
    ? "background-image:url('{$fotoBanner}');background-size:cover;background-position:center"
    : "background-image:radial-gradient(rgba(6,39,56,.10) 1.3px, transparent 1.3px);background-size:13px 13px;background-position:center";
$hasAnyImg  = ($fotoPerfil || $fotoBanner);

// Helper for escaping
function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Perfil — Ruta Nómada</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="topbar.css">
  <style>
    @keyframes rn-shake { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-5px)} 40%,80%{transform:translateX(5px)} }
    @keyframes rn-pop { 0%{transform:scale(.96);opacity:0} 100%{transform:scale(1);opacity:1} }
    @keyframes rn-fab-in { from{transform:scale(.6);opacity:0} to{transform:scale(1);opacity:1} }
    input, select, button { font-family: inherit; }
    .field-err { display:none; font-size:.76rem; color:#e53e3e; margin-top:.35rem; }
    .field-err.show { display:block; }
    .field-invalid { border-color:#fc8181 !important; box-shadow:0 0 0 3px rgba(229,62,62,.12) !important; }
    .rn-select { width:100%; padding:.7rem 2.2rem .7rem .85rem; border:1.5px solid transparent; border-radius:10px; background-color:#eceff2; font-size:.92rem; color:#1a2e35; outline:none; cursor:pointer; -webkit-appearance:none; appearance:none;
      background-image:url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='17' height='17' viewBox='0 0 24 24' fill='none' stroke='%239aa3ad' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .7rem center; }
    .rn-select:focus { border-color:#3e7986; box-shadow:0 0 0 3px rgba(62,121,134,.15); background-color:#fff; }
    .rn-select:disabled { opacity:.6; cursor:not-allowed; }
  </style>
</head>
<body class="dashboard-page">

<!-- ══════════════════ TOPBAR ══════════════════ -->
<?php $topbar_active = null; include __DIR__ . '/includes/topbar.php'; ?>

<!-- ══════════════════ LAYOUT ══════════════════ -->
<div class="layout">

  <main class="main-content">

    <?php if (!empty($error_db)): ?>
    <div class="alert alert--db alert--inline"><span class="alert__icon">⚠</span><div class="alert__body"><strong>Error de conexión</strong><p><?= e($error_db) ?></p></div></div>
    <?php endif; ?>

    <!-- ───────────── PROFILE CARD ───────────── -->
    <div style="max-width:1090px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 8px 30px rgba(6,39,56,.08),0 1px 3px rgba(0,0,0,.06);overflow:hidden">

      <!-- Banner -->
      <div id="bannerDiv" style="height:150px;background-color:#fff8e7;<?= $bannerBg ?>;position:relative"></div>

      <!-- Avatar + camera -->
      <div style="display:flex;flex-direction:column;align-items:center;padding:0 1.5rem 1rem;position:relative">
        <div style="position:relative;margin-top:-59px">
          <div id="avatarDiv" role="img" aria-label="Foto de perfil" style="width:118px;height:118px;border-radius:50%;border:4px solid #fff;box-shadow:0 4px 14px rgba(0,0,0,.18);background-color:#e2e6ea;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;color:#9aa3ad;overflow:hidden;<?= $avatarBg ?>">
            <?php if (!$fotoPerfil): ?>
            <span id="avatarPlaceholder" style="display:flex">
              <svg width="62" height="62" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8.5" r="4"></circle><path d="M4.5 20c0-4.2 3.4-7 7.5-7s7.5 2.8 7.5 7"></path></svg>
            </span>
            <?php else: ?>
            <span id="avatarPlaceholder" style="display:none">
              <svg width="62" height="62" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8.5" r="4"></circle><path d="M4.5 20c0-4.2 3.4-7 7.5-7s7.5 2.8 7.5 7"></path></svg>
            </span>
            <?php endif; ?>
          </div>
          <!-- camera button + menu -->
          <div data-cam="1" style="position:absolute;right:-6px;bottom:6px">
            <button id="camToggle" type="button" aria-label="Cambiar imagen" style="width:34px;height:34px;border-radius:11px;border:3px solid #fff;background:#062738;color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.25)">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.6A2 2 0 0 1 5 6.6h1.7l1-1.7A1 1 0 0 1 9.5 4.4h5a1 1 0 0 1 .87.5l1 1.7H18a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"></path><circle cx="12" cy="12.4" r="3.2"></circle></svg>
            </button>
            <div id="camMenu" style="display:none;position:absolute;top:42px;left:50%;transform:translateX(-50%);width:218px;background:#fff;border:1px solid #e6e9ee;border-radius:12px;box-shadow:0 12px 32px rgba(6,39,56,.18);padding:.4rem;z-index:50;animation:rn-pop .14s ease">
              <button id="chooseProfile" type="button" style="display:flex;align-items:center;gap:.6rem;width:100%;padding:.6rem .65rem;border:none;background:none;border-radius:8px;cursor:pointer;font-size:.85rem;color:#1a2e35;text-align:left">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#3e7986" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8.5" r="4"></circle><path d="M4.5 20c0-4.2 3.4-7 7.5-7s7.5 2.8 7.5 7"></path></svg>
                Cambiar foto de perfil
              </button>
              <button id="chooseBanner" type="button" style="display:flex;align-items:center;gap:.6rem;width:100%;padding:.6rem .65rem;border:none;background:none;border-radius:8px;cursor:pointer;font-size:.85rem;color:#1a2e35;text-align:left">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#3e7986" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"></rect><circle cx="8.5" cy="10" r="1.6"></circle><path d="m5 18 4.5-4.5 3 3L16 13l3 3"></path></svg>
                Cambiar foto de portada
              </button>
              <div id="camRemoveWrap" style="<?= $hasAnyImg ? '' : 'display:none' ?>">
                <div style="height:1px;background:#eef1f4;margin:.35rem .3rem"></div>
                <button id="removeImages" type="button" style="display:flex;align-items:center;gap:.6rem;width:100%;padding:.6rem .65rem;border:none;background:none;border-radius:8px;cursor:pointer;font-size:.85rem;color:#c0392b;text-align:left">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"></path></svg>
                  Restablecer imágenes
                </button>
              </div>
            </div>
          </div>
        </div>

        <h1 id="displayName" style="font-size:1.9rem;font-weight:700;color:#1a2e35;margin:.7rem 0 .15rem;text-align:center"><?= e($fullName) ?></h1>
        <div style="display:flex;align-items:center;gap:1.4rem;color:#6b7280;font-size:.88rem;flex-wrap:wrap;justify-content:center">
          <span style="display:inline-flex;align-items:center;gap:.4rem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="2.6"></circle></svg>
            <span id="profileLocation"><?= e($ubicacion) ?></span>
          </span>
          <span style="display:inline-flex;align-items:center;gap:.4rem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="18" height="17" rx="2.5"></rect><path d="M3 9h18M8 2.5v4M16 2.5v4"></path></svg>
            <?= e($joinDate) ?>
          </span>
        </div>
      </div>

      <!-- Tabs -->
      <div style="display:flex;gap:1.6rem;padding:0 2rem;border-bottom:1px solid #e6e9ee;margin:.6rem 0 0">
        <button id="tabPerfil" type="button" style="background:none;border:none;cursor:pointer;font-size:.95rem;padding:.7rem .1rem;font-weight:600;color:#062738;border-bottom:2.5px solid #062738;margin-bottom:-1px">Editar perfil</button>
        <button id="tabPass" type="button" style="background:none;border:none;cursor:pointer;font-size:.95rem;padding:.7rem .1rem;font-weight:400;color:#6b7280;border-bottom:2.5px solid transparent;margin-bottom:-1px">Cambiar Contraseña</button>
      </div>

      <!-- ───── TAB: EDITAR PERFIL ───── -->
      <div id="panelPerfil" style="padding:1.6rem 2rem 2rem">
        <input type="hidden" id="inputGenero" value="<?= e($genero) ?>">

        <!-- Gender radios -->
        <div style="display:flex;gap:1.6rem;margin-bottom:1.4rem">
          <span id="genderHombre" style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem;color:#374151">
            <span id="ringHombre" style="width:18px;height:18px;border-radius:50%;border:2px solid <?= $genero==='Hombre'?'#062738':'#cbd2d8' ?>;display:flex;align-items:center;justify-content:center"><span id="dotHombre" style="width:8px;height:8px;border-radius:50%;background:<?= $genero==='Hombre'?'#062738':'transparent' ?>"></span></span> Hombre
          </span>
          <span id="genderMujer" style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem;color:#374151">
            <span id="ringMujer" style="width:18px;height:18px;border-radius:50%;border:2px solid <?= $genero==='Mujer'?'#062738':'#cbd2d8' ?>;display:flex;align-items:center;justify-content:center"><span id="dotMujer" style="width:8px;height:8px;border-radius:50%;background:<?= $genero==='Mujer'?'#062738':'transparent' ?>"></span></span> Mujer
          </span>
          <span id="genderOtro" style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem;color:#374151">
            <span id="ringOtro" style="width:18px;height:18px;border-radius:50%;border:2px solid <?= $genero==='Otro'?'#062738':'#cbd2d8' ?>;display:flex;align-items:center;justify-content:center"><span id="dotOtro" style="width:8px;height:8px;border-radius:50%;background:<?= $genero==='Otro'?'#062738':'transparent' ?>"></span></span> Otro
          </span>
        </div>

        <!-- Nombres / Apellidos -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.1rem;margin-bottom:1.1rem">
          <div style="display:flex;flex-direction:column;min-width:0">
            <label style="font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">Nombres</label>
            <input id="inputNombres" name="nombres" value="<?= e($nombres) ?>" style="width:100%;padding:.7rem .85rem;border:1.5px solid transparent;border-radius:10px;background:#eceff2;font-size:.92rem;color:#1a2e35;outline:none;transition:border-color .15s,box-shadow .15s,background .15s" onfocus="this.style.borderColor='#3e7986';this.style.boxShadow='0 0 0 3px rgba(62,121,134,.15)';this.style.background='#fff'" onblur="this.style.borderColor='transparent';this.style.boxShadow='none';this.style.background='#eceff2'">
            <span class="field-err" id="nombresErr"></span>
          </div>
          <div style="display:flex;flex-direction:column;min-width:0">
            <label style="font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">Apellidos</label>
            <input id="inputApellidos" name="apellidos" value="<?= e($apellidos) ?>" style="width:100%;padding:.7rem .85rem;border:1.5px solid transparent;border-radius:10px;background:#eceff2;font-size:.92rem;color:#1a2e35;outline:none;transition:border-color .15s,box-shadow .15s,background .15s" onfocus="this.style.borderColor='#3e7986';this.style.boxShadow='0 0 0 3px rgba(62,121,134,.15)';this.style.background='#fff'" onblur="this.style.borderColor='transparent';this.style.boxShadow='none';this.style.background='#eceff2'">
            <span class="field-err" id="apellidosErr"></span>
          </div>
        </div>

        <!-- Correo -->
        <div style="display:flex;flex-direction:column;margin-bottom:1.1rem">
          <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"></rect><path d="m3 7 9 6 9-6"></path></svg> Correo Electrónico
          </label>
          <input id="inputEmail" name="email" type="email" value="<?= e($email) ?>" style="width:100%;padding:.7rem .85rem;border:1.5px solid transparent;border-radius:10px;background:#eceff2;font-size:.92rem;color:#1a2e35;outline:none;transition:border-color .15s,box-shadow .15s,background .15s" onfocus="this.style.borderColor='#3e7986';this.style.boxShadow='0 0 0 3px rgba(62,121,134,.15)';this.style.background='#fff'" onblur="this.style.boxShadow='none';this.style.background='#eceff2'">
          <span id="emailErr" style="font-size:.76rem;color:#e53e3e;margin-top:.35rem;display:none"></span>
        </div>

        <!-- Teléfono / Fecha -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.1rem;margin-bottom:1.4rem">
          <div style="display:flex;flex-direction:column;min-width:0">
            <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 21A14.5 14.5 0 0 1 3 7.5 2.5 2.5 0 0 1 5.5 5h2L9 9l-2 1.5a11 11 0 0 0 4.5 4.5L13 13l4 1.5v2A2.5 2.5 0 0 1 16.5 21Z"></path></svg> Teléfono
            </label>
            <input id="inputTelefono" name="telefono" value="<?= e($telefono) ?>" inputmode="tel" placeholder="10 dígitos (o 12 con 52)" style="width:100%;padding:.7rem .85rem;border:1.5px solid transparent;border-radius:10px;background:#eceff2;font-size:.92rem;color:#1a2e35;outline:none;transition:border-color .15s,box-shadow .15s,background .15s" onfocus="this.style.borderColor='#3e7986';this.style.boxShadow='0 0 0 3px rgba(62,121,134,.15)';this.style.background='#fff'" onblur="this.style.borderColor='transparent';this.style.boxShadow='none';this.style.background='#eceff2'">
            <span class="field-err" id="telefonoErr"></span>
          </div>
          <div data-cal="1" style="display:flex;flex-direction:column;min-width:0;position:relative">
            <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="18" height="17" rx="2.5"></rect><path d="M3 9h18M8 2.5v4M16 2.5v4"></path></svg> Fecha de Nacimiento
            </label>
            <input type="hidden" id="birthISO" name="fecha_nacimiento" value="<?= e($birthISO) ?>">
            <button id="calToggle" type="button" style="width:100%;padding:.7rem .85rem;border:1.5px solid transparent;border-radius:10px;background:#eceff2;font-size:.92rem;color:#1a2e35;text-align:left;cursor:pointer;display:flex;align-items:center;justify-content:space-between">
              <span id="birthDisplay"><?= e($birthDisplay) ?></span>
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="18" height="17" rx="2.5"></rect><path d="M3 9h18M8 2.5v4M16 2.5v4"></path></svg>
            </button>

            <!-- Calendar popup -->
            <div id="calPopup" style="display:none;position:absolute;top:100%;left:0;margin-top:8px;width:290px;background:#fff;border:1px solid #e6e9ee;border-radius:14px;box-shadow:0 16px 40px rgba(6,39,56,.2);padding:.9rem;z-index:60;animation:rn-pop .14s ease">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.7rem">
                <button id="calPrev" type="button" aria-label="Anterior" style="width:30px;height:30px;border:none;border-radius:8px;background:#f1f4f7;color:#062738;cursor:pointer;display:flex;align-items:center;justify-content:center"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 6-6 6 6 6"></path></svg></button>
                <button id="calTitle" type="button" style="border:none;background:none;cursor:pointer;font-size:.92rem;font-weight:600;color:#062738;text-transform:capitalize"><span id="calTitleText"></span></button>
                <button id="calNext" type="button" aria-label="Siguiente" style="width:30px;height:30px;border:none;border-radius:8px;background:#f1f4f7;color:#062738;cursor:pointer;display:flex;align-items:center;justify-content:center"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"></path></svg></button>
              </div>
              <!-- Days mode -->
              <div id="calDays">
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:4px">
                  <div style="text-align:center;font-size:.7rem;font-weight:600;color:#9aa3ad;padding:4px 0;text-transform:uppercase">lu</div>
                  <div style="text-align:center;font-size:.7rem;font-weight:600;color:#9aa3ad;padding:4px 0;text-transform:uppercase">ma</div>
                  <div style="text-align:center;font-size:.7rem;font-weight:600;color:#9aa3ad;padding:4px 0;text-transform:uppercase">mi</div>
                  <div style="text-align:center;font-size:.7rem;font-weight:600;color:#9aa3ad;padding:4px 0;text-transform:uppercase">ju</div>
                  <div style="text-align:center;font-size:.7rem;font-weight:600;color:#9aa3ad;padding:4px 0;text-transform:uppercase">vi</div>
                  <div style="text-align:center;font-size:.7rem;font-weight:600;color:#9aa3ad;padding:4px 0;text-transform:uppercase">sá</div>
                  <div style="text-align:center;font-size:.7rem;font-weight:600;color:#9aa3ad;padding:4px 0;text-transform:uppercase">do</div>
                </div>
                <div id="calDaysGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px"></div>
              </div>
              <!-- Years mode -->
              <div id="calYears" style="display:none;grid-template-columns:repeat(4,1fr);gap:6px"></div>
            </div>
            <span class="field-err" id="fechaErr"></span>
          </div>
        </div>

        <!-- Nacionalidad / Estado / Ciudad -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.1rem;margin-bottom:1.1rem">
          <div style="display:flex;flex-direction:column;min-width:0">
            <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18"></path><path d="M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"></path></svg> Nacionalidad
            </label>
            <input type="hidden" id="paisIso" name="pais_iso" value="">
            <div style="position:relative">
              <img id="paisFlag" src="" alt="" width="20" height="14" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);border-radius:2px;display:none;pointer-events:none;z-index:1">
              <select id="inputNacionalidad" name="nacionalidad" class="rn-select" data-selected="<?= e($nacionalidad) ?>"><option value="">Cargando países…</option></select>
            </div>
            <span class="field-err" id="nacErr"></span>
          </div>
          <div style="display:flex;flex-direction:column;min-width:0">
            <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m9 4-6 2v14l6-2 6 2 6-2V4l-6 2-6-2Z"></path><path d="M9 4v14M15 6v14"></path></svg> Estado
            </label>
            <input type="hidden" id="estadoIso" name="estado_iso" value="">
            <select id="inputEstado" name="estado" class="rn-select" data-selected="<?= e($estado) ?>" disabled><option value="">Elige un país primero</option></select>
            <span class="field-err" id="estadoErr"></span>
          </div>
          <div style="display:flex;flex-direction:column;min-width:0">
            <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="7" height="18" rx="1"></rect><rect x="13" y="8" width="7" height="13" rx="1"></rect><path d="M7 7h.01M7 11h.01M7 15h.01M16 12h.01M16 16h.01"></path></svg> Ciudad
            </label>
            <select id="inputCiudad" name="ciudad" class="rn-select" data-selected="<?= e($ciudad) ?>" disabled><option value="">Elige un estado primero</option></select>
            <span class="field-err" id="ciudadErr"></span>
          </div>
        </div>

        <!-- Lenguaje / Divisa -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.1rem;margin-bottom:1.8rem">
          <div style="display:flex;flex-direction:column;min-width:0">
            <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h7M8 4v2c0 3.2-2 6-5 7.2M6 9c0 2 2 4 5 5"></path><path d="m13 19 3.5-8 3.5 8M14.2 16h4.6"></path></svg> Lenguaje
            </label>
            <input type="hidden" id="inputLenguaje" value="<?= e($lenguaje) ?>">
            <div title="Disponible próximamente" style="position:relative;cursor:default"><div style="padding:.7rem .85rem;border:1.5px solid transparent;border-radius:10px;background:#eceff2;font-size:.92rem;color:#1a2e35"><?= e($lenguaje) ?></div><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#9aa3ad" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;right:11px;top:50%;transform:translateY(-50%);pointer-events:none"><path d="m6 9 6 6 6-6"></path></svg></div>
          </div>
          <div style="display:flex;flex-direction:column;min-width:0">
            <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v10M14.6 9.2c0-1.3-1.2-2-2.6-2s-2.6.6-2.6 1.9.9 1.6 2.6 1.9 2.6.8 2.6 1.9-1.2 1.9-2.6 1.9-2.6-.7-2.6-2"></path></svg> Divisa (moneda)
            </label>
            <?php $divisaCc = rnCurrencies()[$divisa]['cc'] ?? 'mx'; ?>
            <div style="position:relative">
              <img id="divisaFlag" src="https://flagcdn.com/16x12/<?= $divisaCc ?>.png" width="20" height="14" alt="" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);border-radius:2px;pointer-events:none;z-index:1">
              <select id="inputDivisa" name="divisa" class="rn-select" style="padding-left:2.4rem">
                <?php foreach (rnCurrencies() as $code => $cur): ?>
                <option value="<?= $code ?>" data-cc="<?= $cur['cc'] ?>" <?= $code === $divisa ? 'selected' : '' ?>><?= $code ?> · <?= e($cur['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <span class="field-err" id="divisaErr"></span>
          </div>
        </div>

        <!-- Save (hold-to-save) -->
        <div id="saveWrapPerfil" style="display:flex;flex-direction:column;gap:.45rem;align-items:flex-start">
          <div class="save-success" style="display:none;align-items:center;gap:.6rem;padding:.55rem .85rem .55rem 1.4rem;background:#1f8a5b;color:#fff;border-radius:12px;font-weight:600;font-size:.98rem">Guardado
            <span style="width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 5 5 9-11"></path></svg></span>
          </div>
          <div class="save-idle" style="display:flex;flex-direction:column;gap:.45rem;align-items:flex-start">
            <button id="saveBtnPerfil" type="button" style="display:inline-flex;align-items:center;gap:.6rem;padding:.55rem .85rem .55rem 1.4rem;background:#062738;color:#fff;border:none;border-radius:12px;font-weight:600;font-size:.98rem;cursor:pointer;user-select:none;touch-action:none;box-shadow:0 3px 10px rgba(6,39,56,.25)">Guardar cambios
              <span style="position:relative;width:36px;height:36px;display:flex;align-items:center;justify-content:center">
                <svg width="36" height="36" viewBox="0 0 36 36" style="position:absolute;inset:0;transform:rotate(-90deg)"><circle cx="18" cy="18" r="15" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="3"></circle><circle data-fill="1" data-len="94.2" cx="18" cy="18" r="15" fill="none" stroke="#f0b429" stroke-width="3" stroke-linecap="round" stroke-dasharray="94.2" stroke-dashoffset="94.2"></circle></svg>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:relative;z-index:1"><path d="M12 19V5M6 11l6-6 6 6"></path></svg>
              </span>
            </button>
            <span style="font-size:.75rem;color:#9aa3ad;padding-left:.2rem">Mantén pulsado para guardar</span>
          </div>
        </div>
      </div>

      <!-- ───── TAB: CAMBIAR CONTRASEÑA ───── -->
      <div id="panelPass" style="display:none;padding:1.6rem 2rem 2rem">
        <div style="display:flex;flex-direction:column;margin-bottom:1.2rem;max-width:430px">
          <label style="font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">Contraseña anterior</label>
          <div style="position:relative;display:flex;align-items:center">
            <input id="pwActual" name="password_actual" type="password" style="width:100%;padding:.7rem 2.6rem .7rem .85rem;border:1.5px solid #e2e6ea;border-radius:10px;background:#eceff2;font-size:.92rem;color:#1a2e35;outline:none;transition:border-color .15s,box-shadow .15s,background .15s" onfocus="this.style.borderColor='#3e7986';this.style.boxShadow='0 0 0 3px rgba(62,121,134,.15)';this.style.background='#fff'" onblur="this.style.borderColor='#e2e6ea';this.style.boxShadow='none';this.style.background='#eceff2'">
            <button id="toggleActual" type="button" aria-label="Mostrar contraseña" style="position:absolute;right:.7rem;background:none;border:none;cursor:pointer;color:#6b7280;display:flex;padding:.2rem">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-7 10-7c2 0 3.8.5 5.4 1.4M22 12s-1.4 2.4-3.9 4.3"></path><path d="M9.5 9.6a3 3 0 0 0 4.2 4.2"></path><path d="m3 3 18 18"></path></svg>
            </button>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.1rem;margin-bottom:1.4rem">
          <div style="display:flex;flex-direction:column;min-width:0">
            <label style="font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">Nueva contraseña</label>
            <div style="position:relative;display:flex;align-items:center">
              <input id="pwNueva" name="password_nueva" type="password" style="width:100%;padding:.7rem 2.6rem .7rem .85rem;border:1.5px solid #e2e6ea;border-radius:10px;background:#eceff2;font-size:.92rem;color:#1a2e35;outline:none;transition:border-color .15s,box-shadow .15s,background .15s" onfocus="this.style.borderColor='#3e7986';this.style.boxShadow='0 0 0 3px rgba(62,121,134,.15)';this.style.background='#fff'" onblur="this.style.borderColor='#e2e6ea';this.style.boxShadow='none';this.style.background='#eceff2'">
              <button id="toggleNueva" type="button" aria-label="Mostrar contraseña" style="position:absolute;right:.7rem;background:none;border:none;cursor:pointer;color:#6b7280;display:flex;padding:.2rem">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-7 10-7c2 0 3.8.5 5.4 1.4M22 12s-1.4 2.4-3.9 4.3"></path><path d="M9.5 9.6a3 3 0 0 0 4.2 4.2"></path><path d="m3 3 18 18"></path></svg>
              </button>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;min-width:0">
            <label style="font-size:.8rem;font-weight:500;color:#6b7280;margin-bottom:7px">Repite la nueva contraseña</label>
            <div style="position:relative;display:flex;align-items:center">
              <input id="pwRepetir" name="password_repetir" type="password" style="width:100%;padding:.7rem 2.6rem .7rem .85rem;border:1.5px solid #e2e6ea;border-radius:10px;background:#eceff2;font-size:.92rem;color:#1a2e35;outline:none;transition:border-color .15s,box-shadow .15s,background .15s" onfocus="this.style.borderColor='#3e7986';this.style.boxShadow='0 0 0 3px rgba(62,121,134,.15)';this.style.background='#fff'" onblur="this.style.boxShadow='none';this.style.background='#eceff2'">
              <button id="toggleRepetir" type="button" aria-label="Mostrar contraseña" style="position:absolute;right:.7rem;background:none;border:none;cursor:pointer;color:#6b7280;display:flex;padding:.2rem">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-7 10-7c2 0 3.8.5 5.4 1.4M22 12s-1.4 2.4-3.9 4.3"></path><path d="M9.5 9.6a3 3 0 0 0 4.2 4.2"></path><path d="m3 3 18 18"></path></svg>
              </button>
            </div>
          </div>
        </div>
        <div id="pwErr" style="font-size:.78rem;color:#e53e3e;margin:-.7rem 0 1.2rem;display:none"></div>

        <!-- Save (hold-to-save) -->
        <div id="saveWrapPass" style="display:flex;flex-direction:column;gap:.45rem;align-items:flex-start">
          <div class="save-success" style="display:none;align-items:center;gap:.6rem;padding:.55rem .85rem .55rem 1.4rem;background:#1f8a5b;color:#fff;border-radius:12px;font-weight:600;font-size:.98rem">Guardado
            <span style="width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 5 5 9-11"></path></svg></span>
          </div>
          <div class="save-idle" style="display:flex;flex-direction:column;gap:.45rem;align-items:flex-start">
            <button id="saveBtnPass" type="button" style="display:inline-flex;align-items:center;gap:.6rem;padding:.55rem .85rem .55rem 1.4rem;background:#062738;color:#fff;border:none;border-radius:12px;font-weight:600;font-size:.98rem;cursor:pointer;user-select:none;touch-action:none;box-shadow:0 3px 10px rgba(6,39,56,.25)">Guardar cambios
              <span style="position:relative;width:36px;height:36px;display:flex;align-items:center;justify-content:center">
                <svg width="36" height="36" viewBox="0 0 36 36" style="position:absolute;inset:0;transform:rotate(-90deg)"><circle cx="18" cy="18" r="15" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="3"></circle><circle data-fill="1" data-len="94.2" cx="18" cy="18" r="15" fill="none" stroke="#f0b429" stroke-width="3" stroke-linecap="round" stroke-dasharray="94.2" stroke-dashoffset="94.2"></circle></svg>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:relative;z-index:1"><path d="M12 19V5M6 11l6-6 6 6"></path></svg>
              </span>
            </button>
            <span style="font-size:.75rem;color:#9aa3ad;padding-left:.2rem">Mantén pulsado para guardar</span>
          </div>
        </div>
      </div>

    </div><!-- /profile card -->

  </main>
</div>

<!-- hidden file inputs -->
<input type="file" id="fileProfile" accept="image/*" style="display:none">
<input type="file" id="fileBanner" accept="image/*" style="display:none">

<script src="js/topbar.js"></script>
<script src="js/profile.js"></script>
</body>
</html>
