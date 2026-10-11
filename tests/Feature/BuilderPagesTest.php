<?php

namespace Tests\Feature;

use App\Models\CoursePage;
use App\Services\BuilderPageFactory;
use App\Services\PageTokens;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Larabuild\Pagebuilder\Models\Page;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Page builder integration: legal/info pages stored in the database, custom blocks, tokens,
 * drafts, and informative pages attached to a course.
 */
class BuilderPagesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    private function page(string $slug, array $blocks, string $status = 'published'): Page
    {
        return BuilderPageFactory::upsert($slug, 'Página ' . $slug, 'Título ' . $slug, 'Descripción de ' . $slug, $blocks, $status);
    }

    public function test_every_seeded_page_lives_in_the_database_and_is_editable(): void
    {
        foreach (\App\Http\Controllers\PageController::PAGES as [$slug]) {
            $page = Page::where('slug', $slug)->first();
            $this->assertNotNull($page, "$slug is not a builder page");
            $this->assertNotEmpty($page->settings['grids'], "$slug has no blocks");
        }
    }

    public function test_prune_removes_the_english_demo_pages_and_keeps_custom_ones(): void
    {
        $this->page('home-two', [['ht-hero', ['heading' => 'demo']]]);
        $this->page('mi-pagina-propia', [['ht-hero', ['heading' => 'mía']]]);

        $this->artisan('academy:pages', ['--prune' => true])->assertSuccessful();

        $this->assertNull(Page::where('slug', 'home-two')->first());
        $this->assertNotNull(Page::where('slug', 'mi-pagina-propia')->first());
    }

    public function test_a_custom_page_is_served_at_its_slug_with_all_the_academy_blocks(): void
    {
        $this->page('promo-verano', [
            ['ht-hero', ['heading' => 'Aprende este verano', 'text' => 'Cupos limitados']],
            ['ht-rich-text', ['heading' => 'Detalles', 'content' => '<p>Texto <strong>importante</strong></p>']],
            ['ht-faq', ['items' => [['question' => '¿Cuánto cuesta?', 'answer' => 'Desde $499']]]],
            ['ht-steps', ['items' => [['title' => 'Paso uno', 'text' => 'Regístrate']]]],
            ['ht-video', ['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']],
            ['ht-cta', ['heading' => 'Empieza ya', 'button_text' => 'Ver cursos', 'button_url' => '/cursos']],
        ]);

        $this->get('/promo-verano')
            ->assertOk()
            ->assertSee('Aprende este verano')
            ->assertSee('<strong>importante</strong>', false)
            ->assertSee('¿Cuánto cuesta?')
            ->assertSee('Paso uno')
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Empieza ya')
            ->assertSee('Título promo-verano', false);   // <title>/meta from the page record
    }

    public function test_draft_pages_and_unknown_slugs_return_404_but_admin_can_preview(): void
    {
        $this->page('borrador-secreto', [['ht-hero', ['heading' => 'Aún no']]], 'draft');

        $this->get('/borrador-secreto')->assertNotFound();
        $this->get('/no-existe-esta-pagina')->assertNotFound();
        $this->actingAs($this->student())->get('/borrador-secreto?preview=1')->assertNotFound();
        $this->actingAs($this->admin())->get('/borrador-secreto?preview=1')->assertOk()->assertSee('Aún no');
    }

    public function test_the_page_builder_does_not_swallow_real_routes(): void
    {
        $this->get('/cursos')->assertOk();
        $this->get('/curso/' . $this->course()->slug)->assertOk();
        $this->get('/api/no-existe')->assertNotFound();
        $this->get('/admin/pagina-inexistente')->assertNotFound();
    }

    public function test_tokens_follow_config_and_degrade_without_email_or_address(): void
    {
        config(['academy.refund_days' => 15, 'academy.legal_name' => 'Academia Demo SA', 'academy.contact_email' => '', 'academy.address' => '']);
        // The theme's footer settings are the fallback; blank them to exercise the "no data" path.
        \Larabuild\Optionbuilder\Facades\Settings::set('_front_page_settings', 'footer_email', '');
        \Larabuild\Optionbuilder\Facades\Settings::set('_front_page_settings', 'footer_address', '');
        $html = '<p>[[company]] reembolsa en [[refund_days]] días. Escribe a <a href="mailto:[[email]]">[[email]]</a>.</p><p>X, con domicilio en [[address]], Y</p>';

        $out = PageTokens::apply($html);

        $this->assertStringContainsString('Academia Demo SA reembolsa en 15 días', $out);
        $this->assertStringContainsString('formulario de contacto', $out);
        $this->assertStringNotContainsString('[[', $out);
        $this->assertStringNotContainsString('con domicilio', $out);

        config(['academy.contact_email' => 'hola@academia.test']);
        $this->assertStringContainsString('mailto:hola@academia.test', PageTokens::apply($html));
    }

    public function test_refund_page_uses_the_configured_refund_window(): void
    {
        config(['academy.refund_days' => 21]);

        $this->get(route('legal.refunds'))->assertOk()->assertSee('Garantía de 21 días');
    }

    public function test_page_content_cannot_inject_scripts_through_faq_answers(): void
    {
        $this->page('faq-xss', [
            ['ht-faq', ['items' => [['question' => 'Q', 'answer' => 'Hola <script>alert(1)</script><a href="/x">ok</a>']]]],
        ]);

        $html = $this->get('/faq-xss')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('<a href="/x">ok</a>', $html);
    }

    public function test_info_pages_attach_to_a_course_and_render_with_course_context(): void
    {
        $course = $this->course();
        $page = $this->page('programa-detallado', [
            ['ht-hero', ['heading' => 'Programa detallado']],
            ['ht-course-info', ['heading' => '']],
        ]);
        CoursePage::create(['course_id' => $course->id, 'page_id' => $page->id, 'label' => 'Programa', 'sort' => 1]);

        $url = route('courses.info-page', [$course->slug, 'programa-detallado']);
        $this->get($url)->assertOk()->assertSee('Programa detallado')->assertSee($course->title)->assertSee('Ver curso y comprar');
        // The sales page links to it.
        $this->get('/curso/' . $course->slug)->assertOk()->assertSee($url, false);
        // Drafts and pages not attached to the course are not served through it.
        $page->update(['status' => 'draft']);
        $this->get($url)->assertNotFound();
        $other = $this->page('otra-pagina', [['ht-hero', ['heading' => 'x']]]);
        $this->get(route('courses.info-page', [$course->slug, $other->slug]))->assertNotFound();
    }

    public function test_info_pages_show_a_course_sub_navigation_with_the_other_pages(): void
    {
        $course = $this->course();
        $a = $this->page('programa-uno', [['ht-hero', ['heading' => 'Uno']]]);
        $b = $this->page('programa-dos', [['ht-hero', ['heading' => 'Dos']]]);
        $draft = $this->page('programa-borrador', [['ht-hero', ['heading' => 'Borrador']]]);
        $draft->update(['status' => 'draft']);
        foreach ([[$a, 'Programa'], [$b, 'Instructor'], [$draft, 'Secreta']] as $i => [$page, $label]) {
            CoursePage::create(['course_id' => $course->id, 'page_id' => $page->id, 'label' => $label, 'sort' => $i + 1]);
        }

        $html = $this->get(route('courses.info-page', [$course->slug, 'programa-uno']))->assertOk()->getContent();

        $this->assertStringContainsString('Volver al curso', $html);
        $this->assertStringContainsString(route('courses.info-page', [$course->slug, 'programa-dos']), $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringNotContainsString('Secreta', $html, 'unpublished pages are not offered');
    }

    public function test_admin_can_attach_reorder_and_detach_course_pages(): void
    {
        $course = $this->course();
        $a = $this->page('pagina-a', [['ht-hero', ['heading' => 'A']]]);
        $b = $this->page('pagina-b', [['ht-hero', ['heading' => 'B']]]);

        $c = Livewire::actingAs($this->admin())->test(\App\Livewire\Pages\Admin\CoursePages\CoursePages::class)
            ->set('courseId', $course->id)
            ->set('pageId', $a->id)->assertSet('label', 'Página pagina-a')->call('attach')
            ->set('pageId', $b->id)->set('label', 'Beta')->call('attach')
            ->set('pageId', $b->id)->set('label', 'Beta')->call('attach')->assertHasErrors('pageId');   // duplicate

        $this->assertSame(['Página pagina-a', 'Beta'], $course->infoPages()->pluck('label')->all());

        $second = $course->infoPages()->where('page_id', $b->id)->first();
        $c->call('move', $second->id, 'up');
        $this->assertSame(['Beta', 'Página pagina-a'], $course->infoPages()->pluck('label')->all());

        $c->call('detach', $second->id);
        $this->assertSame(1, $course->infoPages()->count());
    }

    public function test_only_admins_reach_the_course_pages_screen(): void
    {
        $this->get('/admin/paginas-de-curso')->assertRedirect();
        $this->actingAs($this->student())->get('/admin/paginas-de-curso')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/paginas-de-curso')->assertOk()->assertSee('Páginas de curso');
    }
}
