<?php

namespace Tests\Feature;

use App\Http\Controllers\PageController;
use App\Livewire\Pages\Admin\Pages\PagesManager;
use App\Models\PageBanner;
use App\Services\PageBannerService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Página «Nosotros»: informativa e ilustrada, con un recorrido de la plataforma hecho con mockups. */
class AboutPageTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Cache::flush();
    }

    public function test_it_has_a_banner_with_an_image_and_all_the_sections(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();

        $this->assertFileExists(public_path('images/nuevo/banner-nosotros.jpg'));
        $this->assertStringContainsString('banner-nosotros', $html);
        foreach (['Quiénes somos', 'Lo que nos distingue', 'Así funciona', 'Dentro de la plataforma', 'Para instructores', 'Nuestro compromiso', 'Preguntas frecuentes', '¿Listo para aprender a tu ritmo?'] as $section) {
            $this->assertStringContainsString($section, $html, $section);
        }
        $this->assertSame(1, preg_match_all('#<h1[ >]#', $html), 'only the banner title is an h1');
    }

    public function test_the_tour_has_five_accessible_steps_with_their_mockups(): void
    {
        $html = $this->get(route('about'))->getContent();

        $this->assertSame(5, preg_match_all('#role="tab"#', $html));
        $this->assertSame(5, preg_match_all('#role="tabpanel"#', $html));
        foreach (['Solicita tu acceso', 'Elige tu curso', 'Paga con Mercado Pago', 'Aprende en tu aula', 'Obtén tu certificado'] as $step) {
            $this->assertStringContainsString($step, $html);
        }
        foreach (['horizontia.mx/solicitar-acceso', 'horizontia.mx/cursos', 'horizontia.mx/finalizar-compra', 'horizontia.mx/aula/', 'horizontia.mx/verificar'] as $mockup) {
            $this->assertStringContainsString($mockup, $html, $mockup);
        }
    }

    public function test_numbers_and_promises_follow_the_configuration(): void
    {
        config(['academy.refund_days' => 15]);
        $this->get(route('about'))->assertSee('garantía de reembolso de 15 días')->assertSee('primeros 15 días');

        $html = $this->get(route('about'))->getContent();
        $this->assertStringNotContainsString('{{', $html);
        $this->assertStringNotContainsString('@if', $html);
    }

    public function test_optional_features_only_show_when_they_are_on(): void
    {
        config(['academy.community' => true, 'academy.pwa' => true]);
        $this->get(route('about'))->assertSee('Comunidad en cada curso')->assertSee('Instálala como app');

        config(['academy.community' => false, 'academy.pwa' => false]);
        $this->get(route('about'))->assertDontSee('Comunidad en cada curso')->assertDontSee('Instálala como app');
    }

    public function test_the_calls_to_action_follow_the_access_request_switch(): void
    {
        config(['academy.access_requests' => true]);
        $this->get(route('about'))->assertSee(route('access.request'), false)->assertSee('Quiero ser instructor');

        config(['academy.access_requests' => false]);
        $this->get(route('about'))->assertDontSee(route('access.request'), false)->assertDontSee('Quiero ser instructor');
    }

    public function test_the_banner_can_be_changed_from_the_admin(): void
    {
        PageBanner::create(['page_key' => 'system:nosotros', 'title' => 'Quiénes somos hoy', 'subtitle' => 'Subtítulo propio', 'overlay' => 40, 'align' => 'center']);
        PageBannerService::forget('system:nosotros');

        $this->get(route('about'))->assertSee('Quiénes somos hoy')->assertSee('Subtítulo propio');
    }

    public function test_it_has_its_own_view_so_it_is_not_listed_as_an_editor_page(): void
    {
        $this->assertContains('about', PageController::DEDICATED);
        $this->assertSame('nosotros', PageController::PAGES['about'][0], 'still part of the sitemap and the SEO screen');

        $names = Livewire::actingAs($this->admin())->test(PagesManager::class)->viewData('pages')->pluck('slug')->all();
        $this->assertNotContains('nosotros', $names);
        $this->assertContains('como-funciona', $names);

        $this->artisan('academy:pages', ['--only' => 'nosotros', '--force' => true])->assertSuccessful();   // no intenta reconvertir la vista
    }
}
