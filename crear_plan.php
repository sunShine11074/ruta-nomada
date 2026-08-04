<?php
// ============================================================
//  crear_plan.php — Planificador de viajes | Ruta Nómada
//  SPA con 3 pantallas: Crear → Invitar → Planificador
// ============================================================
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/plan_auth.php';
require_once __DIR__ . '/includes/maps_lib.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$plan_csrf = csrfToken();   // para api/plan_create.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planifica tu viaje — Ruta Nómada</title>
    <meta name="description" content="Crea itinerarios de viaje colaborativos con mapa, presupuesto y arrastrar y soltar. Ruta Nómada — tu compañero de viajes.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="topbar.css">
    <link rel="stylesheet" href="crear_plan.css">
</head>
<body class="dashboard-page">

<?php $topbar_active = null; include __DIR__ . '/includes/topbar.php'; ?>

<!-- ════════════════════════════════════════════════════════════
     SCREEN 1: CREAR VIAJE
════════════════════════════════════════════════════════════ -->
<div id="cpCreate" class="cp-screen active cp-create">
  <h1 class="cp-create__title">Planifica un nuevo viaje</h1>
  <div class="cp-create__form">

    <!-- Destino -->
    <div class="cp-input-card" id="cpDestCard">
      <label for="cpDestInput">¿A dónde vas?</label>
      <input type="text" id="cpDestInput" placeholder="p. ej. San Diego, Oaxaca, París…" autocomplete="off" value="">
      <div class="cp-autocomplete" id="cpDestSug" hidden></div>
    </div>

    <!-- Fechas -->
    <div class="cp-input-card" id="cpDateCard" style="position:relative">
      <label>Fechas</label>
      <div class="cp-dates-row">
        <button class="cp-date-btn" id="cpCalToggle" type="button">
          <?= file_exists('img/cal.svg') ? '' : '' ?>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2b5760" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span id="cpStartLbl">Fecha de inicio</span>
        </button>
        <span class="cp-date-sep"></span>
        <button class="cp-date-btn" id="cpCalToggle2" type="button">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2b5760" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span id="cpEndLbl">Fecha final</span>
        </button>
      </div>

      <!-- Calendar dropdown -->
      <div class="cp-calendar" id="cpCalWrap" hidden>
        <div class="cp-cal-grid">
          <button class="cp-cal-nav prev" id="cpCalPrev" type="button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <div class="cp-cal-month">
            <div class="cp-cal-month__title" id="cpCalTitle0"></div>
            <div class="cp-cal-days" id="cpCalGrid0"></div>
          </div>
          <div class="cp-cal-month">
            <div class="cp-cal-month__title" id="cpCalTitle1"></div>
            <div class="cp-cal-days" id="cpCalGrid1"></div>
          </div>
          <button class="cp-cal-nav next" id="cpCalNext" type="button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
          </button>
        </div>
        <div class="cp-cal-footer">
          <button id="cpCalClear" type="button">Limpiar fechas</button>
          <button id="cpCalDone" type="button" class="done">Listo</button>
        </div>
      </div>
    </div>

    <!-- Invite + Privacy row -->
    <div class="cp-meta-row">
      <button class="cp-invite-toggle" id="cpInvToggle" type="button">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
        Invitar
      </button>
      <div style="position:relative">
        <button class="cp-privacy-btn" id="cpPrivBtn" type="button">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span id="cpPrivLabel">Amigos</span>
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="cp-dropdown" id="cpPrivDrop" hidden>
          <button class="cp-dropdown-item" id="cpPrivAmigos">👥 Amigos</button>
          <button class="cp-dropdown-item" id="cpPrivSolo">🔒 Solo yo</button>
          <button class="cp-dropdown-item" id="cpPrivPublico">🌐 Público</button>
        </div>
      </div>
    </div>

    <!-- Invite bar -->
    <div class="cp-invite-bar" id="cpInviteRow" hidden>
      <span id="cpInvitePills"></span>
      <input type="text" id="cpInvInput" placeholder="Escribe un correo y presiona Enter">
    </div>

    <!-- CTA -->
    <div class="cp-cta-area">
      <button class="cp-cta" id="cpStartBtn" type="button" disabled>Comienza a planificar</button>
      <button class="cp-cta-sub" id="cpExploreLink" type="button">← Explorar destinos</button>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SCREEN 2: INVITAR COMPAÑEROS
════════════════════════════════════════════════════════════ -->
<div id="cpInvite" class="cp-screen cp-invite">
  <div class="cp-invite__inner">
    <button class="cp-back-btn" id="cpBackToCreate" type="button">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Atrás
    </button>
    <h1 class="cp-invite__title" id="cpInviteTitle">Invita a compañeros de viaje</h1>
    <p class="cp-invite__sub">Los compañeros que invites podrán ver y editar el itinerario contigo en tiempo real.</p>
    <div class="cp-invite__input-area">
      <div class="cp-invite__field">
        <span class="cp-invite__field-label">Para:</span>
        <span id="cpInvitePills2"></span>
        <input type="text" id="cpInvInput2" placeholder="Nombre o correo">
      </div>
    </div>
    <div class="cp-cta-area" style="margin-top:34px">
      <button class="cp-cta" id="cpInviteGoBtn" type="button" disabled>Continuar</button>
      <button class="cp-cta-sub" id="cpSkipInvite" type="button">Quizás más tarde</button>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SCREEN 3: PLANNER
════════════════════════════════════════════════════════════ -->
<div id="cpPlanner" class="cp-screen cp-planner">

  <!-- Sidebar -->
  <aside class="cp-sidebar" id="cpSidebar">
    <button class="cp-sidebar-ai" id="cpAiBtn" type="button">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="#ffffff"><path d="M12 2l2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6z"/><path d="M19 14l.9 2.6 2.6.9-2.6.9L19 21l-.9-2.6-2.6-.9 2.6-.9z"/></svg>
      Asistente de IA
    </button>

    <button class="cp-sidebar-group-btn" type="button" aria-expanded="true">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      Resumen
    </button>
    <nav class="cp-sidebar-nav">
      <button class="cp-sidebar-link active" data-nav="secExplorar">Explorar</button>
      <button class="cp-sidebar-link" data-nav="secNotas">Notas</button>
      <button class="cp-sidebar-link" data-nav="secSaved">Lugares para visitar</button>
    </nav>

    <button class="cp-sidebar-group-btn sub" type="button" aria-expanded="true">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      Itinerario
    </button>
    <nav class="cp-sidebar-nav" id="cpSidebarDays"></nav>

    <button class="cp-sidebar-group-btn sub" type="button" aria-expanded="true">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      Presupuesto
    </button>
    <nav class="cp-sidebar-nav">
      <button class="cp-sidebar-link" data-nav="secPresupuesto">Gastos</button>
    </nav>

    <div class="cp-sidebar-footer">
      <button class="cp-sidebar-footer-btn" id="cpSidebarHide" type="button">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"/></svg>
        Ocultar barra lateral
      </button>
      <button class="cp-sidebar-footer-btn danger" id="cpResetPlan" type="button">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        Nuevo plan
      </button>
    </div>
  </aside>

  <!-- Show-sidebar FAB -->
  <button class="cp-sidebar-fab" id="cpSidebarFab" type="button" hidden>
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 17l5-5-5-5M13 17l5-5-5-5"/></svg>
  </button>

  <!-- Main content -->
  <div class="cp-main" id="cpMain">

    <!-- Hero -->
    <div class="cp-hero">
      <img id="cpHeroImg" src="" alt="Portada del viaje" loading="lazy">
      <button class="cp-hero__edit" id="cpHeroEdit" title="Cambiar portada" type="button">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
      </button>
    </div>

    <!-- Summary card -->
    <div class="cp-summary-wrap">
      <div class="cp-summary">
        <div style="display:flex;align-items:flex-start;gap:8px">
          <div id="cpNameDisplay">
            <h1 class="cp-summary__name" id="cpTripName"></h1>
          </div>
          <div id="cpNameEdit" hidden>
            <input class="cp-summary__name-input" id="cpTripNameInput" placeholder="Nombre del viaje">
          </div>
          <button class="cp-edit-btn" id="cpNameEditBtn" type="button" title="Editar nombre">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
          </button>
        </div>

        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:10px">
          <div style="position:relative">
            <button class="cp-date-chip" id="cpDateChipBtn" type="button">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2b5760" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              <span id="cpDateChipLabel">Sin fechas</span>
            </button>
            <!-- Calendar portal for planner mode -->
            <div class="cp-calendar" id="cpCalWrap2" hidden style="top:calc(100% + 10px);left:0;transform:none">
              <div class="cp-cal-grid">
                <button class="cp-cal-nav prev" id="cpCalPrev2" type="button"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>
                <div class="cp-cal-month"><div class="cp-cal-month__title" id="cpCalTitle0b"></div><div class="cp-cal-days" id="cpCalGrid0b"></div></div>
                <div class="cp-cal-month"><div class="cp-cal-month__title" id="cpCalTitle1b"></div><div class="cp-cal-days" id="cpCalGrid1b"></div></div>
                <button class="cp-cal-nav next" id="cpCalNext2" type="button"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button>
              </div>
              <div class="cp-cal-footer"><button id="cpCalClear2" type="button">Limpiar fechas</button><button id="cpCalDone2" type="button" class="done">Listo</button></div>
            </div>
          </div>

          <span id="cpAvatars" class="cp-avatars"></span>
          <button class="cp-add-member" id="cpShareOpenBtn" type="button" title="Invitar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
          </button>
          <div style="flex:1"></div>
          <button class="cp-share-btn" id="cpShareOpenBtn2" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
            Comparte
          </button>
        </div>
      </div>
    </div>

    <!-- Content sections -->
    <div class="cp-content">

      <!-- Explorar -->
      <section class="cp-section" id="secExplorar">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
          <h2 class="cp-section__title">Explorar</h2>
          <button class="cp-explore-all" id="cpExploreAllBtn" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            Explorar todo
          </button>
        </div>
        <div class="cp-explore-row" id="cpExploreCards"></div>
      </section>

      <!-- Notas -->
      <section class="cp-section" id="secNotas">
        <h2 class="cp-section__title">Notas</h2>
        <div class="cp-notes-card">
          <textarea id="cpNotasTA" placeholder="Anota ideas, enlaces, cosas por recordar…"></textarea>
        </div>
      </section>

      <!-- Lugares para visitar -->
      <section class="cp-section" id="secSaved">
        <div style="display:flex;align-items:center;gap:10px">
          <h2 class="cp-section__title">Lugares para visitar</h2>
          <span class="cp-badge" id="cpSavedBadge">0</span>
        </div>
        <div class="cp-empty-state" id="cpSavedEmpty">
          Aún no hay nada aquí. Añade lugares desde «Explorar todo» o escribe en el campo de búsqueda de abajo, y luego arrástralos a un día del itinerario.
        </div>
        <div id="cpSavedList" style="display:flex;flex-direction:column;gap:6px;margin-top:10px"></div>
        <div class="cp-add-place" style="margin-top:10px;position:relative">
          <div class="cp-add-place-input">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5b6b74" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input value="" placeholder="Buscar un lugar para añadir" data-addq="saved">
          </div>
          <div class="cp-autocomplete" id="cpSavedAc" hidden></div>
        </div>
      </section>

      <!-- ── Itinerario ────────────────────────────────── -->
      <section class="cp-section" id="secItinerario" style="margin-top:48px">
        <div class="cp-itin-header">
          <h2 class="cp-section__title big">Itinerario</h2>
          <div style="display:flex;align-items:center;gap:10px">
            <button class="cp-date-chip" id="cpDateChipBtn" type="button" style="font-size:12px;padding:6px 12px">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2b5760" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              <span id="cpItinDates">Sin fechas</span>
            </button>
            <button class="cp-print-btn" id="cpPrintBtn" type="button">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
              Imprimir / PDF
            </button>
          </div>
        </div>
        <div id="cpDays"></div>
      </section>

      <!-- ── Presupuesto ───────────────────────────────── -->
      <section class="cp-section" id="secPresupuesto" style="margin-top:48px">
        <h2 class="cp-section__title big">Presupuesto</h2>

        <div class="cp-budget-card">
          <div>
            <p class="cp-budget-total" id="cpBudTotal">MX$0</p>
            <p class="cp-budget-sub" id="cpBudSub" hidden></p>
            <div class="cp-budget-bar" id="cpBudBarWrap" hidden><span id="cpBudBar" style="width:0%"></span></div>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;justify-content:center">
            <div id="cpBudEditOff">
              <button class="cp-outline-btn" id="cpBudEditStart" type="button">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                Definir presupuesto
              </button>
            </div>
            <div id="cpBudEditOn" hidden>
              <div class="cp-budget-edit">
                <span style="font-size:12px;font-weight:600;color:#5b6b74">MX$</span>
                <input id="cpBudInput" type="number" min="0" placeholder="0">
                <button id="cpBudSave" type="button">Guardar</button>
              </div>
            </div>
            <button class="cp-outline-btn" id="cpDesgloseToggle" type="button">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
              Ver desglose
            </button>
          </div>
        </div>

        <div class="cp-desglose" id="cpDesglose" hidden></div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:18px">
          <h3 style="margin:0;font-size:16px;font-weight:700;color:#0d1f27">Gastos</h3>
          <div style="display:flex;align-items:center;gap:8px">
            <label style="display:flex;align-items:center;gap:7px;font-size:12.3px;color:#5b6b74">
              <b style="color:#16262e;font-weight:600">Orden:</b>
              <select id="cpExpSort" style="border:1px solid #c8d3da;border-radius:8px;background:#ffffff;font-size:12px;color:#16262e;padding:5px 8px;cursor:pointer">
                <option value="fecha">Fecha (más reciente primero)</option>
                <option value="monto">Monto (mayor primero)</option>
              </select>
            </label>
            <button class="cp-outline-btn" id="cpExpFormToggle" type="button">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
              Añadir gasto
            </button>
          </div>
        </div>

        <!-- Expense form -->
        <div class="cp-exp-form" id="cpExpForm" hidden>
          <div class="cp-exp-form-fields">
            <input id="cpExpConcepto" placeholder="Concepto" style="flex:2;min-width:140px">
            <input id="cpExpMonto" type="number" min="0" placeholder="Monto" style="width:100px">
            <select id="cpExpCat" style="width:130px">
              <option value="Alojamiento">Alojamiento</option>
              <option value="Comida" selected>Comida</option>
              <option value="Actividades">Actividades</option>
              <option value="Transporte">Transporte</option>
              <option value="Compras">Compras</option>
              <option value="Otro">Otro</option>
            </select>
            <select id="cpExpDia" style="width:120px"></select>
          </div>
          <div class="cp-exp-form-actions">
            <button class="cp-cta" id="cpExpSave" type="button" style="padding:9px 22px;font-size:13px">Guardar</button>
            <button class="cp-cta-sub" id="cpExpCancel" type="button">Cancelar</button>
          </div>
        </div>

        <p id="cpExpEmpty" style="margin:10px 0 0;font-size:13px;color:#6b7a83">Aún no has añadido ningún gasto.</p>
        <div id="cpExpList" style="margin-top:10px;display:flex;flex-direction:column;gap:8px"></div>
      </section>

    </div><!-- .cp-content -->
  </div><!-- .cp-main -->

  <!-- Map panel -->
  <div class="cp-map-panel" id="cpMapPanel">
    <div class="cp-map-container" id="cpMapContainer"></div>
    <div class="cp-map-controls">
      <select class="cp-map-day-sel" id="cpMapDaySel"></select>
    </div>
    <button class="cp-map-fullscreen" id="cpMapFullscreen" type="button" title="Pantalla completa">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3M21 8V5a2 2 0 0 0-2-2h-3M3 16v3a2 2 0 0 0 2 2h3M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
    </button>
  </div>

</div><!-- #cpPlanner -->

<!-- ════════════════════════════════════════════════════════════
     MODALS
════════════════════════════════════════════════════════════ -->

<!-- Share modal -->
<div class="cp-overlay" id="cpShareModal" hidden>
  <div class="cp-modal cp-share-modal">
    <button class="cp-modal__close" id="cpShareClose" type="button">✕</button>
    <h2>Comparte tu plan</h2>
    <p>Cualquier persona con el enlace podrá acceder según el rol que elijas.</p>
    <div class="cp-share-link-row">
      <input id="cpShareLink" value="https://rutanomada.mx/plan/sandiego-x7k9" readonly>
      <button id="cpCopyLink" type="button">Copiar</button>
    </div>
    <div class="cp-share-role">
      <b style="color:#16262e;font-weight:600">Rol del enlace:</b>
      <select id="cpShareRole">
        <option value="editar">Puede editar</option>
        <option value="ver">Solo ver</option>
      </select>
    </div>
    <div class="cp-share-invite-area">
      <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#16262e">Invitar por correo</p>
      <div class="cp-share-link-row">
        <input id="cpShareInv" placeholder="correo@ejemplo.com">
        <button type="button">Invitar</button>
      </div>
    </div>
  </div>
</div>

<!-- Explore all modal -->
<div class="cp-overlay" id="cpExploreModal" hidden>
  <div class="cp-modal cp-explore-modal">
    <button class="cp-modal__close" id="cpExploreClose" type="button">✕</button>
    <h2 style="margin:0;font-size:22px;font-weight:700;color:#0d1f27">Explorar lugares</h2>
    <div style="display:flex;gap:10px;align-items:center;margin-top:14px;flex-wrap:wrap">
      <div class="cp-explore-tabs">
        <button class="cp-explore-tab active" data-extab="hacer">Cosas que hacer</button>
        <button class="cp-explore-tab" data-extab="rest">Restaurantes</button>
        <button class="cp-explore-tab" data-extab="hotel">Hoteles</button>
      </div>
      <div class="cp-add-place-input" style="flex:1;min-width:180px;padding:7px 12px">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5b6b74" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input id="cpExQ" placeholder="Buscar…" value="">
      </div>
    </div>
    <div class="cp-explore-grid" id="cpExploreGrid"></div>
  </div>
</div>

<!-- AI assistant modal -->
<div class="cp-overlay" id="cpAiModal" hidden>
  <div class="cp-modal cp-ai-modal">
    <button class="cp-modal__close" id="cpAiClose" type="button">✕</button>
    <div class="cp-ai-header">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0c8bd7" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6z"/></svg>
      <h2>Asistente de IA</h2>
    </div>
    <div class="cp-ai-log" id="cpAiLog"></div>
    <div class="cp-ai-actions">
      <button class="cp-ai-action" data-ai="fill" type="button">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0c8bd7" stroke-width="1.9" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/></svg>
        Rellena el día más vacío
      </button>
      <button class="cp-ai-action" data-ai="rest" type="button">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="1.9" stroke-linecap="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><path d="M6 1v3M10 1v3M14 1v3"/></svg>
        Sugiéreme restaurantes
      </button>
      <button class="cp-ai-action" data-ai="must" type="button">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 2l2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6z" fill="#f0b429"/></svg>
        Añade los imperdibles
      </button>
    </div>
  </div>
</div>

<!-- Scripts -->
<script>window.PLAN_CSRF = <?= json_encode($plan_csrf) ?>;</script>
<script src="js/topbar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script
  src="<?= htmlspecialchars(mapsScriptUrl('__cpMapReady'), ENT_QUOTES, 'UTF-8') ?>"
  async defer></script>
<script src="js/crear_plan.js"></script>
</body>
</html>
