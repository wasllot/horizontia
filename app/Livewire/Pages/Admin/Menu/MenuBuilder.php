<?php

namespace App\Livewire\Pages\Admin\Menu;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\MenuService;
use Larabuild\Pagebuilder\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Courses\Models\Course;

/**
 * Admin > Menús. Visual menu builder: pick a menu, add links from the platform / pages / courses / custom URL,
 * drag to reorder (one level of dropdown). Every change is saved immediately and the public cache is flushed.
 */
class MenuBuilder extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;

    public ?int $menuId = null;

    public string $newMenuName = '';
    public string $newMenuLocation = 'header';
    public bool $creating = false;

    public string $menuName = '';
    public string $menuLocation = 'header';

    public array $platformChoices = [];
    public array $pageChoices = [];
    public ?int $courseChoice = null;
    public string $customLabel = '';
    public string $customUrl = '';

    public ?int $editingId = null;
    public string $editLabel = '';
    public string $editUrl = '';

    public function mount(): void
    {
        $first = Menu::orderByRaw("location = 'header' desc")->orderBy('id')->first();
        if ($first) {
            $this->select($first->id);
        }
    }

    // ------------------------------------------------------------ menus

    public function select(int $id): void
    {
        $menu = Menu::find($id);
        if (!$menu) {
            return;
        }
        $this->menuId = $menu->id;
        $this->menuName = $menu->name;
        $this->menuLocation = $menu->location;
        $this->creating = false;
        $this->editingId = null;
    }

    public function startCreating(): void
    {
        $this->creating = true;
        $this->newMenuName = '';
        $this->newMenuLocation = 'header';
    }

    public function createMenu(): void
    {
        if ($this->demo()) {
            return;
        }
        $this->validate([
            'newMenuName' => 'required|string|max:60',
            'newMenuLocation' => 'required|in:header,footer',
        ], [], ['newMenuName' => 'nombre', 'newMenuLocation' => 'ubicación']);

        $menu = Menu::create(['name' => trim($this->newMenuName), 'location' => $this->newMenuLocation]);
        MenuService::flush();
        $this->select($menu->id);
        $this->toast('Menú creado.');
    }

    public function saveMenu(): void
    {
        if ($this->demo() || !$this->menuId) {
            return;
        }
        $this->validate([
            'menuName' => 'required|string|max:60',
            'menuLocation' => 'required|in:header,footer',
        ], [], ['menuName' => 'nombre', 'menuLocation' => 'ubicación']);

        Menu::whereKey($this->menuId)->update(['name' => trim($this->menuName), 'location' => $this->menuLocation]);
        MenuService::flush();
        $this->toast('Menú actualizado.');
    }

    public function deleteMenu(): void
    {
        if ($this->demo() || !$this->menuId) {
            return;
        }
        MenuItem::where('menu_id', $this->menuId)->delete();
        Menu::whereKey($this->menuId)->delete();
        MenuService::flush();
        $this->menuId = null;
        $this->mount();
        $this->toast('Menú eliminado.');
    }

    // ------------------------------------------------------------ items

    public function addPlatform(): void
    {
        $available = MenuService::platformLinks();
        foreach (array_intersect($this->platformChoices, array_keys($available)) as $route) {
            $this->append($available[$route], $route, 'route');
        }
        $this->platformChoices = [];
    }

    public function addPages(): void
    {
        foreach (Page::where('status', 'published')->whereIn('id', array_map('intval', $this->pageChoices))->get(['id', 'name', 'title', 'slug']) as $page) {
            $this->append($page->name ?: $page->title, '/' . ltrim($page->slug, '/'), 'page');
        }
        $this->pageChoices = [];
    }

    public function addCourse(): void
    {
        $course = $this->courseChoice ? Course::where('status', Course::STATUSES['active'])->find($this->courseChoice) : null;
        if ($course) {
            $this->append($course->title, '/curso/' . $course->slug, 'custom');
        }
        $this->courseChoice = null;
    }

    public function addCustom(): void
    {
        $this->validate([
            'customLabel' => 'required|string|max:60',
            'customUrl' => ['required', 'string', 'max:500', $this->urlRule()],
        ], [], ['customLabel' => 'texto', 'customUrl' => 'enlace']);

        $this->append(trim($this->customLabel), trim($this->customUrl), 'custom');
        $this->customLabel = $this->customUrl = '';
    }

    public function edit(int $id): void
    {
        $item = $this->item($id);
        if (!$item) {
            return;
        }
        $this->editingId = $id;
        $this->editLabel = $item->label;
        $this->editUrl = $item->type === 'route' ? '' : (string) $item->route;
    }

    public function saveItem(): void
    {
        $item = $this->editingId ? $this->item($this->editingId) : null;
        if (!$item || $this->demo()) {
            return;
        }
        $rules = ['editLabel' => 'required|string|max:60'];
        if ($item->type !== 'route') {
            $rules['editUrl'] = ['required', 'string', 'max:500', $this->urlRule()];
        }
        $this->validate($rules, [], ['editLabel' => 'texto', 'editUrl' => 'enlace']);

        $item->label = trim($this->editLabel);
        if ($item->type !== 'route') {
            $item->route = trim($this->editUrl);
        }
        $item->save();
        $this->editingId = null;
        $this->toast('Enlace actualizado.');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->resetValidation();
    }

    public function toggleBlank(int $id): void
    {
        if ($item = $this->item($id)) {
            $item->class = str_contains((string) $item->class, '_blank') ? null : '_blank';
            $item->save();
        }
    }

    public function removeItem(int $id): void
    {
        if ($this->demo() || !($item = $this->item($id))) {
            return;
        }
        MenuItem::where('menu_id', $this->menuId)->where('parent_id', $id)->get()->each->delete();
        $item->delete();
        MenuService::flush();
    }

    /**
     * @param  array<int, array{id:int|string, children?: array<int, array{id:int|string}>}>  $tree  as dragged in the browser
     */
    public function reorder(array $tree): void
    {
        if ($this->demo() || !$this->menuId) {
            return;
        }
        $valid = MenuItem::where('menu_id', $this->menuId)->pluck('id')->flip();

        foreach (array_values($tree) as $i => $node) {
            $id = (int) ($node['id'] ?? 0);
            if (!$valid->has($id)) {
                continue;
            }
            MenuItem::whereKey($id)->update(['parent_id' => null, 'sort' => $i]);
            foreach (array_values($node['children'] ?? []) as $j => $child) {
                $cid = (int) ($child['id'] ?? 0);
                if ($valid->has($cid) && $cid !== $id) {
                    MenuItem::whereKey($cid)->update(['parent_id' => $id, 'sort' => $j]);
                }
            }
        }
        MenuService::flush();
    }

    // ---------------------------------------------------------- helpers

    private function append(string $label, string $route, string $type): void
    {
        if ($this->demo() || !$this->menuId) {
            return;
        }
        MenuItem::create([
            'menu_id' => $this->menuId, 'label' => $label, 'route' => $route, 'type' => $type,
            'sort' => (int) MenuItem::where('menu_id', $this->menuId)->whereNull('parent_id')->max('sort') + 1,
        ]);
    }

    private function item(int $id): ?MenuItem
    {
        return MenuItem::where('menu_id', $this->menuId)->find($id);
    }

    /** http(s), mailto, tel, relative paths and #anchors only: never javascript: or data:. */
    private function urlRule(): \Closure
    {
        return function ($attr, $value, $fail) {
            if (!preg_match('~^(/|#|https?://|mailto:|tel:)~i', trim($value))) {
                $fail('El enlace debe empezar con /, #, http://, https://, mailto: o tel:.');
            }
        };
    }

    private function demo(): bool
    {
        if (isDemoSite()) {
            $this->dispatch('showAlertMessage', type: 'error', title: __('general.demosite_res_title'), message: __('general.demosite_res_txt'));

            return true;
        }

        return false;
    }

    private function toast(string $message): void
    {
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: $message);
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $items = $this->menuId ? MenuItem::where('menu_id', $this->menuId)->orderBy('sort')->orderBy('id')->get() : collect();
        $service = app(MenuService::class);
        $tree = $items->whereNull('parent_id')->values()->map(fn ($i) => [
            'item' => $i, 'url' => $service->url($i),
            'children' => $items->where('parent_id', $i->id)->values()->map(fn ($c) => ['item' => $c, 'url' => $service->url($c)]),
        ]);

        return view('livewire.pages.admin.menu.menu-builder', [
            'menus' => Menu::orderBy('id')->get(['id', 'name', 'location']),
            'tree' => $tree,
            'platformLinks' => MenuService::platformLinks(),
            'pages' => Page::where('status', 'published')->where('slug', '!=', '/')->orderBy('name')->get(['id', 'name', 'slug']),
            'courses' => Course::where('status', Course::STATUSES['active'])->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
