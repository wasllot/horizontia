/* Horizontia UI — micro-interacciones sutiles (sin dependencias). Respeta prefers-reduced-motion. */
(function () {
    'use strict';
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 1) Barra de progreso al navegar (enlaces internos y peticiones de Livewire)
    var bar;
    function ensureBar() {
        if (!bar) { bar = document.createElement('div'); bar.id = 'ht-progress'; document.body.appendChild(bar); }
        return bar;
    }
    var timer;
    function start() {
        if (reduce) { return; }
        var b = ensureBar();
        clearInterval(timer);
        b.style.width = '0'; b.classList.add('is-on');
        var w = 8;
        requestAnimationFrame(function () { b.style.width = w + '%'; });
        timer = setInterval(function () { w += (92 - w) * 0.12; b.style.width = w + '%'; }, 250);
    }
    function done() {
        if (!bar) { return; }
        clearInterval(timer);
        bar.style.width = '100%';
        setTimeout(function () { bar.classList.remove('is-on'); setTimeout(function () { bar.style.width = '0'; }, 300); }, 200);
    }
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) { return; }
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || a.target === '_blank' || a.hasAttribute('download') || /^(mailto:|tel:|javascript:)/.test(href)) { return; }
        if (a.origin && a.origin !== location.origin) { return; }
        start();
    }, true);
    window.addEventListener('pageshow', done);
    document.addEventListener('livewire:init', function () {
        if (window.Livewire && Livewire.hook) {
            var inflight = 0;
            Livewire.hook('request', function (ctx) {
                inflight++; start();
                var finish = function () { if (--inflight <= 0) { inflight = 0; done(); } };
                if (ctx && typeof ctx.succeed === 'function') { ctx.succeed(finish); }
                if (ctx && typeof ctx.fail === 'function') { ctx.fail(finish); }
            });
        }
    });

    // 2) Contadores animados en las métricas del panel (solo la primera carga)
    function countUp(el) {
        var text = el.textContent.trim();
        var m = text.match(/(\d[\d,]*(?:\.\d+)?)/);
        if (!m) { return; }
        var target = parseFloat(m[1].replace(/,/g, ''));
        if (!isFinite(target) || target === 0) { return; }
        var decimals = (m[1].split('.')[1] || '').length;
        var prefix = text.slice(0, m.index), suffix = text.slice(m.index + m[1].length);
        var t0 = null, dur = 750;
        function fmt(v) { return v.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }); }
        function step(ts) {
            if (t0 === null) { t0 = ts; }
            var p = Math.min((ts - t0) / dur, 1), eased = 1 - Math.pow(1 - p, 3);
            el.textContent = prefix + fmt(target * eased) + suffix;
            if (p < 1) { requestAnimationFrame(step); } else { el.textContent = text; }
        }
        requestAnimationFrame(step);
    }
    document.addEventListener('DOMContentLoaded', function () {
        if (reduce) { return; }
        document.querySelectorAll('.am-payment_item_info h3, [data-countup]').forEach(countUp);
    });
})();
