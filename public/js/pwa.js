/* PWA: registra el service worker y ofrece «Instalar app» cuando el navegador lo permite. */
(function () {
    'use strict';
    var tag = document.currentScript;
    var swUrl = tag && tag.getAttribute('data-sw');

    if ('serviceWorker' in navigator && swUrl && (location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
        window.addEventListener('load', function () { navigator.serviceWorker.register(swUrl, { scope: '/' }).catch(function () {}); });
    }

    var KEY = 'hz_pwa_dismissed', deferred = null;
    var dismissed = function () { try { return localStorage.getItem(KEY) === '1'; } catch (e) { return false; } };

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferred = e;
        if (dismissed() || document.getElementById('hz-install')) { return; }
        var box = document.createElement('div');
        box.id = 'hz-install';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-label', 'Instalar la app');
        box.style.cssText = 'position:fixed;left:50%;top:72px;transform:translateX(-50%);z-index:2147483000;display:flex;gap:10px;align-items:center;max-width:calc(100vw - 24px);padding:12px 14px;background:#14213d;color:#fff;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.3);font:600 14px system-ui,sans-serif';
        box.innerHTML = '<span>📲 Instala la app para entrar más rápido</span>'
            + '<button type="button" data-act="install" style="border:0;border-radius:8px;padding:8px 14px;background:#fed304;color:#14213d;font-weight:700;cursor:pointer">Instalar</button>'
            + '<button type="button" data-act="close" aria-label="Cerrar" style="border:0;background:none;color:#aab3c5;font-size:18px;cursor:pointer">✕</button>';
        box.addEventListener('click', function (ev) {
            var act = ev.target && ev.target.getAttribute('data-act');
            if (act === 'install' && deferred) { deferred.prompt(); deferred.userChoice.finally(function () { deferred = null; box.remove(); }); }
            if (act === 'close') { try { localStorage.setItem(KEY, '1'); } catch (e2) {} box.remove(); }
        });
        document.body.appendChild(box);
    });

    window.addEventListener('appinstalled', function () { var b = document.getElementById('hz-install'); if (b) { b.remove(); } });
})();
