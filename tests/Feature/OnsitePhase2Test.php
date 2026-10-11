<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\CoursePage;
use App\Models\SitePage;
use App\Services\PageTokens;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Course\CourseDetails;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Cursos presenciales, fase 2: elegir fecha en la página del curso, páginas informativas con fechas, portada y catálogo. */
class OnsitePhase2Test extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.timezone' => 'America/Mexico_City', 'academy.private_mode' => true]);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    /** @param array<int, array<string, mixed>> $dates */
    private function onsite(string $title = 'Taller Fase Dos', array $dates = [['days' => 20], ['days' => 40, 'city' => 'Monterrey']], string $delivery = 'in_person'): Course
    {
        $c = $this->course2()->replicate();
        $c->title = $title;
        $c->slug = 'f2-' . uniqid();
        $c->delivery = $delivery;
        $c->status = 'active';
        $c->save();
        $c->pricing()->create(['price' => 1000, 'discount' => 0, 'final_price' => 1000]);
        foreach ($dates as $i => $d) {
            $c->sessions()->create(array_merge([
                'starts_at' => now()->addDays($d['days'] ?? 20), 'ends_at' => now()->addDays($d['days'] ?? 20)->addHours(4), 'timezone' => 'America/Mexico_City',
                'venue_name' => 'Sede ' . ($i + 1), 'address' => 'Calle ' . ($i + 1), 'city' => $d['city'] ?? 'Guadalajara', 'status' => 'scheduled', 'sort' => $i + 1,
            ], array_diff_key($d, ['days' => 1, 'city' => 1])));
        }

        return $c;
    }

    private function details(Course $course, array $query = [])
    {
        return Livewire::withQueryParams(['slug' => $course->slug] + $query)->actingAs($this->student())->test(CourseDetails::class);
    }

    private function cartOf(Course $course)
    {
        return CartItem::where('user_id', $this->student()->id)->where('cartable_id', $course->id)->get();
    }

    private function emptyCart(): void
    {
        CartItem::where('user_id', $this->student()->id)->delete();
        \Illuminate\Support\Facades\Cache::forget('cart_items_' . $this->student()->id);
    }

    // ------------------------------------------------------------ selector on the course page

    public function test_the_course_page_lists_the_dates_and_preselects_the_one_in_the_url(): void
    {
        $course = $this->onsite();
        $second = $course->sessions()->skip(1)->first();

        $html = $this->actingAs($this->student())->get(route('courses.course-detail', ['slug' => $course->slug, 'fecha' => $second->id]))->assertOk()->getContent();

        $this->assertStringContainsString('Fechas y sede', $html);
        $this->assertStringContainsString('Sede 2', $html);
        $this->assertStringContainsString('aria-checked="true"', $html);
        $this->assertStringContainsString('Tu fecha', $html);
        $this->assertStringContainsString('Ver en el mapa', $html);
        $this->assertStringContainsString('"@type":"Event"', $html, 'Event structured data on the course page');
    }

    public function test_without_a_choice_the_student_is_asked_for_a_date_and_cannot_buy(): void
    {
        $course = $this->onsite();
        $this->emptyCart();

        $c = $this->details($course);
        $this->assertNull($c->get('sessionId'));
        $c->call('buyNow')->assertNoRedirect();
        $c->call('addToCart');

        $this->assertCount(0, $this->cartOf($course), 'no date, nothing in the cart');
        $this->actingAs($this->student())->get(route('courses.course-detail', ['slug' => $course->slug]))->assertSee('Elige una fecha para continuar')->assertSee('Elegir fecha');
    }

    public function test_a_single_available_date_is_selected_automatically(): void
    {
        $course = $this->onsite('Taller Una Fecha', [['days' => 15]]);

        $this->details($course)->assertSet('sessionId', $course->sessions()->first()->id);
    }

    public function test_choosing_a_date_puts_it_in_the_cart_and_a_new_choice_replaces_it(): void
    {
        $course = $this->onsite();
        $this->emptyCart();
        [$a, $b] = $course->sessions->all();

        $c = $this->details($course)->call('selectSession', $a->id)->call('addToCart');
        $this->assertCount(1, $this->cartOf($course));
        $this->assertSame($a->id, $this->cartOf($course)->first()->options['session_id']);
        $this->assertStringContainsString('Guadalajara', $this->cartOf($course)->first()->options['session_label']);

        $this->details($course)->call('selectSession', $b->id)->call('addToCart');
        $this->assertCount(1, $this->cartOf($course));
        $this->assertSame($b->id, $this->cartOf($course)->first()->options['session_id'], 'another date replaces the previous one');
    }

    public function test_buy_now_goes_to_checkout_with_the_chosen_date(): void
    {
        $course = $this->onsite();
        $this->emptyCart();

        $this->details($course, ['fecha' => $course->sessions()->first()->id])->call('buyNow')->assertRedirect(route('checkout'));

        $this->assertSame($course->sessions()->first()->id, $this->cartOf($course)->first()->options['session_id']);
    }

    public function test_a_tampered_or_unavailable_date_is_never_accepted(): void
    {
        $course = $this->onsite();
        $other = $this->onsite('Otro curso');
        $foreign = $other->sessions()->first();
        $full = $course->sessions()->first();
        $full->update(['capacity' => 1]);
        Enrollment::create(['student_id' => $this->demoStudent()->id, 'course_id' => $course->id, 'session_id' => $full->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);
        $closed = $course->sessions()->skip(1)->first();
        $closed->update(['sales_close_at' => now()->subHour()]);
        $this->emptyCart();

        foreach ([$foreign->id, $full->id, $closed->id, 999999] as $bad) {
            $c = $this->details($course)->call('selectSession', $bad);
            $this->assertNotSame($bad, $c->get('sessionId'));
        }
        // …nor one forced through the URL / component state.
        $this->details($course, ['fecha' => $foreign->id])->assertSet('sessionId', null)->call('buyNow')->assertNoRedirect();
        $this->details($course, ['fecha' => $full->id])->assertSet('sessionId', null);
        $this->assertCount(0, $this->cartOf($course));
    }

    public function test_a_sold_out_date_is_shown_but_cannot_be_chosen(): void
    {
        $course = $this->onsite('Taller Lleno', [['days' => 10, 'capacity' => 1], ['days' => 30]]);
        $full = $course->sessions()->first();
        Enrollment::create(['student_id' => $this->demoStudent()->id, 'course_id' => $course->id, 'session_id' => $full->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);

        $this->actingAs($this->student())->get(route('courses.course-detail', ['slug' => $course->slug]))->assertOk()->assertSee('Agotada');
    }

    public function test_online_courses_are_not_affected(): void
    {
        $online = $this->course2();
        $this->emptyCart();

        $this->details($online)->assertSet('sessionId', null)->call('addToCart');
        $this->assertCount(1, $this->cartOf($online));
        $this->actingAs($this->student())->get(route('courses.course-detail', ['slug' => $online->slug]))->assertOk()->assertDontSee('Fechas y sede');
    }

    // ------------------------------------------------------------ info pages

    private function infoPage(Course $course, string $html): SitePage
    {
        $page = SitePage::create(['name' => 'Info', 'title' => 'Info', 'slug' => 'info-' . uniqid(), 'status' => 'published', 'settings' => [], 'gjs_html' => $html, 'gjs_css' => '']);
        CoursePage::create(['course_id' => $course->id, 'page_id' => $page->id, 'label' => 'Info', 'sort' => 1]);

        return $page;
    }

    public function test_course_tokens_resolve_inside_the_course_and_vanish_elsewhere(): void
    {
        $course = $this->onsite('Taller Tokens', [['days' => 20, 'capacity' => 3, 'city' => 'Puebla'], ['days' => 40, 'city' => 'Oaxaca']]);
        $page = $this->infoPage($course, '<p>[[curso_proxima_fecha]] en [[curso_ciudades]] · [[curso_sedes]] · [[curso_lugares]]</p>');

        $in = $this->get(route('courses.info-page', [$course->slug, $page->slug]))->assertOk()->getContent();
        $this->assertStringContainsString('Puebla, Oaxaca', $in);
        $this->assertStringContainsString('Sede 1, Sede 2', $in);
        $this->assertStringContainsString('3 lugares', $in);
        $this->assertStringNotContainsString('[[curso_', $in);

        $out = $this->get('/' . $page->slug)->assertOk()->getContent();
        $this->assertStringNotContainsString('[[curso_', $out, 'a visitor never sees a raw token');
        $this->assertStringNotContainsString('Puebla', $out);

        $this->assertSame('', PageTokens::courseValues($this->course2())['[[curso_ciudades]]'], 'online courses have no dates');
    }

    public function test_sub_navigation_offers_the_next_date_and_the_course_info_block_lists_the_dates(): void
    {
        $course = $this->onsite('Taller Subnav');
        $next = $course->sessions()->first();
        $page = $this->infoPage($course, '<div data-hz-block="course-info"></div>');

        $html = $this->get(route('courses.info-page', [$course->slug, $page->slug]))->assertOk()->getContent();

        $this->assertStringContainsString('Elegir fecha y comprar', $html);
        $this->assertStringContainsString('?fecha=' . $next->id . '#dates', $html);
        $this->assertStringContainsString('Próximas fechas', $html);
        $this->assertStringContainsString('Monterrey', $html);
        $this->assertStringContainsString('"@type":"Event"', $html, 'Event structured data on the info page');
    }

    public function test_info_pages_of_online_courses_keep_the_usual_wording(): void
    {
        $online = $this->course2();
        $page = $this->infoPage($online, '<div data-hz-block="course-info"></div>');

        $this->get(route('courses.info-page', [$online->slug, $page->slug]))->assertOk()->assertSee('Ver curso y comprar')->assertDontSee('Elegir fecha y comprar');
    }

    // ------------------------------------------------------------ home and catalog

    public function test_the_home_page_shows_upcoming_events_only_when_there_are_some(): void
    {
        $this->get('/')->assertOk()->assertDontSee('home-events-title', false);

        $this->onsite('Taller Portada');

        $this->get('/')->assertOk()->assertSee('home-events-title', false)->assertSee('Taller Portada')->assertSee(route('events'), false);
    }
}
