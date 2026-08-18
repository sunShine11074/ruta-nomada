<?php
session_start();
require_once __DIR__ . '/db.php';
if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
$user = $_SESSION['user'];
$inicial = mb_strtoupper(mb_substr($user['nombre'],0,1));
require_once __DIR__ . '/includes/user_topbar.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Configuración — Ruta Nómada</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?: 1 ?>">
    <link rel="stylesheet" href="topbar.css?v=<?= @filemtime(__DIR__ . '/topbar.css') ?: 1 ?>">
</head>
<body class="dashboard-page">
<?php $topbar_active = null; include __DIR__ . '/includes/topbar.php'; ?>
<div class="layout">
  <main class="main-content">
    <div class="dashboard-header"><h2 class="dashboard-title">Configuración</h2><p class="dashboard-subtitle">Administra notificaciones, privacidad y preferencias.</p></div>
    <div style="display:grid;grid-template-columns:1fr 320px;gap:1rem;">
      <div style="background:var(--white);padding:1rem;border-radius:var(--radius-md);">
        <h3 style="margin-bottom:.5rem;">Notificaciones</h3>
        <label class="checkbox-label"><input type="checkbox" class="checkbox-input" checked><span class="checkbox-custom"></span>Notificaciones por correo</label>
        <label class="checkbox-label" style="margin-top:.6rem;"><input type="checkbox" class="checkbox-input"><span class="checkbox-custom"></span>Notificaciones push</label>
        <label class="checkbox-label" style="margin-top:.6rem;"><input type="checkbox" class="checkbox-input" checked><span class="checkbox-custom"></span>Ofertas y promociones</label>
      </div>
      <aside style="background:var(--white);padding:1rem;border-radius:var(--radius-md);">
        <h4>Preferencias</h4>
        <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:.6rem;"><div>Moneda: MXN</div><div>Idioma: Español</div><div>Zona horaria: GMT-6</div><label class="checkbox-label" style="margin-top:.3rem;"><input type="checkbox" class="checkbox-input"><span class="checkbox-custom"></span>Tema oscuro</label></div>
        <div style="margin-top:1rem;display:flex;gap:.6rem;"><button class="btn-primary">Guardar cambios</button><button style="padding:.6rem;border-radius:8px;border:1px solid var(--gray-300);background:transparent;">Cancelar</button></div>
      </aside>
    </div>
  </main>
</div>
<script src="js/topbar.js"></script>
</body>
</html>
