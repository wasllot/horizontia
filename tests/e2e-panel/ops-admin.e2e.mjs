// E2E de las pantallas de operación del administrador: Estado del sistema e Inscripciones (inscribir a mano, reembolsar).
//   node --test --test-reporter=spec tests/e2e-panel/ops-admin.e2e.mjs
import test from 'node:test';
import assert from 'node:assert/strict';
import { launch, session, BASE, SHOTS, artisanTinker } from './helpers.mjs';

const browser = await launch();
test.after(async () => { await browser.close(); });

test('Estado del sistema: lista las revisiones y se puede respaldar desde la pantalla', async () => {
    const s = await session(browser, 'admin');
    try {
        const { page } = s;
        await page.goto(`${BASE}/admin/estado-del-sistema`, { waitUntil: 'networkidle' });
        assert.ok(await page.getByTestId('check-debug').count() === 1, 'muestra la revisión de depuración');
        assert.match(await page.getByTestId('ss-summary').innerText(), /bien[\s\S]*avisos[\s\S]*errores/);
        assert.ok(await page.locator('a[href$="/admin/estado-del-sistema"]').first().count() >= 1, 'está en el menú lateral');
        await page.screenshot({ path: `${SHOTS}/system-status.png`, fullPage: true });

        await page.getByTestId('backup-now').click();
        await page.waitForSelector('.hz-ss__msg', { timeout: 60000 });
        assert.match(await page.locator('.hz-ss__msg').innerText(), /Respaldo creado/);
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('Inscripciones: el administrador inscribe a mano a un alumno', async () => {
    const email = 'alumno.nuevo@horizontia.test';
    const cleanup = () => artisanTinker(`$u = App\\Models\\User::where('email', '${email}')->value('id'); $c = Modules\\Courses\\Models\\Course::where('slug', 'prueba-fotografia-celular')->value('id'); if ($u && $c) Modules\\Courses\\Models\\Enrollment::where(['student_id' => $u, 'course_id' => $c])->delete();`);
    cleanup();
    const s = await session(browser, 'admin');
    try {
        const { page } = s;
        await page.goto(`${BASE}/admin/inscripciones`, { waitUntil: 'networkidle' });
        await page.getByTestId('manual-enroll-toggle').click();
        const form = page.getByTestId('manual-enroll-form');
        await form.waitFor();
        await form.locator('#me-email').fill('no-existe@correo.test');
        await form.locator('#me-course').selectOption({ label: 'Fotografía con tu Celular' });
        await form.getByRole('button', { name: 'Inscribir' }).click();
        await page.waitForSelector('.hz-en__err', { timeout: 8000 });
        assert.match(await form.innerText(), /No hay ninguna cuenta/);

        await form.locator('#me-email').fill(email);
        await form.getByRole('button', { name: 'Inscribir' }).click();
        await page.waitForFunction(() => !document.querySelector('[data-testid=manual-enroll-form]'), null, { timeout: 8000 });
        const n = artisanTinker(`echo Modules\\Courses\\Models\\Enrollment::where('student_id', App\\Models\\User::where('email', '${email}')->value('id'))->where('course_id', Modules\\Courses\\Models\\Course::where('slug', 'prueba-fotografia-celular')->value('id'))->count();`).trim().split('\n').pop();
        assert.equal(n, '1', 'quedó inscrito');
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); cleanup(); }
});
