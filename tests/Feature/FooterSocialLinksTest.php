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
}
