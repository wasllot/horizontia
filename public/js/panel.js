/* Horizontia · Panel — comportamiento del shell (sin dependencias, accesible).
   - Sidebar colapsable (recordado) y cajón móvil, grupos del menú, menús emergentes.
   - Navegación asíncrona (SPA) sobre Livewire: los enlaces internos no recargan la página.
   - Tiempo real: pulso en segundo plano (badges) y refresco de listados solo con la pestaña visible.
   - Barra de progreso y contadores animados. */
(function () {
    'use strict';
    if (window.__hzPanel) { return; }          // el script se carga una sola vez aunque se navegue
    window.__hzPanel = true;

    var doc = document, root = doc.documentElement;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var mobile = window.matchMedia('(max-width: 1199px)');
    var KEY = 'hz.sidebar.collapsed';
    function body() { return doc.body; }
    function store(get, v) { try { return get ? localStorage.getItem(KEY) : localStorage.setItem(KEY, v); } catch (e) { return null; } }

    var nativeAdd = doc.addEventListener.bind(doc);   // (panel-compat.js ya parchea doc.addEventListener)

    /* --- Sidebar colapsado (solo escritorio). La clase vive en <html> para sobrevivir al cambio de <body>. --- */
    function setCollapsed(on) {
        root.classList.toggle('hz-collapsed', on);
        syncCollapseButtons();
        store(false, on ? '1' : '0');
    }
    function syncCollapseButtons() {
        var on = root.classList.contains('hz-collapsed');
        doc.querySelectorAll('[data-hz-collapse]').forEach(function (b) {
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            b.setAttribute('aria-label', on ? 'Expandir menú lateral' : 'Contraer menú lateral');
        });
    }
    if (store(true) === '1' && !mobile.matches) { root.classList.add('hz-collapsed'); }

    /* --- Cajón móvil --- */
    var lastFocus = null;
    function openNav() {
        lastFocus = doc.activeElement;
        root.classList.add('hz-nav-open');
        doc.querySelectorAll('[data-hz-menu]').forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
        var first = doc.querySelector('.hz-sidebar a, .hz-sidebar button');
        if (first) { first.focus({ preventScroll: true }); }
    }
    function closeNav(restore) {
        if (!root.classList.contains('hz-nav-open')) { return; }
        root.classList.remove('hz-nav-open');
        doc.querySelectorAll('[data-hz-menu]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
        if (restore !== false && lastFocus && lastFocus.focus) { lastFocus.focus({ preventScroll: true }); }
    }

    /* --- Menús emergentes (avatar, idioma, carrito, notificaciones) --- */
    function closePops(except) {
        doc.querySelectorAll('.hz-pop.is-open').forEach(function (p) {
            if (p !== except) { p.classList.remove('is-open'); var t = p.querySelector('[data-hz-pop-toggle]'); if (t) { t.setAttribute('aria-expanded', 'false'); } }
        });
    }

    /* --- Cambio de idioma en segundo plano --- */
    function switchLang(btn) {
        var token = doc.querySelector('meta[name="csrf-token"]');
        btn.disabled = true;
        fetch(btn.getAttribute('data-hz-lang-url'), {
            method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ 'am-locale': btn.getAttribute('data-hz-lang') }),
            headers: { 'X-CSRF-TOKEN': token ? token.content : '', 'Accept': 'text/html', 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(function () { go(location.href, true); }).catch(function () { location.reload(); });
    }

    /* --- Navegación asíncrona --- */
    var NO_SPA = /(\/paginas\/\d+\/editor|cerrar-sesion|logout|clear-cache|limpiar-cache|suplantaci|impersonat|download|descargar|\.(pdf|zip|csv|xlsx?|docx?|png|jpe?g|gif|webp|mp4)(\?|$)|\/storage\/|\/video-seguro\/|\/contenido-scorm\/)/i;
    var PANEL_PATHS = /^\/(admin|tutor|estudiante|mis-cursos|gestionar-cursos|crear-curso|curso\/editar|certificados)(\/|$)/;
    function go(url, force) {
        if (window.Alpine && typeof Alpine.navigate === 'function') {
            Alpine.navigate(url);
        } else { location.href = url; }
    }
    function spaTarget(a) {
        if (!a || !a.closest('.hz') || a.closest('[data-no-spa]') || a.hasAttribute('data-no-spa') || a.hasAttribute('wire:navigate.remove') && false) { return null; }
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || a.target && a.target !== '_self' || a.hasAttribute('download') || /^(mailto:|tel:|javascript:)/i.test(href)) { return null; }
        if (a.origin !== location.origin || NO_SPA.test(a.pathname + a.search)) { return null; }
        if (!PANEL_PATHS.test(a.pathname) || !PANEL_PATHS.test(location.pathname)) { return null; }   // desde el messenger o el aula: carga normal                 // páginas públicas y el aula usan otro diseño: carga normal
        if (a.closest('.modal, .select2-container, .note-editor')) { return null; }
        return a.href;
    }

    doc.addEventListener('click', function (e) {
        var t = e.target;
        if (!t.closest) { return; }

        // Enlaces internos del panel: sin recarga.
        var a = t.closest('a[href]');
        if (a && !e.defaultPrevented && e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey && !a.hasAttribute('wire:navigate')) {
            var url = spaTarget(a);
            if (url && window.Alpine && Alpine.navigate) {
                if (url === location.href) { e.preventDefault(); return; }
                e.preventDefault(); go(url); closeNav(false); closePops(); return;
            }
        }

        if (t.closest('[data-hz-collapse]')) { setCollapsed(!root.classList.contains('hz-collapsed')); return; }
        if (t.closest('[data-hz-menu]')) { root.classList.contains('hz-nav-open') ? closeNav() : openNav(); return; }
        if (t.closest('[data-hz-scrim]')) { closeNav(); return; }
        if (mobile.matches && t.closest('.hz-sidebar a[href]')) { closeNav(false); }

        var grp = t.closest('[data-hz-toggle]');
        if (grp) {
            if (root.classList.contains('hz-collapsed') && !mobile.matches) { setCollapsed(false); }
            var item = grp.closest('.hz-nav__item'), open = !item.classList.contains('is-open');
            item.classList.toggle('is-open', open);
            grp.setAttribute('aria-expanded', open ? 'true' : 'false');
            return;
        }

        var lang = t.closest('[data-hz-lang]');
        if (lang) { switchLang(lang); return; }

        var pt = t.closest('[data-hz-pop-toggle]');
        if (pt) {
            var pop = pt.closest('.hz-pop'), willOpen = !pop.classList.contains('is-open');
            closePops(pop);
            pop.classList.toggle('is-open', willOpen);
            pt.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            return;
        }
        if (!t.closest('.hz-pop')) { closePops(); }
    });

    doc.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var open = doc.querySelector('.hz-pop.is-open');
            if (open) { var tg = open.querySelector('[data-hz-pop-toggle]'); closePops(); if (tg) { tg.focus(); } return; }
            closeNav();
        }
    });
    if (mobile.addEventListener) { mobile.addEventListener('change', function (m) { if (!m.matches) { closeNav(false); } }); }

    /* --- Barra de progreso (navegaciones y peticiones de Livewire) --- */
    var bar, timer;
    function ensureBar() { if (!bar || !bar.isConnected) { bar = doc.createElement('div'); bar.className = 'hz-progress'; bar.setAttribute('role', 'presentation'); body().appendChild(bar); } return bar; }
    function start() {
        if (reduce) { return; }
        var b = ensureBar(), w = 8;
        clearInterval(timer);
        b.style.width = '0'; b.classList.add('is-on');
        requestAnimationFrame(function () { b.style.width = w + '%'; });
        timer = setInterval(function () { w += (92 - w) * 0.12; b.style.width = w + '%'; }, 250);
    }
    function done() {
        if (!bar) { return; }
        clearInterval(timer);
        bar.style.width = '100%';
        setTimeout(function () { bar.classList.remove('is-on'); setTimeout(function () { bar.style.width = '0'; }, 300); }, 200);
    }
    window.addEventListener('pageshow', done);
    nativeAdd('livewire:navigate', start);
    nativeAdd('livewire:navigated', done);
    nativeAdd('livewire:init', function () {
        if (window.Livewire && Livewire.hook) {
            var inflight = 0;
            Livewire.hook('request', function (ctx) {
                inflight++;
                if (!ctx.silent) { start(); }
                var fin = function () { if (--inflight <= 0) { inflight = 0; done(); } };
                if (ctx && typeof ctx.succeed === 'function') { ctx.succeed(fin); }
                if (ctx && typeof ctx.fail === 'function') { ctx.fail(fin); }
            });
        }
    });

    /* --- Contadores animados en métricas --- */
    function countUp(el) {
        var text = el.textContent.trim(), m = text.match(/(\d[\d,]*(?:\.\d+)?)/);
        if (!m || el.dataset.hzCounted) { return; }
        el.dataset.hzCounted = '1';
        var target = parseFloat(m[1].replace(/,/g, ''));
        if (!isFinite(target) || target === 0) { return; }
        var dec = (m[1].split('.')[1] || '').length, pre = text.slice(0, m.index), suf = text.slice(m.index + m[1].length), t0 = null, dur = 700;
        function step(ts) {
            if (t0 === null) { t0 = ts; }
            var p = Math.min((ts - t0) / dur, 1), v = target * (1 - Math.pow(1 - p, 3));
            el.textContent = pre + v.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suf;
            if (p < 1) { requestAnimationFrame(step); } else { el.textContent = text; }
        }
        requestAnimationFrame(step);
    }

    /* ---------------------------------------------------------------------------------------------------------
       Tiempo real. Sin websockets: sondeo ligero, solo con la pestaña visible, con espera creciente si falla.
       --------------------------------------------------------------------------------------------------------- */
    var pulseUrl = null, pulseTimer = null, pulseDelay = 15000, lastNotif = null;
    function setBadge(name, n) {
        doc.querySelectorAll('[data-hz-badge="' + name + '"]').forEach(function (el) {
            el.textContent = n > 99 ? '99+' : String(n);
            el.hidden = !(n > 0);
        });
    }
    function pulse() {
        clearTimeout(pulseTimer);
        if (!pulseUrl || doc.visibilityState !== 'visible') { return; }
        fetch(pulseUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
            .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
            .then(function (d) {
                pulseDelay = 15000;
                setBadge('notifications', d.notifications || 0);
                setBadge('messages', d.messages || 0);
                if (lastNotif !== null && d.notifications > lastNotif && window.Livewire) { Livewire.dispatch('notifications-refresh'); }
                lastNotif = d.notifications || 0;
                doc.dispatchEvent(new CustomEvent('hz:pulse', { detail: d }));
            })
            .catch(function () { pulseDelay = Math.min(pulseDelay * 2, 120000); })
            .then(function () { pulseTimer = setTimeout(pulse, pulseDelay); });
    }

    // Listados y paneles marcados con data-hz-live="segundos" se refrescan solos (sin tocar formularios).
    var liveTimer = null;
    function liveTick() {
        clearTimeout(liveTimer);
        var main = doc.getElementById('hz-main'), secs = main && parseInt(main.getAttribute('data-hz-live'), 10);
        if (!secs) { return; }
        liveTimer = setTimeout(function () {
            var busy = doc.hidden || doc.querySelector('.modal.show, .hz-pop.is-open') || (doc.activeElement && /^(INPUT|TEXTAREA|SELECT)$/.test(doc.activeElement.tagName));
            if (!busy && window.Livewire) {
                main.querySelectorAll('[wire\\:id]').forEach(function (el) {
                    if (el.parentElement.closest('[wire\\:id]')) { return; }           // solo componentes de primer nivel
                    var c = Livewire.find(el.getAttribute('wire:id'));
                    try { if (c) { c.$refresh(); } } catch (e) { /* componente desmontado */ }
                });
            }
            liveTick();
        }, secs * 1000);
    }

    /* --- Tablas: etiqueta de columna en cada celda (para la vista de tarjetas en móvil). Se reaplica cuando Livewire repinta. --- */
    function labelTables(scope) {
        (scope || doc).querySelectorAll('.hz-content table').forEach(function (t) {
            var heads = t.tHead && t.tHead.rows[0] ? Array.prototype.map.call(t.tHead.rows[0].cells, function (h) { return (h.textContent || '').replace(/\s+/g, ' ').trim(); }) : null;
            if (!heads) { return; }
            Array.prototype.forEach.call(t.tBodies, function (b) {
                Array.prototype.forEach.call(b.rows, function (r) {
                    var col = 0;
                    Array.prototype.forEach.call(r.cells, function (c) {
                        if (heads[col] && c.getAttribute('data-label') !== heads[col]) { c.setAttribute('data-label', heads[col]); }
                        col += c.colSpan || 1;
                    });
                });
            });
        });
    }
    var labelQueued = false, tableObserver = null;
    function watchTables() {
        labelTables();
        var host = doc.querySelector('.hz-content');
        if (!host || !window.MutationObserver) { return; }
        if (tableObserver) { tableObserver.disconnect(); }
        tableObserver = new MutationObserver(function () {
            if (labelQueued) { return; }
            labelQueued = true;
            requestAnimationFrame(function () { labelQueued = false; labelTables(); });
        });
        tableObserver.observe(host, { childList: true, subtree: true });
    }

    /* Imágenes rotas dentro del panel (miniaturas que ya no existen): se ocultan y queda el hueco tonal en lugar del icono roto. */
    doc.addEventListener('error', function (e) {
        var t = e.target;
        if (t && t.tagName === 'IMG' && t.closest && t.closest('.hz-content')) { t.classList.add('is-broken'); }
    }, true);

    window.hzPanel = { pulse: function () { pulse(); }, liveTick: liveTick, go: go, labelTables: labelTables };

    function init() {
        // <html> puede perder sus clases al navegar (el tema pone class="no-js"): se reaplica el estado guardado.
        if (store(true) === '1' && !mobile.matches) { root.classList.add('hz-collapsed'); }
        syncCollapseButtons();
        if (!reduce) { doc.querySelectorAll('[data-countup], .am-payment_item_info h3, .hz-stat__value').forEach(countUp); }
        var m = doc.querySelector('meta[name="hz-pulse"]');
        pulseUrl = m ? m.content : null;
        if (pulseUrl) { lastNotif = null; pulse(); }
        liveTick();
        watchTables();
    }
    nativeAdd('DOMContentLoaded', init);
    nativeAdd('livewire:navigated', function () { closeNav(false); closePops(); init(); });
    doc.addEventListener('visibilitychange', function () { if (doc.visibilityState === 'visible') { pulse(); liveTick(); } });
    nativeAdd('notifications-count-updated', function (e) { setBadge('notifications', (e.detail && e.detail.count) || 0); });
    window.addEventListener('notifications-count-updated', function (e) { setBadge('notifications', (e.detail && e.detail.count) || 0); });
})();
