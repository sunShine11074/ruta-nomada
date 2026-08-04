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

        <!-- Cabecera de bienvenida -->
        <div class="dashboard-header">
            <h2 class="dashboard-title">¡Hola, <?= htmlspecialchars(explode(' ', $user['nombre'])[0], ENT_QUOTES, 'UTF-8') ?>!</h2>
            <p class="dashboard-subtitle">Explora destinos seleccionados para ti y retoma tus rutas guardadas.</p>
        </div>

        <!-- Buscador -->
        <div class="search-bar">
            <span class="search-icon">🔍︎​​</span>
            <input type="text" placeholder="Buscar destinos, actividades, hoteles…">
            <button class="btn-filter">⚙ Filtrar</button>
        </div>

        <!-- ── Recomendado para ti ── -->
        <section class="section">
            <div class="section-header">
                <h3 class="section-title">Recomendado para ti</h3>
            </div>

            <!-- Filtros de categoría -->
            <div class="category-filters">
                <button class="cat-btn cat-btn--active">♜ Cultura</button>
                <button class="cat-btn">❤︎ Romance</button>
                <button class="cat-btn">✈︎ Aventura</button>
                <button class="cat-btn">🔎︎ Descubrimiento</button>
            </div>

           <!-- Tarjetas de destinos -->
<div class="cards-grid">
    <?php foreach (array_slice($destinos, 0, 6) as $d): ?>
    <?php $destId = isset($d['id']) ? (int)$d['id'] : 0; ?>
    <article class="dest-card" <?= $destId ? 'onclick="location.href=\'destino.php?id=' . $destId . '\'"' : '' ?> >
        <div class="dest-card__img">
            <span class="dest-card__tag <?= $d['categoria'] ?? '' ?>"><?= $d['tag'] ?></span>
            <span class="dest-card__img-icon"><?= $d['imagen_url'] ?></span>
            <span class="dest-card__img-label">📍 <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="dest-card__body">
            <h4 class="dest-card__name"><?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?></h4>
            <p class="dest-card__country">📍 <?= htmlspecialchars($d['pais'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="dest-card__desc"><?= htmlspecialchars($d['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
            <div class="dest-card__footer">
                <div>
                    <span class="dest-card__since">Desde</span>
                    <span class="dest-card__price"><?= priceInUserCurrency($d['precio_desde']) ?></span>
                </div>
                <span class="dest-card__rating">⭐ <?= $d['valoracion'] ?></span>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
</div>


<?php if (count($destinos) > 8): ?>
    <div class="see-all-wrap">
        <a href="inicio.php">
            <button class="btn-primary">Ver todos los destinos</button>
        </a>
    </div>
<?php endif; ?>

        <!-- ── Subido recientemente ── -->
        <section class="section">
            <div class="section-header">
                <h3 class="section-title">Subido recientemente</h3>
                <a href="#" class="section-link">Ver más ›</a>
            </div>

            <div class="recents-grid">
                <?php foreach ($recientes as $r): ?>
                <div class="recent-card">
                    <div class="recent-card__thumb"><?= $r['icon'] ?></div>
                    <div class="recent-card__body">
                        <span class="recent-card__time"><?= $r['hace'] ?></span>
                        <p class="recent-card__title"><?= htmlspecialchars($r['titulo'], ENT_QUOTES, 'UTF-8') ?></p>
                        <span class="recent-card__author"><?= htmlspecialchars($r['autor'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    </main>
</div><!-- /.layout -->

<script src="js/topbar.js"></script>
</body>
</html>