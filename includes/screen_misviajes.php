<?php /* Ruta Nómada — Mis Viajes screen (translated from screens.jsx MisViajes) */ ?>
<div class="screen fade" id="screen-misviajes" style="display:none;">
  <div class="page-head" style="display:flex;justify-content:space-between;align-items:flex-end">
    <div>
      <h1>Mis viajes</h1>
      <p>Tus rutas confirmadas y en planeación.</p>
    </div>
    <?= btn('Nuevo viaje', ['variant' => 'cta', 'icon' => 'add']) ?>
  </div>
  <div class="grid grid--3" style="margin-bottom:8px">
    <?php
    $stats = [['3', 'Viajes activos'], ['1', 'Confirmado'], ['$33,300', 'Presupuesto total']];
    foreach ($stats as [$n, $l]): ?>
      <div class="stat-card"><div class="n"><?= e($n) ?></div><div class="l"><?= e($l) ?></div></div>
    <?php endforeach; ?>
  </div>
  <div class="sec-head"><h2>Todos los viajes</h2></div>
  <div class="grid grid--3">
    <?php foreach ($MY_TRIPS as $t): ?>
      <?= tripCard($t) ?>
    <?php endforeach; ?>
  </div>
  <?php include __DIR__ . '/footer.php'; ?>
</div>
