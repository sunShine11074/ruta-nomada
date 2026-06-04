/* ═══════════════════════════════════════════════════════════════
   Ruta Nómada — Vanilla JS app logic
   Replaces all React components' state & interactivity.
   ═══════════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  /* ───────── DOM refs ───────── */
  const $ = (sel, ctx) => (ctx || document).querySelector(sel);
  const $$ = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));

  const authFlow   = $('#auth-flow');
  const appShell   = $('#app-shell');
  const sidebar    = $('#sidebar');
  const mainEl     = $('.main');
  const screens    = $$('.screen');
  const topnavBtns = $$('.topnav__item[data-route]');
  const sideLinks  = $$('.side-link[data-route]');

  /* ───────── Placeholder builder (for detail hero) ───────── */
  const PH_TINTS = {
    cultura: 'var(--neptune-600)', romance: 'var(--naples-600)', aventura: 'var(--olive-600)',
    desierto: 'var(--barley-500)', agua: 'var(--neptune-500)', bosque: 'var(--olive-500)', ciudad: 'var(--rino-700)',
  };
  const PH_ICONS = {
    cultura: 'temple_buddhist', romance: 'wine_bar', aventura: 'landscape',
    desierto: 'wb_sunny', agua: 'sailing', bosque: 'forest', ciudad: 'apartment',
    hotel: 'hotel', food: 'restaurant', map: 'map', video: 'play_circle',
    beach: 'beach_access', default: 'image', sailing: 'sailing',
  };
  function buildPlaceholder(tint, icon, label) {
    const color = PH_TINTS[tint] || PH_TINTS.agua;
    const icoName = PH_ICONS[icon] || PH_ICONS[tint] || PH_ICONS.default;
    let html = '<div class="ph" style="--ph-c:' + color + '">';
    html += '<span class="ph__ico"><span class="material-symbols-outlined">' + icoName + '</span></span>';
    if (label) {
      html += '<span class="ph__tag"><span class="material-symbols-outlined">add_photo_alternate</span>' + label + '</span>';
    }
    html += '</div>';
    return html;
  }

  /* ───────── State (mirrors React's useState + localStorage) ───────── */
  let route     = localStorage.getItem('rn_route') || 'top:inicio';
  let collapsed = localStorage.getItem('rn_sidebar') === '1';
  let authed    = localStorage.getItem('rn_authed') === '1';
  let currentDest = null;  // the destination object for detail view

  function saveState() {
    localStorage.setItem('rn_route', route);
    localStorage.setItem('rn_sidebar', collapsed ? '1' : '0');
    localStorage.setItem('rn_authed', authed ? '1' : '0');
  }

  /* ───────── Auth flow ───────── */
  const authCards = {
    login:            $('#auth-login'),
    registro:         $('#auth-registro'),
    recuperacion:     $('#auth-recuperacion'),
    'recuperacion-ok': $('#auth-recuperacion-ok'),
  };

  function showAuthCard(mode) {
    Object.keys(authCards).forEach(k => {
      authCards[k].style.display = (k === mode) ? '' : 'none';
      if (k === mode) reanimate(authCards[k]);
    });
  }

  function enterApp() {
    authed = true;
    route = 'top:inicio';
    saveState();
    render();
  }

  function logout() {
    authed = false;
    saveState();
    render();
  }

  // Auth navigation
  document.addEventListener('click', function (e) {
    const goBtn = e.target.closest('[data-auth-go]');
    if (goBtn) {
      e.preventDefault();
      showAuthCard(goBtn.dataset.authGo);
    }
  });

  // Login form
  const loginForm = $('#login-form');
  if (loginForm) loginForm.addEventListener('submit', function (e) { e.preventDefault(); enterApp(); });

  // Registro form
  const regForm = $('#registro-form');
  if (regForm) regForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const terms = $('#terms-check');
    if (terms && terms.checked) enterApp();
  });

  // Recuperación form
  const recForm = $('#recuperacion-form');
  if (recForm) recForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const email = $('#recover-email');
    const display = $('#recover-email-display');
    if (display) display.textContent = (email && email.value) || 'tu correo';
    showAuthCard('recuperacion-ok');
  });

  // Logout
  const logoutBtn = $('#logout-btn');
  if (logoutBtn) logoutBtn.addEventListener('click', logout);

  /* ───────── Password visibility toggle ───────── */
  document.addEventListener('click', function (e) {
    const toggle = e.target.closest('[data-pw-toggle]');
    if (!toggle) return;
    const control = toggle.closest('.field__control');
    if (!control) return;
    const input = control.querySelector('[data-pw-input]');
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    const icon = toggle.querySelector('.material-symbols-outlined');
    if (icon) icon.textContent = isPassword ? 'visibility_off' : 'visibility';
  });

  /* ───────── Routing ───────── */
  function navigateTo(newRoute) {
    route = newRoute;
    saveState();
    render();
    if (mainEl) mainEl.scrollTop = 0;
  }

  // All [data-route] clicks (topbar, sidebar, breadcrumbs, detail back button)
  document.addEventListener('click', function (e) {
    const routeEl = e.target.closest('[data-route]');
    if (!routeEl) return;
    e.preventDefault();
    e.stopPropagation();
    navigateTo(routeEl.dataset.route);
  });

  /* ───────── Sidebar toggle ───────── */
  const sideToggle = $('#sidebar-toggle');
  if (sideToggle) sideToggle.addEventListener('click', function () {
    collapsed = !collapsed;
    saveState();
    updateSidebar();
  });

  function updateSidebar() {
    if (!sidebar) return;
    sidebar.classList.toggle('collapsed', collapsed);
    const ico = sideToggle.querySelector('.material-symbols-outlined');
    if (ico) ico.textContent = collapsed ? 'menu' : 'menu_open';
    // Show/hide profile section
    const profile = sidebar.querySelector('.sidebar__profile');
    if (profile) profile.style.display = collapsed ? 'none' : '';
  }

  /* ───────── Destination card clicks → open detail ───────── */
  document.addEventListener('click', function (e) {
    // Ignore if clicking the fav button
    if (e.target.closest('[data-fav-btn]')) return;
    const card = e.target.closest('[data-dest-card]');
    if (!card) return;
    try {
      currentDest = JSON.parse(card.dataset.destJson);
    } catch (_) { return; }
    route = 'detail';
    saveState();
    populateDetail();
    render();
    if (mainEl) mainEl.scrollTop = 0;
  });

  function populateDetail() {
    if (!currentDest) return;
    const d = currentDest;
    const setText = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
    const setHTML = (id, html) => { const el = document.getElementById(id); if (el) el.innerHTML = html; };

    setText('detail-crumb-name', d.name);
    setText('detail-name', d.name);
    setText('detail-rating', d.rating);
    setText('detail-desc', d.desc || 'Disfruta de las playas paradisíacas, su cultura local, vida nocturna, actividades acuáticas y mucho más.');
    setText('detail-country', d.country || 'México');
    setText('detail-price', d.price);
    setText('detail-placeholder-name', d.name);
    setHTML('detail-hero-ph', buildPlaceholder(d.tint, d.icon, 'Portada — ' + d.name));
    setHTML('detail-long-desc',
      '<p class="prose" style="color:var(--ink)">' + d.name +
      ' es uno de los destinos turísticos más famosos del mundo. Ofrece playas de arena blanca y mar turquesa, zonas arqueológicas cercanas, parques temáticos, cenotes, restaurantes de clase mundial y una vibrante vida nocturna. Ideal para viajes en pareja, familias y grupos de amigos.</p>'
    );

    // Reset tab to desc
    showDetailTab('desc');
  }

  /* ───────── Detail tabs ───────── */
  document.addEventListener('click', function (e) {
    const tabBtn = e.target.closest('[data-detail-tab]');
    if (!tabBtn) return;
    showDetailTab(tabBtn.dataset.detailTab);
  });

  function showDetailTab(tabId) {
    // Update tab buttons
    $$('.detail-tab').forEach(btn => {
      const isActive = btn.dataset.detailTab === tabId;
      btn.classList.toggle('active', isActive);
      btn.style.fontWeight = isActive ? '700' : '500';
      btn.style.color = isActive ? 'var(--rino-400)' : 'var(--ink-soft)';
      btn.style.borderBottom = isActive ? '3px solid var(--cta)' : '3px solid transparent';
    });

    // Show/hide panels
    const descPanel = $('#tab-desc');
    const resenasPanel = $('#tab-resenas');
    const placeholderPanel = $('#tab-placeholder');

    if (descPanel) descPanel.style.display = tabId === 'desc' ? '' : 'none';
    if (resenasPanel) resenasPanel.style.display = tabId === 'resenas' ? 'flex' : 'none';
    if (placeholderPanel) placeholderPanel.style.display = ['hacer', 'comer', 'llegar'].includes(tabId) ? '' : 'none';

    // Re-animate visible panel
    const visiblePanel = tabId === 'desc' ? descPanel : tabId === 'resenas' ? resenasPanel : placeholderPanel;
    if (visiblePanel) reanimate(visiblePanel);
  }

  /* ───────── Favorite toggle ───────── */
  document.addEventListener('click', function (e) {
    const favBtn = e.target.closest('[data-fav-btn]');
    if (!favBtn) return;
    e.stopPropagation();
    favBtn.classList.toggle('on');
  });

  /* ───────── Category filter (Explorar) ───────── */
  document.addEventListener('click', function (e) {
    const chip = e.target.closest('[data-cat-filter]');
    if (!chip) return;
    const cat = chip.dataset.catFilter;

    // Update active chip
    $$('[data-cat-filter]').forEach(c => c.classList.toggle('active', c.dataset.catFilter === cat));

    // Filter cards
    const grid = $('#explorar-grid');
    const empty = $('#explorar-empty');
    if (!grid) return;

    const cards = $$('[data-dest-card]', grid);
    let visible = 0;
    cards.forEach(card => {
      const show = cat === 'todos' || card.dataset.category === cat;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    grid.style.display = visible ? '' : 'none';
    if (empty) empty.style.display = visible ? 'none' : '';
  });

  /* ───────── Config toggles ───────── */
  document.addEventListener('click', function (e) {
    const toggle = e.target.closest('[data-toggle]');
    if (!toggle) return;
    toggle.classList.toggle('on');
    toggle.setAttribute('aria-pressed', toggle.classList.contains('on'));
  });

  /* ───────── Fade re-animation helper ───────── */
  function reanimate(el) {
    if (!el) return;
    el.classList.remove('fade');
    // Force reflow
    void el.offsetWidth;
    el.classList.add('fade');
  }

  /* ───────── Main render function ───────── */
  function render() {
    if (!authed) {
      // Show auth, hide app
      authFlow.style.display = '';
      appShell.style.display = 'none';
      showAuthCard('login');
      return;
    }

    // Show app, hide auth
    authFlow.style.display = 'none';
    appShell.style.display = '';

    // Resolve screen ID from route
    const routeToScreen = {
      'top:inicio':    'screen-inicio',
      'top:explorar':  'screen-explorar',
      'top:misviajes': 'screen-misviajes',
      'top:comunidad': 'screen-comunidad',
      'side:perfil':   'screen-perfil',
      'side:misplanes':'screen-misplanes',
      'side:config':   'screen-config',
      'detail':        'screen-detail',
    };
    const activeId = routeToScreen[route] || 'screen-inicio';

    // Show/hide screens
    screens.forEach(s => {
      const isActive = s.id === activeId;
      s.style.display = isActive ? '' : 'none';
      if (isActive) reanimate(s);
    });

    // Update topnav active state
    const topId = route.startsWith('top:') ? route : null;
    topnavBtns.forEach(btn => {
      const isActive = btn.dataset.route === topId;
      btn.classList.toggle('active', isActive);
      // Update icon fill
      const ico = btn.querySelector('.material-symbols-outlined');
      if (ico) ico.classList.toggle('fill', isActive);
    });

    // Update sidebar active state
    const sideId = route.startsWith('side:') ? route : null;
    sideLinks.forEach(link => {
      const isActive = link.dataset.route === sideId;
      link.classList.toggle('active', isActive);
      const ico = link.querySelector('.material-symbols-outlined');
      if (ico) ico.classList.toggle('fill', isActive);
    });

    // Sidebar collapse
    updateSidebar();
  }

  /* ───────── Init ───────── */
  render();

})();
