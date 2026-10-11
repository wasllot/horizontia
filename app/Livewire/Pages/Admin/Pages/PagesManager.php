<?php

namespace App\Livewire\Pages\Admin\Pages;

use App\Http\Controllers\PageController;
use App\Livewire\Concerns\AuthorizesAdmin;
use App\Models\SitePage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Admin > Páginas: list, create, duplicate, publish/unpublish and delete pages of the visual editor. */
class PagesManager extends Component
{
    use AuthorizesAdmin;

    public string $search = '';
    public string $filter = 'all';

    /** Slugs that routes and menus rely on: they can be edited but not deleted. */
    private function systemSlugs(): array
    {
        return array_column(array_values(PageController::PAGES), 0);
    }

    public function create(): void
    {
        $n = SitePage::count() + 1;
        do {
            $slug = 'pagina-' . $n++;
        } while (SitePage::where('slug', $slug)->exists());

        $page = SitePage::create([
            'name' => 'Nueva página', 'title' => 'Nueva página', 'slug' => $slug, 'status' => 'draft', 'settings' => [],
            'gjs_html' => '', 'gjs_css' => '',
        ]);

        $this->redirect(route('admin.pages.editor', $page), navigate: false);
    }

    public function duplicate(int $id): void
    {
        $src = SitePage::findOrFail($id);
        $slug = Str::limit($src->slug, 100, '') . '-copia';
        $i = 2;
        while (SitePage::where('slug', $slug)->exists()) {
            $slug = Str::limit($src->slug, 100, '') . '-copia-' . $i++;
        }
        $copy = $src->replicate(['id', 'created_at', 'updated_at', 'edited_at']);
        $copy->fill(['name' => $src->name . ' (copia)', 'slug' => $slug, 'status' => 'draft'])->save();

        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Página duplicada como borrador.');
    }

    public function togglePublish(int $id): void
    {
        $page = SitePage::findOrFail($id);
        $page->update(['status' => $page->status === 'published' ? 'draft' : 'published']);
        Cache::forget('pagebuilder__pageData_' . $page->id);
    }

    public function delete(int $id): void
    {
        $page = SitePage::findOrFail($id);
        if (in_array($page->slug, $this->systemSlugs(), true) || $page->slug === '/') {
            $this->dispatch('showAlertMessage', type: 'error', title: __('general.error_title'), message: 'Es una página del sistema: puedes editarla pero no eliminarla.');

            return;
        }
        \App\Models\PageRevision::where('page_id', $page->id)->delete();
        \App\Models\CoursePage::where('page_id', $page->id)->delete();
        $page->delete();
        Cache::forget('pagebuilder__pageData_' . $id);
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Página eliminada.');
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $pages = SitePage::query()
            ->where('slug', '!=', '/')
            ->whereNotIn('slug', collect(PageController::DEDICATED)->map(fn ($r) => PageController::PAGES[$r][0])->all())   // vista propia: no se edita aquí
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('slug', 'like', "%{$this->search}%")))
            ->when($this->filter === 'published', fn ($q) => $q->where('status', 'published'))
            ->when($this->filter === 'draft', fn ($q) => $q->where('status', '!=', 'published'))
            ->orderByDesc('updated_at')->get(['id', 'name', 'slug', 'status', 'updated_at', 'edited_at', 'gjs_html']);

        return view('livewire.pages.admin.pages.pages-manager', ['pages' => $pages, 'system' => $this->systemSlugs()]);
    }
}
