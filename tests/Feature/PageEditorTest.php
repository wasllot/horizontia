<?php

namespace Tests\Feature;

use App\Models\PageRevision;
use App\Models\SitePage;
use App\Services\Pages\PageContentRenderer;
use App\Services\Pages\PageHtmlSanitizer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * GrapesJS page editor: admin screens, save/load/revisions API, sanitising, dynamic blocks, SEO schema,
 * uploads, public rendering and the migration of legacy block pages.
 */
class PageEditorTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    private function page(string $slug = 'pagina-prueba', string $html = '<h1>Hola</h1>', string $status = 'published'): SitePage
    {
        return SitePage::create(['name' => 'Prueba', 'title' => 'Título de prueba', 'slug' => $slug, 'status' => $status, 'settings' => [], 'gjs_html' => $html, 'gjs_css' => '']);
    }

    private function project(string $html = '<h1>Nuevo</h1>'): array
    {
        return ['project' => ['pages' => [['frames' => [['component' => ['type' => 'wrapper', 'components' => [['tagName' => 'h1', 'type' => 'text', 'components' => [['type' => 'textnode', 'content' => 'Nuevo']]]]]]]]]], 'html' => $html, 'css' => 'h1{color:red}'];
    }

    // ------------------------------------------------------------------ screens and access

    public function test_pages_list_and_editor_are_admin_only(): void
    {
        $page = $this->page();

        $this->get('/admin/paginas')->assertRedirect();
        $this->actingAs($this->student())->get('/admin/paginas')->assertForbidden();
        $this->actingAs($this->student())->get("/admin/paginas/{$page->id}/editor")->assertForbidden();
        $this->actingAs($this->student())->putJson("/admin/paginas/{$page->id}/proyecto", $this->project())->assertForbidden();
        $this->actingAs($this->student())->post('/admin/paginas/medios')->assertForbidden();

        $this->actingAs($this->admin())->get('/admin/paginas')->assertOk()->assertSee('Nueva página')->assertSee('Prueba');
    }

    public function test_editor_screen_loads_grapesjs_locally_with_its_config(): void
    {
        $page = $this->page();

        $html = $this->actingAs($this->admin())->get("/admin/paginas/{$page->id}/editor")->assertOk()->getContent();

        foreach (['vendor/grapesjs/grapes.min.js', 'vendor/grapesjs/locale/es.js', 'vendor/grapesjs/plugins/preset-webpage.min.js', 'js/page-editor.js', 'window.HZ_EDITOR'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringNotContainsString('cdn.', $html, 'the editor must not load third-party scripts');
        foreach (['public/vendor/grapesjs/grapes.min.js', 'public/vendor/grapesjs/plugins/forms.min.js', 'public/js/page-editor.js', 'public/css/page-editor.css'] as $file) {
            $this->assertFileExists(base_path($file));
        }
    }

    public function test_home_page_record_cannot_be_opened_in_the_editor(): void
    {
        $home = SitePage::firstOrCreate(['slug' => '/'], ['name' => 'Home', 'title' => 'Home', 'status' => 'published', 'settings' => []]);

        $this->actingAs($this->admin())->get("/admin/paginas/{$home->id}/editor")->assertNotFound();
    }

    // ------------------------------------------------------------------ save / load / revisions

    public function test_save_stores_project_html_and_css_and_the_public_page_shows_it(): void
    {
        $page = $this->page('promo-editor', '<p>viejo</p>');

        $this->actingAs($this->admin())->putJson("/admin/paginas/{$page->id}/proyecto", $this->project('<section><h1>Contenido nuevo</h1></section>'))
            ->assertOk()->assertJson(['saved' => true]);

        $page->refresh();
        $this->assertStringContainsString('Contenido nuevo', $page->gjs_html);
        $this->assertSame('h1{color:red}', $page->gjs_css);
        $this->assertNotEmpty(json_decode($page->gjs_project, true));
        $this->assertNotNull($page->edited_at);

        $this->get('/promo-editor')->assertOk()->assertSee('Contenido nuevo')->assertSee('h1{color:red}', false)->assertSee('id="hz-page"', false);
    }

    public function test_project_endpoint_returns_the_stored_project_and_never_caches(): void
    {
        $page = $this->page();
        $this->actingAs($this->admin())->putJson("/admin/paginas/{$page->id}/proyecto", $this->project());

        $res = $this->actingAs($this->admin())->getJson("/admin/paginas/{$page->id}/proyecto")->assertOk()->assertJsonPath('project.pages.0.frames.0.component.type', 'wrapper');
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));

        // A page without a project (migrated/seeded) opens from its HTML.
        $legacy = $this->page('sin-proyecto', '<p>solo html</p>');
        $this->actingAs($this->admin())->getJson("/admin/paginas/{$legacy->id}/proyecto")->assertOk()->assertJson(['project' => null, 'html' => '<p>solo html</p>']);
    }

    public function test_save_validates_its_input(): void
    {
        $page = $this->page();
        $admin = $this->admin();

        $this->actingAs($admin)->putJson("/admin/paginas/{$page->id}/proyecto", [])->assertUnprocessable()->assertJsonValidationErrors(['project', 'html']);
        $this->actingAs($admin)->putJson("/admin/paginas/{$page->id}/proyecto", ['project' => 'texto', 'html' => '<p>x</p>'])->assertUnprocessable();
    }

    public function test_revisions_are_kept_listed_restorable_and_capped(): void
    {
        $page = $this->page();
        $admin = $this->admin();

        $this->actingAs($admin)->putJson("/admin/paginas/{$page->id}/proyecto", $this->project() + ['note' => 'Versión A'])->assertOk();
        $this->actingAs($admin)->putJson("/admin/paginas/{$page->id}/proyecto", $this->project('<h1>B</h1>') + ['note' => 'Versión B'])->assertOk();

        $list = $this->actingAs($admin)->getJson("/admin/paginas/{$page->id}/historial")->assertOk()->json();
        $this->assertSame(['Versión B', 'Versión A'], array_column($list, 'note'));

        $this->actingAs($admin)->getJson("/admin/paginas/{$page->id}/historial/{$list[1]['id']}")->assertOk()->assertJsonStructure(['project']);

        // A revision of another page cannot be restored through this one.
        $other = $this->page('otra');
        $this->actingAs($admin)->putJson("/admin/paginas/{$other->id}/proyecto", $this->project() + ['note' => 'Ajena']);
        $foreign = PageRevision::where('page_id', $other->id)->first();
        $this->actingAs($admin)->getJson("/admin/paginas/{$page->id}/historial/{$foreign->id}")->assertNotFound();

        for ($i = 0; $i < PageRevision::KEEP + 5; $i++) {
            $this->actingAs($admin)->putJson("/admin/paginas/{$page->id}/proyecto", $this->project("<p>$i</p>") + ['note' => "n$i"]);
        }
        $this->assertSame(PageRevision::KEEP, PageRevision::where('page_id', $page->id)->count());
    }

    public function test_autosaves_within_a_minute_update_one_revision_instead_of_flooding(): void
    {
        $page = $this->page();
        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($this->admin())->putJson("/admin/paginas/{$page->id}/proyecto", $this->project("<p>$i</p>"))->assertOk();
        }

        $this->assertSame(1, PageRevision::where('page_id', $page->id)->count());
    }

    // ------------------------------------------------------------------ page settings

    public function test_settings_update_name_slug_status_and_validate_the_url(): void
    {
        $page = $this->page();
        $admin = $this->admin();

        $this->actingAs($admin)->putJson("/admin/paginas/{$page->id}/datos", ['name' => 'Beneficios', 'title' => '', 'description' => 'D', 'slug' => 'beneficios', 'status' => 'draft'])
            ->assertOk()->assertJsonPath('page.slug', 'beneficios')->assertJsonPath('page.title', 'Beneficios');
        $this->get('/beneficios')->assertNotFound();   // drafts are not public

        $bad = fn (string $slug) => $this->actingAs($admin)->putJson("/admin/paginas/{$page->id}/datos", ['name' => 'X', 'slug' => $slug, 'status' => 'published']);
        $bad('Con Mayúsculas')->assertUnprocessable();
        $bad('con espacios')->assertUnprocessable();
        $bad('admin/secreto')->assertUnprocessable();   // reserved prefix
        $bad('cursos')->assertUnprocessable();
        $bad('<script>')->assertUnprocessable();

        $this->page('ocupada');
        $bad('ocupada')->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    // ------------------------------------------------------------------ sanitising

    public function test_sanitizer_strips_scripts_handlers_and_dangerous_urls_but_keeps_layout(): void
    {
        $dirty = '<div class="container" onclick="x()"><script>alert(1)</script><style>body{}</style><a href="javascript:alert(1)" onmouseover="y()">malo</a>'
            . '<a href="/cursos" target="_blank">bueno</a><img src="data:text/html;base64,PHNjcmlwdD4=" onerror="z()"><img src="data:image/png;base64,iVBORw0KGgo=">'
            . '<iframe src="https://evil.example/x"></iframe><iframe src="https://www.youtube-nocookie.com/embed/abc"></iframe><p style="background:url(javascript:alert(1))">p</p><object data="x"></object></div>';

        $clean = PageHtmlSanitizer::html($dirty);

        foreach (['<script', 'alert(1)', 'onclick', 'onmouseover', 'onerror', 'javascript:', 'evil.example', '<object', '<style', 'data:text/html'] as $bad) {
            $this->assertStringNotContainsString($bad, $clean, "survived: $bad");
        }
        foreach (['class="container"', 'href="/cursos"', 'rel="noopener noreferrer"', 'data:image/png;base64', 'youtube-nocookie.com/embed/abc', 'bueno'] as $good) {
            $this->assertStringContainsString($good, $clean, "lost: $good");
        }
    }

    public function test_css_sanitizer_blocks_imports_expressions_and_style_breakouts(): void
    {
        $css = PageHtmlSanitizer::css("@import url(//evil.test/x.css); .a{width:expression(alert(1));background:url(javascript:alert(1))} </style><script>x</script> .b{color:red}");

        $this->assertStringNotContainsString('@import', $css);
        $this->assertStringNotContainsString('expression', $css);
        $this->assertStringNotContainsString('javascript:', $css);
        $this->assertStringNotContainsString('</style', $css);
        $this->assertStringContainsString('.b{color:red}', $css);
    }

    public function test_a_hostile_save_never_reaches_the_public_page(): void
    {
        $page = $this->page('hostil');
        $this->actingAs($this->admin())->putJson("/admin/paginas/{$page->id}/proyecto", $this->project('<h1>ok</h1><script>window.pwned=1</script><img src=x onerror="window.pwned=2">') + []);

        $html = $this->get('/hostil')->assertOk()->getContent();

        $this->assertStringContainsString('<h1>ok</h1>', $html);
        $this->assertStringNotContainsString('window.pwned', $html);
    }

    // ------------------------------------------------------------------ public rendering

    public function test_tokens_dynamic_courses_block_and_drafts(): void
    {
        config(['academy.refund_days' => 11]);
        $course = $this->course();
        $page = $this->page('con-bloques', '<p>Reembolso en [[refund_days]] días</p><div data-hz-block="courses" data-count="3" data-heading="Nuestros cursos"></div>');

        $this->get('/con-bloques')->assertOk()->assertSee('Reembolso en 11 días')->assertSee('Nuestros cursos')->assertSee($course->title)
            ->assertDontSee('data-hz-block', false);

        $page->update(['status' => 'draft']);
        $this->get('/con-bloques')->assertNotFound();
        $this->actingAs($this->admin())->get('/con-bloques?preview=1')->assertOk();
    }

    public function test_faq_accordion_in_any_page_publishes_faqpage_json_ld(): void
    {
        $html = '<div class="accordion"><div class="accordion-item"><h3><button class="accordion-button">¿Cuánto cuesta?</button></h3><div class="accordion-collapse"><div class="accordion-body">Desde $499.</div></div></div></div>';
        $this->page('faq-propia', $html);

        $page = $this->get('/faq-propia')->assertOk()->getContent();
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $page, $m);
        $json = json_decode($m[1] ?? '', true);

        $this->assertSame('FAQPage', $json['@type']);
        $this->assertSame('¿Cuánto cuesta?', $json['mainEntity'][0]['name']);
        $this->assertSame('Desde $499.', $json['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_course_info_block_renders_only_in_the_context_of_a_course(): void
    {
        $course = $this->course();
        $page = $this->page('info-curso', '<div data-hz-block="course-info"></div>');
        \App\Models\CoursePage::create(['course_id' => $course->id, 'page_id' => $page->id, 'label' => 'Info', 'sort' => 1]);

        $this->get('/info-curso')->assertOk()->assertDontSee('Ver curso y comprar');
        $this->get(route('courses.info-page', [$course->slug, 'info-curso']))->assertOk()->assertSee($course->title)->assertSee('Ver curso y comprar');
    }

    // ------------------------------------------------------------------ uploads

    public function test_image_upload_validates_type_and_size_and_returns_grapesjs_format(): void
    {
        Storage::fake(getStorageDisk());
        $admin = $this->admin();

        $ok = $this->actingAs($admin)->post('/admin/paginas/medios', ['files' => [UploadedFile::fake()->image('foto.jpg', 300, 200)]], ['Accept' => 'application/json'])->assertOk();
        $this->assertNotEmpty($ok->json('data.0.src'));

        $this->actingAs($admin)->post('/admin/paginas/medios', ['files' => [UploadedFile::fake()->create('virus.php', 5, 'application/x-php')]], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->actingAs($admin)->post('/admin/paginas/medios', ['files' => [UploadedFile::fake()->create('dibujo.svg', 5, 'image/svg+xml')]], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->actingAs($admin)->post('/admin/paginas/medios', ['files' => [UploadedFile::fake()->image('enorme.jpg')->size(6000)]], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->actingAs($admin)->post('/admin/paginas/medios', [], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    // ------------------------------------------------------------------ list actions

    public function test_pages_manager_creates_duplicates_publishes_and_protects_system_pages(): void
    {
        $c = Livewire::actingAs($this->admin())->test(\App\Livewire\Pages\Admin\Pages\PagesManager::class);

        $before = SitePage::count();
        $c->call('create');
        $this->assertSame($before + 1, SitePage::count());
        $new = SitePage::latest('id')->first();
        $this->assertSame('draft', $new->status);
        $this->assertSame('', $new->gjs_html);

        $c->call('togglePublish', $new->id);
        $this->assertSame('published', $new->fresh()->status);

        $c->call('duplicate', $new->id);
        $copy = SitePage::where('slug', $new->slug . '-copia')->first();
        $this->assertNotNull($copy);
        $this->assertSame('draft', $copy->status);

        $c->call('delete', $copy->id);
        $this->assertNull(SitePage::find($copy->id));

        $terms = SitePage::where('slug', 'terminos-y-condiciones')->first();
        $c->call('delete', $terms->id);
        $this->assertNotNull(SitePage::find($terms->id), 'system pages must not be deletable');

        $c->set('search', 'terminos')->assertSee('Términos')->assertDontSee($new->name . ' (copia)');
    }

    // ------------------------------------------------------------------ legacy migration

    public function test_seeded_pages_are_editable_in_the_visual_editor_and_keep_their_content(): void
    {
        $terms = SitePage::where('slug', 'terminos-y-condiciones')->firstOrFail();

        $this->assertTrue($terms->usesVisualEditor());
        $this->assertStringContainsString('Términos y condiciones', $terms->gjs_html);
        $this->actingAs($this->admin())->get("/admin/paginas/{$terms->id}/editor")->assertOk();
        $this->get('/terminos-y-condiciones')->assertOk()->assertSee('Términos y condiciones')->assertDontSee('[[company]]', false);
    }

    public function test_converter_turns_a_legacy_block_page_into_editable_html_and_is_idempotent(): void
    {
        $legacy = \App\Services\BuilderPageFactory::upsert('antigua', 'Antigua', 'Antigua', 'x', [['ht-hero', ['heading' => 'Título antiguo']], ['ht-cta', ['heading' => 'Actúa ya', 'button_text' => 'Ir', 'button_url' => '/cursos']]]);
        $site = SitePage::find($legacy->id);
        $this->assertFalse($site->usesVisualEditor());

        $this->artisan('pages:to-editor', ['--slug' => 'antigua'])->assertSuccessful();

        $site->refresh();
        $this->assertTrue($site->usesVisualEditor());
        $this->assertStringContainsString('Título antiguo', $site->gjs_html);
        $this->assertStringContainsString('Actúa ya', $site->gjs_html);
        $this->assertNotEmpty($site->settings['grids'], 'legacy settings are kept so the page can be rolled back');

        $before = $site->gjs_html;
        $this->artisan('pages:to-editor', ['--slug' => 'antigua'])->assertSuccessful();
        $this->assertSame($before, $site->fresh()->gjs_html);
    }

    // ------------------------------------------------------------------ cleanup of the old builder

    public function test_old_block_builder_admin_routes_and_loader_are_gone(): void
    {
        foreach (['pagebuilder.build', 'page.edit', 'page.create', 'pagebuilder.iframe', 'pagebuilder.get-section-settings'] as $name) {
            $this->assertFalse(\Illuminate\Support\Facades\Route::has($name), "$name still registered");
        }
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.pages'));
        $css = file_get_contents(public_path('css/panel.css'));
        $this->assertStringContainsString('.lb-preloader-outer', $css);
        $this->assertMatchesRegularExpression('/lb-preloader-outer[^{]*\{[^}]*display:\s*none/', $css);
    }

    public function test_sidebar_and_course_pages_link_to_the_new_editor(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/estadisticas')->getContent();

        $this->assertStringContainsString(route('admin.pages'), $html);
        $this->assertStringNotContainsString('/admin/pages', $html);
    }

    public function test_renderer_handles_missing_course_and_unknown_blocks_gracefully(): void
    {
        $page = $this->page('desconocido', '<div data-hz-block="nope"></div><p>texto</p>');

        $out = app(PageContentRenderer::class)->render($page);

        $this->assertStringContainsString('texto', $out['html']);
        $this->assertStringNotContainsString('data-hz-block', $out['html']);
    }
}
