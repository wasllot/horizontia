<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Models\Blog;
use App\Models\SeoEntry;
use Larabuild\Pagebuilder\Models\Page;
use Modules\Courses\Models\Course;

/**
 * Static legal / informational pages and the XML sitemap.
 */
class PageController extends Controller
{
    /** route name => [uri, view, sitemap priority] */
    public const PAGES = [
        'legal.terms'   => ['terminos-y-condiciones', 'frontend.pages.terminos', '0.3'],
        'legal.privacy' => ['aviso-de-privacidad', 'frontend.pages.privacidad', '0.3'],
        'legal.refunds' => ['politica-de-reembolsos', 'frontend.pages.reembolsos', '0.4'],
        'legal.cookies' => ['politica-de-cookies', 'frontend.pages.cookies', '0.2'],
        'legal.faq'     => ['preguntas-frecuentes', 'frontend.pages.faq', '0.6'],
        'about'         => ['nosotros', 'frontend.pages.nosotros', '0.5'],
        'how-it-works'  => ['como-funciona', 'frontend.pages.como-funciona', '0.6'],
    ];

    /** Páginas de PAGES que se sirven con una vista propia (no con el editor visual): no se editan en Admin › Páginas. */
    public const DEDICATED = ['about'];

    public function robots(): Response
    {
        $lines = ['User-agent: *'];
        foreach (['/admin/', '/finalizar-compra', '/aula/', '/contenido-scorm/', '/video-seguro/', '/estudiante/', '/tutor/', '/panel/', '/api/'] as $path) {
            $lines[] = 'Disallow: ' . $path;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . route('sitemap');

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap(): Response
    {
        $urls = [[route('home'), '1.0', null], [route('courses.search-courses'), '0.9', null], [route('blogs'), '0.6', null], [route('contacto'), '0.4', null]];

        // URLs an admin marked noindex in Admin > SEO never go in the sitemap.
        $noindex = SeoEntry::where('match_type', 'path')->where('is_active', true)->where('robots', 'like', '%noindex%')->pluck('match_value')->map(fn ($p) => '/' . trim($p, '/'))->all();
        $add = function (string $url, string $priority, $date = null) use (&$urls, $noindex) {
            if (!in_array('/' . trim(parse_url($url, PHP_URL_PATH) ?? '', '/'), $noindex, true)) {
                $urls[] = [$url, $priority, $date];
            }
        };

        $fixed = [];
        foreach (self::PAGES as $name => [$uri, , $priority]) {
            $fixed[$uri] = true;
            $add(route($name), $priority);
        }
        $add(route('events'), '0.8');
        // Any other published page made with the page builder.
        Page::where('status', 'published')->whereNotIn('slug', array_keys($fixed) + ['/'])->orderBy('slug')->get(['slug', 'updated_at'])
            ->each(fn ($p) => $add(url($p->slug), '0.5', $p->updated_at));

        Course::query()->where('status', Course::STATUSES['active'])->select('id', 'slug', 'updated_at')->with('infoPages.page:id,slug,status')->each(function ($course) use ($add) {
            $add(route('courses.course-detail', $course->slug), '0.8', $course->updated_at);
            foreach ($course->infoPages as $link) {
                if ($link->page?->status === 'published') {
                    $add(route('courses.info-page', [$course->slug, $link->page->slug]), '0.6', $course->updated_at);
                }
            }
        });

        Blog::query()->where('status', Blog::STATUSES['published'])->select('slug', 'updated_at')->each(fn ($b) => $add(route('blog-details', $b->slug), '0.5', $b->updated_at));

        return response()->view('frontend.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
