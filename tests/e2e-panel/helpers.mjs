// Utilidades compartidas de la batería E2E de los paneles (admin · instructor · alumno).
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const here = path.dirname(fileURLToPath(import.meta.url));
export const { chromium } = require(path.join(here, '../e2e-stagehand/node_modules/playwright-core'));

export const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
export const CHROME = process.env.E2E_CHROME || path.join(process.env.HOME || '', '.cache/ms-playwright/chromium-1228/chrome-linux64/chrome');
export const SHOTS = path.join(here, 'screenshots');
fs.mkdirSync(SHOTS, { recursive: true });

export const ROLES = {
    admin: { email: 'admin@amentotech.com', password: 'google', home: '/admin/estadisticas' },
    tutor: { email: 'instructora@horizontia.test', password: 'Password123!', home: '/tutor/panel' },
    student: { email: 'alumno.pagado@horizontia.test', password: 'Password123!', home: '/mis-cursos' },
};

export async function launch() {
    return chromium.launch({ executablePath: CHROME, args: ['--no-sandbox'] });
}

/** Nuevo contexto con un usuario autenticado y un recolector de errores. */
export async function session(browser, role, viewport = { width: 1440, height: 900 }) {
    const ctx = await browser.newContext({ viewport, reducedMotion: 'reduce' });
    await ctx.addCookies([{ name: 'cookie_consent', value: '1', url: BASE }]);
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
    page.on('console', (m) => {
        if (m.type() === 'error' && !/favicon|Failed to load resource|net::ERR/.test(m.text())) errors.push(`console: ${m.text()}`);
    });
    page.on('response', (r) => { if (r.status() >= 500) errors.push(`HTTP ${r.status()} ${r.url()}`); });
    const { email, password } = ROLES[role];
    await page.goto(`${BASE}/iniciar-sesion`);
    await page.fill('#username', email);
    await page.fill('#password', password);
    await page.click('form.am-login-form button');
    await page.waitForURL((u) => !u.pathname.startsWith('/iniciar-sesion'));
    await page.waitForLoadState('networkidle');
    return { ctx, page, errors, role };
}

/** Marca el documento: si sigue ahí tras navegar, no hubo recarga completa. */
export const mark = (page) => page.evaluate(() => { window.__e2eMarker = Math.random().toString(36).slice(2); return window.__e2eMarker; });
export const sameDocument = (page, m) => page.evaluate((x) => window.__e2eMarker === x, m);

export async function settle(page, ms = 700) {
    await page.waitForTimeout(ms);
    await page.waitForLoadState('networkidle').catch(() => {});
}

/** Ejecuta código PHP en el contenedor de la app (para crear datos "en vivo"). */
export function artisanTinker(code) {
    return execFileSync('docker', ['exec', '-u', 'sail', 'horizontia-laravel.test-1', 'php', 'artisan', 'tinker', `--execute=${code}`], { encoding: 'utf8' });
}

/** Luminancia relativa y contraste WCAG entre dos colores "rgb(a)". */
export function contrast(fg, bg) {
    const parse = (c) => c.match(/[\d.]+/g).slice(0, 3).map(Number);
    const lum = ([r, g, b]) => { const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
    const [a, b] = [lum(parse(fg)), lum(parse(bg))];
    return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
}
