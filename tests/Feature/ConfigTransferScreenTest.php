<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\ConfigTransfer\ConfigTransferPage;
use App\Services\ConfigTransfer\ConfigTransfer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Larabuild\Optionbuilder\Facades\Settings;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** La pantalla Ajustes → Exportar / importar y los comandos `academy:export` / `academy:import`. */
class ConfigTransferScreenTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function fileOf(array $sections = ['menus', 'general'], array $options = []): UploadedFile
    {
        $svc = new ConfigTransfer();

        return UploadedFile::fake()->createWithContent('exportacion.json', $svc->toJson($svc->export($sections, $options)));
    }

    public function test_only_admins_see_the_screen_and_it_is_in_the_settings_menu(): void
    {
        $this->actingAs($this->admin())->get(route('admin.config-transfer'))->assertOk()->assertSee('Exportar e importar configuración')->assertSee('Nunca');
        Livewire::actingAs($this->student())->test(ConfigTransferPage::class)->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.insights'))->assertOk()->assertSee('Exportar / importar');
    }

    public function test_the_default_selection_is_the_safe_one_and_credentials_need_an_explicit_choice(): void
    {
        $c = Livewire::actingAs($this->admin())->test(ConfigTransferPage::class);

        $defaults = $c->get('exportSections');
        foreach (['pages', 'menus', 'seo', 'general', 'integrations', 'templates'] as $k) {
            $this->assertContains($k, $defaults);
        }
        foreach (['payments', 'forms', 'credentials', 'categories'] as $k) {
            $this->assertNotContains($k, $defaults, "$k is opt-in");
        }
        $c->assertDontSee('Claves de API y credenciales')->set('withCredentials', true)->assertSee('Claves de API y credenciales');
    }

    public function test_exporting_downloads_a_json_and_needs_at_least_one_section(): void
    {
        $c = Livewire::actingAs($this->admin())->test(ConfigTransferPage::class);

        $c->set('exportSections', [])->call('download')->assertHasErrors('exportSections');
        $c->set('exportSections', ['menus'])->call('download')->assertFileDownloaded();
    }

    public function test_uploading_a_file_shows_its_contents_and_a_preview_saves_nothing(): void
    {
        $pages = DB::table('pagebuilder__pages')->count();
        Settings::set('_general', 'site_name', 'Nombre original');
        $file = $this->fileOf(['general', 'pages']);
        Settings::set('_general', 'site_name', 'Nombre cambiado');

        $c = Livewire::actingAs($this->admin())->test(ConfigTransferPage::class)->set('tab', 'import')->set('upload', $file);

        $this->assertNotEmpty($c->get('summary'));
        $this->assertArrayHasKey('general', $c->get('summary'));
        $c->set('mode', 'overwrite')->call('preview')->assertSee('Vista previa (no se guardó nada)');
        $this->assertSame('Nombre cambiado', setting('_general.site_name'), 'preview does not change anything');
        $this->assertSame($pages, DB::table('pagebuilder__pages')->count());

        $c->set('backup', false)->call('import');
        $this->assertSame('Nombre original', setting('_general.site_name'));
        $c->assertSee('Importación terminada');
    }

    public function test_a_wrong_file_shows_a_clear_error(): void
    {
        $c = Livewire::actingAs($this->admin())->test(ConfigTransferPage::class)->set('tab', 'import')
            ->set('upload', UploadedFile::fake()->createWithContent('x.json', '{"hola":1}'));

        $c->assertSet('token', null)->assertSee('No es un archivo de exportación');
    }

    public function test_sensitive_sections_start_unchecked_on_import(): void
    {
        $file = $this->fileOf(['menus', 'forms', 'payments']);
        $c = Livewire::actingAs($this->admin())->test(ConfigTransferPage::class)->set('upload', $file);

        $this->assertContains('menus', $c->get('importSections'));
        $this->assertNotContains('forms', $c->get('importSections'));
        $this->assertNotContains('payments', $c->get('importSections'));
    }

    public function test_the_page_choice_limits_what_is_imported(): void
    {
        foreach (['solo-esta', 'esta-no'] as $slug) {
            DB::table('pagebuilder__pages')->insert(['name' => $slug, 'title' => $slug, 'slug' => $slug, 'status' => 'published', 'gjs_html' => '<p>x</p>', 'created_at' => now(), 'updated_at' => now()]);
        }
        $file = $this->fileOf(['pages']);
        DB::table('pagebuilder__pages')->whereIn('slug', ['solo-esta', 'esta-no'])->delete();

        Livewire::actingAs($this->admin())->test(ConfigTransferPage::class)->set('upload', $file)
            ->set('importSections', ['pages'])->set('allImportPages', false)->set('importPages', ['solo-esta'])->set('backup', false)->call('import');

        $this->assertTrue(DB::table('pagebuilder__pages')->where('slug', 'solo-esta')->exists());
        $this->assertFalse(DB::table('pagebuilder__pages')->where('slug', 'esta-no')->exists());
    }

    public function test_the_commands_export_and_import_with_a_dry_run(): void
    {
        $file = sys_get_temp_dir() . '/cfg-' . uniqid() . '.json';
        $this->artisan('academy:export', ['file' => $file, '--sections' => 'menus,seo'])->assertSuccessful();
        $this->assertFileExists($file);
        $this->artisan('academy:export', ['file' => $file . '.x', '--sections' => 'cursos'])->assertFailed();

        $this->artisan('academy:import', ['file' => $file, '--dry-run' => true])->expectsOutputToContain('Vista previa')->assertSuccessful();
        $this->artisan('academy:import', ['file' => $file . '.no'])->assertFailed();
        file_put_contents($file, '{"roto":');
        $this->artisan('academy:import', ['file' => $file])->assertFailed();
        @unlink($file);
        @unlink($file . '.x');
    }
}
