<?php
// ============================================================
//  includes/user_topbar.php — Carga datos del usuario para topbar/sidebar
//  Requiere: $user = $_SESSION['user'], db.php ya incluido.
// ============================================================

// Fetch foto_perfil and apellidos from DB (not in session by default)
$_topbar_foto   = null;
$_topbar_nombre = $user['nombre'] ?? '';
$_topbar_apellidos = '';

if (!isset($user)) {
    die('Error: Usuario no autenticado.');
}

$db_check_tb = checkDBConnection();
if ($db_check_tb['ok']) {
    try {
        $dbTB   = getDB();
        $stmtTB = $dbTB->prepare('SELECT foto_perfil, apellidos FROM usuarios WHERE id = ? LIMIT 1');
        $stmtTB->execute([$user['id']]);
        $rowTB  = $stmtTB->fetch();
        if ($rowTB) {
            $_topbar_foto      = $rowTB['foto_perfil'] ?? null;
            $_topbar_apellidos = $rowTB['apellidos'] ?? '';
        }
    } catch (Exception $e) {
        // silently ignore — fallback to initial letter
    }
}

// Derived display values for topbar & sidebar
$_topbar_firstName = explode(' ', $_topbar_nombre)[0] ?: 'Viajero';
$_topbar_initial   = mb_strtoupper(mb_substr($_topbar_firstName, 0, 1));
// Last name for topbar: first word of apellidos
$_topbar_lastName  = explode(' ', $_topbar_apellidos)[0] ?? '';
$_topbar_displayName = trim($_topbar_firstName . ' ' . $_topbar_lastName);

// Escaped versions
$_topbar_displayNameE = htmlspecialchars($_topbar_displayName, ENT_QUOTES, 'UTF-8');
$_topbar_nombreE      = htmlspecialchars($_topbar_nombre, ENT_QUOTES, 'UTF-8');
$_topbar_emailE       = htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8');

// Avatar HTML helper — reusable for topbar and sidebar
// $size: '' for topbar (34px), 'avatar--lg' for sidebar (42px)
function renderAvatar(string $size = '', ?string $foto = null, string $initial = 'U'): string {
    $cls = 'avatar' . ($size ? ' ' . $size : '');
    if ($foto) {
        return '<img class="' . $cls . '" src="' . htmlspecialchars($foto, ENT_QUOTES, 'UTF-8') . '" alt="Foto de perfil" style="object-fit:cover;">';
    }
    return '<div class="' . $cls . '">' . $initial . '</div>';
}
