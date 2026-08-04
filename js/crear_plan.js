/* ============================================================
   crear_plan.js — Planificador de viajes estilo Wanderlog | Ruta Nómada
   SPA con 3 pantallas: Crear → Invitar → Planificador
   Persistencia en localStorage. Datos demo de San Diego.
============================================================ */
(function () {
  "use strict";

  /* ── Helpers ───────────────────────────────────────────── */
  var $ = function (id) { return document.getElementById(id); };
  var qs = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var qsa = function (sel, ctx) { return (ctx || document).querySelectorAll(sel); };
  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }
  function pad(n) { return (n < 10 ? "0" : "") + n; }
  function isoAdd(iso, n) { var d = new Date(iso + "T12:00:00"); d.setDate(d.getDate() + n); return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate()); }
  function parts(iso) { var d = new Date(iso + "T12:00:00"); return { y: d.getFullYear(), m: d.getMonth(), d: d.getDate(), dow: d.getDay() }; }

  /* ── Constants ─────────────────────────────────────────── */
  var MESES = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
  var DOWS = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
  var DOWS_S = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];
  var DAYCOLORS = ["#f0b429", "#0c8bd7", "#2e9e4f", "#d97706", "#8e44ad", "#e02424", "#2b5760"];
  var EXPCATS = [
    { v: "Alojamiento", c: "#8e44ad" }, { v: "Comida", c: "#d97706" }, { v: "Actividades", c: "#2e9e4f" },
    { v: "Transporte", c: "#0c8bd7" }, { v: "Compras", c: "#e02424" }, { v: "Otro", c: "#6b7a83" }
  ];
  var CAT_COLORS = {
    hacer: { bg: "#dbeafe", col: "#1e40af" }, rest: { bg: "#fef3c7", col: "#92400e" },
    hotel: { bg: "#ede9fe", col: "#5b21b6" }, custom: { bg: "#eef1f4", col: "#374151" }
  };
  var CAT_LABELS = { hacer: "Atracción", rest: "Restaurante", hotel: "Hotel", custom: "Personalizado" };
  var CUR_SYM = "MX$";

  /* ── Demo places (San Diego) ───────────────────────────── */
  var PLACES = [
    { id: "lajolla", name: "La Jolla Cove", cat: "hacer", rating: 4.8, rev: 18240, price: 0, dur: "2 h", lat: 32.8497, lng: -117.2729, meta: "Playa · Fauna marina", seed: "rn-lajolla" },
    { id: "torrey", name: "Torrey Pines State Reserve", cat: "hacer", rating: 4.8, rev: 9120, price: 300, dur: "3 h", lat: 32.9211, lng: -117.2543, meta: "Senderismo · Miradores", seed: "rn-torrey" },
    { id: "balboa", name: "Balboa Park", cat: "hacer", rating: 4.8, rev: 44300, price: 0, dur: "3 h", lat: 32.7312, lng: -117.1467, meta: "Parque urbano · Museos", seed: "rn-balboa" },
    { id: "zoo", name: "Zoológico de San Diego", cat: "hacer", rating: 4.7, rev: 61200, price: 1250, dur: "4 h", lat: 32.7353, lng: -117.1490, meta: "Zoológico · Familias", seed: "rn-zoo" },
    { id: "midway", name: "Museo USS Midway", cat: "hacer", rating: 4.8, rev: 32800, price: 620, dur: "2.5 h", lat: 32.7137, lng: -117.1751, meta: "Museo · Portaaviones", seed: "rn-midway" },
    { id: "gaslamp", name: "Gaslamp Quarter", cat: "hacer", rating: 4.5, rev: 12400, price: 0, dur: "2 h", lat: 32.7107, lng: -117.1600, meta: "Barrio histórico · Vida nocturna", seed: "rn-gaslamp" },
    { id: "seaport", name: "Seaport Village", cat: "hacer", rating: 4.6, rev: 21100, price: 0, dur: "1.5 h", lat: 32.7089, lng: -117.1693, meta: "Paseo costero · Tiendas", seed: "rn-seaport" },
    { id: "oldtown", name: "Old Town San Diego", cat: "hacer", rating: 4.6, rev: 28700, price: 0, dur: "2 h", lat: 32.7543, lng: -117.1963, meta: "Historia · Comida mexicana", seed: "rn-oldtown" },
    { id: "coronado", name: "Playa de Coronado", cat: "hacer", rating: 4.8, rev: 15900, price: 0, dur: "3 h", lat: 32.6816, lng: -117.1783, meta: "Playa · Atardeceres", seed: "rn-coronado" },
    { id: "cabrillo", name: "Monumento Nacional Cabrillo", cat: "hacer", rating: 4.7, rev: 8400, price: 400, dur: "2 h", lat: 32.6738, lng: -117.2413, meta: "Mirador · Faro", seed: "rn-cabrillo" },
    { id: "sunset", name: "Sunset Cliffs", cat: "hacer", rating: 4.8, rev: 7300, price: 0, dur: "1.5 h", lat: 32.7196, lng: -117.2559, meta: "Acantilados · Atardeceres", seed: "rn-sunset" },
    { id: "belmont", name: "Belmont Park", cat: "hacer", rating: 4.4, rev: 9800, price: 850, dur: "2.5 h", lat: 32.7699, lng: -117.2513, meta: "Parque de diversiones", seed: "rn-belmont" },
    { id: "hodads", name: "Hodad's Ocean Beach", cat: "rest", rating: 4.6, rev: 9200, price: 320, dur: "1 h", lat: 32.7486, lng: -117.2525, meta: "Hamburguesas · Casual", seed: "rn-hodads" },
    { id: "milpas", name: "Las Cuatro Milpas", cat: "rest", rating: 4.6, rev: 4100, price: 180, dur: "1 h", lat: 32.6990, lng: -117.1457, meta: "Mexicana · Tradicional", seed: "rn-milpas" },
    { id: "phils", name: "Phil's BBQ", cat: "rest", rating: 4.7, rev: 15600, price: 420, dur: "1 h", lat: 32.7460, lng: -117.2104, meta: "BBQ · Costillas", seed: "rn-phils" },
    { id: "crack", name: "The Crack Shack", cat: "rest", rating: 4.5, rev: 5200, price: 380, dur: "1 h", lat: 32.7180, lng: -117.1627, meta: "Pollo frito · Terraza", seed: "rn-crack" },
    { id: "oscars", name: "Oscar's Mexican Seafood", cat: "rest", rating: 4.7, rev: 6800, price: 250, dur: "1 h", lat: 32.7968, lng: -117.2535, meta: "Mariscos · Tacos", seed: "rn-oscars" },
    { id: "liberty", name: "Liberty Public Market", cat: "rest", rating: 4.6, rev: 7900, price: 300, dur: "1.5 h", lat: 32.7410, lng: -117.2120, meta: "Mercado gastronómico", seed: "rn-liberty" },
    { id: "hoteldel", name: "Hotel del Coronado", cat: "hotel", rating: 4.7, rev: 11900, price: 9800, dur: "", lat: 32.6810, lng: -117.1785, meta: "Hotel histórico · Frente al mar", seed: "rn-hoteldel" },
    { id: "pendry", name: "Pendry San Diego", cat: "hotel", rating: 4.7, rev: 2900, price: 7200, dur: "", lat: 32.7113, lng: -117.1592, meta: "Hotel de lujo · Gaslamp", seed: "rn-pendry" },
    { id: "catamaran", name: "Catamaran Resort Hotel", cat: "hotel", rating: 4.5, rev: 5600, price: 4600, dur: "", lat: 32.7810, lng: -117.2492, meta: "Resort · Mission Bay", seed: "rn-catamaran" }
  ];
  var CITIES = [
    { n: "San Diego", r: "California, EE. UU." },
    { n: "San Francisco", r: "California, EE. UU." },
    { n: "San José del Cabo", r: "Baja California Sur, México" },
    { n: "San Miguel de Allende", r: "Guanajuato, México" },
    { n: "Oaxaca de Juárez", r: "Oaxaca, México" },
    { n: "Ciudad de México", r: "CDMX, México" },
    { n: "Guadalajara", r: "Jalisco, México" },
    { n: "Cancún", r: "Quintana Roo, México" },
    { n: "Mérida", r: "Yucatán, México" },
    { n: "París", r: "Francia" },
    { n: "Tokio", r: "Japón" },
    { n: "Roma", r: "Italia" }
  ];
  var HERO_IMGS = [
    "https://picsum.photos/seed/rn-sd-hero-a/1200/400",
    "https://picsum.photos/seed/rn-sd-hero-b/1200/400",
    "https://picsum.photos/seed/rn-sd-hero-c/1200/400"
  ];

  /* ── State ─────────────────────────────────────────────── */
  var _uid = 1;
  function nu() { _uid++; return "u" + _uid; }

  var S = {
    screen: "create", // create | invite | planner
    dest: "", destFocus: false,
    calOpen: false, calY: 2026, calM: 6,
    start: null, end: null,
    privacy: "Amigos", privOpen: false,
    invRow: false, invInput: "", invites: [],
    tripName: "", nameEdit: false, heroIdx: 0,
    notas: "",
    saved: [], days: [],
    expenses: [], expFormOpen: false, expConcepto: "", expMonto: "", expCat: "Comida", expDia: "", expSort: "fecha",
    budget: null, budgetEdit: false, budgetTxt: "",
    shareOpen: false, copied: false, linkRole: "editar", shareInv: "",
    exploreOpen: false, exTab: "hacer", exQ: "",
    aiOpen: false, aiLog: [],
    mapDay: "all", mapFull: false, mapHintOff: false,
    hoverUid: null, infoUid: null,
    sidebarHidden: false, activeSec: "secExplorar",
    dayMenu: null, desgloseOpen: false,
    addFocus: null, addQ: {},
    drag: null, dragOver: null
  };

  /* ── Utility functions ─────────────────────────────────── */
  function place(pid) { for (var i = 0; i < PLACES.length; i++) { if (PLACES[i].id === pid) return PLACES[i]; } return null; }
  function money(mxn, dec) { return CUR_SYM + mxn.toLocaleString("es-MX", { minimumFractionDigits: dec || 0, maximumFractionDigits: dec || 0 }); }
  function itemName(it) { if (it.custom) return it.custom; var p = place(it.pid); return p ? p.name : "—"; }
  function priceLabel(p) { if (p.price === 0) return "Gratis"; return p.cat === "hotel" ? money(p.price, 0) + "/noche" : money(p.price, 0); }
  function dayColor(i) { return DAYCOLORS[i % DAYCOLORS.length]; }
  function dayTitle(i) {
    var d = S.days[i];
    if (d && d.iso) { var p = parts(d.iso); return DOWS[p.dow] + ", " + p.d + " de " + MESES[p.m]; }
    return "Día " + (i + 1);
  }
  function sideDayLabel(i) {
    var d = S.days[i];
    if (d && d.iso) { var p = parts(d.iso); return DOWS_S[p.dow] + ". " + p.d + "/" + (p.m + 1); }
    return "Día " + (i + 1);
  }
  function tripDatesLabel() {
    if (!S.start) return "Sin fechas";
    var a = parts(S.start);
    var lbl = a.d + "/" + (a.m + 1);
    if (S.end) { var b = parts(S.end); lbl += " - " + b.d + "/" + (b.m + 1); }
    return lbl;
  }
  function usedPids() {
    var set = {};
    S.saved.forEach(function (it) { if (it.pid) set[it.pid] = 1; });
    S.days.forEach(function (d) { d.items.forEach(function (it) { if (it.pid) set[it.pid] = 1; }); });
    return set;
  }
  function placeSeed(p) { return "https://picsum.photos/seed/" + p.seed + "/"; }
  function expCatColor(cat) { for (var i = 0; i < EXPCATS.length; i++) { if (EXPCATS[i].v === cat) return EXPCATS[i].c; } return "#6b7a83"; }

  /* ── Days generation ───────────────────────────────────── */
  function genDays(start, end, oldDays) {
    var isos = null;
    if (start && end) { isos = []; var c = start, g = 0; while (c <= end && g < 21) { isos.push(c); c = isoAdd(c, 1); g++; } }
    else if (start) { isos = [start]; }
    var n = isos ? isos.length : 3;
    var days = [], extra = [];
    for (var i = 0; i < n; i++) {
      var old = oldDays && oldDays[i];
      days.push({ iso: isos ? isos[i] : null, sub: old ? old.sub : "", note: old ? old.note : null, open: true, items: old ? old.items : [] });
    }
    if (oldDays) { for (var j = n; j < oldDays.length; j++) { oldDays[j].items.forEach(function (it) { extra.push(Object.assign({}, it, { hora: "" })); }); } }
    return { days: days, extra: extra };
  }

  /* ── Items CRUD ─────────────────────────────────────────── */
  function addItem(target, pid, customName) {
    var it = { uid: nu(), pid: pid || null, custom: customName || null, hora: "", dur: "", precio: "", nota: "" };
    if (pid) { var p = place(pid); it.dur = p.dur || ""; it.precio = p.price ? String(p.price) : ""; }
    else { it.dur = "1 h"; }
    if (target === "saved") { S.saved.push(it); }
    else { S.days[target].items.push(it); }
    return it;
  }
  function removeItem(key, uid) {
    if (key === "saved") { S.saved = S.saved.filter(function (i) { return i.uid !== uid; }); }
    else { S.days[key].items = S.days[key].items.filter(function (i) { return i.uid !== uid; }); }
  }
  function moveItem(fromKey, uid, toKey, index) {
    var src = fromKey === "saved" ? S.saved : S.days[fromKey].items;
    var idx = -1;
    for (var i = 0; i < src.length; i++) { if (src[i].uid === uid) { idx = i; break; } }
    if (idx < 0) { S.drag = null; S.dragOver = null; return; }
    var it = src.splice(idx, 1)[0];
    var dst = toKey === "saved" ? S.saved : S.days[toKey].items;
    var ti = (index === undefined || index === null || index < 0) ? dst.length : index;
    if (src === dst && idx < ti) ti--;
    if (ti > dst.length) ti = dst.length;
    if (toKey === "saved") it.hora = "";
    dst.splice(ti, 0, it);
    S.drag = null;
    S.dragOver = null;
  }

  /* ── AI ─────────────────────────────────────────────────── */
  function fillDay(idx) {
    var used = usedPids();
    var picks = PLACES.filter(function (p) { return p.cat === "hacer" && !used[p.id]; })
      .sort(function (a, b) { return (b.rating - a.rating) || (b.rev - a.rev); }).slice(0, 3);
    if (!picks.length) return [];
    var horas = ["10:00", "13:30", "17:00"];
    picks.forEach(function (p, j) {
      S.days[idx].items.push({ uid: nu(), pid: p.id, custom: null, hora: horas[j] || "", dur: p.dur, precio: p.price ? String(p.price) : "", nota: "" });
    });
    return picks;
  }
  function aiRun(kind) {
    if (kind === "fill") {
      var idx = 0, min = Infinity;
      S.days.forEach(function (d, i) { if (d.items.length < min) { min = d.items.length; idx = i; } });
      var picks = fillDay(idx);
      S.aiLog.push({ who: "u", t: "Rellena el día más vacío" });
      S.aiLog.push({ who: "a", t: picks.length ? "Listo: añadí " + picks.map(function (p) { return p.name; }).join(", ") + " al " + dayTitle(idx).toLowerCase() + ", con horarios sugeridos." : "Ya usaste todas las atracciones de mi lista para San Diego." });
    } else if (kind === "rest") {
      var used = usedPids();
      var picks2 = PLACES.filter(function (p) { return p.cat === "rest" && !used[p.id]; }).sort(function (a, b) { return b.rating - a.rating; }).slice(0, 3);
      picks2.forEach(function (p) { addItem("saved", p.id, null); });
      S.aiLog.push({ who: "u", t: "Sugiéreme restaurantes" });
      S.aiLog.push({ who: "a", t: picks2.length ? "Guardé " + picks2.length + " restaurantes muy bien calificados en «Lugares para visitar»: " + picks2.map(function (p) { return p.name; }).join(", ") + "." : "Ya tienes todos mis restaurantes recomendados en el plan." });
    } else {
      var used2 = usedPids();
      var picks3 = PLACES.filter(function (p) { return p.cat === "hacer" && !used2[p.id]; }).sort(function (a, b) { return b.rev - a.rev; }).slice(0, 4);
      picks3.forEach(function (p) { addItem("saved", p.id, null); });
      S.aiLog.push({ who: "u", t: "Añade los imperdibles" });
      S.aiLog.push({ who: "a", t: picks3.length ? "Añadí " + picks3.length + " imperdibles de San Diego a «Lugares para visitar». Arrástralos a un día del itinerario." : "Ya tienes todos los imperdibles en tu plan." });
    }
  }

  /* ── Budget calc ────────────────────────────────────────── */
  function budgetTotal() {
    var total = 0;
    S.days.forEach(function (d) { d.items.forEach(function (it) { total += parseFloat(it.precio) || 0; }); });
    S.saved.forEach(function (it) { total += parseFloat(it.precio) || 0; });
    S.expenses.forEach(function (e) { total += parseFloat(e.monto) || 0; });
    return total;
  }
  function budgetDesglose() {
    var cats = {};
    EXPCATS.forEach(function (c) { cats[c.v] = 0; });
    S.days.forEach(function (d) {
      d.items.forEach(function (it) {
        var p = place(it.pid);
        var cat = p ? (p.cat === "hotel" ? "Alojamiento" : p.cat === "rest" ? "Comida" : "Actividades") : "Otro";
        cats[cat] = (cats[cat] || 0) + (parseFloat(it.precio) || 0);
      });
    });
    S.saved.forEach(function (it) {
      var p = place(it.pid);
      var cat = p ? (p.cat === "hotel" ? "Alojamiento" : p.cat === "rest" ? "Comida" : "Actividades") : "Otro";
      cats[cat] = (cats[cat] || 0) + (parseFloat(it.precio) || 0);
    });
    S.expenses.forEach(function (e) { cats[e.cat] = (cats[e.cat] || 0) + (parseFloat(e.monto) || 0); });
    var total = 0;
    for (var k in cats) total += cats[k];
    return EXPCATS.map(function (c) {
      return { label: c.v, amt: cats[c.v] || 0, color: c.c, pct: total > 0 ? Math.round((cats[c.v] || 0) / total * 100) : 0 };
    }).filter(function (r) { return r.amt > 0; });
  }

  /* ── Autocomplete helper ───────────────────────────────── */
  function acList(key) {
    var q = (S.addQ[key] || "").trim();
    if (!q) return [];
    var used = usedPids();
    var t = q.toLowerCase();
    var list = PLACES.filter(function (p) { return !used[p.id] && p.name.toLowerCase().indexOf(t) !== -1; }).slice(0, 5);
    list = list.map(function (p) {
      return { pid: p.id, name: p.name, meta: p.meta, img: placeSeed(p) + "120/120", isNew: false };
    });
    list.push({ pid: null, name: "Crear «" + q + "»", meta: "Añadir como lugar personalizado", img: "", isNew: true, customName: q });
    return list;
  }

  /* ── Persistence ────────────────────────────────────────── */
  var _pt;
  function persist() {
    var keep = {
      _uid: _uid, screen: S.screen, dest: S.dest, start: S.start, end: S.end, privacy: S.privacy,
      invites: S.invites, tripName: S.tripName, heroIdx: S.heroIdx, notas: S.notas, saved: S.saved,
      days: S.days, expenses: S.expenses, budget: S.budget, linkRole: S.linkRole, mapDay: S.mapDay,
      sidebarHidden: S.sidebarHidden, mapHintOff: S.mapHintOff, aiLog: S.aiLog
    };
    try { localStorage.setItem("rn_crear_plan_v1", JSON.stringify(keep)); } catch (e) { }
  }
  function restore() {
    try {
      var raw = localStorage.getItem("rn_crear_plan_v1");
      if (raw) {
        var d = JSON.parse(raw);
        if (d && typeof d === "object") {
          _uid = d._uid || 500;
          delete d._uid;
          for (var k in d) { if (d.hasOwnProperty(k) && S.hasOwnProperty(k)) S[k] = d[k]; }
        }
      }
    } catch (e) { }
  }

  /* ── SVG icons ──────────────────────────────────────────── */
  var ICO = {
    pin: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2b5760" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    plus: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
    chevDown: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>',
    chevLeft: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>',
    chevRight: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16262e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
    cal: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2b5760" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    edit: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>',
    share: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>',
    addUser: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>',
    users: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    star: '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6z"/></svg>',
    search: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>',
    print: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>',
    grip: '<svg width="13" height="18" viewBox="0 0 12 18" fill="#b9c6cf"><circle cx="3" cy="3" r="1.5"/><circle cx="9" cy="3" r="1.5"/><circle cx="3" cy="9" r="1.5"/><circle cx="9" cy="9" r="1.5"/><circle cx="3" cy="15" r="1.5"/><circle cx="9" cy="15" r="1.5"/></svg>',
    clock: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#5b6b74" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    hourglass: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#5b6b74" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 22h14M5 2h14"/><path d="M7 22v-4.2c0-.6.2-1.1.6-1.4L12 12 7.6 7.6C7.2 7.3 7 6.8 7 6.2V2"/><path d="M17 22v-4.2c0-.6-.2-1.1-.6-1.4L12 12l4.4-4.4c.4-.3.6-.8.6-1.4V2"/></svg>',
    aiStar: '<svg width="15" height="15" viewBox="0 0 24 24" fill="#ffffff"><path d="M12 2l2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6z"/><path d="M19 14l.9 2.6 2.6.9-2.6.9L19 21l-.9-2.6-2.6-.9 2.6-.9z"/></svg>',
    noteEdit: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#b98a00" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4z"/></svg>',
    hide: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"/></svg>',
    show: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 17l5-5-5-5M13 17l5-5-5-5"/></svg>',
    close: "✕",
    dots: "⋯"
  };

  /* ═══════════════════════════════════════════════════════════
     RENDER
  ═══════════════════════════════════════════════════════════ */
  function render() {
    renderScreen("cpCreate", S.screen === "create");
    renderScreen("cpInvite", S.screen === "invite");
    renderScreen("cpPlanner", S.screen === "planner");

    if (S.screen === "create") renderCreateScreen();
    if (S.screen === "invite") renderInviteScreen();
    if (S.screen === "planner") renderPlanner();

    renderModals();
    schedulePersist();
  }
  function renderScreen(id, active) {
    var el = $(id);
    if (el) { el.classList.toggle("active", active); }
  }
  function schedulePersist() { clearTimeout(_pt); _pt = setTimeout(persist, 250); }

  /* ── SCREEN 1: CREATE ──────────────────────────────────── */
  function renderCreateScreen() {
    // Destination suggestions
    var destSug = $("cpDestSug");
    if (S.destFocus && S.dest.trim().length > 0) {
      var q = S.dest.toLowerCase();
      var matches = CITIES.filter(function (c) { return c.n.toLowerCase().indexOf(q) !== -1; }).slice(0, 5);
      if (matches.length) {
        destSug.innerHTML = matches.map(function (c) {
          return '<div class="cp-ac-row" data-city="' + esc(c.n) + '"><span class="cp-ac-icon">' + ICO.pin + '</span>' +
            '<span class="cp-ac-text"><span class="cp-ac-name">' + esc(c.n) + '</span><span class="cp-ac-meta">' + esc(c.r) + '</span></span></div>';
        }).join("");
        destSug.hidden = false;
      } else { destSug.hidden = true; }
    } else { destSug.hidden = true; }

    // Calendar
    renderCalendar();

    // Date display
    var startLbl = $("cpStartLbl");
    var endLbl = $("cpEndLbl");
    if (startLbl) {
      if (S.start) { var p = parts(S.start); startLbl.textContent = p.d + " " + MESES[p.m].substr(0, 3); startLbl.parentElement.classList.add("has-date"); }
      else { startLbl.textContent = "Fecha de inicio"; startLbl.parentElement.classList.remove("has-date"); }
    }
    if (endLbl) {
      if (S.end) { var p2 = parts(S.end); endLbl.textContent = p2.d + " " + MESES[p2.m].substr(0, 3); endLbl.parentElement.classList.add("has-date"); }
      else { endLbl.textContent = "Fecha final"; endLbl.parentElement.classList.remove("has-date"); }
    }

    // Privacy
    $("cpPrivLabel").textContent = S.privacy;
    var privDrop = $("cpPrivDrop");
    privDrop.hidden = !S.privOpen;

    // Invite row
    $("cpInviteRow").hidden = !S.invRow;
    renderInvitePills($("cpInvitePills"));

    // CTA
    var cta = $("cpStartBtn");
    cta.disabled = !S.dest.trim();
  }

  /* ── CALENDAR ──────────────────────────────────────────── */
  function renderCalendarInstance(wrapId, titlePrefix, gridPrefix, suffix) {
    suffix = suffix || "";
    var wrap = $(wrapId);
    if (!wrap) return;
    wrap.hidden = !S.calOpen;
    if (!S.calOpen) return;

    for (var mi = 0; mi < 2; mi++) {
      var m = S.calM + mi, y = S.calY;
      while (m > 11) { m -= 12; y++; }
      var titleEl = $(titlePrefix + mi + suffix);
      var gridEl = $(gridPrefix + mi + suffix);
      if (!titleEl || !gridEl) continue;
      titleEl.textContent = MESES[m] + " " + y;
      var lead = (new Date(y, m, 1).getDay() + 6) % 7;
      var dim = new Date(y, m + 1, 0).getDate();
      var html = '<span class="dow">lu</span><span class="dow">ma</span><span class="dow">mi</span><span class="dow">ju</span><span class="dow">vi</span><span class="dow">sá</span><span class="dow">do</span>';
      for (var i = 0; i < lead; i++) html += '<button class="empty"></button>';
      for (var d = 1; d <= dim; d++) {
        var iso = y + "-" + pad(m + 1) + "-" + pad(d);
        var sel = iso === S.start || iso === S.end;
        var inR = S.start && S.end && iso > S.start && iso < S.end;
        var cls = sel ? "selected" : (inR ? "in-range" : "");
        html += '<button class="' + cls + '" data-iso="' + iso + '">' + d + '</button>';
      }
      gridEl.innerHTML = html;
    }
  }
  function renderCalendar() {
    // Create-screen calendar
    renderCalendarInstance("cpCalWrap", "cpCalTitle", "cpCalGrid", "");
    // Planner-screen calendar
    renderCalendarInstance("cpCalWrap2", "cpCalTitle", "cpCalGrid", "b");
  }

  /* ── INVITE PILLS ──────────────────────────────────────── */
  function renderInvitePills(container) {
    if (!container) return;
    container.innerHTML = S.invites.map(function (email, i) {
      return '<span class="cp-pill">' + esc(email) + '<button data-inv-rm="' + i + '">✕</button></span>';
    }).join("");
  }

  /* ── SCREEN 2: INVITE ──────────────────────────────────── */
  function renderInviteScreen() {
    $("cpInviteTitle").textContent = "Invita a compañeros de viaje a tu viaje a " + (S.dest || "San Diego");
    renderInvitePills($("cpInvitePills2"));
    var cta = $("cpInviteGoBtn");
    cta.disabled = S.invites.length === 0;
  }

  /* ── SCREEN 3: PLANNER ─────────────────────────────────── */
  function renderPlanner() {
    renderSidebar();
    renderHero();
    renderSummary();
    renderExplore();
    renderNotes();
    renderSaved();
    renderItinerary();
    renderBudget();
    renderMapPanel();

    // Sidebar visibility
    var sidebar = $("cpSidebar");
    var sidebarFab = $("cpSidebarFab");
    var main = $("cpMain");
    if (sidebar) sidebar.classList.toggle("hidden", S.sidebarHidden);
    if (sidebarFab) sidebarFab.hidden = !S.sidebarHidden;
    if (main) {
      main.classList.toggle("no-sidebar", S.sidebarHidden);
      main.classList.toggle("no-map", window.innerWidth <= 1080);
    }
  }

  /* ── Sidebar ────────────────────────────────────────────── */
  function renderSidebar() {
    // Day links
    var dayNav = $("cpSidebarDays");
    if (!dayNav) return;
    dayNav.innerHTML = S.days.map(function (d, i) {
      var active = S.activeSec === "secDia" + i;
      return '<button class="cp-sidebar-link' + (active ? " active" : "") + '" data-nav="secDia' + i + '">' + esc(sideDayLabel(i)) + '</button>';
    }).join("");

    // Active highlights
    qsa(".cp-sidebar-link[data-nav]").forEach(function (btn) {
      btn.classList.toggle("active", S.activeSec === btn.getAttribute("data-nav"));
    });
  }

  /* ── Hero ────────────────────────────────────────────────── */
  function renderHero() {
    var img = $("cpHeroImg");
    if (img) img.src = HERO_IMGS[S.heroIdx % HERO_IMGS.length];
  }

  /* ── Summary ────────────────────────────────────────────── */
  function renderSummary() {
    var nameEl = $("cpTripName");
    var nameInput = $("cpTripNameInput");
    var nameWrap = $("cpNameDisplay");
    var editWrap = $("cpNameEdit");
    if (nameWrap && editWrap) {
      nameWrap.hidden = S.nameEdit;
      editWrap.hidden = !S.nameEdit;
      if (nameEl) nameEl.textContent = S.tripName;
      if (nameInput && S.nameEdit) nameInput.value = S.tripName;
    }
    var dateChip = $("cpDateChipLabel");
    if (dateChip) dateChip.textContent = tripDatesLabel();

    // Avatars
    var avatarsEl = $("cpAvatars");
    if (avatarsEl) {
      var initials = [S.tripName ? S.tripName.charAt(0).toUpperCase() : "R"];
      S.invites.forEach(function (e) { initials.push(e.charAt(0).toUpperCase()); });
      avatarsEl.innerHTML = initials.map(function (init, i) {
        var bg = i === 0 ? "#f0b429" : DAYCOLORS[(i + 1) % DAYCOLORS.length];
        var col = i === 0 ? "#062738" : "#ffffff";
        return '<span class="cp-avatar" style="background:' + bg + ';color:' + col + '">' + esc(init) + '</span>';
      }).join("");
    }
  }

  /* ── Explore ────────────────────────────────────────────── */
  function renderExplore() {
    var wrap = $("cpExploreCards");
    if (!wrap) return;
    var cards = [
      { title: "Mejores atracciones en " + (S.dest || "San Diego"), img: "https://picsum.photos/seed/rn-explore-a/400/200" },
      { title: "Mejores restaurantes en " + (S.dest || "San Diego"), img: "https://picsum.photos/seed/rn-explore-r/400/200" },
      { title: "Buscar hoteles con precios transparentes", img: "https://picsum.photos/seed/rn-explore-h/400/200" }
    ];
    wrap.innerHTML = cards.map(function (c) {
      return '<div class="cp-explore-card" role="button" tabindex="0">' +
        '<img src="' + c.img + '" alt="" loading="lazy">' +
        '<div class="cp-explore-card__body"><p class="cp-explore-card__name">' + esc(c.title) + '</p>' +
        '<div class="cp-explore-card__source"><img src="img/logo.png" alt=""><span>Ruta Nómada</span></div></div></div>';
    }).join("");
  }

  /* ── Notes ──────────────────────────────────────────────── */
  function renderNotes() {
    var ta = $("cpNotasTA");
    if (ta && document.activeElement !== ta) ta.value = S.notas;
  }

  /* ── Saved places ──────────────────────────────────────── */
  function renderSaved() {
    var badge = $("cpSavedBadge");
    if (badge) badge.textContent = S.saved.length;

    var emptyEl = $("cpSavedEmpty");
    if (emptyEl) emptyEl.hidden = S.saved.length > 0;

    var list = $("cpSavedList");
    if (!list) return;

    var dayOpts = S.days.map(function (d, i) {
      return '<option value="' + i + '">' + esc(sideDayLabel(i)) + '</option>';
    }).join("");

    list.innerHTML = S.saved.map(function (it, idx) {
      var p = place(it.pid);
      var imgHtml = p ? '<img class="cp-place-thumb" src="' + placeSeed(p) + '120/120" alt="" loading="lazy">' :
        '<span class="cp-place-custom-icon">' + ICO.pin.replace(/#2b5760/g, "#6b7a83") + '</span>';
      return '<div class="cp-drag-gap' + (S.drag ? " dragging" : "") + (S.dragOver === "saved:" + idx ? " over" : "") + '" data-gap="saved:' + idx + '"></div>' +
        '<div class="cp-place-row' + (S.hoverUid === it.uid ? " hovered" : "") + '" draggable="true" data-uid="' + it.uid + '" data-from="saved">' +
        '<span class="cp-grip">' + ICO.grip + '</span>' + imgHtml +
        '<div class="cp-place-info"><p class="cp-place-name">' + esc(itemName(it)) + '</p>' +
        '<div class="cp-place-meta">' + (p ? '<span class="cp-rating">★ ' + p.rating.toFixed(1) + '</span>' : '') +
        '<span>' + esc(p ? p.meta : "Lugar personalizado") + '</span>' +
        (p ? '<span class="cp-price-tag">' + priceLabel(p) + '</span>' : '') + '</div></div>' +
        '<select class="cp-move-sel" data-move-uid="' + it.uid + '"><option value="">Añadir a…</option>' + dayOpts + '</select>' +
        '<button class="cp-remove-btn" data-rm-uid="' + it.uid + '" data-rm-from="saved">✕</button></div>';
    }).join("") + '<div class="cp-drag-gap' + (S.drag ? " dragging" : "") + (S.dragOver === "saved:" + S.saved.length ? " over" : "") + '" data-gap="saved:' + S.saved.length + '"></div>';

    // Saved autocomplete
    var acWrap = $("cpSavedAc");
    var qVal = S.addQ["saved"] || "";
    if (acWrap) {
      if (S.addFocus === "saved" && qVal.trim()) {
        var items = acList("saved");
        acWrap.innerHTML = items.map(function (a) {
          var imgTag = a.isNew ? '<span class="cp-ac-new-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#b98a00" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>' :
            '<img class="cp-ac-img" src="' + esc(a.img) + '" alt="" loading="lazy">';
          return '<div class="cp-ac-row" data-ac-key="saved" data-ac-pid="' + (a.pid || "") + '" data-ac-custom="' + esc(a.customName || "") + '">' +
            imgTag + '<span class="cp-ac-text"><span class="cp-ac-name">' + esc(a.name) + '</span><span class="cp-ac-meta">' + esc(a.meta) + '</span></span></div>';
        }).join("");
        acWrap.hidden = false;
      } else { acWrap.hidden = true; }
    }

    initSortable("cpSavedList", "saved");
  }

  /* ── Itinerary ──────────────────────────────────────────── */
  function renderItinerary() {
    var dateChip = $("cpItinDates");
    if (dateChip) dateChip.textContent = tripDatesLabel();

    var wrap = $("cpDays");
    if (!wrap) return;

    wrap.innerHTML = S.days.map(function (day, di) {
      var color = dayColor(di);
      var catInfo = function (it) {
        var p = place(it.pid);
        var cat = p ? p.cat : "custom";
        var cc = CAT_COLORS[cat] || CAT_COLORS.custom;
        return { bg: cc.bg, col: cc.col, label: CAT_LABELS[cat] || "Personalizado" };
      };

      var itemsHtml = "";
      if (day.open) {
        itemsHtml = '<div class="cp-day__body" id="cpDayBody' + di + '">';
        day.items.forEach(function (it, ii) {
          var p = place(it.pid);
          var ci = catInfo(it);
          var imgHtml = p ? '<img class="cp-itin-thumb" src="' + placeSeed(p) + '160/160" alt="" loading="lazy">' :
            '<span class="cp-itin-custom-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6b7a83" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>';
          itemsHtml += '<div class="cp-drag-gap' + (S.drag ? " dragging" : "") + (S.dragOver === di + ":" + ii ? " over" : "") + '" data-gap="' + di + ':' + ii + '"></div>';
          itemsHtml += '<div class="cp-itin-item' + (S.hoverUid === it.uid ? " hovered" : "") + '" draggable="true" data-uid="' + it.uid + '" data-from="' + di + '">' +
            '<span class="cp-itin-num" style="background:' + color + '">' + (ii + 1) + '</span>' +
            '<div class="cp-itin-body">' +
            '<div style="display:flex;align-items:flex-start;gap:8px"><p class="cp-itin-name">' + esc(itemName(it)) + '</p>' +
            '<button class="cp-itin-del" data-rm-uid="' + it.uid + '" data-rm-from="' + di + '">✕</button></div>' +
            '<div class="cp-itin-meta"><span class="cp-cat-badge" style="background:' + ci.bg + ';color:' + ci.col + '">' + ci.label + '</span>' +
            (p ? '<span class="cp-rating">★ ' + p.rating.toFixed(1) + '</span><span>(' + (p.rev > 1000 ? (p.rev / 1000).toFixed(1) + "k" : p.rev) + ' reseñas)</span>' : '') + '</div>' +
            '<div class="cp-itin-controls">' +
            '<label class="cp-itin-control">' + ICO.clock + '<input type="time" value="' + esc(it.hora) + '" data-field="hora" data-uid="' + it.uid + '" data-day="' + di + '"></label>' +
            '<label class="cp-itin-control">' + ICO.hourglass + '<select data-field="dur" data-uid="' + it.uid + '" data-day="' + di + '">' +
            '<option value="">Duración</option><option value="30 min"' + (it.dur === "30 min" ? " selected" : "") + '>30 min</option>' +
            '<option value="1 h"' + (it.dur === "1 h" ? " selected" : "") + '>1 h</option><option value="1.5 h"' + (it.dur === "1.5 h" ? " selected" : "") + '>1.5 h</option>' +
            '<option value="2 h"' + (it.dur === "2 h" ? " selected" : "") + '>2 h</option><option value="2.5 h"' + (it.dur === "2.5 h" ? " selected" : "") + '>2.5 h</option>' +
            '<option value="3 h"' + (it.dur === "3 h" ? " selected" : "") + '>3 h</option><option value="4 h"' + (it.dur === "4 h" ? " selected" : "") + '>4 h</option>' +
            '<option value="Todo el día"' + (it.dur === "Todo el día" ? " selected" : "") + '>Todo el día</option></select></label>' +
            '<label class="cp-itin-control"><span class="cur-sym">' + CUR_SYM + '</span><input type="number" min="0" value="' + esc(it.precio) + '" placeholder="0" data-field="precio" data-uid="' + it.uid + '" data-day="' + di + '"></label></div>' +
            '<input class="cp-itin-nota" value="' + esc(it.nota) + '" placeholder="Añadir notas" data-field="nota" data-uid="' + it.uid + '" data-day="' + di + '">' +
            '</div>' + imgHtml + '</div>';
        });
        // End gap
        itemsHtml += '<div class="cp-drag-gap' + (S.drag ? " dragging" : "") + (S.dragOver === di + ":" + day.items.length ? " over" : "") + '" data-gap="' + di + ':' + day.items.length + '"></div>';
        // Add place input
        var k = String(di);
        var acItems = S.addFocus === k ? acList(k) : [];
        itemsHtml += '<div class="cp-add-place"><div class="cp-add-place-input">' +
          '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5b6b74" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
          '<input value="' + esc(S.addQ[k] || "") + '" placeholder="Añadir un lugar" data-addq="' + k + '"></div>';
        if (S.addFocus === k && acItems.length > 0) {
          itemsHtml += '<div class="cp-autocomplete">';
          acItems.forEach(function (a) {
            var imgTag = a.isNew ? '<span class="cp-ac-new-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#b98a00" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>' :
              '<img class="cp-ac-img" src="' + esc(a.img) + '" alt="" loading="lazy">';
            itemsHtml += '<div class="cp-ac-row" data-ac-key="' + k + '" data-ac-pid="' + (a.pid || "") + '" data-ac-custom="' + esc(a.customName || "") + '">' +
              imgTag + '<span class="cp-ac-text"><span class="cp-ac-name">' + esc(a.name) + '</span><span class="cp-ac-meta">' + esc(a.meta) + '</span></span></div>';
          });
          itemsHtml += '</div>';
        }
        itemsHtml += '</div></div>';
      }

      var noteHtml = "";
      if (day.note !== null && day.note !== undefined) {
        noteHtml = '<div class="cp-day__note">' + ICO.noteEdit +
          '<input value="' + esc(day.note) + '" placeholder="Nota del día…" data-daynote="' + di + '">' +
          '<button data-daynote-rm="' + di + '">✕</button></div>';
      }

      return '<div class="cp-day" id="secDia' + di + '">' +
        '<div class="cp-day__header">' +
        '<button class="cp-day__collapse" data-collapse="' + di + '"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="transform:rotate(' + (day.open ? "0" : "-90") + 'deg)"><path d="M6 9l6 6 6-6"/></svg></button>' +
        '<span class="cp-day__dot" style="background:' + color + '"></span>' +
        '<h3 class="cp-day__title">' + esc(dayTitle(di)) + '</h3>' +
        '<button class="cp-day__fill" data-fill="' + di + '">' + ICO.star + ' Rellenar día</button>' +
        '<div style="position:relative;flex-shrink:0"><button class="cp-day__menu-btn" data-daymenu="' + di + '">' + ICO.dots + '</button>' +
        (S.dayMenu === di ? '<div class="cp-dropdown" style="z-index:470"><button class="cp-dropdown-item" data-daynote-add="' + di + '">Añadir nota del día</button><button class="cp-dropdown-item danger" data-dayclear="' + di + '">Vaciar día</button></div>' : '') +
        '</div></div>' +
        '<input class="cp-day__sub" value="' + esc(day.sub) + '" placeholder="Añadir subtítulo" data-daysub="' + di + '">' +
        noteHtml + itemsHtml + '</div>';
    }).join("");

    // Init sortable for each day
    S.days.forEach(function (d, i) {
      initSortable("cpDayBody" + i, i);
    });
  }

  /* ── Budget ─────────────────────────────────────────────── */
  function renderBudget() {
    var total = budgetTotal();
    var totalEl = $("cpBudTotal");
    if (totalEl) totalEl.textContent = money(total, 0);

    var budBar = $("cpBudBar");
    var budSub = $("cpBudSub");
    var budBarWrap = $("cpBudBarWrap");
    if (S.budget && S.budget > 0) {
      var pct = Math.min(100, Math.round(total / S.budget * 100));
      if (budBar) { budBar.style.width = pct + "%"; budBar.style.background = pct > 100 ? "#e02424" : pct > 80 ? "#d97706" : "#2e9e4f"; }
      if (budSub) { budSub.textContent = "de " + money(S.budget, 0) + " presupuestado"; budSub.hidden = false; }
      if (budBarWrap) budBarWrap.hidden = false;
    } else {
      if (budSub) budSub.hidden = true;
      if (budBarWrap) budBarWrap.hidden = true;
    }

    // Budget edit
    var editOff = $("cpBudEditOff");
    var editOn = $("cpBudEditOn");
    if (editOff) editOff.hidden = S.budgetEdit;
    if (editOn) editOn.hidden = !S.budgetEdit;

    // Expense form
    var expForm = $("cpExpForm");
    if (expForm) expForm.hidden = !S.expFormOpen;

    // Desglose
    var desgl = $("cpDesglose");
    if (desgl) {
      desgl.hidden = !S.desgloseOpen;
      if (S.desgloseOpen) {
        var rows = budgetDesglose();
        desgl.innerHTML = rows.map(function (r) {
          return '<div class="cp-desglose-row"><span class="cp-desglose-dot" style="background:' + r.color + '"></span>' +
            '<span class="cp-desglose-label">' + esc(r.label) + '</span>' +
            '<span class="cp-desglose-bar"><span style="width:' + r.pct + '%;background:' + r.color + '"></span></span>' +
            '<span class="cp-desglose-amt">' + money(r.amt, 0) + '</span></div>';
        }).join("");
      }
    }

    // Expense day select options
    var expDiaSel = $("cpExpDia");
    if (expDiaSel) {
      var opts = '<option value="">Sin día</option>';
      S.days.forEach(function (d, i) { opts += '<option value="' + i + '"' + (S.expDia === String(i) ? " selected" : "") + '>' + esc(sideDayLabel(i)) + '</option>'; });
      expDiaSel.innerHTML = opts;
    }

    // Expense rows
    var expList = $("cpExpList");
    var expEmpty = $("cpExpEmpty");
    if (expList) {
      if (S.expenses.length === 0) {
        expList.innerHTML = "";
        if (expEmpty) expEmpty.hidden = false;
      } else {
        if (expEmpty) expEmpty.hidden = true;
        var sorted = S.expenses.slice();
        if (S.expSort === "monto") sorted.sort(function (a, b) { return (parseFloat(b.monto) || 0) - (parseFloat(a.monto) || 0); });
        expList.innerHTML = sorted.map(function (e) {
          return '<div class="cp-exp-row"><span class="cp-desglose-dot" style="background:' + expCatColor(e.cat) + '"></span>' +
            '<div style="flex:1;min-width:0"><p class="cp-exp-concepto">' + esc(e.concepto) + '</p>' +
            '<p class="cp-exp-sub">' + esc(e.cat) + (e.dia !== "" ? " · " + esc(sideDayLabel(parseInt(e.dia))) : "") + '</p></div>' +
            '<span class="cp-exp-amt">' + money(parseFloat(e.monto) || 0, 0) + '</span>' +
            '<button class="cp-remove-btn" data-exp-rm="' + e.id + '">✕</button></div>';
        }).join("");
      }
    }
  }

  /* ── Map panel ──────────────────────────────────────────── */
  var _map, _markers = [], _polylines = [], _infoWin;
  function renderMapPanel() {
    if (!_map || !window.google) return;
    // Clear
    _markers.forEach(function (m) { m.setMap(null); });
    _markers = [];
    _polylines.forEach(function (p) { p.setMap(null); });
    _polylines = [];

    var bounds = new google.maps.LatLngBounds();
    var hasItems = false;

    S.days.forEach(function (day, di) {
      if (S.mapDay !== "all" && S.mapDay !== String(di)) return;
      var color = dayColor(di);
      var path = [];
      day.items.forEach(function (it, ii) {
        var p = place(it.pid);
        if (!p) return;
        hasItems = true;
        var pos = { lat: p.lat, lng: p.lng };
        bounds.extend(pos);
        path.push(pos);
        var marker = new google.maps.Marker({
          position: pos,
          map: _map,
          icon: pinIcon(color, S.hoverUid === it.uid),
          label: { text: String(ii + 1), color: "#ffffff", fontSize: "12px", fontWeight: "700" },
          zIndex: S.hoverUid === it.uid ? 100 : 10
        });
        marker._uid = it.uid;
        marker._name = itemName(it);
        marker.addListener("mouseover", function () { S.hoverUid = it.uid; render(); });
        marker.addListener("mouseout", function () { if (S.hoverUid === it.uid) { S.hoverUid = null; render(); } });
        marker.addListener("click", function () {
          if (_infoWin) _infoWin.close();
          _infoWin = new google.maps.InfoWindow({ content: '<div style="font-family:Poppins,sans-serif;font-size:13px;font-weight:600;color:#0d1f27;padding:2px 4px">' + esc(marker._name) + '</div>' });
          _infoWin.open(_map, marker);
        });
        _markers.push(marker);
      });
      if (path.length > 1) {
        var poly = new google.maps.Polyline({
          path: path,
          geodesic: true,
          strokeColor: color,
          strokeOpacity: .8,
          strokeWeight: 3,
          map: _map
        });
        _polylines.push(poly);
      }
    });

    if (hasItems) { _map.fitBounds(bounds, 60); }
    else { _map.setCenter({ lat: 32.715, lng: -117.161 }); _map.setZoom(11); }

    // Map day filter
    var sel = $("cpMapDaySel");
    if (sel) {
      var opts = '<option value="all">Todos los días</option>';
      S.days.forEach(function (d, i) { opts += '<option value="' + i + '"' + (S.mapDay === String(i) ? " selected" : "") + '>' + esc(sideDayLabel(i)) + '</option>'; });
      sel.innerHTML = opts;
    }
  }
  function pinIcon(color, active) {
    if (!window.google) return null;
    var w = active ? 39 : 32, h = active ? 49 : 40;
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '" viewBox="0 0 30 38"><path d="M15 0C6.7 0 0 6.7 0 15c0 10.5 15 23 15 23s15-12.5 15-23C30 6.7 23.3 0 15 0z" fill="' + color + '" stroke="#ffffff" stroke-width="2"/></svg>';
    return {
      url: "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg),
      scaledSize: new google.maps.Size(w, h),
      anchor: new google.maps.Point(w / 2, h),
      labelOrigin: new google.maps.Point(w / 2, active ? 18 : 15)
    };
  }

  /* ── Modals ─────────────────────────────────────────────── */
  function renderModals() {
    // Share
    var shareModal = $("cpShareModal");
    if (shareModal) shareModal.hidden = !S.shareOpen;

    // Explore all
    renderExploreModal();

    // AI
    renderAiModal();

    // Calendar in planner mode
    if (S.screen === "planner") renderCalendar();

    // Overlay lock
    var lock = S.shareOpen || S.exploreOpen || S.aiOpen;
    document.body.style.overflow = lock ? "hidden" : "";
  }

  function renderExploreModal() {
    var modal = $("cpExploreModal");
    if (modal) modal.hidden = !S.exploreOpen;
    if (!S.exploreOpen) return;

    // Tabs
    qsa(".cp-explore-tab").forEach(function (btn) {
      btn.classList.toggle("active", btn.getAttribute("data-extab") === S.exTab);
    });

    // Grid
    var grid = $("cpExploreGrid");
    if (!grid) return;
    var used = usedPids();
    var q = (S.exQ || "").toLowerCase();
    var items = PLACES.filter(function (p) {
      if (S.exTab === "hacer") return p.cat === "hacer";
      if (S.exTab === "rest") return p.cat === "rest";
      return p.cat === "hotel";
    }).filter(function (p) { return !q || p.name.toLowerCase().indexOf(q) !== -1; });

    grid.innerHTML = items.map(function (p) {
      var added = !!used[p.id];
      return '<div class="cp-explore-item"><img src="' + placeSeed(p) + '400/200" alt="" loading="lazy">' +
        '<div class="cp-explore-item__body"><p class="cp-explore-item__name">' + esc(p.name) + '</p>' +
        '<div class="cp-explore-item__meta"><span class="cp-rating">★ ' + p.rating.toFixed(1) + '</span><span>' + esc(p.meta) + '</span><span class="cp-price-tag">' + priceLabel(p) + '</span></div>' +
        '<button class="cp-explore-item__add' + (added ? " added" : "") + '" data-explore-add="' + p.id + '"' + (added ? " disabled" : "") + '>' + (added ? "✓ Añadido" : "+ Añadir al plan") + '</button></div></div>';
    }).join("");
  }

  function renderAiModal() {
    var modal = $("cpAiModal");
    if (modal) modal.hidden = !S.aiOpen;
    if (!S.aiOpen) return;

    var log = $("cpAiLog");
    if (!log) return;
    if (S.aiLog.length === 0) {
      log.innerHTML = '<div class="cp-ai-empty">Soy tu asistente de Ruta Nómada. Puedo ayudarte a rellenar días, sugerir restaurantes y añadir los imperdibles de ' + esc(S.dest || "San Diego") + '.</div>';
    } else {
      log.innerHTML = S.aiLog.map(function (m) {
        return '<div class="cp-ai-bubble ' + (m.who === "u" ? "user" : "assistant") + '">' + esc(m.t) + '</div>';
      }).join("");
      log.scrollTop = log.scrollHeight;
    }
  }

  /* ═══════════════════════════════════════════════════════════
     SORTABLE (drag & drop)
  ═══════════════════════════════════════════════════════════ */
  var _sortables = {};
  function initSortable(containerId, key) {
    var el = $(containerId);
    if (!el || !window.Sortable) return;
    if (_sortables[containerId]) { _sortables[containerId].destroy(); }
    _sortables[containerId] = new Sortable(el, {
      group: "plan-items",
      animation: 150,
      handle: ".cp-grip, .cp-itin-num",
      ghostClass: "dragging",
      chosenClass: "hovered",
      dragClass: "dragging",
      onEnd: function (evt) {
        var uid = evt.item.getAttribute("data-uid");
        var fromKey = evt.item.getAttribute("data-from");
        fromKey = fromKey === "saved" ? "saved" : parseInt(fromKey);
        var toEl = evt.to;
        var toId = toEl.id;
        var toKey;
        if (toId === "cpSavedList") toKey = "saved";
        else {
          var m = toId.match(/cpDayBody(\d+)/);
          toKey = m ? parseInt(m[1]) : fromKey;
        }
        moveItem(fromKey, uid, toKey, evt.newIndex);
        render();
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════
     EVENT DELEGATION
  ═══════════════════════════════════════════════════════════ */
  document.addEventListener("click", function (e) {
    var t = e.target;
    var btn = t.closest("[data-action]") || t.closest("button") || t.closest("[data-city]") || t.closest("[data-ac-key]") || t;

    // Close menus on outside click
    if (S.privOpen && !t.closest("#cpPrivBtn") && !t.closest("#cpPrivDrop")) { S.privOpen = false; render(); }
    if (S.dayMenu !== null && !t.closest("[data-daymenu]") && !t.closest(".cp-dropdown")) { S.dayMenu = null; render(); }

    // Destination suggestions
    if (btn.hasAttribute("data-city")) {
      S.dest = btn.getAttribute("data-city");
      S.destFocus = false;
      $("cpDestInput").value = S.dest;
      render();
      return;
    }

    // Autocomplete picks
    if (btn.closest("[data-ac-key]")) {
      var acRow = btn.closest("[data-ac-key]");
      var key = acRow.getAttribute("data-ac-key");
      var pid = acRow.getAttribute("data-ac-pid");
      var custom = acRow.getAttribute("data-ac-custom");
      var target = key === "saved" ? "saved" : parseInt(key);
      if (pid) addItem(target, pid, null);
      else if (custom) addItem(target, null, custom);
      S.addQ[key] = "";
      S.addFocus = null;
      render();
      return;
    }

    // Calendar day click
    if (btn.hasAttribute("data-iso")) {
      e.preventDefault();
      calClick(btn.getAttribute("data-iso"));
      render();
      return;
    }

    // Invite pill remove
    if (btn.hasAttribute("data-inv-rm")) {
      S.invites.splice(parseInt(btn.getAttribute("data-inv-rm")), 1);
      render();
      return;
    }

    // Move select (saved → day)
    if (btn.hasAttribute("data-move-uid")) return; // handled by change event

    // Remove item
    if (btn.hasAttribute("data-rm-uid")) {
      var uid = btn.getAttribute("data-rm-uid");
      var from = btn.getAttribute("data-rm-from");
      removeItem(from === "saved" ? "saved" : parseInt(from), uid);
      render();
      return;
    }

    // Explore add
    if (btn.hasAttribute("data-explore-add")) {
      addItem("saved", btn.getAttribute("data-explore-add"), null);
      render();
      return;
    }

    // Expense remove
    if (btn.hasAttribute("data-exp-rm")) {
      var eid = btn.getAttribute("data-exp-rm");
      S.expenses = S.expenses.filter(function (e) { return e.id !== eid; });
      render();
      return;
    }

    // Day collapse
    if (btn.hasAttribute("data-collapse")) {
      var di = parseInt(btn.getAttribute("data-collapse"));
      S.days[di].open = !S.days[di].open;
      render();
      return;
    }

    // Day fill
    if (btn.hasAttribute("data-fill")) {
      fillDay(parseInt(btn.getAttribute("data-fill")));
      render();
      return;
    }

    // Day menu
    if (btn.hasAttribute("data-daymenu")) {
      var idx = parseInt(btn.getAttribute("data-daymenu"));
      S.dayMenu = S.dayMenu === idx ? null : idx;
      render();
      return;
    }

    // Day note add
    if (btn.hasAttribute("data-daynote-add")) {
      var di2 = parseInt(btn.getAttribute("data-daynote-add"));
      S.days[di2].note = S.days[di2].note === null ? "" : S.days[di2].note;
      S.dayMenu = null;
      render();
      return;
    }

    // Day note remove
    if (btn.hasAttribute("data-daynote-rm")) {
      S.days[parseInt(btn.getAttribute("data-daynote-rm"))].note = null;
      render();
      return;
    }

    // Day clear
    if (btn.hasAttribute("data-dayclear")) {
      var di3 = parseInt(btn.getAttribute("data-dayclear"));
      var moved = S.days[di3].items.map(function (it) { return Object.assign({}, it, { hora: "" }); });
      S.saved = S.saved.concat(moved);
      S.days[di3].items = [];
      S.dayMenu = null;
      render();
      return;
    }

    // Explore tab
    if (btn.hasAttribute("data-extab")) {
      S.exTab = btn.getAttribute("data-extab");
      render();
      return;
    }

    // Sidebar nav
    if (btn.hasAttribute("data-nav")) {
      var secId = btn.getAttribute("data-nav");
      S.activeSec = secId;
      var el = $(secId);
      if (el) window.scrollTo({ top: window.scrollY + el.getBoundingClientRect().top - 74, behavior: "smooth" });
      render();
      return;
    }

    // AI actions
    if (btn.hasAttribute("data-ai")) {
      aiRun(btn.getAttribute("data-ai"));
      render();
      return;
    }
  });

  /* ── Named action buttons ──────────────────────────────── */
  /* ── Crear el plan en BD y redirigir a plan.php ─────────── */
  function crearPlanEnBD(btn) {
    if (S._creando) return;
    S._creando = true;
    if (btn) { btn.disabled = true; btn.textContent = "Creando tu plan…"; }

    // Borrador local (si el usuario alcanzó a armar días en la
    // versión anterior del planner) → se importa una sola vez
    var draftDays = (S.days || []).map(function (d) {
      return (d.items || []).map(function (it) {
        var p = it.pid ? PLACES.find(function (x) { return x.id === it.pid; }) : null;
        return {
          name: it.custom || (p ? p.name : ""),
          cat: p ? p.cat : "custom",
          hora: it.hora || "",
          precio: it.precio !== "" && it.precio != null ? it.precio : (p ? p.price : ""),
          nota: it.nota || ""
        };
      }).filter(function (x) { return x.name; });
    });
    var hayDraft = draftDays.some(function (d) { return d.length; });

    var privMap = { "Solo yo": "solo", "Amigos": "amigos", "Público": "publico" };
    fetch("api/plan_create.php", {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-CSRF": window.PLAN_CSRF || "" },
      body: JSON.stringify({
        destino: S.dest.trim(),
        nombre: S.tripName || "",
        fecha_inicio: S.start || "",
        fecha_fin: S.end || "",
        privacidad: privMap[S.privacy] || "solo",
        invitados: S.invites || [],
        draft: hayDraft ? { days: draftDays } : null
      })
    }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j.ok) throw new Error(j.error || "No se pudo crear el plan");
      try { localStorage.removeItem("rn_crear_plan_v1"); } catch (err) { }
      window.location.href = "plan.php?id=" + j.id;
    }).catch(function (err) {
      S._creando = false;
      if (btn) { btn.disabled = false; btn.textContent = "Continuar"; }
      alert(err.message || "No se pudo crear el plan. Intenta de nuevo.");
    });
  }

  document.addEventListener("click", function (e) {
    var id = e.target.id || (e.target.closest("button") || {}).id;
    switch (id) {
      // Create screen
      case "cpCalToggle": case "cpCalToggle2":
        S.calOpen = !S.calOpen; render(); break;
      case "cpCalPrev": case "cpCalPrev2": S.calM--; if (S.calM < 0) { S.calM = 11; S.calY--; } render(); break;
      case "cpCalNext": case "cpCalNext2": S.calM++; if (S.calM > 11) { S.calM = 0; S.calY++; } render(); break;
      case "cpCalClear": case "cpCalClear2": S.start = null; S.end = null; S.calOpen = false; render(); break;
      case "cpCalDone": case "cpCalDone2": S.calOpen = false; render(); break;
      case "cpInvToggle": S.invRow = !S.invRow; render(); break;
      case "cpPrivBtn": S.privOpen = !S.privOpen; render(); break;
      case "cpPrivAmigos": S.privacy = "Amigos"; S.privOpen = false; render(); break;
      case "cpPrivSolo": S.privacy = "Solo yo"; S.privOpen = false; render(); break;
      case "cpPrivPublico": S.privacy = "Público"; S.privOpen = false; render(); break;
      case "cpStartBtn":
        if (!S.dest.trim()) return;
        S.screen = "invite"; S.calOpen = false; S.privOpen = false;
        if (!S.tripName) S.tripName = "Viaje a " + S.dest;
        render(); window.scrollTo({ top: 0 }); break;
      case "cpExploreLink": S.screen = "create"; render(); window.scrollTo({ top: 0 }); break;

      // Invite screen
      case "cpBackToCreate": S.screen = "create"; render(); window.scrollTo({ top: 0 }); break;
      case "cpInviteGoBtn": case "cpSkipInvite":
        // Crear el plan en la base de datos y abrir su espacio de
        // trabajo (plan.php). Reemplaza al planner local de la
        // pantalla 3 (que guardaba en localStorage).
        crearPlanEnBD(e.target.closest("button"));
        break;

      // Planner
      case "cpHeroEdit": S.heroIdx = (S.heroIdx + 1) % HERO_IMGS.length; render(); break;
      case "cpNameEditBtn": S.nameEdit = true; render(); setTimeout(function () { var inp = $("cpTripNameInput"); if (inp) inp.focus(); }, 50); break;
      case "cpDateChipBtn": S.calOpen = !S.calOpen; render(); break;
      case "cpShareOpenBtn": case "cpShareOpenBtn2": S.shareOpen = true; render(); break;
      case "cpShareClose": S.shareOpen = false; render(); break;
      case "cpCopyLink":
        try { navigator.clipboard.writeText("https://rutanomada.mx/plan/" + (S.dest || "sandiego").toLowerCase().replace(/\s+/g, "-") + "-x7k9"); } catch (err) { }
        S.copied = true; render();
        setTimeout(function () { S.copied = false; render(); }, 1800);
        break;
      case "cpExploreAllBtn": S.exploreOpen = true; render(); break;
      case "cpExploreClose": S.exploreOpen = false; render(); break;
      case "cpAiBtn": S.aiOpen = true; render(); break;
      case "cpAiClose": S.aiOpen = false; render(); break;
      case "cpPrintBtn": window.print(); break;
      case "cpSidebarHide": S.sidebarHidden = true; render(); break;
      case "cpSidebarFab": S.sidebarHidden = false; render(); break;
      case "cpResetPlan":
        if (!confirm("¿Empezar un plan nuevo? Se borrará el plan actual.")) return;
        try { localStorage.removeItem("rn_crear_plan_v1"); } catch (err2) { }
        _uid = 1;
        S.screen = "create"; S.dest = ""; S.start = null; S.end = null; S.invites = [];
        S.tripName = ""; S.notas = ""; S.saved = []; S.days = []; S.expenses = [];
        S.budget = null; S.aiLog = []; S.mapDay = "all"; S.mapFull = false;
        S.mapHintOff = false; S.calOpen = false; S.shareOpen = false;
        S.exploreOpen = false; S.aiOpen = false; S.sidebarHidden = false;
        render(); window.scrollTo({ top: 0 }); break;
      case "cpDesgloseToggle": S.desgloseOpen = !S.desgloseOpen; render(); break;
      case "cpExpFormToggle": S.expFormOpen = !S.expFormOpen; render(); break;
      case "cpExpCancel": S.expFormOpen = false; render(); break;
      case "cpExpSave":
        if (S.expConcepto.trim()) {
          S.expenses.push({ id: nu(), concepto: S.expConcepto, monto: S.expMonto, cat: S.expCat, dia: S.expDia });
          S.expConcepto = ""; S.expMonto = ""; S.expCat = "Comida"; S.expDia = "";
          S.expFormOpen = false;
        }
        render(); break;
      case "cpBudEditStart": S.budgetEdit = true; S.budgetTxt = S.budget ? String(S.budget) : ""; render();
        setTimeout(function () { var inp = $("cpBudInput"); if (inp) inp.focus(); }, 50); break;
      case "cpBudSave":
        S.budget = parseFloat(S.budgetTxt) || null; S.budgetEdit = false; render(); break;
      case "cpMapHintClose": S.mapHintOff = true; render(); break;
      case "cpMapFullscreen": S.mapFull = !S.mapFull;
        var mapPanel = $("cpMapPanel");
        if (mapPanel) mapPanel.classList.toggle("fullscreen", S.mapFull);
        setTimeout(function () { if (_map) google.maps.event.trigger(_map, "resize"); }, 300);
        render(); break;
    }
  });

  /* ── Input events ──────────────────────────────────────── */
  document.addEventListener("input", function (e) {
    var t = e.target;
    if (t.id === "cpDestInput") { S.dest = t.value; render(); }
    else if (t.id === "cpNotasTA") { S.notas = t.value; }
    else if (t.id === "cpTripNameInput") { S.tripName = t.value; }
    else if (t.id === "cpInvInput" || t.id === "cpInvInput2") { S.invInput = t.value; }
    else if (t.id === "cpExpConcepto") { S.expConcepto = t.value; }
    else if (t.id === "cpExpMonto") { S.expMonto = t.value; }
    else if (t.id === "cpBudInput") { S.budgetTxt = t.value; }
    else if (t.id === "cpExQ") { S.exQ = t.value; render(); }
    else if (t.id === "cpShareInv") { S.shareInv = t.value; }
    else if (t.hasAttribute("data-addq")) { S.addQ[t.getAttribute("data-addq")] = t.value; render(); }
    else if (t.hasAttribute("data-daysub")) { S.days[parseInt(t.getAttribute("data-daysub"))].sub = t.value; }
    else if (t.hasAttribute("data-daynote")) { S.days[parseInt(t.getAttribute("data-daynote"))].note = t.value; }
    else if (t.hasAttribute("data-field")) {
      var uid = t.getAttribute("data-uid");
      var day = parseInt(t.getAttribute("data-day"));
      var field = t.getAttribute("data-field");
      var item = S.days[day].items.find(function (i) { return i.uid === uid; });
      if (item) item[field] = t.value;
      if (field === "precio") renderBudget();
    }
    schedulePersist();
  });

  document.addEventListener("change", function (e) {
    var t = e.target;
    if (t.id === "cpExpCat") { S.expCat = t.value; }
    else if (t.id === "cpExpDia") { S.expDia = t.value; }
    else if (t.id === "cpExpSort") { S.expSort = t.value; render(); }
    else if (t.id === "cpShareRole") { S.linkRole = t.value; }
    else if (t.id === "cpMapDaySel") { S.mapDay = t.value; renderMapPanel(); }
    else if (t.hasAttribute("data-field")) {
      var uid = t.getAttribute("data-uid");
      var day = parseInt(t.getAttribute("data-day"));
      var field = t.getAttribute("data-field");
      var item = S.days[day].items.find(function (i) { return i.uid === uid; });
      if (item) item[field] = t.value;
    }
    else if (t.hasAttribute("data-move-uid")) {
      var uid2 = t.getAttribute("data-move-uid");
      var val = t.value;
      if (val !== "") { moveItem("saved", uid2, parseInt(val), -1); render(); }
    }
    schedulePersist();
  });

  document.addEventListener("focus", function (e) {
    var t = e.target;
    if (t.id === "cpDestInput") { S.destFocus = true; render(); }
    if (t.hasAttribute("data-addq")) { S.addFocus = t.getAttribute("data-addq"); render(); }
  }, true);

  document.addEventListener("blur", function (e) {
    var t = e.target;
    if (t.id === "cpDestInput") { setTimeout(function () { S.destFocus = false; render(); }, 150); }
    if (t.id === "cpTripNameInput") { S.nameEdit = false; render(); }
    if (t.hasAttribute("data-addq")) {
      var key = t.getAttribute("data-addq");
      setTimeout(function () { if (S.addFocus === key) { S.addFocus = null; render(); } }, 150);
    }
  }, true);

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      if (S.calOpen) { S.calOpen = false; render(); return; }
      if (S.shareOpen) { S.shareOpen = false; render(); return; }
      if (S.exploreOpen) { S.exploreOpen = false; render(); return; }
      if (S.aiOpen) { S.aiOpen = false; render(); return; }
      if (S.dayMenu !== null) { S.dayMenu = null; render(); return; }
    }
    // Enter on invite input
    if (e.key === "Enter" && (e.target.id === "cpInvInput" || e.target.id === "cpInvInput2")) {
      e.preventDefault();
      var val = S.invInput.trim();
      if (val && S.invites.indexOf(val) === -1) { S.invites.push(val); S.invInput = ""; e.target.value = ""; render(); }
    }
    // Enter on add-place input
    if (e.key === "Enter" && e.target.hasAttribute("data-addq")) {
      e.preventDefault();
      var key = e.target.getAttribute("data-addq");
      var items = acList(key);
      if (items.length > 0) {
        var a = items[0];
        var target = key === "saved" ? "saved" : parseInt(key);
        if (a.pid) addItem(target, a.pid, null);
        else if (a.customName) addItem(target, null, a.customName);
        S.addQ[key] = "";
        S.addFocus = null;
        render();
      }
    }
    // Enter on budget input
    if (e.key === "Enter" && e.target.id === "cpBudInput") {
      S.budget = parseFloat(S.budgetTxt) || null; S.budgetEdit = false; render();
    }
  });

  /* ── Calendar click ─────────────────────────────────────── */
  function calClick(iso) {
    if (!S.start || (S.start && S.end)) { S.start = iso; S.end = null; }
    else if (iso < S.start) { S.start = iso; }
    else {
      S.end = iso;
      if (S.screen === "planner") {
        var r = genDays(S.start, S.end, S.days.length ? S.days : null);
        S.days = r.days;
        S.saved = S.saved.concat(r.extra);
      }
    }
  }

  /* ── Scroll spy ─────────────────────────────────────────── */
  var _sraf;
  window.addEventListener("scroll", function () {
    if (_sraf || S.screen !== "planner") return;
    _sraf = requestAnimationFrame(function () {
      _sraf = null;
      var ids = ["secExplorar", "secNotas", "secSaved"];
      S.days.forEach(function (d, i) { ids.push("secDia" + i); });
      ids.push("secPresupuesto");
      var cur = ids[0];
      ids.forEach(function (id) { var el = $(id); if (el && el.getBoundingClientRect().top < 190) cur = id; });
      if (cur !== S.activeSec) { S.activeSec = cur; renderSidebar(); }
    });
  }, { passive: true });

  /* ── Map initialization ─────────────────────────────────── */
  function initMap() {
    if (!window.google || _map) return;
    var container = $("cpMapContainer");
    if (!container) return;
    _map = new google.maps.Map(container, {
      center: { lat: 32.715, lng: -117.161 },
      zoom: 11,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: false,
      zoomControl: false,
      gestureHandling: "greedy",
      clickableIcons: false
    });
    renderMapPanel();
  }
  window.__cpMapReady = function () { if (S.screen === "planner") initMap(); };

  /* ── Init ───────────────────────────────────────────────── */
  restore();
  render();
  if (S.screen === "planner") {
    setTimeout(function () { initMap(); }, 500);
  }

})();
