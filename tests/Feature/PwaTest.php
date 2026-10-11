<?php

namespace Tests\Feature;

use Tests\Feature\Support\SeedsPlatformData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** App instalable (PWA): manifiesto válido, service worker en la raíz, página sin conexión y etiquetas en cada plantilla. */
class PwaTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.pwa' => true]);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    public function test_the_manifest_is_installable(): void
    {
        $res = $this->get('/manifest.webmanifest')->assertOk();
        $this->assertStringContainsString('application/manifest+json', $res->headers->get('Content-Type'));
        $m = $res->json();

        $this->assertSame('standalone', $m['display']);
        $this->assertNotEmpty($m['name']);
        $this->assertStringStartsWith('/', $m['start_url']);
        $sizes = collect($m['icons'])->pluck('sizes')->all();
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertContains('maskable', collect($m['icons'])->pluck('purpose')->all());
        foreach ($m['icons'] as $icon) {
            $this->assertFileExists(public_path(parse_url($icon['src'], PHP_URL_PATH)));
        }
    }

    public function test_the_service_worker_is_served_from_the_root_with_full_scope_and_never_caches_pages_or_private_routes(): void
    {
        $res = $this->get('/sw.js')->assertOk()->assertHeader('Service-Worker-Allowed', '/');
        $this->assertStringContainsString('javascript', $res->headers->get('Content-Type'));
        $sw = $res->getContent();

        $this->assertStringContainsString("'/sin-conexion'", $sw);
        $this->assertStringContainsString('livewire|api|admin', $sw);
        $this->assertStringContainsString("req.method !== 'GET'", $sw);
        $this->assertStringContainsString("req.mode === 'navigate'", $sw);
    }

    public function test_the_offline_page_exists_and_is_not_cached_by_browsers(): void
    {
        $this->get('/sin-conexion')->assertOk()->assertSee('Estás sin conexión')->assertSee('Reintentar')->assertHeader('Cache-Control');
    }

    public function test_every_layout_links_the_manifest_and_registers_the_worker(): void
    {
        foreach (['/', '/iniciar-sesion'] as $url) {
            $this->get($url)->assertOk()->assertSee('rel="manifest"', false)->assertSee('name="theme-color"', false)->assertSee('js/pwa.js', false);
        }
        $this->actingAs($this->student())->get(route('courses.course-list'))->assertOk()->assertSee('rel="manifest"', false);
        $this->actingAs($this->admin())->get(route('admin.insights'))->assertOk()->assertSee('rel="manifest"', false);
    }

    public function test_it_can_be_switched_off(): void
    {
        config(['academy.pwa' => false]);

        $this->get('/manifest.webmanifest')->assertNotFound();
        $this->get('/sw.js')->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('rel="manifest"', false);
    }
}
