<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\SeoEntry;
use App\Services\BuilderPageFactory;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Admin-managed SEO (per URL, per route, per pattern) and the dynamic header/footer menus.
 */
class SeoAndMenusTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true]);
        MenuService::flush();
        \Illuminate\Support\Facades\Cache::forget(SeoEntry::CACHE_KEY);
    }

    // ---------------------------------------------------------------- SEO

    public function test_a_path_override_changes_title_description_robots_canonical_and_social_image(): void
    {
        SeoEntry::create([
            'match_type' => 'path', 'match_value' => 'nosotros', 'is_active' => true,
            'title' => 'Conoce a la Academia', 'description' => 'Descripción SEO personalizada.', 'robots' => 'noindex, follow',
            'canonical' => 'https://academia.test/nosotros', 'og_image' => 'https://academia.test/og.jpg',
        ]);

        $this->get('/nosotros')->assertOk()
            ->assertSee('<title>', false)->assertSee('Conoce a la Academia', false)
            ->assertSee('Descripción SEO personalizada.', false)
            ->assertSee('noindex, follow', false)
            ->assertSee('https://academia.test/nosotros', false)
            ->assertSee('https://academia.test/og.jpg', false);
    }

    public function test_a_custom_seo_title_is_the_whole_window_title_without_the_site_prefix(): void
    {
        SeoEntry::create(['match_type' => 'path', 'match_value' => 'nosotros', 'is_active' => true, 'title' => 'N']);
        SeoEntry::create(['match_type' => 'path', 'match_value' => '/', 'is_active' => true, 'title' => 'Inicio propio']);

        $this->get('/nosotros')->assertOk()->assertSee('<title>N</title>', false)->assertSee('property="og:title" content="N"', false);
        $this->get('/')->assertOk()->assertSee('<title>Inicio propio</title>', false);
    }

    public function test_precedence_is_exact_path_then_route_then_longest_pattern(): void
    {
        $this->assertNull(app(\App\Services\SeoOverrideService::class)->match('curso/x', 'courses.course-detail'));

        SeoEntry::create(['match_type' => 'pattern', 'match_value' => 'curso/*', 'is_active' => true, 'title' => 'patrón corto']);
        SeoEntry::create(['match_type' => 'pattern', 'match_value' => 'curso/demo-*', 'is_active' => true, 'title' => 'patrón largo']);
        $svc = app(\App\Services\SeoOverrideService::class);
        $this->assertSame('patrón largo', $svc->match('curso/demo-uno', 'courses.course-detail')['title']);
        $this->assertSame('patrón corto', $svc->match('curso/otro', 'courses.course-detail')['title']);

        SeoEntry::create(['match_type' => 'route', 'match_value' => 'courses.course-detail', 'is_active' => true, 'title' => 'por ruta']);
        $this->assertSame('por ruta', $svc->match('curso/demo-uno', 'courses.course-detail')['title']);

        SeoEntry::create(['match_type' => 'path', 'match_value' => 'curso/demo-uno', 'is_active' => true, 'title' => 'exacta']);
        $this->assertSame('exacta', $svc->match('curso/demo-uno', 'courses.course-detail')['title']);
    }

    public function test_inactive_entries_are_ignored(): void
    {
        SeoEntry::create(['match_type' => 'path', 'match_value' => 'nosotros', 'is_active' => false, 'title' => 'No debe verse']);

        $this->get('/nosotros')->assertOk()->assertDontSee('No debe verse', false);
    }

    public function test_a_course_page_can_be_customised_and_private_areas_stay_noindex(): void
    {
        $course = $this->course();
        SeoEntry::create(['match_type' => 'path', 'match_value' => 'curso/' . $course->slug, 'is_active' => true, 'title' => 'Título SEO del curso']);

        $this->get('/curso/' . $course->slug)->assertOk()->assertSee('Título SEO del curso', false);
        $this->actingAs($this->admin())->get('/admin/estadisticas')->assertOk()->assertSee('noindex', false);
        $this->actingAs($this->student())->get('/mis-cursos')->assertOk()->assertSee('noindex', false);
    }

    public function test_noindex_pages_are_left_out_of_the_sitemap(): void
    {
        BuilderPageFactory::upsert('oferta-privada', 'Oferta', 'Oferta', 'x', [['ht-hero', ['heading' => 'Privada']]]);
        BuilderPageFactory::upsert('oferta-publica', 'Oferta', 'Oferta', 'x', [['ht-hero', ['heading' => 'Pública']]]);
        BuilderPageFactory::upsert('oferta-borrador', 'Oferta', 'Oferta', 'x', [['ht-hero', ['heading' => 'Borrador']]], 'draft');
        SeoEntry::create(['match_type' => 'path', 'match_value' => 'oferta-privada', 'is_active' => true, 'robots' => 'noindex, nofollow']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString(url('/oferta-publica'), $xml);
        $this->assertStringNotContainsString(url('/oferta-privada'), $xml);
        $this->assertStringNotContainsString(url('/oferta-borrador'), $xml);
        $this->assertStringContainsString(route('legal.terms'), $xml);
    }

    public function test_seo_screen_is_admin_only_and_saves_validates_and_removes_entries(): void
    {
        $this->get('/admin/seo')->assertRedirect();
        $this->actingAs($this->student())->get('/admin/seo')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/seo')->assertOk()->assertSee('SEO de la plataforma');

        $c = Livewire::actingAs($this->admin())->test(\App\Livewire\Pages\Admin\Seo\SeoManager::class)
            ->call('edit', 'nosotros')
            ->set('title', 'Título nuevo')->set('robots', 'cualquier-cosa')->call('save')->assertHasErrors('robots')
            ->set('robots', 'noindex, follow')->set('schema', '{no es json')->call('save')->assertHasErrors('schema')
            ->set('schema', '{"@context":"https://schema.org","@type":"AboutPage"}')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('seo_entries', ['match_type' => 'path', 'match_value' => 'nosotros', 'title' => 'Título nuevo', 'robots' => 'noindex, follow']);
        $this->get('/nosotros')->assertSee('Título nuevo', false)->assertSee('"@type":"AboutPage"', false);

        // Clearing every field removes the entry instead of keeping an empty override.
        $c->call('edit', 'nosotros')->set('title', '')->set('robots', '')->set('schema', '')->call('save');
        $this->assertDatabaseMissing('seo_entries', ['match_value' => 'nosotros']);
    }

    public function test_global_default_description_is_used_when_a_page_has_none(): void
    {
        Livewire::actingAs($this->admin())->test(\App\Livewire\Pages\Admin\Seo\SeoManager::class)
            ->set('defaultDescription', 'Descripción global de la academia')->call('saveGlobal');

        $this->get('/contacto')->assertOk()->assertSee('Descripción global de la academia', false);
    }

    // -------------------------------------------------------------- Menus

    private function headerMenu(array $items): Menu
    {
        Menu::where('location', 'header')->each(fn ($m) => $m->delete());
        $menu = Menu::create(['name' => 'Menú de prueba', 'location' => 'header']);
        foreach ($items as $i => $item) {
            MenuItem::create(['menu_id' => $menu->id, 'sort' => $i] + $item);
        }

        return $menu;
    }

    public function test_public_header_is_rendered_from_the_menu_builder_including_dropdowns(): void
    {
        $menu = $this->headerMenu([
            ['label' => 'Inicio', 'route' => 'home', 'type' => 'route'],
            ['label' => 'Recursos', 'route' => '#', 'type' => 'custom'],
            ['label' => 'Guía externa', 'route' => 'https://example.org/guia', 'type' => 'custom', 'class' => '_blank'],
        ]);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Preguntas', 'route' => 'legal.faq', 'type' => 'route', 'parent_id' => MenuItem::where('label', 'Recursos')->value('id'), 'sort' => 0]);

        $this->get('/cursos')->assertOk()
            ->assertSeeInOrder(['Inicio', 'Recursos', 'Guía externa'])
            ->assertSee('Preguntas')
            ->assertSee('href="https://example.org/guia"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('ht-nav__dropdown', false);
    }

    public function test_menu_changes_show_up_immediately_despite_the_cache(): void
    {
        $menu = $this->headerMenu([['label' => 'Inicio', 'route' => 'home', 'type' => 'route']]);
        $this->get('/cursos')->assertOk()->assertDontSee('Oferta flash');

        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Oferta flash', 'route' => '/promo', 'type' => 'custom', 'sort' => 5]);
        $this->get('/cursos')->assertSee('Oferta flash');

        MenuItem::where('label', 'Oferta flash')->first()->delete();
        $this->get('/cursos')->assertDontSee('Oferta flash');
    }

    public function test_marketplace_links_are_hidden_in_academy_mode_and_shown_otherwise(): void
    {
        $this->headerMenu([
            ['label' => 'Inicio', 'route' => 'home', 'type' => 'route'],
            ['label' => 'Buscar tutores', 'route' => 'find-tutors', 'type' => 'route'],
        ]);

        config(['academy.private_mode' => true]);
        $labels = collect(app(MenuService::class)->header())->pluck('label');
        $this->assertTrue($labels->contains('Inicio'));
        $this->assertFalse($labels->contains('Buscar tutores'));

        config(['academy.private_mode' => false]);
        $this->assertTrue(collect(app(MenuService::class)->header())->pluck('label')->contains('Buscar tutores'));
    }

    public function test_site_never_loses_its_navigation_when_no_menu_exists(): void
    {
        Menu::query()->each(fn ($m) => $m->delete());
        MenuItem::query()->delete();
        MenuService::flush();

        $this->get('/cursos')->assertOk()->assertSee('Cómo funciona')->assertSee('Contacto');
        $columns = app(MenuService::class)->footerColumns();
        $this->assertSame(['Explorar', 'Ayuda y legal'], array_column($columns, 'title'));
    }

    public function test_urls_resolve_for_routes_relative_paths_and_demo_localhost_links(): void
    {
        $svc = app(MenuService::class);
        $mk = fn (string $type, string $route) => new MenuItem(['type' => $type, 'route' => $route]);

        $this->assertSame(route('contacto'), $svc->url($mk('route', 'contacto')));
        $this->assertSame('#', $svc->url($mk('route', 'ruta.que.no.existe')));
        $this->assertSame(url('/promo'), $svc->url($mk('custom', 'promo')));
        $this->assertSame(url('/promo?x=1'), $svc->url($mk('custom', 'http://localhost/promo?x=1')));
        $this->assertSame('https://example.org/a', $svc->url($mk('custom', 'https://example.org/a')));
        $this->assertSame('#', $svc->url($mk('custom', '')));
    }

    public function test_footer_columns_follow_the_footer_menus(): void
    {
        Menu::where('location', 'footer')->each(fn ($m) => $m->delete());
        $menu = Menu::create(['name' => 'Recursos extra', 'location' => 'footer']);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Guía de estudio', 'route' => '/guia', 'type' => 'custom', 'sort' => 0]);

        $this->get('/nosotros')->assertOk()->assertSee('Recursos extra')->assertSee('Guía de estudio');
    }

    public function test_menu_screen_is_admin_only_and_lists_platform_links(): void
    {
        $this->get('/admin/menus')->assertRedirect();
        $this->actingAs($this->student())->get('/admin/menus')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/menus')->assertOk();

        $links = MenuService::platformLinks();
        $this->assertArrayHasKey('legal.refunds', $links);
        $this->assertArrayNotHasKey('find-tutors', $links);   // academy mode
    }

    // ------------------------------------------------------ Menu builder

    private function builder(?int $menuId = null)
    {
        $menu = $menuId ? Menu::find($menuId) : $this->headerMenu([['label' => 'Inicio', 'route' => 'home', 'type' => 'route']]);

        return Livewire::actingAs($this->admin())->test(\App\Livewire\Pages\Admin\Menu\MenuBuilder::class)->call('select', $menu->id);
    }

    public function test_builder_adds_platform_pages_courses_and_custom_links_and_the_site_updates(): void
    {
        $page = BuilderPageFactory::upsert('beneficios', 'Beneficios', 'Beneficios', 'x', [['ht-hero', ['heading' => 'B']]]);
        $course = $this->course();

        $this->builder()
            ->set('platformChoices', ['legal.faq', 'ruta.inexistente'])->call('addPlatform')
            ->set('pageChoices', [$page->id])->call('addPages')
            ->set('courseChoice', $course->id)->call('addCourse')
            ->set('customLabel', 'Blog')->set('customUrl', '/blog')->call('addCustom')->assertHasNoErrors();

        $labels = MenuItem::where('menu_id', Menu::where('name', 'Menú de prueba')->value('id'))->orderBy('sort')->pluck('label')->all();
        $this->assertSame(['Inicio', 'Preguntas frecuentes', 'Beneficios', $course->title, 'Blog'], $labels);
        $this->assertDatabaseHas('menu_items', ['route' => '/beneficios', 'type' => 'page']);
        $this->assertDatabaseHas('menu_items', ['route' => '/curso/' . $course->slug]);

        $this->get('/cursos')->assertSee('Beneficios')->assertSee('href="' . url('/beneficios') . '"', false);
    }

    public function test_builder_rejects_dangerous_or_malformed_urls(): void
    {
        $c = $this->builder();
        foreach (['javascript:alert(1)', 'data:text/html,x', 'ftp://x.org', 'sin-barra'] as $bad) {
            $c->set('customLabel', 'X')->set('customUrl', $bad)->call('addCustom')->assertHasErrors('customUrl');
        }
        foreach (['/ok', '#ancla', 'https://example.org', 'mailto:a@b.com', 'tel:+5255555'] as $good) {
            $c->set('customLabel', 'X')->set('customUrl', $good)->call('addCustom')->assertHasNoErrors();
        }
        $this->assertSame(0, MenuItem::where('route', 'like', 'javascript:%')->count());
    }

    public function test_builder_reorders_nests_and_flattens_to_one_level(): void
    {
        $menu = $this->headerMenu([
            ['label' => 'A', 'route' => '/a', 'type' => 'custom'], ['label' => 'B', 'route' => '/b', 'type' => 'custom'], ['label' => 'C', 'route' => '/c', 'type' => 'custom'],
        ]);
        [$a, $b, $c] = MenuItem::where('menu_id', $menu->id)->orderBy('id')->pluck('id')->all();

        // C first, then B with A as its dropdown child.
        $this->builder($menu->id)->call('reorder', [['id' => $c, 'children' => []], ['id' => $b, 'children' => [['id' => $a]]]]);

        $this->assertSame(['C', 'B'], collect(app(MenuService::class)->header())->pluck('label')->all());
        $this->assertSame(['A'], collect(app(MenuService::class)->header())->firstWhere('label', 'B')['children'] ? array_column(collect(app(MenuService::class)->header())->firstWhere('label', 'B')['children'], 'label') : []);
        $this->assertSame($b, (int) MenuItem::find($a)->parent_id);

        // Ids from another menu are ignored.
        $other = Menu::create(['name' => 'Otro', 'location' => 'footer']);
        $foreign = MenuItem::create(['menu_id' => $other->id, 'label' => 'Ajeno', 'route' => '/x', 'type' => 'custom', 'sort' => 0]);
        $this->builder($menu->id)->call('reorder', [['id' => $foreign->id, 'children' => []], ['id' => $c, 'children' => []]]);
        $this->assertSame($other->id, (int) $foreign->fresh()->menu_id);
        $this->assertNull($foreign->fresh()->parent_id);
    }

    public function test_builder_edits_removes_items_and_children_and_flushes_the_public_cache(): void
    {
        $menu = $this->headerMenu([['label' => 'Padre', 'route' => '/p', 'type' => 'custom']]);
        $parent = MenuItem::where('menu_id', $menu->id)->first();
        MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $parent->id, 'label' => 'Hijo', 'route' => '/h', 'type' => 'custom', 'sort' => 0]);
        $this->get('/cursos')->assertSee('Hijo');

        $c = $this->builder($menu->id)->call('edit', $parent->id)->set('editLabel', 'Madre')->set('editUrl', '/m')->call('saveItem')->assertHasNoErrors();
        $this->assertDatabaseHas('menu_items', ['id' => $parent->id, 'label' => 'Madre', 'route' => '/m']);
        $this->get('/cursos')->assertSee('Madre')->assertDontSee('Padre');

        $c->call('toggleBlank', $parent->id);
        $this->assertSame('_blank', MenuItem::find($parent->id)->class);

        $c->call('removeItem', $parent->id);
        $this->assertSame(0, MenuItem::where('menu_id', $menu->id)->count(), 'children must go with their parent');
        $this->get('/cursos')->assertDontSee('Hijo')->assertDontSee('Madre');
    }

    public function test_builder_creates_renames_and_deletes_menus_and_validates_names(): void
    {
        $c = Livewire::actingAs($this->admin())->test(\App\Livewire\Pages\Admin\Menu\MenuBuilder::class)
            ->call('startCreating')->set('newMenuName', '')->call('createMenu')->assertHasErrors('newMenuName')
            ->set('newMenuName', 'Legal extra')->set('newMenuLocation', 'footer')->call('createMenu')->assertHasNoErrors();

        $this->assertDatabaseHas('menu', ['name' => 'Legal extra', 'location' => 'footer']);

        $c->set('menuLocation', 'sidebar')->call('saveMenu')->assertHasErrors('menuLocation');
        $c->set('menuName', 'Legal 2')->set('menuLocation', 'header')->call('saveMenu')->assertHasNoErrors();
        $this->assertDatabaseHas('menu', ['name' => 'Legal 2', 'location' => 'header']);

        $c->call('deleteMenu');
        $this->assertDatabaseMissing('menu', ['name' => 'Legal 2']);
    }

    public function test_only_admins_can_use_the_builder(): void
    {
        foreach ([\App\Livewire\Pages\Admin\Menu\MenuBuilder::class, \App\Livewire\Pages\Admin\Seo\SeoManager::class, \App\Livewire\Pages\Admin\CoursePages\CoursePages::class] as $component) {
            Livewire::actingAs($this->student())->test($component)->assertForbidden();
        }
    }
}
