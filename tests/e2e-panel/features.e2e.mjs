// E2E de las funciones nuevas: cupones, certificados, rutas, notas, comunidad, reportes, estudio de contenido, búsqueda y PWA.
//   node --test --test-reporter=spec tests/e2e-panel/features.e2e.mjs
// Requiere los cursos de la demo (php artisan db:seed --class=DemoAcademySeeder) y el sitio en E2E_BASE_URL.
import test from 'node:test';
import assert from 'node:assert/strict';
import { launch, session, BASE, SHOTS, ROLES, artisanTinker } from './helpers.mjs';

const browser = await launch();
const php = (code) => artisanTinker(code).trim().split('\n').pop().trim();
const TAG = Date.now().toString(36).toUpperCase();
const PAID = ROLES.student.email;                 // alumno con cursos de la demo
const FREE = 'alumno.gratis@horizontia.test';     // sin cursos

test.after(async () => {
    await browser.close();
    php(`App\\Models\\Coupon::where('code', 'like', 'E2E${TAG}%')->delete(); App\\Models\\LearningPath::where('title', 'like', 'E2E ${TAG}%')->get()->each(function ($p) { $p->courses()->detach(); $p->delete(); }); App\\Models\\CommunityPost::where('title', 'like', 'E2E ${TAG}%')->delete(); App\\Models\\CourseCertificate::where('student_name', 'E2E ${TAG}')->delete(); echo 'ok';`);
});

const ownedCourse = () => JSON.parse(php(`$u = App\\Models\\User::where('email', '${PAID}')->first(); $e = Modules\\Courses\\Models\\Enrollment::where('student_id', $u->id)->whereHas('course', fn($q) => $q->where('delivery', 'online'))->first(); $c = $e->course; echo json_encode(['id' => $c->id, 'slug' => $c->slug, 'title' => html_entity_decode($c->title)]);`));

test('cupones: el admin crea uno y el alumno lo aplica en el pago', async () => {
    const admin = await session(browser, 'admin');
    try {
        const { page } = admin;
        await page.goto(`${BASE}/admin/cupones`, { waitUntil: 'networkidle' });
        await page.getByTestId('coupon-new').click();
        const form = page.getByTestId('coupon-form');
        await form.locator('#cp-code').fill(`E2E${TAG}`);
        await form.locator('#cp-value').fill('25');
        await form.getByRole('button', { name: 'Guardar cupón' }).click();
        await page.waitForFunction(() => !document.querySelector('[data-testid=coupon-form]'), null, { timeout: 8000 });
        assert.match(await page.getByTestId('coupons-table').innerText(), new RegExp(`E2E${TAG}`));
        await page.screenshot({ path: `${SHOTS}/feat-coupons-admin.png` });
        assert.deepEqual(admin.errors, []);
    } finally { await admin.ctx.close(); }

    // El alumno sin cursos pone uno en el carrito y aplica el código.
    php(`$u = App\\Models\\User::where('email', '${FREE}')->first(); App\\Models\\CartItem::where('user_id', $u->id)->delete(); Illuminate\\Support\\Facades\\Cache::forget('cart_items_'.$u->id); $c = Modules\\Courses\\Models\\Course::where('status', Modules\\Courses\\Models\\Course::STATUSES['active'])->where('delivery','online')->whereHas('pricing', fn($q) => $q->where('final_price', '>', 0))->whereDoesntHave('enrollments', fn($q) => $q->where('student_id', $u->id))->first(); App\\Models\\CartItem::create(['user_id' => $u->id, 'cartable_id' => $c->id, 'cartable_type' => Modules\\Courses\\Models\\Course::class, 'name' => $c->title, 'qty' => 1, 'price' => $c->pricing->final_price, 'options' => ['price' => $c->pricing->final_price]]); echo 'ok';`);
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    await ctx.addCookies([{ name: 'cookie_consent', value: '1', url: BASE }]);
    const page = await ctx.newPage();
    try {
        await page.goto(`${BASE}/iniciar-sesion`);
        await page.fill('#username', FREE); await page.fill('#password', 'Password123!');
        await page.click('form.am-login-form button');
        await page.waitForURL((u) => !u.pathname.startsWith('/iniciar-sesion'));
        await page.goto(`${BASE}/finalizar-compra`, { waitUntil: 'networkidle' });
        const before = await page.locator('.ht-co__totals .is-total dd').innerText();
        await page.getByTestId('co-coupon-form').locator('input').fill(`e2e${TAG}`);
        await page.getByTestId('co-coupon-form').getByRole('button', { name: 'Aplicar' }).click();
        await page.getByTestId('co-coupon-applied').waitFor({ timeout: 8000 });
        const after = await page.locator('.ht-co__totals .is-total dd').innerText();
        assert.notEqual(before, after, 'el total baja con el cupón');
        assert.match(await page.locator('.ht-co__totals').innerText(), /Descuento/);
        await page.screenshot({ path: `${SHOTS}/feat-coupon-checkout.png`, fullPage: true });
    } finally {
        await ctx.close();
        php(`$u = App\\Models\\User::where('email', '${FREE}')->first(); App\\Models\\CartItem::where('user_id', $u->id)->delete(); Illuminate\\Support\\Facades\\Cache::forget('cart_items_'.$u->id); echo 'ok';`);
    }
});

test('certificados: página pública verificable y verificador por código', async () => {
    const c = ownedCourse();
    const code = php(`$u = App\\Models\\User::where('email', '${PAID}')->first(); $c = Modules\\Courses\\Models\\Course::find(${c.id}); $cert = App\\Models\\CourseCertificate::firstOrCreate(['user_id' => $u->id, 'course_id' => $c->id], ['code' => app(App\\Services\\CourseCertificateService::class)->newCode(), 'student_name' => 'E2E ${TAG}', 'course_title' => $c->title, 'content_seconds' => 3600, 'issued_at' => now()]); $cert->update(['student_name' => 'E2E ${TAG}']); echo $cert->code;`);
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    try {
        await page.goto(`${BASE}/verificar-certificado`, { waitUntil: 'networkidle' });
        await page.fill('#codigo', code.toLowerCase());
        await page.click('button[type=submit]');
        await page.getByTestId('certificate').waitFor();
        assert.match(await page.getByTestId('cert-status').innerText(), /auténtico/);
        assert.match(await page.getByTestId('certificate').innerText(), new RegExp(`E2E ${TAG}`));
        await page.waitForTimeout(1200);   // el QR se dibuja con una librería externa
        await page.screenshot({ path: `${SHOTS}/feat-certificate.png` });
        const notFound = await ctx.request.get(`${BASE}/certificado/HZ-AAAA-BBBB-CCCC`);
        assert.equal(notFound.status(), 404);
    } finally { await ctx.close(); }
});

test('ruta de aprendizaje: lista, detalle con avance y siguiente paso', async () => {
    const title = `E2E ${TAG} ruta`;
    const slug = php(`$ids = Modules\\Courses\\Models\\Course::where('status', Modules\\Courses\\Models\\Course::STATUSES['active'])->where('delivery','online')->limit(3)->pluck('id'); $p = App\\Models\\LearningPath::create(['title' => '${title}', 'slug' => app(App\\Services\\LearningPathService::class)->uniqueSlug('${title}'), 'subtitle' => 'Ruta de prueba', 'status' => 'active']); $p->courses()->sync($ids->mapWithKeys(fn($id, $i) => [$id => ['sort' => $i + 1]])->all()); echo $p->slug;`);
    const s = await session(browser, 'student');
    try {
        const { page } = s;
        await page.goto(`${BASE}/rutas`, { waitUntil: 'networkidle' });
        assert.ok(await page.getByTestId('path-card').filter({ hasText: title }).count() >= 1);
        await page.goto(`${BASE}/ruta/${slug}`, { waitUntil: 'networkidle' });
        assert.ok((await page.getByTestId('path-step').count()) >= 2);
        assert.match(await page.getByTestId('path-progress').innerText(), /cursos terminados/);
        await page.screenshot({ path: `${SHOTS}/feat-path.png`, fullPage: true });
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('aula: notas y comunidad dentro de la lección', async () => {
    const c = ownedCourse();
    const s = await session(browser, 'student');
    try {
        const { page } = s;
        await page.goto(`${BASE}/aula/${c.slug}`, { waitUntil: 'networkidle' });

        // Mis notas
        await page.getByTestId('notes-tab').click();
        await page.locator('#cr-notes textarea').fill(`E2E ${TAG} mi nota`);
        await page.getByTestId('note-save').click();
        await page.locator('#cr-notes [data-testid=notes-list]').getByText(`E2E ${TAG} mi nota`).waitFor({ timeout: 8000 });
        await page.screenshot({ path: `${SHOTS}/feat-notes.png` });

        // Comunidad
        await page.getByTestId('community-tab').click();
        await page.getByTestId('community-ask').click();
        await page.locator('#cm-title').fill(`E2E ${TAG} pregunta`);
        await page.locator('#cm-body').fill('Esta es una pregunta de prueba del E2E con texto suficiente.');
        await page.getByTestId('community-publish').click();
        await page.getByTestId('community-thread').waitFor({ timeout: 8000 });
        await page.locator('#cm-reply').fill('Primera respuesta de prueba');
        await page.getByTestId('community-send').click();
        await page.getByTestId('community-reply').first().waitFor({ timeout: 8000 });
        await page.screenshot({ path: `${SHOTS}/feat-community.png` });
        assert.deepEqual(s.errors, []);
    } finally {
        await s.ctx.close();
        php(`App\\Models\\LessonNote::where('body', 'like', 'E2E ${TAG}%')->delete(); echo 'ok';`);
    }
});

test('aula: el video guarda dónde te quedaste y retoma ahí', async () => {
    const lesson = JSON.parse(php(`$u = App\\Models\\User::where('email', '${PAID}')->first(); $l = Modules\\Courses\\Models\\Curriculum::where('type', 'video')->whereHas('section.course.enrollments', fn($q) => $q->where('student_id', $u->id))->first(); App\\Models\\LessonPosition::where('user_id', $u->id)->where('curriculum_id', $l->id)->delete(); echo json_encode(['id' => $l->id, 'slug' => $l->section->course->slug, 'title' => html_entity_decode($l->title), 'uid' => $u->id]);`));
    const s = await session(browser, 'student');
    try {
        const { page } = s;
        const saves = [];
        page.on('response', (r) => { if (r.url().endsWith('/aula-posicion')) saves.push(r.status()); });
        await page.goto(`${BASE}/aula/${lesson.slug}`, { waitUntil: 'networkidle' });
        await page.getByText(lesson.title).first().click();
        await page.waitForFunction(() => window.crLessonVideo && window.crLessonVideo.player(), null, { timeout: 15000 });
        await page.evaluate(() => window.crLessonVideo.player().trigger('pause'));
        await page.waitForTimeout(1500);
        assert.deepEqual(saves, [200], 'al pausar se guarda la posición');

        // El video de la demo dura 6 s: se comprueba con un reproductor simulado que, con una posición guardada, salta ahí al cargar.
        const jumped = await page.evaluate(() => {
            const handlers = {}; let set = null;
            const fake = { one: (e, f) => { handlers[e] = f; }, on: () => {}, currentTime: (v) => { if (v !== undefined) set = v; return set || 0; } };
            window.crLessonVideo.track(fake, 1, '/aula-posicion', 'x', 245);
            handlers.loadedmetadata();
            return set;
        });
        assert.equal(jumped, 245);
        assert.deepEqual(s.errors.filter((e) => !/Failed to load|net::|MEDIA_ERR|NotSupported|play\(\)/i.test(e)), []);
    } finally {
        await s.ctx.close();
        php(`App\\Models\\LessonPosition::where('curriculum_id', ${lesson.id})->delete(); echo 'ok';`);
    }
});

test('admin: reportes, estudio de contenido y comunidad cargan sin errores y exportan CSV', async () => {
    const s = await session(browser, 'admin');
    try {
        const { page } = s;
        await page.goto(`${BASE}/admin/reportes`, { waitUntil: 'networkidle' });
        await page.getByTestId('kpi-gross').waitFor();
        for (const tab of ['aprendizaje', 'cupones', 'busquedas', 'whatsapp', 'ventas']) {
            await page.getByTestId(`tab-${tab}`).click();
            await page.waitForTimeout(600);
        }
        await page.screenshot({ path: `${SHOTS}/feat-reports.png`, fullPage: true });
        const csv = await page.evaluate(async (u) => { const r = await fetch(u); return { status: r.status, type: r.headers.get('content-type'), text: (await r.text()).slice(0, 40) }; }, `${BASE}/admin/reportes/exportar/ventas`);
        assert.equal(csv.status, 200);
        assert.match(csv.type, /text\/csv/);

        await page.goto(`${BASE}/admin/estudio-de-contenido`, { waitUntil: 'networkidle' });
        assert.ok(await page.getByTestId('studio-form').isVisible());
        await page.screenshot({ path: `${SHOTS}/feat-studio.png`, fullPage: true });

        await page.goto(`${BASE}/admin/comunidad`, { waitUntil: 'networkidle' });
        await page.goto(`${BASE}/admin/certificados`, { waitUntil: 'networkidle' });
        await page.goto(`${BASE}/admin/rutas-de-aprendizaje`, { waitUntil: 'networkidle' });
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('catálogo: la búsqueda tolera errores de tecleo y sugiere cómo se escribe', async () => {
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    await ctx.addCookies([{ name: 'cookie_consent', value: '1', url: BASE }]);
    const page = await ctx.newPage();
    try {
        await page.goto(`${BASE}/cursos`, { waitUntil: 'networkidle' });
        const search = page.locator('.ht-cat__search input');
        await search.fill('marketng');   // falta una letra
        await page.waitForTimeout(1200);
        assert.ok(await page.locator('.ht-ccard').count() >= 1, 'encuentra «Marketing» aunque falte una letra');
        await search.fill('zzzqqqx');
        await page.waitForTimeout(1200);
        assert.match(await page.locator('.ht-empty').innerText(), /No encontramos/);
        await page.screenshot({ path: `${SHOTS}/feat-search.png` });
    } finally { await ctx.close(); }
});

test('PWA: manifiesto válido, service worker activo y página sin conexión', async () => {
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    try {
        await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
        const manifest = await page.evaluate(async () => { const l = document.querySelector('link[rel=manifest]'); const r = await fetch(l.href); return r.json(); });
        assert.equal(manifest.display, 'standalone');
        await page.waitForFunction(async () => (await navigator.serviceWorker.getRegistrations()).length > 0, null, { timeout: 10000 });
        const scope = await page.evaluate(async () => (await navigator.serviceWorker.getRegistrations())[0].scope);
        assert.equal(scope, `${BASE}/`);
        const offline = await ctx.request.get(`${BASE}/sin-conexion`);
        assert.equal(offline.status(), 200);
    } finally { await ctx.close(); }
});

test('banners de páginas: el admin cambia título, subtítulo e imagen y se ve en la página pública', async () => {
    const s = await session(browser, 'admin');
    const key = php(`echo 'page:' . App\\Models\\SitePage::where('slug', 'nosotros')->value('id');`);
    try {
        const { page } = s;
        await page.goto(`${BASE}/admin/banners-de-paginas`, { waitUntil: 'networkidle' });
        await page.locator(`[wire\\:key="pb-${key}"] [title="Editar banner"], [wire\\:key="pb-${key}"] button`).first().click();
        const form = page.getByTestId('banner-form');
        await form.waitFor();
        await form.locator('#pb-title').fill('Conoce nuestra academia');
        await form.locator('#pb-sub').fill('Instructores que acompañan cada paso de tu aprendizaje.');
        await form.getByTestId('banner-upload').setInputFiles('public/images/nuevo/Slider-horizontia.jpg');
        await page.waitForSelector('.hz-pb__preview .hz-banner--image', { timeout: 15000 });
        await page.screenshot({ path: `${SHOTS}/feat-banner-admin.png`, fullPage: true });
        await form.getByTestId('banner-save').click();
        await page.waitForFunction(() => !document.querySelector('[data-testid=banner-form]'), null, { timeout: 15000 });

        const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
        await ctx.addCookies([{ name: 'cookie_consent', value: '1', url: BASE }]);
        const pub = await ctx.newPage();
        await pub.goto(`${BASE}/nosotros`, { waitUntil: 'networkidle' });
        assert.match(await pub.getByTestId('page-banner').innerText(), /Conoce nuestra academia/);
        assert.equal(await pub.getByTestId('page-banner').locator('h1').count(), 1);
        assert.equal(await pub.locator('h1').count(), 1, 'una sola portada: la original se reemplaza');
        await pub.screenshot({ path: `${SHOTS}/feat-banner-public.png` });
        await ctx.close();
        assert.deepEqual(s.errors, []);
    } finally {
        await s.ctx.close();
        php(`$b = App\\Models\\PageBanner::where('page_key', '${key}')->first(); if ($b) { if ($b->image) { Storage::disk(getStorageDisk())->delete($b->image); } $b->delete(); } App\\Services\\PageBannerService::forget('${key}'); echo 'ok';`);
    }
});

test('solicitar acceso: la persona pide acceso, el admin aprueba y puede crear su contraseña', async () => {
    const email = `e2e.${TAG.toLowerCase()}@ejemplo.mx`;
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    await ctx.addCookies([{ name: 'cookie_consent', value: '1', url: BASE }]);
    const page = await ctx.newPage();
    try {
        await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
        await page.locator('.am-steps-section').screenshot({ path: `${SHOTS}/feat-home-step.png` });
        await page.getByRole('link', { name: 'SOLICITAR', exact: true }).click();
        await page.waitForURL(/solicitar-acceso/);
        await page.getByTestId('type-tutor').click();
        await page.locator('#ra-name').fill(`E2E ${TAG} Instructor`);
        await page.locator('#ra-email').fill(email);
        await page.locator('#ra-msg').fill('Soy consultor con diez años de experiencia y quiero enseñar ventas B2B.');
        await page.locator('#solicitar input[type=checkbox]').check();
        await page.screenshot({ path: `${SHOTS}/feat-access-form.png`, fullPage: true });
        await page.getByTestId('access-submit').click();
        await page.getByTestId('access-sent').waitFor({ timeout: 10000 });
        await page.screenshot({ path: `${SHOTS}/feat-access-sent.png` });
    } finally { await ctx.close(); }

    const admin = await session(browser, 'admin');
    try {
        const { page: ap } = admin;
        await ap.goto(`${BASE}/admin/solicitudes-de-acceso`, { waitUntil: 'networkidle' });
        const row = ap.locator('tr', { hasText: email });
        await row.waitFor();
        await ap.screenshot({ path: `${SHOTS}/feat-access-admin.png` });
        ap.on('dialog', (d) => d.accept());
        await row.getByRole('button', { name: 'Aprobar y crear la cuenta' }).click();
        await ap.waitForFunction((e) => ![...document.querySelectorAll('tr')].some((r) => r.innerText.includes(e)), email, { timeout: 15000 });   // sale de «Pendientes»
        await ap.getByTestId('filter-approved').click();
        await ap.locator('tr', { hasText: email }).getByText('Aprobada').waitFor({ timeout: 10000 });
        assert.deepEqual(admin.errors, []);
    } finally {
        await admin.ctx.close();
        php(`$u = App\\Models\\User::where('email', '${email}')->first(); if ($u) { $u->profile()->delete(); $u->roles()->detach(); $u->delete(); } App\\Models\\AccessRequest::where('email', '${email}')->delete(); echo 'ok';`);
    }
});

test('mensajes: el alumno escribe a su instructor, adjunta un archivo y el instructor lo lee', async () => {
    const tutorId = php(`echo App\\Models\\User::where('email', '${ROLES.tutor.email}')->value('id');`);
    const text = `E2E ${TAG} hola profe`;
    const st = await session(browser, 'student');
    try {
        const { page } = st;
        await page.goto(`${BASE}/messenger?con=${tutorId}`, { waitUntil: 'networkidle' });
        await page.getByTestId('chat-thread').waitFor();
        await page.getByTestId('chat-input').fill(text + '\nsegunda línea https://horizontia.test');
        await page.getByTestId('chat-file').setInputFiles('public/images/nuevo/Logo-Horizontia-footer.png');
        await page.getByTestId('chat-send').click();
        await page.getByTestId('chat-message').filter({ hasText: text }).waitFor({ timeout: 10000 });
        assert.equal(await page.locator('.hz-msg__img').count(), 1, 'la imagen se ve en la burbuja');
        await page.screenshot({ path: `${SHOTS}/feat-chat-student.png` });
        assert.deepEqual(st.errors, []);
    } finally { await st.ctx.close(); }

    const tu = await session(browser, 'tutor');
    try {
        const { page } = tu;
        await page.goto(`${BASE}/messenger`, { waitUntil: 'networkidle' });
        await page.getByTestId('chat-item').filter({ hasText: text.slice(0, 20) }).first().click();
        await page.getByTestId('chat-message').filter({ hasText: text }).waitFor({ timeout: 10000 });
        await page.getByTestId('chat-input').fill('Hola, con gusto te ayudo.');
        await page.getByTestId('chat-send').click();
        await page.getByTestId('chat-message').filter({ hasText: 'con gusto te ayudo' }).waitFor({ timeout: 10000 });
        await page.screenshot({ path: `${SHOTS}/feat-chat-tutor.png` });
    } finally {
        await tu.ctx.close();
        php(`$c = App\\Models\\ChatMessage::where('body', 'like', 'E2E ${TAG}%')->pluck('conversation_id')->unique(); foreach (App\\Models\\ChatMessage::whereIn('conversation_id', $c)->get() as $m) { if ($m->file_path) { Storage::disk(App\\Services\\ChatService::disk())->delete($m->file_path); } $m->forceDelete(); } App\\Models\\ChatConversation::whereIn('id', $c)->delete(); echo 'ok';`);
    }
});
