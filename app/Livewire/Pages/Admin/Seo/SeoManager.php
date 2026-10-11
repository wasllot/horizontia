<?php

namespace App\Livewire\Pages\Admin\Seo;

use App\Models\Blog;
use App\Models\SeoEntry;
use Larabuild\Optionbuilder\Facades\Settings;
use Larabuild\Pagebuilder\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Courses\Models\Course;

/**
 * Admin > SEO: one place to control title, description, indexing, social image and JSON-LD of every URL.
 * Anything left empty falls back to what the page computes by itself (shown as a placeholder).
 */
class SeoManager extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;

    public string $tab = 'urls';
    public string $search = '';
    public string $type = 'all';
    public int $page = 1;

    public ?string $editing = null;      // path or pattern of the entry being edited
    public string $matchType = 'path';
    public string $title = '';
    public string $description = '';
    public string $keywords = '';
    public string $ogImage = '';
    public string $canonical = '';
    public string $robots = '';
    public string $schema = '';
    public bool $isActive = true;
    public array $auto = ['title' => '', 'description' => '', 'url' => ''];

    public string $newRule = '';
    public string $newRuleType = 'pattern';

    public string $defaultDescription = '';
    public string $defaultImage = '';

    private const PER_PAGE = 12;

    public function mount(): void
    {
        $global = setting('_seo') ?: [];
        $this->defaultDescription = $global['default_description'] ?? '';
        $this->defaultImage = $global['default_image'] ?? '';
    }

    public function updatingSearch(): void { $this->page = 1; }
    public function updatingType(): void { $this->page = 1; }
    public function goTo(int $page): void { $this->page = max(1, $page); }

    /** Every URL an admin may want to tune: static pages, builder pages, courses and blog posts. */
    private function catalog(): array
    {
        $rows = [
            ['type' => 'static', 'label' => 'Inicio', 'path' => '/', 'title' => '', 'description' => ''],
            ['type' => 'static', 'label' => 'Catálogo de cursos', 'path' => 'cursos', 'title' => '', 'description' => ''],
            ['type' => 'static', 'label' => 'Contacto', 'path' => 'contacto', 'title' => '', 'description' => ''],
            ['type' => 'static', 'label' => 'Blog', 'path' => 'blog', 'title' => '', 'description' => ''],
        ];

        foreach (Page::where('status', 'published')->get(['name', 'title', 'description', 'slug']) as $p) {
            $rows[] = ['type' => 'pages', 'label' => $p->name, 'path' => SeoEntry::normalizePath($p->slug ?: '/'), 'title' => (string) $p->title, 'description' => (string) $p->description];
        }
        foreach (Course::where('status', Course::STATUSES['active'])->orderByDesc('id')->limit(300)->get(['title', 'subtitle', 'slug']) as $c) {
            $rows[] = ['type' => 'courses', 'label' => $c->title, 'path' => 'curso/' . $c->slug, 'title' => (string) $c->title, 'description' => (string) $c->subtitle];
        }
        foreach (Blog::where('status', Blog::STATUSES['published'])->orderByDesc('id')->limit(300)->get(['title', 'slug', 'meta_title', 'meta_description']) as $b) {
            $rows[] = ['type' => 'blog', 'label' => $b->title, 'path' => 'blog/' . $b->slug, 'title' => (string) ($b->meta_title ?: $b->title), 'description' => (string) $b->meta_description];
        }

        return $rows;
    }

    public function edit(string $path, string $matchType = 'path'): void
    {
        $entry = SeoEntry::where('match_type', $matchType)->where('match_value', $path)->first();
        $known = collect($this->catalog())->firstWhere('path', $path);

        $this->editing = $path;
        $this->matchType = $matchType;
        $this->title = $entry->title ?? '';
        $this->description = $entry->description ?? '';
        $this->keywords = $entry->keywords ?? '';
        $this->ogImage = $entry->og_image ?? '';
        $this->canonical = $entry->canonical ?? '';
        $this->robots = $entry->robots ?? '';
        $this->schema = $entry->schema ?? '';
        $this->isActive = $entry->is_active ?? true;
        $this->auto = [
            'title' => $known['title'] ?? '',
            'description' => $known['description'] ?? '',
            'url' => $matchType === 'path' ? url($path === '/' ? '/' : $path) : $path,
        ];
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->editing = null;
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:320',
            'keywords' => 'nullable|string|max:255',
            'ogImage' => 'nullable|string|max:500',
            'canonical' => 'nullable|url|max:500',
            'robots' => ['nullable', \Illuminate\Validation\Rule::in(['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'])],
            'schema' => ['nullable', function ($attribute, $value, $fail) {
                if (filled($value) && !is_array(json_decode($value, true))) {
                    $fail(__('El JSON-LD no es un JSON válido.'));
                }
            }],
        ], [], ['title' => 'título', 'description' => 'descripción', 'ogImage' => 'imagen social', 'canonical' => 'URL canónica']);

        $data = [
            'title' => $this->title ?: null, 'description' => $this->description ?: null, 'keywords' => $this->keywords ?: null,
            'og_image' => $this->ogImage ?: null, 'canonical' => $this->canonical ?: null, 'robots' => $this->robots ?: null,
            'schema' => $this->schema ?: null, 'is_active' => $this->isActive,
        ];
        $key = ['match_type' => $this->matchType, 'match_value' => $this->editing];

        if (collect($data)->except('is_active')->filter()->isEmpty()) {
            SeoEntry::where($key)->delete();
        } else {
            SeoEntry::updateOrCreate($key, $data + ['label' => collect($this->catalog())->firstWhere('path', $this->editing)['label'] ?? null]);
        }

        $this->editing = null;
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'SEO guardado.');
    }

    public function remove(): void
    {
        SeoEntry::where('match_type', $this->matchType)->where('match_value', $this->editing)->delete();
        $this->editing = null;
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Se restauró el SEO automático.');
    }

    public function addRule(): void
    {
        $this->validate(['newRule' => 'required|string|max:191', 'newRuleType' => 'required|in:pattern,route,path']);
        $value = $this->newRuleType === 'route' ? trim($this->newRule) : SeoEntry::normalizePath($this->newRule);
        $this->edit($value, $this->newRuleType);
        $this->newRule = '';
    }

    public function saveGlobal(): void
    {
        $this->validate(['defaultDescription' => 'nullable|string|max:320', 'defaultImage' => 'nullable|string|max:500']);
        Settings::set('_seo', 'default_description', $this->defaultDescription);
        Settings::set('_seo', 'default_image', $this->defaultImage);
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Ajustes globales de SEO guardados.');
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $overrides = SeoEntry::where('match_type', 'path')->pluck('is_active', 'match_value');
        $rules = SeoEntry::whereIn('match_type', ['pattern', 'route'])->orderBy('match_type')->orderBy('match_value')->get();

        $rows = collect($this->catalog())
            ->when($this->type !== 'all', fn ($c) => $c->where('type', $this->type))
            ->when($this->search !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r['label'] . ' ' . $r['path']), mb_strtolower($this->search))))
            ->map(fn ($r) => $r + ['custom' => $overrides->has($r['path']), 'active' => (bool) ($overrides[$r['path']] ?? false)])
            ->values();

        $total = $rows->count();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $this->page = min($this->page, $pages);

        return view('livewire.pages.admin.seo.seo-manager', [
            'rows' => $rows->forPage($this->page, self::PER_PAGE),
            'total' => $total,
            'pages' => $pages,
            'rules' => $rules,
            'customCount' => $overrides->count(),
        ]);
    }
}
