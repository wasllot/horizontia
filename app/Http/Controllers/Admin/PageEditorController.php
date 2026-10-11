<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageRevision;
use App\Models\SitePage;
use App\Models\CoursePage;
use App\Services\Pages\PageContentRenderer;
use App\Services\Pages\PageHtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Courses\Models\Category;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;

/**
 * Backend of the GrapesJS page editor (Admin > Páginas): editor screen, project load/save, page settings,
 * revisions and image uploads. Every action is admin-only (route group `role:admin`) and every save is sanitised.
 */
class PageEditorController extends Controller
{
    /** First URL segments that belong to the application and can never be a page slug. */
    public const RESERVED = ['admin', 'api', 'livewire', 'curso', 'cursos', 'aula', 'blog', 'estudiante', 'tutor', 'mis-cursos', 'gestionar-cursos', 'crear-curso',
        'finalizar-compra', 'gracias', 'iniciar-sesion', 'registro', 'olvide-mi-contrasena', 'restablecer-contrasena', 'contacto', 'storage', 'vendor', 'build', 'css', 'js',
        'images', 'fonts', 'panel', 'mercadopago', 'payfast', 'video-seguro', 'contenido-scorm', 'auth', 'messenger', 'sitemap.xml', 'robots.txt', 'pagar', 'pago', 'cerrar-sesion',
        'verificar-correo', 'confirmar-contrasena', 'scorm', 'favourite-tutor', 'find-tutors', 'buscar-tutores', 'google', 'remove-cart', 'switch-lang', 'switch-currency'];

    public function editor(SitePage $page)
    {
        abort_if($page->slug === '/', 404);

        return view('pages.editor', [
            'page' => $page,
            'config' => [
                'page' => $this->meta($page),
                'urls' => [
                    'project' => route('admin.pages.project', $page),
                    'save' => route('admin.pages.save', $page),
                    'meta' => route('admin.pages.meta', $page),
                    'media' => route('admin.pages.media'),
                    'blockPreview' => route('admin.pages.block-preview', $page),
                    'revisions' => route('admin.pages.revisions', $page),
                    'list' => route('admin.pages'),
                    'view' => $page->url(),
                ],
                'categories' => Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name'])->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])->all(),
                'canvas' => [
                    asset('css/bootstrap.min.css'),
                    asset('css/fonts.css'),
                    asset('css/horizontia-ui.css') . '?v=' . @filemtime(public_path('css/horizontia-ui.css')),
                    asset('css/events.css') . '?v=' . @filemtime(public_path('css/events.css')),
                    asset('css/page-editor-canvas.css') . '?v=' . @filemtime(public_path('css/page-editor-canvas.css')),
                ],
                'cities' => CourseSession::upcoming()->distinct()->orderBy('city')->pluck('city')->all(),
                'hasCourse' => CoursePage::where('page_id', $page->id)->exists(),
                'tokens' => ['[[company]]', '[[email]]', '[[address]]', '[[refund_days]]', '[[jurisdiction]]', '[[updated]]', '[[curso_proxima_fecha]]', '[[curso_ciudades]]', '[[curso_sedes]]', '[[curso_lugares]]'],
            ],
        ]);
    }

    public function project(SitePage $page): JsonResponse
    {
        $project = $page->gjs_project ? json_decode($page->gjs_project, true) : null;

        return response()->json([
            'project' => $project,
            'html' => $project ? null : (string) $page->gjs_html,   // pages migrated without a project still open (as HTML)
            'css' => $project ? null : (string) $page->gjs_css,
            'page' => $this->meta($page),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function save(Request $request, SitePage $page): JsonResponse
    {
        $data = $request->validate([
            'project' => ['required', 'array'],
            'html' => ['required', 'string', 'max:2000000'],
            'css' => ['nullable', 'string', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:120'],
        ]);

        $projectJson = json_encode($data['project'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        abort_if(strlen($projectJson) > 8_000_000, 413, 'El proyecto es demasiado grande.');

        $page->forceFill([
            'gjs_project' => $projectJson,
            'gjs_html' => PageHtmlSanitizer::html($data['html']),
            'gjs_css' => PageHtmlSanitizer::css($data['css'] ?? ''),
            'edited_at' => now(),
        ])->save();

        $this->remember($page, $request->user()->id, $data['note'] ?? null);
        Cache::forget('pagebuilder__pageData_' . $page->id);

        return response()->json(['saved' => true, 'edited_at' => $page->edited_at->toIso8601String(), 'page' => $this->meta($page)]);
    }

    public function meta_update(Request $request, SitePage $page): JsonResponse
    {
        abort_if($page->slug === '/', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:320'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9][a-z0-9\-\/]*$/', Rule::unique($page->getTable(), 'slug')->ignore($page->id)],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ], ['slug.regex' => 'Usa solo minúsculas, números y guiones.', 'slug.unique' => 'Ya existe una página con esa URL.']);

        $first = explode('/', $data['slug'])[0];
        abort_if(in_array($first, self::RESERVED, true) && $data['slug'] !== $page->slug, 422, 'Esa URL está reservada por la aplicación.');

        $page->update(array_merge($data, ['title' => $data['title'] ?: $data['name'], 'description' => $data['description'] ?? '']));
        Cache::forget('pagebuilder__pageData_' . $page->id);

        return response()->json(['page' => $this->meta($page->fresh())]);
    }

    public function revisions(SitePage $page): JsonResponse
    {
        return response()->json(PageRevision::where('page_id', $page->id)->latest('id')->limit(PageRevision::KEEP)->get(['id', 'note', 'created_at', 'user_id'])->map(fn ($r) => [
            'id' => $r->id, 'note' => $r->note, 'at' => $r->created_at->toIso8601String(), 'human' => $r->created_at->locale('es')->diffForHumans(),
        ]));
    }

    public function restore(SitePage $page, PageRevision $revision): JsonResponse
    {
        abort_unless($revision->page_id === $page->id, 404);

        return response()->json(['project' => json_decode($revision->project, true)]);
    }

    /** POST /admin/paginas/medios — GrapesJS asset manager upload ({data: [{src}]}). */
    public function media(Request $request): JsonResponse
    {
        $request->validate(['files' => ['required', 'array', 'max:10'], 'files.*' => ['file', 'image', 'mimes:jpg,jpeg,png,gif,webp,avif', 'max:4096']]);

        $disk = getStorageDisk();
        $out = [];
        foreach ($request->file('files') as $file) {
            $path = Storage::disk($disk)->putFileAs('pages/' . now()->format('Y/m'), $file, Str::random(24) . '.' . $file->extension(), ['visibility' => 'public']);
            $out[] = ['src' => mediaUrl($path, $disk), 'name' => $file->getClientOriginalName()];
        }

        return response()->json(['data' => $out]);
    }

    /**
     * Live preview of a dynamic block inside the editor canvas: the exact markup the public page will print, rendered
     * with the block's current settings. Blocks that need a course use the one the page is attached to, or a sample.
     */
    public function blockPreview(Request $request, SitePage $page, PageContentRenderer $renderer): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['courses', 'course-info', 'events', 'course-dates'])],
            'count' => ['nullable', 'integer', 'min:1', 'max:12'],
            'category' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:80'],
            'heading' => ['nullable', 'string', 'max:120'],
        ]);
        $type = $data['type'];
        $attrs = array_filter([
            'data-count' => (string) ($data['count'] ?? 3),
            'data-category' => preg_replace('/\D/', '', (string) ($data['category'] ?? '')),
            'data-city' => trim(strip_tags((string) ($data['city'] ?? ''))),
            'data-heading' => trim(strip_tags((string) ($data['heading'] ?? ''))),
        ], fn ($v) => $v !== '');

        $course = null;
        $note = null;
        if (in_array($type, ['course-info', 'course-dates'], true)) {
            $attached = CoursePage::where('page_id', $page->id)->first()?->course;
            $course = $attached ?? ($type === 'course-dates'
                ? Course::where('status', Course::STATUSES['active'])->whereIn('delivery', ['in_person', 'hybrid'])->whereHas('sessions', fn ($q) => $q->upcoming())->first()
                : Course::where('status', Course::STATUSES['active'])->latest('id')->first());
            $note = $course
                ? ($attached ? 'Vista con «' . html_entity_decode($course->title) . '», el curso de esta página.' : 'Vista de ejemplo con «' . html_entity_decode($course->title) . '». En la página real se mostrará el curso al que la asocies.')
                : null;
        }

        $html = $renderer->renderBlock($type, $attrs, $course);
        if (trim($html) === '') {
            $html = '<div class="hz-empty-preview"><strong>' . match ($type) {
                'events' => 'Aún no hay eventos programados',
                'course-dates' => 'Sin fechas para mostrar',
                'course-info' => 'Sin curso para mostrar',
                default => 'Sin cursos para mostrar',
            } . '</strong><span>' . match ($type) {
                'events' => 'Cuando publiques cursos presenciales con fechas futuras, aparecerán aquí.',
                'course-dates' => 'Este bloque muestra las fechas de un curso presencial. Asocia la página a uno desde «Páginas de curso».',
                'course-info' => 'Este bloque muestra los datos y el botón de compra del curso al que asocies la página.',
                default => 'Cuando haya cursos publicados aparecerán aquí.',
            } . '</span></div>';
        }

        return response()->json(['html' => $html, 'note' => $note])->header('Cache-Control', 'no-store, private');
    }

    private function remember(SitePage $page, int $userId, ?string $note): void
    {
        // Avoid a revision per autosave: only one per minute unless a note is given.
        $last = PageRevision::where('page_id', $page->id)->latest('id')->first();
        if ($last && !$note && $last->created_at->gt(now()->subMinute())) {
            $last->update(['project' => $page->gjs_project, 'html' => $page->gjs_html, 'css' => $page->gjs_css]);

            return;
        }
        PageRevision::create(['page_id' => $page->id, 'user_id' => $userId, 'project' => $page->gjs_project, 'html' => $page->gjs_html, 'css' => $page->gjs_css, 'note' => $note]);

        $keep = PageRevision::where('page_id', $page->id)->latest('id')->limit(PageRevision::KEEP)->pluck('id');
        PageRevision::where('page_id', $page->id)->whereNotIn('id', $keep)->delete();
    }

    private function meta(SitePage $p): array
    {
        return ['id' => $p->id, 'name' => $p->name, 'title' => $p->title, 'description' => $p->description, 'slug' => $p->slug, 'status' => $p->status,
            'url' => $p->url(), 'edited_at' => $p->edited_at?->toIso8601String()];
    }
}
