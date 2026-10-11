<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Regression guards for the unified design system and the performance work:
 * one stylesheet for every role, no bloated/inlined/hot-linked assets, optimized images, paid prices shown.
 */
class UiAndPerformanceTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true]);
    }

    public function test_every_area_loads_the_unified_stylesheet(): void
    {
        $slug = $this->course()->slug;
        $pages = [
            'public' => [null, '/cursos'],
            'course page' => [null, "/curso/{$slug}"],
            'legal' => [null, '/preguntas-frecuentes'],
            'student' => [$this->student(), '/mis-cursos'],
            'lesson player' => [$this->student(), "/aula/{$slug}"],
            'instructor' => [$this->tutor(), '/gestionar-cursos'],
            'admin' => [$this->admin(), '/admin/estadisticas'],
        ];

        foreach ($pages as $label => [$user, $uri]) {
            $response = ($user ? $this->actingAs($user) : $this)->get($uri);
            $response->assertOk();
            // Dashboards (student / instructor / admin) use the panel design system; public pages and the player the site one.
            $sheet = in_array($label, ['student', 'instructor', 'admin'], true) ? 'css/panel.css' : 'css/horizontia-ui.css';
            $this->assertStringContainsString($sheet, $response->getContent(), "$label does not load $sheet");
        }
        $this->assertFileExists(public_path('css/horizontia-ui.css'));
    }

    public function test_dashboard_pages_stay_light_and_do_not_inline_vendor_bundles(): void
    {
        // Regression: the spotlight search used to inline ~400 KB of JS/CSS into every dashboard page.
        foreach ([[$this->student(), '/mis-cursos'], [$this->tutor(), '/gestionar-cursos'], [$this->admin(), '/admin/estadisticas']] as [$user, $uri]) {
            $html = $this->actingAs($user)->get($uri)->getContent();

            $this->assertLessThan(200 * 1024, strlen($html), "$uri HTML is " . round(strlen($html) / 1024) . ' KB');
            $this->assertStringNotContainsString('function LivewireUISpotlight', $html, "$uri inlines the spotlight bundle");
        }
        $this->assertStringContainsString('vendor/spotlight/spotlight.js', $this->actingAs($this->student())->get('/mis-cursos')->getContent());
    }

    public function test_pages_never_hotlink_the_production_site(): void
    {
        foreach ([[null, '/'], [null, '/cursos'], [$this->student(), '/mis-cursos'], [$this->admin(), '/admin/estadisticas']] as [$user, $uri]) {
            $html = ($user ? $this->actingAs($user) : $this)->get($uri)->getContent();

            $this->assertStringNotContainsString('horizontia.com/storage', $html, "$uri loads assets from the live site");
        }
        $this->assertFileExists(public_path('images/nuevo/logo-horizontia.png'));
    }

    public function test_brand_images_use_optimized_webp_when_available(): void
    {
        $this->assertStringEndsWith('/images/nuevo/fondo-horizontia-amarillo.webp', webpAsset('images/nuevo/fondo-horizontia-amarillo.jpg'));
        $this->assertStringEndsWith('/images/nuevo/Logo-Horizontia-footer.png', webpAsset('images/nuevo/Logo-Horizontia-footer.png'), 'small logos have no webp sibling');
        $this->assertStringEndsWith('/images/no-existe.jpg', webpAsset('images/no-existe.jpg'));

        $home = $this->get('/')->getContent();
        $this->assertStringNotContainsString('fondo-horizontia-amarillo.jpg', $home);
        $this->assertStringContainsString('fondo-horizontia-amarillo.webp', $home);
    }

    public function test_course_thumbnails_are_served_as_small_webp_variants(): void
    {
        Storage::fake('public');
        $original = UploadedFile::fake()->image('big.png', 2400, 1350)->size(900);
        Storage::disk('public')->put('media/thumbnails/big.png', file_get_contents($original->getPathname()));

        $url = mediaThumb('media/thumbnails/big.png', 640);

        $this->assertStringContainsString('/thumbnails/w640/', $url);
        $this->assertStringEndsWith('.webp', $url);
        $variant = 'thumbnails/w640/' . md5('media/thumbnails/big.png') . '.webp';
        Storage::disk('public')->assertExists($variant);
        $this->assertLessThan(Storage::disk('public')->size('media/thumbnails/big.png'), Storage::disk('public')->size($variant));
        [$width] = getimagesizefromstring(Storage::disk('public')->get($variant));
        $this->assertLessThanOrEqual(640, $width);

        // Second call reuses the variant instead of re-encoding.
        $this->assertSame($url, mediaThumb('media/thumbnails/big.png', 640));
    }

    public function test_thumbnail_helper_never_breaks_a_page(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/thumbnails/broken.png', 'not an image');

        $this->assertStringEndsWith('media/thumbnails/broken.png', mediaThumb('media/thumbnails/broken.png'));
        $this->assertStringEndsWith('placeholder.png', mediaThumb(null));
        $this->assertStringEndsWith('placeholder.png', mediaThumb(''));
        $this->assertStringEndsWith('media/missing.png', mediaThumb('media/missing.png'));
    }

    public function test_paid_system_is_on_for_an_academy_and_prices_are_shown(): void
    {
        // Regression: with enable_paid_system off every course said "Gratis" and could not be bought.
        $this->assertTrue(isPrivateAcademy() && isPaidSystem());

        $html = $this->get('/curso/' . $this->course()->slug)->assertOk()->getContent();
        $this->assertStringContainsString('44.99', $html);
        $this->assertStringContainsString(__('courses::courses.add_to_cart'), $html);

        config(['academy.private_mode' => false]);
        \Larabuild\Optionbuilder\Facades\Settings::set('_general', 'enable_paid_system', '0');
        $this->assertFalse(isPaidSystem(), 'outside academy mode the admin setting still decides');
    }

    public function test_dashboards_are_free_of_marketplace_clutter_in_academy_mode(): void
    {
        $student = $this->actingAs($this->student())->get('/mis-cursos')->assertOk()->getContent();
        $this->assertStringNotContainsString('am-wallet', $student, 'wallet widget');
        $this->assertStringNotContainsString('Explorar Cursos', $student, 'redundant header pills');

        $admin = $this->actingAs($this->admin())->get('/admin/estadisticas')->assertOk();
        $admin->assertDontSee('Pagos a Tutores');
        $admin->assertSee('Pedidos completados');
        $admin->assertSee('Ingresos por día');
    }

    public function test_public_course_page_hides_tutor_marketplace_bits(): void
    {
        $html = $this->get('/curso/' . $this->course()->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('cr-view-profile-btn', $html);
        $this->assertStringNotContainsString(__('courses::courses.i_can_speak'), $html);
        $this->assertStringNotContainsString(' of ', strip_tags(explode('cr-includes-list', $html)[1] ?? ''), 'hard-coded English "of" in the includes list');
    }

    public function test_lesson_player_strings_are_translated(): void
    {
        $this->app->setLocale('es');
        $html = $this->actingAs($this->student())->get('/aula/' . $this->course()->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('Go to next item', $html);
        $this->assertStringNotContainsString('Mark as complete', $html);
    }

    public function test_public_footer_is_identical_on_every_page(): void
    {
        // Regression: the home page overrode the footer colour with its own <style> block, so it differed from all other pages.
        $normalize = function (string $html): string {
            preg_match('/<footer.*?<\/footer>/s', $html, $m);

            return preg_replace(['/\s+/', '/wire:[a-z.:\-]+="[^"]*"/', '/ id="[^"]*"/'], [' ', '', ''], $m[0] ?? '');
        };

        $reference = $normalize($this->get('/')->getContent());
        $this->assertNotSame('', $reference, 'home has no footer');

        foreach (['/cursos', '/curso/' . $this->course()->slug, '/como-funciona', '/blog', '/contacto', '/preguntas-frecuentes'] as $uri) {
            $other = $normalize($this->get($uri)->getContent());
            $at = 0;
            while ($at < min(strlen($reference), strlen($other)) && $reference[$at] === $other[$at]) {
                $at++;
            }
            $this->assertSame($reference, $other, "$uri footer differs from the home footer near: home=[" . substr($reference, max(0, $at - 60), 160) . "] page=[" . substr($other, max(0, $at - 60), 160) . ']');
        }

        $this->assertStringNotContainsString('Dynamic Style Block for Footer Override', $this->get('/')->getContent());
        $this->assertStringContainsString('footer.am-footer { background-color: #222222', file_get_contents(public_path('css/horizontia-ui.css')));
    }
}

