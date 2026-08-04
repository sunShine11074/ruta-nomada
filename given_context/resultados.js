/* ============================================================
   resultados.js — Ruta Nómada · Página de resultados de búsqueda
   Mapa interactivo (Leaflet) sincronizado con la lista de resultados.
   Nota: el mapa usa teselas claras sin clave; se puede sustituir por
   la integración de Google Maps Platform existente sin cambiar el layout.
============================================================ */
(function () {
  "use strict";

  /* ── Categorías (colores tomados de style.css) ─────────────── */
  const CAT = {
    cultura:        { label: "Cultura",        color: "#4a7c6b" },
    romance:        { label: "Romance",        color: "#c47a7a" },
    aventura:       { label: "Aventura",       color: "#7a8c5a" },
    descubrimiento: { label: "Descubrimiento", color: "#8a7a5a" },
  };

  /* ── Catálogo de destinos ──────────────────────────────────── */
  const DESTINATIONS = [
    { id:"kyoto",     name:"Kyoto",              country:"Japón",      cat:"cultura",        lat:35.0116,  lng:135.7681, rating:4.9, reviews:1204, price:24800, desc:"Templos serenos, jardines zen e historia milenaria en la antigua capital imperial.", award:true },
    { id:"santorini", name:"Santorini",          country:"Grecia",     cat:"romance",        lat:36.3932,  lng:25.4615,  rating:4.8, reviews:2310, price:31200, desc:"Casas blancas sobre el Egeo y las puestas de sol más célebres del Mediterráneo.", award:true },
    { id:"patagonia", name:"Patagonia",          country:"Argentina",  cat:"aventura",       lat:-49.3314, lng:-72.8860, rating:4.9, reviews:876,  price:28900, desc:"Glaciares, montañas y senderos salvajes en el fin del mundo.", award:true },
    { id:"marrakech", name:"Marrakech",          country:"Marruecos",  cat:"descubrimiento", lat:31.6295,  lng:-7.9811,  rating:4.7, reviews:1542, price:19500, desc:"Zocos vibrantes, palacios y el bullicio mágico de la medina." },
    { id:"cancun",    name:"Cancún",             country:"México",     cat:"descubrimiento", lat:21.1619,  lng:-86.8515, rating:4.8, reviews:3987, price:8500,  desc:"Playas turquesa, cultura maya y una vida nocturna inagotable." },
    { id:"amalfi",    name:"Costa Amalfitana",   country:"Italia",     cat:"romance",        lat:40.6340,  lng:14.6027,  rating:4.9, reviews:1689, price:33400, desc:"Pueblos colgados del mar, limoncello y carreteras de postal." },
    { id:"baviera",   name:"Selva de Baviera",   country:"Alemania",   cat:"aventura",       lat:48.9460,  lng:13.3960,  rating:4.6, reviews:512,  price:26100, desc:"Senderos entre niebla, castillos de cuento y aire de pino." },
    { id:"lisboa",    name:"Lisboa",             country:"Portugal",   cat:"cultura",        lat:38.7223,  lng:-9.1393,  rating:4.8, reviews:2741, price:22700, desc:"Tranvías amarillos, azulejos y miradores sobre el Tajo." },
    { id:"reikiavik", name:"Reikiavik",          country:"Islandia",   cat:"aventura",       lat:64.1466,  lng:-21.9426, rating:4.7, reviews:933,  price:35600, desc:"Auroras boreales, géiseres y paisajes volcánicos de otro planeta." },
    { id:"praga",     name:"Praga",              country:"Chequia",    cat:"cultura",        lat:50.0755,  lng:14.4378,  rating:4.8, reviews:2105, price:18900, desc:"Callejones medievales, el puente de Carlos y un castillo sobre la ciudad." },
    { id:"ubud",      name:"Ubud, Bali",         country:"Indonesia",  cat:"descubrimiento", lat:-8.5069,  lng:115.2625, rating:4.7, reviews:1876, price:21300, desc:"Arrozales en terraza, templos en la selva y bienestar tropical." },
    { id:"estambul",  name:"Estambul",           country:"Turquía",    cat:"cultura",        lat:41.0082,  lng:28.9784,  rating:4.8, reviews:2588, price:17800, desc:"Donde Europa y Asia se encuentran: bazares, mezquitas y el Bósforo." },
  ];

  /* ── SVG helpers ───────────────────────────────────────────── */
  const svg = {
    heart: '<svg viewBox="0 0 24 24"><path d="M12 21s-7.5-4.6-10-9.2C.7 9 1.8 5.3 5.2 4.6 7.4 4.1 9.3 5.1 12 8c2.7-2.9 4.6-3.9 6.8-3.4C22.2 5.3 23.3 9 22 11.8 19.5 16.4 12 21 12 21z"/></svg>',
    star:  '<svg viewBox="0 0 24 24"><path d="M12 3.2l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9 6.8 19.3l1-5.8L3.5 9.4l5.9-.9z"/></svg>',
    trophy:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h12v3a6 6 0 0 1-12 0z"/><path d="M6 5H3v2a3 3 0 0 0 3 3M18 5h3v2a3 3 0 0 1-3 3"/><path d="M9 17h6M10 17v-2M14 17v-2M8 21h8"/></svg>',
    pin:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6 7-11a7 7 0 0 0-14 0c0 5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
  };

  const fmt = (n) => n.toLocaleString("es-MX");

  /* ── Estado ────────────────────────────────────────────────── */
  let activeCat = "todos";
  let query = "";
  let map, markersLayer;
  const markers = {};   // id -> L.marker

  /* ── Filtro ────────────────────────────────────────────────── */
  function getFiltered() {
    const q = query.trim().toLowerCase();
    return DESTINATIONS.filter((d) => {
      const okCat = activeCat === "todos" || d.cat === activeCat;
      const okQ = !q || (d.name + " " + d.country + " " + CAT[d.cat].label + " " + d.desc).toLowerCase().includes(q);
      return okCat && okQ;
    });
  }

  /* ── Render de tarjetas ────────────────────────────────────── */
  function cardHtml(d, idx) {
    const c = CAT[d.cat];
    return `
      <article class="rcard" data-id="${d.id}" tabindex="0">
        <div class="rcard__media tag-bg--${d.cat}">
          <span class="rcard__num" style="background:${c.color}">${idx + 1}</span>
          <button class="rcard__fav" aria-label="Guardar destino">${svg.heart}</button>
          ${d.award ? '<span class="rcard__award">★ Favorito 2026</span>' : ""}
          <span class="rcard__media-ico">${svg.pin}</span>
          <span class="rcard__media-label">${d.name}</span>
        </div>
        <div class="rcard__body">
          <span class="rcard__country">${d.country}</span>
          <h3 class="rcard__name">${d.name}</h3>
          <div class="rcard__rating">
            <span class="rcard__stars">${svg.star}</span>
            <b>${d.rating.toFixed(1)}</b>
            <span class="rcard__reviews">(${fmt(d.reviews)})</span>
          </div>
          <p class="rcard__desc">${d.desc}</p>
          <div class="rcard__foot">
            <span class="rcard__cat" style="color:${c.color}">
              <span class="rcard__dot" style="background:${c.color}"></span>${c.label}
            </span>
            <span class="rcard__price"><i>Desde</i> $${fmt(d.price)} MXN</span>
          </div>
        </div>
      </article>`;
  }

  function renderCards(list) {
    const grid = document.getElementById("grid");
    const empty = document.getElementById("empty");
    if (!list.length) {
      grid.innerHTML = "";
      empty.hidden = false;
      return;
    }
    empty.hidden = true;
    grid.innerHTML = list.map(cardHtml).join("");

    // hover / focus sync card -> marker
    grid.querySelectorAll(".rcard").forEach((card) => {
      const id = card.dataset.id;
      const enter = () => highlight(id, true);
      const leave = () => highlight(id, false);
      card.addEventListener("mouseenter", enter);
      card.addEventListener("mouseleave", leave);
      card.addEventListener("focus", enter);
      card.addEventListener("blur", leave);
      card.addEventListener("click", (e) => {
        if (e.target.closest(".rcard__fav")) return;
        focusMarker(id);
      });
    });
    grid.querySelectorAll(".rcard__fav").forEach((b) =>
      b.addEventListener("click", (e) => {
        e.stopPropagation();
        b.classList.toggle("is-on");
      })
    );
  }

  /* ── Mapa ──────────────────────────────────────────────────── */
  function makeIcon(d, idx) {
    return L.divIcon({
      className: "rn-pin-wrap",
      html: `<div class="rn-pin" data-id="${d.id}" style="--c:${CAT[d.cat].color}">${idx + 1}</div>`,
      iconSize: [28, 28],
      iconAnchor: [14, 14],
      popupAnchor: [0, -15],
    });
  }

  function popupHtml(d) {
    const c = CAT[d.cat];
    return `
      <div class="rn-pop" data-id="${d.id}">
        <div class="rn-pop__media" style="background:${c.color}">${d.name.charAt(0)}</div>
        <div class="rn-pop__body">
          <div class="rn-pop__cat" style="color:${c.color}">${c.label}</div>
          <div class="rn-pop__name">${d.name}</div>
          <div class="rn-pop__meta">${svg.star} ${d.rating.toFixed(1)} · ${d.country}</div>
          <div class="rn-pop__price">Desde <b>$${fmt(d.price)} MXN</b></div>
        </div>
      </div>`;
  }

  function initMap() {
    map = L.map("map", { zoomControl: false, scrollWheelZoom: true, worldCopyJump: true })
      .setView([30, 5], 2);
    L.tileLayer("https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png", {
      attribution: '&copy; OpenStreetMap &copy; CARTO',
      subdomains: "abcd",
      maxZoom: 19,
    }).addTo(map);
    L.control.zoom({ position: "bottomright" }).addTo(map);
    markersLayer = L.layerGroup().addTo(map);
  }

  function renderMarkers(list) {
    markersLayer.clearLayers();
    for (const k in markers) delete markers[k];

    list.forEach((d, idx) => {
      const m = L.marker([d.lat, d.lng], { icon: makeIcon(d, idx) }).addTo(markersLayer);
      m.bindPopup(popupHtml(d), { closeButton: false, autoClose: false, autoPan: false, maxWidth: 260, offset: [0, 4] });
      m.on("mouseover", () => { highlightCard(d.id, true); m.openPopup(); bringFront(d.id, true); });
      m.on("mouseout",  () => { highlightCard(d.id, false); m.closePopup(); bringFront(d.id, false); });
      m.on("click", () => scrollToCard(d.id));
      markers[d.id] = m;
    });

    // fit to visible markers
    if (list.length) {
      const group = L.featureGroup(Object.values(markers));
      map.fitBounds(group.getBounds().pad(0.25), { animate: true });
    }
    document.getElementById("mapCount").textContent = list.length;
  }

  /* ── Sincronización tarjeta ↔ marcador ─────────────────────── */
  function pinEl(id) { const m = markers[id]; return m && m.getElement ? m.getElement().querySelector(".rn-pin") : null; }

  function highlight(id, on) {        // card -> marker
    const el = pinEl(id);
    if (el) el.classList.toggle("is-active", on);
    const m = markers[id];
    if (m) { on ? m.openPopup() : m.closePopup(); m.setZIndexOffset(on ? 1000 : 0); }
  }
  function highlightCard(id, on) {    // marker -> card
    const card = document.querySelector(`.rcard[data-id="${id}"]`);
    if (card) card.classList.toggle("is-hl", on);
  }
  function bringFront(id, on) { const m = markers[id]; if (m) m.setZIndexOffset(on ? 1000 : 0); const el = pinEl(id); if (el) el.classList.toggle("is-active", on); }

  function focusMarker(id) {
    const m = markers[id];
    if (!m) return;
    map.flyTo(m.getLatLng(), Math.max(map.getZoom(), 5), { duration: 0.6 });
    setTimeout(() => m.openPopup(), 650);
  }
  function scrollToCard(id) {
    const card = document.querySelector(`.rcard[data-id="${id}"]`);
    if (!card) return;
    const top = card.getBoundingClientRect().top + window.scrollY - 90;
    window.scrollTo({ top, behavior: "smooth" });
    card.classList.add("is-hl");
    setTimeout(() => card.classList.remove("is-hl"), 1400);
  }

  /* ── Aplicar filtros ───────────────────────────────────────── */
  function apply(refit) {
    const list = getFiltered();
    renderCards(list);
    renderMarkers(list);
    const count = document.getElementById("rcount");
    count.textContent = list.length;
    const q = query.trim();
    const lbl = document.getElementById("queryLabel");
    lbl.textContent = q ? ` para “${q}”` : "";
  }

  /* ── Eventos UI ────────────────────────────────────────────── */
  function bindUI() {
    // chips de categoría
    document.querySelectorAll(".cat-btn").forEach((btn) =>
      btn.addEventListener("click", () => {
        document.querySelectorAll(".cat-btn").forEach((b) => b.classList.remove("cat-btn--active"));
        btn.classList.add("cat-btn--active");
        activeCat = btn.dataset.cat;
        apply();
      })
    );
    // búsqueda (topbar)
    const top = document.getElementById("topSearch");
    top.addEventListener("input", (e) => { query = e.target.value; apply(); });

    // refrescar
    document.getElementById("refreshBtn").addEventListener("click", (e) => {
      e.currentTarget.classList.add("is-spin");
      setTimeout(() => e.currentTarget.classList.remove("is-spin"), 600);
      apply(true);
    });

    // expandir mapa
    document.getElementById("expandBtn").addEventListener("click", () => {
      document.getElementById("mapPane").classList.toggle("is-expanded");
      document.body.classList.toggle("map-expanded");
      setTimeout(() => map.invalidateSize(), 260);
    });

    // sidebar (mismo comportamiento que el resto de la app)
    const open = () => document.body.classList.remove("sidebar-collapsed");
    const close = () => document.body.classList.add("sidebar-collapsed");
    document.getElementById("sidebarFab").addEventListener("click", open);
    document.getElementById("sideClose").addEventListener("click", close);
    document.getElementById("sideScrim").addEventListener("click", close);
  }

  /* ── Init ──────────────────────────────────────────────────── */
  document.addEventListener("DOMContentLoaded", () => {
    initMap();
    bindUI();
    apply();
    setTimeout(() => map.invalidateSize(), 200);
  });
})();
