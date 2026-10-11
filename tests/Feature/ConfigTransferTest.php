<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ConfigTransfer\ConfigTransfer;
use App\Services\ConfigTransfer\ConfigTransferException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Larabuild\Optionbuilder\Facades\Settings;
use Modules\Courses\Models\Course;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Exportar / importar la configuración del sitio: nunca cursos, blogs ni usuarios; credenciales fuera por defecto. */
class ConfigTransferTest extends TestCase
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

    private function page(string $slug, array $extra = []): int
    {
        return DB::table('pagebuilder__pages')->insertGetId($extra + [
            'name' => 'Página ' . $slug, 'title' => 'T ' . $slug, 'slug' => $slug, 'status' => 'published', 'gjs_html' => '<h1>Hola ' . $slug . '</h1>', 'gjs_css' => 'h1{color:red}',
            'settings' => '{}', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function roundTrip(array $payload): array
    {
        return $this->svc->parse($this->svc->toJson($payload));
    }

    // ------------------------------------------------------------ what is (not) exported

    public function test_the_file_never_carries_courses_blogs_or_users(): void
    {
        $payload = $this->svc->export(array_keys(ConfigTransfer::sections()), ['credentials' => true]);
        $json = $this->svc->toJson($payload);

        $this->assertEqualsCanonicalizing(array_diff(array_keys(ConfigTransfer::sections()), []), array_keys($payload['data']));
        foreach (['courses', 'blogs', 'users', 'enrollments', 'orders'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $payload['data']);
        }
        $this->assertStringNotContainsString($this->student()->email, $json);
        $this->assertStringNotContainsString(Course::first()->title, $json);
    }

    public function test_credentials_and_secrets_stay_out_unless_requested(): void
    {
        Settings::set('admin_settings', 'payment_method', ['mercadopago' => ['status' => 'on', 'currency' => 'MXN', 'mp_access_token' => 'APP_USR-SECRETO', 'mp_public_key' => 'PUBLICA', 'mp_webhook_secret' => 'WEBHOOK-SECRETO']]);
        Settings::set('_integrations', 'recaptcha_site_key', 'SITE-KEY');
        Settings::set('_integrations', 'recaptcha_secret_key', 'RECAPTCHA-SECRETO');
        Settings::set('_api', 'openai_api_key', 'sk-OPENAI');

        $safe = $this->svc->toJson($this->svc->export(['payments', 'integrations', 'credentials']));
        $this->assertStringNotContainsString('SECRETO', $safe);
        $this->assertStringNotContainsString('sk-OPENAI', $safe);
        $this->assertStringContainsString('PUBLICA', $safe, 'public keys are not secrets');
        $this->assertStringContainsString('SITE-KEY', $safe);
        $this->assertArrayNotHasKey('credentials', $this->svc->export(['credentials'])['data'], 'the credentials section needs the explicit option');

        $full = $this->svc->toJson($this->svc->export(['payments', 'integrations', 'credentials'], ['credentials' => true]));
        $this->assertStringContainsString('APP_USR-SECRETO', $full);
        $this->assertStringContainsString('RECAPTCHA-SECRETO', $full);
        $this->assertStringContainsString('sk-OPENAI', $full);
    }

    public function test_maintenance_mode_is_never_exported_or_imported(): void
    {
        Settings::set('_maintenance', 'maintenance_mode', 'on');
        $payload = $this->svc->export(['general']);
        $this->assertStringNotContainsString('maintenance_mode', $this->svc->toJson($payload));

        $payload['data']['general']['settings'][] = ['section' => '_maintenance', 'key' => 'maintenance_mode', 'value' => 'off', 'ser' => false];
        $payload['meta']['checksum'] = hash('sha256', json_encode($payload['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
        $report = $this->svc->import($this->roundTrip($payload), ['general'], ConfigTransfer::MODE_OVERWRITE);

        $this->assertSame('on', DB::table('optionbuilder__settings')->where(['section' => '_maintenance', 'key' => 'maintenance_mode'])->value('value'));
        $this->assertGreaterThan(0, $report['sections']['general']['skipped']);
    }

    // ------------------------------------------------------------ the file

    public function test_a_damaged_foreign_or_future_file_is_refused_with_a_clear_message(): void
    {
        $good = $this->svc->export(['menus']);

        $tampered = $good;
        $tampered['data']['menus'][] = ['name' => 'Inyectado', 'location' => 'header', 'items' => []];
        foreach ([
            ['no es json', 'No es un archivo'],
            [json_encode(['hola' => 1]), 'No es un archivo'],
            [json_encode(array_replace_recursive($good, ['meta' => ['version' => 99]])), 'versión más nueva'],
            [json_encode($tampered), 'suma de verificación'],
        ] as [$json, $message]) {
            try {
                $this->svc->parse($json);
                $this->fail('should have been refused: ' . $message);
            } catch (ConfigTransferException $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
        $this->assertIsArray($this->svc->parse(json_encode($good)));
    }

    // ------------------------------------------------------------ round trip and modes

    public function test_pages_menus_seo_settings_and_templates_survive_a_round_trip(): void
    {
        $this->page('mi-pagina', ['gjs_html' => '<h2>Contenido propio</h2>']);
        DB::table('seo_entries')->insert(['match_type' => 'path', 'match_value' => '/mi-pagina', 'title' => 'SEO propio', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $menuId = DB::table('menu')->insertGetId(['name' => 'Menú de prueba', 'location' => 'footer', 'created_at' => now(), 'updated_at' => now()]);
        $parent = DB::table('menu_items')->insertGetId(['menu_id' => $menuId, 'label' => 'Padre', 'route' => 'home', 'type' => 'route', 'sort' => 0, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('menu_items')->insert(['menu_id' => $menuId, 'parent_id' => $parent, 'label' => 'Hijo', 'route' => '/mi-pagina', 'type' => 'page', 'sort' => 0, 'created_at' => now(), 'updated_at' => now()]);
        Settings::set('_general', 'site_name', 'Mi Academia');
        Settings::set('_general', 'logo_default', 'logos/mio.png');
        $payload = $this->roundTrip($this->svc->export(['pages', 'menus', 'seo', 'general', 'templates']));

        // The destination knows none of it.
        DB::table('pagebuilder__pages')->where('slug', 'mi-pagina')->delete();
        DB::table('seo_entries')->where('match_value', '/mi-pagina')->delete();
        DB::table('menu_items')->where('menu_id', $menuId)->delete();
        DB::table('menu')->where('id', $menuId)->delete();
        Settings::set('_general', 'site_name', 'Otro nombre');

        $report = $this->svc->import($payload, ['pages', 'menus', 'seo', 'general', 'templates'], ConfigTransfer::MODE_SKIP);

        $this->assertSame('<h2>Contenido propio</h2>', DB::table('pagebuilder__pages')->where('slug', 'mi-pagina')->value('gjs_html'));
        $this->assertSame('SEO propio', DB::table('seo_entries')->where('match_value', '/mi-pagina')->value('title'));
        $menu = DB::table('menu')->where('name', 'Menú de prueba')->first();
        $this->assertNotNull($menu);
        $items = DB::table('menu_items')->where('menu_id', $menu->id)->orderBy('id')->get();
        $this->assertSame(['Padre', 'Hijo'], $items->pluck('label')->all());
        $this->assertSame($items[0]->id, $items[1]->parent_id, 'the submenu is rebuilt under its parent');
        $this->assertSame('Otro nombre', setting('_general.site_name'), 'skip mode keeps what the site already has');
        $this->assertGreaterThanOrEqual(1, $report['sections']['pages']['created']);

        $this->svc->import($payload, ['general'], ConfigTransfer::MODE_OVERWRITE);
        $this->assertSame('Mi Academia', setting('_general.site_name'), 'overwrite brings the exported value');
    }

    public function test_skip_leaves_existing_pages_alone_and_overwrite_updates_them(): void
    {
        $this->page('legal', ['gjs_html' => '<p>versión A</p>']);
        $payload = $this->roundTrip($this->svc->export(['pages']));
        DB::table('pagebuilder__pages')->where('slug', 'legal')->update(['gjs_html' => '<p>versión B local</p>']);

        $r = $this->svc->import($payload, ['pages'], ConfigTransfer::MODE_SKIP)['sections']['pages'];
        $this->assertSame('<p>versión B local</p>', DB::table('pagebuilder__pages')->where('slug', 'legal')->value('gjs_html'));
        $this->assertGreaterThan(0, $r['skipped']);

        $this->svc->import($payload, ['pages'], ConfigTransfer::MODE_OVERWRITE);
        $this->assertSame('<p>versión A</p>', DB::table('pagebuilder__pages')->where('slug', 'legal')->value('gjs_html'));
    }

    public function test_a_dry_run_reports_what_would_happen_and_changes_nothing(): void
    {
        $this->page('solo-en-archivo');
        $payload = $this->roundTrip($this->svc->export(['pages']));
        DB::table('pagebuilder__pages')->where('slug', 'solo-en-archivo')->delete();
        $before = DB::table('pagebuilder__pages')->count();

        $dry = $this->svc->import($payload, ['pages'], ConfigTransfer::MODE_SKIP, ['dry' => true]);

        $this->assertTrue($dry['dry']);
        $this->assertGreaterThan(0, $dry['sections']['pages']['created']);
        $this->assertSame($before, DB::table('pagebuilder__pages')->count(), 'nothing was written');

        $real = $this->svc->import($payload, ['pages'], ConfigTransfer::MODE_SKIP);
        $this->assertSame($dry['sections']['pages']['created'], $real['sections']['pages']['created'], 'the preview is exactly what happens');
    }

    public function test_only_the_chosen_pages_are_exported_and_imported(): void
    {
        $this->page('uno');
        $this->page('dos');

        $payload = $this->roundTrip($this->svc->export(['pages'], ['pages' => ['uno']]));
        $this->assertSame(['uno'], array_column($payload['data']['pages'], 'slug'));

        DB::table('pagebuilder__pages')->whereIn('slug', ['uno', 'dos'])->delete();
        $all = $this->roundTrip($this->svc->export(['pages'], []));   // nothing left of those two in the file
        $this->svc->import($payload, ['pages'], ConfigTransfer::MODE_SKIP, ['pages' => []]);
        $this->assertFalse(DB::table('pagebuilder__pages')->where('slug', 'uno')->exists(), 'an empty page selection imports no page');
        $this->svc->import($payload, ['pages'], ConfigTransfer::MODE_SKIP, ['pages' => ['uno']]);
        $this->assertTrue(DB::table('pagebuilder__pages')->where('slug', 'uno')->exists());
        $this->assertFalse(DB::table('pagebuilder__pages')->where('slug', 'dos')->exists());
    }

    // ------------------------------------------------------------ safety of what comes in

    public function test_imported_pages_are_sanitized_and_bad_slugs_refused(): void
    {
        $payload = $this->svc->export(['pages']);
        $payload['data']['pages'] = [
            ['name' => 'Mala', 'slug' => 'mala-pagina', 'status' => 'published', 'gjs_html' => '<p>ok</p><script>alert(1)</script><img src=x onerror=alert(2)>', 'gjs_css' => 'p{color:red}'],
            ['name' => 'Ruta rara', 'slug' => '../../etc/passwd', 'status' => 'published', 'gjs_html' => '<p>x</p>'],
        ];
        $payload['meta']['checksum'] = hash('sha256', json_encode($payload['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

        $report = $this->svc->import($this->roundTrip($payload), ['pages'], ConfigTransfer::MODE_SKIP);

        $html = (string) DB::table('pagebuilder__pages')->where('slug', 'mala-pagina')->value('gjs_html');
        $this->assertStringContainsString('<p>ok</p>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertFalse(DB::table('pagebuilder__pages')->where('slug', 'like', '%passwd%')->exists());
        $this->assertSame(1, $report['sections']['pages']['skipped']);
    }

    public function test_dangerous_menu_links_and_unknown_routes_are_skipped(): void
    {
        $payload = $this->svc->export(['menus']);
        $payload['data']['menus'] = [['name' => 'Peligro', 'location' => 'header', 'items' => [
            ['label' => 'Bien', 'route' => 'home', 'type' => 'route', 'children' => []],
            ['label' => 'JS', 'route' => 'javascript:alert(1)', 'type' => 'url', 'children' => []],
            ['label' => 'Datos', 'route' => 'data:text/html;base64,xx', 'type' => 'url', 'children' => []],
            ['label' => 'Fantasma', 'route' => 'ruta.que.no.existe', 'type' => 'route', 'children' => []],
        ]]];
        $payload['meta']['checksum'] = hash('sha256', json_encode($payload['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

        $report = $this->svc->import($this->roundTrip($payload), ['menus'], ConfigTransfer::MODE_SKIP);

        $menu = DB::table('menu')->where('name', 'Peligro')->first();
        $this->assertSame(['Bien'], DB::table('menu_items')->where('menu_id', $menu->id)->pluck('label')->all());
        $this->assertStringContainsString('3 enlace', $report['sections']['menus']['notes'][0]);
    }

    public function test_a_serialized_object_in_a_template_is_never_stored(): void
    {
        $payload = $this->svc->export(['templates']);
        $payload['data']['templates']['email'] = [['title' => 'Mala', 'type' => 'plantilla_mala', 'role' => 'student', 'content' => 'O:8:"stdClass":0:{}', 'status' => 'active']];
        $payload['meta']['checksum'] = hash('sha256', json_encode($payload['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

        $this->svc->import($this->roundTrip($payload), ['templates'], ConfigTransfer::MODE_SKIP);

        $this->assertFalse(DB::table('email_templates')->where('type', 'plantilla_mala')->exists());
    }

    public function test_a_file_that_hides_credentials_cannot_bring_them_in(): void
    {
        $payload = $this->svc->export(['general']);
        $payload['data']['credentials'] = ['settings' => [['section' => '_api', 'key' => 'openai_api_key', 'value' => 'sk-COLADA', 'ser' => false]]];
        $payload['meta']['sections'][] = 'credentials';
        $payload['meta']['checksum'] = hash('sha256', json_encode($payload['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

        $report = $this->svc->import($this->roundTrip($payload), ['credentials'], ConfigTransfer::MODE_OVERWRITE);

        $this->assertArrayNotHasKey('credentials', $report['sections']);
        $this->assertNotSame('sk-COLADA', DB::table('optionbuilder__settings')->where(['section' => '_api', 'key' => 'openai_api_key'])->value('value'));
    }

    // ------------------------------------------------------------ smart merges

    public function test_secrets_this_site_already_has_survive_an_import_that_left_them_out(): void
    {
        Settings::set('admin_settings', 'payment_method', ['mercadopago' => ['status' => 'on', 'currency' => 'MXN', 'mp_access_token' => 'TOKEN-DE-ORIGEN', 'mp_public_key' => 'PK-ORIGEN']]);
        $payload = $this->roundTrip($this->svc->export(['payments']));   // no secrets in the file

        Settings::set('admin_settings', 'payment_method', ['mercadopago' => ['status' => 'off', 'currency' => 'USD', 'mp_access_token' => 'TOKEN-DE-DESTINO', 'mp_public_key' => 'PK-DESTINO']]);
        $this->svc->import($payload, ['payments'], ConfigTransfer::MODE_OVERWRITE);

        $mp = setting('admin_settings.payment_method')['mercadopago'];
        $this->assertSame('on', $mp['status'], 'the non-secret configuration comes from the file');
        $this->assertSame('MXN', $mp['currency']);
        $this->assertSame('TOKEN-DE-DESTINO', $mp['mp_access_token'], 'the destination keeps its own secret');
    }

    public function test_page_ids_in_settings_are_relinked_by_slug(): void
    {
        $idOrigin = $this->page('portada-especial');
        Settings::set('_front_page_settings', 'header_variation_for_pages', [['page_id' => $idOrigin, 'header_variation' => 'am-header_three']]);
        $payload = $this->roundTrip($this->svc->export(['pages', 'general']));
        $this->assertSame('portada-especial', $payload['data']['general']['settings'][array_search('header_variation_for_pages', array_column($payload['data']['general']['settings'], 'key'))]['value'][0]['page_slug']);

        DB::table('pagebuilder__pages')->where('slug', 'portada-especial')->delete();
        $this->page('relleno-para-mover-ids');   // the page will get a different id here
        $this->svc->import($payload, ['pages', 'general'], ConfigTransfer::MODE_OVERWRITE);

        $newId = DB::table('pagebuilder__pages')->where('slug', 'portada-especial')->value('id');
        $variations = setting('_front_page_settings.header_variation_for_pages');
        $this->assertSame((int) $newId, (int) $variations[0]['page_id']);
        $this->assertSame('am-header_three', $variations[0]['header_variation']);
        $this->assertArrayNotHasKey('page_slug', $variations[0]);
    }

    public function test_settings_for_a_missing_page_are_dropped_instead_of_pointing_nowhere(): void
    {
        $id = $this->page('pagina-que-no-viajara');
        Settings::set('_front_page_settings', 'header_variation_for_pages', [['page_id' => $id, 'header_variation' => 'x']]);
        $payload = $this->roundTrip($this->svc->export(['general']));   // pages not exported
        DB::table('pagebuilder__pages')->where('slug', 'pagina-que-no-viajara')->delete();

        $this->svc->import($payload, ['general'], ConfigTransfer::MODE_OVERWRITE);

        $this->assertEmpty(setting('_front_page_settings.header_variation_for_pages'));
    }

    // ------------------------------------------------------------ categories, forms, boundaries

    public function test_categories_keep_their_hierarchy_and_never_bring_courses(): void
    {
        $parent = DB::table('courses_categories')->insertGetId(['name' => 'Padre cat', 'slug' => 'padre-cat', 'description' => 'x', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('courses_categories')->insert(['parent_id' => $parent, 'name' => 'Hija cat', 'slug' => 'hija-cat', 'description' => 'x', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $payload = $this->roundTrip($this->svc->export(['categories']));
        DB::table('courses_categories')->whereIn('slug', ['padre-cat', 'hija-cat'])->delete();
        $courses = Course::count();

        $this->svc->import($payload, ['categories'], ConfigTransfer::MODE_SKIP);

        $p = DB::table('courses_categories')->where('slug', 'padre-cat')->first();
        $this->assertSame($p->id, DB::table('courses_categories')->where('slug', 'hija-cat')->value('parent_id'));
        $this->assertSame($courses, Course::count());
    }

    public function test_the_same_category_slug_under_two_parents_stays_two_categories(): void
    {
        $now = ['status' => 'active', 'description' => 'x', 'created_at' => now(), 'updated_at' => now()];
        $a = DB::table('courses_categories')->insertGetId($now + ['name' => 'Diseño X', 'slug' => 'diseno-x']);
        $b = DB::table('courses_categories')->insertGetId($now + ['name' => 'Web X', 'slug' => 'web-x']);
        DB::table('courses_categories')->insert([$now + ['parent_id' => $a, 'name' => 'Photoshop', 'slug' => 'photoshop-x'], $now + ['parent_id' => $b, 'name' => 'Photoshop', 'slug' => 'photoshop-x']]);
        $payload = $this->roundTrip($this->svc->export(['categories']));
        DB::table('courses_categories')->whereIn('slug', ['diseno-x', 'web-x', 'photoshop-x'])->delete();

        $r = $this->svc->import($payload, ['categories'], ConfigTransfer::MODE_SKIP)['sections']['categories'];

        $this->assertSame(2, DB::table('courses_categories')->where('slug', 'photoshop-x')->count());
        $this->assertSame(0, $r['updated']);
    }

    public function test_form_data_is_opt_in_and_never_duplicated(): void
    {
        DB::table('contacts')->insert(['name' => 'Ana', 'email' => 'ana@ejemplo.com', 'subject' => 'Hola', 'message' => 'Mensaje', 'created_at' => '2026-01-02 10:00:00', 'updated_at' => now()]);
        $this->assertArrayNotHasKey('forms', $this->svc->export(['pages'])['data']);
        $payload = $this->roundTrip($this->svc->export(['forms']));

        $first = $this->svc->import($payload, ['forms'], ConfigTransfer::MODE_SKIP)['sections']['forms'];
        $this->assertSame(0, $first['created'], 'the same message already exists here');

        DB::table('contacts')->where('email', 'ana@ejemplo.com')->delete();
        $this->assertGreaterThan(0, $this->svc->import($payload, ['forms'], ConfigTransfer::MODE_SKIP)['sections']['forms']['created']);
        $this->assertSame(1, DB::table('contacts')->where('email', 'ana@ejemplo.com')->count());
    }

    public function test_importing_never_touches_users_or_courses(): void
    {
        $users = User::count();
        $courses = Course::count();
        $payload = $this->roundTrip($this->svc->export(array_keys(ConfigTransfer::sections()), ['credentials' => true]));

        $this->svc->import($payload, array_keys(ConfigTransfer::sections()), ConfigTransfer::MODE_OVERWRITE);

        $this->assertSame($users, User::count());
        $this->assertSame($courses, Course::count());
    }

    public function test_warnings_mention_files_that_are_not_copied(): void
    {
        Settings::set('_general', 'logo_default', 'logos/mio.png');
        $payload = $this->roundTrip($this->svc->export(['general', 'pages']));

        $warnings = $this->svc->import($payload, ['general', 'pages'], ConfigTransfer::MODE_SKIP, ['dry' => true])['warnings'];

        $this->assertNotEmpty(array_filter($warnings, fn ($w) => str_contains($w, 'NO se copian')));
    }
}
