// E2E de la compra de un curso presencial y la gestión de inscritos:
// alumno elige fecha → compra (curso gratis, sin pasarela) → gracias con fecha/.ics → Mis cursos → administrador mueve al inscrito.
//   node --test --test-reporter=spec tests/e2e-panel/onsite-purchase.e2e.mjs
// Requiere los datos de demostración (DemoAcademySeeder) y un método de pago activo (el botón de pagar se bloquea sin ninguno).
import test from 'node:test';
import assert from 'node:assert/strict';
import { launch, session, BASE, SHOTS, artisanTinker } from './helpers.mjs';

const browser = await launch();
let SLUG;   // el sistema regenera el slug a partir del título: se lee el real tras crear el curso
const STUDENT = 'alumno.pagado@horizontia.test';
// tinker puede imprimir avisos de PHP antes del resultado: se usa la última línea.
const php = (code) => artisanTinker(code).trim().split('\n').pop().trim();

const clearCart = () => php(`$u = App\\Models\\User::where('email', '${STUDENT}')->value('id'); App\\Models\\CartItem::where('user_id', $u)->delete(); Illuminate\\Support\\Facades\\Cache::forget('cart_items_' . $u);`);

function setup() {
    return php(`
        $base = Modules\\Courses\\Models\\Course::where('slug', 'demo-taller-excel-presencial')->firstOrFail();
        $c = $base->replicate(); $c->title = 'E2E Taller presencial ' . time(); $c->slug = 'e2e-' . uniqid(); $c->delivery = 'in_person'; $c->status = 'active'; $c->save();
        $c->pricing()->create(['price' => 0, 'discount' => 0, 'final_price' => 0]);
        foreach ([[10, 'Guadalajara', 5], [20, 'Monterrey', 5]] as $i => [$d, $city, $cap]) {
            $c->sessions()->create(['starts_at' => now()->addDays($d), 'ends_at' => now()->addDays($d)->addHours(4), 'timezone' => 'America/Mexico_City',
                'venue_name' => 'Sede ' . $city, 'address' => 'Av. Reforma 100', 'city' => $city, 'capacity' => $cap, 'status' => 'scheduled', 'sort' => $i + 1]);
        }
        echo $c->id . '|' . $c->slug . '|' . $c->sessions()->orderBy('starts_at')->pluck('id')->implode(',');
    `);
}

let courseId, dateA, dateB;
test.before(() => {
    clearCart();
    const [id, slug, ids] = setup().split('|');
    courseId = id; SLUG = slug;
    [dateA, dateB] = ids.split(',');
});
test.after(async () => {
    await browser.close();
    if (courseId) php(`$c = Modules\\Courses\\Models\\Course::find(${courseId}); if ($c) { Modules\\Courses\\Models\\Enrollment::where('course_id', $c->id)->delete(); $c->sessions()->delete(); $c->pricing()->delete(); $c->forceDelete(); }`);
    clearCart();
});

test('alumno: elige fecha, compra y ve su fecha en gracias y Mis cursos', async () => {
    const s = await session(browser, 'student');
    try {
        const { page } = s;
        await page.goto(`${BASE}/curso/${SLUG}?fecha=${dateA}`, { waitUntil: 'networkidle' });
        await page.waitForSelector('#dates .ev-pick__item');
        assert.equal(await page.locator('.ev-pick__item[aria-checked=true]').count(), 1);

        await page.getByRole('button', { name: /Comprar ahora|Inscribirme gratis/ }).first().click();
        await page.waitForURL(/finalizar-compra/, { timeout: 15000 });
        assert.match(await page.getByTestId('co-date').innerText(), /Guadalajara/, 'el resumen del pedido muestra la fecha');
        assert.equal(await page.getByTestId('date-problem').count(), 0);

        await page.locator('button.ht-co__pay').first().click();
        await page.waitForURL(/gracias|thank-you/, { timeout: 20000 });
        const thanks = page.getByTestId('thanks-date');
        await thanks.waitFor();
        assert.match(await thanks.innerText(), /Av\. Reforma 100/);
        const ics = await thanks.locator('a:has-text("Añadir al calendario")').getAttribute('href');
        const res = await page.request.get(ics.startsWith('http') ? ics : BASE + ics);
        assert.equal(res.status(), 200);
        assert.match(await res.text(), /BEGIN:VEVENT[\s\S]*LOCATION:Sede Guadalajara/);
        await page.screenshot({ path: `${SHOTS}/onsite-thanks.png` });

        await page.goto(BASE + '/mis-cursos', { waitUntil: 'networkidle' });
        const mine = page.locator('.cr-card', { hasText: 'E2E Taller presencial' });
        assert.match(await mine.getByTestId('my-date').innerText(), /Presencial[\s\S]*Sede Guadalajara/);
        await page.screenshot({ path: `${SHOTS}/onsite-my-courses.png` });

        await page.goto(`${BASE}/aula/${SLUG}`, { waitUntil: 'networkidle' });
        assert.match(await page.getByTestId('aula-date').innerText(), /Tu fecha presencial/);
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('administrador: ve los inscritos por fecha y mueve a la persona a otra fecha', async () => {
    const s = await session(browser, 'admin');
    try {
        const { page } = s;
        await page.goto(`${BASE}/admin/curso/${courseId}/inscritos`, { waitUntil: 'networkidle' });
        assert.equal(await page.getByTestId('date-card').count(), 2);
        assert.ok((await page.getByTestId('enrollees-table').innerText()).includes(STUDENT), 'la compra aparece en Inscritos');

        // El CSV se descarga con la persona dentro.
        const csv = await page.request.get(await page.getByTestId('enrollees-csv').getAttribute('href'));
        assert.equal(csv.status(), 200);
        assert.ok((await csv.text()).includes(STUDENT));

        // Filtrar por la otra fecha deja la tabla vacía.
        await page.getByTestId('date-card').nth(1).locator('.hz-en__pick').click();
        await page.waitForTimeout(1200);
        await page.screenshot({ path: `${SHOTS}/onsite-enrollees-filter.png` });
        await page.waitForSelector('.hz-empty');

        // Mover a la persona a la segunda fecha.
        await page.getByTestId('date-card').nth(0).locator('.hz-en__pick').click();
        await page.waitForSelector('[data-testid=enrollees-table]');
        await page.locator('.hz-en__move').first().selectOption({ label: await page.locator('.hz-en__move option').nth(1).innerText() });
        await page.waitForFunction(() => !document.querySelector('[data-testid=enrollees-table]'), null, { timeout: 8000 });
        await page.screenshot({ path: `${SHOTS}/onsite-enrollees.png` });
        const moved = php(`echo Modules\\Courses\\Models\\Enrollment::where('course_id', ${courseId})->value('session_id');`);
        assert.equal(moved, dateB, 'el inscrito quedó en la segunda fecha');

        // Abrir el formulario de aviso.
        await page.getByTestId('date-card').nth(1).getByRole('button', { name: 'Avisar cambio' }).click();
        await page.getByTestId('notice-form').waitFor();
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});
