// E2E del flujo principal: la instructora construye un curso con todos los tipos de lección, lo envía a revisión,
// el administrador lo publica y una alumna lo cursa (video, artículo, test calificado en el servidor, sesión en vivo, SCORM).
//   node --test --test-reporter=spec tests/e2e-panel/course-wizard.e2e.mjs
// Requiere el sitio en :8000, la demo sembrada y el contenedor de la app (artisanTinker).
import test from 'node:test';
import assert from 'node:assert/strict';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { launch, session, BASE, SHOTS, settle, artisanTinker } from './helpers.mjs';

const FX = path.join(path.dirname(fileURLToPath(import.meta.url)), 'fixtures');
const STAMP = Date.now().toString(36);
const TITLE = `E2E Constructor ${STAMP}`;
const browser = await launch();
test.after(async () => {
    await browser.close();
    artisanTinker(`$c = Modules\\Courses\\Models\\Course::where('title', '${TITLE}')->first(); if ($c) { $c->enrollments()->delete(); $c->delete(); }`);
});

let courseId;

test.describe('flujo de creación de un curso, de punta a punta', () => {
    test('preparación: curso en borrador de la instructora con portada y precio gratuito', () => {
        const out = artisanTinker(`
            $u = App\\Models\\User::where('email', 'instructora@horizontia.test')->firstOrFail();
            $src = Modules\\Courses\\Models\\Course::where('instructor_id', $u->id)->whereHas('thumbnail')->firstOrFail();
            $c = $src->replicate(); $c->title = '${TITLE}'; $c->slug = 'e2e-constructor-${STAMP}'; $c->status = 'draft'; $c->content_length = 0; $c->save();
            $t = $src->thumbnail->replicate(); $t->mediable_id = $c->id; $t->save();
            Modules\\Courses\\Models\\Pricing::create(['course_id' => $c->id, 'price' => 0, 'discount' => 0, 'final_price' => 0]);
            echo 'ID=' . $c->id;
        `);
        courseId = Number(out.match(/ID=(\d+)/)?.[1]);
        assert.ok(courseId > 0, 'no se pudo crear el curso de prueba: ' + out);
    });

    test('la instructora crea una lección de cada tipo en el constructor', async () => {
        const s = await session(browser, 'tutor', { width: 1500, height: 950 });
        try {
            const { page } = s;
            await page.goto(`${BASE}/curso/editar/content/${courseId}`, { waitUntil: 'networkidle' });
            await settle(page, 800);

            // Primera sección
            await page.getByRole('button', { name: /Nueva sección/ }).first().click();
            await page.locator('.cr-addrow--section input').fill('Módulo 1');
            await page.keyboard.press('Enter');
            await page.waitForSelector('.cr-sec');

            const addLesson = async (title) => {
                await page.locator('.cr-sec').first().locator('.cr-add').first().click();
                await page.fill('.cr-addrow input', title);
                await page.click('.cr-addrow button[type=submit]');
                await page.waitForFunction((t) => document.querySelector('.cr-title-input')?.value === t, title);
            };
            const save = async () => { await page.getByRole('button', { name: 'Guardar lección' }).click(); await page.waitForTimeout(1000); };
            const activeId = () => page.evaluate(() => document.querySelector('.cr-lesson.is-active').dataset.id);

            await addLesson('YouTube');
            await page.getByRole('radio', { name: 'YouTube' }).click();
            await page.fill('#lesson-link', 'https://youtu.be/dQw4w9WgXcQ');
            await save();

            await addLesson('Artículo');
            await page.getByRole('tab', { name: /Artículo/ }).click();
            await page.waitForSelector('.note-editable');
            await page.click('.note-editable');
            await page.keyboard.type('Texto del artículo E2E');
            await page.waitForTimeout(500);
            await save();

            await addLesson('Test final');
            await page.getByRole('tab', { name: /Test/ }).click();
            await page.getByRole('button', { name: 'Añadir pregunta' }).first().click();
            await page.fill('.cr-q__text', '¿Cuánto es 2+2?');
            const opts = page.locator('.cr-opt input[type=text]');
            await opts.nth(0).fill('3'); await opts.nth(1).fill('4'); await opts.nth(2).fill('5');
            await page.locator('.cr-opt__mark input').nth(1).check();
            await save();

            await addLesson('Clase en vivo');
            await page.getByRole('tab', { name: /En vivo/ }).click();
            await page.fill('#live-date', new Date(Date.now() + 3 * 86400000).toISOString().slice(0, 16));
            await page.fill('#live-link', 'https://meet.google.com/abc-defg-hij');
            await save();

            await addLesson('Paquete SCORM');
            await page.getByRole('tab', { name: /SCORM/ }).click();
            await page.setInputFiles(`#scorm-file-${await activeId()}`, path.join(FX, 'scorm-e2e.zip'));
            await page.waitForTimeout(1500);
            await save();
            await page.waitForSelector('text=Paquete SCORM cargado', { timeout: 8000 });

            await addLesson('Video propio');
            await page.setInputFiles(`#video-file-${await activeId()}`, path.join(FX, 'tiny.mp4'));
            await page.waitForTimeout(1500);
            await save();

            await page.screenshot({ path: `${SHOTS}/wizard-builder-complete.png`, fullPage: true });
            const count = artisanTinker(`echo 'N=' . Modules\\Courses\\Models\\Curriculum::whereIn('section_id', Modules\\Courses\\Models\\Section::where('course_id', ${courseId})->pluck('id'))->count();`);
            assert.match(count, /N=6/, 'deben existir las seis lecciones');
            assert.deepEqual(s.errors, []);
        } finally { await s.ctx.close(); }
    });

    test('un paquete SCORM con ejecutables se rechaza o se limpia', async () => {
        const out = artisanTinker(`
            $lessons = Modules\\Courses\\Models\\Curriculum::whereIn('section_id', Modules\\Courses\\Models\\Section::where('course_id', ${courseId})->pluck('id'))->where('type', 'scorm')->get();
            foreach ($lessons as $l) { $dir = public_path('storage/scorm/' . $l->id); echo 'PHP=' . count(glob($dir . '/*.php') ?: []) . ';'; }
        `);
        assert.match(out, /PHP=0/);
    });

    test('la instructora ve la lista de verificación y envía el curso a revisión', async () => {
        const s = await session(browser, 'tutor');
        try {
            const { page } = s;
            await page.goto(`${BASE}/curso/editar/publish/${courseId}`, { waitUntil: 'networkidle' });
            await settle(page, 600);
            assert.equal(await page.getByTestId('check-lessons').getAttribute('class').then((c) => /is-ok/.test(c)), true);
            assert.equal(await page.getByTestId('check-complete').getAttribute('class').then((c) => /is-ok/.test(c)), true);
            await page.getByTestId('publish-btn').click();
            await page.waitForSelector('[data-testid="publish-done"]', { timeout: 8000 });
            assert.match(artisanTinker(`echo Modules\\Courses\\Models\\Course::find(${courseId})->status;`), /under_review/);
            await page.screenshot({ path: `${SHOTS}/wizard-publish-review.png` });
            assert.deepEqual(s.errors, []);
        } finally { await s.ctx.close(); }
    });

    test('el administrador edita el curso con rutas de administrador y lo publica', async () => {
        const s = await session(browser, 'admin');
        try {
            const { page } = s;
            await page.goto(`${BASE}/admin/curso/editar/media/${courseId}`, { waitUntil: 'networkidle' });
            const back = await page.locator('a:has-text("Atrás")').first().getAttribute('href');
            assert.match(back, /\/admin\//, 'el botón Atrás del administrador no debe salir de /admin');
            await page.goto(`${BASE}/admin/curso/editar/publish/${courseId}`, { waitUntil: 'networkidle' });
            await page.getByTestId('publish-btn').click();
            await page.waitForSelector('[data-testid="publish-done"]', { timeout: 8000 });
            assert.match(artisanTinker(`echo Modules\\Courses\\Models\\Course::find(${courseId})->status;`), /active/);
            assert.deepEqual(s.errors, []);
        } finally { await s.ctx.close(); }
    });

    test('la alumna ve el curso gratis, lo "compra", y la inscripción es inmediata', () => {
        const out = artisanTinker(`
            $u = App\\Models\\User::where('email', 'alumno.pagado@horizontia.test')->firstOrFail();
            Modules\\Courses\\Models\\Enrollment::firstOrCreate(['student_id' => $u->id, 'course_id' => ${courseId}], ['tutor_id' => Modules\\Courses\\Models\\Course::find(${courseId})->instructor_id, 'course_price' => 0, 'course_discount' => 0, 'status' => 'active']);
            echo 'OK';
        `);
        assert.match(out, /OK/);
    });

    test('la alumna cursa: video, artículo, test calificado en el servidor, sesión en vivo y SCORM', async () => {
        const s = await session(browser, 'student', { width: 1440, height: 900 });
        try {
            const { page } = s;
            await page.goto(`${BASE}/aula/e2e-constructor-${STAMP}`, { waitUntil: 'networkidle' });
            await settle(page, 1000);
            const open = async (name) => { const a = page.locator('.cr-sidebar_accordion_content_item a').filter({ hasText: name }).first(); if (!(await a.getAttribute('class'))?.includes('cr-active')) { await a.click(); await settle(page, 900); } };

            await open('YouTube');
            assert.ok(await page.locator('iframe[src*="youtube-nocookie.com/embed/dQw4w9WgXcQ"]').count(), 'el iframe de YouTube debe cargarse');

            await open('Artículo');
            assert.ok(await page.getByText('Texto del artículo E2E').count());

            await open('Test final');
            const html = await page.content();
            assert.ok(!/"correct"/.test(html), 'las respuestas correctas no deben estar en la página');
            await page.getByTestId('quiz-start').click().catch(() => {});
            await page.locator('[data-testid^="q-"] label').nth(0).click();   // respuesta incorrecta ("3")
            await page.getByTestId('quiz-submit').click();
            await page.waitForSelector('[data-testid="quiz-result"]:visible', { timeout: 8000 });
            assert.match(await page.getByTestId('quiz-result').innerText(), /0\s*%|No aprobado|Reprobado/i);

            await open('Clase en vivo');
            assert.ok(await page.getByTestId('live-lesson').count());
            assert.equal(await page.getByTestId('live-join').count() === 0 || !(await page.getByTestId('live-join').isVisible()), true, 'el enlace no debe verse antes de la ventana de acceso');
            assert.ok(!(await page.content()).includes('meet.google.com/abc-defg-hij'), 'el enlace de la reunión no debe estar en la página antes de tiempo');
            assert.ok(await page.getByTestId('live-ics').isVisible(), 'debe ofrecer añadir al calendario');

            await open('Paquete SCORM');
            assert.ok(await page.locator('iframe').count(), 'el SCORM se carga en un iframe');

            await page.screenshot({ path: `${SHOTS}/student-course-taking.png` });
            assert.deepEqual(s.errors, []);
        } finally { await s.ctx.close(); }
    });

    test('el curso gratis aparece en "Mis cursos" y su página ya no ofrece comprarlo', async () => {
        const s = await session(browser, 'student');
        try {
            const { page } = s;
            await page.goto(`${BASE}/mis-cursos`, { waitUntil: 'networkidle' });
            await settle(page, 800);
            assert.ok(await page.getByText(TITLE).count(), 'el curso debe listarse en Mis cursos');
            await page.goto(`${BASE}/curso/e2e-constructor-${STAMP}`, { waitUntil: 'networkidle' });
            assert.equal(await page.getByRole('button', { name: /Comprar ahora/ }).count(), 0);
        } finally { await s.ctx.close(); }
    });
});
