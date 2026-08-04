/* ============================================================
   js/profile.js — Ruta Nómada · Perfil de usuario
   Vanilla JS — tabs, calendar, hold-to-save, image upload, etc.
   Faithfully reproduces the interactivity of Perfil.dc.html.
============================================================ */
(function () {
  "use strict";

  /* ── Helpers ─────────────────────────────────────────────── */
  var $ = function (id) { return document.getElementById(id); };
  var MES = ['enero','febrero','marzo','abril','mayo','junio',
             'julio','agosto','septiembre','octubre','noviembre','diciembre'];
  var WEEK = ['lu','ma','mi','ju','vi','sá','do'];

  /* ── State ──────────────────────────────────────────────── */
  var currentTab  = 'perfil';
  var calOpen     = false;
  var calMode     = 'days';         // 'days' | 'years'
  var calViewY, calViewM;           // calendar navigation
  var selBirthY, selBirthM, selBirthD;
  var camOpen     = false;
  var showActual  = false, showNueva = false, showRepetir = false;
  var holdEngine  = null;

  /* ── Init ───────────────────────────────────────────────── */
  function init() {
    // Read initial birth date from the hidden input
    var biso = ($('birthISO') || {}).value || '';
    if (biso) {
      var parts = biso.split('-');
      selBirthY = parseInt(parts[0], 10);
      selBirthM = parseInt(parts[1], 10) - 1; // 0-indexed month
      selBirthD = parseInt(parts[2], 10);
    } else {
      var now = new Date();
      selBirthY = now.getFullYear() - 20;
      selBirthM = 0;
      selBirthD = 1;
    }
    calViewY = selBirthY;
    calViewM = selBirthM;

    bindTabs();
    bindGender();
    bindEmail();
    bindPasswordEyes();
    bindCalendar();
    bindCamera();
    bindHoldButtons();
    bindOutsideClicks();
    initGeo();
    initDivisa();

    // Show initial tab
    switchTab(currentTab);
  }

  /* ── Fecha: límites [hoy-100 años, hoy] ── */
  function todayMidnight() { var d = new Date(); d.setHours(0,0,0,0); return d; }
  function minBirthDate()  { var d = todayMidnight(); d.setFullYear(d.getFullYear() - 100); return d; }
  var CAL_MAXY = new Date().getFullYear();
  var CAL_MINY = CAL_MAXY - 100;

  /* ── Errores por campo ── */
  function showErr(id, msg) {
    var el = document.getElementById(id);
    if (el) { el.textContent = msg; el.classList.add('show'); el.style.display = 'block'; }
  }
  function clearErr(id) {
    var el = document.getElementById(id);
    if (el) { el.textContent = ''; el.classList.remove('show'); el.style.display = 'none'; }
  }
  function markInvalid(inputId, on) {
    var el = document.getElementById(inputId);
    if (el) el.classList.toggle('field-invalid', !!on);
  }

  /* ── Geo: cascada país → estado → ciudad ── */
  function initGeo() {
    var pais   = $('inputNacionalidad');
    var estado = $('inputEstado');
    var ciudad = $('inputCiudad');
    if (!pais) return;

    pais.addEventListener('change', function () {
      clearErr('nacErr'); markInvalid('inputNacionalidad', false);
      updatePaisFlag();
      var opt = pais.options[pais.selectedIndex];
      $('paisIso').value = opt ? (opt.getAttribute('data-iso') || '') : '';
      loadStates(opt ? opt.getAttribute('data-iso') : '', '');
      // reset ciudad
      resetSelect(ciudad, 'Elige un estado primero'); ciudad.disabled = true;
      $('estadoIso').value = '';
    });
    estado.addEventListener('change', function () {
      clearErr('estadoErr'); markInvalid('inputEstado', false);
      var opt = estado.options[estado.selectedIndex];
      $('estadoIso').value = opt ? (opt.getAttribute('data-iso') || '') : '';
      loadCities($('paisIso').value, opt ? opt.getAttribute('data-iso') : '', '');
    });
    ciudad.addEventListener('change', function () {
      clearErr('ciudadErr'); markInvalid('inputCiudad', false);
    });

    loadCountries();
  }

  function resetSelect(sel, placeholder) {
    if (!sel) return;
    sel.innerHTML = '<option value="">' + placeholder + '</option>';
  }

  function fillSelect(sel, items, valueKey, isoAttr, selectedName) {
    if (!sel) return;
    var html = '<option value="">Selecciona…</option>';
    items.forEach(function (it) {
      var name = it[valueKey];
      var iso  = isoAttr ? (it[isoAttr] || '') : '';
      var sel2 = (selectedName && name === selectedName) ? ' selected' : '';
      html += '<option value="' + escapeHtml(name) + '" data-iso="' + escapeHtml(iso) + '"' + sel2 + '>' + escapeHtml(name) + '</option>';
    });
    sel.innerHTML = html;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
    });
  }

  function geoFetch(params) {
    return fetch('geo.php?' + params, { cache: 'default' }).then(function (r) { return r.json(); });
  }

  function loadCountries() {
    var pais = $('inputNacionalidad');
    var wantName = pais.getAttribute('data-selected') || '';
    geoFetch('type=countries').then(function (res) {
      if (!res.ok || !res.data || !res.data.length) {
        resetSelect(pais, 'No disponible (revisa la API key)');
        return;
      }
      fillSelect(pais, res.data, 'name', 'iso2', wantName);
      updatePaisFlag();
      // Preseleccionar cascada guardada
      var opt = pais.options[pais.selectedIndex];
      var iso = opt ? opt.getAttribute('data-iso') : '';
      $('paisIso').value = iso || '';
      if (iso) {
        loadStates(iso, $('inputEstado').getAttribute('data-selected') || '');
      }
    }).catch(function () { resetSelect(pais, 'Error de red'); });
  }

  function loadStates(iso, wantName) {
    var estado = $('inputEstado');
    var ciudad = $('inputCiudad');
    if (!iso) { resetSelect(estado, 'Elige un país primero'); estado.disabled = true; return; }
    resetSelect(estado, 'Cargando…'); estado.disabled = true;
    geoFetch('type=states&country=' + encodeURIComponent(iso)).then(function (res) {
      if (!res.ok || !res.data || !res.data.length) { resetSelect(estado, 'Sin estados'); estado.disabled = true; return; }
      fillSelect(estado, res.data, 'name', 'iso2', wantName);
      estado.disabled = false;
      var opt = estado.options[estado.selectedIndex];
      var siso = opt ? opt.getAttribute('data-iso') : '';
      $('estadoIso').value = siso || '';
      if (siso) loadCities(iso, siso, $('inputCiudad').getAttribute('data-selected') || '');
      else { resetSelect(ciudad, 'Elige un estado primero'); ciudad.disabled = true; }
    }).catch(function () { resetSelect(estado, 'Error de red'); });
  }

  function loadCities(ciso, siso, wantName) {
    var ciudad = $('inputCiudad');
    if (!ciso || !siso) { resetSelect(ciudad, 'Elige un estado primero'); ciudad.disabled = true; return; }
    resetSelect(ciudad, 'Cargando…'); ciudad.disabled = true;
    geoFetch('type=cities&country=' + encodeURIComponent(ciso) + '&state=' + encodeURIComponent(siso)).then(function (res) {
      if (!res.ok || !res.data || !res.data.length) { resetSelect(ciudad, 'Sin ciudades'); ciudad.disabled = true; return; }
      fillSelect(ciudad, res.data, 'name', null, wantName);
      ciudad.disabled = false;
    }).catch(function () { resetSelect(ciudad, 'Error de red'); });
  }

  function updatePaisFlag() {
    var pais = $('inputNacionalidad');
    var flag = $('paisFlag');
    if (!pais || !flag) return;
    var opt = pais.options[pais.selectedIndex];
    var iso = opt ? (opt.getAttribute('data-iso') || '') : '';
    if (iso) {
      flag.src = 'https://flagcdn.com/16x12/' + iso.toLowerCase() + '.png';
      flag.style.display = 'block';
      pais.style.paddingLeft = '2.4rem';
    } else {
      flag.style.display = 'none';
      pais.style.paddingLeft = '';
    }
  }

  /* ── Divisa: bandera del seleccionado ── */
  function initDivisa() {
    var sel = $('inputDivisa');
    if (!sel) return;
    sel.addEventListener('change', function () {
      var opt = sel.options[sel.selectedIndex];
      var cc = opt ? (opt.getAttribute('data-cc') || 'mx') : 'mx';
      var flag = $('divisaFlag');
      if (flag) flag.src = 'https://flagcdn.com/16x12/' + cc + '.png';
      clearErr('divisaErr');
    });
  }

  /* ════════════════════════════════════════════════════════════
     TABS
  ════════════════════════════════════════════════════════════ */
  function bindTabs() {
    $('tabPerfil').addEventListener('click', function () { switchTab('perfil'); });
    $('tabFact').addEventListener('click', function () { switchTab('facturacion'); });
    $('tabPass').addEventListener('click', function () { switchTab('password'); });
  }

  function switchTab(tab) {
    currentTab = tab;
    var teal = '#062738', gray = '#6b7280', transp = 'transparent';

    // Panels
    $('panelPerfil').style.display   = tab === 'perfil'      ? 'block' : 'none';
    $('panelFact').style.display     = tab === 'facturacion'  ? 'flex'  : 'none';
    $('panelPass').style.display     = tab === 'password'     ? 'block' : 'none';

    // Tab buttons
    var tabs = [
      { btn: $('tabPerfil'),  active: tab === 'perfil' },
      { btn: $('tabFact'),    active: tab === 'facturacion' },
      { btn: $('tabPass'),    active: tab === 'password' },
    ];
    tabs.forEach(function (t) {
      t.btn.style.fontWeight   = t.active ? '600' : '400';
      t.btn.style.color        = t.active ? teal  : gray;
      t.btn.style.borderBottom = '2.5px solid ' + (t.active ? teal : transp);
    });

    // Reset save buttons on each panel
    resetSave('saveWrapPerfil');
    resetSave('saveWrapPass');
    clearErrors();
  }

  /* ════════════════════════════════════════════════════════════
     GENDER RADIOS
  ════════════════════════════════════════════════════════════ */
  function bindGender() {
    ['Hombre','Mujer','Otro'].forEach(function (g) {
      var el = $('gender' + g);
      if (el) el.addEventListener('click', function () { setGender(g); });
    });
  }

  function setGender(g) {
    $('inputGenero').value = g;
    var teal = '#062738';
    ['Hombre','Mujer','Otro'].forEach(function (v) {
      var ring = $('ring' + v);
      var dot  = $('dot' + v);
      if (ring) ring.style.borderColor = (v === g) ? teal : '#cbd2d8';
      if (dot)  dot.style.background   = (v === g) ? teal : 'transparent';
    });
  }

  /* ════════════════════════════════════════════════════════════
     EMAIL VALIDATION
  ════════════════════════════════════════════════════════════ */
  function bindEmail() {
    var inp = $('inputEmail');
    var err = $('emailErr');
    if (!inp) return;
    inp.addEventListener('input', function () {
      if (err) { err.textContent = ''; err.style.display = 'none'; }
      inp.style.borderColor = 'transparent';
    });
    inp.addEventListener('blur', function () {
      var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (inp.value && !re.test(inp.value.trim())) {
        if (err) { err.textContent = 'Introduce un correo electrónico válido.'; err.style.display = 'block'; }
        inp.style.borderColor = '#fc8181';
      }
    });
  }

  /* ════════════════════════════════════════════════════════════
     PASSWORD EYE TOGGLES
  ════════════════════════════════════════════════════════════ */
  function bindPasswordEyes() {
    bindEye('toggleActual', 'pwActual', 'showActual');
    bindEye('toggleNueva',  'pwNueva',  'showNueva');
    bindEye('toggleRepetir','pwRepetir','showRepetir');
  }

  function bindEye(btnId, inputId, flagName) {
    var btn = $(btnId), inp = $(inputId);
    if (!btn || !inp) return;
    btn.addEventListener('click', function () {
      if (flagName === 'showActual')  showActual  = !showActual;
      if (flagName === 'showNueva')   showNueva   = !showNueva;
      if (flagName === 'showRepetir') showRepetir = !showRepetir;
      var show = (flagName === 'showActual') ? showActual
               : (flagName === 'showNueva')  ? showNueva
               : showRepetir;
      inp.type = show ? 'text' : 'password';
      btn.innerHTML = eyeSVG(show);
    });
  }

  function eyeSVG(open) {
    if (open) {
      return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    }
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-7 10-7c2 0 3.8.5 5.4 1.4M22 12s-1.4 2.4-3.9 4.3"></path><path d="M9.5 9.6a3 3 0 0 0 4.2 4.2"></path><path d="m3 3 18 18"></path></svg>';
  }

  /* ════════════════════════════════════════════════════════════
     CUSTOM CALENDAR DATE PICKER
  ════════════════════════════════════════════════════════════ */
  function bindCalendar() {
    var togBtn = $('calToggle');
    if (togBtn) togBtn.addEventListener('click', function () { toggleCalendar(); });
    var prev = $('calPrev');
    var next = $('calNext');
    var titleBtn = $('calTitle');
    if (prev)     prev.addEventListener('click', calPrevious);
    if (next)     next.addEventListener('click', calFollowing);
    if (titleBtn) titleBtn.addEventListener('click', calToggleMode);
  }

  function toggleCalendar() {
    calOpen = !calOpen;
    if (calOpen) { calMode = 'days'; calViewY = selBirthY; calViewM = selBirthM; }
    renderCalendar();
  }

  function renderCalendar() {
    var popup = $('calPopup');
    var togBtn = $('calToggle');
    if (!popup) return;
    popup.style.display = calOpen ? 'block' : 'none';
    if (togBtn) togBtn.style.borderColor = calOpen ? '#3e7986' : 'transparent';
    if (!calOpen) return;

    // Title
    var titleEl = $('calTitleText');
    if (titleEl) titleEl.textContent = MES[calViewM] + ' ' + calViewY;

    // Mode toggle
    var daysPanel  = $('calDays');
    var yearsPanel = $('calYears');
    if (daysPanel)  daysPanel.style.display  = calMode === 'days'  ? 'block' : 'none';
    if (yearsPanel) yearsPanel.style.display = calMode === 'years' ? 'grid'  : 'none';

    if (calMode === 'days') renderDays();
    else renderYears();
  }

  function renderDays() {
    var grid = $('calDaysGrid');
    if (!grid) return;
    grid.innerHTML = '';

    var first = new Date(calViewY, calViewM, 1).getDay();
    var offset = (first + 6) % 7;
    var daysInMonth = new Date(calViewY, calViewM + 1, 0).getDate();

    // Blank cells
    for (var b = 0; b < offset; b++) {
      var blank = document.createElement('div');
      blank.style.cssText = 'aspect-ratio:1';
      grid.appendChild(blank);
    }

    // Day cells (restringidos a [hoy-100 años, hoy])
    var maxD = todayMidnight(), minD = minBirthDate();
    for (var d = 1; d <= daysInMonth; d++) {
      var dDate = new Date(calViewY, calViewM, d); dDate.setHours(0,0,0,0);
      var outOfRange = dDate > maxD || dDate < minD;
      var isSel = (selBirthY === calViewY && selBirthM === calViewM && selBirthD === d);
      var cell = document.createElement('div');
      cell.style.cssText = 'aspect-ratio:1;display:flex;align-items:center;justify-content:center';
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = d;
      btn.setAttribute('data-day', d);
      if (outOfRange) {
        btn.disabled = true;
        btn.style.cssText = 'width:34px;height:34px;border:none;border-radius:9px;background:none;color:#cbd2d8;font-size:.85rem;cursor:not-allowed';
      } else if (isSel) {
        btn.style.cssText = 'width:34px;height:34px;border:none;border-radius:9px;background:#062738;color:#fff;font-size:.85rem;font-weight:600;cursor:pointer';
        btn.addEventListener('click', onPickDay);
      } else {
        btn.style.cssText = 'width:34px;height:34px;border:none;border-radius:9px;background:none;color:#374151;font-size:.85rem;cursor:pointer';
        btn.addEventListener('mouseenter', function () { this.style.background = '#eaf0f2'; this.style.color = '#062738'; });
        btn.addEventListener('mouseleave', function () { this.style.background = 'none'; this.style.color = '#374151'; });
        btn.addEventListener('click', onPickDay);
      }
      cell.appendChild(btn);
      grid.appendChild(cell);
    }
  }

  function onPickDay(e) {
    var d = parseInt(e.currentTarget.getAttribute('data-day'), 10);
    selBirthD = d;
    selBirthY = calViewY;
    selBirthM = calViewM;
    calOpen = false;
    updateBirthDisplay();
    renderCalendar();
  }

  function renderYears() {
    var panel = $('calYears');
    if (!panel) return;
    panel.innerHTML = '';
    var base = calViewY - 6;
    for (var i = 0; i < 12; i++) {
      var y = base + i;
      var isSel = (y === selBirthY);
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = y;
      btn.setAttribute('data-year', y);
      if (y < CAL_MINY || y > CAL_MAXY) {
        btn.disabled = true;
        btn.style.cssText = 'padding:.55rem 0;border:none;border-radius:9px;background:#f6f8fa;color:#cbd2d8;font-size:.85rem;cursor:not-allowed';
      } else if (isSel) {
        btn.style.cssText = 'padding:.55rem 0;border:none;border-radius:9px;background:#062738;color:#fff;font-size:.85rem;font-weight:600;cursor:pointer';
        btn.addEventListener('click', onPickYear);
      } else {
        btn.style.cssText = 'padding:.55rem 0;border:none;border-radius:9px;background:#f1f4f7;color:#374151;font-size:.85rem;cursor:pointer';
        btn.addEventListener('mouseenter', function () { this.style.background = '#e2e7ec'; this.style.color = '#062738'; });
        btn.addEventListener('mouseleave', function () { this.style.background = '#f1f4f7'; this.style.color = '#374151'; });
        btn.addEventListener('click', onPickYear);
      }
      panel.appendChild(btn);
    }
  }

  function onPickYear(e) {
    var y = parseInt(e.currentTarget.getAttribute('data-year'), 10);
    calViewY = y;
    calMode = 'days';
    renderCalendar();
  }

  function calPrevious() {
    if (calMode === 'years') { calViewY -= 12; }
    else { calViewM--; if (calViewM < 0) { calViewM = 11; calViewY--; } }
    renderCalendar();
  }

  function calFollowing() {
    if (calMode === 'years') { calViewY += 12; }
    else { calViewM++; if (calViewM > 11) { calViewM = 0; calViewY++; } }
    renderCalendar();
  }

  function calToggleMode() {
    calMode = (calMode === 'years') ? 'days' : 'years';
    renderCalendar();
  }

  function updateBirthDisplay() {
    var disp = $('birthDisplay');
    var iso  = $('birthISO');
    if (disp) disp.textContent = selBirthD + ' de ' + MES[selBirthM] + ' de ' + selBirthY;
    if (iso) iso.value = selBirthY + '-' + String(selBirthM + 1).padStart(2, '0') + '-' + String(selBirthD).padStart(2, '0');
  }

  /* ════════════════════════════════════════════════════════════
     CAMERA MENU (profile/banner image)
  ════════════════════════════════════════════════════════════ */
  function bindCamera() {
    var camBtn = $('camToggle');
    if (camBtn) camBtn.addEventListener('click', function () {
      camOpen = !camOpen;
      $('camMenu').style.display = camOpen ? 'block' : 'none';
    });
    var chooseProfile = $('chooseProfile');
    var chooseBanner  = $('chooseBanner');
    var removeImgs    = $('removeImages');
    if (chooseProfile) chooseProfile.addEventListener('click', function () {
      camOpen = false; $('camMenu').style.display = 'none';
      $('fileProfile').click();
    });
    if (chooseBanner) chooseBanner.addEventListener('click', function () {
      camOpen = false; $('camMenu').style.display = 'none';
      $('fileBanner').click();
    });
    if (removeImgs) removeImgs.addEventListener('click', function () {
      camOpen = false; $('camMenu').style.display = 'none';
      removeAllImages();
    });

    // File inputs
    var fp = $('fileProfile');
    var fb = $('fileBanner');
    if (fp) fp.addEventListener('change', function (e) { uploadImage(e, 'profile'); });
    if (fb) fb.addEventListener('change', function (e) { uploadImage(e, 'banner'); });
  }

  function uploadImage(e, type) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    var fd = new FormData();
    fd.append('image', file);
    fd.append('action', type === 'profile' ? 'upload_profile_img' : 'upload_banner_img');
    fetch('profile.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) {
          if (type === 'profile') {
            $('avatarDiv').style.backgroundImage = 'url("' + data.url + '?' + Date.now() + '")';
            var placeholder = $('avatarPlaceholder');
            if (placeholder) placeholder.style.display = 'none';
            if ($('removeImages')) $('removeImages').closest('.cam-remove-wrap') && ($('camRemoveWrap').style.display = 'block');
          } else {
            var banner = $('bannerDiv');
            banner.style.backgroundImage = 'url("' + data.url + '?' + Date.now() + '")';
            banner.style.backgroundSize = 'cover';
          }
          updateRemoveVisibility();
        } else {
          alert(data.error || 'Error al subir la imagen.');
        }
      })
      .catch(function () { alert('Error de conexión.'); });
    e.target.value = '';
  }

  function removeAllImages() {
    var fd = new FormData();
    fd.append('action', 'remove_images');
    fetch('profile.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) {
          $('avatarDiv').style.backgroundImage = 'none';
          var placeholder = $('avatarPlaceholder');
          if (placeholder) placeholder.style.display = 'flex';
          var banner = $('bannerDiv');
          banner.style.backgroundImage = "radial-gradient(rgba(6,39,56,.10) 1.3px, transparent 1.3px)";
          banner.style.backgroundSize = '13px 13px';
          updateRemoveVisibility();
        }
      })
      .catch(function () { alert('Error de conexión.'); });
  }

  function updateRemoveVisibility() {
    var wrap = $('camRemoveWrap');
    if (!wrap) return;
    var hasProfile = $('avatarDiv').style.backgroundImage && $('avatarDiv').style.backgroundImage !== 'none';
    var hasBanner  = $('bannerDiv').style.backgroundImage.indexOf('url(') !== -1;
    wrap.style.display = (hasProfile || hasBanner) ? 'block' : 'none';
  }

  /* ════════════════════════════════════════════════════════════
     HOLD-TO-SAVE BUTTON
  ════════════════════════════════════════════════════════════ */
  var HOLD_MS = 2000; // 2 seconds

  function bindHoldButtons() {
    bindHold('saveBtnPerfil', 'saveWrapPerfil', function () { saveProfile(); });
    bindHold('saveBtnPass',   'saveWrapPass',   function () { savePassword(); });
  }

  function bindHold(btnId, wrapId, onComplete) {
    var btn = $(btnId);
    if (!btn) return;
    btn.addEventListener('pointerdown', function (e) { e.preventDefault(); startHold(btn, onComplete); });
    btn.addEventListener('pointerup',   function ()  { releaseHold(); });
    btn.addEventListener('pointerleave',function ()  { releaseHold(); });
    btn.addEventListener('pointercancel',function () { releaseHold(); });
  }

  function startHold(btn, onComplete) {
    releaseHold(true);
    var fill = btn.querySelector('[data-fill]');
    if (!fill) return;
    var len = parseFloat(fill.getAttribute('data-len')) || 94.2;
    var start = performance.now();
    var eng = { fill: fill, len: len, active: true, raf: null, p: 0 };

    function step(now) {
      if (!eng.active) return;
      eng.p = Math.min((now - start) / HOLD_MS, 1);
      fill.style.strokeDashoffset = String(len * (1 - eng.p));
      if (eng.p >= 1) {
        eng.active = false;
        holdEngine = null;
        onComplete();
        return;
      }
      eng.raf = requestAnimationFrame(step);
    }
    eng.raf = requestAnimationFrame(step);
    holdEngine = eng;
  }

  function releaseHold(silent) {
    var eng = holdEngine;
    if (!eng) return;
    if (eng.raf) cancelAnimationFrame(eng.raf);
    eng.active = false;
    holdEngine = null;
    if (silent) return;

    // Animate back
    var fill = eng.fill, len = eng.len, p = eng.p;
    var t0 = performance.now(), dur = Math.max(160, p * 420);
    function back(now) {
      var k = Math.min((now - t0) / dur, 1);
      fill.style.strokeDashoffset = String(len * (1 - p * (1 - k)));
      if (k < 1) requestAnimationFrame(back);
    }
    requestAnimationFrame(back);
  }

  /* ════════════════════════════════════════════════════════════
     SAVE PROFILE (AJAX)
  ════════════════════════════════════════════════════════════ */
  var FIELD_ERR_MAP = {
    nombres: 'nombresErr', apellidos: 'apellidosErr', email: 'emailErr',
    telefono: 'telefonoErr', fecha: 'fechaErr', nacionalidad: 'nacErr',
    estado: 'estadoErr', ciudad: 'ciudadErr', divisa: 'divisaErr'
  };

  // Validación cliente por campo. Devuelve true si todo es válido.
  function validateProfile() {
    var ok = true;
    var nameRe = /^[\p{L}][\p{L}\s'\-]{0,59}$/u;

    var nombres = (($('inputNombres') || {}).value || '').trim();
    if (!nameRe.test(nombres)) { showErr('nombresErr', 'Ingresa un nombre válido (solo letras).'); markInvalid('inputNombres', true); ok = false; }
    else { clearErr('nombresErr'); markInvalid('inputNombres', false); }

    var apellidos = (($('inputApellidos') || {}).value || '').trim();
    if (!nameRe.test(apellidos)) { showErr('apellidosErr', 'Ingresa apellidos válidos (solo letras).'); markInvalid('inputApellidos', true); ok = false; }
    else { clearErr('apellidosErr'); markInvalid('inputApellidos', false); }

    var email = (($('inputEmail') || {}).value || '').trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showErr('emailErr', 'Introduce un correo con formato válido.'); markInvalid('inputEmail', true); ok = false; }
    else { clearErr('emailErr'); markInvalid('inputEmail', false); }

    var digits = (($('inputTelefono') || {}).value || '').replace(/\D+/g, '');
    if (!(digits.length === 10 || (digits.length === 12 && digits.slice(0, 2) === '52'))) {
      showErr('telefonoErr', 'Teléfono inválido: 10 dígitos (o 12 con lada 52).'); markInvalid('inputTelefono', true); ok = false;
    } else { clearErr('telefonoErr'); markInvalid('inputTelefono', false); }

    var iso = ($('birthISO') || {}).value || '';
    var fechaOk = /^\d{4}-\d{2}-\d{2}$/.test(iso);
    if (fechaOk) {
      var p = iso.split('-'); var bd = new Date(+p[0], +p[1] - 1, +p[2]); bd.setHours(0, 0, 0, 0);
      if (bd > todayMidnight() || bd < minBirthDate()) fechaOk = false;
    }
    if (!fechaOk) { showErr('fechaErr', 'Elige una fecha entre hoy y hace 100 años.'); ok = false; }
    else { clearErr('fechaErr'); }

    if (!(($('inputNacionalidad') || {}).value)) { showErr('nacErr', 'Selecciona tu nacionalidad.'); markInvalid('inputNacionalidad', true); ok = false; }
    else { clearErr('nacErr'); markInvalid('inputNacionalidad', false); }
    if (!(($('inputEstado') || {}).value)) { showErr('estadoErr', 'Selecciona tu estado.'); markInvalid('inputEstado', true); ok = false; }
    else { clearErr('estadoErr'); markInvalid('inputEstado', false); }
    if (!(($('inputCiudad') || {}).value)) { showErr('ciudadErr', 'Selecciona tu ciudad.'); markInvalid('inputCiudad', true); ok = false; }
    else { clearErr('ciudadErr'); markInvalid('inputCiudad', false); }

    return ok;
  }

  function saveProfile() {
    if (!validateProfile()) { resetSave('saveWrapPerfil'); return; }

    var email = ($('inputEmail') || {}).value || '';
    var fd = new FormData();
    fd.append('action',           'save_profile');
    fd.append('nombres',          ($('inputNombres')  || {}).value || '');
    fd.append('apellidos',        ($('inputApellidos') || {}).value || '');
    fd.append('email',            email);
    fd.append('genero',           ($('inputGenero')    || {}).value || '');
    fd.append('telefono',         ($('inputTelefono')  || {}).value || '');
    fd.append('fecha_nacimiento', ($('birthISO')       || {}).value || '');
    fd.append('nacionalidad',     ($('inputNacionalidad') || {}).value || '');
    fd.append('pais_iso',         ($('paisIso')        || {}).value || '');
    fd.append('estado',           ($('inputEstado')    || {}).value || '');
    fd.append('estado_iso',       ($('estadoIso')      || {}).value || '');
    fd.append('ciudad',           ($('inputCiudad')    || {}).value || '');
    fd.append('lenguaje',         ($('inputLenguaje')  || {}).value || '');
    fd.append('divisa',           ($('inputDivisa')    || {}).value || '');

    fetch('profile.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) {
          showSaveSuccess('saveWrapPerfil');
          var fullName = (data.nombre || '').trim();
          var displayEl = $('displayName');
          if (displayEl) displayEl.textContent = fullName;
          var firstName = fullName.split(' ')[0] || 'Viajera';
          var topGreeting = document.querySelector('.topbar-greeting');
          if (topGreeting) topGreeting.textContent = '¡Hola, ' + firstName + '!';
          var sidebarName = $('sidebarName');
          if (sidebarName) sidebarName.textContent = fullName;
          var sidebarEmail = $('sidebarEmail');
          if (sidebarEmail) sidebarEmail.textContent = data.email || email;
          var initial = (firstName.charAt(0) || 'U').toUpperCase();
          document.querySelectorAll('.js-initial').forEach(function (el) { el.textContent = initial; });
          var locEl = $('profileLocation');
          if (locEl) {
            var loc = [data.ciudad || '', data.estado || ''].filter(function (x) { return x.trim(); }).join(', ');
            locEl.textContent = loc || 'Ubicación no definida';
          }
        } else {
          // Error específico de campo desde el servidor
          var errId = FIELD_ERR_MAP[data.field];
          if (errId) { showErr(errId, data.error || 'Dato inválido.'); if (data.field) markInvalid('input' + data.field.charAt(0).toUpperCase() + data.field.slice(1), true); }
          else { alert(data.error || 'Error al guardar.'); }
          resetSave('saveWrapPerfil');
        }
      })
      .catch(function () { alert('Error de conexión.'); resetSave('saveWrapPerfil'); });
  }

  /* ════════════════════════════════════════════════════════════
     SAVE PASSWORD (AJAX)
  ════════════════════════════════════════════════════════════ */
  function savePassword() {
    var actual  = ($('pwActual')  || {}).value || '';
    var nueva   = ($('pwNueva')   || {}).value || '';
    var repetir = ($('pwRepetir') || {}).value || '';
    var errEl   = $('pwErr');

    // Client-side validation
    if (!actual) {
      if (errEl) { errEl.textContent = 'Introduce tu contraseña anterior.'; errEl.style.display = 'block'; }
      resetSave('saveWrapPass'); return;
    }
    if (nueva.length < 6) {
      if (errEl) { errEl.textContent = 'La nueva contraseña debe tener al menos 6 caracteres.'; errEl.style.display = 'block'; }
      resetSave('saveWrapPass'); return;
    }
    if (nueva !== repetir) {
      if (errEl) { errEl.textContent = 'Las contraseñas no coinciden.'; errEl.style.display = 'block'; }
      $('pwRepetir').style.borderColor = '#fc8181';
      resetSave('saveWrapPass'); return;
    }

    var fd = new FormData();
    fd.append('action',          'change_password');
    fd.append('password_actual', actual);
    fd.append('password_nueva',  nueva);

    fetch('profile.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) {
          showSaveSuccess('saveWrapPass');
          $('pwActual').value = '';
          $('pwNueva').value = '';
          $('pwRepetir').value = '';
        } else {
          if (errEl) { errEl.textContent = data.error || 'Error al cambiar la contraseña.'; errEl.style.display = 'block'; }
          resetSave('saveWrapPass');
        }
      })
      .catch(function () { alert('Error de conexión.'); resetSave('saveWrapPass'); });
  }

  /* ════════════════════════════════════════════════════════════
     SAVE STATE (success/idle)
  ════════════════════════════════════════════════════════════ */
  function showSaveSuccess(wrapId) {
    var wrap = $(wrapId);
    if (!wrap) return;
    var idle    = wrap.querySelector('.save-idle');
    var success = wrap.querySelector('.save-success');
    if (idle)    idle.style.display    = 'none';
    if (success) success.style.display = 'inline-flex';
    setTimeout(function () { resetSave(wrapId); }, 1700);
  }

  function resetSave(wrapId) {
    var wrap = $(wrapId);
    if (!wrap) return;
    var idle    = wrap.querySelector('.save-idle');
    var success = wrap.querySelector('.save-success');
    if (idle)    idle.style.display    = 'flex';
    if (success) success.style.display = 'none';
    // Reset ring
    var fill = wrap.querySelector('[data-fill]');
    if (fill) {
      var len = parseFloat(fill.getAttribute('data-len')) || 94.2;
      fill.style.strokeDashoffset = String(len);
    }
  }

  function clearErrors() {
    var emailErr = $('emailErr');
    var pwErr    = $('pwErr');
    if (emailErr) { emailErr.textContent = ''; emailErr.style.display = 'none'; }
    if (pwErr)    { pwErr.textContent = '';    pwErr.style.display = 'none'; }
    var emailInp = $('inputEmail');
    if (emailInp) emailInp.style.borderColor = 'transparent';
    var repInp = $('pwRepetir');
    if (repInp) repInp.style.borderColor = '#e2e6ea';
  }

  /* ════════════════════════════════════════════════════════════
     OUTSIDE CLICKS (dismiss popups)
  ════════════════════════════════════════════════════════════ */
  function bindOutsideClicks() {
    document.addEventListener('pointerdown', function (e) {
      if (camOpen && !e.target.closest('[data-cam]')) {
        camOpen = false;
        var m = $('camMenu');
        if (m) m.style.display = 'none';
      }
      if (calOpen && !e.target.closest('[data-cal]')) {
        calOpen = false;
        renderCalendar();
      }
    }, true);
  }

  /* ── Boot ────────────────────────────────────────────────── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
