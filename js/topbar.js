// ============================================================
//  js/topbar.js — Topbar global | Ruta Nómada
//  • Menú desplegable del avatar (reemplaza al sidebar).
//  • Buscador con selector de categoría → resultados.php?q=…&tab=…
//  • Burbuja del chatbot (placeholder de funcionalidad futura).
// ============================================================
(function () {
    'use strict';

    function $(id) { return document.getElementById(id); }

    function init() {
        /* ── Menú del avatar ─────────────────────────────── */
        var userWrap = $('tbUser'), userBtn = $('tbUserBtn'), menu = $('tbMenu');
        function closeMenu() {
            if (!menu || menu.hidden) return;
            menu.hidden = true;
            userWrap.classList.remove('open');
            userBtn.setAttribute('aria-expanded', 'false');
        }
        if (userBtn) {
            userBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = menu.hidden;
                closeCat(); closeChat();
                menu.hidden = !open;
                userWrap.classList.toggle('open', open);
                userBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        /* ── Selector de categoría del buscador ──────────── */
        var cat = $('tbCat'), catBtn = $('tbCatBtn'), catList = $('tbCatList'), catLabel = $('tbCatLabel');
        function closeCat() {
            if (!catList || catList.hidden) return;
            catList.hidden = true;
            cat.classList.remove('open');
        }
        function markCat() {
            var v = catLabel.getAttribute('data-value');
            catList.querySelectorAll('li').forEach(function (li) {
                li.classList.toggle('on', li.getAttribute('data-value') === v);
            });
        }
        if (catBtn) {
            catBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = catList.hidden;
                closeMenu(); closeChat();
                markCat();
                catList.hidden = !open;
                cat.classList.toggle('open', open);
            });
            catList.querySelectorAll('li').forEach(function (li) {
                li.addEventListener('click', function () {
                    catLabel.setAttribute('data-value', li.getAttribute('data-value'));
                    catLabel.textContent = li.textContent;
                    closeCat();
                    $('tbSearchInput').focus();
                });
            });
        }

        /* ── Envío de búsqueda ───────────────────────────── */
        var form = $('tbSearch');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var q = $('tbSearchInput').value.trim();
                if (!q) { $('tbSearchInput').focus(); return; }
                var tab = catLabel ? catLabel.getAttribute('data-value') : 'ciudad';
                var url = 'resultados.php?q=' + encodeURIComponent(q);
                if (tab && tab !== 'ciudad') url += '&tab=' + encodeURIComponent(tab);
                window.location.href = url;
            });
        }

        /* ── Burbuja del chatbot ─────────────────────────── */
        var chatBtn = $('tbChatbotBtn'), chatPanel = $('tbChatbotPanel');
        function closeChat() { if (chatPanel && !chatPanel.hidden) chatPanel.hidden = true; }
        if (chatBtn) {
            chatBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = chatPanel.hidden;
                closeMenu(); closeCat();
                chatPanel.hidden = !open;
            });
        }

        /* ── Cierre global (clic fuera / Esc) ────────────── */
        document.addEventListener('click', function (e) {
            if (userWrap && !userWrap.contains(e.target)) closeMenu();
            if (cat && !cat.contains(e.target)) closeCat();
            if (chatPanel && !chatPanel.hidden && !$('tbChatbot').contains(e.target)) closeChat();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeMenu(); closeCat(); closeChat(); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
