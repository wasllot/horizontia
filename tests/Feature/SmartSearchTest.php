<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\Reports\Reports;
use App\Models\SearchLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Search\SearchCourses;
use Modules\Courses\Models\Category;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Services\CourseRecommendationService;
use Modules\Courses\Services\CourseSearchService;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Búsqueda por relevancia (acentos, plurales, errores de tecleo), «¿quisiste decir?», registro de búsquedas y recomendaciones. */
class SmartSearchTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true]);
        Cache::forget('course_search_corpus');
        // Solo cuentan los cursos de esta prueba.
        Course::query()->update(['status' => Course::STATUSES['inactive']]);
        Cache::forget('course_search_corpus');
    }

    protected function tearDown(): void
    {
        Cache::forget('course_search_corpus');
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function course(string $title, array $over = []): Course
    {
        $c = $this->course2()->replicate();
        $c->title = $title;
        $c->slug = 'ss-' . uniqid();
        $c->status = 'active';
        $c->delivery = 'online';
        foreach ($over as $k => $v) {
            $c->{$k} = $v;
        }
        $c->save();
        $c->pricing()->create(['price' => 100, 'discount' => 0, 'final_price' => 100]);
        Cache::forget('course_search_corpus');

        return $c;
    }

    private function search(string $q): array
    {
        return app(CourseSearchService::class)->rank($q);
    }

    public function test_search_ignores_accents_case_and_plurals(): void
    {
        $c = $this->course('Técnicas de Negociación');

        $this->assertSame([$c->id], $this->search('negociacion'));
        $this->assertSame([$c->id], $this->search('TECNICAS'));
        $this->assertSame([$c->id], $this->search('tecnica'));     // singular de «técnicas»
        $this->assertSame([], $this->search('de la el'), 'only stop words: nothing to search');
    }

    public function test_the_title_beats_the_description_and_a_phrase_beats_loose_words(): void
    {
        $onlyBody = $this->course('Taller creativo', ['description' => '<p>Aprende ventas con ejemplos reales de ventas.</p>']);
        $title = $this->course('Ventas B2B');
        $both = $this->course('Cierre de ventas avanzadas', ['description' => 'ventas']);

        $ids = $this->search('ventas');

        $this->assertSame($onlyBody->id, end($ids), 'a mention in the description ranks last');
        $this->assertEqualsCanonicalizing([$title->id, $both->id], array_slice($ids, 0, 2));

        $phrase = $this->course('Marketing digital para pymes');
        $loose = $this->course('Digital: guía de redes y marketing');
        $this->assertSame($phrase->id, $this->search('marketing digital')[0], 'the exact phrase in the title wins');
    }

    public function test_small_typos_still_find_the_course_and_far_words_do_not(): void
    {
        $c = $this->course('Liderazgo y comunicación');

        $this->assertSame([$c->id], $this->search('liderazgo'));
        $this->assertSame([$c->id], $this->search('lidarazgo'), 'one letter wrong');
        $this->assertSame([$c->id], $this->search('comunicacion'));
        $this->assertSame([], $this->search('astronomia'));
    }

    public function test_a_category_name_is_searchable(): void
    {
        $cat = Category::firstOrCreate(['slug' => 'finanzas-test'], ['name' => 'Finanzas personales']);
        $c = $this->course('Mi primer presupuesto', ['category_id' => $cat->id]);

        $this->assertContains($c->id, $this->search('finanzas'));
    }

    public function test_suggests_the_right_spelling_only_when_it_would_find_something(): void
    {
        $this->course('Excel avanzado para analistas');
        $svc = app(CourseSearchService::class);

        $this->assertSame('excel avanzado', $svc->suggest('exel avansado'));
        $this->assertNull($svc->suggest('excel'), 'already correct');
        $this->assertNull($svc->suggest('zzzzqqqq'), 'nothing close');
    }

    public function test_the_catalog_uses_the_ranking_and_shows_the_suggestion_with_a_one_click_fix(): void
    {
        $a = $this->course('Ventas consultivas');
        $b = $this->course('Taller de oratoria', ['description' => 'incluye ventas']);

        $page = Livewire::test(SearchCourses::class)->set('filters.keyword', 'ventas');
        $page->assertSet('filters.sort', 'relevance')->assertSee('Ventas consultivas')->assertSeeInOrder(['Ventas consultivas', 'Taller de oratoria']);

        $page->set('filters.keyword', 'bentaz')->assertSee('data-testid="search-suggestion"', false)->assertSee('Quisiste decir');
        $page->call('useSuggestion', 'venta')->assertSet('filters.keyword', 'venta')->assertSee('Ventas consultivas');

        $page->set('filters.keyword', '')->assertSet('filters.sort', 'desc');
    }

    public function test_searches_are_logged_once_while_typing_and_feed_the_report(): void
    {
        $this->course('Ventas consultivas');
        $page = Livewire::test(SearchCourses::class);

        foreach (['ven', 'vent', 'ventas'] as $typed) {
            $page->set('filters.keyword', $typed);
        }
        $this->assertSame(1, SearchLog::count(), 'typing a phrase is one search');
        $this->assertSame(['ventas', 1], [SearchLog::first()->query, (int) SearchLog::first()->results > 0 ? 1 : 0]);

        SearchLog::query()->delete();
        Cache::flush();
        foreach (['astronomía', 'astronomía', 'drones'] as $q) {
            SearchLog::create(['query' => $q, 'results' => 0]);
        }

        $r = (new \App\Services\BusinessReportService('30d'))->searches();
        $this->assertSame(['astronomía', 2], [$r['empty'][0]->query, $r['empty'][0]->searches]);
        Livewire::actingAs($this->admin())->test(Reports::class)->call('setTab', 'busquedas')->assertSee('Buscan y no encuentran')->assertSee('astronomía');
    }

    public function test_short_queries_are_not_logged(): void
    {
        Livewire::test(SearchCourses::class)->set('filters.keyword', 'ab');

        $this->assertSame(0, SearchLog::count());
    }

    // ------------------------------------------------------------ recomendaciones

    private function enroll(Course $c, $user): void
    {
        Enrollment::updateOrCreate(['student_id' => $user->id, 'course_id' => $c->id], ['tutor_id' => $c->instructor_id, 'status' => 'active']);
    }

    public function test_others_who_took_this_course_also_took_these_and_never_what_you_own(): void
    {
        [$base, $b, $c, $owned, $unrelated] = [$this->course('Base'), $this->course('Complemento popular'), $this->course('Otro complemento'), $this->course('Ya lo tengo'), $this->course('Sin relación')];
        Enrollment::whereIn('course_id', [$base->id, $b->id, $c->id, $owned->id, $unrelated->id])->delete();
        foreach ([$this->demoStudent(), $this->tutor()] as $u) {
            $this->enroll($base, $u);
            $this->enroll($b, $u);
        }
        $this->enroll($base, $this->admin());
        $this->enroll($c, $this->admin());
        $this->enroll($owned, $this->demoStudent());
        $this->enroll($owned, $this->student());   // quien mira ya lo tiene

        $recs = app(CourseRecommendationService::class)->related($base, $this->student(), 4);

        $ids = $recs->pluck('id')->all();
        $this->assertSame($b->id, $ids[0], 'two shared students beats one');
        $this->assertContains($c->id, $ids);
        $this->assertNotContains($base->id, $ids);
        $this->assertNotContains($owned->id, $ids, 'what the viewer already owns is never suggested');
        $this->assertStringContainsString('también tomaron este', $recs[0]->reason);
    }

    public function test_the_student_gets_recommendations_by_category_and_they_exclude_owned_and_unpublished(): void
    {
        $cat = Category::firstOrCreate(['slug' => 'rec-test'], ['name' => 'Recomendación']);
        [$mine, $same, $draft] = [$this->course('Mío', ['category_id' => $cat->id]), $this->course('Misma categoría', ['category_id' => $cat->id]), $this->course('Borrador', ['category_id' => $cat->id])];
        $draft->update(['status' => 'draft']);
        Enrollment::where('student_id', $this->student()->id)->delete();
        $this->enroll($mine, $this->student());

        $recs = app(CourseRecommendationService::class)->forStudent($this->student(), 6);

        $this->assertContains($same->id, $recs->pluck('id')->all());
        $this->assertNotContains($mine->id, $recs->pluck('id')->all());
        $this->assertNotContains($draft->id, $recs->pluck('id')->all());
        $this->assertStringContainsString('Porque estudias «Mío»', $recs->firstWhere('id', $same->id)->reason);
    }

    public function test_recommendations_appear_on_my_courses_and_the_course_page(): void
    {
        $cat = Category::firstOrCreate(['slug' => 'rec-test'], ['name' => 'Recomendación']);
        $mine = $this->course('Curso mío', ['category_id' => $cat->id]);
        $other = $this->course('Sugerido para ti', ['category_id' => $cat->id]);
        Enrollment::where('student_id', $this->student()->id)->delete();
        $this->enroll($mine, $this->student());

        $this->actingAs($this->student())->get(route('courses.course-list'))->assertOk()->assertSee('Recomendados para ti')->assertSee('Sugerido para ti');
        $this->get(route('courses.course-detail', ['slug' => $mine->slug]))->assertOk()->assertSee('Otros alumnos también tomaron')->assertSee('Sugerido para ti');
    }
}
