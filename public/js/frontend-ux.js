/* UX del sitio público: aparición al scroll, barra de progreso, encabezado con sombra, imágenes con fundido y contadores. */
(function () {
    'use strict';
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Bloques que aparecen solos al entrar en pantalla (solo los que están por debajo del primer vistazo y fuera de Livewire,
       porque Livewire vuelve a pintar su contenido y un estado «oculto» se repetiría). */
    var AUTO = '.ht-ccard, .ev-card, .bn-card, .lp-step, .rec__card, .ht-block, .ht-faq details, .ev-hero, .ev-filters, .hz-banner__in > *, main section > .container > h2, .am-contact-section .ht-contact__card';
    var io = null;
    function observer() {
        if (io || !('IntersectionObserver' in window)) { return io; }
        io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) { return; }
                en.target.classList.add('is-in'); io.unobserve(en.target);
                en.target.querySelectorAll('[data-count]').forEach(count);
                if (en.target.hasAttribute('data-count')) { count(en.target); }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        return io;
    }
    function count(el) {
        var to = parseInt(el.getAttribute('data-count'), 10);
        if (!to || reduce || el.__counted) { return; }
        el.__counted = true;
        var t0 = null, dur = 1100;
        var step = function (t) { if (t0 === null) { t0 = t; } var p = Math.min(1, (t - t0) / dur); el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3))); if (p < 1) { requestAnimationFrame(step); } };
        el.textContent = '0'; requestAnimationFrame(step);
    }
    function scan(root) {
        root = root || document;
        var obs = observer();
        var items = [].slice.call(root.querySelectorAll('.ht-reveal:not(.is-in)'));
        if (root.matches && root.matches('.ht-reveal:not(.is-in)')) { items.push(root); }
        [].slice.call(root.querySelectorAll(AUTO)).forEach(function (el, i) {
            if (el.__htScanned || el.classList.contains('ht-reveal') || el.closest('[wire\\:id]') || el.closest('.hz') || el.closest('.ht-hero')) { return; }
            el.__htScanned = true;
            if (el.getBoundingClientRect().top < innerHeight * 0.92) { return; }   // lo que ya se ve al cargar no se anima
            el.classList.add('ht-reveal');
            el.style.setProperty('--d', ((i % 4) * 0.08).toFixed(2) + 's');
            items.push(el);
        });
        items.forEach(function (el) { if (!obs || reduce || el.closest('[wire\\:id]')) { el.classList.add('is-in'); } else { obs.observe(el); } });
        [].slice.call(root.querySelectorAll('[data-count]')).forEach(function (el) { if (!el.closest('.ht-reveal')) { count(el); } });
    }

    /* Barra de progreso + sombra del encabezado */
    function chrome() {
        var bar = document.getElementById('ht-progress');
        if (!bar) { bar = document.createElement('div'); bar.id = 'ht-progress'; bar.setAttribute('aria-hidden', 'true'); document.body.appendChild(bar); }
        var ticking = false;
        var update = function () {
            var h = document.documentElement, max = h.scrollHeight - h.clientHeight, y = window.scrollY || h.scrollTop;
            bar.style.width = (max > 0 ? Math.min(100, y / max * 100) : 0) + '%';
            document.body.classList.toggle('ht-scrolled', y > 12);
            ticking = false;
        };
        window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
        update();
    }

    /* Imágenes con carga diferida: fundido al terminar de cargar */
    function fades(root) {
        if (reduce) { return; }
        [].slice.call((root || document).querySelectorAll('img[loading=lazy]:not(.ht-fade)')).forEach(function (img) {
            if (img.complete && img.naturalWidth) { return; }
            img.classList.add('ht-fade');
            var done = function () { img.classList.add('is-loaded'); };
            img.addEventListener('load', done, { once: true }); img.addEventListener('error', done, { once: true });
        });
    }

    function init() { scan(document); fades(document); }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { chrome(); init(); }); } else { chrome(); init(); }
    document.addEventListener('livewire:navigated', init);
    /* Contenido que llega después (pagina «ver más», etc.) */
    if ('MutationObserver' in window) {
        var pending = false;
        new MutationObserver(function () { if (pending) { return; } pending = true; setTimeout(function () { pending = false; fades(document); }, 200); }).observe(document.body || document.documentElement, { childList: true, subtree: true });
    }
})();
