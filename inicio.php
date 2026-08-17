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
$destinos = [];

// ── Verificar conexión a BD y Consultar Destinos ─────────────
$db_check = checkDBConnection();
if (!$db_check['ok']) {
    $error_db = $db_check['error'];
} else {
    $db = getDB();

    // Optimizamos la validación de categorías
    $categorias_validas = ['Cultura', 'Romance', 'Aventura', 'Descubrimiento'];
    if (!in_array($categoria, $categorias_validas)) {
        $categoria = 'Cultura'; // Categoría por defecto si escriben algo raro en la URL
    }

    $stmt = $db->prepare('SELECT * FROM destinos WHERE categoria = ? ORDER BY id');
    $stmt->execute([$categoria]);
    $destinos = $stmt->fetchAll();
}

// ── Datos de ejemplo ─────────────────────────────────────────
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
<?php $topbar_active = 'inicio'; $topbar_search = true; include __DIR__ . '/includes/topbar.php'; ?>

<!-- ════════════════════════════════════════════════════════════
     LAYOUT PRINCIPAL
════════════════════════════════════════════════════════════ -->
<div class="layout">

    <!-- ── Sidebar (si lo tienes configurado, iría aquí) ── -->

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
             SECCIÓN HERO (Con Carrusel CSS)
        ════════════════════════════════════════════════════════════ -->
        <section class="hero-section">
            <!-- Carrusel de fondo -->
            <div class="hero-slider">
                <!-- Cambia estas URLs por imágenes de tus destinos -->
                <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1499856871958-5b9627545d1a?auto=format&fit=crop&w=1920&q=80');"></div>
                <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?auto=format&fit=crop&w=1920&q=80');"></div>
                <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=1920&q=80');"></div>
            </div>
            
            <!-- Capa oscura para que el texto y el botón resalten -->
            <div class="hero-gradient"></div>
            
            <div class="hero-content">
                <form class="hero-search" id="heroSearch" role="search">
                    <input type="text" id="heroSearchInput" class="hero-search__input" placeholder="Explora tu próximo destino..." aria-label="Buscar destino" autocomplete="off">
                    <button type="submit" class="hero-search__go" aria-label="Buscar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                    </button>
                </form>

                <h1 class="hero-title">¡Tu próxima aventura comienza aquí!</h1>
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

<!-- ════════════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════════ -->
<footer class="site-footer">
    <div class="footer-container">
        <!-- Columna 1: Logo y descripción -->
        <div class="footer-col brand-col">
            <!-- .PNG en mayúsculas: así se llama el archivo en git. Windows no
                 distingue mayúsculas y esto funcionaba igual, pero en Linux el
                 logo desaparecía. Se arregla aquí y NO renombrando el archivo:
                 git en Windows no registra un cambio que sólo sea de caja. -->
            <img src="img/DERIVA EN BLANCO.PNG" alt="Logo Deriva" class="footer-logo">
            <p>Descubre el mundo con nosotros. Creamos rutas, destinos y aventuras diseñadas especialmente a tu medida.</p>
        </div>
        
        <!-- Columna 2: Contacto -->
        <div class="footer-col contact-col">
            <h3>Contacto</h3>
            <ul>
                <li><span>📍</span> Av. Jalisco y 59, San Luis Rio Colorado, Sonora</li>
                <li><span>✉️</span> contacto@rutanomada.com</li>
            </ul>
        </div>
        
        <!-- Columna 3: Enlaces y Redes -->
        <div class="footer-col social-col">
            <h3>Síguenos</h3>
            <div class="social-links">
                <a href="#">Instagram</a>
                <a href="#">Facebook</a>
                <a href="#">TikTok</a>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Ruta Nómada / Deriva. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="js/topbar.js"></script>
</body>
</html>