// E2E de paquetes: el administrador crea uno, el alumno lo ve en /paquetes, lo compra y el checkout lo muestra.
//   node --test --test-reporter=spec tests/e2e-panel/bundles.e2e.mjs
// Requiere los cursos de prueba (php artisan db:seed --class=DemoLiveAndRegularCoursesSeeder).
import test from 'node:test';
import assert from 'node:assert/strict';
import { launch, session, BASE, SHOTS, artisanTinker } from './helpers.mjs';

const browser = await launch();
const STUDENT = 'alumno.gratis@horizontia.test';   // no tiene ninguno de los cursos del paquete de la demo
const php = (code) => artisanTinker(code).trim().split('\n').pop().trim();
const clearCart = () => php(`$u = App\\Models\\User::where('email', '${STUDENT}')->value('id'); App\\Models\\CartItem::where('user_id', $u)->delete(); Illuminate\\Support\\Facades\\Cache::forget('cart_items_' . $u);`);
const TITLE = 'E2E Paquete ' + Date.now().toString(36);

test.after(async () => {
    await browser.close();
    clearCart();
    php(`$b = Modules\\Courses\\Models\\Bundle::where('title', '${TITLE}')->first(); if ($b) { $b->courses()->detach(); $b->delete(); }`);
});

test('administrador: crea un paquete validando que ahorre, y lo publica', async () => {
    const s = await session(browser, 'admin');
    try {
        const { page } = s;
        await page.goto(`${BASE}/admin/paquetes-de-cursos`, { waitUntil: 'networkidle' });
        await page.getByTestId('bundle-new').click();
        const form = page.getByTestId('bundle-form');
        await form.waitFor();
        await form.locator('#bn-title').fill(TITLE);
        await form.locator('#bn-price').fill('100');
        await form.getByRole('button', { name: 'Guardar paquete' }).click();
        await page.waitForFunction(() => /al menos 2 cursos/.test(document.querySelector('[data-testid=bundle-form]')?.innerText || ''), null, { timeout: 8000 });

        await form.locator('.hz-bn__opt', { hasText: 'Marketing Digital desde Cero' }).click();
        await form.locator('.hz-bn__opt', { hasText: 'Fotografía con tu Celular' }).click();
        await form.locator('#bn-price').fill('99999');
        await page.waitForTimeout(700);   // el precio se envía con retardo (debounce)
        await form.getByRole('button', { name: 'Guardar paquete' }).click();
        await page.waitForFunction(() => /menos que los cursos por separado/.test(document.querySelector('[data-testid=bundle-form]')?.innerText || ''), null, { timeout: 8000 });

        await form.locator('#bn-price').fill('1600');
        await page.waitForTimeout(700);
        await form.locator('#bn-status').selectOption('active');
        await form.getByRole('button', { name: 'Guardar paquete' }).click();
        await page.waitForFunction(() => !document.querySelector('[data-testid=bundle-form]'), null, { timeout: 8000 });
        assert.match(await page.getByTestId('bundles-table').innerText(), new RegExp(TITLE));
        await page.screenshot({ path: `${SHOTS}/bundles-admin.png` });
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('alumno: ve el paquete, lo compra y el checkout cobra el precio del paquete', async () => {
    clearCart();
    const s = await session(browser, 'student');
    try {
        const { page } = s;
        // El helper entra con alumno.pagado; para este flujo se usa alumno.gratis.
        await page.context().clearCookies();
        await page.goto(`${BASE}/iniciar-sesion`);
        await page.fill('#username', STUDENT);
        await page.fill('#password', 'Password123!');
        await page.click('form.am-login-form button');
        await page.waitForURL((u) => !u.pathname.startsWith('/iniciar-sesion'));

        await page.goto(BASE + '/paquetes', { waitUntil: 'networkidle' });
        const card = page.locator('[data-testid=bundle-card]', { hasText: TITLE });
        assert.equal(await card.count(), 1, 'el paquete aparece en el listado');
        await card.click();
        await page.waitForURL(/\/paquete\//);
        assert.equal(await page.getByTestId('bundle-course').count(), 2);
        assert.match(await page.locator('.bn-save').innerText(), /Ahorras \d+%/);
        await page.screenshot({ path: `${SHOTS}/bundle-page.png` });

        await page.getByTestId('bundle-buy').click();
        await page.waitForURL(/finalizar-compra/, { timeout: 15000 });
        assert.equal(await page.getByTestId('co-bundle').count(), 2, 'dos cursos con la etiqueta del paquete');
        const sum = php(`echo round(App\\Models\\CartItem::where('user_id', App\\Models\\User::where('email', '${STUDENT}')->value('id'))->sum('price'), 2);`);
        assert.equal(sum, '1600', 'el carrito suma el precio del paquete');

        // Quitar un curso quita el paquete completo.
        await page.locator('.ht-co__items .ht-link', { hasText: 'Quitar' }).first().click();
        await page.waitForTimeout(1500);
        const left = php(`echo App\\Models\\CartItem::where('user_id', App\\Models\\User::where('email', '${STUDENT}')->value('id'))->count();`);
        assert.equal(left, '0', 'el paquete se quitó completo');
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});
