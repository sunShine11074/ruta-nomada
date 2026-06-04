<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ruta Nómada — Prototipo</title>
  <meta name="description" content="Planifica viajes con alma de explorador. Descubre destinos, cotiza tu ruta y reparte gastos con tu grupo." />

  <!-- Fonts: Noto Serif + Inter + Source Code Pro (per design system) -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Serif:wght@600;700&family=Source+Code+Pro:wght@500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  <!-- Design tokens + app styles -->
  <link rel="stylesheet" href="css/colors_renm.css" />
  <link rel="stylesheet" href="css/fonts_renm.css" />
  <link rel="stylesheet" href="css/ruta.css" />
</head>
<body>
  <template id="__bundler_thumbnail" data-bg-color="#15323d">
    <svg viewBox="0 0 1200 800" xmlns="http://www.w3.org/2000/svg">
      <rect width="1200" height="800" fill="#15323d" />
      <circle cx="600" cy="360" r="150" fill="none" stroke="#FDF6E3" stroke-width="9" opacity="0.85" />
      <circle cx="600" cy="360" r="105" fill="none" stroke="#FDF6E3" stroke-width="4" opacity="0.4" />
      <path d="M600 245 L632 360 L600 475 L568 360 Z" fill="#FAD564" />
      <path d="M600 328 L715 360 L600 392 L485 360 Z" fill="#FDF6E3" opacity="0.92" />
      <circle cx="600" cy="360" r="20" fill="#15323d" />
      <text x="600" y="600" font-family="Georgia, 'Noto Serif', serif" font-size="76" font-weight="700" fill="#FDF6E3" text-anchor="middle">Ruta Nómada</text>
    </svg>
  </template>

  <?php require_once __DIR__ . '/includes/data.php'; ?>
  <?php require_once __DIR__ . '/includes/components.php'; ?>

  <!-- AUTH FLOW (shown when not authenticated) -->
  <?php include __DIR__ . '/includes/auth.php'; ?>

  <!-- APP SHELL (shown when authenticated) -->
  <div class="app" id="app-shell" style="display:none;">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="app__body">
      <?php include __DIR__ . '/includes/sidebar.php'; ?>
      <main class="main">
        <div class="main__inner">
          <?php include __DIR__ . '/includes/screen_inicio.php'; ?>
          <?php include __DIR__ . '/includes/screen_explorar.php'; ?>
          <?php include __DIR__ . '/includes/screen_misviajes.php'; ?>
          <?php include __DIR__ . '/includes/screen_comunidad.php'; ?>
          <?php include __DIR__ . '/includes/screen_perfil.php'; ?>
          <?php include __DIR__ . '/includes/screen_misplanes.php'; ?>
          <?php include __DIR__ . '/includes/screen_config.php'; ?>
          <?php include __DIR__ . '/includes/screen_detail.php'; ?>
        </div>
      </main>
    </div>
  </div>

  <!-- Vanilla JS — replaces React + Babel -->
  <script src="js/app.js"></script>
</body>
</html>
