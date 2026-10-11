<?php

namespace Tests\Feature;

use App\Services\MenuService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Services\CourseService;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Sección pública «Eventos»: cursos presenciales/híbridos con fechas próximas, agrupados por mes, con filtros y en el menú. */
class EventsPageTest extends TestCase
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

    private function event(string $title, array $dates, string $delivery = 'in_person', string $status = 'active'): Course
    {
        $course = $this->course()->replicate();
        $course->title = $title;
        $course->slug = 'evento-' . uniqid();
        $course->delivery = $delivery;
        $course->status = $status;
        $course->save();
        foreach ($dates as $i => $d) {
            $course->sessions()->create(array_merge([
                'starts_at' => now()->addDays($d['days'] ?? 20), 'ends_at' => now()->addDays($d['days'] ?? 20)->addHours(4), 'timezone' => 'America/Mexico_City',
                'venue_name' => 'Sede ' . ($i + 1), 'address' => 'Calle ' . ($i + 1), 'city' => $d['city'] ?? 'Guadalajara', 'status' => 'scheduled', 'sort' => $i + 1,
            ], array_diff_key($d, ['days' => 1, 'city' => 1])));
        }

        return $course;
    }

    public function test_lists_only_active_in_person_courses_with_upcoming_dates(): void
    {
        $this->event('Taller Alfa', [['days' => 20]]);
        $this->event('Curso Híbrido Beta', [['days' => 25]], 'hybrid');
        $this->event('Borrador Gamma', [['days' => 20]], 'in_person', 'draft');
        $this->event('Taller Pasado', [['days' => -10]]);
        $this->event('Taller Cancelado', [['days' => 20, 'status' => 'cancelled']]);
        $online = $this->course();
        $online->update(['delivery' => 'online']);

        $html = $this->get(route('events'))->assertOk()->getContent();

        $this->assertStringContainsString('Taller Alfa', $html);
        $this->assertStringContainsString('Curso Híbrido Beta', $html);
        $this->assertStringContainsString('Presencial + en línea', $html);
        foreach (['Borrador Gamma', 'Taller Pasado', 'Taller Cancelado', $online->title] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html, "$hidden must not be listed");
        }
    }

    public function test_cards_show_when_where_price_and_the_date_link(): void
    {
        $course = $this->event('Taller Delta', [['days' => 20, 'venue_name' => 'Casa Horizontia', 'city' => 'Puebla', 'capacity' => 3]]);
        $session = $course->sessions()->first();

        $html = $this->get(route('events'))->assertOk()->getContent();

        $this->assertStringContainsString('Casa Horizontia · Puebla', $html);
        $this->assertStringContainsString('Quedan 3 lugares', $html);
        $this->assertStringContainsString('?fecha=' . $session->id, $html);
        $this->assertStringContainsString('datetime="' . $session->starts_at->toIso8601String() . '"', $html);
    }

    public function test_multiple_dates_are_summarised_and_the_first_bookable_one_leads(): void
    {
        $course = $this->event('Taller Épsilon', [['days' => 10, 'capacity' => 1], ['days' => 30, 'city' => 'Monterrey'], ['days' => 45]]);
        $first = $course->sessions()->first();
        Enrollment::create(['student_id' => $this->student()->id, 'course_id' => $course->id, 'session_id' => $first->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);

        $html = $this->get(route('events'))->assertOk()->getContent();

        $this->assertStringContainsString('+ 2 fechas más', $html);
        $this->assertStringContainsString('?fecha=' . $course->sessions()->skip(1)->first()->id, $html, 'the first date is full: the card leads with the next one');
        $this->assertStringContainsString('Elegir fecha', $html);
    }

    public function test_an_event_whose_every_date_is_full_shows_sold_out(): void
    {
        $course = $this->event('Taller Zeta', [['days' => 10, 'capacity' => 1]]);
        Enrollment::create(['student_id' => $this->student()->id, 'course_id' => $course->id, 'session_id' => $course->sessions()->first()->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);

        $this->get(route('events'))->assertOk()->assertSee('Agotado')->assertSee('is-soldout', false);
    }

    public function test_filters_by_city_month_and_text(): void
    {
        $this->event('Taller Norte', [['days' => 20, 'city' => 'Monterrey']]);
        $this->event('Taller Sur', [['days' => 80, 'city' => 'Oaxaca']]);
        $month = now()->addDays(80)->setTimezone('America/Mexico_City')->format('Y-m');

        $this->get(route('events', ['ciudad' => 'Monterrey']))->assertOk()->assertSee('Taller Norte')->assertDontSee('Taller Sur');
        $this->get(route('events', ['mes' => $month]))->assertOk()->assertSee('Taller Sur')->assertDontSee('Taller Norte');
        $this->get(route('events', ['q' => 'Norte']))->assertOk()->assertSee('Taller Norte')->assertDontSee('Taller Sur');
        $this->get(route('events', ['ciudad' => 'Nowhere']))->assertOk()->assertSee('No encontramos eventos con esos filtros');
        $this->get(route('events', ['mes' => "<script>alert(1)</script>"]))->assertOk()->assertDontSee('<script>alert(1)', false);   // an invalid month is ignored
    }

    public function test_empty_state_is_friendly(): void
    {
        $this->get(route('events'))->assertOk()->assertSee('Todavía no hay eventos programados')->assertSee(route('courses.search-courses'), false);
    }

    public function test_events_can_be_placed_in_the_menu_and_appear_in_the_default_one(): void
    {
        $this->assertArrayHasKey('events', MenuService::platformLinks());
        $this->assertSame('Eventos', MenuService::platformLinks()['events']);
        // The seeded academy menus include it (header and footer).
        \Illuminate\Support\Facades\Artisan::call('academy:menus', ['--force' => true]);
        MenuService::flush();
        $this->assertStringContainsString(route('events'), $this->get('/')->getContent());
    }

    public function test_in_person_courses_stay_out_of_the_online_catalog_and_home(): void
    {
        $event = $this->event('Taller Solo Presencial', [['days' => 20]]);
        $hybrid = $this->event('Curso Híbrido Catálogo', [['days' => 20]], 'hybrid');

        $ids = (new CourseService())->getCourses(filters: ['status' => 'active', 'exclude_delivery' => ['in_person']], perPage: 100)->pluck('id');
        $this->assertFalse($ids->contains($event->id));
        $this->assertTrue($ids->contains($hybrid->id));

        // The home's course carousel never lists it (the dedicated "Próximos eventos" strip does).
        $home = $this->get('/')->assertOk()->getContent();
        $carousel = substr($home, (int) strpos($home, 'class="ht-carousel-track"'), (int) strpos($home, '.ht-carousel-track::-webkit-scrollbar') - (int) strpos($home, 'class="ht-carousel-track"'));
        $this->assertStringNotContainsString('Taller Solo Presencial', $carousel);
    }

    public function test_structured_data_describes_each_date_as_an_event_and_is_in_the_sitemap(): void
    {
        $this->event('Taller Eta', [['days' => 20], ['days' => 40]]);

        $html = $this->get(route('events'))->assertOk()->getContent();
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $events = collect($m[1])->map(fn ($j) => json_decode($j, true))->firstWhere('@graph')['@graph'] ?? [];

        $this->assertCount(2, $events);
        $this->assertSame('Event', $events[0]['@type']);
        $this->assertSame('https://schema.org/OfflineEventAttendanceMode', $events[0]['eventAttendanceMode']);

        $this->get(route('sitemap'))->assertOk()->assertSee(route('events'), false);
    }
}
