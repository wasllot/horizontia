<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Larabuild\Optionbuilder\Facades\Settings;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** El selector de idioma no aparece si el sitio solo tiene uno. */
class LanguageSwitcherTest extends TestCase
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

    private function render(): string
    {
        return view('components.multi-lingual')->render();
    }

    public function test_it_is_hidden_with_a_single_language_or_when_off(): void
    {
        Settings::set('_general', 'enable_multi_language', '1');
        Settings::set('_general', 'multi_language_list', ['es']);
        $this->assertStringNotContainsString('am-switch-language', $this->render());

        Settings::set('_general', 'enable_multi_language', '');
        Settings::set('_general', 'multi_language_list', ['es', 'en']);
        $this->assertStringNotContainsString('am-switch-language', $this->render());
    }

    public function test_it_shows_when_there_are_two_or_more_languages(): void
    {
        Settings::set('_general', 'enable_multi_language', '1');
        Settings::set('_general', 'multi_language_list', ['es', 'en']);

        $html = $this->render();
        $this->assertStringContainsString('am-switch-language', $html);
        $this->assertStringContainsString('data-lang="en"', $html);
    }
}
