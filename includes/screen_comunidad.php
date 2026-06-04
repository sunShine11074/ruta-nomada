<?php /* Ruta Nómada — Comunidad screen (translated from screens.jsx Comunidad) */ ?>
<div class="screen fade" id="screen-comunidad" style="display:none;">
  <div class="page-head" style="display:flex;justify-content:space-between;align-items:flex-end">
    <div>
      <h1>Comunidad</h1>
      <p>Rutas, diarios y recomendaciones de otros viajeros.</p>
    </div>
    <?= btn('Compartir ruta', ['variant' => 'secondary', 'icon' => 'edit']) ?>
  </div>
  <div class="grid grid--2">
    <?php
    $posts = [
      array_merge($STORIES[0], ['likes' => 248, 'comments' => 32]),
      array_merge($STORIES[1], ['likes' => 187, 'comments' => 19]),
      array_merge($STORIES[2], ['likes' => 421, 'comments' => 56]),
      array_merge($STORIES[3], ['likes' => 96,  'comments' => 11]),
    ];
    foreach ($posts as $p): ?>
      <article class="dcard">
        <div class="dcard__media" style="aspect-ratio:16/8"><?= placeholder($p['tint'], $p['icon'], $p['title']) ?></div>
        <div class="dcard__body">
          <span class="story__when"><?= e($p['when']) ?> · <?= e($p['by']) ?></span>
          <div class="dcard__title" style="font-size:1.15rem"><?= e($p['title']) ?></div>
          <div class="dcard__foot" style="margin-top:8px">
            <span class="dcard__meta"><?= ico('favorite', false, '', 'font-size:17px') ?> <?= $p['likes'] ?></span>
            <span class="dcard__meta"><?= ico('chat_bubble', false, '', 'font-size:16px') ?> <?= $p['comments'] ?></span>
            <a class="more" href="#" style="color:var(--rino-300);font-weight:600">Leer <?= ico('chevron_right') ?></a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php include __DIR__ . '/footer.php'; ?>
</div>
