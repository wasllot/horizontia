// E2E de un curso presencial con varias fechas: Eventos → elegir fecha en la página del curso → comprar con esa fecha.
//   node --test --test-reporter=spec tests/e2e-panel/onsite-course.e2e.mjs
// Requiere los eventos de demostración (php artisan db:seed --class=DemoAcademySeeder).
import test from 'node:test';
import assert from 'node:assert/strict';
import { launch, session, BASE, SHOTS, artisanTinker } from './helpers.mjs';

const browser = await launch();
const SLUG = 'demo-taller-excel-presencial';
const clearCart = () => artisanTinker(`App\\Models\\CartItem::whereHas('user', fn($q) => $q->where('email', 'alumno.pagado@horizontia.test'))->delete(); Illuminate\\Support\\Facades\\Cache::forget('cart_items_' . App\\Models\\User::where('email', 'alumno.pagado@horizontia.test')->value('id'));`);
test.after(async () => { await browser.close(); clearCart(); });

test('presencial: Eventos → elegir fecha → comprar con esa fecha', async () => {
    clearCart();
    const s = await session(browser, 'student');
    try {
        const { page } = s;
        // 1. La sección Eventos lista el curso con varias fechas y enlaza a una fecha concreta.
        await page.goto(BASE + '/eventos', { waitUntil: 'networkidle' });
        const card = page.locator('.ev-card', { hasText: 'Taller presencial de Excel' });
        assert.ok(await card.count() === 1, 'el taller aparece una sola vez en Eventos');
        assert.match(await card.innerText(), /fechas más/);
        await card.locator('.ev-card__link').click();
        await page.waitForURL(/fecha=\d+/);

        // 2. La fecha del enlace llega preseleccionada y se ven las tres fechas con su sede.
        await page.waitForSelector('#dates .ev-pick__item');
        assert.equal(await page.locator('#dates .ev-pick__item').count(), 3);
        assert.equal(await page.locator('.ev-pick__item[aria-checked=true]').count(), 1);
        const first = (await page.getByTestId('chosen-date').innerText()).replace(/\s+/g, ' ');

        // 3. Elegir otra fecha cambia la selección, el resumen del panel de compra y la URL.
        await page.locator('.ev-pick__item').nth(2).click();
        await page.waitForFunction((prev) => document.querySelector('[data-testid=chosen-date]').innerText.replace(/\s+/g, ' ') !== prev, first, { timeout: 8000 });
        assert.match(await page.getByTestId('chosen-date').innerText(), /Guadalajara/);
        assert.match(page.url(), /fecha=\d+/);
        await page.screenshot({ path: `${SHOTS}/onsite-course-page.png` });

        // 4. Comprar ahora lleva al checkout con la fecha elegida guardada en el carrito.
        await page.click('button:has-text("Comprar ahora")');
        await page.waitForURL(/finalizar-compra/, { timeout: 15000 });
        const cart = artisanTinker(`echo App\\Models\\CartItem::whereHas('user', fn($q) => $q->where('email', 'alumno.pagado@horizontia.test'))->get()->map(fn($i) => ($i->options['session_label'] ?? 'SIN FECHA'))->implode('|');`);
        assert.match(cart, /Guadalajara/, 'el carrito recuerda la fecha elegida: ' + cart);
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('presencial: sin fecha elegida no se puede comprar, y la home muestra los próximos eventos', async () => {
    const s = await session(browser, 'student');
    try {
        const { page } = s;
        await page.goto(`${BASE}/curso/${SLUG}`, { waitUntil: 'networkidle' });
        assert.equal(await page.locator('button:has-text("Comprar ahora")').count(), 0, 'sin fecha no hay botón de compra');
        assert.match(await page.locator('.cr-action-buttons').innerText(), /Elegir fecha/);

        await page.goto(BASE + '/', { waitUntil: 'networkidle' });
        assert.ok(await page.locator('.ev-home .ev-card').count() >= 1, 'la portada anuncia eventos');
        assert.equal(await page.locator('nav a:has-text("Eventos")').first().isVisible(), true, 'Eventos está en el menú');
    } finally { await s.ctx.close(); }
});
