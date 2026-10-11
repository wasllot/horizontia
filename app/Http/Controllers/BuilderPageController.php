<?php

namespace App\Http\Controllers;

use App\Models\SitePage;
use App\Services\PageTokens;
use App\Services\Pages\PageContentRenderer;
use Illuminate\Http\Request;
use Larabuild\Pagebuilder\Models\Page;
use Modules\Courses\Models\Course;

/**
 * Public renderer for pages made with the page builder (legal/info pages, any custom slug and the
 * informative pages attached to a course). Replaces the package's catch-all so we can resolve tokens,
 * hide drafts and send SEO data.
 */
class BuilderPageController extends Controller
{
    public function show(Request $request, ?string $any = null)
    {
        $slug = trim($any ?? $request->path(), '/');
        abort_if($slug === '' || str_starts_with($slug, 'admin/'), 404);
        view()->share(['htCourse' => null, 'htCourseLinks' => null]);   // never inherit a previous course page's context

        return $this->render($this->find($slug, (bool) $request->query('preview') && auth()->user()?->hasRole('admin')));
    }

    /** /curso/{slug}/info/{page}: a builder page shown in the context of one course. */
    public function course(Request $request, string $slug, string $page)
    {
        $course = Course::where('slug', $slug)->where('status', Course::STATUSES['active'])->firstOrFail();
        $link = $course->infoPages()->whereHas('page', fn ($q) => $q->where('slug', $page)->where('status', 'published'))->firstOrFail();

        view()->share('htCourse', $course);
        view()->share('htCourseLinks', $course->infoPages()->with('page:id,slug,status')->get());

        return $this->render($link->page, $course);
    }

    private function find(string $slug, bool $preview): Page
    {
        return Page::where('slug', $slug)->when(!$preview, fn ($q) => $q->where('status', 'published'))->firstOrFail();
    }

    private function render(Page $page, ?Course $course = null)
    {
        // Pages made with the visual editor (GrapesJS) are printed from their stored HTML/CSS.
        $visual = SitePage::find($page->id);
        if ($visual?->usesVisualEditor()) {
            if ($course) {
                $page->title = $page->title . ' · ' . $course->title;
            }

            $data = app(PageContentRenderer::class)->render($visual, $course);
            // Banner propio (Administración › Banners de páginas): reemplaza la portada del contenido. No aplica a las páginas de un curso.
            $banners = app(\App\Services\PageBannerService::class);
            $banner = $course ? null : $banners->forPage($visual);
            if ($banner) {
                $data['html'] = $banners->stripHero($data['html']);
            }

            return response(view('pages.visual', ['page' => $page, 'banner' => $banner] + $data));
        }

        setCurrentPageId($page->id);
        $sections = PageTokens::apply(view('pagebuilder::components.page-components', compact('page'))->render(), $course);

        if ($course) {
            $page->title = $page->title . ' · ' . $course->title;
        }

        return response(view('pagebuilder::page', ['page' => $page, 'pageSections' => $sections]));
    }
}
