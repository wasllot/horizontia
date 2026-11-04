<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Larabuild\Optionbuilder\Facades\Settings;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** El pie solo muestra redes sociales con una dirección real (nunca «#» ni texto suelto). */
class FooterSocialLinksTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.social' => []]);   // sin respaldo oficial: estas pruebas miden solo lo que se captura en Ajustes
        foreach (['fb_link', 'insta_link', 'linkedin_link', 'yt_link', 'tiktok_link'] as $k) {
            Settings::set('_general', $k, '');
        }
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    public function test_placeholder_links_do_not_render_dead_icons(): void
    {
        Settings::set('_general', 'fb_link', '#');
        Settings::set('_general', 'insta_link', 'instagram');

        $this->get('/')->assertOk()->assertDontSee('am-socialmedia', false)->assertDontSee('aria-label="Facebook"', false)->assertDontSee('aria-label="Instagram"', false);
    }

    public function test_real_links_render_and_the_others_stay_hidden(): void
    {
        Settings::set('_general', 'fb_link', 'https://facebook.com/horizontia');
        Settings::set('_general', 'yt_link', '#');

        $this->get('/')->assertOk()->assertSee('href="https://facebook.com/horizontia"', false)->assertSee('aria-label="Facebook"', false)->assertDontSee('aria-label="YouTube"', false);
    }

    public function test_empty_settings_fall_back_to_the_official_horizontia_networks(): void
    {
        config(['academy.social' => ['facebook' => 'https://www.facebook.com/somoshorizontia', 'instagram' => 'https://www.instagram.com/somoshorizontia/', 'youtube' => 'https://www.youtube.com/@SomosHorizontia', 'linkedin' => 'https://www.linkedin.com/company/somoshorizontia']]);
        Settings::set('_general', 'insta_link', 'https://instagram.com/otra');
        Settings::set('_general', 'yt_link', '#');   // valor de plantilla: tampoco cuenta

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('href="https://www.facebook.com/somoshorizontia"', $html);
        $this->assertStringContainsString('href="https://www.youtube.com/@SomosHorizontia"', $html);
        $this->assertStringContainsString('href="https://www.linkedin.com/company/somoshorizontia"', $html);
        $this->assertStringContainsString('href="https://instagram.com/otra"', $html, 'lo capturado en Ajustes manda');
        $this->assertStringNotContainsString('instagram.com/somoshorizontia', $html);
    }
}
