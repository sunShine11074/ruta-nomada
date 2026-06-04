<?php /* Ruta Nómada — Mis Planes screen (translated from screens.jsx MisPlanes) */ ?>
<div class="screen fade" id="screen-misplanes" style="display:none;">
  <div class="crumbs"><span>Cuenta</span><?= ico('chevron_right') ?><b>Mis planes</b></div>
  <div class="page-head">
    <h1>Mis planes</h1>
    <p>Cotizaciones guardadas y borradores de ruta.</p>
  </div>
  <div class="grid grid--3">
    <?php foreach ($MY_TRIPS as $t): ?>
      <?= tripCard($t) ?>
    <?php endforeach; ?>
  </div>
  <?php include __DIR__ . '/footer.php'; ?>
</div>
