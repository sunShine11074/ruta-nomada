<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$inicial = mb_strtoupper(mb_substr($user['nombre'], 0, 1));
$error_db = null;
$destinos = [];
$totalDestinos = 0;
$activos = 0;
$promedioPrecio = 0;
$promedioRating = 0;

$buscar = trim($_GET['buscar'] ?? '');
$filtroCategoria = $_GET['categoria'] ?? 'Todas';

$db_check = checkDBConnection();
if ($db_check['ok']) {
    $db = getDB();

    $where = [];
    $params = [];

    if ($buscar !== '') {
        $term = '%' . $buscar . '%';
        $where[] = '(nombre LIKE ? OR pais LIKE ? OR categoria LIKE ? OR descripcion LIKE ?)';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if ($filtroCategoria !== '' && $filtroCategoria !== 'Todas') {
        $where[] = 'categoria = ?';
        $params[] = $filtroCategoria;
    }

    $query = 'SELECT id, nombre, pais, categoria, precio_desde, valoracion, estado, descripcion FROM destinos';
    if ($where) {
        $query .= ' WHERE ' . implode(' AND ', $where);
    }
    $query .= ' ORDER BY id DESC';

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $destinos = $stmt->fetchAll();

    $totalDestinos = (int) $db->query('SELECT COUNT(*) FROM destinos')->fetchColumn();
    $activos = (int) $db->query("SELECT COUNT(*) FROM destinos WHERE LOWER(estado) IN ('activo','publicado')")->fetchColumn();
    $promedioPrecio = $db->query('SELECT AVG(precio_desde) FROM destinos')->fetchColumn();
    $promedioRating = $db->query('SELECT AVG(valoracion) FROM destinos')->fetchColumn();
} else {
    $error_db = $db_check['error'];
}

function badgeClass(string $categoria): string
{
    $slug = strtolower(trim($categoria));
    return match ($slug) {
        'cultura' => 'badge--cultura',
        'romance' => 'badge--romance',
        'aventura' => 'badge--aventura',
        'descubrimiento' => 'badge--descubrimiento',
        default => 'badge--cultura',
    };
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinos — Administración | Ruta Nómada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-page">
<header class="topbar">
    <div class="topbar__left">
        <span class="logo-icon">✦</span>
        <span class="logo-text">Ruta Nómada</span>
    </div>

    <nav class="topbar__nav">
        <a href="inicio.php" class="nav-link">Inicio</a>
        <a href="admin_prin.php" class="nav-link nav-link--active">Destinos</a>
        <a href="#" class="nav-link">Usuarios</a>
        <a href="#" class="nav-link">Viajes</a>
        <a href="#" class="nav-link">Reportes</a>
        <a href="configuracion.php" class="nav-link">Configuración</a>
    </nav>

    <div class="topbar__right">
        <div class="search-bar-mini">
            <span class="search-icon">🔍</span>
            <input type="text" placeholder="Buscar destinos…">
        </div>
        <button class="btn-icon" aria-label="Notificaciones">🔔</button>
        <div class="user-chip">
            <span class="user-chip__greeting">Administrador</span>
            <div class="avatar"><?= htmlspecialchars($inicial, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
</header>

<div class="layout">
    <aside class="sidebar">
        <button class="sidebar-toggle" aria-label="Menú">☰</button>
        <div class="sidebar-user">
            <div class="avatar avatar--lg"><?= htmlspecialchars($inicial, ENT_QUOTES, 'UTF-8') ?></div>
            <div>
                <p class="sidebar-user__name"><?= htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="sidebar-user__email"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <p class="sidebar-section-label">GENERAL</p>
        <nav class="sidebar-nav">
            <a href="inicio.php" class="sidebar-link">Inicio</a>
            <a href="admin_prin.php" class="sidebar-link">Destinos</a>
            <a href="#" class="sidebar-link">Usuarios</a>
            <a href="#" class="sidebar-link">Viajes</a>
        </nav>

        <p class="sidebar-section-label">SISTEMA</p>
        <nav class="sidebar-nav">
            <a href="#" class="sidebar-link">Reportes</a>
            <a href="configuracion.php" class="sidebar-link">Configuración</a>
        </nav>

        <div class="sidebar-footer">
            <a href="admin_prin.php?logout=1" class="sidebar-link sidebar-link--logout">⟵ Cerrar sesión</a>
        </div>
    </aside>

    <main class="main-content">
        <?php if ($error_db): ?>
            <div class="alert alert--db alert--inline">
                <span class="alert__icon">⚠</span>
                <div class="alert__body">
                    <strong>Error de conexión a la base de datos</strong>
                    <p><?= htmlspecialchars($error_db, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="dashboard-header">
            <h2 class="dashboard-title">Gestión de destinos</h2>
            <p class="dashboard-subtitle">Administra, edita y gestiona todos los destinos de la plataforma.</p>
        </div>

        <div class="stats-panel">
            <div class="stats-card">
                <span class="stats-card__label">Total destinos</span>
                <span class="stats-card__value"><?= number_format($totalDestinos) ?></span>
            </div>
            <div class="stats-card">
                <span class="stats-card__label">Activos</span>
                <span class="stats-card__value"><?= number_format($activos) ?></span>
            </div>
            <div class="stats-card">
                <span class="stats-card__label">Precio promedio</span>
                <span class="stats-card__value">$<?= $promedioPrecio ? number_format(round($promedioPrecio)) : '0' ?></span>
            </div>
            <div class="stats-card">
                <span class="stats-card__label">Rating promedio</span>
                <span class="stats-card__value"><?= $promedioRating ? number_format($promedioRating, 1) : '0.0' ?></span>
            </div>
        </div>

        <div class="admin-action-bar">
            <form method="GET" action="admin_prin.php" style="flex:1; display:flex; gap:.75rem; flex-wrap:wrap;">
                <label style="flex:1; min-width:220px;">
                    <input type="text" name="buscar" value="<?= htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar destinos, países, categorías..." style="width:100%; padding:.85rem 1rem; border:1.5px solid var(--gray-300); border-radius:var(--radius-md);">
                </label>
                <label>
                    <select name="categoria" style="padding:.85rem 1rem; border:1.5px solid var(--gray-300); border-radius:var(--radius-md); background:var(--white); font-family:var(--font-body);">
                        <?php foreach (['Todas', 'Cultura', 'Romance', 'Aventura', 'Descubrimiento'] as $category): ?>
                            <option value="<?= $category ?>" <?= $category === $filtroCategoria ? 'selected' : '' ?>><?= $category ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="btn-secondary">Filtrar</button>
            </form>
            <a href="#" class="btn-primary">Nuevo destino</a>
        </div>

        <div class="table-card">
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Destino</th>
                            <th>Categoría</th>
                            <th>Precio (USD)</th>
                            <th>Rating</th>
                            <th>Estado</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($destinos)): ?>
                            <tr>
                                <td colspan="7" class="admin-empty">No hay destinos que mostrar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($destinos as $destino): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($destino['nombre'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <span style="color:var(--gray-500); font-size:.85rem;"><?= htmlspecialchars($destino['pais'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td>
                                        <span class="table-badge <?= badgeClass($destino['categoria']) ?>">
                                            <?= htmlspecialchars($destino['categoria'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>$<?= htmlspecialchars(number_format((float)$destino['precio_desde'], 0, '.', ','), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(number_format((float)$destino['valoracion'], 1), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="status-pill <?= strtolower(trim($destino['estado'])) === 'activo' || strtolower(trim($destino['estado'])) === 'publicado' ? 'status-pill--active' : 'status-pill--inactive' ?>">
                                            <?= htmlspecialchars($destino['estado'] ?: 'Pendiente', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(mb_strimwidth($destino['descripcion'] ?? '', 0, 70, '...'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="#" class="btn-secondary btn-small">Editar</a>
                                            <a href="#" class="btn-secondary btn-small">Eliminar</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<script src="js/sidebar.js"></script>
</body>
</html>
