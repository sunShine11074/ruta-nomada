<?php
/* ═══════════════════════════════════════════════════════════════
   Ruta Nómada — API: Registro de usuario
   POST  /api/registro.php
   Body: nombre, email, password, password_confirm
   ═══════════════════════════════════════════════════════════════ */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

$nombre           = trim($_POST['nombre']           ?? '');
$email            = trim($_POST['email']             ?? '');
$password         = $_POST['password']               ?? '';
$password_confirm = $_POST['password_confirm']       ?? '';

// ── Validation ──────────────────────────────────────────────
if ($nombre === '' || $email === '' || $password === '') {
    echo json_encode(['ok' => false, 'error' => 'Todos los campos son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'El correo electrónico no es válido.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.']);
    exit;
}

if ($password !== $password_confirm) {
    echo json_encode(['ok' => false, 'error' => 'Las contraseñas no coinciden.']);
    exit;
}

// ── Check duplicate email ───────────────────────────────────
$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'Ya existe una cuenta con ese correo.']);
    exit;
}

// ── Insert ──────────────────────────────────────────────────
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)');
$stmt->execute([$nombre, $email, $hash]);

$userId = $pdo->lastInsertId();

// ── Auto-login after registration ───────────────────────────
$_SESSION['user'] = [
    'id'         => $userId,
    'nombre'     => $nombre,
    'email'      => $email,
    'telefono'   => '',
    'pais'       => 'México',
    'idioma'     => 'Español',
    'creado_en'  => date('Y-m-d H:i:s'),
];

echo json_encode(['ok' => true]);
