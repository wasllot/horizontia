/* Service worker de Horizontia.
 * - Páginas: siempre de la red (nunca se guardan: pueden tener datos privados); sin conexión se muestra /sin-conexion.
 * - Archivos estáticos propios (css, js, imágenes, fuentes): se sirven al instante desde caché y se actualizan en segundo plano.
 * - Nunca toca: POST, /livewire, /api, /admin, videos, Mercado Pago ni otros dominios.
 * Sube VERSION cuando cambies esta lógica. */
const VERSION = 'hz-v1';
const STATIC = VERSION + '-static';
const OFFLINE_URL = '/sin-conexion';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC).then((cache) => cache.addAll([OFFLINE_URL, '/images/pwa/icon-192.png', '/images/nuevo/logo-horizontia.png'])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

const isStaticAsset = (url) => /^\/(css|js|images|fonts|build|vendor|modules)\//.test(url.pathname) && /\.(css|js|png|jpe?g|webp|svg|gif|ico|woff2?|ttf)$/i.test(url.pathname);

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') { return; }
    const url = new URL(req.url);
    if (url.origin !== self.location.origin || req.headers.has('range')) { return; }
    if (/^\/(livewire|api|admin|video-seguro|sw\.js|manifest)/.test(url.pathname)) { return; }

    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(caches.open(STATIC).then((cache) => cache.match(req).then((hit) => {
            const refresh = fetch(req).then((res) => { if (res && res.ok) { cache.put(req, res.clone()); } return res; }).catch(() => hit);
            return hit || refresh;
        })));
    }
});
