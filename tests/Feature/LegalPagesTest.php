<?php

namespace Tests\Feature;

use App\Http\Controllers\PageController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Courses\Models\Course;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Legal / information pages, footer links, cookie banner, sitemap and robots.
 */
class LegalPagesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    public function test_every_legal_and_info_page_is_public_and_renders(): void
    {
        $expected = [
            'legal.terms' => 'Términos y condiciones',
            'legal.privacy' => 'Aviso de privacidad',
            'legal.refunds' => 'Política de reembolsos',
            'legal.cookies' => 'Política de cookies',
            'legal.faq' => 'Preguntas frecuentes',
            'about' => 'Nosotros',
            'how-it-works' => 'Cómo funciona',
        ];
        $this->assertSame(array_keys($expected), array_keys(PageController::PAGES));

        foreach ($expected as $route => $title) {
            $response = $this->get(route($route));
            $response->assertOk();
            $response->assertSee($title);
            $response->assertSee('Aviso de cookies', false);   // cookie banner is on every public page
            $response->assertSee('Pagos seguros con Mercado Pago');
        }
    }

    public function test_pages_do_not_expose_unresolved_blade_or_placeholders(): void
    {
        foreach (array_keys(PageController::PAGES) as $route) {
            $html = $this->get(route($route))->getContent();

            $this->assertStringNotContainsString('{contact}', $html, $route);
            $this->assertStringNotContainsString('@if', $html, $route);
            $this->assertStringNotContainsString('{{', $html, $route);
        }
    }

    public function test_legal_pages_cover_the_mandatory_topics(): void
    {
        $this->get(route('legal.privacy'))->assertSee('LFPDPPP')->assertSee('ARCO')->assertSee('Mercado Pago')->assertSee('Gemini');
        $this->get(route('legal.terms'))->assertSee('licencia personal')->assertSee('Tutor con inteligencia artificial')->assertSee('Ley Federal de Protección al Consumidor');
        $this->get(route('legal.cookies'))->assertSee('laravel_session');
    }

    public function test_refund_window_follows_configuration(): void
    {
        config(['academy.refund_days' => 15]);

        $this->get(route('legal.refunds'))->assertSee('Garantía de 15 días');
        $this->get(route('legal.faq'))->assertSee('15 días naturales');
        $this->get(route('about'))->assertSee('reembolso de 15 días');
    }

    public function test_contact_email_comes_from_configuration(): void
    {
        config(['academy.contact_email' => 'hola@academia.test']);

        $this->get(route('legal.privacy'))->assertSee('mailto:hola@academia.test', false);
        $this->get(route('legal.refunds'))->assertSee('hola@academia.test');
    }

    public function test_faq_publishes_valid_structured_data(): void
    {
        $html = $this->get(route('legal.faq'))->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $match);
        $json = json_decode($match[1] ?? '', true);

        $this->assertNotNull($json, 'FAQ JSON-LD is not valid JSON');
        $this->assertSame('FAQPage', $json['@type']);
        $this->assertGreaterThanOrEqual(8, count($json['mainEntity']));
    }

    public function test_home_footer_links_to_every_legal_page(): void
    {
        $home = $this->get('/')->assertOk();

        foreach (['legal.terms', 'legal.privacy', 'legal.refunds', 'legal.cookies', 'legal.faq', 'about', 'how-it-works'] as $route) {
            $home->assertSee(route($route, [], false), false);
        }
    }

    public function test_sitemap_lists_pages_and_only_active_courses(): void
    {
        $active = $this->course();
        $draft = Course::create([
            'instructor_id' => $this->tutor()->id, 'title' => 'Sitemap Draft ' . uniqid(), 'description' => 'x',
            'category_id' => $this->category()->id, 'sub_category_id' => $this->category()->id, 'type' => 'article',
            'level' => 'beginner', 'language_id' => 1, 'status' => 'draft',
        ]);

        $response = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml, 'sitemap is not valid XML');
        $locs = collect(iterator_to_array($xml->url, false))->map(fn ($u) => (string) $u->loc);
        $this->assertTrue($locs->contains(route('legal.terms')));
        $this->assertTrue($locs->contains(route('courses.course-detail', $active->slug)));
        $this->assertFalse($locs->contains(route('courses.course-detail', $draft->slug)), 'draft course leaked into the sitemap');
    }

    public function test_robots_blocks_private_areas_and_points_to_the_sitemap(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /admin/', $robots);
        $this->assertStringContainsString('Disallow: /aula/', $robots);
        $this->assertStringContainsString('Sitemap: ' . route('sitemap'), $robots);
    }
}
