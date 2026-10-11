<?php

namespace Tests\Feature;

use App\Models\PageBanner;
use App\Services\ConfigTransfer\ConfigTransfer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\Course;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Exportar/importar: banners de páginas fijas, cupones y rutas de aprendizaje, y que «Nosotros» (vista propia) no viaje como página. */
class ConfigTransferAcademyTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    private ConfigTransfer $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        $this->svc = new ConfigTransfer();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function viaFile(array $sections, array $options = []): array
    {
        return $this->svc->parse($this->svc->toJson($this->svc->export($sections, $options)));
    }

    public function test_the_new_sections_are_offered_with_safe_defaults(): void
    {
        $sections = ConfigTransfer::sections();

        $this->assertTrue($sections['banners']['default']);
        $this->assertFalse($sections['coupons']['default'], 'coupons are opt-in');
        $this->assertFalse($sections['paths']['default'], 'paths depend on courses: opt-in');
        $this->assertFalse($sections['banners']['sensitive'] || $sections['coupons']['sensitive'] || $sections['paths']['sensitive']);
    }

    public function test_system_page_banners_travel_and_are_cleaned_on_import(): void
    {
        PageBanner::create(['page_key' => 'system:nosotros', 'title' => 'Quiénes somos', 'subtitle' => 'Sub', 'overlay' => 40, 'align' => 'center']);
        PageBanner::create(['page_key' => 'page:999', 'title' => 'De una página del editor']);
        $payload = $this->viaFile(['banners']);

        $this->assertSame(['nosotros'], array_column($payload['data']['banners'], 'page'), 'only fixed pages; editor pages carry theirs inside «Páginas»');

        PageBanner::where('page_key', 'system:nosotros')->delete();
        $payload['data']['banners'][] = ['page' => 'inexistente', 'title' => 'x'];
        $payload['data']['banners'][] = ['page' => 'contacto', 'title' => '<b>Hola</b>', 'image' => 'https://evil.test/x.jpg', 'overlay' => 500, 'align' => 'right'];
        $payload['meta']['checksum'] = hash('sha256', json_encode($payload['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

        $report = $this->svc->import($payload, ['banners']);

        $this->assertSame(2, $report['sections']['banners']['created']);
        $this->assertSame(1, $report['sections']['banners']['skipped']);
        $this->assertSame('center', PageBanner::where('page_key', 'system:nosotros')->value('align'));
        $contacto = PageBanner::where('page_key', 'system:contacto')->first();
        $this->assertSame('Hola', $contacto->title);
        $this->assertNull($contacto->image, 'never an outside URL');
        $this->assertSame(85, (int) $contacto->overlay);
        $this->assertSame('left', $contacto->align);
    }

    public function test_the_about_page_has_its_own_view_so_it_is_not_exported_or_imported_as_a_page(): void
    {
        DB::table('pagebuilder__pages')->updateOrInsert(['slug' => 'nosotros'], ['name' => 'Nosotros', 'title' => 'Nosotros', 'status' => 'published', 'settings' => '{}', 'created_at' => now(), 'updated_at' => now()]);

        $slugs = array_column($this->svc->export(['pages'])['data']['pages'], 'slug');
        $this->assertNotContains('nosotros', $slugs);

        $payload = $this->svc->export(['pages']);
        $payload['data']['pages'][] = ['slug' => 'nosotros', 'name' => 'Otra', 'title' => 'Otra', 'status' => 'published', 'gjs_html' => '<p>x</p>'];
        $report = $this->svc->import($payload, ['pages'], ConfigTransfer::MODE_OVERWRITE);
        $this->assertNotSame('Otra', DB::table('pagebuilder__pages')->where('slug', 'nosotros')->value('name'));
        $this->assertGreaterThanOrEqual(1, $report['sections']['pages']['skipped']);
    }

    public function test_coupons_round_trip_by_course_slug_without_usage_data(): void
    {
        $course = Course::first();
        DB::table('coupons')->insert([
            ['code' => 'BIENVENIDA10', 'type' => 'percent', 'value' => 10, 'per_user_limit' => 1, 'used_count' => 7, 'course_ids' => null, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'SOLOUNO', 'type' => 'fixed', 'value' => 100, 'per_user_limit' => 1, 'used_count' => 0, 'course_ids' => json_encode([$course->id]), 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $payload = $this->viaFile(['coupons']);
        $json = $this->svc->toJson($payload);

        $this->assertStringNotContainsString('used_count', $json);
        $this->assertSame([$course->slug], collect($payload['data']['coupons'])->firstWhere('code', 'SOLOUNO')['courses']);
        $this->assertNull(collect($payload['data']['coupons'])->firstWhere('code', 'BIENVENIDA10')['courses']);

        DB::table('coupons')->whereIn('code', ['BIENVENIDA10', 'SOLOUNO'])->delete();
        $report = $this->svc->import($payload, ['coupons']);

        $this->assertSame(2, $report['sections']['coupons']['created']);
        $this->assertSame(0, (int) DB::table('coupons')->where('code', 'BIENVENIDA10')->value('used_count'));
        $this->assertSame([$course->id], json_decode(DB::table('coupons')->where('code', 'SOLOUNO')->value('course_ids'), true));

        // «solo añadir» no toca lo que existe; «reemplazar» actualiza pero conserva los usos
        DB::table('coupons')->where('code', 'BIENVENIDA10')->update(['used_count' => 4]);
        $payload['data']['coupons'][0]['value'] = 25.0;
        $payload['meta']['checksum'] = hash('sha256', json_encode($payload['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
        $this->svc->import($payload, ['coupons'], ConfigTransfer::MODE_SKIP);
        $this->assertEquals(10, DB::table('coupons')->where('code', 'BIENVENIDA10')->value('value'));
        $this->svc->import($payload, ['coupons'], ConfigTransfer::MODE_OVERWRITE);
        $this->assertEquals(25, DB::table('coupons')->where('code', 'BIENVENIDA10')->value('value'));
        $this->assertSame(4, (int) DB::table('coupons')->where('code', 'BIENVENIDA10')->value('used_count'));
    }

    public function test_a_coupon_limited_to_a_missing_course_is_not_imported_and_bad_ones_are_refused(): void
    {
        $payload = ['meta' => ['format' => ConfigTransfer::FORMAT, 'version' => 1, 'checksum' => ''], 'data' => ['coupons' => [
            ['code' => 'FANTASMA', 'type' => 'percent', 'value' => 20, 'courses' => ['curso-que-no-existe']],
            ['code' => 'ab', 'type' => 'percent', 'value' => 20],
            ['code' => 'DEMASIADO', 'type' => 'percent', 'value' => 150],
            ['code' => 'RARO', 'type' => 'otro', 'value' => 5],
            ['code' => 'bueno-15', 'type' => 'percent', 'value' => 15],
        ]]];

        $report = $this->svc->import($payload, ['coupons']);

        $this->assertSame(1, $report['sections']['coupons']['created']);
        $this->assertSame(4, $report['sections']['coupons']['skipped']);
        $this->assertTrue(DB::table('coupons')->where('code', 'BUENO-15')->exists());
        $this->assertFalse(DB::table('coupons')->where('code', 'FANTASMA')->exists(), 'a restricted coupon must never become a coupon for every course');
        $this->assertStringContainsString('FANTASMA', implode(' ', $report['sections']['coupons']['notes']));
    }

    public function test_learning_paths_keep_their_course_order_and_stay_draft_when_a_course_is_missing(): void
    {
        $courses = Course::orderBy('id')->take(2)->get();
        $this->assertCount(2, $courses);
        $pid = DB::table('learning_paths')->insertGetId(['title' => 'Ruta calma', 'slug' => 'ruta-calma', 'status' => 'active', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('learning_path_courses')->insert([['learning_path_id' => $pid, 'course_id' => $courses[1]->id, 'sort' => 0], ['learning_path_id' => $pid, 'course_id' => $courses[0]->id, 'sort' => 1]]);

        $payload = $this->viaFile(['paths']);
        $this->assertSame([$courses[1]->slug, $courses[0]->slug], $payload['data']['paths'][0]['courses']);

        DB::table('learning_paths')->where('id', $pid)->delete();
        $this->svc->import($payload, ['paths']);
        $new = DB::table('learning_paths')->where('slug', 'ruta-calma')->first();
        $this->assertSame('active', $new->status);
        $this->assertSame([$courses[1]->id, $courses[0]->id], DB::table('learning_path_courses')->where('learning_path_id', $new->id)->orderBy('sort')->pluck('course_id')->map(fn ($v) => (int) $v)->all());

        DB::table('learning_paths')->where('id', $new->id)->delete();
        $payload['data']['paths'][0]['courses'][] = 'curso-que-no-existe';
        $report = $this->svc->import($payload, ['paths']);
        $this->assertSame('draft', DB::table('learning_paths')->where('slug', 'ruta-calma')->value('status'));
        $this->assertNotEmpty($report['sections']['paths']['notes']);
    }

    public function test_the_import_summary_and_warnings_cover_the_new_sections(): void
    {
        DB::table('coupons')->insert(['code' => 'RESUMEN', 'type' => 'fixed', 'value' => 50, 'per_user_limit' => 1, 'used_count' => 0, 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $payload = $this->viaFile(['banners', 'coupons', 'paths']);

        $summary = $this->svc->summarize($payload);
        $this->assertSame(1, $summary['coupons']['count']);
        $this->assertArrayHasKey('banners', $summary);

        $report = $this->svc->import($payload, ['banners', 'coupons', 'paths'], ConfigTransfer::MODE_SKIP, ['dry' => true]);
        $text = implode(' ', $report['warnings']);
        $this->assertStringContainsString('slug', $text);
        $this->assertStringContainsString('banner', $text);
        $this->assertFalse(DB::table('coupons')->where('code', 'RESUMEN')->count() > 1);
    }
}
