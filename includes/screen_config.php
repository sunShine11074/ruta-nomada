<?php /* Ruta Nómada — Configuración screen (translated from screens.jsx Configuracion) */ ?>
<div class="screen fade" id="screen-config" style="display:none;">
  <div class="crumbs"><span>Cuenta</span><?= ico('chevron_right') ?><b>Configuración</b></div>
  <div class="page-head">
    <h1>Configuración</h1>
    <p>Administra notificaciones, privacidad y preferencias.</p>
  </div>
  <div class="grid grid--2">
    <div class="panel">
      <h3 style="font-family:'Noto Serif',serif;color:var(--rino-200);font-size:1.3rem;margin-bottom:4px">Notificaciones</h3>
      <div class="kv">
        <?php
        $notifRows = [
          ['Notificaciones por correo', 'Resúmenes de viaje y recordatorios', 'correo', true],
          ['Notificaciones push',       'Alertas en tiempo real en tu dispositivo', 'push', false],
          ['Ofertas y promociones',     'Descuentos en destinos que sigues', 'ofertas', true],
          ['Compartir ubicación',       'Mejora las recomendaciones cercanas', 'ubic', true],
        ];
        foreach ($notifRows as [$title, $sub, $key, $on]): ?>
          <div class="kv__row">
            <span>
              <span class="kv__v" style="display:block"><?= e($title) ?></span>
              <span class="kv__k"><?= e($sub) ?></span>
            </span>
            <button class="toggle<?= $on ? ' on' : '' ?>" data-toggle="<?= e($key) ?>" aria-pressed="<?= $on ? 'true' : 'false' ?>"></button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="panel">
      <h3 style="font-family:'Noto Serif',serif;color:var(--rino-200);font-size:1.3rem;margin-bottom:4px">Preferencias</h3>
      <div class="kv">
        <div class="kv__row"><span class="kv__k">Moneda</span><span class="kv__v data">MXN · Peso mexicano</span></div>
        <div class="kv__row"><span class="kv__k">Idioma</span><span class="kv__v">Español</span></div>
        <div class="kv__row"><span class="kv__k">Zona horaria</span><span class="kv__v data">GMT-6</span></div>
        <div class="kv__row">
          <span>
            <span class="kv__v" style="display:block">Tema oscuro</span>
            <span class="kv__k">Reduce el brillo de la interfaz</span>
          </span>
          <button class="toggle" data-toggle="oscuro" aria-pressed="false"></button>
        </div>
      </div>
      <div style="margin-top:18px;display:flex;gap:10px">
        <?= btn('Guardar cambios', ['variant' => 'cta']) ?>
        <?= btn('Cancelar', ['variant' => 'ghost']) ?>
      </div>
    </div>
  </div>
  <?php include __DIR__ . '/footer.php'; ?>
</div>
