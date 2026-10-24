// Reembolsos: el alumno pide la devolución desde «Mis facturas», el admin la aprueba y el acceso se retira.
// Requiere los cursos de la demo y el sitio en E2E_BASE_URL. Crea (y borra) un pedido temporal con cortesía de pago manual.
import test from 'node:test';
import assert from 'node:assert/strict';
import { launch, session, BASE, SHOTS, artisanTinker } from './helpers.mjs';

const php = (code) => artisanTinker(code).trim().split('\n').pop().trim();
const PAID = 'alumno.pagado@horizontia.test';
let browser; let order;

test.before(async () => {
    browser = await launch();
    order = JSON.parse(php(`$u = App\\Models\\User::where('email', '${PAID}')->first();
        $owned = Modules\\Courses\\Models\\Enrollment::where('student_id', $u->id)->pluck('course_id');
        $c = Modules\\Courses\\Models\\Course::where('status', Modules\\Courses\\Models\\Course::STATUSES['active'])->where('delivery', 'online')->whereNotIn('id', $owned)->first();
        $o = App\\Models\\Order::create(['user_id' => $u->id, 'amount' => 499, 'currency' => 'MXN', 'first_name' => 'E2E', 'last_name' => 'Reembolso', 'email' => $u->email, 'company' => '', 'country' => '', 'state' => '', 'postal_code' => '', 'city' => '', 'phone' => '', 'description' => '', 'payment_method' => 'manual', 'status' => 'complete', 'unique_payment_id' => (string) Illuminate\\Support\\Str::uuid()]);
        DB::table('order_items')->insert(['order_id' => $o->id, 'title' => 'E2E Reembolso ' . $c->id, 'quantity' => 1, 'price' => 499, 'total' => 499, 'platform_fee' => 0, 'orderable_type' => Modules\\Courses\\Models\\Course::class, 'orderable_id' => $c->id, 'created_at' => now(), 'updated_at' => now()]);
        Modules\\Courses\\Models\\Enrollment::create(['student_id' => $u->id, 'course_id' => $c->id, 'tutor_id' => $c->instructor_id, 'status' => 'active']);
        echo json_encode(['id' => $o->id, 'course' => $c->id]);`));
});

test.after(async () => {
    php(`App\\Models\\RefundRequest::where('order_id', ${order.id})->delete(); DB::table('order_items')->where('order_id', ${order.id})->delete(); App\\Models\\Order::where('id', ${order.id})->delete(); Modules\\Courses\\Models\\Enrollment::where('course_id', ${order.course})->where('student_id', App\\Models\\User::where('email', '${PAID}')->value('id'))->delete(); echo 'ok';`);
    await browser?.close();
});

test('reembolsos: el alumno lo pide, el admin lo aprueba y se retira el acceso', async () => {
    const st = await session(browser, 'student');
    try {
        await st.page.goto(`${BASE}/estudiante/facturas`, { waitUntil: 'networkidle' });
        const box = st.page.getByTestId('refund-box');
        await box.waitFor();
        await st.page.screenshot({ path: `${SHOTS}/feat-refund-student.png` });
        await st.page.getByTestId(`refund-open-${order.id}`).click();
        await st.page.getByTestId('refund-form').locator('textarea').fill('El temario no cubre lo que necesito para mi trabajo.');
        await st.page.getByTestId('refund-send').click();
        await st.page.getByTestId(`refund-row-${order.id}`).getByText('En revisión').waitFor();
        assert.deepEqual(st.errors, []);
    } finally { await st.ctx.close(); }

    const ad = await session(browser, 'admin');
    try {
        ad.page.on('dialog', (d) => d.accept());
        await ad.page.goto(`${BASE}/admin/reembolsos`, { waitUntil: 'networkidle' });
        const row = ad.page.locator(`[data-testid^="refund-"]`, { hasText: `#${order.id}` }).first();
        await row.waitFor();
        await ad.page.screenshot({ path: `${SHOTS}/feat-refund-admin.png`, fullPage: true });
        await row.getByRole('button', { name: 'Aprobar' }).click();
        await ad.page.getByTestId('refund-tab-resolved').click();
        await ad.page.locator('[data-testid^="refund-"]', { hasText: `#${order.id}` }).first().getByText('Aprobada').waitFor();
        assert.deepEqual(ad.errors, []);
    } finally { await ad.ctx.close(); }

    const state = JSON.parse(php(`$o = App\\Models\\Order::find(${order.id}); $u = App\\Models\\User::where('email', '${PAID}')->first(); echo json_encode(['status' => $o->status, 'enrolled' => Modules\\Courses\\Models\\Enrollment::where('student_id', $u->id)->where('course_id', ${order.course})->exists(), 'req' => App\\Models\\RefundRequest::where('order_id', $o->id)->value('status')]);`));
    assert.equal(state.status, 'refunded');
    assert.equal(state.enrolled, false, 'el acceso se retiró');
    assert.equal(state.req, 'approved');
});
