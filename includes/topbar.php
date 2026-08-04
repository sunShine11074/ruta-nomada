<?php /* Ruta Nómada — Top navigation bar (translated from app.jsx Topbar) */
$avatarLetter = $user ? mb_strtoupper(mb_substr($user['nombre'], 0, 1)) : 'U';
?>
<header class="topbar">
  <div class="topbar__brand">
    <?= logoMark(34) ?>
    <span class="name">Ruta Nómada</span>
  </div>
  <nav class="topnav">
    <button class="topnav__item" data-route="top:inicio"><?= ico('home') ?>Inicio</button>
    <button class="topnav__item" data-route="top:explorar"><?= ico('explore') ?>Explorar</button>
    <button class="topnav__item" data-route="top:misviajes"><?= ico('luggage') ?>Mis viajes</button>
    <button class="topnav__item" data-route="top:comunidad"><?= ico('groups') ?>Comunidad</button>
  </nav>
  <div class="topbar__spacer"></div>
  <div class="searchbox">
    <?= ico('search') ?>
    <input placeholder="Buscar destinos, actividades, hoteles…" />
  </div>
  <div class="topbar__user">
    <button class="icon-btn" aria-label="Notificaciones"><?= ico('notifications') ?></button>
    <div class="topbar__greet">
      <div class="hi">¡Hola, <?= e($userFirstName ?: 'Usuario') ?>!</div>
      <div class="role">Viajera</div>
    </div>
    <button class="avatar" data-route="side:perfil" aria-label="Perfil"><?= $avatarLetter ?></button>
  </div>
</header>
