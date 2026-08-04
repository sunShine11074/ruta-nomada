<?php
/* ═══════════════════════════════════════════════════════════════
   Ruta Nómada — API: Login
   POST  /api/login.php
   Body: email, password
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

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

// ── Validation ──────────────────────────────────────────────
if ($email === '' || $password === '') {
    echo json_encode(['ok' => false, 'error' => 'Email y contraseña son obligatorios.']);
    exit;
}

// ── Find user ───────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    echo json_encode(['ok' => false, 'error' => 'Credenciales incorrectas.']);
    exit;
}

// ── Set session ─────────────────────────────────────────────
$_SESSION['user'] = [
    'id'         => $user['id'],
    'nombre'     => $user['nombre'],
    'email'      => $user['email'],
    'telefono'   => $user['telefono'],
    'pais'       => $user['pais'],
    'idioma'     => $user['idioma'],
    'creado_en'  => $user['creado_en'],
];

echo json_encode(['ok' => true]);
