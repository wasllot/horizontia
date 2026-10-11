<?php

namespace Tests\Feature;

use App\Models\CoursePage;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Courses\Models\Course;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Editor de páginas: vista previa en vivo de los bloques dinámicos y los bloques de eventos y fechas. */
class PageBuilderLiveBlocksTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.timezone' => 'America/Mexico_City']);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function page(string $html = '<h1>Hola</h1>'): SitePage
    {
        return SitePage::create(['name' => 'Prueba', 'title' => 'Prueba', 'slug' => 'prueba-' . uniqid(), 'status' => 'published', 'settings' => [], 'gjs_html' => $html, 'gjs_css' => '']);
    }

    private function onsite(string $title = 'Taller Vista Previa', int $days = 20): Course
    {
        $c = $this->course()->replicate();
        $c->title = $title;
        $c->slug = 'vp-' . uniqid();
        $c->delivery = 'in_person';
        $c->status = 'active';
        $c->save();
        $c->sessions()->create(['starts_at' => now()->addDays($days), 'ends_at' => now()->addDays($days)->addHours(3), 'timezone' => 'America/Mexico_City',
            'venue_name' => 'Casa VP', 'address' => 'Calle 9', 'city' => 'Puebla', 'capacity' => 4, 'status' => 'scheduled', 'sort' => 1]);

        return $c;
    }

    private function preview(SitePage $page, array $query)
    {
        return $this->actingAs($this->admin())->getJson(route('admin.pages.block-preview', $page) . '?' . http_build_query($query));
    }

    // ------------------------------------------------------------ preview endpoint

    public function test_preview_is_admin_only(): void
    {
        $page = $this->page();

        $this->getJson(route('admin.pages.block-preview', $page) . '?type=courses')->assertUnauthorized();
        $this->actingAs($this->student())->getJson(route('admin.pages.block-preview', $page) . '?type=courses')->assertForbidden();
        $this->actingAs($this->tutor())->getJson(route('admin.pages.block-preview', $page) . '?type=courses')->assertForbidden();
    }

    public function test_preview_validates_its_input(): void
    {
        $page = $this->page();

        $this->preview($page, ['type' => 'hack'])->assertStatus(422);
        $this->preview($page, ['type' => 'courses', 'count' => 99])->assertStatus(422);
        $this->preview($page, [])->assertStatus(422);
    }

    public function test_courses_preview_follows_count_and_heading_and_escapes_them(): void
    {
        $page = $this->page();

        $r = $this->preview($page, ['type' => 'courses', 'count' => 1, 'heading' => '<script>alert(1)</script>Mis cursos'])->assertOk()->json();

        $this->assertStringContainsString('ht-courses', $r['html']);
        $this->assertSame(1, substr_count($r['html'], 'ht-course-card"'), 'count = 1 shows a single card');
        $this->assertStringNotContainsString('<script>', $r['html']);
        $this->assertStringContainsString('Mis cursos', $r['html']);
    }

    public function test_events_preview_lists_upcoming_events_and_honours_the_city_filter(): void
    {
        $page = $this->page();
        $this->onsite('Taller Puebla');

        $all = $this->preview($page, ['type' => 'events', 'count' => 3])->assertOk()->json('html');
        $this->assertStringContainsString('Taller Puebla', $all);
        $this->assertStringContainsString('Quedan 4 lugares', $all);

        $this->assertStringNotContainsString('Taller Puebla', $this->preview($page, ['type' => 'events', 'city' => 'Mérida'])->json('html'));
    }

    public function test_events_preview_has_a_friendly_empty_state(): void
    {
        $r = $this->preview($this->page(), ['type' => 'events'])->assertOk()->json();

        $this->assertStringContainsString('Aún no hay eventos programados', $r['html']);
    }

    public function test_course_dates_preview_uses_the_attached_course_or_a_sample(): void
    {
        $page = $this->page();
        $course = $this->onsite('Taller Fechas');

        $sample = $this->preview($page, ['type' => 'course-dates'])->assertOk()->json();
        $this->assertStringContainsString('Casa VP', $sample['html']);
        $this->assertStringContainsString('Vista de ejemplo', $sample['note']);

        CoursePage::create(['course_id' => $course->id, 'page_id' => $page->id, 'label' => 'Fechas', 'sort' => 1]);
        $attached = $this->preview($page, ['type' => 'course-dates'])->json();
        $this->assertStringContainsString('el curso de esta página', $attached['note']);
    }

    public function test_editor_config_exposes_the_preview_url_cities_and_event_styles(): void
    {
        $page = $this->page();
        $this->onsite('Taller Config');

        $html = $this->actingAs($this->admin())->get("/admin/paginas/{$page->id}/editor")->assertOk()->getContent();

        $this->assertStringContainsString('bloque-vista', $html);
        $this->assertStringContainsString('css/events.css', $html);
        $this->assertStringContainsString('Puebla', $html);
    }

    // ------------------------------------------------------------ public rendering

    public function test_events_block_renders_on_a_public_page(): void
    {
        $this->onsite('Taller Público');
        $page = $this->page('<div data-hz-block="events" data-count="3" data-heading="Próximas citas"></div>');

        $this->get('/' . $page->slug)->assertOk()->assertSee('Próximas citas')->assertSee('Taller Público')->assertSee('css/events.css', false);
    }

    public function test_course_dates_block_lists_the_dates_with_a_link_per_date(): void
    {
        $course = $this->onsite('Taller Con Fechas');
        $session = $course->sessions()->first();
        $page = $this->page('<div data-hz-block="course-dates" data-heading="Elige tu fecha"></div>');
        CoursePage::create(['course_id' => $course->id, 'page_id' => $page->id, 'label' => 'Fechas', 'sort' => 1]);

        $html = $this->get(route('courses.info-page', [$course->slug, $page->slug]))->assertOk()->getContent();

        $this->assertStringContainsString('Elige tu fecha', $html);
        $this->assertStringContainsString('Casa VP', $html);
        $this->assertStringContainsString('?fecha=' . $session->id, $html);

        // Outside a course context (or for an online course) the block prints nothing.
        $this->get('/' . $page->slug)->assertOk()->assertDontSee('Elige tu fecha');
    }

    public function test_course_dates_block_marks_a_full_date_as_unavailable(): void
    {
        $course = $this->onsite('Taller Lleno');
        $session = $course->sessions()->first();
        $session->update(['capacity' => 1]);
        \Modules\Courses\Models\Enrollment::create(['student_id' => $this->student()->id, 'course_id' => $course->id, 'session_id' => $session->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);
        $page = $this->page('<div data-hz-block="course-dates"></div>');
        CoursePage::create(['course_id' => $course->id, 'page_id' => $page->id, 'label' => 'Fechas', 'sort' => 1]);

        $html = $this->get(route('courses.info-page', [$course->slug, $page->slug]))->assertOk()->getContent();

        $this->assertStringContainsString('Agotada', $html);
        $this->assertDoesNotMatchRegularExpression('/href="[^"]*\?fecha=' . $session->id . '/', $html, 'a full date offers no link to buy it');
    }

    public function test_the_editor_script_ships_the_live_preview_and_new_blocks(): void
    {
        $js = file_get_contents(public_path('js/page-editor.js'));

        foreach (["'hz-events'", "'hz-course-dates'", 'blockPreview', 'data-hz-block', 'hzed-thumb', 'STARTERS'] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
        $this->assertStringNotContainsString('se muestran al publicar', file_get_contents(public_path('css/page-editor-canvas.css')), 'dynamic blocks are previewed, not replaced by a placeholder');
    }
}
