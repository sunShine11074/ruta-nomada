<?php
// ============================================================
//  mis_guardados.php — Mis guardados (placeholder) | Ruta Nómada
//  Lugares, destinos y ciudades que el usuario ha guardado (favoritos).
// ============================================================
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis guardados — Ruta Nómada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?: 1 ?>">
    <link rel="stylesheet" href="topbar.css?v=<?= @filemtime(__DIR__ . '/topbar.css') ?: 1 ?>">
</head>
<body class="dashboard-page">

<?php $topbar_active = null; include __DIR__ . '/includes/topbar.php'; ?>

<div class="layout">
    <main class="main-content">
        <div class="dashboard-header">
            <h2 class="dashboard-title">Mis guardados</h2>
            <p class="dashboard-subtitle">Lugares, destinos y ciudades que guardaste para después.</p>
        </div>

        <div style="background:var(--white);border-radius:var(--radius-md);padding:3rem 2rem;text-align:center;">
            <div style="font-size:3rem;margin-bottom:.5rem;">🔖</div>
            <h3 style="font-family:var(--font-display);margin-bottom:.4rem;">Próximamente</h3>
            <p style="color:var(--gray-500);max-width:440px;margin:0 auto;">
                Aquí verás todo lo que guardes con el corazón ♥ en Ruta Nómada:
                hoteles, restaurantes, cosas que hacer y ciudades.
            </p>
        </div>
    </main>
</div>

<script src="js/topbar.js"></script>
</body>
</html>
