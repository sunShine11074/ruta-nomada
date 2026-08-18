<?php
// ============================================================
//  includes/plan_nuevo.php — Pantalla «nuevo viaje» | Ruta Nómada
//
//  La sirve plan.php cuando la URL no trae ?id=. Pide lo único que
//  el espacio de trabajo no puede inventarse (destino y, si se
//  quiere, fechas), crea el plan con api/plan_create.php y entra a
//  plan.php?id=N.
//
//  Antes esto vivía en crear_plan.php, que además arrastraba un
//  planificador viejo guardado en localStorage. Ese planificador ya
//  no existe: el itinerario real es plan.php.
//
//  Espera definidas: $user (sesión) y $plan_csrf.
// ============================================================
if (empty($user) || !isset($plan_csrf)) {
    http_response_code(500);
    exit('plan_nuevo.php requiere $user y $plan_csrf.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo viaje — Ruta Nómada</title>
    <meta name="description" content="Crea un itinerario de viaje colaborativo con mapa, rutas y presupuesto. Ruta Nómada — tu compañero de viajes.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?: 1 ?>">
    <link rel="stylesheet" href="topbar.css?v=<?= @filemtime(__DIR__ . '/topbar.css') ?: 1 ?>">
    <link rel="stylesheet" href="crear_plan.css">
    <style>
        /* Retoques propios de esta pantalla (el resto sale de crear_plan.css) */
        .pn-sub {
            max-width: 520px;
            margin: 12px auto 0;
            text-align: center;
            font-size: 14px;
            line-height: 1.55;
            color: var(--cp-text-hint);
        }
        .pn-opt { font-weight: 500; color: var(--cp-text-hint); }
        .pn-back {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-link);
            text-decoration: none;
        }
        .pn-back:hover { text-decoration: underline; }
        .pn-err {
            margin: 0;
            font-size: 13px;
            font-weight: 500;
            color: #c0341d;
            text-align: center;
        }
        .cp-ac-row.on { background: var(--cp-bg-soft); }
        .cp-ac-row[aria-disabled="true"] { cursor: default; }
        .cp-ac-row[aria-disabled="true"]:hover { background: none; }
    </style>
</head>
<body class="dashboard-page">

<?php $topbar_active = 'misplanes'; $topbar_search = true; include __DIR__ . '/topbar.php'; ?>

<main class="cp-screen active cp-create">
  <h1 class="cp-create__title">Planifica un nuevo viaje</h1>
  <p class="pn-sub">Dinos a dónde vas y, si ya las tienes, las fechas. Después podrás
     añadir lugares, moverlos entre días y ver la ruta en el mapa.</p>

  <div class="cp-create__form">

    <!-- Destino -->
    <div class="cp-input-card" id="pnDestCard">
      <label for="pnDest">¿A dónde vas?</label>
      <input type="text" id="pnDest" placeholder="p. ej. Ensenada, Oaxaca, París…"
             autocomplete="off" role="combobox" aria-expanded="false"
             aria-autocomplete="list" aria-controls="pnDestSug">
      <div class="cp-autocomplete" id="pnDestSug" role="listbox" aria-label="Sugerencias de destino" hidden></div>
    </div>

    <!-- Fechas -->
    <div class="cp-input-card" id="pnDateCard" style="position:relative">
      <label>Fechas <span class="pn-opt">(puedes ponerlas después)</span></label>
      <div class="cp-dates-row">
        <button class="cp-date-btn" id="pnStartBtn" type="button" aria-haspopup="dialog">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2b5760" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span id="pnStartLbl">Fecha de inicio</span>
        </button>
        <span class="cp-date-sep"></span>
        <button class="cp-date-btn" id="pnEndBtn" type="button" aria-haspopup="dialog">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2b5760" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span id="pnEndLbl">Fecha final</span>
        </button>
      </div>

      <div class="cp-calendar" id="pnCalWrap" role="dialog" aria-label="Elegir fechas del viaje" hidden>
        <div class="cp-cal-grid">
          <button class="cp-cal-nav prev" id="pnCalPrev" type="button" aria-label="Mes anterior">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <div class="cp-cal-month">
            <div class="cp-cal-month__title" id="pnCalTitle0"></div>
            <div class="cp-cal-days" id="pnCalGrid0"></div>
          </div>
          <div class="cp-cal-month">
            <div class="cp-cal-month__title" id="pnCalTitle1"></div>
            <div class="cp-cal-days" id="pnCalGrid1"></div>
          </div>
          <button class="cp-cal-nav next" id="pnCalNext" type="button" aria-label="Mes siguiente">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
          </button>
        </div>
        <div class="cp-cal-footer">
          <button id="pnCalClear" type="button">Limpiar fechas</button>
          <button id="pnCalDone" type="button" class="done">Listo</button>
        </div>
      </div>
    </div>

    <!-- Invitar + privacidad -->
    <div class="cp-meta-row">
      <button class="cp-invite-toggle" id="pnInvToggle" type="button" aria-expanded="false" aria-controls="pnInvRow">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
        Invitar
      </button>
      <div style="position:relative">
        <button class="cp-privacy-btn" id="pnPrivBtn" type="button" aria-haspopup="menu" aria-expanded="false">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span id="pnPrivLabel">Amigos</span>
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="cp-dropdown" id="pnPrivDrop" role="menu" hidden>
          <button class="cp-dropdown-item" type="button" role="menuitem" data-priv="amigos">👥 Amigos</button>
          <button class="cp-dropdown-item" type="button" role="menuitem" data-priv="solo">🔒 Solo yo</button>
          <button class="cp-dropdown-item" type="button" role="menuitem" data-priv="publico">🌐 Público</button>
        </div>
      </div>
    </div>

    <!-- Correos invitados -->
    <div class="cp-invite-bar" id="pnInvRow" hidden>
      <span id="pnInvPills"></span>
      <input type="text" id="pnInvInput" placeholder="Escribe un correo y presiona Enter" aria-label="Correo de un compañero de viaje">
    </div>

    <!-- CTA -->
    <div class="cp-cta-area">
      <button class="cp-cta" id="pnGo" type="button" disabled>Comienza a planificar</button>
      <p class="pn-err" id="pnErr" role="alert" hidden></p>
      <a class="pn-back" href="mis_planes.php">← Volver a mis planes</a>
    </div>
  </div>
</main>

<script>
window.PLAN_CSRF = <?= json_encode($plan_csrf) ?>;
window.pnMapsReady = new Promise(function (res) { window.__pnMapsReady = res; });
</script>
<script src="js/topbar.js"></script>
<?php $_mapsUrl = mapsScriptUrl('__pnMapsReady'); ?>
<?php if ($_mapsUrl !== ''): ?>
<script async src="<?= htmlspecialchars($_mapsUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php else: ?>
<script>console.warn('[Ruta Nómada] Falta includes/maps_config.php: el destino no va a autocompletarse.');</script>
<?php endif; ?>
<script src="js/plan_nuevo.js"></script>
</body>
</html>
