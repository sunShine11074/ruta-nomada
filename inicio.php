<?php
// ============================================================
//  inicio.php — Panel principal | Ruta Nómada
// ============================================================
session_start();

// ── Cerrar sesión ────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('remember_email', '', time() - 3600, '/', '', true, true);
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/currency.php';

// Protección de ruta: requiere sesión activa
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user     = $_SESSION['user'];
$error_db = null;
$categoria = $_GET['categoria'] ?? 'Cultura';

// ── Verificar conexión a BD ──────────────────────────────────
$db_check = checkDBConnection();
if (!$db_check['ok']) {
    $error_db = $db_check['error'];
} else {
    $db = getDB();

    if($categoria === 'Cultura') {
        $stmt = $db->prepare('SELECT * FROM destinos WHERE categoria = ? ORDER BY id');
        $stmt->execute([$categoria]);
    }
    if($categoria === 'Romance') {
        $stmt = $db->prepare('SELECT * FROM destinos WHERE categoria = ? ORDER BY id');
        $stmt->execute([$categoria]);
    }
    if($categoria === 'Aventura') {
        $stmt = $db->prepare('SELECT * FROM destinos WHERE categoria = ? ORDER BY id');
        $stmt->execute([$categoria]);
    }
    if($categoria === 'Descubrimiento') {
        $stmt = $db->prepare('SELECT * FROM destinos WHERE categoria = ? ORDER BY id');
        $stmt->execute([$categoria]);
    }

    $destinos = $stmt->fetchAll();
}


// ── Datos de ejemplo (reemplazar con consultas reales) ───────
// Intentar leer destinos desde la base de datos; si falla, usar datos de ejemplo


$recientes = [
    [
        'hace'   => 'Hace 2 días',
        'titulo' => 'Fin de semana en París',
        'autor'  => 'Por Ana López',
        'icon'   => '🏨',
    ],
    [
        'hace'   => 'Hace 5 días',
        'titulo' => 'Ruta Gastronómica: Asia',
        'autor'  => 'Por Carlos M.',
        'icon'   => '🍽',
    ],
];

$nombre_display = htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8');
$email_display  = htmlspecialchars($user['email'],  ENT_QUOTES, 'UTF-8');
$inicial        = mb_strtoupper(mb_substr($user['nombre'], 0, 1));

require_once __DIR__ . '/includes/user_topbar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio — Ruta Nómada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body class="dashboard-page">

<!-- ════════════════════════════════════════════════════════════
     TOPBAR
════════════════════════════════════════════════════════════ -->
<?php $topbar_active = 'inicio'; $topbar_search = false; include __DIR__ . '/includes/topbar.php'; ?>

<!-- ════════════════════════════════════════════════════════════
     LAYOUT PRINCIPAL
════════════════════════════════════════════════════════════ -->
<div class="layout">

    <!-- ── Sidebar ── -->

    <!-- ── Contenido principal ── -->
    <main class="main-content">

        <!-- Error de BD (banner dentro del dashboard) -->
        <?php if ($error_db): ?>
        <div class="alert alert--db alert--inline">
            <span class="alert__icon">⚠</span>
            <div class="alert__body">
                <strong>Error de conexión a la base de datos</strong>
                <p><?= htmlspecialchars($error_db, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════════════════════════
             SECCIÓN HERO
        ════════════════════════════════════════════════════════════ -->
        <section class="hero-section">
            <div class="hero-background">
                <div class="hero-gradient"></div>
            </div>
            
            <div class="hero-content">
                <h1 class="hero-title">¡Tú próxima aventura comienza aquí!</h1>
                <p class="hero-subtitle">Planifica, cotiza y reserva viajes increíbles con la plataforma más completa para viajeros</p>
                
                <div class="hero-buttons">
                    <a href="plan.php" class="btn-cta btn-cta--primary">
                        Planificar Viaje
                    </a>
                </div>
            </div>
        </section>

        <!-- ════════════════════════════════════════════════════════════
             SECCIÓN DE BENEFICIOS
        ════════════════════════════════════════════════════════════ -->
        <section class="benefits-section">
            <h2 class="benefits-title">Todo lo que necesitas para viajar</h2>
            
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">✈︎</div>
                    <h3 class="benefit-name">Destinos Únicos</h3>
                    <p class="benefit-desc">Explora miles de destinos recomendados especialmente para ti.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">$</div>
                    <h3 class="benefit-name">Mejores Precios</h3>
                    <p class="benefit-desc">Cotiza y compara precios para obtener los mejores viajes.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">🗪</div>
                    <h3 class="benefit-name">Colaboración</h3>
                    <p class="benefit-desc">Planifica viajes en equipo y organiza tus aventuras juntos.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">𖡡</div>
                    <h3 class="benefit-name">Mapas Interactivos</h3>
                    <p class="benefit-desc">Visualiza tu ruta completa con mapas detallados en tiempo real.</p>
                </div>
            </div>
        </section>

    </main>
</div><!-- /.layout -->

<script src="js/topbar.js"></script>
</body>
</html>