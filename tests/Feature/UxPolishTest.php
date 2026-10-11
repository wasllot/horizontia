<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Larabuild\Optionbuilder\Facades\Settings;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Mejoras de UX: títulos y encabezados correctos, textos en español, nota de pago, barra de compra y aviso de cookies. */
class UxPolishTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true]);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function title(string $html): string
    {
        preg_match('#<title>(.*?)</title>#s', $html, $m);

        return html_entity_decode(trim($m[1] ?? ''));
    }

    public function test_the_course_page_has_one_h1_with_the_title_and_no_mismatched_tags(): void
    {
        $course = $this->course();
        $html = $this->get(route('courses.course-detail', ['slug' => $course->slug]))->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('#<h1[ >]#', $html));
        $this->assertMatchesRegularExpression('#<h1>[^<]*' . preg_quote(html_entity_decode($course->title), '#') . '[^<]*</h1>#u', html_entity_decode($html));
        $this->assertStringNotContainsString('</h2></h1>', $html);
    }

    public function test_a_course_without_reviews_says_new_instead_of_zero_reviews(): void
    {
        $course = $this->course();
        $course->ratings()->delete();

        $html = $this->get(route('courses.course-detail', ['slug' => $course->slug]))->assertOk()->getContent();

        $this->assertStringContainsString('data-testid="new-course"', $html);
        $this->assertStringNotContainsString('(0 Reseñas)', $html);
    }

    public function test_the_payment_note_only_promises_oxxo_and_spei_when_mercado_pago_allows_them(): void
    {
        $course = $this->course();
        $course->pricing()->update(['price' => 500, 'discount' => 0, 'final_price' => 500]);
        $methods = ['mercadopago' => ['status' => 'on', 'currency' => 'MXN', 'mp_access_token' => 'TEST-x', 'mp_public_key' => 'k', 'mp_webhook_secret' => 's', 'binary_mode' => '']];
        Settings::set('admin_settings', 'payment_method', $methods);

        $html = $this->actingAs($this->demoStudent())->get(route('courses.course-detail', ['slug' => $course->slug]))->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="pay-note"', $html);
        $this->assertStringContainsString('Tarjeta · OXXO · SPEI', $html);

        $methods['mercadopago']['binary_mode'] = '1';
        Settings::set('admin_settings', 'payment_method', $methods);
        $html = $this->actingAs($this->demoStudent())->get(route('courses.course-detail', ['slug' => $course->slug]))->getContent();
        $this->assertStringNotContainsString('OXXO', $html);
        $this->assertStringContainsString('Paga con', $html);

        Settings::set('admin_settings', 'payment_method', []);
        $this->assertSame([], paymentMethodLabels());
    }

    public function test_the_mobile_buy_bar_is_present_for_buyers(): void
    {
        $html = $this->actingAs($this->demoStudent())->get(route('courses.course-detail', ['slug' => $this->course()->slug]))->assertOk()->getContent();

        $this->assertStringContainsString('data-testid="buy-bar"', $html);
    }

    public function test_pages_have_meaningful_titles(): void
    {
        $this->assertStringContainsString('Mis cursos', $this->title($this->actingAs($this->student())->get(route('courses.course-list'))->getContent()));
        $this->assertStringContainsString('Paquetes de cursos', $this->title($this->get(route('courses.bundles.index'))->getContent()));
        $slug = $this->course()->slug;
        $this->assertStringContainsString(html_entity_decode($this->course()->title), $this->title($this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $slug]))->getContent()));
    }

    public function test_login_has_an_h1_and_no_pale_gold_button_or_links(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringContainsString('Iniciar sesión', $this->title($html));

        $this->assertSame(1, preg_match_all('#<h1[ >]#', $html));
        $this->assertStringNotContainsString('#F2D07F', $html);
    }

    public function test_spanish_language_and_date_helpers(): void
    {
        $this->assertSame('Español', languageLabel('Spanish'));
        $this->assertSame('Inglés', languageLabel('English'));
        $this->assertSame('Klingon', languageLabel('Klingon'), 'unknown names are returned as stored');
        $this->assertSame('11 oct. 2026', dateEs('2026-10-11'));
        $this->assertSame('', dateEs(null));
    }

    public function test_the_cookie_banner_is_compact_markup_with_stable_test_ids(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="ht-cookie"', $html);
        $this->assertStringContainsString('ht-cookie__short', $html);
        $this->assertMatchesRegularExpression('#data-testid="cookies-(ok|all)"#', $html);
    }

    public function test_the_frontend_has_a_skip_link_and_the_ux_stylesheet(): void
    {
        $html = $this->get('/cursos')->assertOk()->getContent();

        $this->assertStringContainsString('href="#main-content"', $html);
        $this->assertStringContainsString('id="main-content"', $html);
        $this->assertStringContainsString('css/ux-polish.css', $html);
    }

    public function test_the_demo_covers_are_branded_files(): void
    {
        foreach (range(1, 12) as $n) {
            $file = public_path("modules/courses/demo-content/images/{$n}.png");
            $this->assertFileExists($file);
            $this->assertLessThan(900_000, filesize($file), 'light branded covers, not the 1 MB theme images');
        }
    }

    public function test_a_free_course_offers_free_enrollment_and_no_payment_note(): void
    {
        $course = $this->course();
        $course->pricing()->update(['price' => 0, 'discount' => 0, 'final_price' => 0]);
        Settings::set('admin_settings', 'payment_method', ['mercadopago' => ['status' => 'on', 'currency' => 'MXN', 'mp_access_token' => 'TEST-x', 'mp_public_key' => 'k', 'mp_webhook_secret' => 's']]);

        $html = $this->actingAs($this->demoStudent())->get(route('courses.course-detail', ['slug' => $course->slug]))->assertOk()->getContent();

        $this->assertStringContainsString('Inscribirme gratis', $html);
        $this->assertStringNotContainsString('data-testid="pay-note"', $html);
        $this->assertStringNotContainsString("content: 'GRATIS'", $html, 'the duplicate GRATIS tag is gone');
    }
}
