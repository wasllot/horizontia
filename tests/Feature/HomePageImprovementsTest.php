<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Larabuild\Optionbuilder\Facades\Settings;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Mejoras de la home: SEO en español, datos estructurados, imágenes con tamaño, accesibilidad y confianza. */
class HomePageImprovementsTest extends TestCase
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

    private function home(): string
    {
        return $this->get('/')->assertOk()->getContent();
    }

    public function test_seo_is_in_spanish_and_describes_the_academy(): void
    {
        $html = $this->home();

        $this->assertStringContainsString('<title>Horizontia | Cursos en línea con certificado</title>', $html);
        $this->assertMatchesRegularExpression('/<meta name="description" content="[^"]*cursos en línea[^"]*"/u', $html);
        $this->assertStringNotContainsString('Book sessions', $html);
        $this->assertStringNotContainsString('tutoring', strtolower($html));
    }

    public function test_structured_data_is_valid_json_with_organization_courses_and_faq(): void
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $this->home(), $m);
        $types = [];
        foreach ($m[1] as $json) {
            $data = json_decode($json, true);
            $this->assertIsArray($data, 'every JSON-LD block must be valid JSON');
            $types[] = $data['@type'];
        }

        foreach (['EducationalOrganization', 'ItemList', 'FAQPage', 'WebSite'] as $type) {
            $this->assertContains($type, $types, "missing $type structured data");
        }
    }

    public function test_hero_is_preloaded_and_images_reserve_their_space(): void
    {
        $html = $this->home();

        $this->assertMatchesRegularExpression('#<link rel="preload" as="image" href="[^"]*Slider-horizontia[^"]*" fetchpriority="high">#', $html);

        preg_match_all('/<img\b[^>]*>/i', $html, $imgs);
        $missing = array_values(array_filter($imgs[0], fn ($tag) => !preg_match('/\bwidth="\d+"/', $tag) || !preg_match('/\bheight="\d+"/', $tag)));
        $this->assertSame([], $missing, 'every <img> needs width and height to avoid layout shift');
    }

    public function test_trust_strip_faq_and_accessible_names(): void
    {
        $html = $this->home();

        $this->assertStringContainsString('Certificado al terminar', $html);
        $this->assertStringContainsString('Garantía de reembolso de ' . config('academy.refund_days') . ' días', $html);
        $this->assertStringContainsString('Preguntas frecuentes', $html);
        $this->assertStringContainsString('aria-label="Volver arriba"', $html);
        $this->assertStringNotContainsString('color: #F2D07F; font-weight: 700', $html, 'pale text on white fails contrast');
    }

    public function test_footer_never_ships_template_contact_data(): void
    {
        foreach (['footer_contact' => '', 'footer_email' => '', 'footer_address' => ''] as $key => $value) {
            Settings::set('_front_page_settings', $key, $value);
        }
        $html = $this->home();
        $this->assertStringNotContainsString('hello@gmail.com', $html);
        $this->assertStringNotContainsString('Kentucky', $html);

        Settings::set('_front_page_settings', 'footer_email', 'contacto@horizontia.test');
        $this->assertStringContainsString('mailto:contacto@horizontia.test', $this->home(), 'the mailto link must use the configured email');
    }

    public function test_cookie_banner_offers_a_real_choice_only_when_analytics_is_on(): void
    {
        $this->assertStringContainsString('data-testid="cookies-ok"', $this->home());

        Settings::set('_integrations', 'gtm_enabled', '1');
        Settings::set('_integrations', 'gtm_id', 'GTM-ABC1234');
        $html = $this->home();
        $this->assertStringContainsString('data-testid="cookies-necessary"', $html);
        $this->assertStringContainsString('data-testid="cookies-all"', $html);
    }

    public function test_courses_without_reviews_show_no_empty_stars(): void
    {
        $this->assertStringNotContainsString('<span style="font-size:.75rem;color:#6b7280;margin-left:3px;">—</span>', $this->home());
    }
}
