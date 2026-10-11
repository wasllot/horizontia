/* Horizontia · compatibilidad del tema con la navegación asíncrona (SPA) de Livewire.
   El tema y las pantallas registran listeners al cargar la página. Con navegación SPA el documento no se
   recarga, así que sin este puente: (1) DOMContentLoaded/load no se vuelven a disparar y (2) los listeners de
   pantallas anteriores se acumulan y fallan en la siguiente. Se carga justo después de jQuery y una sola vez. */
(function () {
    'use strict';
    if (window.__hzCompat) { return; }
    window.__hzCompat = true;

    var doc = document, win = window;
    var nativeDocAdd = doc.addEventListener.bind(doc);
    var nativeWinAdd = win.addEventListener.bind(win);
    var pageScoped = [];                       // [target, type, fn, opts] registrados por scripts en línea de la pantalla
    var keyed = Object.create(null);           // clave -> [target, type, fn, opts]  (misma fuente = misma pantalla revisitada)

    function isPageScript(cs) { return cs && !cs.src && !cs.hasAttribute('data-navigate-once'); }

    // Livewire dispara 'livewire:initialized' una sola vez; en pantallas cargadas por SPA ya pasó.
    var livewireStarted = false;
    doc.addEventListener.call(doc, 'livewire:initialized', function () { livewireStarted = true; });

    // Durante una navegación los <script src> de la pantalla nueva aún se están cargando: los callbacks "tardíos"
    // esperan a 'livewire:navigated' (Livewire lo dispara cuando terminaron de cargar) en vez de correr de inmediato.
    var swapping = false, queue = [];
    // Los <script src> del cuerpo nuevo pueden seguir cargándose cuando Livewire anuncia 'navigated': si el callback
    // falla porque aún no existe una librería (moment, un plugin de jQuery…), se reintenta unos instantes.
    function withRetry(fn) {
        var tries = 0;
        (function attempt() {
            try { fn(); } catch (e) {
                var missing = e && (e.name === 'ReferenceError' || (e.name === 'TypeError' && /is not a function|undefined/.test(e.message)));
                if (missing && ++tries <= 30) { setTimeout(attempt, 100); } else { console.error(e); }
            }
        })();
    }
    function later(fn) {
        var run = function () { withRetry(fn); };
        if (swapping) { queue.push(run); } else { setTimeout(run, 0); }
    }
    doc.addEventListener.call(doc, 'livewire:navigating', function () { swapping = true; });
    doc.addEventListener.call(doc, 'livewire:navigated', function () {
        swapping = false;
        var q = queue.splice(0);
        setTimeout(function () { q.forEach(function (f) { f(); }); }, 0);
    });

    function wrap(target, nativeAdd, isDoc) {
        return function (type, fn, opts) {
            if (typeof fn !== 'function') { return nativeAdd(type, fn, opts); }
            if (isDoc && type === 'livewire:initialized' && livewireStarted) {
                later(function () { fn.call(target, new Event(type)); });
                return;
            }
            var late = doc.readyState === 'complete';
            if (late && ((isDoc && type === 'DOMContentLoaded') || (!isDoc && type === 'load'))) {
                later(function () { fn.call(target, new Event(type)); });
                return;
            }
            if (isPageScript(doc.currentScript) && type !== 'DOMContentLoaded' && type !== 'load') {
                var key = (isDoc ? 'd:' : 'w:') + type + '|' + fn.toString();
                var prev = keyed[key];
                if (prev) { prev[0].removeEventListener(prev[1], prev[2], prev[3]); }   // la nueva copia sustituye a la anterior
                keyed[key] = [target, type, fn, opts];
                pageScoped.push(keyed[key]);
            }
            return nativeAdd(type, fn, opts);
        };
    }
    doc.addEventListener = wrap(doc, nativeDocAdd, true);
    win.addEventListener = wrap(win, nativeWinAdd, false);

    // Al abandonar una pantalla se retiran los listeners que registraron sus scripts.
    nativeDocAdd('livewire:navigating', function () {
        pageScoped.splice(0).forEach(function (l) { l[0].removeEventListener(l[1], l[2], l[3]); });
        Object.keys(keyed).forEach(function (k) { delete keyed[k]; });
    });

    // Handlers delegados de jQuery sobre document: misma fuente + mismo evento/selector => se reemplaza, no se acumula.
    function patchJquery() {
        var $ = win.jQuery;
        if (!$ || !$.fn || $.fn.__hzPatched) { return; }
        var on = $.fn.on, off = $.fn.off, seen = Object.create(null);
        $.fn.on = function (types, selector, data, fn) {
            if (this.length === 1 && this[0] === doc && typeof types === 'string') {
                var handler = typeof selector === 'function' ? selector : (typeof data === 'function' ? data : fn);
                var sel = typeof selector === 'string' ? selector : '';
                if (typeof handler === 'function') {
                    var key = types + '|' + sel + '|' + handler.toString();
                    if (seen[key]) { off.call(this, types, sel || null, seen[key]); }
                    seen[key] = handler;
                }
            }
            return on.apply(this, arguments);
        };
        $.fn.__hzPatched = true;
    }
    patchJquery();
})();
