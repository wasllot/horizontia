<?php

namespace Tests\Feature;

use App\Livewire\Frontend\Blogs;
use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Public blog: index (search, chips, load more), article page (reading time, share, related, courses, JSON-LD) and SEO.
 */
class BlogTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    private function makePost(string $title, string $body, string $status = 'published', ?BlogCategory $cat = null, array $extra = []): Blog
    {
        $blog = Blog::create($extra + [
            'title' => $title, 'slug' => \Illuminate\Support\Str::slug($title) . '-' . uniqid(), 'description' => $body,
            'status' => $status, 'author_id' => $this->admin()->id,
        ]);
        if ($cat) {
            $blog->categories()->attach($cat->id);
        }

        return $blog;
    }

    private function category(string $name): BlogCategory
    {
        return BlogCategory::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(), 'status' => 'active']);
    }

    public function test_reading_time_and_excerpt_are_computed_from_the_body(): void
    {
        $short = $this->makePost('Corto', '<p>' . str_repeat('palabra ', 50) . '</p>');
        $long = $this->makePost('Largo', '<p>' . str_repeat('palabra ', 650) . '</p>');

        $this->assertSame(1, $short->reading_minutes);
        $this->assertSame(4, $long->reading_minutes);   // 650 words / 200
        $this->assertSame('palabra palabra palabra…', $short->excerpt(3));
        $this->assertStringNotContainsString('<p>', $long->excerpt());
    }

    public function test_index_lists_only_published_posts_in_the_first_response_with_reading_time(): void
    {
        $this->makePost('Guía publicada sobre hábitos de estudio', '<p>Contenido útil.</p>');
        $this->makePost('Borrador que no debe verse', '<p>Secreto</p>', 'draft');

        $html = $this->get('/blog')->assertOk()->assertSee('Guía publicada sobre hábitos de estudio')->assertDontSee('Borrador que no debe verse')->getContent();
        $this->assertStringContainsString('min de lectura', $html);
        $this->assertStringNotContainsString('am-select2', $html);   // no legacy select widgets needed to browse
    }

    public function test_category_chips_and_search_filter_the_index_and_urls_are_shareable(): void
    {
        $salud = $this->category('Salud mental');
        $tec = $this->category('Tecnología');
        $this->makePost('Cómo dormir mejor para estudiar', '<p>Sueño</p>', 'published', $salud);
        $this->makePost('Herramientas de IA para estudiar', '<p>IA</p>', 'published', $tec);

        Livewire::test(Blogs::class)
            ->call('setCategory', $salud->slug)->assertSee('Cómo dormir mejor para estudiar')->assertDontSee('Herramientas de IA para estudiar')
            ->call('setCategory', '')->assertSee('Herramientas de IA para estudiar')
            ->set('keyword', 'Herramientas')->assertSee('Herramientas de IA para estudiar')->assertDontSee('Cómo dormir mejor')
            ->set('keyword', 'zzz-inexistente')->assertSee('No encontramos artículos')->call('clearFilters')->assertSee('Cómo dormir mejor para estudiar');

        $this->get('/blog?category=' . $tec->slug)->assertOk()->assertSee('Herramientas de IA para estudiar')->assertDontSee('Cómo dormir mejor para estudiar');
        $this->get('/blog?q=dormir')->assertOk()->assertSee('Cómo dormir mejor para estudiar')->assertDontSee('Herramientas de IA para estudiar');
    }

    public function test_search_input_cannot_inject_sql_or_html(): void
    {
        $this->makePost('Artículo normal', '<p>x</p>');

        $this->get('/blog?q=' . urlencode("' OR 1=1 --"))->assertOk()->assertSee('No encontramos artículos');
        $this->get('/blog?q=' . urlencode('<script>alert(1)</script>'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_load_more_adds_articles_and_is_capped(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->makePost("Artículo número $i", '<p>x</p>');
        }
        $c = Livewire::test(Blogs::class)->assertSet('perPage', Blogs::PER_PAGE)->call('loadMore')->assertSet('perPage', Blogs::PER_PAGE * 2);
        for ($i = 0; $i < 20; $i++) {
            $c->call('loadMore');
        }
        $c->assertSet('perPage', 90);
    }

    public function test_article_page_has_h1_share_related_courses_and_structured_data(): void
    {
        $cat = $this->category('Estudio');
        $main = $this->makePost('Técnicas de estudio que funcionan', '<p>' . str_repeat('texto ', 300) . '</p>', 'published', $cat, ['meta_description' => 'Descripción SEO del artículo']);
        $this->makePost('Otro artículo relacionado', '<p>y</p>', 'published', $cat);
        $course = $this->course();

        $html = $this->get('/blog/' . $main->slug)->assertOk()
            ->assertSee('<h1>Técnicas de estudio que funcionan</h1>', false)
            ->assertSee('min de lectura')->assertSee('Compartir')->assertSee('wa.me', false)
            ->assertSee('Artículos relacionados')->assertSee('Otro artículo relacionado')
            ->assertSee('Sigue aprendiendo con nuestros cursos')->assertSee($course->title)
            ->assertSee('Descripción SEO del artículo', false)->assertSee('og:type" content="article"', false)
            ->getContent();

        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
        $types = array_map(fn ($j) => json_decode($j, true)['@type'] ?? null, $m[1]);
        $this->assertContains('BlogPosting', $types);
        $this->assertContains('BreadcrumbList', $types);
        $posting = json_decode($m[1][array_search('BlogPosting', $types)], true);
        $this->assertSame('es-MX', $posting['inLanguage']);
        $this->assertSame('Técnicas de estudio que funcionan', $posting['headline']);
    }

    public function test_article_without_meta_fields_falls_back_to_title_and_excerpt(): void
    {
        $post = $this->makePost('Sin metadatos propios', '<p>Resumen automático del artículo para buscadores.</p>');

        $this->get('/blog/' . $post->slug)->assertOk()
            ->assertSee('<title>', false)->assertSee('Sin metadatos propios', false)->assertSee('Resumen automático del artículo para buscadores', false);
    }

    public function test_drafts_and_unknown_articles_are_not_public_and_views_count_once_per_session(): void
    {
        $draft = $this->makePost('Aún no publicado', '<p>x</p>', 'draft');
        $this->get('/blog/' . $draft->slug)->assertNotFound();
        $this->get('/blog/no-existe-este-articulo')->assertNotFound();

        $post = $this->makePost('Visitas únicas', '<p>x</p>');
        $before = (int) $post->views_count;
        $this->get('/blog/' . $post->slug)->assertOk();
        $this->get('/blog/' . $post->slug)->assertOk();   // same session
        $this->assertSame($before + 1, (int) $post->fresh()->views_count);
    }

    public function test_sitemap_lists_the_blog_index_and_published_articles_only(): void
    {
        $ok = $this->makePost('Aparece en el sitemap', '<p>x</p>');
        $draft = $this->makePost('No aparece en el sitemap', '<p>x</p>', 'draft');

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString(route('blogs'), $xml);
        $this->assertStringContainsString(route('blog-details', $ok->slug), $xml);
        $this->assertStringNotContainsString($draft->slug, $xml);
    }
}
