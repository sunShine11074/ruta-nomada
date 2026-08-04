<?php
// ============================================================
//  includes/asistente.php — Chatbot flotante global | Ruta Nómada
//
//  La burbuja de abajo a la izquierda y el panel que abre. Se incluye
//  desde includes/topbar.php, así que aparece en todas las páginas que
//  llevan barra superior (todas menos plan.php, que tiene su propio
//  asistente integrado y con contexto del viaje).
//
//  Iconos: Font Awesome Free 7.3.1 (los mismos SVG del Figma).
//  La flecha de las sugerencias es "arrow-turn-up" girada 90° para que
//  quede como "↳", que es como aparece en el diseño.
//
//  Requiere sesión iniciada y $user = $_SESSION['user'].
// ============================================================
require_once __DIR__ . '/plan_auth.php';

$_as_user   = $_SESSION['user'] ?? null;
$_as_nombre = trim((string)($_as_user['nombre'] ?? ''));
// Sólo el nombre de pila: "Ramon Ernesto Nieblas Mendivil" saluda fatal.
$_as_pila   = $_as_nombre !== '' ? explode(' ', $_as_nombre)[0] : '';
$_as_ini    = $_as_pila !== '' ? mb_strtoupper(mb_substr($_as_pila, 0, 1)) : '?';
$_as_sugs   = [
    '¿Cómo puedes ayudarme?',
    '¿Qué es Ruta Nómada?',
    '¿De qué forma puedo crear un plan de viaje?',
];
?>
<link rel="stylesheet" href="asistente.css">

<div class="rnas" id="rnas" data-csrf="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>" data-inicial="<?= htmlspecialchars($_as_ini, ENT_QUOTES, 'UTF-8') ?>">

  <!-- ── Burbuja flotante ───────────────────────────────── -->
  <button class="rnas__bubble" id="rnasBubble" type="button"
          aria-label="Abrir el asistente de IA" aria-expanded="false" aria-controls="rnasPanel">
    <img src="img/asistente.png" alt="" width="26" height="26">
    <span class="rnas__bubbleTip">Asistente IA</span>
  </button>

  <!-- ── Panel ──────────────────────────────────────────── -->
  <section class="rnas__panel" id="rnasPanel" role="dialog" aria-labelledby="rnasTitle" hidden>

    <!-- Elipse #C5F9FF 615x463 con desenfoque de capa, como en el Figma -->
    <span class="rnas__glow" aria-hidden="true"></span>

    <header class="rnas__head">
      <img class="rnas__logo" src="img/asistente.png" alt="" width="34" height="34">
      <h2 class="rnas__title" id="rnasTitle">Asistente IA</h2>
      <button class="rnas__new" id="rnasNew" type="button" title="Empezar una conversación nueva">
        Nuevo chat
        <svg viewBox="0 0 640 640" width="13" height="13" aria-hidden="true"><path fill="currentColor" d="M300.3 440.8C312.9 451 331.4 450.3 343.1 438.6L471.1 310.6C480.3 301.4 483 287.7 478 275.7C473 263.7 461.4 256 448.5 256L192.5 256C179.6 256 167.9 263.8 162.9 275.8C157.9 287.8 160.7 301.5 169.9 310.6L297.9 438.6L300.3 440.8z"/></svg>
      </button>
      <button class="rnas__min" id="rnasMin" type="button" aria-label="Minimizar el asistente" title="Minimizar">
        <svg viewBox="0 0 24 24" width="17" height="17" aria-hidden="true"><path d="M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
      </button>
    </header>

    <div class="rnas__body" id="rnasBody">

      <p class="rnas__note">
        <svg viewBox="0 0 640 640" width="15" height="15" aria-hidden="true"><path fill="currentColor" d="M320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM288 224C288 206.3 302.3 192 320 192C337.7 192 352 206.3 352 224C352 241.7 337.7 256 320 256C302.3 256 288 241.7 288 224zM280 288L328 288C341.3 288 352 298.7 352 312L352 400L360 400C373.3 400 384 410.7 384 424C384 437.3 373.3 448 360 448L280 448C266.7 448 256 437.3 256 424C256 410.7 266.7 400 280 400L304 400L304 336L280 336C266.7 336 256 325.3 256 312C256 298.7 266.7 288 280 288z"/></svg>
        <span>La información generada por la IA puede no ser completamente precisa</span>
      </p>

      <!-- Pantalla de bienvenida: se oculta en cuanto hay conversación -->
      <div class="rnas__intro" id="rnasIntro">
        <h3 class="rnas__hello">
          <?php if ($_as_pila !== ''): ?>
            Hola, <?= htmlspecialchars($_as_pila, ENT_QUOTES, 'UTF-8') ?>. ¿Tienes alguna pregunta?
          <?php else: ?>
            Hola. ¿Tienes alguna pregunta?
          <?php endif; ?>
        </h3>

        <p class="rnas__sugsTitle">Puedes preguntar</p>
        <ul class="rnas__sugs">
          <?php foreach ($_as_sugs as $s): ?>
            <li>
              <button class="rnas__sug" type="button" data-q="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>">
                <svg class="rnas__sugIcon" viewBox="0 0 640 640" width="15" height="15" aria-hidden="true"><path fill="currentColor" d="M160 512C142.3 512 128 526.3 128 544C128 561.7 142.3 576 160 576L256 576C309 576 352 533 352 480L352 173.3L425.4 246.7C437.9 259.2 458.2 259.2 470.7 246.7C483.2 234.2 483.2 213.9 470.7 201.4L342.7 73.4C330.2 60.9 309.9 60.9 297.4 73.4L169.4 201.4C156.9 213.9 156.9 234.2 169.4 246.7C181.9 259.2 202.2 259.2 214.7 246.7L288 173.3L288 480C288 497.7 273.7 512 256 512L160 512z"/></svg>
                <span><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></span>
              </button>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Conversación -->
      <div class="rnas__log" id="rnasLog" role="log" aria-live="polite" aria-atomic="false"></div>
    </div>

    <footer class="rnas__foot">
      <form class="rnas__form" id="rnasForm" autocomplete="off">
        <input class="rnas__input" id="rnasInput" type="text" maxlength="500"
               placeholder="Pide información sobre viajes …" aria-label="Escribe tu pregunta al asistente">
        <button class="rnas__send" id="rnasSend" type="submit" aria-label="Enviar pregunta">
          <svg viewBox="0 0 640 640" width="15" height="15" aria-hidden="true"><path fill="currentColor" d="M342.6 73.4C330.1 60.9 309.8 60.9 297.3 73.4L137.3 233.4C124.8 245.9 124.8 266.2 137.3 278.7C149.8 291.2 170.1 291.2 182.6 278.7L288 173.3L288 544C288 561.7 302.3 576 320 576C337.7 576 352 561.7 352 544L352 173.3L457.4 278.7C469.9 291.2 490.2 291.2 502.7 278.7C515.2 266.2 515.2 245.9 502.7 233.4L342.7 73.4z"/></svg>
        </button>
      </form>
    </footer>
  </section>
</div>

<script src="js/asistente.js" defer></script>
