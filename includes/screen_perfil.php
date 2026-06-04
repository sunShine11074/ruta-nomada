<?php /* Ruta Nómada — Perfil screen (translated from screens.jsx Perfil) */ ?>
<div class="screen fade" id="screen-perfil" style="display:none;">
  <div class="crumbs"><span>Cuenta</span><?= ico('chevron_right') ?><b>Perfil</b></div>
  <div class="panel panel--hi" style="margin-bottom:24px">
    <div class="profile-hero">
      <div class="avatar-lg">A</div>
      <div style="flex:1">
        <h2 style="font-family:'Noto Serif',serif;color:var(--rino-300);font-size:1.7rem">Ana López</h2>
        <p style="color:var(--ink-soft)">Viajera · Miembro desde 2024</p>
        <div style="display:flex;gap:8px;margin-top:10px">
          <span class="badge-data"><?= ico('verified', false, '', 'font-size:14px') ?>Cuenta verificada</span>
          <span class="badge-data" style="background:var(--olive-600)"><?= ico('workspace_premium', false, '', 'font-size:14px') ?>Plan Explorador</span>
        </div>
      </div>
      <?= btn('Editar', ['variant' => 'ghost', 'icon' => 'edit']) ?>
    </div>
  </div>
  <div class="grid grid--2">
    <div class="panel">
      <h3 style="font-family:'Noto Serif',serif;color:var(--rino-200);font-size:1.3rem;margin-bottom:4px">Datos personales</h3>
      <div class="kv">
        <?php
        $personal = [['Nombre','Ana López'],['Email','ana@rutanomada.mx'],['Teléfono','+52 55 1234 5678'],['País','México'],['Idioma','Español']];
        foreach ($personal as [$k,$v]): ?>
          <div class="kv__row"><span class="kv__k"><?= e($k) ?></span><span class="kv__v"><?= e($v) ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="panel">
      <h3 style="font-family:'Noto Serif',serif;color:var(--rino-200);font-size:1.3rem;margin-bottom:4px">Actividad</h3>
      <div class="kv">
        <?php
        $activity = [['Viajes realizados','7'],['Rutas guardadas','12'],['Reseñas escritas','18'],['Gastos repartidos','$42,300 MXN']];
        foreach ($activity as [$k,$v]): ?>
          <div class="kv__row"><span class="kv__k"><?= e($k) ?></span><span class="kv__v data"><?= e($v) ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php include __DIR__ . '/footer.php'; ?>
</div>
