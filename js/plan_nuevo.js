/* ============================================================
   js/plan_nuevo.js — Pantalla «nuevo viaje» | Ruta Nómada

   Acompaña a includes/plan_nuevo.php. Recoge destino, fechas,
   privacidad e invitados, llama a api/plan_create.php y entra al
   itinerario real (plan.php?id=N).

   El destino se autocompleta con Google Places de verdad: el
   planificador viejo traía una lista de doce ciudades escrita a
   mano, así que sólo se podía viajar a esas doce.
============================================================ */
(function () {
  "use strict";

  var $ = function (id) { return document.getElementById(id); };
  function pad(n) { return (n < 10 ? "0" : "") + n; }
  function iso(d) { return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate()); }
  function parts(s) { var d = new Date(s + "T12:00:00"); return { m: d.getMonth(), d: d.getDate() }; }

  var MESES = ["enero", "febrero", "marzo", "abril", "mayo", "junio",
               "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
  var HOY = iso(new Date());

  /* ── Estado ─────────────────────────────────────────────── */
  var S = {
    dest: "", destPid: null, destLat: null, destLng: null,
    sug: [], sugOpen: false, sugSel: -1, sugCargando: false,
    start: null, end: null,
    calOpen: false, calY: new Date().getFullYear(), calM: new Date().getMonth(),
    privacy: "amigos", privOpen: false,
    invOpen: false, invites: [],
    creando: false, error: ""
  };
  var PRIV_TXT = { amigos: "Amigos", solo: "Solo yo", publico: "Público" };

  /* ── Google Places ──────────────────────────────────────── */
  var acSvc = null, acTok = null, plSvc = null, mapsListo = false;
  if (window.pnMapsReady) {
    window.pnMapsReady.then(function () {
      try {
        acSvc = new google.maps.places.AutocompleteService();
        acTok = new google.maps.places.AutocompleteSessionToken();
        plSvc = new google.maps.places.PlacesService(document.createElement("div"));
        mapsListo = true;
        if (S.dest.trim()) buscarDestino();
      } catch (e) { mapsListo = false; }
    });
  }

  var _acT;
  function buscarDestino() {
    clearTimeout(_acT);
    var q = S.dest.trim();
    if (!mapsListo || q.length < 2) { S.sug = []; S.sugCargando = false; render(); return; }
    S.sugCargando = S.sug.length === 0;
    _acT = setTimeout(function () {
      var pedido = q;
      acSvc.getPlacePredictions(
        { input: q, types: ["(cities)"], sessionToken: acTok },
        function (res, st) {
          // Llegó tarde: el usuario ya escribió otra cosa.
          if (pedido !== S.dest.trim()) return;
          S.sugCargando = false;
          var ok = st === google.maps.places.PlacesServiceStatus.OK;
          S.sug = (ok && res ? res : []).slice(0, 5).map(function (p) {
            return {
              pid: p.place_id,
              nombre: p.structured_formatting ? p.structured_formatting.main_text : p.description,
              zona: p.structured_formatting ? (p.structured_formatting.secondary_text || "") : ""
            };
          });
          S.sugSel = -1;
          render();
        }
      );
    }, 220);
  }

  // Al elegir una sugerencia pedimos las coordenadas: así el mapa del
  // plan abre ya centrado en el destino en vez de geocodificar después.
  function elegirDestino(s) {
    S.dest = s.nombre + (s.zona ? ", " + s.zona : "");
    S.destPid = s.pid;
    S.destLat = S.destLng = null;
    S.sug = []; S.sugOpen = false; S.sugSel = -1;
    $("pnDest").value = S.dest;
    render();
    if (!plSvc) return;
    plSvc.getDetails({ placeId: s.pid, fields: ["geometry"], sessionToken: acTok }, function (d, st) {
      // La petición de detalles cierra la sesión de facturación: token nuevo.
      try { acTok = new google.maps.places.AutocompleteSessionToken(); } catch (e) { }
      if (st === google.maps.places.PlacesServiceStatus.OK && d && d.geometry && d.geometry.location) {
        S.destLat = d.geometry.location.lat();
        S.destLng = d.geometry.location.lng();
      }
    });
  }

  /* ── Calendario ─────────────────────────────────────────── */
  function calClick(f) {
    if (f < HOY) return;
    if (!S.start || S.end) { S.start = f; S.end = null; }
    else if (f < S.start) { S.start = f; }
    else { S.end = f; }
    render();
  }

  function renderCal() {
    var wrap = $("pnCalWrap");
    wrap.hidden = !S.calOpen;
    if (!S.calOpen) return;
    for (var mi = 0; mi < 2; mi++) {
      var m = S.calM + mi, y = S.calY;
      while (m > 11) { m -= 12; y++; }
      $("pnCalTitle" + mi).textContent = MESES[m] + " " + y;
      var lead = (new Date(y, m, 1).getDay() + 6) % 7;
      var dim = new Date(y, m + 1, 0).getDate();
      var h = ["lu", "ma", "mi", "ju", "vi", "sá", "do"]
        .map(function (d) { return '<span class="dow">' + d + "</span>"; }).join("");
      for (var i = 0; i < lead; i++) h += '<button class="empty" type="button" tabindex="-1"></button>';
      for (var d = 1; d <= dim; d++) {
        var f = y + "-" + pad(m + 1) + "-" + pad(d);
        var cls = f < HOY ? "past"
          : (f === S.start || f === S.end) ? "selected"
          : (S.start && S.end && f > S.start && f < S.end) ? "in-range" : "";
        h += '<button type="button" class="' + cls + '" data-f="' + f + '"' +
             (f < HOY ? " disabled" : "") + ">" + d + "</button>";
      }
      $("pnCalGrid" + mi).innerHTML = h;
    }
  }

  /* ── Render ─────────────────────────────────────────────── */
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }

  var ICO_PIN = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2b5760" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';

  function render() {
    // Sugerencias del destino
    var sug = $("pnDestSug"), inp = $("pnDest");
    var verSug = S.sugOpen && S.dest.trim().length >= 2 && (S.sug.length > 0 || S.sugCargando);
    if (verSug) {
      sug.innerHTML = S.sug.length
        ? S.sug.map(function (s, i) {
            return '<div class="cp-ac-row' + (i === S.sugSel ? " on" : "") + '" role="option"' +
              ' aria-selected="' + (i === S.sugSel) + '" data-i="' + i + '">' +
              '<span class="cp-ac-icon">' + ICO_PIN + "</span>" +
              '<span class="cp-ac-text"><span class="cp-ac-name">' + esc(s.nombre) + "</span>" +
              '<span class="cp-ac-meta">' + esc(s.zona) + "</span></span></div>";
          }).join("")
        : '<div class="cp-ac-row" aria-disabled="true"><span class="cp-ac-text">' +
          '<span class="cp-ac-meta">Buscando…</span></span></div>';
      sug.hidden = false;
    } else {
      sug.hidden = true;
    }
    inp.setAttribute("aria-expanded", String(!sug.hidden));
    $("pnDestCard").classList.toggle("focus", document.activeElement === inp);

    // Fechas
    renderCal();
    var sl = $("pnStartLbl"), el = $("pnEndLbl");
    if (S.start) { var a = parts(S.start); sl.textContent = a.d + " " + MESES[a.m].substr(0, 3); }
    else { sl.textContent = "Fecha de inicio"; }
    if (S.end) { var b = parts(S.end); el.textContent = b.d + " " + MESES[b.m].substr(0, 3); }
    else { el.textContent = "Fecha final"; }
    $("pnStartBtn").classList.toggle("has-date", !!S.start);
    $("pnEndBtn").classList.toggle("has-date", !!S.end);

    // Privacidad
    $("pnPrivLabel").textContent = PRIV_TXT[S.privacy];
    $("pnPrivDrop").hidden = !S.privOpen;
    $("pnPrivBtn").setAttribute("aria-expanded", String(S.privOpen));
    Array.prototype.forEach.call($("pnPrivDrop").children, function (b) {
      b.classList.toggle("active", b.getAttribute("data-priv") === S.privacy);
    });

    // Invitados
    $("pnInvRow").hidden = !S.invOpen;
    $("pnInvToggle").setAttribute("aria-expanded", String(S.invOpen));
    $("pnInvPills").innerHTML = S.invites.map(function (e, i) {
      return '<span class="cp-pill">' + esc(e) +
        '<button type="button" data-rm="' + i + '" aria-label="Quitar a ' + esc(e) + '">✕</button></span>';
    }).join("");

    // CTA
    var go = $("pnGo");
    go.disabled = !S.dest.trim() || S.creando;
    go.textContent = S.creando ? "Creando tu viaje…" : "Comienza a planificar";
    var err = $("pnErr");
    err.hidden = !S.error;
    err.textContent = S.error;
  }

  /* ── Crear el plan ──────────────────────────────────────── */
  function crear() {
    var destino = S.dest.trim();
    if (!destino || S.creando) return;
    S.creando = true; S.error = ""; render();

    fetch("api/plan_create.php", {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-CSRF": window.PLAN_CSRF || "" },
      body: JSON.stringify({
        destino: destino,
        nombre: "Viaje a " + destino.split(",")[0].trim(),
        lat: S.destLat, lng: S.destLng,
        fecha_inicio: S.start, fecha_fin: S.end || S.start,
        privacidad: S.privacy,
        invitados: S.invites
      })
    }).then(function (r) {
      return r.json().catch(function () { throw new Error("El servidor no respondió como se esperaba."); });
    }).then(function (j) {
      if (!j.ok) throw new Error(j.error || "No se pudo crear el viaje.");
      window.location.href = "plan.php?id=" + j.id;
    }).catch(function (e) {
      S.creando = false;
      S.error = e.message || "No se pudo crear el viaje. Inténtalo de nuevo.";
      render();
      $("pnGo").focus();
    });
  }

  /* ── Eventos ────────────────────────────────────────────── */
  $("pnDest").addEventListener("input", function () {
    S.dest = this.value; S.destPid = null; S.destLat = S.destLng = null;
    S.sugOpen = true; S.error = "";
    buscarDestino(); render();
  });
  $("pnDest").addEventListener("focus", function () { S.sugOpen = true; render(); });
  $("pnDest").addEventListener("blur", function () {
    setTimeout(function () { S.sugOpen = false; render(); }, 150);
  });
  $("pnDest").addEventListener("keydown", function (e) {
    if ($("pnDestSug").hidden || !S.sug.length) {
      if (e.key === "Enter" && S.dest.trim()) { e.preventDefault(); crear(); }
      return;
    }
    if (e.key === "ArrowDown") { e.preventDefault(); S.sugSel = (S.sugSel + 1) % S.sug.length; render(); }
    else if (e.key === "ArrowUp") { e.preventDefault(); S.sugSel = (S.sugSel <= 0 ? S.sug.length : S.sugSel) - 1; render(); }
    else if (e.key === "Enter") {
      e.preventDefault();
      if (S.sugSel >= 0) elegirDestino(S.sug[S.sugSel]);
      else if (S.dest.trim()) crear();
    } else if (e.key === "Escape") { S.sugOpen = false; render(); }
  });
  $("pnDestSug").addEventListener("mousedown", function (e) {
    var row = e.target.closest("[data-i]");
    if (!row) return;
    e.preventDefault();                       // no quitar el foco antes de tiempo
    elegirDestino(S.sug[parseInt(row.getAttribute("data-i"), 10)]);
  });

  $("pnStartBtn").addEventListener("click", abrirCal);
  $("pnEndBtn").addEventListener("click", abrirCal);
  function abrirCal() {
    S.calOpen = !S.calOpen;
    if (S.calOpen && S.start) { var p = new Date(S.start + "T12:00:00"); S.calY = p.getFullYear(); S.calM = p.getMonth(); }
    render();
  }
  $("pnCalPrev").addEventListener("click", function () {
    S.calM--; if (S.calM < 0) { S.calM = 11; S.calY--; } render();
  });
  $("pnCalNext").addEventListener("click", function () {
    S.calM++; if (S.calM > 11) { S.calM = 0; S.calY++; } render();
  });
  $("pnCalClear").addEventListener("click", function () { S.start = S.end = null; render(); });
  $("pnCalDone").addEventListener("click", function () { S.calOpen = false; render(); });
  $("pnCalWrap").addEventListener("click", function (e) {
    var b = e.target.closest("button[data-f]");
    if (b) calClick(b.getAttribute("data-f"));
  });

  $("pnPrivBtn").addEventListener("click", function () { S.privOpen = !S.privOpen; render(); });
  $("pnPrivDrop").addEventListener("click", function (e) {
    var b = e.target.closest("[data-priv]");
    if (!b) return;
    S.privacy = b.getAttribute("data-priv"); S.privOpen = false; render();
  });

  $("pnInvToggle").addEventListener("click", function () {
    S.invOpen = !S.invOpen; render();
    if (S.invOpen) $("pnInvInput").focus();
  });
  $("pnInvInput").addEventListener("keydown", function (e) {
    if (e.key !== "Enter" && e.key !== ",") return;
    e.preventDefault();
    var v = this.value.trim().replace(/,$/, "");
    if (!v) return;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) { S.error = "«" + v + "» no parece un correo."; render(); return; }
    if (S.invites.indexOf(v) === -1) S.invites.push(v);
    this.value = ""; S.error = ""; render();
  });
  $("pnInvPills").addEventListener("click", function (e) {
    var b = e.target.closest("[data-rm]");
    if (!b) return;
    S.invites.splice(parseInt(b.getAttribute("data-rm"), 10), 1);
    render();
  });

  $("pnGo").addEventListener("click", crear);

  // Cerrar el calendario y el menú de privacidad al pulsar fuera / Escape
  document.addEventListener("mousedown", function (e) {
    if (S.calOpen && !e.target.closest("#pnDateCard")) { S.calOpen = false; render(); }
    if (S.privOpen && !e.target.closest("#pnPrivBtn, #pnPrivDrop")) { S.privOpen = false; render(); }
  });
  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    if (S.calOpen) { S.calOpen = false; render(); }
    else if (S.privOpen) { S.privOpen = false; render(); $("pnPrivBtn").focus(); }
  });

  render();
  $("pnDest").focus();
})();
