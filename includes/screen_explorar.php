<?php /* Ruta Nómada — Explorar screen (translated from screens.jsx Explorar) */ ?>
<div class="screen fade" id="screen-explorar" style="display:none;">
  <div class="page-head">
    <h1>Explorar destinos</h1>
    <p>120+ destinos alrededor del mundo, listos para cotizar y planear.</p>
  </div>
  <div class="chips" id="explorar-chips" style="margin-bottom:24px">
    <?php foreach ($CATEGORIES as $c): ?>
      <button class="chip<?= $c['id'] === 'todos' ? ' active' : '' ?>" data-cat-filter="<?= e($c['id']) ?>">
        <?= ico($c['icon']) ?><?= e($c['label']) ?>
      </button>
    <?php endforeach; ?>
  </div>
  <div class="grid grid--3" id="explorar-grid">
    <?php foreach ($DESTINATIONS as $d): ?>
      <?= destinationCard($d) ?>
    <?php endforeach; ?>
  </div>
  <div class="placeholder" id="explorar-empty" style="display:none;">
    <?= ico('travel_explore') ?>
    <h3>Sin resultados</h3>
    <p>No hay destinos en esta categoría todavía.</p>
  </div>
  <?php include __DIR__ . '/footer.php'; ?>
</div>
