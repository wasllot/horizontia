// Mercado Pago simulado (Checkout Pro · API de Preferences) para las pruebas E2E. NO es el real: sirve para recorrer todo el flujo sin credenciales.
// Habla igual que la API que usa el sitio:
//   POST /checkout/preferences      → crea la preferencia y devuelve init_point (una página de pago simulada)
//   GET  /v1/payments/:id           → estado del pago (el sitio SIEMPRE lo relee; nunca confía en la URL ni en el cuerpo del webhook)
//   POST /pay/:pref/:resultado      → lo que hace la persona en la página de pago: approved | rejected | pending (efectivo) | tampered (monto alterado)
// Tras cada pago manda el webhook firmado (x-signature HMAC) al sitio y devuelve a la persona a back_urls, como Mercado Pago.
//
// Corre DENTRO del contenedor de la app (el contenedor no alcanza procesos del equipo anfitrión) en el puerto 5173, que Sail ya publica:
//   docker exec -d -u sail horizontia-laravel.test-1 node tests/e2e-panel/mp-mock.mjs serve
// El sitio lo usa con MERCADOPAGO_API_URL=http://127.0.0.1:5173 y el navegador abre sus páginas en http://localhost:5173.
// Las pruebas lo gobiernan por /__mock/* (ver connectMock).
import http from 'node:http';
import crypto from 'node:crypto';

export const MOCK_PORT = Number(process.env.MP_MOCK_PORT || 5173);
export const MOCK_TOKEN = 'TEST-e2e-access-token';
export const MOCK_SECRET = 'e2e-webhook-secret';

export function startMock({ appBase = 'http://127.0.0.1:8000', port = MOCK_PORT, publicUrl = `http://localhost:${port}` } = {}) {
    const state = { preferences: new Map(), payments: new Map(), requests: [], webhooks: [], webhookMode: 'on', nextPayment: 900000 };

    const sign = (dataId, { secret = MOCK_SECRET, tamper = false } = {}) => {
        const ts = String(Math.floor(Date.now() / 1000));
        const rid = 'req-' + crypto.randomBytes(5).toString('hex');
        const v1 = crypto.createHmac('sha256', tamper ? 'otro-secreto' : secret).update(`id:${String(dataId).toLowerCase()};request-id:${rid};ts:${ts};`).digest('hex');
        return { 'x-signature': `ts=${ts},v1=${v1}`, 'x-request-id': rid, 'content-type': 'application/json' };
    };

    /** Lo que Mercado Pago hace al cambiar un pago: avisar al sitio. */
    async function sendWebhook(paymentId, opts = {}) {
        const res = await fetch(`${appBase}/mercadopago/webhook?type=payment&data.id=${paymentId}`, {
            method: 'POST', headers: sign(paymentId, opts),
            body: JSON.stringify({ action: 'payment.updated', type: 'payment', data: { id: String(paymentId) } }),
        });
        const out = { status: res.status, body: await res.text() };
        state.webhooks.push({ paymentId, ...out });
        return out;
    }

    function createPayment(pref, outcome) {
        const id = state.nextPayment++;
        const unit = pref.items[0];
        const base = { id, external_reference: pref.external_reference, currency_id: unit.currency_id, transaction_amount: unit.unit_price, payment_type_id: 'credit_card', preference: pref.id };
        const variants = {
            approved: { status: 'approved' },
            rejected: { status: 'rejected', status_detail: 'cc_rejected_insufficient_amount' },
            pending: { status: 'pending', status_detail: 'pending_waiting_payment', payment_type_id: 'ticket' },
            tampered: { status: 'approved', transaction_amount: 10 },
        };
        const payment = { ...base, ...(variants[outcome] || variants.approved) };
        state.payments.set(String(id), payment);
        return payment;
    }

    const page = (pref) => `<!doctype html><meta charset="utf-8"><title>Mercado Pago (simulado)</title>
<body style="font:16px system-ui;max-width:520px;margin:40px auto;padding:0 16px">
<h1 style="margin:0 0 6px">Mercado Pago <small style="color:#888">(simulado)</small></h1>
<p data-testid="mp-title">${pref.items[0].title}</p><p data-testid="mp-amount"><b>$${pref.items[0].unit_price} ${pref.items[0].currency_id}</b></p><p data-testid="mp-ref">${pref.external_reference}</p>
${['approved|Pagar con tarjeta (aprobado)', 'rejected|Pago rechazado', 'pending|Pagar en efectivo (pendiente)', 'tampered|Pago con monto alterado'].map((x) => { const [k, t] = x.split('|'); return `<form method="post" action="/pay/${pref.id}/${k}" style="margin:8px 0"><button data-testid="mp-${k}" style="padding:12px 18px;font-size:16px">${t}</button></form>`; }).join('')}</body>`;

    const server = http.createServer(async (req, res) => {
        const url = new URL(req.url, `http://localhost:${port}`);
        let raw = ''; for await (const c of req) raw += c;
        let body = null; try { body = raw ? JSON.parse(raw) : null; } catch { body = raw; }
        state.requests.push({ method: req.method, path: url.pathname, headers: req.headers, body });
        const json = (code, obj) => { res.writeHead(code, { 'content-type': 'application/json' }); res.end(JSON.stringify(obj)); };

        if (req.method === 'POST' && url.pathname === '/checkout/preferences') {
            if (req.headers.authorization !== `Bearer ${MOCK_TOKEN}`) return json(401, { message: 'invalid access token' });
            const id = 'PREF-' + crypto.randomBytes(4).toString('hex');
            const pref = { id, ...body };
            state.preferences.set(id, pref);
            const pay = `${publicUrl}/pay/${id}`;
            return json(201, { id, init_point: pay, sandbox_init_point: pay });
        }
        // --- Control para las pruebas ---
        if (url.pathname.startsWith('/__mock/')) {
            const act = url.pathname.slice(8); const q = url.searchParams;
            if (act === 'state') return json(200, { preferences: [...state.preferences.values()], payments: [...state.payments.values()], requests: state.requests, webhooks: state.webhooks, webhookMode: state.webhookMode });
            if (act === 'webhook') return json(200, await sendWebhook(q.get('payment'), { tamper: q.get('tamper') === '1' }));
            if (act === 'mode') { state.webhookMode = q.get('webhook') === 'off' ? 'off' : 'on'; return json(200, { webhookMode: state.webhookMode }); }
            if (act === 'approve') { const p = state.payments.get(q.get('payment')); if (p) p.status = 'approved'; return json(200, p || {}); }
            if (act === 'reset') { state.preferences.clear(); state.payments.clear(); state.requests.length = 0; state.webhooks.length = 0; state.webhookMode = 'on'; return json(200, { ok: true }); }
            return json(404, { message: 'acción desconocida' });
        }
        let m;
        if (req.method === 'GET' && (m = url.pathname.match(/^\/v1\/payments\/(\d+)$/))) {
            const p = state.payments.get(m[1]);
            return p ? json(200, p) : json(404, { message: 'Payment not found' });
        }
        if (req.method === 'GET' && (m = url.pathname.match(/^\/pay\/([\w-]+)$/))) {
            const pref = state.preferences.get(m[1]);
            if (!pref) { res.writeHead(404); return res.end('preferencia desconocida'); }
            res.writeHead(200, { 'content-type': 'text/html; charset=utf-8' }); return res.end(page(pref));
        }
        if (req.method === 'POST' && (m = url.pathname.match(/^\/pay\/([\w-]+)\/(approved|rejected|pending|tampered)$/))) {
            const pref = state.preferences.get(m[1]);
            if (!pref) { res.writeHead(404); return res.end(); }
            const payment = createPayment(pref, m[2]);
            if (state.webhookMode === 'on') await sendWebhook(payment.id);
            const back = new URL(m[2] === 'rejected' ? pref.back_urls.failure : m[2] === 'pending' ? pref.back_urls.pending : pref.back_urls.success);
            back.searchParams.set('payment_id', String(payment.id)); back.searchParams.set('collection_id', String(payment.id));
            back.searchParams.set('status', payment.status === 'approved' ? 'approved' : payment.status); back.searchParams.set('external_reference', pref.external_reference);
            res.writeHead(303, { location: back.toString() }); return res.end();
        }
        res.writeHead(404); res.end('not found');
    });

    return new Promise((resolve) => server.listen(port, '::', () => resolve({
        state, sendWebhook, createPayment, sign, port,
        lastPreference: () => [...state.preferences.values()].at(-1),
        close: () => new Promise((r) => { server.closeAllConnections?.(); server.close(r); }),
    })));
}

/** Cliente de las pruebas: habla con el simulador que corre en el contenedor. */
export function connectMock(base = `http://localhost:${MOCK_PORT}`) {
    const get = async (path) => (await fetch(base + path)).json();
    return {
        base,
        state: () => get('/__mock/state'),
        lastPreference: async () => (await get('/__mock/state')).preferences.at(-1),
        lastPayment: async () => (await get('/__mock/state')).payments.at(-1),
        sendWebhook: (paymentId, { tamper = false } = {}) => get(`/__mock/webhook?payment=${paymentId}${tamper ? '&tamper=1' : ''}`),
        setWebhook: (mode) => get(`/__mock/mode?webhook=${mode}`),
        approve: (paymentId) => get(`/__mock/approve?payment=${paymentId}`),
        reset: () => get('/__mock/reset'),
    };
}

// Modo servidor: node tests/e2e-panel/mp-mock.mjs serve
if (process.argv[2] === 'serve') {
    const appBase = process.env.MP_APP_BASE || 'http://127.0.0.1:8000';
    await startMock({ appBase });
    console.log(`Mercado Pago simulado en :${MOCK_PORT} (app: ${appBase})`);
}
