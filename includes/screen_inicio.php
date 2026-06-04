<?php /* Ruta Nómada — Inicio screen (translated from screens.jsx Inicio) */ ?>
<div class="screen fade" id="screen-inicio">
  <div class="page-head">
    <h1>Hola Ana, ¿a dónde vamos?</h1>
    <p>Explora destinos seleccionados para ti y retoma tus rutas guardadas.</p>
  </div>

  <div class="panel panel--hi" style="display:flex;align-items:center;gap:14px;padding:14px">
    <?= ico('search', false, '', 'color:var(--ink-soft)') ?>
    <input placeholder="Buscar destinos, actividades, hoteles…" style="flex:1;background:transparent;border:none;font-size:1rem;color:var(--ink)" />
    <?= btn('Filtrar', ['variant' => 'cta', 'size' => 'sm', 'icon' => 'tune']) ?>
  </div>

  <div class="sec-head">
    <h2>Recomendado para ti</h2>
    <a class="more" href="#">Ver todos <?= ico('chevron_right') ?></a>
  </div>
  <div class="chips" style="margin-bottom:20px">
    <?php for ($i = 1; $i <= 4; $i++): ?>
      <span class="chip"><?= ico($CATEGORIES[$i]['icon']) ?><?= e($CATEGORIES[$i]['label']) ?></span>
    <?php endfor; ?>
  </div>
  <div class="grid grid--4">
    <?php for ($i = 0; $i < 4; $i++): ?>
      <?= destinationCard($DESTINATIONS[$i], $i) ?>
    <?php endfor; ?>
  </div>

  <div class="sec-head">
    <h2>Subido recientemente</h2>
    <a class="more" href="#">Ver más <?= ico('chevron_right') ?></a>
  </div>
  <div class="grid grid--2">
    <?php foreach ($STORIES as $s): ?>
      <?= storyItem($s) ?>
    <?php endforeach; ?>
  </div>

  <?php include __DIR__ . '/footer.php'; ?>
</div>
