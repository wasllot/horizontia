<?php

namespace Tests\Feature;

use App\Support\SettingsRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Larabuild\Optionbuilder\Facades\Settings;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Ajustes: pantalla curada en español; cada página guarda solo sus propios campos. */
class SettingsScreenTest extends TestCase
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

    private function save(string $page, array $data)
    {
        return $this->actingAs($this->admin())->postJson(route('optionbuilder.update-section-settings'), ['page' => $page, 'data' => http_build_query($data)]);
    }

    public function test_the_screen_lists_every_curated_page_in_spanish(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/ajustes')->assertOk()->getContent();

        foreach (SettingsRegistry::pages() as $page) {
            $this->assertStringContainsString('data-testid="page-' . $page['id'] . '"', $html);
            $this->assertStringContainsString($page['label'], $html);
        }
        foreach (['Global Settings', 'Reset all', 'Save changes', 'Dispute', 'IA Writer', 'Enter SMTP', 'Select SMTP', 'Add maintenance', 'United States dollar'] as $english) {
            $this->assertStringNotContainsString($english, $html, "English leftover: {$english}");
        }
        $this->assertStringContainsString('Guardar cambios', $html);
    }

    public function test_only_admins_can_open_or_save_settings(): void
    {
        $this->assertNotSame(200, $this->actingAs($this->student())->get('/admin/ajustes')->getStatusCode());
        $this->actingAs($this->student())->postJson(route('optionbuilder.update-section-settings'), ['page' => 'sitio', 'data' => 'site_name=X'])->assertStatus(403);
    }

    public function test_saving_a_page_writes_its_fields_and_leaves_every_other_setting_alone(): void
    {
        Settings::set('_general', 'site_name', 'Antes');
        Settings::set('_api', 'google_places_key', 'CLAVE-AJENA');   // no la ofrece ninguna página

        $this->save('sitio', ['site_name' => 'Horizontia Test'])->assertOk()->assertJsonPath('success.type', 'success');

        $this->assertSame('Horizontia Test', setting('_general.site_name'));
        $this->assertSame('CLAVE-AJENA', setting('_api.google_places_key'));
    }

    public function test_an_unticked_switch_is_stored_as_off(): void
    {
        Settings::set('_general', 'enable_multi_language', '1');

        $this->save('sitio', ['site_name' => 'X', 'enable_multi_language' => '0'])->assertOk();

        $this->assertSame('0', (string) setting('_general.enable_multi_language'));
    }

    public function test_resetting_a_page_clears_only_that_pages_fields(): void
    {
        Settings::set('_general', 'site_name', 'Se borra');
        Settings::set('_integrations', 'gtm_id', 'GTM-KEEP123');

        $this->actingAs($this->admin())->postJson(route('optionbuilder.reset-section-settings'), ['page' => 'sitio'])->assertOk();

        $this->assertNotSame('Se borra', setting('_general.site_name'));
        $this->assertSame('GTM-KEEP123', setting('_integrations.gtm_id'));
    }

    public function test_secrets_render_as_password_inputs(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/ajustes')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*type="password"[^>]*name="smtp_password"|<input[^>]*name="smtp_password"[^>]*type="password"/', $html);
    }

    public function test_an_unknown_page_is_rejected(): void
    {
        $this->save('inexistente', ['a' => 'b'])->assertStatus(422);
    }
}
