/* ============================================================
   js/asistente.js — Chatbot flotante global | Ruta Nómada

   Estas páginas son PHP plano: aquí no existe React ni el runtime
   de plan.php, así que el mini-formato (**negritas** y viñetas)
   se pinta a mano con nodos del DOM.

   ⚠ Regla que no se cruza: la respuesta del modelo NUNCA se mete
   con innerHTML. Se crea con createTextNode, que escapa solo. Si
   alguien la pasara a innerHTML, un modelo manipulado por prompt
   injection podría devolver <img onerror=...> y ejecutarlo.
   ============================================================ */
(function () {
    'use strict';

    var raiz = document.getElementById('rnas');
    if (!raiz) return;

    var burbuja = document.getElementById('rnasBubble');
    var panel   = document.getElementById('rnasPanel');
    var cuerpo  = document.getElementById('rnasBody');
    var intro   = document.getElementById('rnasIntro');
    var registro = document.getElementById('rnasLog');
    var forma   = document.getElementById('rnasForm');
    var entrada = document.getElementById('rnasInput');
    var enviar  = document.getElementById('rnasSend');
    var nuevo   = document.getElementById('rnasNew');
    var minimizar = document.getElementById('rnasMin');

    var CSRF     = raiz.dataset.csrf || '';
    var INICIAL  = raiz.dataset.inicial || '?';
    var LLAVE    = 'rnAsistente';          // sessionStorage
    var escribiendo = false;
    var temporizador = null;

    /* ── Estado que sobrevive al cambio de página ──────────────
       El servidor ya guarda el hilo en $_SESSION, pero el navegador
       pierde la pantalla al navegar. Sin esto, el usuario pasa de
       inicio.php a guias.php y ve un chat vacío aunque el modelo
       siga recordando: descuadre confuso. */
    function leerEstado() {
        try {
            var s = sessionStorage.getItem(LLAVE);
            var o = s ? JSON.parse(s) : null;
            if (o && Object.prototype.toString.call(o.log) === '[object Array]') return o;
        } catch (e) { /* sessionStorage bloqueado o JSON corrupto */ }
        return { abierto: false, log: [] };
    }
    function guardarEstado(estado) {
        try { sessionStorage.setItem(LLAVE, JSON.stringify(estado)); } catch (e) {}
    }
    var estado = leerEstado();

    /* ── Mini-formato: **negritas**, viñetas "• " y saltos ───── */
    function pintarTexto(src) {
        var cont = document.createElement('div');
        String(src).split('\n').forEach(function (linea) {
            var esVineta = linea.indexOf('• ') === 0;
            var txt = esVineta ? linea.slice(2) : linea;

            if (!esVineta && txt.trim() === '') {
                var hueco = document.createElement('div');
                hueco.className = 'rnas__hueco';
                cont.appendChild(hueco);
                return;
            }

            // Partir por **negritas** conservando el orden.
            var trozos = document.createDocumentFragment();
            var resto = txt;
            var guardia = 0;
            while (resto.length && guardia++ < 200) {
                var m = resto.match(/\*\*(.+?)\*\*/);
                if (!m) { trozos.appendChild(document.createTextNode(resto)); break; }
                var en = resto.indexOf(m[0]);
                if (en > 0) trozos.appendChild(document.createTextNode(resto.slice(0, en)));
                var fuerte = document.createElement('b');
                fuerte.appendChild(document.createTextNode(m[1]));
                trozos.appendChild(fuerte);
                resto = resto.slice(en + m[0].length);
            }

            if (esVineta) {
                var fila = document.createElement('div');
                fila.className = 'rnas__vineta';
                var punto = document.createElement('span');
                punto.appendChild(document.createTextNode('•'));
                var cuerpoV = document.createElement('span');
                cuerpoV.appendChild(trozos);
                fila.appendChild(punto);
                fila.appendChild(cuerpoV);
                cont.appendChild(fila);
            } else {
                var p = document.createElement('div');
                p.appendChild(trozos);
                cont.appendChild(p);
            }
        });
        return cont;
    }

    /* ── Mensajes ─────────────────────────────────────────────── */
    function nodoUsuario(texto) {
        var fila = document.createElement('div');
        fila.className = 'rnas__msg rnas__msg--user';
        var b = document.createElement('span');
        b.className = 'rnas__burbuja';
        b.appendChild(document.createTextNode(texto));
        var av = document.createElement('span');
        av.className = 'rnas__avatar';
        av.appendChild(document.createTextNode(INICIAL));
        fila.appendChild(b);
        fila.appendChild(av);
        return fila;
    }

    function nodoAsistente(texto, conCursor) {
        var fila = document.createElement('div');
        fila.className = 'rnas__msg';
        var logo = document.createElement('img');
        logo.className = 'rnas__msgLogo';
        logo.src = 'img/asistente.png';
        logo.alt = '';
        var caja = document.createElement('div');
        caja.className = 'rnas__texto';
        caja.appendChild(pintarTexto(texto));
        if (conCursor) {
            var c = document.createElement('span');
            c.className = 'rnas__caret';
            caja.appendChild(c);
        }
        fila.appendChild(logo);
        fila.appendChild(caja);
        return fila;
    }

    function alFinal() { cuerpo.scrollTop = cuerpo.scrollHeight; }

    function repintar() {
        registro.textContent = '';
        estado.log.forEach(function (m) {
            registro.appendChild(m.who === 'u' ? nodoUsuario(m.t) : nodoAsistente(m.t, false));
        });
        intro.hidden = estado.log.length > 0;
    }

    /* ── Abrir / cerrar ───────────────────────────────────────── */
    function alinearConTopbar() {
        // Hay dos variantes de topbar (con y sin buscador) y su alto
        // cambia con el ancho de la ventana. Medirla evita descuadres.
        var tb = document.querySelector('.topbar');
        if (tb) {
            var abajo = Math.round(tb.getBoundingClientRect().bottom);
            raiz.style.setProperty('--rnas-top', Math.max(8, abajo + 12) + 'px');
        }
    }

    function abrir() {
        alinearConTopbar();
        panel.hidden = false;
        raiz.classList.add('is-open');
        burbuja.setAttribute('aria-expanded', 'true');
        estado.abierto = true;
        guardarEstado(estado);
        alFinal();
        // El foco al input: quien abre el chat es para escribir.
        try { entrada.focus({ preventScroll: true }); } catch (e) { entrada.focus(); }
    }

    function cerrar() {
        panel.hidden = true;
        raiz.classList.remove('is-open');
        burbuja.setAttribute('aria-expanded', 'false');
        estado.abierto = false;
        guardarEstado(estado);
        burbuja.focus();
    }

    burbuja.addEventListener('click', function () { panel.hidden ? abrir() : cerrar(); });
    minimizar.addEventListener('click', cerrar);
    window.addEventListener('resize', function () { if (!panel.hidden) alinearConTopbar(); });
    // Las tipografías web pueden cambiar el alto de la topbar después de la
    // primera medición y dejar el panel unos píxeles descuadrado.
    window.addEventListener('load', function () { if (!panel.hidden) alinearConTopbar(); });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) { e.stopPropagation(); cerrar(); }
    });

    /* ── Nuevo chat ───────────────────────────────────────────── */
    nuevo.addEventListener('click', function () {
        clearInterval(temporizador);
        escribiendo = false;
        enviar.disabled = false;
        estado.log = [];
        guardarEstado(estado);
        repintar();
        // El hilo también vive en la sesión del servidor: sin avisarle,
        // la pantalla queda limpia pero el modelo sigue recordando.
        fetch('api/asistente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
            body: JSON.stringify({ reset: 1 })
        }).catch(function () {});
        entrada.focus();
    });

    /* ── Envío ────────────────────────────────────────────────── */
    function tecleo(texto) {
        var fila = nodoAsistente('', true);
        registro.appendChild(fila);
        var caja = fila.querySelector('.rnas__texto');
        var i = 0;
        var rapido = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function terminar() {
            clearInterval(temporizador);
            caja.textContent = '';
            caja.appendChild(pintarTexto(texto));
            escribiendo = false;
            enviar.disabled = false;
            estado.log.push({ who: 'a', t: texto });
            guardarEstado(estado);
            alFinal();
        }

        if (rapido) { terminar(); return; }

        temporizador = setInterval(function () {
            i += 3;
            if (i >= texto.length) { terminar(); return; }
            caja.textContent = '';
            caja.appendChild(pintarTexto(texto.slice(0, i)));
            var c = document.createElement('span');
            c.className = 'rnas__caret';
            caja.appendChild(c);
            alFinal();
        }, 30);
    }

    function preguntar(txt) {
        var t = String(txt || '').trim();
        if (!t || escribiendo) return;

        escribiendo = true;
        enviar.disabled = true;
        entrada.value = '';
        estado.log.push({ who: 'u', t: t });
        guardarEstado(estado);
        intro.hidden = true;
        registro.appendChild(nodoUsuario(t));
        alFinal();

        fetch('api/asistente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
            body: JSON.stringify({ mensaje: t })
        })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                tecleo(j && j.ok && j.respuesta
                    ? j.respuesta
                    : (j && j.error) || 'No pude responder en este momento. Inténtalo de nuevo en un minuto.');
            })
            .catch(function () {
                tecleo('Parece que no hay conexión. Revisa tu internet e inténtalo otra vez.');
            });
    }

    forma.addEventListener('submit', function (e) {
        e.preventDefault();
        preguntar(entrada.value);
    });

    Array.prototype.forEach.call(raiz.querySelectorAll('.rnas__sug'), function (b) {
        b.addEventListener('click', function () { preguntar(b.dataset.q); });
    });

    /* ── Arranque ─────────────────────────────────────────────── */
    repintar();
    if (estado.abierto) abrir();
})();
