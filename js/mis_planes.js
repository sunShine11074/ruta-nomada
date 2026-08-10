/* ============================================================
   js/mis_planes.js — Mis planes | Ruta Nómada

   Una sola fuente de verdad: el índice del plan seleccionado. Tabla,
   tarjetas, mapa y paginador son cuatro maneras de moverlo, y el panel
   derecho es lo único que lo lee. Así no hay que sincronizar cada vista
   con las demás: todas escriben en el mismo sitio.
   ============================================================ */
(function () {
  'use strict';

  var P = window.MP_PLANES || [];
  var ICO = window.MP_ICONOS || {};
  if (!P.length) return;

  var sel = 0;               // índice del plan seleccionado
  var mapa = null, marcadores = [], mapaListo = false;
  // Zoom con el que se abre el mapa sobre el plan elegido: a 11 se ve la
  // ciudad del destino con algo de alrededor. Después manda el usuario,
  // porque cambiar de plan sólo desplaza el mapa y no toca el zoom.
  var ZOOM_PLAN = 11;

  function $(id) { return document.getElementById(id); }
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  /* ── Selección ─────────────────────────────────────────── */
  function seleccionar(i, origen) {
    if (i < 0 || i >= P.length) return;
    sel = i;
    document.querySelectorAll('[data-idx]').forEach(function (el) {
      el.setAttribute('aria-selected', Number(el.getAttribute('data-idx')) === i ? 'true' : 'false');
    });
    pintarFicha();
    pintarPager();
    marcarPin();
    // Si el cambio vino del panel o del mapa, traer a la vista el
    // elemento correspondiente de la lista para no perderlo de vista.
    if (origen !== 'lista') {
      var el = document.querySelector('.mp-vista:not(.mp-oculto) [data-idx="' + i + '"]');
      if (el && el.scrollIntoView) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    if (origen !== 'mapa' && mapa) centrarEn(i);
  }

  function pintarPager() {
    var n = $('mpPagerN');
    if (n) n.textContent = (sel + 1) + ' de ' + P.length;
    var a = $('mpPrev'), b = $('mpNext');
    if (a) a.disabled = sel === 0;
    if (b) b.disabled = sel === P.length - 1;
  }

  /* ── Ficha del panel ───────────────────────────────────── */
  function pintarFicha() {
    var p = P[sel];
    var host = $('mpFicha');
    if (!host) return;

    var portada = p.portTipo === 'img'
      ? '<img src="' + esc(p.portVal) + '" alt="" onerror="this.style.display=\'none\'">'
      : '<span class="mp-cover__grad" style="background:linear-gradient(135deg,#0E2A33,#3e7986)">' + esc(p.portVal) + '</span>';

    var coords = (p.lat !== null && p.lng !== null)
      ? '[Lat: ' + p.lat.toFixed(7) + ', Lng: ' + p.lng.toFixed(7) + ']'
      : 'Sin coordenadas';

    var esProp = p.rol === 'propietario';

    /* Dos bloques y no uno: lo que va dentro de .mp-ficha__scroll se
       desplaza si no cabe, y .mp-acciones queda fuera, pegado al fondo del
       panel. Los botones tienen que verse siempre sin buscarlos. */
    host.innerHTML =
      '<div class="mp-ficha__scroll">' +
      '<div class="mp-cover">' + portada +
        // La chapita de la esquina es la marca del sitio, como en los frames
        '<span class="mp-cover__badge"><img src="img/logo.png" alt="" width="17" height="17"></span>' +
      '</div>' +
      '<span class="mp-chip" style="background:' + esc(p.estColor) + '">' + esc(p.estTexto) + '</span>' +
      '<h2 class="mp-side__t">' + esc(p.nombre) + '</h2>' +

      '<div class="mp-grupo"><p class="mp-grupo__t">Destino:</p>' +
        // Chincheta y no marcador: en el frame el nombre del destino
        // lleva la chincheta y el marcador rojo se reserva para la
        // cuenta de sitios, que es lo que de verdad hay en el mapa.
        '<span class="mp-dato">' + (ICO.chincheta || '') + esc(p.destino || '—') + '</span>' +
        '<span class="mp-dato">' + (ICO.brujula || '') + esc(coords) + '</span>' +
        '<span class="mp-dato">' + (ICO.sitios || '') + p.lugares + ' sitios añadidos</span>' +
      '</div>' +

      '<div class="mp-grupo"><p class="mp-grupo__t">Fechas de inicio y fin:</p>' +
        '<span class="mp-dato">' + (ICO.calendario || '') + esc(p.fechas) + '</span>' +
      '</div>' +

      '<div class="mp-grupo"><p class="mp-grupo__t">Participantes:</p>' + p.avatares + '</div>' +

      '<div class="mp-grupo"><p class="mp-grupo__t">Presupuesto y gastos:</p>' +
        '<span class="mp-dato">' + (ICO.cerdito || '') +
          (p.presup ? '<b>' + esc(p.presup) + '</b> <small>Presupuesto asignado</small>'
                    : '<span class="mp-vacio">Sin presupuesto asignado</span>') + '</span>' +
        '<span class="mp-dato">' + (ICO.recibo || '') +
          (p.nGastos ? '<b>' + p.nGastos + ' gastos</b> <small>Gastos definidos</small>'
                     : '<span class="mp-vacio">Sin gastos registrados</span>') + '</span>' +
        (p.nGastos ? '<span class="mp-dato">' + (ICO.transferir || '') +
          '<b>' + esc(p.totGastos) + '</b> <small>Total de gastos</small></span>' : '') +
      '</div>' +

      '<div class="mp-grupo"><p class="mp-grupo__t">Última modificación</p>' +
        '<span class="mp-dato">' + (ICO.lapiz || '') + '<b>' + esc(p.modifGuion) + '</b></span>' +
      '</div>' +
      '</div>' +

      /* Los dos botones miden 145x35 y el texto NO puede partirse, así
         que sólo caben dos piezas: un icono y una etiqueta. En el de
         editar el icono es el pen-to-square, que ya dibuja su propio
         recuadro —no hace falta ponerle otro detrás—. En el de borrar la
         papelera va DENTRO del aro que se rellena de amarillo, no al
         lado: con las dos piezas separadas el texto se iba a dos líneas.
         El aro mide 26 de fuera a fuera (r 11,5 + 1,5 de trazo), y de ahí
         sale data-len = 2·π·11,5 = 72,26, que es lo que recorre la
         animación de la pulsación. */
      '<div class="mp-acciones">' +
        '<a class="mp-btn" href="plan.php?id=' + p.id + '">' +
          (ICO.lapizBlanco || '') + '<span>Editar plan</span></a>' +
        '<button type="button" class="mp-btn" id="mpDel" ' +
          'aria-describedby="mpDelAyuda">' +
          '<span class="mp-btn__aro">' +
            '<svg width="26" height="26" viewBox="0 0 26 26" style="position:absolute;inset:0;transform:rotate(-90deg)">' +
              '<circle cx="13" cy="13" r="11.5" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="3"></circle>' +
              '<circle data-fill="1" data-len="72.26" cx="13" cy="13" r="11.5" fill="none" stroke="#f0b429" ' +
                'stroke-width="3" stroke-linecap="round" stroke-dasharray="72.26" stroke-dashoffset="72.26"></circle>' +
            '</svg>' +
            (ICO.papelera || '') +
          '</span>' +
          '<span>' + (esProp ? 'Eliminar plan' : 'Salir del plan') + '</span>' +
        '</button>' +
        '<span class="mp-ayuda" id="mpDelAyuda">Mantén pulsado para ' + (esProp ? 'eliminar' : 'salir') + '</span>' +
      '</div>';

    enlazarBorrado();
  }

  /* ── Borrado por pulsación ─────────────────────────────────
     Mismo mecanismo que el «Guardar cambios» de profile.php (aro SVG,
     stroke-dashoffset, requestAnimationFrame), con tres diferencias que
     pide el que la acción sea destructiva:
       · cinco segundos en vez de dos,
       · sólo el botón primario del ratón y sólo el puntero principal —
         sin esto, un segundo dedo apoyado en la pantalla también
         arrancaría el relleno,
       · setPointerCapture, para que soltar fuera del botón cancele de
         verdad en lugar de dejar la animación colgada.
     Y un camino de teclado, que el original no tiene: Enter o Espacio
     mantenidos rellenan igual.                                        */
  var HOLD_MS = 5000;
  var motor = null;

  function enlazarBorrado() {
    var btn = $('mpDel');
    if (!btn) return;
    btn.addEventListener('pointerdown', function (e) {
      if (e.button !== 0 || !e.isPrimary) return;
      e.preventDefault();
      try { btn.setPointerCapture(e.pointerId); } catch (_) {}
      empezar(btn);
    });
    ['pointerup', 'pointerleave', 'pointercancel'].forEach(function (ev) {
      btn.addEventListener(ev, function () { soltar(); });
    });
    btn.addEventListener('keydown', function (e) {
      if ((e.key === 'Enter' || e.key === ' ') && !e.repeat) { e.preventDefault(); empezar(btn); }
    });
    btn.addEventListener('keyup', function (e) {
      if (e.key === 'Enter' || e.key === ' ') soltar();
    });
    btn.addEventListener('blur', function () { soltar(); });
  }

  function empezar(btn) {
    soltar(true);
    var aro = btn.querySelector('[data-fill]');
    if (!aro) return;
    var len = parseFloat(aro.getAttribute('data-len')) || 94.2;
    var t0 = performance.now();
    var m = { aro: aro, len: len, vivo: true, raf: null, p: 0 };
    (function paso(now) {
      if (!m.vivo) return;
      m.p = Math.min((now - t0) / HOLD_MS, 1);
      aro.style.strokeDashoffset = String(len * (1 - m.p));
      if (m.p >= 1) { m.vivo = false; motor = null; confirmar(); return; }
      m.raf = requestAnimationFrame(paso);
    })(t0);
    motor = m;
  }

  function soltar(silencio) {
    var m = motor;
    if (!m) return;
    if (m.raf) cancelAnimationFrame(m.raf);
    m.vivo = false; motor = null;
    if (silencio) return;
    var aro = m.aro, len = m.len, p = m.p, t0 = performance.now();
    var dur = Math.max(160, p * 420);
    (function volver(now) {
      var k = Math.min((now - t0) / dur, 1);
      aro.style.strokeDashoffset = String(len * (1 - p * (1 - k)));
      if (k < 1) requestAnimationFrame(volver);
    })(t0);
  }

  function confirmar() {
    var p = P[sel];
    var btn = $('mpDel');
    if (btn) { btn.disabled = true; btn.style.opacity = '.6'; }
    fetch('api/plan_delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ plan_id: p.id, csrf: window.MP_CSRF })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j || !j.ok) throw new Error((j && j.error) || 'Error');
        // Recargar es lo honesto: se han ido tarjeta, fila, pin y la
        // numeración del paginador. Reconstruirlo a mano en el cliente
        // sería más código y más sitios donde equivocarse.
        location.reload();
      })
      .catch(function () {
        if (btn) { btn.disabled = false; btn.style.opacity = ''; }
        alert('No se pudo completar la operación. Inténtalo de nuevo.');
      });
  }

  /* ── Conmutador de vistas ──────────────────────────────── */
  // El paso se mide del propio botón: si mañana cambia su anchura en el
  // CSS, el recuadro sigue cuadrando sin tocar este archivo.
  function colocarDeslizante(btn) {
    var botones = Array.prototype.slice.call(document.querySelectorAll('.mp-views__btn'));
    var sl = $('mpSlider');
    if (sl && btn) sl.style.transform = 'translateX(' + (botones.indexOf(btn) * btn.offsetWidth) + 'px)';
  }

  function cambiarVista(v, btn) {
    document.querySelectorAll('.mp-vista').forEach(function (el) {
      el.classList.toggle('mp-oculto', el.getAttribute('data-vista') !== v);
    });
    document.querySelectorAll('.mp-views__btn').forEach(function (b) {
      b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
    });
    colocarDeslizante(btn);
    if (v === 'mapa') montarMapa();
  }

  /* La vista de partida NO está escrita aquí: se lee del botón que trae
     aria-selected="true" en mis_planes.php, que es el mismo sitio donde
     se decide qué .mp-vista arranca sin .mp-oculto. Cambiarla es mover
     ese atributo y esa clase, sin tocar este archivo.
     El recuadro se coloca con la transición apagada: si no, al cargar la
     página se le vería deslizarse desde el primer botón hasta el que
     toca, que es un movimiento que nadie ha pedido. */
  (function vistaInicial() {
    var btn = document.querySelector('.mp-views__btn[aria-selected="true"]');
    if (!btn) return;
    var sl = $('mpSlider');
    if (sl) {
      var antes = sl.style.transition;
      sl.style.transition = 'none';
      colocarDeslizante(btn);
      void sl.offsetWidth;                 // fuerza el reflujo antes de devolverla
      sl.style.transition = antes;
    }
    if (btn.getAttribute('data-vista') === 'mapa') montarMapa();
  })();

  /* ── Mapa ──────────────────────────────────────────────── */
  function montarMapa() {
    if (mapaListo) return;
    if (!window.google || !google.maps) {
      // Todavía descargando: reintentar cuando Google avise.
      if (window.mpMapsReady) window.mpMapsReady.then(function () { montarMapa(); });
      return;
    }
    var nodo = $('mpMapa');
    if (!nodo) return;
    var con = P.filter(function (p) { return p.lat !== null && p.lng !== null; });
    if (!con.length) { nodo.innerHTML = '<p style="padding:24px;color:#6B7A83">Ningún plan tiene coordenadas todavía.</p>'; return; }

    /* Encuadre sobre el plan SELECCIONADO y no sobre todos. Un
       fitBounds de todos parece buena idea hasta que los planes están
       repartidos por el mundo: con cuatro destinos en México y uno en
       Madrid, el mapa se abría hasta cruzar el Atlántico y los cinco
       pines quedaban apelotonados en dos manchas diminutas.
       Si el plan elegido no tiene coordenadas se cae al primero que sí
       las tenga, que ya está filtrado en `con`. */
    var foco = (P[sel] && P[sel].lat !== null && P[sel].lng !== null) ? P[sel] : con[0];
    mapa = new google.maps.Map(nodo, {
      center: { lat: foco.lat, lng: foco.lng }, zoom: ZOOM_PLAN,
      disableDefaultUI: true, clickableIcons: false
    });
    // Pin propio en vez del marcador rojo de Google: es el map-pin del
    // diseño, dibujado como SVG para que el color y el tamaño sean los
    // del frame y no los que Google decida.
    var PIN = 'M352 348.4C416.1 333.9 464 276.5 464 208C464 128.5 399.5 64 320 64C240.5 64 176 128.5 176 208' +
              'C176 276.5 223.9 333.9 288 348.4L288 544C288 561.7 302.3 576 320 576C337.7 576 352 561.7 352 544L352 348.4z' +
              'M328 160C297.1 160 272 185.1 272 216C272 229.3 261.3 240 248 240C234.7 240 224 229.3 224 216' +
              'C224 158.6 270.6 112 328 112C341.3 112 352 122.7 352 136C352 149.3 341.3 160 328 160z';
    // Caja de tinta del trazado dentro del lienzo de 640x640, y grosor
    // del contorno EN UNIDADES DEL TRAZADO.
    var CAJA = { x: 176, y: 64, w: 288, h: 512 }, TRAZO = 22;

    /* Icono como SVG en un data: URI y no como google.maps.Symbol.
       El Symbol interpreta strokeWeight en PÍXELES DE PANTALLA, no en
       unidades del trazado: los 22 que había aquí no daban el contorno
       fino de 1 px que se pretendía, sino un borde blanco de 22 px que
       se tragaba entero un pin de 15x27. De ahí que en el mapa no se
       viera ningún pin.
       Con el SVG el grosor va en unidades del viewBox y lo escala el
       propio SVG, que es determinista y no depende de cómo interprete
       Google sus campos. El viewBox se agranda TRAZO/2 por cada lado
       porque la mitad del contorno cae fuera del dibujo y si no, se
       recorta. paint-order deja el relleno encima, así que el cuerpo
       rojo conserva sus 15x27 exactos y el blanco queda por fuera. */
    function simbolo(sel) {
      var alto = sel ? 32 : 27;              // alto del cuerpo rojo, del frame
      var k = alto / CAJA.h, m = TRAZO / 2;
      var vb = [CAJA.x - m, CAJA.y - m, CAJA.w + TRAZO, CAJA.h + TRAZO];
      var an = vb[2] * k, al = vb[3] * k;
      var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' + vb.join(' ') + '"' +
                ' width="' + an + '" height="' + al + '">' +
                '<path d="' + PIN + '" fill="' + (sel ? '#0E2A33' : '#FA003F') + '"' +
                ' stroke="#ffffff" stroke-width="' + TRAZO + '" stroke-linejoin="round"' +
                ' paint-order="stroke"/></svg>';
      return {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
        scaledSize: new google.maps.Size(an, al),
        // La punta del pin está en (320,576) del trazado
        anchor: new google.maps.Point((320 - vb[0]) * k, (576 - vb[1]) * k)
      };
    }
    window.__mpSimbolo = simbolo;   // lo necesita marcarPin()
    P.forEach(function (p, i) {
      if (p.lat === null || p.lng === null) return;
      try {
        var mk = new google.maps.Marker({
          position: { lat: p.lat, lng: p.lng }, map: mapa,
          icon: simbolo(false),
          title: p.nombre                   // el nombre al pasar por encima
        });
        mk.addListener('click', function () { seleccionar(i, 'mapa'); });
        marcadores.push({ i: i, mk: mk });
      } catch (err) {
        // Si Google retira Marker algún día, que se sepa por qué está
        // vacío el mapa en vez de quedarse callado.
        console.error('[Ruta Nómada] No se pudo crear el pin de "' + p.nombre + '":', err);
      }
    });
    if (!marcadores.length) {
      console.error('[Ruta Nómada] El mapa se creó pero no hay ningún pin. Planes con coordenadas: ' + con.length);
    }
    mapaListo = true;
    marcarPin();
  }

  // El pin seleccionado se distingue por color y tamaño, no dando
  // saltos: un marcador rebotando sin parar cansa la vista y además
  // BOUNCE no se detiene solo.
  function marcarPin() {
    if (!mapaListo) return;
    marcadores.forEach(function (m) {
      var s = m.i === sel;
      m.mk.setZIndex(s ? 999 : 1);
      m.mk.setIcon(window.__mpSimbolo ? window.__mpSimbolo(s) : null);
    });
  }
  function centrarEn(i) {
    var p = P[i];
    if (!mapa || p.lat === null || p.lng === null) return;
    mapa.panTo({ lat: p.lat, lng: p.lng });
  }

  /* ── Enlaces ───────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var v = e.target.closest ? e.target.closest('.mp-views__btn') : null;
    if (v) { cambiarVista(v.getAttribute('data-vista'), v); return; }
    // El menú de tres puntos no debe arrastrar la selección de la tarjeta
    if (e.target.closest && e.target.closest('.mp-card__menu')) { e.stopPropagation(); return; }
    var fila = e.target.closest ? e.target.closest('[data-idx]') : null;
    if (fila) seleccionar(Number(fila.getAttribute('data-idx')), 'lista');
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var fila = e.target.closest ? e.target.closest('[data-idx]') : null;
    if (fila) { e.preventDefault(); seleccionar(Number(fila.getAttribute('data-idx')), 'lista'); }
  });
  if ($('mpPrev')) $('mpPrev').addEventListener('click', function () { seleccionar(sel - 1, 'pager'); });
  if ($('mpNext')) $('mpNext').addEventListener('click', function () { seleccionar(sel + 1, 'pager'); });
  // Zoom del mapa. Los controles nativos van desactivados
  // (disableDefaultUI) porque el diseño trae los suyos.
  if ($('mpZoomIn'))  $('mpZoomIn').addEventListener('click',  function () { if (mapa) mapa.setZoom(mapa.getZoom() + 1); });
  if ($('mpZoomOut')) $('mpZoomOut').addEventListener('click', function () { if (mapa) mapa.setZoom(mapa.getZoom() - 1); });

  seleccionar(0, 'lista');
})();
