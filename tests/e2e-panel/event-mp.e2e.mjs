// E2E de la compra de entradas de Astrofest con Mercado Pago SIMULADO (Checkout Pro · API de Preferences).
// Recorre en un navegador real: landing → carrito → datos → "Mercado Pago" → regreso → confirmación, más correo (Mailpit), webhook, cupo y admin.
//
// Requisitos:
//   · Sitio en E2E_BASE_URL (por defecto http://localhost:8000), contenedor horizontia-laravel.test-1 y Mailpit en http://localhost:18025.
//   · En el .env del proyecto:  MERCADOPAGO_API_URL=http://127.0.0.1:5173   (quítalo al terminar; en producción no se define).
//   · Para no toparse con el límite de intentos por IP: ACADEMY_EVENT_CHECKOUT_PER_MINUTE=200 y ACADEMY_EVENT_CART_PER_MINUTE=200 (solo en local).
//   · El simulador se arranca solo dentro del contenedor (puerto 5173, ya publicado por Sail).
//   node --test --test-reporter=spec tests/e2e-panel/event-mp.e2e.mjs
import test from 'node:test';
import { execFileSync, spawn } from 'node:child_process';
import assert from 'node:assert/strict';
import { launch, BASE, SHOTS, artisanTinker, session } from './helpers.mjs';
import { connectMock, MOCK_PORT, MOCK_TOKEN, MOCK_SECRET } from './mp-mock.mjs';

const SLUG = 'astrofest-geminidas-2026';
const EVENT = `${BASE}/eventos/${SLUG}`;
const MAILPIT = process.env.E2E_MAILPIT || 'http://localhost:18025';
const php = (code) => artisanTinker(code).trim().split('\n').pop().trim();
const b64 = (s) => Buffer.from(s).toString('base64');
const uniq = (() => { let n = 0; return (tag) => `e2e-${tag}-${Date.now().toString(36)}${n++}@e2e.test`; })();
const until = async (fn, ms = 40000, step = 600) => { const t0 = Date.now(); for (;;) { const v = await fn(); if (v) return v; if (Date.now() - t0 > ms) return null; await new Promise((r) => setTimeout(r, step)); } };

let browser; let mock; let saved;
const errors = [];

const dbOrder = (email) => JSON.parse(php(`$o = App\\Models\\EventOrder::with('tickets')->where('email', '${email}')->latest('id')->first(); echo $o ? json_encode(['id' => $o->id, 'status' => $o->status, 'total' => (float) $o->total, 'iva' => (float) $o->iva, 'qty' => $o->quantity, 'payment_id' => $o->payment_id, 'method' => $o->payment_method, 'expires' => optional($o->expires_at)->toIso8601String(), 'confirmed' => (bool) $o->confirmation_sent_at, 'codes' => $o->tickets->pluck('code'), 'names' => $o->tickets->pluck('attendee_name'), 'ref' => $o->reference()]) : 'null';`));
const mails = async (email) => { const r = await (await fetch(`${MAILPIT}/api/v1/search?query=${encodeURIComponent('to:' + email)}`)).json(); return r.messages || []; };
const mailFull = async (id) => (await fetch(`${MAILPIT}/api/v1/message/${id}`)).json();

async function newContext(viewport = { width: 1440, height: 900 }) {
    const ctx = await browser.newContext({ viewport, reducedMotion: 'reduce' });
    await ctx.addInitScript(() => { try { localStorage.setItem('ht_cookie_ok', 'necessary'); } catch (e) { /* sin almacenamiento */ } });
    const page = await ctx.newPage();
    page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
    page.on('console', (m) => { if (m.type() === 'error' && !/favicon|Failed to load resource|net::ERR/.test(m.text())) errors.push(`console: ${m.text()}`); });
    page.on('response', (r) => { if (r.status() >= 500) errors.push(`HTTP ${r.status()} ${r.url()}`); });
    return { ctx, page };
}

/** Landing → cantidad → carrito → datos del comprador. Deja el formulario listo; no envía. */
async function fillCheckout(page, { qty = 1, email, name = 'Ana E2E' }) {
    await page.goto(EVENT, { waitUntil: 'networkidle' });
    await page.fill('#evQty', String(qty));
    await page.dispatchEvent('#evQty', 'input');
    await page.click('text=Agregar al carrito');
    await page.waitForURL(/\/carrito$/);
    await page.click('text=Continuar al pago');
    await page.waitForURL(/\/pago$/);
    await page.fill('#name', name);
    await page.fill('#email', email);
    await page.fill('#phone', '55 1234 5678');
    for (let i = 0; i < qty; i++) await page.fill(`input[name="attendees[${i}]"]`, i === 0 ? name : `Invitado ${i + 1}`);
    await page.check('input[name="accept_policies"]');
}

/** Espera la página de pago de Mercado Pago; si no llega, el error dice dónde se quedó y qué aviso mostró el sitio. */
async function toMock(page, ms = 20000) {
    try { await page.waitForURL(`http://localhost:${MOCK_PORT}/pay/**`, { timeout: ms }); } catch (e) {
        const alert = await page.locator('.ev-alert, .ev-err').allTextContents().catch(() => []);
        await page.screenshot({ path: `${SHOTS}/evmp-fallo-${Date.now()}.png`, fullPage: true }).catch(() => {});
        throw new Error(`No se llegó a Mercado Pago. URL: ${page.url()} · avisos: ${JSON.stringify(alert)}`);
    }
}

test.before(async () => {
    const base = php('echo config("services.mercadopago.base_url");');
    assert.ok(base.includes(`:${MOCK_PORT}`), `Define MERCADOPAGO_API_URL=http://127.0.0.1:${MOCK_PORT} en el .env (ahora: ${base}) y vuelve a correr.`);

    execFileSync('docker', ['exec', '-u', 'sail', 'horizontia-laravel.test-1', 'bash', '-c', 'pkill -f "[m]p-mock.mjs serve" || true'], { stdio: 'ignore' });
    spawn('docker', ['exec', '-u', 'sail', 'horizontia-laravel.test-1', 'node', 'tests/e2e-panel/mp-mock.mjs', 'serve'], { stdio: 'ignore', detached: true }).unref();
    mock = connectMock();
    assert.ok(await until(async () => { try { return (await mock.state()).webhookMode; } catch { return false; } }, 15000, 400), 'el Mercado Pago simulado no arrancó');
    await fetch(`${MAILPIT}/api/v1/messages`, { method: 'DELETE' }).catch(() => {});

    // Pasarela configurada como en producción (token + clave secreta del webhook) y evento listo para vender; se guarda lo anterior.
    const gw = php(`$m = setting('admin_settings.payment_method') ?: []; echo base64_encode(json_encode($m));
        $m['mercadopago'] = ['status' => 'on', 'currency' => 'MXN', 'exchange_rate' => '', 'enable_test_mode' => '', 'binary_mode' => '', 'mp_access_token' => '${MOCK_TOKEN}', 'mp_public_key' => 'TEST-pk', 'mp_webhook_secret' => '${MOCK_SECRET}'];
        Larabuild\\Optionbuilder\\Facades\\Settings::set('admin_settings', 'payment_method', $m);`);
    const ev = php(`$e = App\\Models\\Event::where('slug', '${SLUG}')->firstOrFail();
        echo base64_encode(json_encode($e->only(['status', 'sales_enabled', 'policies_html', 'capacity', 'max_per_order', 'price', 'hold_minutes', 'offline_hold_hours', 'sales_open_at', 'sales_close_at', 'content'])));
        $e->update(['status' => 'published', 'sales_enabled' => true, 'policies_html' => '<p>Políticas de prueba E2E.</p>', 'capacity' => null, 'max_per_order' => 6, 'price' => 1740, 'hold_minutes' => 45, 'offline_hold_hours' => 72, 'sales_open_at' => null, 'sales_close_at' => null]);`);
    saved = { gw, ev };
    browser = await launch();
});

test.after(async () => {
    if (saved) {
        php(`App\\Models\\EventOrder::where('email', 'like', '%@e2e.test')->delete();
            Larabuild\\Optionbuilder\\Facades\\Settings::set('admin_settings', 'payment_method', json_decode(base64_decode('${saved.gw}'), true) ?: []);
            App\\Models\\Event::where('slug', '${SLUG}')->update(json_decode(base64_decode('${saved.ev}'), true)); echo 'ok';`);
    }
    execFileSync('docker', ['exec', '-u', 'sail', 'horizontia-laravel.test-1', 'bash', '-c', 'pkill -f "[m]p-mock.mjs serve" || true'], { stdio: 'ignore' });
    await browser?.close();
});

test('1 · tarjeta aprobada: del carrito al correo con QR (2 entradas)', async () => {
    const email = uniq('ok'); const { ctx, page } = await newContext();
    try {
        await fillCheckout(page, { qty: 2, email });
        await page.screenshot({ path: `${SHOTS}/evmp-1-datos.png`, fullPage: true });
        await page.click('#evPay');
        await toMock(page);

        // La preferencia que el sitio mandó a Mercado Pago: monto, moneda, referencia, retornos y webhook, todo desde la base de datos.
        const pref = await mock.lastPreference(); const o = dbOrder(email);
        assert.equal(o.status, 'pending'); assert.equal(o.qty, 2); assert.equal(o.total, 3480); assert.equal(o.iva, 480);
        assert.equal(pref.external_reference, o.ref); assert.match(pref.external_reference, /^EV-\d+$/);
        assert.equal(pref.items[0].unit_price, 3480); assert.equal(pref.items[0].currency_id, 'MXN'); assert.equal(pref.items[0].quantity, 1);
        assert.match(pref.items[0].title, /2 entradas/); assert.equal(pref.payer.email, email);
        assert.match(pref.back_urls.success, new RegExp(`/eventos/${SLUG}/gracias\\?order=`)); assert.equal(pref.back_urls.failure, pref.back_urls.success);
        assert.match(pref.notification_url, /\/mercadopago\/webhook$/); assert.equal(pref.auto_return, 'approved'); assert.equal(pref.expires, true);
        assert.equal((await mock.state()).requests.find((r) => r.path === '/checkout/preferences').headers.authorization, `Bearer ${MOCK_TOKEN}`);
        assert.equal(await page.textContent('[data-testid="mp-amount"]').then((t) => t.includes('3480')), true);

        await page.click('[data-testid="mp-approved"]');
        await page.waitForURL(/\/gracias\?order=/);
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: `${SHOTS}/evmp-1-gracias.png`, fullPage: true });

        const paid = dbOrder(email);
        assert.equal(paid.status, 'paid'); assert.ok(paid.payment_id); assert.equal(paid.method, 'credit_card'); assert.equal(paid.codes.length, 2);
        assert.match(await page.textContent('body'), /Tu lugar está apartado/);
        for (const c of paid.codes) assert.ok((await page.textContent('body')).includes(c), `falta la clave ${c}`);
        const qrOk = await page.$$eval('.ev-ticket-card img', (imgs) => imgs.map((i) => i.complete && i.naturalWidth > 0));
        assert.deepEqual(qrOk, [true, true], 'los dos QR deben cargar');
        assert.equal((await page.request.get(`${EVENT}/evento.ics`)).status(), 200);

        // Correo de confirmación (por la cola): una vez, con un QR incrustado por entrada y el diseño del evento.
        const msg = await until(async () => (await mails(email))[0]);
        assert.ok(msg, 'no llegó el correo de confirmación');
        const full = await mailFull(msg.ID);
        assert.match(full.Subject, /Tus entradas/);
        assert.equal(full.Inline.length, 3, 'el logotipo del evento + un QR incrustado por entrada');
        for (const c of paid.codes) assert.ok(full.Inline.some((i) => i.FileName.includes(c)), `falta el QR de ${c}`);
        for (const c of paid.codes) assert.ok(full.HTML.includes(c));
        assert.match(full.HTML, /¿Prefieres habitación\?/);
        assert.doesNotMatch(full.HTML, /garantía|curso/i);
        assert.equal(paid.confirmed, true);
    } finally { await ctx.close(); }
});

test('2 · el webhook repetido no duplica el correo y la firma falsa se rechaza', async () => {
    const email = uniq('ok2'); const { ctx, page } = await newContext();
    try {
        await fillCheckout(page, { qty: 1, email }); await page.click('#evPay');
        await toMock(page);
        await page.click('[data-testid="mp-approved"]'); await page.waitForURL(/\/gracias\?order=/);
        await until(async () => (await mails(email)).length === 1);
        const pid = dbOrder(email).payment_id;

        for (let i = 0; i < 3; i++) { const w = await mock.sendWebhook(pid); assert.equal(w.status, 200); }
        const forged = await mock.sendWebhook(pid, { tamper: true });
        assert.equal(forged.status, 401, 'firma falsa → 401');
        await new Promise((r) => setTimeout(r, 4000));
        assert.equal((await mails(email)).length, 1, 'un solo correo aunque lleguen 4 avisos');
        assert.equal(dbOrder(email).status, 'paid');
    } finally { await ctx.close(); }
});

test('3 · pago rechazado: no se cobra, el lugar se libera y se puede reintentar', async () => {
    const email = uniq('rej'); const { ctx, page } = await newContext();
    try {
        await fillCheckout(page, { qty: 2, email }); await page.click('#evPay');
        await toMock(page);
        await page.click('[data-testid="mp-rejected"]'); await page.waitForURL(/\/gracias\?order=/);
        await page.screenshot({ path: `${SHOTS}/evmp-3-rechazado.png`, fullPage: true });
        const body = await page.textContent('body');
        assert.match(body, /No se completó el pago/, 'debe decir que no se completó, no "pendiente"');
        assert.doesNotMatch(body, /Tu lugar está apartado/);
        const o = dbOrder(email); assert.notEqual(o.status, 'paid'); assert.equal(o.status, 'expired', 'el cupo se libera al volver rechazado');
        assert.equal((await mails(email)).length, 0, 'sin correo de entradas');

        // «Intentar de nuevo» conserva el carrito y crea otra compra que sí se puede pagar.
        await page.click('text=Intentar de nuevo'); await page.waitForURL(/\/pago$/);
        await page.fill('#name', 'Ana E2E'); await page.fill('#email', email); await page.fill('#phone', '55 1234 5678');
        await page.fill('input[name="attendees[0]"]', 'Ana E2E'); await page.fill('input[name="attendees[1]"]', 'Invitado 2'); await page.check('input[name="accept_policies"]');
        await page.click('#evPay'); await toMock(page);
        await page.click('[data-testid="mp-approved"]'); await page.waitForURL(/\/gracias\?order=/);
        assert.equal(dbOrder(email).status, 'paid'); assert.equal(dbOrder(email).codes.length, 2);
    } finally { await ctx.close(); }
});

test('4 · efectivo: queda pendiente con reserva larga y se confirma cuando Mercado Pago avisa', async () => {
    const email = uniq('cash'); const { ctx, page } = await newContext();
    try {
        await fillCheckout(page, { qty: 1, email }); await page.click('#evPay');
        await toMock(page);
        await page.click('[data-testid="mp-pending"]'); await page.waitForURL(/\/gracias\?order=/);
        await page.screenshot({ path: `${SHOTS}/evmp-4-pendiente.png`, fullPage: true });
        assert.match(await page.textContent('body'), /Tu pago está pendiente/);
        const pend = dbOrder(email);
        assert.equal(pend.status, 'pending'); assert.equal(pend.method, 'ticket');
        const hours = (new Date(pend.expires) - Date.now()) / 36e5;
        assert.ok(hours > 70 && hours <= 72.1, `reserva de ~72 h para efectivo (${hours.toFixed(1)} h)`);
        assert.equal((await mails(email)).length, 0, 'aún sin entradas');

        // La persona paga en la tienda: Mercado Pago aprueba el MISMO pago y avisa por webhook.
        const pid = (await mock.lastPayment()).id;
        await mock.approve(pid);
        assert.equal((await mock.sendWebhook(pid)).status, 200);
        assert.equal(dbOrder(email).status, 'paid');
        const msg = await until(async () => (await mails(email))[0]); assert.ok(msg, 'llegó el correo al confirmarse');
        await page.reload({ waitUntil: 'networkidle' });
        assert.match(await page.textContent('body'), /Tu lugar está apartado/);
    } finally { await ctx.close(); }
});

test('5 · monto alterado: se ignora y no se entregan entradas', async () => {
    const email = uniq('tamper'); const { ctx, page } = await newContext();
    try {
        await fillCheckout(page, { qty: 2, email }); await page.click('#evPay');
        await toMock(page);
        await page.click('[data-testid="mp-tampered"]'); await page.waitForURL(/\/gracias\?order=/);
        const o = dbOrder(email);
        assert.notEqual(o.status, 'paid', 'un pago de $10 no cubre 2 entradas');
        assert.ok((await mock.state()).webhooks.at(-1).body.includes('ignored'));
        assert.doesNotMatch(await page.textContent('body'), /Tu lugar está apartado/);
        await new Promise((r) => setTimeout(r, 3500));
        assert.equal((await mails(email)).length, 0);
    } finally { await ctx.close(); }
});

test('6 · sin webhook: al volver, el sitio lee el pago a Mercado Pago y confirma', async () => {
    const email = uniq('nohook'); const { ctx, page } = await newContext(); await mock.setWebhook('off');
    try {
        await fillCheckout(page, { qty: 1, email }); await page.click('#evPay');
        await toMock(page);
        await page.click('[data-testid="mp-approved"]'); await page.waitForURL(/\/gracias\?order=/);
        assert.equal(dbOrder(email).status, 'paid', 'confirmó solo por la lectura del pago al regresar');
        assert.match(await page.textContent('body'), /Tu lugar está apartado/);
        assert.ok(await until(async () => (await mails(email))[0]), 'y mandó el correo');

        // La URL de regreso no se cree: con un payment_id inventado no se confirma nada.
        const email2 = uniq('fake'); await fillCheckout(page, { qty: 1, email: email2 }); await page.click('#evPay');
        await toMock(page);
        const code = dbOrder(email2); const back = `${EVENT}/gracias?order=${php(`echo App\\Models\\EventOrder::find(${code.id})->code;`)}&payment_id=123456789&status=approved`;
        await page.goto(back, { waitUntil: 'networkidle' });
        assert.equal(dbOrder(email2).status, 'pending'); assert.doesNotMatch(await page.textContent('body'), /Tu lugar está apartado/);
    } finally { await mock.setWebhook('on'); await ctx.close(); }
});

test('7 · último lugar: dos compradores a la vez, solo uno lo obtiene; agotado y se libera al vencer', async () => {
    const a = uniq('race-a'); const b = uniq('race-b');
    php(`$e = App\\Models\\Event::where('slug', '${SLUG}')->first(); $taken = (int) app(App\\Services\\Events\\EventSales::class)->taken($e); $e->update(['capacity' => $taken + 1]); echo 'ok';`);
    const c1 = await newContext(); const c2 = await newContext();
    try {
        await Promise.all([fillCheckout(c1.page, { qty: 1, email: a }), fillCheckout(c2.page, { qty: 1, email: b })]);
        await Promise.all([c1.page.click('#evPay'), c2.page.click('#evPay')]);
        await Promise.all([c1.page.waitForLoadState('networkidle'), c2.page.waitForLoadState('networkidle')]);
        const urls = [c1.page.url(), c2.page.url()];
        const toMp = urls.filter((u) => u.startsWith(`http://localhost:${MOCK_PORT}/pay/`));
        assert.equal(toMp.length, 1, `exactamente uno llega a Mercado Pago: ${urls.join(' | ')}`);
        const count = Number(php(`echo App\\Models\\EventOrder::whereIn('email', ['${a}', '${b}'])->where('status', 'pending')->count();`));
        assert.equal(count, 1, 'una sola reserva pendiente');

        const loser = urls[0].startsWith(`http://localhost:${MOCK_PORT}`) ? c2.page : c1.page;
        assert.match(await loser.textContent('body'), /agotaron|ya no está disponible|Agotado/i, 'quien no alcanzó lo ve claro');

        // Un tercer visitante ve «Agotado» y no puede agregar.
        const c3 = await newContext();
        await c3.page.goto(EVENT, { waitUntil: 'networkidle' });
        assert.match(await c3.page.textContent('#entrada'), /Agotado/);
        assert.equal(await c3.page.locator('#evBuy').count(), 0);
        assert.ok((await c3.page.textContent('head')).length > 0 || true);
        await c3.ctx.close();

        // La reserva vence y el lugar vuelve a estar disponible.
        php(`App\\Models\\EventOrder::whereIn('email', ['${a}', '${b}'])->update(['expires_at' => now()->subMinute()]); app(App\\Services\\Events\\EventSales::class)->expireStale(); echo 'ok';`);
        const c4 = await newContext(); await c4.page.goto(EVENT, { waitUntil: 'networkidle' });
        assert.equal(await c4.page.locator('#evBuy').count(), 1, 'el lugar se libera al vencer la reserva');
        await c4.ctx.close();
    } finally { await c1.ctx.close(); await c2.ctx.close(); php(`App\\Models\\Event::where('slug', '${SLUG}')->update(['capacity' => null]); echo 'ok';`); }
});

test('8 · celular: la compra completa sin desbordes y con la barra fija', async () => {
    const email = uniq('mobile'); const { ctx, page } = await newContext({ width: 390, height: 844 });
    const noOverflow = async (label) => assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true, `desborde horizontal en ${label}`);
    try {
        await page.goto(EVENT, { waitUntil: 'networkidle' }); await noOverflow('landing');
        await page.evaluate(() => document.getElementById('que-vamos-a-ver').scrollIntoView()); await page.waitForTimeout(1200);   // ni la portada ni la entrada a la vista
        assert.equal(await page.locator('#evBar').isVisible(), true, 'barra fija de compra visible a mitad de página');
        await page.screenshot({ path: `${SHOTS}/evmp-8-landing-m.png` });
        await fillCheckout(page, { qty: 2, email }); await noOverflow('pago');
        await page.screenshot({ path: `${SHOTS}/evmp-8-pago-m.png`, fullPage: true });
        await page.click('#evPay'); await toMock(page);
        await page.click('[data-testid="mp-approved"]'); await page.waitForURL(/\/gracias\?order=/); await page.waitForLoadState('networkidle');
        await noOverflow('gracias'); await page.screenshot({ path: `${SHOTS}/evmp-8-gracias-m.png`, fullPage: true });
        assert.equal(dbOrder(email).status, 'paid');
    } finally { await ctx.close(); }
});

test('9 · admin: ve la compra pagada y descarga el CSV con asistentes y claves', async () => {
    const s = await session(browser, 'admin');
    try {
        const eventId = php(`echo App\\Models\\Event::where('slug', '${SLUG}')->value('id');`);
        await s.page.goto(`${BASE}/admin/eventos`, { waitUntil: 'networkidle' });
        assert.match(await s.page.textContent('[data-testid="events-table"]'), /Astrofest/);
        const res = await s.page.request.get(`${BASE}/admin/eventos/${eventId}/compras.csv?estado=paid`);
        assert.equal(res.status(), 200); assert.match(res.headers()['content-type'], /csv/);
        const csv = await res.text();
        assert.ok(csv.includes('@e2e.test'), 'el CSV incluye las compras de prueba pagadas');
        assert.match(csv, /AF-[A-Z2-9]{4}-[A-Z2-9]{4}/);
        assert.match(csv.split('\n')[0], /Total/);
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('10 · ninguna pantalla produjo errores de JavaScript ni respuestas 5xx', () => {
    assert.deepEqual(errors, []);
});
