// E2E del editor visual de páginas: biblioteca con miniaturas, plantillas de inicio y VISTA PREVIA EN TIEMPO REAL de los bloques dinámicos.
//   node --test --test-reporter=spec tests/e2e-panel/page-editor.e2e.mjs
// Requiere el sitio en :8000 con los eventos de demostración (php artisan db:seed --class=DemoAcademySeeder).
import test from 'node:test';
import assert from 'node:assert/strict';
import { launch, session, BASE, SHOTS, artisanTinker } from './helpers.mjs';

const browser = await launch();
const STAMP = Date.now().toString(36);
let pageId;
test.after(async () => {
    await browser.close();
    if (pageId) { artisanTinker(`App\\Models\\SitePage::whereKey(${pageId})->get()->each->delete();`); }
});

test('editor: plantillas, miniaturas reales, vista previa en vivo, búsqueda y modo de vista previa', async () => {
    const s = await session(browser, 'admin', { width: 1600, height: 950 });
    try {
        const { page } = s;
        await page.goto(BASE + '/admin/paginas', { waitUntil: 'networkidle' });
        await page.click('button:has-text("Nueva página")');
        await page.waitForURL(/\/admin\/paginas\/(\d+)\/editor/, { timeout: 15000 });
        pageId = Number(page.url().match(/paginas\/(\d+)\/editor/)[1]);
        await page.waitForFunction(() => window.editor && /Listo|Guardado/.test(document.querySelector('#hzed-saved')?.textContent || ''), null, { timeout: 20000 });
        const canvas = page.frameLocator('#gjs iframe.gjs-frame');

        // 1. Biblioteca visual: Academia primero, miniaturas con la apariencia real y sin textos en inglés.
        const cats = await page.$$eval('#hzed-blocks .gjs-block-category .gjs-title', (e) => e.map((x) => x.textContent.trim()));
        assert.equal(cats[0], 'Academia', 'los bloques de la academia van primero: ' + cats);
        assert.ok(cats.includes('Cursos y eventos'));
        await page.waitForFunction(() => document.querySelectorAll('.hzed-thumb iframe').length >= 12, null, { timeout: 15000 });
        const labels = await page.$$eval('#hzed-blocks .gjs-block', (e) => e.map((x) => x.innerText));
        for (const english of ['Text', 'Link', 'Image', 'Textarea', 'Select']) assert.ok(!labels.some((l) => l.trim() === english), `queda "${english}" sin traducir`);

        // 2. Página vacía: plantillas de inicio.
        await page.waitForSelector('#hzed-empty:not([hidden])');
        await page.click('[data-starter=event]');
        await page.waitForTimeout(2500);

        // 3. Los bloques dinámicos se ven reales en el lienzo (no un recuadro gris).
        const info = canvas.locator('[data-hz-block=course-info]');
        await info.waitFor({ timeout: 8000 });
        await canvas.locator('[data-hz-block=course-info] .ht-course-info__card').waitFor({ timeout: 8000 });
        assert.ok(!(await canvas.locator('body').innerText()).includes('se muestran al publicar'), 'no debe haber marcador de posición');

        // 4. Bloque de eventos: cambiar un ajuste actualiza la vista previa al instante.
        await page.evaluate(() => { const c = window.editor.addComponents(window.editor.Blocks.get('hz-events').get('content')); window.editor.select(c[0]); });
        const cards = canvas.locator('[data-hz-block=events] .ev-card');
        await cards.first().waitFor({ timeout: 8000 });
        const before = await cards.count();
        await page.locator('#hzed-traits select').first().selectOption('6');
        await page.waitForFunction((n) => { const f = document.querySelector('iframe.gjs-frame'); return f.contentDocument.querySelectorAll('[data-hz-block=events] .ev-card').length > n; }, before, { timeout: 8000 });
        const input = page.locator('#hzed-traits input[type=text]').first();
        await input.fill('Eventos E2E ' + STAMP); await input.press('Enter');
        await page.waitForFunction((t) => document.querySelector('iframe.gjs-frame').contentDocument.querySelector('[data-hz-block=events] h2')?.textContent.includes(t), 'Eventos E2E ' + STAMP, { timeout: 8000 });
        await page.screenshot({ path: `${SHOTS}/page-editor-live.png` });

        // 5. Inspector: selección → pestañas Contenido/Estilo, acciones y nota del bloque.
        await page.evaluate(() => { window.editor.select(window.editor.getWrapper().find('h1,h2')[0]); });
        await page.click('[data-right=styles]');
        assert.ok(await page.locator('#hzed-styles .gjs-sm-sector').count() >= 3);
        assert.match(await page.locator('#hzed-sel-name').innerText(), /\S/);

        // 6. Buscador de bloques.
        const total = await page.locator('#hzed-blocks .gjs-block').count();
        await page.fill('#hzed-block-search', 'pregunt'); await page.waitForTimeout(400);
        const found = await page.locator('#hzed-blocks .gjs-block').count();
        assert.ok(found >= 1 && found < total, `la búsqueda debe filtrar (${found} de ${total})`);
        await page.fill('#hzed-block-search', ''); await page.waitForTimeout(300);

        // 7. Modo vista previa: solo el lienzo, y se sale con el botón.
        await page.click('#hzed-preview');
        assert.equal(await page.locator('.hzed__left').isVisible(), false);
        await page.screenshot({ path: `${SHOTS}/page-editor-preview.png` });
        await page.click('#hzed-exit-preview');
        assert.equal(await page.locator('.hzed__left').isVisible(), true);

        // 8. Guardar: se guarda el marcador, nunca el HTML de la vista previa; la página pública sí dibuja el bloque.
        await page.click('#hzed-settings');
        await page.fill('#hzed-form-settings [name=slug]', 'e2e-editor-' + STAMP);
        await page.selectOption('#hzed-form-settings [name=status]', 'published');
        await page.click('#hzed-form-settings button[type=submit]');
        await page.waitForFunction(() => !document.querySelector('#hzed-dlg-settings').open, null, { timeout: 8000 });
        await page.keyboard.press('Control+s');
        await page.waitForFunction(() => /Guardado/.test(document.querySelector('#hzed-saved').textContent), null, { timeout: 10000 });
        const html = await page.evaluate(() => window.editor.getHtml());
        assert.match(html, /data-hz-block="events"/);
        assert.ok(!/ev-card/.test(html), 'la vista previa no debe guardarse en la página');

        const pub = await s.ctx.newPage();
        await pub.goto(`${BASE}/e2e-editor-${STAMP}`, { waitUntil: 'networkidle' });
        assert.ok(await pub.locator('.ev-card').count() >= 1, 'la página pública muestra los eventos');
        assert.match(await pub.locator('h2', { hasText: 'Eventos E2E' }).first().innerText(), /Eventos E2E/);

        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});
