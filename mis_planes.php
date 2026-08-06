<?php
// ============================================================
//  mis_planes.php — Planes de viaje del usuario | Ruta Nómada
//  Lista los planes donde el usuario es miembro (propietario,
//  editor o lector) y enlaza a su espacio de trabajo (plan.php).
// ============================================================
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
require_once __DIR__ . '/includes/user_topbar.php';

$planes = [];
$viajes = [];
$error_db = null;
$db_check = checkDBConnection();
if ($db_check['ok']) {
    $db = getDB();

    // Planes por membresía (los del planificador nuevo)
    $stmt = $db->prepare(
        'SELECT p.id, p.nombre, p.destino, p.fecha_inicio, p.fecha_fin, p.portada_url,
                p.presupuesto, m.rol,
                (SELECT COUNT(*) FROM plan_items i WHERE i.plan_id = p.id)    AS lugares,
                (SELECT COUNT(*) FROM plan_miembros mm WHERE mm.plan_id = p.id) AS miembros
           FROM planes p
           JOIN plan_miembros m ON m.plan_id = p.id AND m.usuario_id = ?
          ORDER BY p.updated_at DESC, p.id DESC'
    );
    $stmt->execute([(int)$user['id']]);
    $planes = $stmt->fetchAll();

    // Viajes antiguos (destinos guardados desde destino.php)
    $stmt = $db->prepare(
        'SELECT d.nombre AS destino_nombre, d.imagen_url AS destino_imagen, v.fecha
           FROM viajes_usuario v JOIN destinos d ON v.destino_id = d.id
          WHERE v.usuario_id = ? ORDER BY v.fecha DESC'
    );
    $stmt->execute([(int)$user['id']]);
    $viajes = $stmt->fetchAll();
} else {
    $error_db = $db_check['error'];
}

function fechasPlan(?string $ini, ?string $fin): string
{
    if (!$ini) return 'Fechas por definir';
    $a = date('d M Y', strtotime($ini));
    if (!$fin || $fin === $ini) return $a;
    return $a . ' – ' . date('d M Y', strtotime($fin));
}
$rolBadge = ['propietario' => '👑 Propietario', 'editor' => '✏️ Editor', 'lector' => '👁 Lector'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mis planes — Ruta Nómada</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="topbar.css">
</head>
<body class="dashboard-page">
<?php $topbar_active = 'misplanes'; include __DIR__ . '/includes/topbar.php'; ?>
<div class="layout">
  <main class="main-content">
    <div class="dashboard-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
      <div>
        <h2 class="dashboard-title">Mis planes</h2>
        <p class="dashboard-subtitle">Tus itinerarios de viaje: los que creaste y los que compartieron contigo.</p>
      </div>
      <a href="plan.php" style="display:inline-flex;align-items:center;gap:8px;background:#F5B93F;color:#0E2A33;font-weight:700;font-size:.85rem;padding:.65rem 1.2rem;border-radius:20px;text-decoration:none;box-shadow:0 2px 8px rgba(245,185,63,.4);">＋ Crear plan de viaje</a>
    </div>

    <?php if (!empty($error_db)): ?>
      <div class="alert alert--db alert--inline"><span class="alert__icon">⚠</span><div class="alert__body"><strong>Error</strong><p><?= htmlspecialchars($error_db, ENT_QUOTES, 'UTF-8') ?></p></div></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
      <?php if (empty($planes) && !$error_db): ?>
        <div style="grid-column:1/-1;background:var(--white);padding:2.2rem;border-radius:var(--radius-md);text-align:center;">
          <div style="font-size:2.4rem;margin-bottom:.4rem;">🗺️</div>
          <strong>Aún no tienes planes de viaje.</strong>
          <p style="color:var(--gray-500);margin:.4rem 0 1rem;">Crea el primero y arma tu itinerario día por día.</p>
          <a href="plan.php" style="display:inline-block;background:#F5B93F;color:#0E2A33;font-weight:700;padding:.7rem 1.4rem;border-radius:20px;text-decoration:none;">Crear plan de viaje</a>
        </div>
      <?php endif; ?>
      <?php foreach ($planes as $p): ?>
        <a href="plan.php?id=<?= (int)$p['id'] ?>" style="background:var(--white);border-radius:var(--radius-md);overflow:hidden;text-decoration:none;color:inherit;box-shadow:0 2px 10px rgba(13,31,39,.07);display:block;transition:transform .15s;"
           onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
          <div style="height:120px;background:#dfe6ec url('<?= htmlspecialchars($p['portada_url'] ?: 'https://picsum.photos/seed/rn-plan-' . (int)$p['id'] . '/600/240', ENT_QUOTES, 'UTF-8') ?>') center/cover;"></div>
          <div style="padding:.9rem 1rem 1rem;">
            <strong style="display:block;font-family:var(--font-display);font-size:1rem;color:#0D1F27;"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
            <span style="display:block;font-size:.8rem;color:var(--gray-500);margin:.2rem 0 .5rem;">
              📅 <?= fechasPlan($p['fecha_inicio'], $p['fecha_fin']) ?>
            </span>
            <span style="display:flex;gap:.8rem;font-size:.75rem;color:var(--gray-500);">
              <span>📍 <?= (int)$p['lugares'] ?> lugares</span>
              <span>👥 <?= (int)$p['miembros'] ?></span>
              <span style="margin-left:auto;"><?= $rolBadge[$p['rol']] ?? '' ?></span>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($viajes)): ?>
      <h3 style="margin:1.6rem 0 .6rem;font-family:var(--font-display);">Destinos guardados</h3>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;">
        <?php foreach ($viajes as $v): ?>
          <div style="background:linear-gradient(135deg,#d4e4d8,#e8ead0);border-radius:var(--radius-md);overflow:hidden;">
            <div style="padding:1rem;min-height:80px;">
              <div style="background:rgba(255,255,255,.9);padding:.6rem;border-radius:8px;">
                <strong><?= htmlspecialchars($v['destino_nombre'] ?: 'Destino', ENT_QUOTES, 'UTF-8') ?></strong>
                <div style="font-size:.8rem;color:var(--gray-700);"><?= $v['fecha'] ? date('d M Y', strtotime($v['fecha'])) : '' ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>
</div>
<script src="js/topbar.js"></script>
</body>
</html>
