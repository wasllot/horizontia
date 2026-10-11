// Ajustes › Mantenimiento de punta a punta: el admin lo activa desde la pantalla, un visitante ve el aviso en español y el admin sigue entrando.
//   node --test --test-reporter=spec tests/e2e-panel/maintenance.e2e.mjs
import test from 'node:test';
import assert from 'node:assert/strict';
import { launch, session, BASE, SHOTS } from './helpers.mjs';

const browser = await launch();
test.after(() => browser.close());

test('mantenimiento: activar desde Ajustes, aviso a visitantes y apagar', async () => {
    const s = await session(browser, 'admin');
    const guest = await browser.newContext();
    try {
        const { page } = s;
        await page.goto(BASE + '/admin/ajustes#mantenimiento', { waitUntil: 'networkidle' });
        await page.locator('#mantenimiento.active').waitFor();
        assert.equal(await page.locator('#mantenimiento input[type=radio]').first().isVisible(), true, 'los botones de opción se ven');
        await page.screenshot({ path: `${SHOTS}/admin-mantenimiento.png`, fullPage: true });

        const on = async (value) => {
            await page.locator(`#mantenimiento input[name=maintenance_mode][value=${value}]`).evaluate((el) => el.closest('.op-radiobtn').querySelector('label').click());
            await page.locator('#mantenimiento .op-btn.update-section-settings').first().click();
            await page.waitForTimeout(1800);
        };

        await on('Yes');
        try {
            const g = await guest.newPage();
            const res = await g.goto(BASE + '/', { waitUntil: 'networkidle' });
            assert.equal(res.status(), 503);
            assert.equal(await g.locator('.ln-content img').isVisible(), true, 'el logo se ve');
            assert.match(await g.locator('.ln-content').innerText(), /Volvemos pronto/);
            await g.screenshot({ path: `${SHOTS}/sitio-mantenimiento.png` });
            await page.goto(BASE + '/', { waitUntil: 'networkidle' });
            assert.equal(await page.locator('.ln-content').count(), 0, 'el administrador ve el sitio normal');
        } finally {
            await page.goto(BASE + '/admin/ajustes#mantenimiento', { waitUntil: 'networkidle' });
            await on('No');
        }
        const after = await (await guest.newPage()).goto(BASE + '/', { waitUntil: 'networkidle' });
        assert.equal(after.status(), 200, 'al apagarlo el sitio vuelve a abrir');
    } finally { await guest.close(); await s.ctx.close(); }
});
