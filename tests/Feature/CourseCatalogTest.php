<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoAcademySeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Courses\Jobs\ClearCourseFundsJob;
use Modules\Courses\Livewire\Pages\Search\SearchCourses;
use Modules\Courses\Models\Category;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * The public catalog (/cursos): first-paint rendering, category chips, applied-filter chips, sorting,
 * free/paid and level filters, "load more", empty state and "continue where you left off".
 */
class CourseCatalogTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    /** Demo categories that hold the 4 published demo courses. */
    private array $demoCats = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true]);
        Storage::fake('public');
        Bus::fake([ClearCourseFundsJob::class]);
        (new DemoAcademySeeder())->run();
        $this->demoCats = Category::whereIn('slug', ['negocios-marketing', 'tecnologia-productividad', 'idiomas'])->pluck('id')->all();
    }

    protected function tearDown(): void
    {
        foreach (Curriculum::where('type', 'scorm')->pluck('id') as $id) {
            File::deleteDirectory(public_path('storage/scorm/' . $id));
        }
        parent::tearDown();
    }

    private function catalog(array $set = [])
    {
        $c = Livewire::test(SearchCourses::class)->set('searchCategories', $this->demoCats);
        foreach ($set as $key => $value) {
            $c->set($key, $value);
        }

        return $c;
    }

    public function test_courses_are_in_the_first_html_response_for_crawlers_and_slow_connections(): void
    {
        $html = $this->get('/cursos?searchCategories[0]=' . Category::where('slug', 'negocios-marketing')->value('id'))->assertOk()->getContent();

        $this->assertStringContainsString('Marketing Digital desde Cero', $html);
        $this->assertStringContainsString('application/ld+json', $html);          // ItemList for search engines
        $this->assertStringNotContainsString('cr-card-skeleton', $html);           // no skeleton placeholders
        $this->assertStringNotContainsString('placehold.co', $html);               // no external image hotlinks
        $this->assertStringNotContainsString('nouislider', $html);                 // heavy legacy widgets are gone
    }

    public function test_only_published_courses_are_listed(): void
    {
        $this->catalog()->assertSee('Inglés Conversacional A1')->assertDontSee('Liderazgo y Comunicación (borrador)');
    }

    public function test_category_chips_toggle_filter_and_show_a_removable_applied_chip(): void
    {
        $idiomas = Category::where('slug', 'idiomas')->value('id');

        $c = Livewire::test(SearchCourses::class)
            ->call('toggleCategory', $idiomas)
            ->assertSet('searchCategories', [$idiomas])
            ->assertSee('Inglés Conversacional A1')->assertDontSee('Marketing Digital desde Cero')
            ->assertSee('con tus filtros')->assertSee('Limpiar todo');

        $this->assertSame([['type' => 'category', 'value' => $idiomas, 'label' => 'Idiomas']], $c->instance()->appliedFilters());

        $c->call('removeFilter', 'category', $idiomas)->assertSet('searchCategories', [])->assertSee('disponibles');
    }

    public function test_facet_counts_survive_livewire_round_trips(): void
    {
        // Regression: facets stored as public properties were rehydrated without their *_count attributes.
        $c = Livewire::test(SearchCourses::class)->call('toggleCategory', Category::where('slug', 'idiomas')->value('id'));

        $c->assertSeeHtml('<em>1</em>');   // Idiomas chip still shows its count after the update
    }

    public function test_sorting_by_price_rating_popularity_and_date_and_unknown_values_fall_back(): void
    {
        $demo = ['Excel Esencial (curso gratuito)', 'Seguridad en el Trabajo (SCORM)', 'Inglés Conversacional A1', 'Marketing Digital desde Cero'];
        // Titles of the 4 demo courses in the order the catalog returns them for a given sort.
        $order = fn (string $sort) => collect($this->catalog(['filters.sort' => $sort])->instance()->render()->getData()['courses']->items())
            ->map(fn ($c) => html_entity_decode($c->title))->filter(fn ($t) => in_array($t, $demo, true))->values()->all();

        // final prices: Excel 0 < Seguridad 799 < Inglés 809.10 < Marketing 1199.20
        $this->assertSame($demo, $order('price_asc'));
        $this->assertSame(array_reverse($demo), $order('price_desc'));
        $this->assertSame('Marketing Digital desde Cero', $order('rating')[0], 'the only reviewed course comes first');
        $this->assertCount(4, $order('popular'));
        $this->assertCount(4, $order('asc'));
        $this->assertCount(4, $order('desc'));

        // Unknown sort from a tampered URL does not break the page.
        $this->get('/cursos?filters[sort]=drop%20table')->assertOk();
    }

    public function test_free_paid_and_level_filters(): void
    {
        $this->catalog(['filters.pricing_type' => 'free'])->assertSee('Excel Esencial (curso gratuito)')->assertDontSee('Marketing Digital desde Cero')->assertSee('Gratis');
        $this->catalog(['filters.pricing_type' => 'paid'])->assertSee('Marketing Digital desde Cero')->assertDontSee('Excel Esencial (curso gratuito)');
        $this->catalog(['filters.levels' => ['intermediate']])->assertSee('Seguridad en el Trabajo (SCORM)')->assertDontSee('Marketing Digital desde Cero');
    }

    public function test_keyword_search_and_helpful_empty_state(): void
    {
        $this->catalog(['filters.keyword' => 'Excel'])->assertSee('Excel Esencial (curso gratuito)')->assertDontSee('Inglés Conversacional A1');

        $this->catalog(['filters.keyword' => 'zzz-no-existe'])
            ->assertSee('No encontramos cursos con esos filtros')->assertSee('Quitar todos los filtros')
            ->call('resetFilters')->assertSee('Inglés Conversacional A1');
    }

    public function test_load_more_adds_a_page_and_filters_reset_it(): void
    {
        $c = Livewire::test(SearchCourses::class)->assertSet('perPage', SearchCourses::PER_PAGE)
            ->call('loadMore')->assertSet('perPage', SearchCourses::PER_PAGE * 2)
            ->set('filters.keyword', 'a')->assertSet('perPage', SearchCourses::PER_PAGE);

        for ($i = 0; $i < 20; $i++) {
            $c->call('loadMore');
        }
        $c->assertSet('perPage', 120);   // capped
    }

    public function test_filters_live_in_the_url_so_results_can_be_shared(): void
    {
        $idiomas = Category::where('slug', 'idiomas')->value('id');

        $this->get("/cursos?searchCategories[0]={$idiomas}&filters[pricing_type]=paid")->assertOk()
            ->assertSee('Inglés Conversacional A1')->assertDontSee('Excel Esencial (curso gratuito)')->assertDontSee('Marketing Digital desde Cero');
    }

    public function test_prices_are_shown_with_the_discount_and_free_courses_say_gratis(): void
    {
        $this->catalog()->assertSee('Gratis')->assertSee('-20%')->assertSee('-10%');
    }

    public function test_continue_learning_strip_only_for_enrolled_students_on_the_unfiltered_catalog(): void
    {
        $student = User::where('email', 'alumno.pagado@horizontia.test')->firstOrFail();

        $this->get('/cursos')->assertDontSee('Continúa donde lo dejaste');

        Livewire::actingAs($student)->test(SearchCourses::class)
            ->assertSee('Continúa donde lo dejaste')->assertSee('Marketing Digital desde Cero')
            ->set('filters.keyword', 'Marketing')->assertDontSee('Continúa donde lo dejaste');

        $nobody = User::where('email', 'alumno.nuevo@horizontia.test')->firstOrFail();
        Livewire::actingAs($nobody)->test(SearchCourses::class)->assertDontSee('Continúa donde lo dejaste');
    }

    public function test_legacy_wire_init_call_is_harmless(): void
    {
        Livewire::test(SearchCourses::class)->call('loadData')->assertSet('isLoading', false)->assertSee('Cursos en línea');
    }

    public function test_page_has_seo_title_and_description(): void
    {
        $this->get('/cursos')->assertOk()->assertSee('Catálogo de cursos', false)->assertSee('Explora los cursos en línea de la academia', false);
    }
}
