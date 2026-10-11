// E2E de los paneles. Requiere el sitio en E2E_BASE_URL (por defecto http://localhost:8000) con la demo sembrada.
//   node --test tests/e2e-panel/            (ver docs/e2e-panel.md)
import test from 'node:test';
import assert from 'node:assert/strict';
import { launch, session, ROLES, BASE, SHOTS, mark, sameDocument, settle, contrast, artisanTinker } from './helpers.mjs';

const browser = await launch();
test.after(() => browser.close());

const PANEL_ONLY = (h) => h && h !== '#' && /^(\/|https?:\/\/[^/]+\/)(admin|tutor|estudiante|mis-cursos|gestionar-cursos|crear-curso|tutor|certificados)/.test(h.replace(BASE, ''));

for (const role of Object.keys(ROLES)) {
    test.describe(`panel ${role}`, () => {
        let s;
        test.before(async () => { s = await session(browser, role); });
        test.after(async () => { await s.ctx.close(); });

        test('shell: landmarks, skip link, tipografía Inter y sin desbordes', async () => {
            const { page } = s;
            await page.goto(BASE + ROLES[role].home, { waitUntil: 'networkidle' });
            assert.equal(await page.locator('aside.hz-sidebar').count(), 1);
            assert.equal(await page.locator('nav.hz-nav[aria-label]').count(), 1);
            assert.equal(await page.locator('main#hz-main').count(), 1);
            assert.equal(await page.locator('header.hz-topbar').count(), 1);
            assert.match(await page.locator('a.hz-skip').getAttribute('href'), /#hz-main/);
            const font = await page.evaluate(() => getComputedStyle(document.body).fontFamily);
            assert.match(font, /Inter/);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true, 'desborde horizontal');
            // Los controles solo-icono del topbar deben tener nombre accesible.
            const nameless = await page.$$eval('.hz-topbar button, .hz-topbar a.hz-iconbtn', (els) => els.filter((e) => !(e.getAttribute('aria-label') || e.textContent.trim())).length);
            assert.equal(nameless, 0, 'botones del topbar sin nombre accesible');
            await page.screenshot({ path: `${SHOTS}/${role}-home.png` });
        });

        test('navegación asíncrona: cada enlace del menú carga sin recargar y sin errores', async () => {
            const { page, errors } = s;
            await page.goto(BASE + ROLES[role].home, { waitUntil: 'networkidle' });
            const hrefs = [...new Set(await page.$$eval('.hz-sidebar a.hz-nav__link[href]', (as) => as.map((a) => a.getAttribute('href'))))].filter(PANEL_ONLY);
            assert.ok(hrefs.length >= 4, 'el menú debería tener varias pantallas de panel');
            const m = await mark(page);
            errors.length = 0;
            for (const href of hrefs) {
                await page.evaluate((h) => { [...document.querySelectorAll('.hz-sidebar a[href]')].find((x) => x.getAttribute('href') === h)?.click(); }, href);
                await settle(page, 600);
                assert.equal(await sameDocument(page, m), true, `${href} recargó la página completa`);
                await page.waitForFunction(() => (document.querySelector('#hz-main')?.innerText || '').trim().length > 20, null, { timeout: 6000 }).catch(() => {});
                assert.ok((await page.locator('#hz-main').innerText()).trim().length > 20, `${href} quedó vacío`);
                const cur = await page.$$eval('.hz-sidebar [aria-current="page"]', (e) => e.length);
                assert.ok(cur >= 1, `${href} no marca el elemento activo del menú`);
            }
            assert.deepEqual(errors, [], 'errores de JS/HTTP durante la navegación:\n' + errors.join('\n'));
        });

        test('botón atrás/adelante del navegador funciona dentro del SPA', async () => {
            const { page } = s;
            await page.goto(BASE + ROLES[role].home, { waitUntil: 'networkidle' });
            const first = page.url();
            const link = await page.$$eval('.hz-sidebar a.hz-nav__link[href]', (as) => as.map((a) => a.getAttribute('href')).filter((h) => h && h !== '#'));
            const target = link.find((h) => PANEL_ONLY(h) && !first.endsWith(h));
            const m = await mark(page);
            await page.evaluate((h) => [...document.querySelectorAll('.hz-sidebar a[href]')].find((x) => x.getAttribute('href') === h).click(), target);
            await settle(page);
            assert.notEqual(page.url(), first);
            await page.goBack(); await settle(page);
            assert.equal(page.url(), first);
            assert.equal(await sameDocument(page, m), true);
            await page.goForward(); await settle(page);
            assert.ok(page.url().endsWith(target));
        });

        test('sidebar: colapsar se recuerda entre pantallas y recargas', async () => {
            const { page } = s;
            await page.goto(BASE + ROLES[role].home, { waitUntil: 'networkidle' });
            await page.click('[data-hz-collapse]');
            assert.equal(await page.evaluate(() => document.documentElement.classList.contains('hz-collapsed')), true);
            await page.waitForTimeout(350);
            const width = await page.locator('aside.hz-sidebar').evaluate((e) => e.getBoundingClientRect().width);
            assert.ok(width < 100, 'el riel debería medir < 100px, mide ' + width);
            // sigue colapsado tras navegar por SPA y tras recargar
            const href = (await page.$$eval('.hz-sidebar a.hz-nav__link[href]', (as) => as.map((a) => a.getAttribute('href')))).filter(PANEL_ONLY)[1];
            await page.evaluate((h) => [...document.querySelectorAll('.hz-sidebar a[href]')].find((x) => x.getAttribute('href') === h).click(), href);
            await settle(page);
            assert.equal(await page.evaluate(() => document.documentElement.classList.contains('hz-collapsed')), true, 'perdió el estado al navegar');
            await page.reload({ waitUntil: 'networkidle' });
            assert.equal(await page.evaluate(() => document.documentElement.classList.contains('hz-collapsed')), true, 'perdió el estado al recargar');
            await page.click('[data-hz-collapse]');                      // restaurar
            assert.equal(await page.evaluate(() => document.documentElement.classList.contains('hz-collapsed')), false);
        });

        test('menú de cuenta: abre, cierra con Escape y devuelve el foco', async () => {
            const { page } = s;
            await page.goto(BASE + ROLES[role].home, { waitUntil: 'networkidle' });
            const btn = page.locator('.hz-avatarbtn');
            await btn.click();
            assert.equal(await btn.getAttribute('aria-expanded'), 'true');
            await page.locator('.hz-pop.is-open .hz-pop__panel').waitFor({ state: 'visible', timeout: 2000 }).catch(() => {});
            assert.equal(await page.locator('.hz-pop.is-open .hz-pop__panel').isVisible(), true, 'el menú debería verse al abrirlo');
            await page.keyboard.press('Escape');
            assert.equal(await btn.getAttribute('aria-expanded'), 'false');
            assert.equal(await page.evaluate(() => document.activeElement.classList.contains('hz-avatarbtn')), true, 'Escape debe devolver el foco al botón');
            await btn.click(); await page.mouse.click(700, 500);          // clic fuera
            assert.equal(await page.locator('.hz-pop.is-open').count(), 0, 'un clic fuera debe cerrar el menú');
        });

        test('contraste AA del menú lateral y de los botones primarios', async () => {
            const { page } = s;
            await page.goto(BASE + ROLES[role].home, { waitUntil: 'networkidle' });
            const side = await page.$eval('.hz-nav__link:not([aria-current])', (e) => [getComputedStyle(e).color, getComputedStyle(e.closest('.hz-sidebar')).backgroundColor]);
            assert.ok(contrast(...side) >= 4.5, `menú lateral: contraste ${contrast(...side).toFixed(2)}`);
            const btn = await page.$('.am-btn, .hz-btn--primary');
            if (btn) {
                const c = await btn.evaluate((e) => [getComputedStyle(e).color, getComputedStyle(e).backgroundColor]);
                assert.ok(contrast(...c) >= 4.5, `botón primario: contraste ${contrast(...c).toFixed(2)}`);
            }
        });

        test('móvil (390px): cajón del menú con botón, scrim y Escape; sin desbordes', async () => {
            const ms = await session(browser, role, { width: 390, height: 844 });
            try {
                const { page } = ms;
                await page.goto(BASE + ROLES[role].home, { waitUntil: 'networkidle' });
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true, 'desborde horizontal en móvil');
                const sidebarX = () => page.locator('aside.hz-sidebar').evaluate((e) => e.getBoundingClientRect().right);
                assert.ok((await sidebarX()) <= 0, 'el cajón debe empezar cerrado');
                await page.click('[data-hz-menu]');
                await page.waitForTimeout(300);
                assert.ok((await sidebarX()) > 200, 'el cajón debería abrirse');
                assert.equal(await page.locator('[data-hz-menu]').getAttribute('aria-expanded'), 'true');
                await page.keyboard.press('Escape'); await page.waitForTimeout(300);
                assert.ok((await sidebarX()) <= 0, 'Escape debería cerrar el cajón');
                await page.click('[data-hz-menu]'); await page.waitForTimeout(250);
                await page.mouse.click(370, 500);                                        // scrim
                await page.waitForTimeout(300);
                assert.ok((await sidebarX()) <= 0, 'el scrim debería cerrar el cajón');
                await page.screenshot({ path: `${SHOTS}/${role}-mobile.png` });
                assert.deepEqual(ms.errors, []);
            } finally { await ms.ctx.close(); }
        });
    });
}

test('tiempo real: las notificaciones del alumno aparecen en el badge sin recargar', async () => {
    const s = await session(browser, 'student');
    try {
        const { page } = s;
        await page.goto(BASE + ROLES.student.home, { waitUntil: 'networkidle' });
        const badge = page.locator('[data-hz-badge="notifications"]');
        const before = (await badge.isVisible()) ? Number(await badge.innerText()) : 0;
        const m = await mark(page);
        artisanTinker(`$u=App\\Models\\User::where('email','${ROLES.student.email}')->first(); \\Illuminate\\Support\\Facades\\DB::table('notifications')->insert(['id'=>(string)\\Illuminate\\Support\\Str::uuid(),'type'=>'App\\\\Notifications\\\\E2E','notifiable_type'=>'App\\\\Models\\\\User','notifiable_id'=>$u->id,'data'=>json_encode(['subject'=>'E2E','content'=>'tiempo real']),'created_at'=>now(),'updated_at'=>now()]);`);
        await page.evaluate(() => window.hzPanel.pulse());              // adelanta el sondeo para no esperar 15 s
        await page.waitForFunction((b) => { const e = document.querySelector('[data-hz-badge="notifications"]'); return e && !e.hidden && Number(e.textContent) > b; }, before, { timeout: 8000 });
        assert.equal(await sameDocument(page, m), true);
        // limpieza
        artisanTinker(`\\Illuminate\\Support\\Facades\\DB::table('notifications')->where('type','App\\\\Notifications\\\\E2E')->delete();`);
        await page.evaluate(() => window.hzPanel.pulse());
        await page.waitForFunction((b) => { const e = document.querySelector('[data-hz-badge="notifications"]'); return !e || e.hidden || Number(e.textContent) <= b; }, before, { timeout: 8000 });
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('tiempo real: el listado de usuarios del admin se actualiza solo', async () => {
    const s = await session(browser, 'admin');
    try {
        const { page } = s;
        await page.goto(BASE + '/admin/usuarios', { waitUntil: 'networkidle' });
        const m = await mark(page);
        assert.ok(await page.locator('#hz-main').getAttribute('data-hz-live'), 'la pantalla debería declarar data-hz-live');
        await page.evaluate(() => document.getElementById('hz-main').setAttribute('data-hz-live', '2'));   // acelera el sondeo para la prueba
        await page.evaluate(() => window.hzPanel.liveTick());
        const stamp = 'Zzvivo' + Date.now().toString(36);
        artisanTinker(`$u=new App\\Models\\User(); $u->email='${stamp}@horizontia.test'; $u->password=bcrypt('x'); $u->email_verified_at=now(); $u->status=1; $u->save(); $u->assignRole('student'); $u->profile()->create(['first_name'=>'${stamp}','last_name'=>'Prueba','verified_at'=>now()]);`);
        try {
            await page.waitForFunction((t) => document.body.innerText.includes(t), stamp, { timeout: 15000 });
            assert.equal(await sameDocument(page, m), true);
        } finally {
            artisanTinker(`$u=App\\Models\\User::where('email','${stamp}@horizontia.test')->first(); if($u){ App\\Models\\Profile::withTrashed()->where('user_id',$u->id)->forceDelete(); $u->forceDelete(); }`);
        }
    } finally { await s.ctx.close(); }
});

test('formularios asíncronos: perfil del alumno guarda sin recargar', async () => {
    const s = await session(browser, 'student');
    try {
        const { page } = s;
        await page.goto(BASE + '/estudiante/perfil/datos-personales', { waitUntil: 'networkidle' });
        const m = await mark(page);
        const phone = '55' + String(Date.now()).slice(-8);
        await page.fill('input[placeholder*="teléfono" i]', phone);
        const reqs = [];
        page.on('request', (r) => { if (r.url().includes('/livewire/update')) reqs.push(r.method()); });
        await page.click('#hz-main .am-btn:has-text("Guardar y actualizar")');
        await page.waitForResponse((r) => r.url().includes('/livewire/update'), { timeout: 10000 });
        await settle(page, 500);
        assert.ok(reqs.includes('POST'), 'el guardado debe ser una petición Livewire en segundo plano');
        assert.equal(await sameDocument(page, m), true, 'guardar no debe recargar la página');
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('formularios asíncronos: ajustes del admin (interruptor + guardar) sin recargar', async () => {
    const s = await session(browser, 'admin');
    try {
        const { page } = s;
        await page.goto(BASE + '/admin/ajustes', { waitUntil: 'networkidle' });
        assert.equal(await page.locator('.hz-set__nav a').count() >= 8, true, 'el menú de ajustes lista las páginas');
        assert.match(await page.locator('.hz-set__head h3').first().innerText(), /Sitio/);
        await page.screenshot({ path: `${SHOTS}/admin-ajustes.png`, fullPage: true });
        await page.locator('.hz-set__nav a[href="#formatos"]').click();
        await page.locator('#formatos.active').waitFor();
        assert.equal(await page.evaluate(() => location.hash), '#formatos', 'el hash sigue a la página abierta');
        await page.locator('.hz-set__nav a[href="#sitio"]').click();
        const m = await mark(page);
        const calls = [];
        page.on('request', (r) => { if (r.method() === 'POST') calls.push(r.url()); });
        await page.locator('.op-switchbtn').first().click();
        await page.locator('.op-btn.update-section-settings').first().click();
        await page.waitForTimeout(2500);
        assert.ok(calls.length >= 1, 'guardar debe hacer una petición POST en segundo plano');
        assert.equal(await sameDocument(page, m), true);
        await page.locator('.op-switchbtn').first().click();      // deja el valor como estaba
        await page.locator('.op-btn.update-section-settings').first().click();
        await page.waitForTimeout(1500);
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('catálogos del panel: blog y cursos del admin se renderizan con tabla accesible', async () => {
    const s = await session(browser, 'admin');
    try {
        for (const url of ['/admin/blog', '/admin/cursos', '/admin/usuarios', '/admin/inscripciones']) {
            await s.page.goto(BASE + url, { waitUntil: 'networkidle' });
            assert.ok((await s.page.locator('table').count()) >= 1, `${url} sin tabla`);
            const heads = await s.page.$$eval('table thead th', (t) => t.length);
            assert.ok(heads >= 2, `${url} sin cabeceras`);
            await s.page.screenshot({ path: `${SHOTS}/admin${url.replace(/\//g, '_')}.png` });
        }
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('diseño tonal: menú agrupado, filas en bloques sin bordes y tarjeta por pantalla', async () => {
    const s = await session(browser, 'admin');
    try {
        await s.page.goto(BASE + '/admin/usuarios', { waitUntil: 'networkidle' });
        const headings = await s.page.$$eval('.hz-nav__heading', (h) => h.map((x) => x.textContent.trim()));
        assert.deepEqual(headings, ['Academia', 'Contenido', 'Negocio', 'Sistema'], 'el menú debe estar agrupado');
        // Una fila es un bloque tonal: fondo distinto del de la tarjeta, sin borde visible y esquinas redondeadas.
        const row = await s.page.evaluate(() => {
            const td = document.querySelector('table tbody tr td');
            const cs = getComputedStyle(td);
            const card = getComputedStyle(document.querySelector('.hz-content > main, .am-dashboard_box_wrap'));
            return { bg: cs.backgroundColor, cardBg: card.backgroundColor, border: cs.borderTopWidth + '/' + cs.borderBottomWidth, radius: cs.borderTopLeftRadius };
        });
        assert.notEqual(row.bg, row.cardBg, 'la fila debe distinguirse por tono, no por línea');
        assert.equal(row.border, '0px/0px');
        assert.notEqual(row.radius, '0px');
        // Los campos son rellenos y sin contorno visible.
        const field = await s.page.evaluate(() => { const i = document.querySelector('.hz-toolbar input'); const c = getComputedStyle(i); return { bg: c.backgroundColor, border: c.borderTopColor }; });
        assert.notEqual(field.bg, 'rgb(255, 255, 255)');
        assert.match(field.border, /rgba\(0, 0, 0, 0\)|transparent/);
        // Nada desborda la página y la tabla cabe en su tarjeta o se desplaza dentro de ella.
        assert.equal(await s.page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true);
        await s.page.screenshot({ path: `${SHOTS}/admin-tonal-users.png` });
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('tablas en móvil: cada fila pasa a tarjeta con etiquetas de columna', async () => {
    const s = await session(browser, 'admin', { width: 390, height: 844 });
    try {
        await s.page.goto(BASE + '/admin/usuarios', { waitUntil: 'networkidle' });
        const info = await s.page.evaluate(() => {
            const td = document.querySelector('table tbody tr td:nth-child(2)');
            return { label: td.getAttribute('data-label'), display: getComputedStyle(td).display, before: getComputedStyle(td, '::before').content, thead: getComputedStyle(document.querySelector('table thead')).position };
        });
        assert.ok(info.label && info.label.length > 1, 'la celda necesita data-label');
        assert.equal(info.display, 'flex');
        assert.match(info.before, /./);
        assert.equal(info.thead, 'absolute', 'la cabecera se oculta visualmente pero sigue accesible');
        assert.equal(await s.page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true, 'desborde horizontal en móvil');
        await s.page.screenshot({ path: `${SHOTS}/admin-users-mobile-cards.png` });
    } finally { await s.ctx.close(); }
});

test('asistente de cursos: stepper superior, barra de acciones pegada, campos rellenos y botón de carga oculto', async () => {
    const s = await session(browser, 'tutor');
    try {
        await s.page.goto(BASE + '/gestionar-cursos', { waitUntil: 'networkidle' });
        const href = await s.page.$$eval('a[href*="/curso/editar/"]', (a) => a.map((x) => x.getAttribute('href'))[0]);
        await s.page.goto(BASE + (href ? href.replace(BASE, '') : '/crear-curso'), { waitUntil: 'networkidle' });
        await settle(s.page, 800);
        const w = await s.page.evaluate(() => {
            const footer = document.querySelector('.cr-course-box .am-themeform_footer');
            const side = document.querySelector('.cr-stepper');
            const input = document.querySelector('.cr-create-course input.form-control');
            const dup = [...document.querySelectorAll('.am-themeform_footer button[wire\\:loading\\.flex]')].map((b) => getComputedStyle(b).display);
            return {
                footerPos: footer && getComputedStyle(footer).position, stepperTop: side && Math.round(side.getBoundingClientRect().top), stepperWide: side && side.getBoundingClientRect().width > innerWidth * 0.6,
                inputBorder: input && getComputedStyle(input).borderTopColor, inputBg: input && getComputedStyle(input).backgroundColor,
                hasProgress: !!document.querySelector('.cr-progress[role="progressbar"]'), dup,
                inlineStyleTags: document.querySelectorAll('.cr-create-course style').length,
                overflow: document.documentElement.scrollWidth > innerWidth + 1,
            };
        });
        assert.equal(w.footerPos, 'sticky');
        assert.ok(w.stepperWide, 'el stepper ocupa el ancho de la pantalla, arriba');
        assert.ok(w.hasProgress, 'progreso del asistente');
        assert.match(w.inputBorder, /rgba\(0, 0, 0, 0\)|transparent/);
        assert.notEqual(w.inputBg, 'rgb(255, 255, 255)');
        assert.ok(w.dup.every((d) => d === 'none'), 'el botón "guardando" no debe verse duplicado: ' + w.dup);
        assert.equal(w.inlineStyleTags, 0);
        assert.equal(w.overflow, false);
        await s.page.screenshot({ path: `${SHOTS}/tutor-wizard.png` });
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('panel general: datos útiles, cambio de periodo en segundo plano y sin "Perspectivas"', async () => {
    const s = await session(browser, 'admin');
    try {
        const { page } = s;
        await page.goto(BASE + '/admin/usuarios', { waitUntil: 'networkidle' });
        assert.equal(await page.locator('.hz-topbar__crumbs').innerText().then((t) => /Perspectivas/.test(t)), false, 'la miga raíz absurda ya no existe');
        const m = await mark(page);
        await page.evaluate(() => [...document.querySelectorAll('.hz-sidebar a[href]')].find((a) => /estadisticas/.test(a.getAttribute('href'))).click());
        await page.waitForSelector('.hz-kpis', { timeout: 8000 });
        assert.equal(await sameDocument(page, m), true, 'el dashboard debe cargarse sin recarga completa');
        assert.ok((await page.locator('.hz-kpi').count()) >= 4, 'indicadores');
        for (const t of ['Ingresos por día', 'Cursos más vendidos', 'Últimas ventas', 'Requiere tu atención', 'Tu catálogo']) {
            assert.ok(await page.getByText(t, { exact: true }).first().isVisible(), t);
        }
        const before = await page.locator('.hz-dash__period').innerText();
        await page.getByRole('tab', { name: '7 días' }).click();
        await page.waitForFunction(() => document.querySelector('[role="tab"][aria-selected="true"]')?.textContent.trim() === '7 días', null, { timeout: 6000 });
        assert.notEqual(await page.locator('.hz-dash__period').innerText(), before, 'el periodo mostrado cambia');
        assert.equal(await sameDocument(page, m), true);
        await page.screenshot({ path: `${SHOTS}/admin-dashboard.png`, fullPage: true });
        assert.deepEqual(s.errors, []);
    } finally { await s.ctx.close(); }
});

test('detalles: ningún control del panel muestra barras de desplazamiento internas', async () => {
    const s = await session(browser, 'admin');
    try {
        for (const url of ['/admin/blog/crear', '/admin/usuarios', '/admin/cursos', '/admin/inscripciones', '/admin/facturas']) {
            await s.page.goto(BASE + url, { waitUntil: 'networkidle' });
            await settle(s.page, 800);
            const bad = await s.page.evaluate(() => [...document.querySelectorAll('.hz-content select, .hz-content .select2-selection, .hz-content .am-disputelist, .hz-content table')].filter((e) => {
                const st = getComputedStyle(e);
                if (st.display === 'none' || st.position === 'absolute') return false;
                const x = (st.overflowX === 'auto' || st.overflowX === 'scroll') && e.scrollWidth > e.clientWidth + 2;
                const y = (st.overflowY === 'auto' || st.overflowY === 'scroll') && e.scrollHeight > e.clientHeight + 2;
                return x || y;
            }).map((e) => e.className));
            assert.deepEqual(bad, [], `${url} tiene controles con barras de desplazamiento`);
        }
    } finally { await s.ctx.close(); }
});

test('editor visual (GrapesJS): crear página, añadir bloques, guardar, publicar y verla en el sitio', async () => {
    const s = await session(browser, 'admin');
    const slug = 'e2e-editor-' + Date.now().toString(36);
    try {
        const { page } = s;
        await page.goto(BASE + '/admin/paginas', { waitUntil: 'networkidle' });
        assert.equal(await page.locator('.lb-preloader-outer:visible, .lb-generic-spinner:visible').count(), 0, 'no debe haber loader antiguo');
        await page.click('button:has-text("Nueva página")');
        await page.waitForURL(/\/admin\/paginas\/\d+\/editor/, { timeout: 15000 });
        await page.waitForFunction(() => window.editor && document.querySelector('#hzed-saved')?.textContent.includes('Listo'), null, { timeout: 20000 });

        // Interfaz en español y con los bloques de la academia.
        const cats = await page.evaluate(() => [...new Set(window.editor.Blocks.getAll().map((b) => b.get('category')?.id || b.get('category')))]);
        assert.ok(cats.includes('Academia'), 'falta la categoría Academia: ' + cats);
        assert.ok(await page.evaluate(() => window.editor.Blocks.getAll().length) >= 20, 'pocos bloques');
        assert.equal(await page.evaluate(() => window.editor.I18n.getLocale()), 'es');

        // Añade bloques (hero + cursos dinámicos) y edita el título.
        await page.evaluate(() => {
            const b = (id) => window.editor.Blocks.get(id).get('content');
            window.editor.addComponents(b('hz-hero'));
            window.editor.addComponents(b('hz-courses'));
            window.editor.addComponents(b('hz-faq'));
            window.editor.getWrapper().find('h1')[0].components('Página creada por E2E');
        });
        // Ajustes: URL + publicar.
        await page.click('#hzed-settings');
        await page.fill('#hzed-form-settings [name=name]', 'Página E2E');
        await page.fill('#hzed-form-settings [name=slug]', slug);
        await page.selectOption('#hzed-form-settings [name=status]', 'published');
        await page.click('#hzed-form-settings button[type=submit]');
        await page.waitForFunction(() => !document.querySelector('#hzed-dlg-settings').open, null, { timeout: 8000 });
        // Guardar con Ctrl+S y esperar confirmación.
        await page.keyboard.press('Control+s');
        await page.waitForFunction(() => document.querySelector('#hzed-saved')?.dataset.state === 'ok' && /Guardado/.test(document.querySelector('#hzed-saved').textContent), null, { timeout: 10000 });

        // Versiones: aparece al menos una.
        await page.click('#hzed-revisions');
        await page.waitForSelector('#hzed-revision-list li button', { timeout: 6000 });
        await page.click('#hzed-dlg-revisions [data-close]');

        // La página pública muestra el contenido y el bloque dinámico ya renderizado (no el marcador).
        const pub = await s.ctx.newPage();
        await pub.goto(`${BASE}/${slug}`, { waitUntil: 'networkidle' });
        const body = await pub.locator('#hz-page').innerText();
        assert.match(body, /Página creada por E2E/);
        assert.equal(await pub.locator('[data-hz-block]').count(), 0, 'el marcador dinámico debe sustituirse');
        assert.ok(await pub.locator('.ht-course-card').count() >= 1, 'el bloque de cursos debe listar cursos');
        const schemas = await pub.$$eval('script[type="application/ld+json"]', (els) => els.map((e) => e.textContent));
        assert.ok(schemas.some((t) => t.includes('"FAQPage"')), 'el acordeón debe publicar FAQPage JSON-LD');
        await pub.screenshot({ path: `${SHOTS}/editor-public.png`, fullPage: true });
        await page.screenshot({ path: `${SHOTS}/editor.png` });
        assert.deepEqual(s.errors.filter((e) => !/favicon/.test(e)), []);
    } finally {
        artisanTinker(`$p=App\\Models\\SitePage::where('slug','${slug}')->first(); if($p){ App\\Models\\PageRevision::where('page_id',$p->id)->delete(); $p->delete(); }`);
        await s.ctx.close();
    }
});
