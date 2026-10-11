<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\PageBanners\PageBanners;
use App\Models\PageBanner;
use App\Models\SitePage;
use App\Services\ConfigTransfer\ConfigTransfer;
use App\Services\PageBannerService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Banners de páginas estáticas: título, subtítulo e imagen editables sin tocar el editor visual. */
class PageBannersTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Storage::fake('public');
        config(['filesystems.default' => 'local']);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function page(string $slug = 'pagina-banner'): SitePage
    {
        $hero = '<section class="pb-themesection"><div class="container-fluid"><div class="row"><div class="col-12"><section class="ht-block ht-hero ht-hero--light"><div class="container"><h1>Título original</h1><p>Subtítulo original.</p></div></section></div></div></div></section>';
        $body = '<section class="pb-themesection"><div class="container-fluid"><div class="row"><div class="col-12"><section class="ht-block ht-rich"><div class="container"><p>Contenido de la página.</p></div></section></div></div></div></section>';

        return SitePage::create(['name' => 'Página de prueba', 'title' => 'Página de prueba', 'slug' => $slug, 'status' => 'published', 'settings' => json_encode([]), 'gjs_html' => $hero . $body, 'gjs_css' => '']);
    }

    private function banners()
    {
        return Livewire::actingAs($this->admin())->test(PageBanners::class);
    }

    public function test_without_a_custom_banner_the_page_is_exactly_as_before(): void
    {
        $p = $this->page();

        $this->get('/' . $p->slug)->assertOk()->assertSee('Título original')->assertSee('Contenido de la página.')->assertDontSee('data-testid="page-banner"', false);
    }

    public function test_the_admin_first_sees_the_current_title_and_subtitle_to_edit(): void
    {
        $p = $this->page();

        $this->banners()->call('edit', PageBannerService::keyFor($p))->assertSet('title', 'Título original')->assertSet('subtitle', 'Subtítulo original.');
    }

    public function test_a_custom_banner_replaces_the_original_cover_with_the_new_text_and_image(): void
    {
        $p = $this->page();
        $page = $this->banners()->call('edit', PageBannerService::keyFor($p))
            ->set('title', 'Conoce nuestra academia')->set('subtitle', 'Una frase nueva')->set('upload', UploadedFile::fake()->image('banner.jpg', 1600, 600))->set('overlay', 40)->set('align', 'center')
            ->call('save')->assertHasNoErrors()->assertSet('editingKey', null);

        $b = PageBanner::where('page_key', PageBannerService::keyFor($p))->firstOrFail();
        $this->assertStringStartsWith('page-banners/', $b->image);
        Storage::disk('public')->assertExists($b->image);

        $html = $this->get('/' . $p->slug)->assertOk()->getContent();
        $this->assertStringContainsString('Conoce nuestra academia', $html);
        $this->assertStringContainsString('Una frase nueva', $html);
        $this->assertStringContainsString('hz-banner--image', $html);
        $this->assertStringContainsString('hz-banner--center', $html);
        $this->assertStringContainsString('--hz-banner-overlay: 0.4', $html);
        $this->assertStringNotContainsString('Título original', $html, 'the old cover block is gone, not duplicated');
        $this->assertStringContainsString('Contenido de la página.', $html);
    }

    public function test_disabling_or_restoring_brings_back_the_original_cover(): void
    {
        $p = $this->page();
        $key = PageBannerService::keyFor($p);
        $this->banners()->call('edit', $key)->set('title', 'Nuevo')->call('save');
        $this->get('/' . $p->slug)->assertSee('Nuevo')->assertDontSee('Título original');

        $this->banners()->call('edit', $key)->set('enabled', false)->call('save');
        $this->get('/' . $p->slug)->assertSee('Título original')->assertDontSee('data-testid="page-banner"', false);

        $this->banners()->call('edit', $key)->set('enabled', true)->set('title', 'Otra vez')->call('save');
        $this->banners()->call('reset_', $key);
        $this->assertNull(PageBanner::where('page_key', $key)->first());
        $this->get('/' . $p->slug)->assertSee('Título original');
    }

    public function test_replacing_or_removing_the_image_deletes_the_old_file(): void
    {
        $p = $this->page();
        $key = PageBannerService::keyFor($p);
        $this->banners()->call('edit', $key)->set('title', 'T')->set('upload', UploadedFile::fake()->image('a.jpg', 1600, 600))->call('save');
        $first = PageBanner::where('page_key', $key)->value('image');

        $this->banners()->call('edit', $key)->set('upload', UploadedFile::fake()->image('b.jpg', 1600, 600))->call('save');
        $second = PageBanner::where('page_key', $key)->value('image');
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->banners()->call('edit', $key)->call('removeCurrentImage')->call('save');
        $this->assertNull(PageBanner::where('page_key', $key)->value('image'));
        Storage::disk('public')->assertMissing($second);
    }

    public function test_the_form_validates_title_image_type_and_size(): void
    {
        $p = $this->page();
        $page = $this->banners()->call('edit', PageBannerService::keyFor($p));

        $page->set('title', '')->call('save')->assertHasErrors('title');
        $page->set('title', 'Ok')->set('upload', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))->assertHasErrors('upload')->assertSet('upload', null);
        $page->set('upload', UploadedFile::fake()->image('big.jpg')->size(5000))->assertHasErrors('upload');
        $page->set('title', str_repeat('a', 141))->call('save')->assertHasErrors('title');
    }

    public function test_html_in_the_text_is_stripped_and_escaped(): void
    {
        $p = $this->page();
        $this->banners()->call('edit', PageBannerService::keyFor($p))->set('title', '<script>alert(1)</script>Hola')->set('subtitle', '<b>x</b>')->call('save');

        $html = $this->get('/' . $p->slug)->getContent();
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringContainsString('alert(1)Hola', $html);
    }

    public function test_the_contact_page_uses_its_banner_and_keeps_the_default_when_none(): void
    {
        $this->get('/contacto')->assertOk()->assertSee('data-testid="page-banner"', false)->assertSee('Contacto');

        $this->banners()->call('edit', 'system:contacto')->set('title', 'Hablemos')->set('subtitle', 'Te respondemos en 24 h')->call('save');
        $this->get('/contacto')->assertOk()->assertSee('Hablemos')->assertSee('Te respondemos en 24 h');
    }

    public function test_course_pages_keep_their_own_cover_and_the_home_page_is_not_listed(): void
    {
        $p = $this->page();
        $this->banners()->call('edit', PageBannerService::keyFor($p))->set('title', 'Banner nuevo')->call('save');
        $this->assertNull(app(PageBannerService::class)->forPage(new SitePage(['id' => 999999])), 'no banner for a page without one');

        $this->banners()->assertSee('Página de prueba')->assertSee('Contacto');
    }

    public function test_banners_travel_with_the_pages_in_export_import_but_never_with_outside_images(): void
    {
        $p = $this->page('pagina-exportada');
        PageBanner::create(['page_key' => PageBannerService::keyFor($p), 'title' => 'Exportado', 'subtitle' => 'Sub', 'image' => 'page-banners/2026/10/abc.jpg', 'overlay' => 30, 'align' => 'center']);

        $export = (new ConfigTransfer())->export(['pages'], ['pages' => ['pagina-exportada']]);
        $row = $export['data']['pages'][0];
        $this->assertSame('Exportado', $row['banner']['title']);

        $p->delete();
        PageBanner::query()->delete();
        $row['banner']['image'] = 'https://evil.example/x.jpg';
        $export['data']['pages'][0] = $row;
        (new ConfigTransfer())->import($export, ['pages'], 'skip');

        $new = SitePage::where('slug', 'pagina-exportada')->firstOrFail();
        $b = PageBanner::where('page_key', PageBannerService::keyFor($new))->firstOrFail();
        $this->assertSame(['Exportado', null, 'center'], [$b->title, $b->image, $b->align], 'an external image URL is dropped');
    }

    public function test_only_admins_reach_the_banners_screen(): void
    {
        $this->assertNotSame(200, $this->actingAs($this->student())->get(route('admin.page-banners'))->getStatusCode());
        Livewire::actingAs($this->student())->test(PageBanners::class)->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.page-banners'))->assertOk()->assertSee('Banners de páginas');
    }
}
