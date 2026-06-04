<?php /* Ruta Nómada — Destination Detail screen (translated from detail.jsx)
     This is a template that gets populated dynamically by JS when a destination is clicked.
     PHP renders the structural HTML; JS fills in the data. */ ?>
<div class="screen fade" id="screen-detail" style="display:none;">
  <div class="crumbs">
    <button data-route="top:inicio" style="color:var(--rino-300);font-weight:600">Inicio</button>
    <?= ico('chevron_right') ?><span>Destinos</span><?= ico('chevron_right') ?><b id="detail-crumb-name"></b>
  </div>

  <!-- Hero -->
  <div style="border-radius:var(--radius);overflow:hidden;border:1px solid var(--card-border);height:320px;position:relative;margin-bottom:24px">
    <div id="detail-hero-ph"></div>
    <button data-route="top:inicio" class="icon-btn" style="position:absolute;top:16px;left:16px;background:rgba(255,253,247,.9);color:var(--rino-300)"><?= ico('arrow_back') ?></button>
  </div>

  <div class="grid" style="grid-template-columns:1.7fr 1fr;align-items:start">
    <!-- Left column -->
    <div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <h1 style="font-family:'Noto Serif',serif;font-size:2.3rem;color:var(--rino-400)" id="detail-name"></h1>
        <span class="dcard__rating" style="font-size:1rem"><?= ico('star') ?><span id="detail-rating"></span></span>
        <span style="color:var(--ink-soft);font-size:.9rem">(256 opiniones)</span>
      </div>
      <p class="prose" style="margin-top:12px;color:var(--ink);max-width:62ch" id="detail-desc"></p>

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin:18px 0 4px" id="detail-badges">
        <span class="badge-data"><?= ico('public', false, '', 'font-size:14px') ?><span id="detail-country"></span></span>
        <span class="badge-data"><?= ico('thermostat', false, '', 'font-size:14px') ?>24°C – 32°C</span>
        <span class="badge-data" style="background:var(--olive-600)"><?= ico('event_available', false, '', 'font-size:14px') ?>Mejor: Dic – Abr</span>
      </div>

      <!-- Tabs -->
      <div style="display:flex;gap:4px;border-bottom:1px solid var(--card-border);margin:26px 0 18px;flex-wrap:wrap" id="detail-tabs">
        <button class="detail-tab active" data-detail-tab="desc" style="padding:10px 14px;font-weight:700;font-size:.94rem;color:var(--rino-400);border-bottom:3px solid var(--cta);margin-bottom:-1px">Descripción</button>
        <button class="detail-tab" data-detail-tab="hacer" style="padding:10px 14px;font-weight:500;font-size:.94rem;color:var(--ink-soft);border-bottom:3px solid transparent;margin-bottom:-1px">Qué hacer</button>
        <button class="detail-tab" data-detail-tab="comer" style="padding:10px 14px;font-weight:500;font-size:.94rem;color:var(--ink-soft);border-bottom:3px solid transparent;margin-bottom:-1px">Dónde comer</button>
        <button class="detail-tab" data-detail-tab="llegar" style="padding:10px 14px;font-weight:500;font-size:.94rem;color:var(--ink-soft);border-bottom:3px solid transparent;margin-bottom:-1px">Cómo llegar</button>
        <button class="detail-tab" data-detail-tab="resenas" style="padding:10px 14px;font-weight:500;font-size:.94rem;color:var(--ink-soft);border-bottom:3px solid transparent;margin-bottom:-1px">Reseñas</button>
      </div>

      <!-- Tab: Descripción -->
      <div class="detail-tab-panel fade" id="tab-desc">
        <p class="prose" style="color:var(--ink)" id="detail-long-desc"></p>
        <h3 style="font-family:'Noto Serif',serif;color:var(--rino-200);font-size:1.3rem;margin:26px 0 14px">Lugares destacados</h3>
        <div class="grid grid--4" style="gap:14px">
          <?php
          $highlights = [
            ['t' => 'Zona Hotelera', 's' => 'Playas y resorts',    'tint' => 'agua',    'icon' => 'beach'],
            ['t' => 'Chichén Itzá',  's' => 'Zona arqueológica',   'tint' => 'cultura', 'icon' => 'cultura'],
            ['t' => 'Isla Mujeres',  's' => 'Playas cristalinas',  'tint' => 'agua',    'icon' => 'sailing'],
            ['t' => 'Xcaret',        's' => 'Parque temático',     'tint' => 'bosque',  'icon' => 'forest'],
          ];
          foreach ($highlights as $h): ?>
            <div style="border-radius:12px;overflow:hidden;border:1px solid var(--card-border);background:var(--card)">
              <div style="height:92px"><?= placeholder($h['tint'], $h['icon']) ?></div>
              <div style="padding:10px 12px">
                <div style="font-weight:600;font-size:.92rem;color:var(--rino-200)"><?= e($h['t']) ?></div>
                <div style="font-size:.78rem;color:var(--ink-soft)"><?= e($h['s']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Tab: Reseñas -->
      <div class="detail-tab-panel fade" id="tab-resenas" style="display:none;flex-direction:column;gap:14px">
        <?php
        $reviews = [
          ['q' => 'Un lugar increíble, playas hermosas y mucha diversión.', 'a' => 'María G.', 'w' => '12 May 2023'],
          ['q' => 'Excelente atención y hospedaje, ¡volveremos pronto!',    'a' => 'Luis P.',  'w' => '28 Abr 2023'],
        ];
        foreach ($reviews as $r): ?>
          <div class="panel" style="padding:18px">
            <p class="prose" style="font-style:italic;color:var(--rino-200)">&ldquo;<?= e($r['q']) ?>&rdquo;</p>
            <div style="margin-top:8px;font-size:.84rem;color:var(--ink-soft)"><b style="color:var(--ink-2)"><?= e($r['a']) ?></b> · <span class="data"><?= e($r['w']) ?></span></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Tab: Placeholder (hacer, comer, llegar) -->
      <div class="detail-tab-panel placeholder fade" id="tab-placeholder" style="display:none;">
        <?= ico('travel_explore') ?>
        <h3>Contenido en preparación</h3>
        <p>Esta sección se está completando para <span id="detail-placeholder-name"></span>.</p>
      </div>
    </div>

    <!-- Right column — booking + info -->
    <div style="display:flex;flex-direction:column;gap:18px;position:sticky;top:0">
      <div class="panel panel--hi">
        <div style="display:flex;align-items:flex-end;justify-content:space-between">
          <div><span class="kv__k">Precio desde · por persona</span>
            <div class="data" style="font-size:1.9rem;font-weight:600;color:var(--rino-400)" id="detail-price"></div>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;margin-top:16px">
          <?= btn('Cotizar este viaje', ['variant' => 'cta', 'block' => true, 'icon' => 'request_quote']) ?>
          <?= btn('Guardar plan', ['variant' => 'secondary', 'block' => true, 'icon' => 'bookmark']) ?>
        </div>
      </div>
      <div class="panel">
        <h4 style="font-family:'Inter',sans-serif;font-size:1.05rem;color:var(--rino-100);margin-bottom:12px">Servicios incluidos</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <?php
          $services = [
            ['flight','Vuelos redondos'], ['hotel','Hotel 5 estrellas'], ['restaurant','Desayunos'],
            ['airport_shuttle','Traslados'], ['confirmation_number','Actividades'], ['support_agent','Asistencia 24/7'],
          ];
          foreach ($services as [$ic,$lb]): ?>
            <div style="display:flex;align-items:center;gap:8px;font-size:.86rem;color:var(--ink-2)">
              <?= ico($ic, false, '', 'color:var(--neptune-200);font-size:20px') ?><?= e($lb) ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="panel" style="background:var(--accent-soft)">
        <h4 style="font-family:'Inter',sans-serif;font-size:1.05rem;color:var(--rino-100);margin-bottom:10px">Información útil</h4>
        <div class="kv">
          <?php
          $info = [['Moneda','Peso mexicano (MXN)'],['Idioma','Español'],['Voltaje','127 V'],['Emergencias','911']];
          foreach ($info as [$k,$v]): ?>
            <div class="kv__row" style="padding:10px 0;border-color:rgba(7,24,32,.08)"><span class="kv__k"><?= e($k) ?></span><span class="kv__v data" style="font-size:.88rem"><?= e($v) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php include __DIR__ . '/footer.php'; ?>
</div>
