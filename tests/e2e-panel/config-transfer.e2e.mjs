// E2E de Ajustes → Exportar / importar: descargar el archivo, subirlo, ver la vista previa e importar (modo "solo añadir").
//   node --test --test-reporter=spec tests/e2e-panel/config-transfer.e2e.mjs
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { launch, session, BASE, SHOTS } from './helpers.mjs';

const browser = await launch();
test.after(async () => { await browser.close(); });

test('exportar → subir → vista previa → importar', async () => {
    const s = await session(browser, 'admin');
    try {
        const { page } = s;
        await page.goto(`${BASE}/admin/exportar-importar`, { waitUntil: 'networkidle' });
        assert.match(await page.locator('h2').first().innerText(), /Exportar e importar/);
        assert.ok(await page.locator('a[href$="/admin/exportar-importar"]').count() >= 1, 'está en el menú de Ajustes');

        // Exportar: la selección por defecto es la segura y las credenciales no aparecen.
        const card = page.getByTestId('export-card');
        assert.equal(await card.getByText('Claves de API y credenciales').count(), 0);
        await page.screenshot({ path: `${SHOTS}/config-export.png`, fullPage: true });
        const [download] = await Promise.all([page.waitForEvent('download'), page.getByTestId('download-btn').click()]);
        const file = path.join(os.tmpdir(), `cfg-${Date.now()}.json`);
        await download.saveAs(file);
        const json = JSON.parse(fs.readFileSync(file, 'utf8'));
        assert.equal(json.meta.format, 'horizontia-config');
        assert.equal(json.meta.includes_credentials, false);
        for (const forbidden of ['courses', 'blogs', 'users']) assert.equal(json.data[forbidden], undefined);
        assert.ok(json.data.pages.length >= 1, 'incluye páginas');
        assert.doesNotMatch(fs.readFileSync(file, 'utf8'), /alumno\.pagado@/, 'sin datos de usuarios');

        // Importar
        await page.getByTestId('tab-import').click();
        await page.getByTestId('upload-input').setInputFiles(file);
        await page.getByTestId('preview-btn').waitFor({ timeout: 15000 });
        assert.ok(await page.getByText('Esto contiene el archivo').count() === 1);
        await page.screenshot({ path: `${SHOTS}/config-import.png`, fullPage: true });

        await page.getByTestId('preview-btn').click();
        await page.getByTestId('preview').waitFor({ timeout: 30000 });
        assert.match(await page.getByTestId('preview').innerText(), /Vista previa \(no se guardó nada\)/);

        page.once('dialog', (d) => d.accept());
        await page.getByTestId('import-btn').click();
        await page.getByText('Importación terminada').waitFor({ timeout: 60000 });
        assert.ok(await page.getByTestId('report-table').count() >= 1);
        await page.screenshot({ path: `${SHOTS}/config-done.png`, fullPage: true });
        assert.deepEqual(s.errors, []);
        fs.unlinkSync(file);
    } finally { await s.ctx.close(); }
});

test('un archivo que no es de exportación se rechaza con un mensaje claro', async () => {
    const s = await session(browser, 'admin');
    try {
        const { page } = s;
        const bad = path.join(os.tmpdir(), `bad-${Date.now()}.json`);
        fs.writeFileSync(bad, JSON.stringify({ hola: 'mundo' }));
        await page.goto(`${BASE}/admin/exportar-importar?tab=import`, { waitUntil: 'networkidle' });
        await page.getByTestId('upload-input').setInputFiles(bad);
        await page.getByTestId('import-error').waitFor({ timeout: 15000 });
        assert.match(await page.getByTestId('import-error').innerText(), /No es un archivo de exportación/);
        fs.unlinkSync(bad);
    } finally { await s.ctx.close(); }
});
