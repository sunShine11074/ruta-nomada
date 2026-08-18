<?php
// ============================================================
//  resultados.php — Resultados de búsqueda por ciudad | Ruta Nómada
//  Rediseño (design_handoff_resultados): hero de fotos, descripción,
//  clima dinámico, tabs de categorías con Google Places y mapa fijo.
// ============================================================
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user           = $_SESSION['user'];
$inicial        = mb_strtoupper(mb_substr($user['nombre'], 0, 1));
$nombre_display = htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8');
$email_display  = htmlspecialchars($user['email'],  ENT_QUOTES, 'UTF-8');
require_once __DIR__ . '/includes/user_topbar.php';
require_once __DIR__ . '/includes/maps_lib.php';
$q      = trim($_GET['q'] ?? '');
$q_attr = htmlspecialchars($q, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $q ? $q_attr . ' — Ruta Nómada' : 'Buscar — Ruta Nómada' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?: 1 ?>">
    <link rel="stylesheet" href="topbar.css?v=<?= @filemtime(__DIR__ . '/topbar.css') ?: 1 ?>">
    <link rel="stylesheet" href="resultados.css">
</head>
<body class="dashboard-page rz-results" data-user-id="<?= (int) $user['id'] ?>">

<!-- ════════════ TOPBAR (existente) ════════════ -->
<?php $topbar_active = null; include __DIR__ . '/includes/topbar.php'; ?>

<!-- ════════════ SIDEBAR (existente, overlay en esta página) ════════════ -->

<!-- ════════════ LAYOUT 2 COLUMNAS ════════════ -->
<div class="rz-layout">

  <!-- ── Columna izquierda (scrollea) ── -->
  <main class="rz-main" id="rzMain">

    <!-- Estado sin búsqueda -->
    <div class="rz-empty-state" id="rzNoQuery" hidden>
      <div class="rz-empty-state__icon">🔎</div>
      <h2>Busca una ciudad</h2>
      <p>Escribe una ciudad en el buscador de arriba para ver fotos, clima, cosas que hacer, restaurantes y hoteles.</p>
    </div>

    <div id="rzContent" hidden>

    <!-- Breadcrumb -->
    <nav class="rz-crumbs" aria-label="Ruta de navegación">
      <a href="#" id="bcContinent">—</a>
      <span class="rz-crumbs__sep">›</span>
      <a href="#" id="bcCountry">—</a>
      <span class="rz-crumbs__sep">›</span>
      <a href="#" id="bcCity" class="rz-crumbs__cur">—</a>
    </nav>

    <!-- ── HERO carrusel ── -->
    <div class="rz-hero" id="hero">
      <img id="heroImg" src="" alt="" class="rz-hero__img" style="opacity:0">
      <img src="img/logo.png" alt="" class="rz-hero__logo">
      <div class="rz-hero__attr" id="heroAttr" hidden>
        <span class="rz-hero__attr-ico">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="#ffffff"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zm0 2.2c-3.6 0-9 1.8-9 5.3V21h18v-1.5c0-3.5-5.4-5.3-9-5.3z"/></svg>
        </span>
        <span id="heroAttrName">—</span>
      </div>
      <div class="rz-hero__dots" id="heroDots"></div>
      <div class="rz-hero__count" id="heroCountPill" hidden>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5" fill="#ffffff" stroke="none"/><path d="M21 15l-4.5-4.5L9 18"/></svg>
        <span id="heroCount">0</span>
      </div>
      <button class="rz-hero__nav rz-hero__nav--prev" id="heroPrev" aria-label="Foto anterior" hidden>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
      </button>
      <button class="rz-hero__nav rz-hero__nav--next" id="heroNext" aria-label="Foto siguiente" hidden>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
      </button>
    </div>

    <!-- ── Título + Guardar ── -->
    <div class="rz-titlerow">
      <h1 id="cityTitle">—</h1>
      <button class="rz-save" id="saveBtn" aria-label="Guardar destino">
        <svg width="19" height="19" viewBox="0 0 24 24" id="saveHeartSvg"><path id="saveHeart" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="none" stroke="#16262e" stroke-width="1.8" stroke-linejoin="round"/></svg>
        <span id="saveLabel">Guardar</span>
      </button>
    </div>

    <!-- ── Descripción + Leer más ── -->
    <div class="rz-desc" id="descBlock" hidden>
      <div class="rz-desc__clip" id="descWrap">
        <p id="descText"></p>
      </div>
      <button class="rz-desc__more" id="readMore">
        <span id="readLabel">Leer más</span>
        <svg width="15" height="15" viewBox="0 0 24 24" id="readChev"><path d="M6 9l6 6 6-6" fill="none" stroke="#16262e" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>

    <!-- ── Clima actual ── -->
    <section class="rz-clima" id="climaBlock" hidden>
      <h2>Clima actual</h2>
      <div class="rz-clima__card" id="climaCard">
        <div class="rz-clima__track" id="climaTrack">
          <!-- Página 1 -->
          <div class="rz-clima__page">
            <div class="rz-metric">
              <span class="rz-metric__ico" id="tempWrap">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" id="tempSvg" stroke="#f0b429" stroke-width="1.8" stroke-linecap="round">
                  <path d="M10 4a2 2 0 0 1 4 0v9.3a4.5 4.5 0 1 1-4 0z"/>
                  <rect id="tempMerc" x="11.15" y="10" width="1.7" height="5" rx="0.85" fill="#f0b429" stroke="none" style="transition:all .4s"/>
                  <circle id="tempDot" cx="12" cy="17" r="2.1" fill="#f0b429" stroke="none"/>
                  <text id="tempBadge" x="16.5" y="8" font-size="7" fill="#5aa7e6" stroke="none"></text>
                </svg>
              </span>
              <div><p class="rz-metric__lbl">Temperatura</p><p class="rz-metric__val" id="tempVal">—</p></div>
            </div>
            <div class="rz-metric">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" class="rz-metric__ico" id="condSvg">
                <g id="cSunFull" opacity="0" stroke="#f0b429" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6L7 7M17 17l1.4 1.4M18.4 5.6L17 7M7 17l-1.4 1.4"/></g>
                <g id="cSunSmall" opacity="0" stroke="#f0b429" stroke-width="1.6" stroke-linecap="round"><circle cx="8" cy="7.5" r="2.6"/><path d="M8 2.8v1.3M2.8 7.5h1.3M4.3 3.8l.9.9M11.7 3.8l-.9.9"/></g>
                <g id="cCloud2" opacity="0" stroke="#a9bac4" stroke-width="1.6" stroke-linejoin="round"><path d="M13.5 6.5a3 3 0 0 1 5.8-.6 2.5 2.5 0 0 1-.6 4.9H14a2.6 2.6 0 0 1-.5-4.3z" fill="#ffffff"/></g>
                <g id="cCloud" opacity="0" stroke="#7d93a0" stroke-width="1.8" stroke-linejoin="round"><path d="M7 11.2a3.6 3.6 0 0 1 7-.9 3 3 0 0 1-.6 5.9H8a3.1 3.1 0 0 1-1-5z" fill="#ffffff"/></g>
                <g id="cRain" opacity="0" stroke="#4aa8e0" stroke-width="1.7" stroke-linecap="round"><path d="M9 18.5l-.8 2M12.5 18.5l-.8 2M16 18.5l-.8 2"/></g>
                <g id="cSnow" opacity="0" fill="#7fc4e8"><circle cx="9" cy="19.4" r="1"/><circle cx="12.7" cy="20.6" r="1"/><circle cx="16" cy="19.4" r="1"/></g>
                <g id="cBolt" opacity="0"><path d="M12.5 15.5l-2.4 3.6h2l-1.3 3.4 3.9-4.6h-2.1l1.7-2.4z" fill="#f0b429"/></g>
                <g id="cTornado" opacity="0" stroke="#6b7a83" stroke-width="1.8" stroke-linecap="round"><path d="M4.5 5.5h15M6.5 9h11M9 12.5h7M11 16h4M12.5 19.5h1.5"/></g>
              </svg>
              <div><p class="rz-metric__lbl">Condiciones meteorológicas</p><p class="rz-metric__val" id="condVal">—</p></div>
            </div>
            <div class="rz-metric">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" class="rz-metric__ico" id="windSvg" stroke="#8fa3ad" stroke-width="1.8" stroke-linecap="round">
                <path class="rz-gust" d="M3 8h9.5a3 3 0 1 0-2.8-4" stroke-dasharray="6 5"/>
                <path class="rz-gust rz-gust--d2" d="M3 12h13.5a3 3 0 1 1-2.8 4" stroke-dasharray="7 4"/>
                <path class="rz-gust rz-gust--d3" d="M3 16h7" stroke-dasharray="6 5"/>
              </svg>
              <div><p class="rz-metric__lbl">Velocidad del viento</p><p class="rz-metric__val" id="windVal">—</p></div>
            </div>
            <div class="rz-metric">
              <svg width="30" height="30" viewBox="0 0 24 24" class="rz-metric__ico">
                <defs><clipPath id="rnDrop"><path d="M12 3s6 6.6 6 10.5a6 6 0 1 1-12 0C6 9.6 12 3 12 3z"/></clipPath></defs>
                <rect id="humRect" x="4" y="21" width="16" height="18" fill="#4aa8e0" opacity="0.85" clip-path="url(#rnDrop)" style="transition:y .4s"/>
                <path d="M12 3s6 6.6 6 10.5a6 6 0 1 1-12 0C6 9.6 12 3 12 3z" fill="none" stroke="#0d3a4d" stroke-width="1.8" stroke-linejoin="round"/>
              </svg>
              <div><p class="rz-metric__lbl">Humedad</p><p class="rz-metric__val" id="humVal">—</p></div>
            </div>
          </div>
          <!-- Página 2 -->
          <div class="rz-clima__page">
            <div class="rz-metric">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" class="rz-metric__ico" id="uvSvg" stroke="#f0b429" stroke-width="1.8" stroke-linecap="round">
                <circle cx="12" cy="9" r="3.4"/>
                <path id="uvR0" d="M12 4.2V2.4" opacity="0.18"/>
                <path id="uvR1" d="M15.39 5.61l1.28-1.28" opacity="0.18"/>
                <path id="uvR2" d="M8.61 5.61L7.33 4.33" opacity="0.18"/>
                <path id="uvR3" d="M16.8 9h1.8" opacity="0.18"/>
                <path id="uvR4" d="M7.2 9H5.4" opacity="0.18"/>
                <path id="uvR5" d="M15.39 12.39l1.28 1.28" opacity="0.18"/>
                <path id="uvR6" d="M8.61 12.39l-1.28 1.28" opacity="0.18"/>
                <path id="uvR7" d="M12 13.8v1.8" opacity="0.18"/>
                <text id="uvText" x="12" y="21" text-anchor="middle" font-size="7.5" font-weight="700" fill="#f0b429" stroke="none">UV</text>
              </svg>
              <div><p class="rz-metric__lbl">UV</p><p class="rz-metric__val" id="uvVal">—</p></div>
            </div>
            <div class="rz-metric">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" class="rz-metric__ico" id="airSvg" stroke="#2e9e4f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 18a4.5 4.5 0 1 1 .6-8.96A6 6 0 0 1 18.6 10.5 3.75 3.75 0 0 1 18 18z"/></svg>
              <div><p class="rz-metric__lbl">Calidad del aire</p><p class="rz-metric__val" id="airVal">—</p></div>
            </div>
            <div class="rz-metric">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" class="rz-metric__ico">
                <path d="M3 17.5h18" stroke="#c3cfd6" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M4 17.5a8 8 0 0 1 16 0" stroke="#e3e9ed" stroke-width="1.8" stroke-linecap="round"/>
                <path id="sunProgArc" d="M4 17.5a8 8 0 0 1 16 0" pathLength="100" stroke-dasharray="0 100" stroke="#f0a12b" stroke-width="1.8" stroke-linecap="round" style="transition:stroke-dasharray .4s"/>
                <circle id="sunDot1" cx="4" cy="17.5" r="2" fill="#f0b429" stroke="#ffffff" stroke-width="0.8"/>
              </svg>
              <div><p class="rz-metric__lbl">Salida de sol</p><p class="rz-metric__val" id="sunriseVal">—</p></div>
            </div>
            <div class="rz-metric">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" class="rz-metric__ico">
                <path d="M3 17.5h18" stroke="#c3cfd6" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M4 17.5a8 8 0 0 1 16 0" stroke="#e3e9ed" stroke-width="1.8" stroke-linecap="round"/>
                <path id="sunRemArc" d="M4 17.5a8 8 0 0 1 16 0" pathLength="100" stroke-dasharray="100 100" stroke-dashoffset="0" stroke="#e2703a" stroke-width="1.8" stroke-linecap="round" style="transition:stroke-dasharray .4s,stroke-dashoffset .4s"/>
                <circle id="sunDot2" cx="4" cy="17.5" r="2" fill="#e8702a" stroke="#ffffff" stroke-width="0.8"/>
              </svg>
              <div><p class="rz-metric__lbl">Puesta de sol</p><p class="rz-metric__val" id="sunsetVal">—</p></div>
            </div>
          </div>
        </div>
        <button class="rz-clima__nav rz-clima__nav--next" id="climaNext" aria-label="Más datos de clima">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
        </button>
        <button class="rz-clima__nav rz-clima__nav--prev" id="climaPrev" aria-label="Datos anteriores de clima" hidden>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
        </button>
      </div>
    </section>

    <!-- ── Submenú de categorías (sticky) + Filtros ── -->
    <div class="rz-tabs" id="tabsBar">
      <div class="rz-tabs__row">
        <button class="rz-tab" data-tab="hacer">Cosas que hacer</button>
        <button class="rz-tab" data-tab="restaurantes">Restaurantes</button>
        <button class="rz-tab" data-tab="hoteles">Hoteles</button>
        <div class="rz-tabs__spacer"></div>
        <button class="rz-filters" id="filtersBtn">
          <span>Filtros</span>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="2" stroke-linecap="round"><path d="M4 7h9M19 7h1M4 17h1M11 17h9"/><circle cx="16" cy="7" r="2.6"/><circle cx="8" cy="17" r="2.6"/></svg>
        </button>
      </div>
    </div>

    <!-- ── Grid: skeleton / tarjetas / vacío ── -->
    <div class="rz-grid" id="skeleton" hidden></div>
    <div class="rz-grid" id="grid"></div>
    <div class="rz-empty-state" id="catEmpty" hidden>
      <div class="rz-empty-state__icon">🗺️</div>
      <p id="catEmptyMsg">Sin resultados en esta categoría.</p>
    </div>

    </div><!-- /rzContent -->
  </main>

  <!-- ── Columna derecha: MAPA ── -->
  <div class="rz-mapcol" id="mapWrap">
    <div class="rz-mappanel" id="mapPanel">
      <div id="map"></div>
      <button class="rz-map-close" id="mapClose" hidden><svg width="13" height="13" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true"><path d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"></path></svg> Cerrar mapa</button>
      <button class="rz-map-expand" id="expandBtn" aria-label="Expandir mapa">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3a4a52" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path id="expandIcon" d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5"/></svg>
      </button>
      <button class="rz-map-center" id="recenterBtn" aria-label="Centrar mapa">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3a4a52" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2 2M12 3l-2 2M12 21l2-2M12 21l-2-2M3 12l2-2M3 12l2 2M21 12l-2-2M21 12l-2 2"/><circle cx="12" cy="12" r="1.4" fill="#3a4a52" stroke="none"/></svg>
      </button>
      <div class="rz-map-zoom">
        <button id="zIn" aria-label="Acercar">+</button>
        <div class="rz-map-zoom__sep"></div>
        <button id="zOut" aria-label="Alejar">−</button>
      </div>
    </div>
  </div>
</div>

<!-- Botón flotante "Ver mapa" (pantallas angostas) -->
<button class="rz-vermapa" id="verMapa" hidden>
  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linejoin="round"><path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2-6-2zM9 4v14M15 6v14"/></svg>
  Ver mapa
</button>

<!-- Google Maps JS API (geocoding + places + mapa). Requiere en Google Cloud:
     Maps JavaScript API, Geocoding API y Places API habilitadas para la key. -->
<script>
  window.gmapsReady = new Promise(function (resolve) { window.__gmReady = resolve; });
</script>
<script async
  src="<?= htmlspecialchars(mapsScriptUrl('__gmReady'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="js/resultados.js"></script>
<script src="js/topbar.js"></script>
</body>
</html>
