<?php
// ============================================================
//  guias.php — Guías de viaje (placeholder) | Ruta Nómada
//  Aquí vivirán las guías/itinerarios curados para descubrir destinos.
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
    <title>Guías de viaje — Ruta Nómada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body class="dashboard-page">

<?php $topbar_active = 'guias'; include __DIR__ . '/includes/topbar.php'; ?>

<div class="layout">
    <main class="main-content">
        <div class="dashboard-header">
            <h2 class="dashboard-title">Guías de viaje</h2>
            <p class="dashboard-subtitle">Itinerarios curados y consejos para descubrir cada destino.</p>
        </div>

        <div style="background:var(--white);border-radius:var(--radius-md);padding:3rem 2rem;text-align:center;">
            <div style="font-size:3rem;margin-bottom:.5rem;">🧭</div>
            <h3 style="font-family:var(--font-display);margin-bottom:.4rem;">Próximamente</h3>
            <p style="color:var(--gray-500);max-width:440px;margin:0 auto;">
                Estamos preparando guías de viaje con rutas recomendadas, lo mejor de cada ciudad
                y consejos para armar tu itinerario en Ruta Nómada.
            </p>
        </div>
    </main>
</div>

<script src="js/topbar.js"></script>
</body>
</html>
