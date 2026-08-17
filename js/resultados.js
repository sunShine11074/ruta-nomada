/* ============================================================
   resultados.js — Resultados de búsqueda por ciudad | Ruta Nómada
   Rediseño (design_handoff_resultados) portado a vanilla JS con datos reales:
   • Google Geocoding → centro, breadcrumb y título.
   • Google Places   → tarjetas por categoría + fotos/atribución del hero.
   • Wikipedia REST  → descripción de la ciudad.
   • Open-Meteo      → clima, UV y calidad del aire (sin key).
============================================================ */
(function () {
  "use strict";

  var $ = function (id) { return document.getElementById(id); };
  var qs = new URLSearchParams(location.search).get("q");
  var UID = document.body.getAttribute("data-user-id") || "0";
  // Pestaña inicial desde el selector del buscador (?tab=hoteles|restaurantes|hacer)
  var tabParam = new URLSearchParams(location.search).get("tab");
  if (["hacer", "restaurantes", "hoteles"].indexOf(tabParam) === -1) tabParam = "hacer";

  /* ── Estado ────────────────────────────────────────────── */
  var S = {
    tab: tabParam, heroI: 0, heroPhotos: [], saved: false, descOpen: false,
    climaPage: 0, favs: {}, cardImg: {}, hoverId: null, infoId: null,
    mapFull: false, mapModal: false, narrow: false, mobile: false,
    center: null, cityKey: "", items: [], markers: [], cache: {}
  };
  var map, geocoder, places, infoWin;
  var PIN = "#2b5760", PIN_ACT = "#f0b429";

  try { S.favs = JSON.parse(localStorage.getItem("rn_favs_" + UID) || "{}"); } catch (e) {}

  /* ── Continente por ISO2 ───────────────────────────────── */
  var CONT = {
    "América del Norte": "US CA MX GT BZ SV HN NI CR PA CU DO HT JM BS BB TT PR GL BM",
    "América del Sur": "AR BO BR CL CO EC GY PY PE SR UY VE GF FK",
    "Europa": "AD AL AT BA BE BG BY CH CY CZ DE DK EE ES FI FR GB GR HR HU IE IS IT LI LT LU LV MC MD ME MK MT NL NO PL PT RO RS RU SE SI SK SM UA VA XK",
    "Asia": "AE AF AM AZ BD BH BN BT CN GE HK ID IL IN IQ IR JO JP KG KH KP KR KW KZ LA LB LK MM MN MO MY MV NP OM PH PK PS QA SA SG SY TH TJ TL TM TR TW UZ VN YE",
    "África": "AO BF BI BJ BW CD CF CG CI CM CV DJ DZ EG EH ER ET GA GH GM GN GQ GW KE KM LR LS LY MA MG ML MR MU MW MZ NA NE NG RW SC SD SL SN SO SS ST SZ TD TG TN TZ UG ZA ZM ZW",
    "Oceanía": "AU CK FJ FM KI MH NC NR NU NZ PF PG PW SB TO TV VU WS"
  };
  function continentOf(iso2) {
    for (var name in CONT) { if (CONT[name].indexOf(iso2) !== -1) return name; }
    return "Mundo";
  }

  /* ── Utiles ────────────────────────────────────────────── */
  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }
  function fmtTime(iso) {
    var d = new Date(iso);
    var h = d.getHours(), m = d.getMinutes(), ap = h >= 12 ? "pm" : "am";
    h = h % 12; if (h === 0) h = 12;
    return h + ":" + (m < 10 ? "0" : "") + m + " " + ap;
  }

  /* ── Pines SVG ─────────────────────────────────────────── */
  function pinIcon(color, active) {
    var w = active ? 39 : 32, h = active ? 49 : 40;
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '" viewBox="0 0 30 38">' +
      '<path d="M15 0C6.7 0 0 6.7 0 15c0 10.5 15 23 15 23s15-12.5 15-23C30 6.7 23.3 0 15 0z" fill="' + color + '" stroke="#ffffff" stroke-width="2"/></svg>';
    return {
      url: "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg),
      scaledSize: new google.maps.Size(w, h),
      anchor: new google.maps.Point(w / 2, h),
      labelOrigin: new google.maps.Point(w / 2, (active ? 18 : 15))
    };
  }

  /* ══════════════ MAPA ══════════════ */
  function initMap() {
    geocoder = new google.maps.Geocoder();
    map = new google.maps.Map($("map"), {
      center: { lat: 23, lng: -102 }, zoom: 4,
      mapTypeControl: false, streetViewControl: false,
      fullscreenControl: false, zoomControl: false,
      gestureHandling: "greedy", clickableIcons: false
    });
    places = new google.maps.places.PlacesService(map);
    infoWin = new google.maps.InfoWindow({ disableAutoPan: true });
  }

  function fixMapLeft() {
    var panel = $("mapPanel"), wrap = $("mapWrap");
    if (!panel || !wrap) return;
    if (S.mapFull || document.body.classList.contains("rz-mapmodal")) return;
    if (S.narrow) return;
    panel.style.left = Math.round(wrap.getBoundingClientRect().left) + "px";
  }

  function mapResized() {
    google.maps.event.trigger(map, "resize");
    if (S.center) map.panTo(S.center);
  }

  /* ══════════════ GEOCODE + ARRANQUE ══════════════ */
  function geocodeCity(city) {
    return new Promise(function (resolve, reject) {
      geocoder.geocode({ address: city }, function (res, status) {
        if (status === "OK" && res && res[0]) resolve(res[0]);
        else reject(new Error(status));
      });
    });
  }

  function compOf(result, type, short) {
    var comps = result.address_components || [];
    for (var i = 0; i < comps.length; i++) {
      if (comps[i].types.indexOf(type) !== -1) return short ? comps[i].short_name : comps[i].long_name;
    }
    return "";
  }

  async function boot() {
    if (!qs || !qs.trim()) { $("rzNoQuery").hidden = false; $("mapWrap").style.display = "none"; return; }
    $("rzContent").hidden = false;

    try { await window.gmapsReady; } catch (e) {}
    if (!window.google || !google.maps) {
      $("rzContent").hidden = true; $("rzNoQuery").hidden = false;
      $("rzNoQuery").querySelector("p").textContent = "No se pudo cargar Google Maps. Revisa tu conexión.";
      return;
    }
    initMap();
    fixMapLeft();

    var g;
    try { g = await geocodeCity(qs.trim()); }
    catch (err) {
      $("rzContent").hidden = true; $("rzNoQuery").hidden = false;
      $("rzNoQuery").querySelector("h2").textContent = "Sin resultados";
      $("rzNoQuery").querySelector("p").textContent =
        String(err.message) === "ZERO_RESULTS"
          ? "No encontramos la ciudad “" + qs + "”. Intenta con otro nombre."
          : "El servicio de mapas rechazó la solicitud (" + err.message + ").";
      $("mapWrap").style.display = "none";
      return;
    }

    var loc = g.geometry.location;
    S.center = { lat: loc.lat(), lng: loc.lng() };
    var city = compOf(g, "locality") || compOf(g, "administrative_area_level_2") || (g.formatted_address || qs).split(",")[0];
    var state = compOf(g, "administrative_area_level_1");
    var country = compOf(g, "country");
    var iso = compOf(g, "country", true);
    S.cityKey = (city + "|" + iso).toLowerCase();

    // Breadcrumb + título
    $("bcContinent").textContent = continentOf(iso);
    $("bcCountry").textContent = country || "—";
    $("bcCity").textContent = city;
    $("cityTitle").textContent = city + (state ? ", " + state : "");
    document.title = city + " — Ruta Nómada";

    // Guardado de ciudad (persistencia local por usuario)
    try {
      var savedCities = JSON.parse(localStorage.getItem("rn_city_saved_" + UID) || "{}");
      S.saved = !!savedCities[S.cityKey];
    } catch (e) {}
    paintSave();

    map.setCenter(S.center);
    map.setZoom(13);

    // Cargas en paralelo (independientes)
    loadHero(g.place_id, city);
    loadDescription(city, state);
    loadWeather(S.center.lat, S.center.lng);
    changeTab(S.tab, true);
  }

  /* ══════════════ HERO (fotos de Places) ══════════════ */
  function heroApply() {
    var ph = S.heroPhotos;
    var img = $("heroImg");
    if (!ph.length) return;
    var p = ph[S.heroI];
    img.style.opacity = 0;
    var pre = new Image();
    pre.onload = function () { img.src = p.url; img.style.opacity = 1; };
    pre.src = p.url;
    // precargar siguiente
    if (ph.length > 1) { var nx = new Image(); nx.src = ph[(S.heroI + 1) % ph.length].url; }
    // atribución
    if (p.attr) {
      $("heroAttr").hidden = false;
      $("heroAttrName").innerHTML = p.attr; // html_attributions es HTML confiable de Google
    } else { $("heroAttr").hidden = true; }
    // dots
    var dots = $("heroDots"); dots.innerHTML = "";
    for (var i = 0; i < ph.length; i++) {
      var s = document.createElement("span");
      if (i === S.heroI) s.className = "on";
      dots.appendChild(s);
    }
  }

  function stepHero(d) {
    var n = S.heroPhotos.length;
    if (n < 2) return;
    S.heroI = (S.heroI + d + n) % n;
    heroApply();
  }

  function loadHero(placeId, city) {
    if (!placeId) return;
    places.getDetails({ placeId: placeId, fields: ["photos"] }, function (det, status) {
      if (status !== "OK" || !det || !det.photos || !det.photos.length) return;
      S.heroPhotos = det.photos.slice(0, 10).map(function (p) {
        var attr = (p.html_attributions && p.html_attributions[0]) || "";
        return { url: p.getUrl({ maxWidth: 1280, maxHeight: 720 }), attr: attr };
      });
      $("heroCountPill").hidden = false;
      $("heroCount").textContent = det.photos.length.toLocaleString("es-MX");
      $("heroImg").alt = "Fotos de " + city;
      S.heroI = 0;
      heroApply();
    });
  }

  function bindHero() {
    var hero = $("hero");
    hero.addEventListener("mouseenter", function () {
      if (S.mobile || S.heroPhotos.length < 2) return;
      $("heroPrev").hidden = false; $("heroNext").hidden = false;
    });
    hero.addEventListener("mouseleave", function () {
      $("heroPrev").hidden = true; $("heroNext").hidden = true;
    });
    $("heroPrev").addEventListener("click", function () { stepHero(-1); });
    $("heroNext").addEventListener("click", function () { stepHero(1); });
    var hx = 0;
    hero.addEventListener("touchstart", function (e) { hx = e.touches[0].clientX; }, { passive: true });
    hero.addEventListener("touchend", function (e) {
      var dx = e.changedTouches[0].clientX - hx;
      if (Math.abs(dx) > 40) stepHero(dx < 0 ? 1 : -1);
    });
  }

  /* ══════════════ GUARDAR CIUDAD ══════════════ */
  function paintSave() {
    var p = $("saveHeart"), b = $("saveBtn");
    if (S.saved) {
      p.setAttribute("fill", "#ef4444"); p.setAttribute("stroke", "#ef4444");
      $("saveLabel").textContent = "Guardado";
      b.classList.add("saved");
    } else {
      p.setAttribute("fill", "none"); p.setAttribute("stroke", "#16262e");
      $("saveLabel").textContent = "Guardar";
      b.classList.remove("saved");
    }
  }
  function bindSave() {
    $("saveBtn").addEventListener("click", function () {
      S.saved = !S.saved;
      try {
        var k = "rn_city_saved_" + UID;
        var o = JSON.parse(localStorage.getItem(k) || "{}");
        if (S.saved) o[S.cityKey] = true; else delete o[S.cityKey];
        localStorage.setItem(k, JSON.stringify(o));
      } catch (e) {}
      // reiniciar animación pop
      var b = $("saveBtn"); b.classList.remove("saved"); void b.offsetWidth;
      paintSave();
    });
  }

  /* ══════════════ DESCRIPCIÓN (Wikipedia) ══════════════ */
  async function wikiSummary(title) {
    var r = await fetch("https://es.wikipedia.org/api/rest_v1/page/summary/" + encodeURIComponent(title), { headers: { Accept: "application/json" } });
    if (!r.ok) throw new Error("wiki-" + r.status);
    var j = await r.json();
    if (j.type && j.type.indexOf("disambiguation") !== -1) throw new Error("wiki-disamb");
    return j.extract || "";
  }

  async function loadDescription(city, state) {
    var text = "";
    try { text = await wikiSummary(city); }
    catch (e) {
      try { text = await wikiSummary(city + " (" + state + ")"); } catch (e2) {}
    }
    if (!text) return; // sin descripción: el bloque queda oculto
    $("descText").textContent = text;
    $("descBlock").hidden = false;
    // "Leer más" solo si el texto desborda las ~3 líneas
    requestAnimationFrame(function () {
      var clip = $("descWrap");
      if ($("descText").scrollHeight <= clip.clientHeight + 4) $("readMore").style.display = "none";
    });
  }

  function bindDesc() {
    $("readMore").addEventListener("click", function () {
      S.descOpen = !S.descOpen;
      $("descWrap").classList.toggle("open", S.descOpen);
      $("readMore").classList.toggle("open", S.descOpen);
      $("readLabel").textContent = S.descOpen ? "Leer menos" : "Leer más";
    });
  }

  /* ══════════════ CLIMA (Open-Meteo) ══════════════ */
  var WMO = { 0: "Soleado", 1: "Mayormente soleado", 2: "Parcialmente nublado", 3: "Nublado" };
  function wmoToCond(code) {
    if (WMO[code]) return WMO[code];
    if (code === 45 || code === 48) return "Mayormente nublado";
    if (code >= 51 && code <= 57) return "Chubascos";
    if (code >= 61 && code <= 67) return "Lluvia";
    if ((code >= 71 && code <= 77) || code === 85 || code === 86) return "Nieve";
    if (code >= 80 && code <= 82) return "Chubascos";
    if (code >= 95) return "Tormenta";
    return "Nublado";
  }
  var COND_PARTS = {
    "Soleado": ["cSunFull"], "Mayormente soleado": ["cSunFull", "cCloud2"],
    "Parcialmente nublado": ["cSunSmall", "cCloud"], "Mayormente nublado": ["cSunSmall", "cCloud", "cCloud2"],
    "Nublado": ["cCloud", "cCloud2"], "Chubascos": ["cSunSmall", "cCloud", "cRain"],
    "Lluvia": ["cCloud", "cRain"], "Nieve": ["cCloud", "cSnow"],
    "Tormenta": ["cCloud", "cBolt"], "Tornado": ["cTornado"]
  };

  function applyWeather(cur, daily, aqi) {
    // ── Temperatura ──
    var t = Math.round(cur.temperature_2m);
    var tempColor = "#f0b429", glow = "none", badge = "";
    if (t <= 0) { tempColor = "#2d7dd2"; glow = "drop-shadow(0 0 4px rgba(45,125,210,.75))"; badge = "❄"; }
    else if (t < 15) { tempColor = "#4a90b8"; }
    else if (t < 32) { tempColor = "#f0b429"; }
    else if (t <= 40) { tempColor = "#e8702a"; }
    else { tempColor = "#e02424"; glow = "drop-shadow(0 0 6px rgba(224,36,36,.9))"; }
    var mercPct = Math.max(0.08, Math.min(1, (t + 15) / 63));
    $("tempSvg").setAttribute("stroke", tempColor);
    $("tempMerc").setAttribute("y", (15 - 10 * mercPct).toFixed(2));
    $("tempMerc").setAttribute("height", (10 * mercPct).toFixed(2));
    $("tempMerc").setAttribute("fill", tempColor);
    $("tempDot").setAttribute("fill", tempColor);
    $("tempBadge").textContent = badge;
    $("tempWrap").style.filter = glow;
    $("tempVal").textContent = t + " °C";
    $("tempVal").style.color = (t <= 0 || t > 40) ? tempColor : "#0d1f27";

    // ── Condiciones ──
    var cond = wmoToCond(cur.weather_code);
    var parts = COND_PARTS[cond] || ["cSunFull"];
    ["cSunFull", "cSunSmall", "cCloud", "cCloud2", "cRain", "cSnow", "cBolt", "cTornado"].forEach(function (idp) {
      $(idp).style.transition = "opacity .3s";
      $(idp).setAttribute("opacity", parts.indexOf(idp) !== -1 ? "1" : "0");
    });
    $("condVal").textContent = cond;

    // ── Viento ──
    var v = cur.wind_speed_10m;
    var windColor = v < 5 ? "#8fa3ad" : (v < 20 ? "#2d7d9a" : (v < 40 ? "#d97706" : "#e02424"));
    $("windSvg").setAttribute("stroke", windColor);
    var dur = Math.max(0.35, 2.6 - v / 18).toFixed(2) + "s";
    document.querySelectorAll(".rz-gust").forEach(function (p) { p.style.animationDuration = dur; });
    $("windVal").textContent = v.toFixed(1) + " km/h";

    // ── Humedad ──
    var h = Math.max(0, Math.min(100, cur.relative_humidity_2m));
    $("humRect").setAttribute("y", (3 + (1 - h / 100) * 16.5).toFixed(2));
    $("humVal").textContent = h + " %";

    // ── UV ──
    var uv = Math.round(daily.uv_index_max[0] || 0);
    var uvColor = uv <= 2 ? "#2e9e4f" : (uv <= 5 ? "#f0b429" : (uv <= 7 ? "#f57c00" : (uv <= 10 ? "#e02424" : "#8e44ad")));
    var rays = Math.max(2, Math.min(8, 2 + Math.round(uv / 11 * 6)));
    $("uvSvg").setAttribute("stroke", uvColor);
    $("uvText").setAttribute("fill", uvColor);
    for (var i = 0; i < 8; i++) $("uvR" + i).setAttribute("opacity", i < rays ? "1" : "0.18");
    $("uvVal").textContent = String(uv);
    $("uvVal").style.color = uvColor;

    // ── Calidad del aire (US AQI) ──
    if (aqi != null) {
      var air = aqi <= 50 ? "Buena" : (aqi <= 100 ? "Regular" : "Mala");
      var airColor = air === "Buena" ? "#2e9e4f" : (air === "Regular" ? "#f0b429" : "#e02424");
      $("airSvg").setAttribute("stroke", airColor);
      $("airVal").textContent = air;
      $("airVal").style.color = airColor;
    } else { $("airVal").textContent = "—"; }

    // ── Salida / puesta de sol (hora local de la ciudad) ──
    var sr = daily.sunrise[0], ss = daily.sunset[0], nowIso = cur.time;
    $("sunriseVal").textContent = fmtTime(sr);
    $("sunsetVal").textContent = fmtTime(ss);
    var srM = new Date(sr).getTime(), ssM = new Date(ss).getTime(), nowM = new Date(nowIso).getTime();
    var f = Math.max(0, Math.min(1, (nowM - srM) / (ssM - srM)));
    var prog = (f * 100).toFixed(1), rem = (100 - f * 100).toFixed(1);
    var sx = (12 - 8 * Math.cos(Math.PI * f)).toFixed(2), sy = (17.5 - 8 * Math.sin(Math.PI * f)).toFixed(2);
    $("sunProgArc").setAttribute("stroke-dasharray", prog + " 100");
    $("sunRemArc").setAttribute("stroke-dasharray", rem + " 100");
    $("sunRemArc").setAttribute("stroke-dashoffset", "-" + prog);
    $("sunDot1").setAttribute("cx", sx); $("sunDot1").setAttribute("cy", sy);
    $("sunDot2").setAttribute("cx", sx); $("sunDot2").setAttribute("cy", sy);

    $("climaBlock").hidden = false;
  }

  async function loadWeather(lat, lng) {
    try {
      var fUrl = "https://api.open-meteo.com/v1/forecast?latitude=" + lat + "&longitude=" + lng +
        "&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m" +
        "&daily=sunrise,sunset,uv_index_max&timezone=auto&forecast_days=1";
      var aUrl = "https://air-quality-api.open-meteo.com/v1/air-quality?latitude=" + lat + "&longitude=" + lng +
        "&current=us_aqi&timezone=auto";
      var results = await Promise.allSettled([fetch(fUrl), fetch(aUrl)]);
      var fj = null, aj = null;
      if (results[0].status === "fulfilled" && results[0].value.ok) fj = await results[0].value.json();
      if (results[1].status === "fulfilled" && results[1].value.ok) aj = await results[1].value.json();
      if (!fj || !fj.current) return; // sin clima: bloque oculto
      applyWeather(fj.current, fj.daily, aj && aj.current ? aj.current.us_aqi : null);
    } catch (e) { /* bloque de clima permanece oculto */ }
  }

  function bindClima() {
    function setPage(p) {
      S.climaPage = p;
      $("climaTrack").classList.toggle("p2", p === 1);
      $("climaNext").hidden = p === 1;
      $("climaPrev").hidden = p === 0;
    }
    $("climaNext").addEventListener("click", function () { setPage(1); });
    $("climaPrev").addEventListener("click", function () { setPage(0); });
    var cx = 0, card = $("climaCard");
    card.addEventListener("touchstart", function (e) { cx = e.touches[0].clientX; }, { passive: true });
    card.addEventListener("touchend", function (e) {
      var dx = e.changedTouches[0].clientX - cx;
      if (Math.abs(dx) > 40) setPage(dx < 0 ? 1 : 0);
    });
  }

  /* ══════════════ CATEGORÍAS (Google Places) ══════════════ */
  var PLACE_TYPE = { hacer: "tourist_attraction", restaurantes: "restaurant", hoteles: "lodging" };
  var TYPE_ES = {
    museum: "Museo", art_gallery: "Galería de arte", park: "Parque", church: "Sitio religioso",
    shopping_mall: "Centro comercial", amusement_park: "Parque de diversiones", zoo: "Zoológico",
    aquarium: "Acuario", stadium: "Estadio", casino: "Casino", night_club: "Club nocturno",
    tourist_attraction: "Atracción turística", cafe: "Cafetería", bar: "Bar", bakery: "Panadería",
    restaurant: "Restaurante", meal_takeaway: "Comida para llevar", lodging: "Hotel",
    campground: "Campamento", rv_park: "Parque de casas rodantes"
  };

  function metaOf(r, tab) {
    var label = "";
    for (var i = 0; i < (r.types || []).length; i++) {
      if (TYPE_ES[r.types[i]]) { label = TYPE_ES[r.types[i]]; break; }
    }
    if (!label) label = tab === "hoteles" ? "Hotel" : (tab === "restaurantes" ? "Restaurante" : "Atracción turística");
    if (tab === "hoteles" && r.vicinity) return label + " · " + r.vicinity;
    return label;
  }

  function searchPlaces(tab) {
    return new Promise(function (resolve, reject) {
      places.nearbySearch({ location: S.center, radius: 12000, type: PLACE_TYPE[tab] }, function (res, status) {
        if (status === "OK") resolve(res || []);
        else if (status === "ZERO_RESULTS") resolve([]);
        else reject(new Error(status));
      });
    });
  }

  function buildItems(results, tab) {
    // Google incluye hoteles (lodging) en otras categorías cuando tienen
    // restaurante o atracciones; fuera de "hoteles" los excluimos.
    if (tab !== "hoteles") {
      results = results.filter(function (r) { return (r.types || []).indexOf("lodging") === -1; });
    }
    return results.slice(0, 9).map(function (r, i) {
      var photos = (r.photos || []).slice(0, 3).map(function (p) {
        return p.getUrl({ maxWidth: 640, maxHeight: 400 });
      });
      return {
        id: tab + "-" + (r.place_id || i),
        num: i + 1,
        name: r.name || "—",
        rating: r.rating || 0,
        reviews: r.user_ratings_total ? r.user_ratings_total.toLocaleString("es-MX") : "0",
        extra: (tab === "restaurantes" && r.price_level) ? "$".repeat(r.price_level) : "",
        meta: metaOf(r, tab),
        photos: photos,
        pos: r.geometry && r.geometry.location ? { lat: r.geometry.location.lat(), lng: r.geometry.location.lng() } : S.center
      };
    });
  }

  function skeletonShow(on) {
    var sk = $("skeleton");
    if (on && !sk.childElementCount) {
      var html = "";
      for (var i = 0; i < 6; i++) {
        html += '<div class="rz-skel"><div class="rz-skel__media"></div><div class="rz-skel__body">' +
          '<div class="rz-skel__line" style="width:65%"></div><div class="rz-skel__line" style="width:85%"></div>' +
          '<div class="rz-skel__line" style="width:45%"></div></div></div>';
      }
      sk.innerHTML = html;
    }
    sk.hidden = !on;
    $("grid").hidden = on;
  }

  function cardHtml(it, i) {
    var fav = !!S.favs[it.id];
    var ci = S.cardImg[it.id] || 0;
    var img = it.photos[ci] || it.photos[0] || "";
    var starPct = (it.rating / 5 * 100) + "%";
    var multi = it.photos.length > 1;
    var dots = "";
    if (multi) {
      for (var k = 0; k < it.photos.length; k++) dots += '<span class="' + (k === ci ? "on" : "") + '"></span>';
    }
    return '<article class="rz-card" data-id="' + esc(it.id) + '" tabindex="0" aria-label="' + esc(it.name) + '" style="animation-delay:' + (i * 0.04) + 's">' +
      '<div class="rz-card__media rz-shimmer">' +
        (img ? '<img src="' + esc(img) + '" alt="' + esc(it.name) + '" loading="lazy">' : "") +
        '<button class="rz-card__fav' + (fav ? " on" : "") + '" aria-label="Guardar en favoritos">' +
          '<svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="' + (fav ? "#ef4444" : "none") + '" stroke="' + (fav ? "#ef4444" : "#33454e") + '" stroke-width="1.8" stroke-linejoin="round"/></svg>' +
        "</button>" +
        (multi ?
          '<button class="rz-card__nav rz-card__nav--prev" aria-label="Imagen anterior"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>' +
          '<button class="rz-card__nav rz-card__nav--next" aria-label="Imagen siguiente"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>' +
          '<div class="rz-card__dots">' + dots + "</div>"
        : "") +
      "</div>" +
      '<div class="rz-card__body">' +
        '<h3 class="rz-card__name">' + esc(it.name) + "</h3>" +
        '<div class="rz-card__rating">' +
          "<b>" + (it.rating ? (it.rating % 1 ? it.rating.toFixed(1) : String(it.rating)) : "—") + "</b>" +
          '<span class="rz-stars">★★★★★<span style="width:' + starPct + '">★★★★★</span></span>' +
          '<span class="rz-card__reviews">(' + esc(it.reviews) + ")</span>" +
          (it.extra ? "<span>" + esc(it.extra) + "</span>" : "") +
        "</div>" +
        '<div class="rz-card__meta"><span>' + esc(it.meta) + "</span></div>" +
      "</div></article>";
  }

  function itemById(id) {
    for (var i = 0; i < S.items.length; i++) if (S.items[i].id === id) return S.items[i];
    return null;
  }
  function markerById(id) {
    for (var i = 0; i < S.markers.length; i++) if (S.markers[i]._id === id) return S.markers[i];
    return null;
  }

  function setActive(id, on) {
    // tarjeta
    var card = document.querySelector('.rz-card[data-id="' + CSS.escape(id) + '"]');
    if (card) card.classList.toggle("hl", on || S.infoId === id);
    // pin
    var m = markerById(id);
    if (m) {
      var act = on || S.infoId === id;
      m.setIcon(pinIcon(act ? PIN_ACT : PIN, act));
      m.setZIndex(act ? 40 : 10);
    }
  }

  function openInfo(id) {
    var it = itemById(id), m = markerById(id);
    if (!it || !m) return;
    var prev = S.infoId;
    S.infoId = id;
    if (prev && prev !== id) setActive(prev, false);
    var starPct = (it.rating / 5 * 100) + "%";
    infoWin.setContent(
      '<div class="rz-info">' +
        '<p class="rz-info__name">' + esc(it.name) + "</p>" +
        '<div class="rz-info__row"><b>' + (it.rating ? it.rating.toFixed(1) : "—") + "</b>" +
        '<span class="rz-stars" style="font-size:11.5px">★★★★★<span style="width:' + starPct + '">★★★★★</span></span></div>' +
        '<p class="rz-info__meta">' + esc(it.meta) + (it.extra ? " · " + esc(it.extra) : "") + "</p>" +
        '<button class="rz-info__close" aria-label="Cerrar"><svg width="12" height="12" viewBox="0 0 640 640" fill="currentColor"><path d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"></path></svg></button>' +
      "</div>"
    );
    infoWin.open({ map: map, anchor: m });
    setActive(id, true);
    google.maps.event.addListenerOnce(infoWin, "domready", function () {
      var btn = document.querySelector(".rz-info__close");
      if (btn) btn.addEventListener("click", closeInfo);
    });
  }
  function closeInfo() {
    var prev = S.infoId;
    S.infoId = null;
    infoWin.close();
    if (prev) setActive(prev, false);
  }

  function scrollToCard(id) {
    var el = document.querySelector('.rz-card[data-id="' + CSS.escape(id) + '"]');
    if (!el) return;
    var y = el.getBoundingClientRect().top + window.scrollY - 140;
    window.scrollTo({ top: y, behavior: "smooth" });
  }

  function renderCards() {
    var grid = $("grid");
    grid.innerHTML = S.items.map(cardHtml).join("");
    $("catEmpty").hidden = S.items.length > 0;

    grid.querySelectorAll(".rz-card").forEach(function (card) {
      var id = card.getAttribute("data-id");
      card.addEventListener("mouseenter", function () { S.hoverId = id; setActive(id, true); });
      card.addEventListener("mouseleave", function () { if (S.hoverId === id) S.hoverId = null; setActive(id, false); });
      card.addEventListener("focus", function () { S.hoverId = id; setActive(id, true); });
      card.addEventListener("blur", function () { if (S.hoverId === id) S.hoverId = null; setActive(id, false); });
      card.addEventListener("click", function (e) {
        if (e.target.closest(".rz-card__fav") || e.target.closest(".rz-card__nav")) return;
        openInfo(id);
      });
      card.addEventListener("keydown", function (e) { if (e.key === "Enter") openInfo(id); });

      var fav = card.querySelector(".rz-card__fav");
      fav.addEventListener("click", function (e) {
        e.stopPropagation();
        S.favs[id] = !S.favs[id];
        if (!S.favs[id]) delete S.favs[id];
        try { localStorage.setItem("rn_favs_" + UID, JSON.stringify(S.favs)); } catch (er) {}
        var on = !!S.favs[id];
        fav.classList.remove("on"); void fav.offsetWidth;
        fav.classList.toggle("on", on);
        var path = fav.querySelector("path");
        path.setAttribute("fill", on ? "#ef4444" : "none");
        path.setAttribute("stroke", on ? "#ef4444" : "#33454e");
      });

      var prev = card.querySelector(".rz-card__nav--prev");
      var next = card.querySelector(".rz-card__nav--next");
      function stepImg(d) {
        var it = itemById(id); if (!it || it.photos.length < 2) return;
        var cur = S.cardImg[id] || 0;
        var ni = (cur + d + it.photos.length) % it.photos.length;
        S.cardImg[id] = ni;
        card.querySelector(".rz-card__media img").src = it.photos[ni];
        card.querySelectorAll(".rz-card__dots span").forEach(function (s, k) { s.classList.toggle("on", k === ni); });
      }
      if (prev) prev.addEventListener("click", function (e) { e.stopPropagation(); stepImg(-1); });
      if (next) next.addEventListener("click", function (e) { e.stopPropagation(); stepImg(1); });
    });
  }

  function renderMarkers() {
    S.markers.forEach(function (m) { m.setMap(null); });
    S.markers = [];
    var bounds = new google.maps.LatLngBounds();
    S.items.forEach(function (it) {
      var m = new google.maps.Marker({
        position: it.pos, map: map,
        icon: pinIcon(PIN, false),
        label: { text: String(it.num), color: "#fff", fontSize: "12.5px", fontWeight: "700", fontFamily: "Poppins, sans-serif" },
        title: it.name
      });
      m._id = it.id;
      m.addListener("mouseover", function () { S.hoverId = it.id; setActive(it.id, true); });
      m.addListener("mouseout", function () { if (S.hoverId === it.id) S.hoverId = null; setActive(it.id, false); });
      m.addListener("click", function () { openInfo(it.id); scrollToCard(it.id); });
      S.markers.push(m);
      bounds.extend(it.pos);
    });
    if (S.items.length) {
      map.fitBounds(bounds, { top: 60, right: 60, bottom: 60, left: 60 });
      google.maps.event.addListenerOnce(map, "bounds_changed", function () {
        if (map.getZoom() > 15) map.setZoom(15);
      });
    }
  }

  async function changeTab(tab, force) {
    if (!force && tab === S.tab && S.items.length) return;
    S.tab = tab;
    document.querySelectorAll(".rz-tab").forEach(function (b) {
      b.classList.toggle("on", b.getAttribute("data-tab") === tab);
    });
    closeInfo();
    skeletonShow(true);
    $("catEmpty").hidden = true;

    try {
      if (!S.cache[tab]) S.cache[tab] = buildItems(await searchPlaces(tab), tab);
      S.items = S.cache[tab];
      skeletonShow(false);
      renderCards();
      renderMarkers();
    } catch (err) {
      skeletonShow(false);
      $("grid").innerHTML = "";
      S.items = [];
      renderMarkers();
      $("catEmpty").hidden = false;
      $("catEmptyMsg").textContent = /REQUEST_DENIED/.test(err.message)
        ? "Google Places rechazó la solicitud. Habilita la 'Places API' para esta key en Google Cloud."
        : "No se pudieron cargar los resultados (" + err.message + "). Intenta de nuevo.";
    }
  }

  function bindTabs() {
    document.querySelectorAll(".rz-tab").forEach(function (b) {
      b.addEventListener("click", function () { changeTab(b.getAttribute("data-tab")); });
    });
    // sombra al quedarse pegado (IntersectionObserver con centinela)
    var bar = $("tabsBar");
    var sentinel = document.createElement("div");
    bar.parentNode.insertBefore(sentinel, bar);
    new IntersectionObserver(function (entries) {
      bar.classList.toggle("stuck", !entries[0].isIntersecting);
    }, { rootMargin: "-57px 0px 0px 0px" }).observe(sentinel);
  }

  /* ══════════════ MAPA: expandir / zoom / modal móvil ══════════════ */
  function bindMapControls() {
    $("expandBtn").addEventListener("click", function () {
      if (S.narrow) { closeMapModal(); return; }
      S.mapFull = !S.mapFull;
      var panel = $("mapPanel");
      if (S.mapFull) {
        window.scrollTo({ top: 0 });
        document.body.style.overflow = "hidden";
        panel.classList.add("full");
        $("expandIcon").setAttribute("d", "M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5");
      } else {
        document.body.style.overflow = "";
        panel.classList.remove("full");
        panel.style.left = "";
        fixMapLeft();
        $("expandIcon").setAttribute("d", "M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5");
      }
      setTimeout(mapResized, 470);
    });
    $("zIn").addEventListener("click", function () { map.setZoom(map.getZoom() + 1); });
    $("zOut").addEventListener("click", function () { map.setZoom(map.getZoom() - 1); });
    $("recenterBtn").addEventListener("click", function () { if (S.center) { map.panTo(S.center); } });
    $("verMapa").addEventListener("click", openMapModal);
    $("mapClose").addEventListener("click", closeMapModal);
  }

  function openMapModal() {
    S.mapModal = true;
    document.body.classList.add("rz-mapmodal");
    document.body.style.overflow = "hidden";
    $("verMapa").hidden = true;
    $("mapClose").hidden = false;
    setTimeout(mapResized, 100);
  }
  function closeMapModal() {
    S.mapModal = false;
    document.body.classList.remove("rz-mapmodal");
    document.body.style.overflow = "";
    $("mapClose").hidden = true;
    updateResponsive();
  }

  /* ══════════════ Responsive / teclado ══════════════ */
  function updateResponsive() {
    S.narrow = window.matchMedia("(max-width: 1080px)").matches;
    S.mobile = window.matchMedia("(max-width: 640px)").matches;
    $("verMapa").hidden = !(S.narrow && !S.mapModal) || !qs;
    if (!S.narrow && S.mapModal) closeMapModal();
    if (!S.narrow) fixMapLeft();
  }

  function bindGlobal() {
    window.addEventListener("resize", function () { updateResponsive(); fixMapLeft(); });
    window.addEventListener("keydown", function (e) {
      if (/INPUT|TEXTAREA|SELECT/.test((e.target.tagName || ""))) return;
      if (e.key === "ArrowRight") stepHero(1);
      if (e.key === "ArrowLeft") stepHero(-1);
      if (e.key === "Escape") {
        if (S.mapModal) closeMapModal();
        if (S.mapFull) $("expandBtn").click();
        if (S.infoId) closeInfo();
        document.body.classList.add("sidebar-collapsed");
      }
    });
  }

  /* ══════════════ Init ══════════════ */
  document.addEventListener("DOMContentLoaded", function () {
    bindHero(); bindSave(); bindDesc(); bindClima(); bindTabs(); bindGlobal();
    updateResponsive();
    boot().then(function () {
      if (window.google && window.google.maps && map) bindMapControls();
      fixMapLeft();
    });
  });
})();
