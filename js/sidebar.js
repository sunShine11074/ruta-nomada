// ============================================================
//  js/sidebar.js — Sidebar dinámico (colapsa/expande) | Ruta Nómada
// ------------------------------------------------------------
//  • Botón ☰ dentro del sidebar lo oculta (se desliza a la izquierda).
//  • Botón flotante circular (esquina sup. izq.) lo vuelve a mostrar.
//  • El estado se recuerda entre páginas con localStorage.
//  • En móvil arranca colapsado y se superpone con un scrim.
//  No requiere modificar el markup de cada página: los controles
//  se inyectan automáticamente.
// ============================================================
(function () {
    'use strict';

    var KEY  = 'rn_sidebar_collapsed';
    var body = document.body;

    function isMobile() {
        return window.matchMedia('(max-width: 700px)').matches;
    }

    function setCollapsed(state, persist) {
        body.classList.toggle('sidebar-collapsed', state);
        if (persist !== false) {
            try { localStorage.setItem(KEY, state ? '1' : '0'); } catch (e) {}
        }
    }

    function toggle() {
        setCollapsed(!body.classList.contains('sidebar-collapsed'), true);
    }

    // ── Estado inicial (antes de pintar para evitar parpadeo) ──
    var saved = null;
    try { saved = localStorage.getItem(KEY); } catch (e) {}
    var collapsed = (saved === null) ? isMobile() : (saved === '1');
    setCollapsed(collapsed, false);

    // ── Inyectar controles cuando el DOM esté listo ──
    function init() {
        // Botón flotante para reabrir
        var fab = document.createElement('button');
        fab.className = 'sidebar-fab';
        fab.setAttribute('aria-label', 'Mostrar menú');
        fab.type = 'button';
        fab.innerHTML = '☰'; // ☰
        fab.addEventListener('click', toggle);
        body.appendChild(fab);

        // Scrim (capa oscura para cerrar en móvil)
        var scrim = document.createElement('div');
        scrim.className = 'sidebar-scrim';
        scrim.addEventListener('click', function () { setCollapsed(true, true); });
        body.appendChild(scrim);

        // Botón de colapsar dentro del sidebar (se crea si no existe)
        var sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            var toggleBtn = sidebar.querySelector('.sidebar-toggle');
            if (!toggleBtn) {
                toggleBtn = document.createElement('button');
                toggleBtn.className = 'sidebar-toggle';
                toggleBtn.type = 'button';
                toggleBtn.setAttribute('aria-label', 'Ocultar menú');
                toggleBtn.innerHTML = '☰'; // ☰
                sidebar.insertBefore(toggleBtn, sidebar.firstChild);
            }
            toggleBtn.addEventListener('click', toggle);
        }

        // ── Buscador del topbar: Enter → página de resultados ──
        // Habilita el buscador en todas las páginas sin tocar su markup.
        document.querySelectorAll('.search-bar-mini input').forEach(function (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                var q = input.value.trim();
                if (!q) return;
                window.location.href = 'resultados.php?q=' + encodeURIComponent(q);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
