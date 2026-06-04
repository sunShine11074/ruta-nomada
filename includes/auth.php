<?php /* Ruta Nómada — Auth flow: Login · Registro · Recuperación (translated from auth.jsx) */ ?>
<div class="auth" id="auth-flow" style="display:none;">

  <!-- Brand Panel (left side) -->
  <div class="auth__brand">
    <div class="auth__brand-top">
      <?= logoMark(40) ?>
      <span style="font-family:'Noto Serif',serif;font-weight:700;font-size:1.4rem;color:var(--barley-400)">Ruta Nómada</span>
    </div>
    <div class="auth__pitch">
      <h1>Planifica viajes con alma de explorador y precisión de contador.</h1>
      <p>Descubre destinos, cotiza tu ruta y reparte gastos con tu grupo — todo en un mismo lugar.</p>
    </div>
    <div class="auth__brand-foot">
      <div class="auth__stat"><div class="n">120+</div><div class="l">Destinos</div></div>
      <div class="auth__stat"><div class="n">48k</div><div class="l">Viajeros</div></div>
      <div class="auth__stat"><div class="n">4.9★</div><div class="l">Valoración</div></div>
    </div>
  </div>

  <!-- Auth panel (right side) -->
  <div class="auth__panel">

    <!-- LOGIN -->
    <div class="auth__card fade" id="auth-login">
      <h2>Iniciar sesión</h2>
      <p class="auth__sub">Bienvenida de vuelta. Continúa planeando tu próxima aventura.</p>
      <form class="auth__form" id="login-form">
        <div class="field">
          <label class="field__label">Email</label>
          <div class="field__control">
            <?= ico('mail') ?>
            <input type="email" placeholder="Ingresa tu email" value="ana@rutanomada.mx" />
          </div>
        </div>
        <div class="field">
          <label class="field__label">Contraseña</label>
          <div class="field__control">
            <?= ico('lock') ?>
            <input type="password" placeholder="Ingresa tu contraseña" data-pw-input />
            <button type="button" class="field__toggle" data-pw-toggle aria-label="Mostrar contraseña">
              <?= ico('visibility') ?>
            </button>
          </div>
        </div>
        <div class="auth__row">
          <label class="check">
            <input type="checkbox" checked />
            <span class="check__box"><?= ico('check') ?></span>
            Recordar mi contraseña
          </label>
          <button type="button" class="auth__link" data-auth-go="recuperacion">¿Olvidaste tu contraseña?</button>
        </div>
        <?= btn('Ingresar', ['variant' => 'cta', 'block' => true, 'type' => 'submit', 'iconRight' => 'arrow_forward']) ?>
      </form>
      <p class="auth__alt">¿No tienes cuenta? <button data-auth-go="registro">Regístrate</button></p>
    </div>

    <!-- REGISTRO -->
    <div class="auth__card fade" id="auth-registro" style="display:none;">
      <h2>Crear cuenta</h2>
      <p class="auth__sub">Crea una cuenta para comenzar a planear tus rutas.</p>
      <form class="auth__form" id="registro-form">
        <div class="field">
          <label class="field__label">Nombre</label>
          <div class="field__control">
            <?= ico('person') ?>
            <input type="text" placeholder="Tu nombre completo" />
          </div>
        </div>
        <div class="field">
          <label class="field__label">Email</label>
          <div class="field__control">
            <?= ico('mail') ?>
            <input type="email" placeholder="tu@email.com" />
          </div>
        </div>
        <div class="field">
          <label class="field__label">Contraseña</label>
          <div class="field__control">
            <?= ico('lock') ?>
            <input type="password" placeholder="••••••••" data-pw-input />
            <button type="button" class="field__toggle" data-pw-toggle aria-label="Mostrar contraseña">
              <?= ico('visibility') ?>
            </button>
          </div>
        </div>
        <div class="field">
          <label class="field__label">Confirmar contraseña</label>
          <div class="field__control">
            <?= ico('lock') ?>
            <input type="password" placeholder="••••••••" data-pw-input />
            <button type="button" class="field__toggle" data-pw-toggle aria-label="Mostrar contraseña">
              <?= ico('visibility') ?>
            </button>
          </div>
        </div>
        <label class="check">
          <input type="checkbox" id="terms-check" />
          <span class="check__box"><?= ico('check') ?></span>
          He leído y acepto los <a href="#">Términos de Servicio</a>
        </label>
        <?= btn('Registrarse', ['variant' => 'cta', 'block' => true, 'type' => 'submit', 'iconRight' => 'arrow_forward']) ?>
      </form>
      <p class="auth__alt">¿Ya tienes una cuenta? <button data-auth-go="login">Inicia sesión</button></p>
    </div>

    <!-- RECUPERACIÓN -->
    <div class="auth__card fade" id="auth-recuperacion" style="display:none;">
      <button class="auth__back" data-auth-go="login"><?= ico('arrow_back') ?>Volver</button>
      <h2>Recuperación de contraseña</h2>
      <p class="auth__sub">Ingresa tu correo electrónico y te enviaremos las instrucciones para restablecer tu contraseña.</p>
      <form class="auth__form" id="recuperacion-form">
        <div class="field">
          <label class="field__label">Correo electrónico</label>
          <div class="field__control">
            <?= ico('mail') ?>
            <input type="email" placeholder="ejemplo@correo.com" id="recover-email" />
          </div>
        </div>
        <?= btn('Recuperar contraseña', ['variant' => 'cta', 'block' => true, 'type' => 'submit', 'iconRight' => 'send']) ?>
      </form>
      <p class="auth__alt">
        <button data-auth-go="login">Inicio de sesión</button>
        <span style="margin:0 8px;color:var(--rino-600)">|</span>
        <button data-auth-go="registro">Regístrate</button>
      </p>
    </div>

    <!-- RECUPERACIÓN SUCCESS -->
    <div class="auth__card fade" id="auth-recuperacion-ok" style="display:none;">
      <div class="auth__success">
        <div class="auth__success-ring"><?= ico('mark_email_read') ?></div>
        <h2 style="font-size:1.6rem">Revisa tu correo</h2>
        <p class="auth__sub" style="margin-bottom:24px">
          Enviamos las instrucciones para restablecer tu contraseña a <b style="color:var(--rino-200)" id="recover-email-display">tu correo</b>.
        </p>
        <?= btn('Volver a iniciar sesión', ['variant' => 'ghost', 'block' => true, 'icon' => 'arrow_back', 'extra' => 'data-auth-go="login"']) ?>
      </div>
    </div>

  </div>
</div>
