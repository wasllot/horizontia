<?php

namespace Tests\Feature;

use App\Rules\RecaptchaRule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Larabuild\Optionbuilder\Facades\Settings;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Ajustes › Integraciones: reCAPTCHA (formularios públicos) y Google Tag Manager (todas las páginas). */
class IntegrationsSettingsTest extends TestCase
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

    private function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Settings::set('_integrations', $key, $value);
        }
    }

    public function test_settings_screen_offers_recaptcha_and_gtm(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/ajustes')->assertOk()->getContent();

        $this->assertStringContainsString('Integraciones', $html);
        $this->assertStringContainsString('recaptcha_site_key', $html);
        $this->assertStringContainsString('gtm_id', $html);
    }

    public function test_gtm_is_added_to_public_pages_only_with_a_valid_enabled_container(): void
    {
        $this->get('/')->assertOk()->assertDontSee('googletagmanager.com', false);

        $this->set(['gtm_enabled' => '1', 'gtm_id' => 'gtm-abc1234']);   // lower case is normalised
        $this->get('/')->assertOk()->assertSee("'dataLayer', 'GTM-ABC1234'", false)->assertSee("ht_cookie_ok') === 'all'", false)->assertDontSee('ns.html', false);
        $this->get('/iniciar-sesion')->assertOk()->assertSee('GTM-ABC1234', false);

        $this->set(['gtm_id' => 'GTM-1"><script>alert(1)</script>']);
        $this->get('/')->assertOk()->assertDontSee('googletagmanager.com', false);

        $this->set(['gtm_id' => 'GTM-ABC1234', 'gtm_enabled' => '0']);
        $this->get('/')->assertOk()->assertDontSee('googletagmanager.com', false);
    }

    public function test_panels_load_gtm_only_when_asked(): void
    {
        $this->set(['gtm_enabled' => '1', 'gtm_id' => 'GTM-ABC1234']);
        $student = $this->student();

        $this->actingAs($student)->get(route('courses.course-list'))->assertOk()->assertDontSee('GTM-ABC1234', false);

        $this->set(['gtm_in_panels' => '1']);
        $this->actingAs($student)->get(route('courses.course-list'))->assertOk()->assertSee('GTM-ABC1234', false);
    }

    public function test_recaptcha_needs_the_switch_and_both_keys(): void
    {
        $this->assertFalse(recaptchaEnabled('login'));

        $this->set(['enable_recaptcha' => '1', 'recaptcha_site_key' => 'site-key']);
        $this->assertFalse(recaptchaEnabled('login'), 'without the secret it stays off so nobody is locked out');

        $this->set(['recaptcha_secret_key' => 'secret-key']);
        $this->assertTrue(recaptchaEnabled('login'));
        $this->assertTrue(recaptchaEnabled('contact'));
        $this->assertTrue(recaptchaEnabled('newsletter'));

        $this->set(['enable_recaptcha' => '0']);
        $this->assertFalse(recaptchaEnabled('login'));
    }

    public function test_login_page_shows_the_widget_only_when_protected(): void
    {
        $this->get('/iniciar-sesion')->assertOk()->assertDontSee('g-recaptcha', false);

        $this->set(['enable_recaptcha' => '1', 'recaptcha_site_key' => 'site-key-123', 'recaptcha_secret_key' => 'secret']);
        $this->get('/iniciar-sesion')->assertOk()->assertSee('g-recaptcha', false)->assertSee('site-key-123', false)->assertDontSee('secret"', false);
    }

    public function test_whatsapp_button_needs_the_switch_and_a_valid_number(): void
    {
        $this->get('/')->assertOk()->assertDontSee('whatsapp-button', false);

        $this->set(['whatsapp_enabled' => '1', 'whatsapp_number' => 'abc']);
        $this->get('/')->assertOk()->assertDontSee('whatsapp-button', false);

        $this->set(['whatsapp_number' => '+52 1 55 1234 5678', 'whatsapp_message' => 'Hola <b>equipo</b> & gracias']);
        $html = $this->get('/')->assertOk()->assertSee('data-testid="whatsapp-button"', false)->getContent();
        $this->assertStringContainsString('https://wa.me/5215512345678?text=Hola%20equipo%20%26%20gracias', $html);
        $this->get('/iniciar-sesion')->assertOk()->assertSee('whatsapp-button', false);

        $this->set(['whatsapp_enabled' => '0']);
        $this->get('/')->assertOk()->assertDontSee('whatsapp-button', false);
    }

    public function test_whatsapp_position_and_panels(): void
    {
        $this->set(['whatsapp_enabled' => '1', 'whatsapp_number' => '5215512345678', 'whatsapp_position' => 'left']);
        $this->get('/')->assertOk()->assertSee('hz-wa--left', false);

        $student = $this->student();
        $this->actingAs($student)->get(route('courses.course-list'))->assertOk()->assertDontSee('whatsapp-button', false);

        $this->set(['whatsapp_in_panels' => '1']);
        $this->actingAs($student)->get(route('courses.course-list'))->assertOk()->assertSee('whatsapp-button', false);
    }

    public function test_recaptcha_rule_verifies_the_answer_on_the_server(): void
    {
        $rule = new RecaptchaRule();
        $this->assertTrue($rule->passes('token', ''), 'off: nothing to verify');

        $this->set(['enable_recaptcha' => '1', 'recaptcha_site_key' => 'k', 'recaptcha_secret_key' => 's']);

        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::sequence()
            ->push(['success' => true])
            ->push(['success' => false])
            ->pushFailedConnection()]);

        $this->assertTrue($rule->passes('token', 'good'));
        $this->assertFalse($rule->passes('token', ''), 'an empty answer never passes');
        $this->assertFalse($rule->passes('token', 'bad'));
        $this->assertFalse($rule->passes('token', 'any'), 'Google unreachable: fail closed');
    }
}
