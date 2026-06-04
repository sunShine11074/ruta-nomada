<?php /* Ruta Nómada — Sidebar navigation (translated from app.jsx Sidebar) */ ?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar__top">
    <button class="sidebar__toggle" id="sidebar-toggle" aria-label="Mostrar/ocultar menú">
      <?= ico('menu_open') ?>
    </button>
    <div class="sidebar__profile">
      <div class="avatar">A</div>
      <div class="sidebar__id">
        <div class="nm">Ana López</div>
        <div class="em">ana@rutanomada.mx</div>
      </div>
    </div>
  </div>

  <div class="sidebar__section-label">Mi cuenta</div>
  <nav class="sidebar__nav">
    <button class="side-link" data-route="side:perfil">
      <?= ico('account_circle') ?>
      <span class="side-link__label">Perfil</span>
      <span class="side-link__tip">Perfil</span>
    </button>
    <button class="side-link" data-route="side:misplanes">
      <?= ico('map') ?>
      <span class="side-link__label">Mis planes</span>
      <span class="side-link__tip">Mis planes</span>
    </button>
    <button class="side-link" data-route="side:config">
      <?= ico('settings') ?>
      <span class="side-link__label">Configuración</span>
      <span class="side-link__tip">Configuración</span>
    </button>
  </nav>

  <div class="sidebar__foot">
    <button class="side-link side-link--danger" id="logout-btn">
      <?= ico('logout') ?>
      <span class="side-link__label">Cerrar sesión</span>
      <span class="side-link__tip">Cerrar sesión</span>
    </button>
  </div>
</aside>
