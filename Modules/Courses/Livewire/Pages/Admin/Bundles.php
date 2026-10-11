<?php

namespace Modules\Courses\Livewire\Pages\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Courses\Models\Bundle;
use Modules\Courses\Services\BundleService;

/** Admin > Paquetes: courses sold together at one price. */
class Bundles extends Component
{
    public bool $formOpen = false;
    public ?int $editingId = null;
    public string $title = '';
    public string $subtitle = '';
    public string $description = '';
    public $price = '';
    public string $status = Bundle::DRAFT;
    /** @var array<int, int|string> selected course ids, in the order they will be shown */
    public array $courseIds = [];
    public string $search = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('sub_admin'), 403);   // also on every Livewire action
    }

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('showAlertMessage', type: $type, title: $type === 'success' ? __('general.success_title') : 'Atención', message: $message);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        $b = Bundle::with('courses')->findOrFail($id);
        $this->editingId = $b->id;
        $this->title = $b->title;
        $this->subtitle = (string) $b->subtitle;
        $this->description = (string) $b->description;
        $this->price = (string) $b->price;
        $this->status = $b->status;
        $this->courseIds = $b->courses->pluck('id')->map(fn ($i) => (string) $i)->all();
        $this->resetErrorBag();
        $this->formOpen = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset('formOpen', 'editingId', 'title', 'subtitle', 'description', 'price', 'courseIds', 'search');
        $this->status = Bundle::DRAFT;
        $this->resetErrorBag();
    }

    public function toggleCourse(int $id): void
    {
        $key = array_search((string) $id, array_map('strval', $this->courseIds), true);
        if ($key === false) {
            $this->courseIds[] = (string) $id;
        } else {
            unset($this->courseIds[$key]);
            $this->courseIds = array_values($this->courseIds);
        }
    }

    public function save(BundleService $service): void
    {
        $this->validate([
            'title' => 'required|string|max:160',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price' => 'required|numeric|min:1|max:1000000',
            'status' => 'required|in:draft,active',
        ], ['title.required' => 'Escribe el nombre del paquete.', 'price.required' => 'Indica el precio del paquete.', 'price.min' => 'El precio debe ser mayor a 0.'], ['price' => 'precio']);

        $eligible = $service->eligibleCourses()->keyBy('id');
        $ids = collect($this->courseIds)->map(fn ($i) => (int) $i)->unique()->filter(fn ($i) => $eligible->has($i))->values();
        if ($ids->count() < 2) {
            $this->addError('courseIds', 'Elige al menos 2 cursos publicados y en línea (con precio).');

            return;
        }
        $list = round((float) $ids->sum(fn ($i) => (float) $eligible[$i]->pricing->final_price), 2);
        if ((float) $this->price >= $list) {
            $this->addError('price', 'El paquete debe costar menos que los cursos por separado (' . strip_tags(formatAmount($list, true)) . ').');

            return;
        }

        $data = ['title' => trim(strip_tags($this->title)), 'subtitle' => trim(strip_tags($this->subtitle)) ?: null, 'description' => trim(strip_tags($this->description)) ?: null, 'price' => round((float) $this->price, 2), 'status' => $this->status];
        if ($this->editingId) {
            $bundle = Bundle::findOrFail($this->editingId);
            $bundle->update($data);
        } else {
            $bundle = Bundle::create($data + ['slug' => $service->uniqueSlug($data['title']), 'sort' => (int) Bundle::max('sort') + 1]);
        }
        $bundle->courses()->sync($ids->mapWithKeys(fn ($id, $i) => [$id => ['sort' => $i + 1]])->all());

        $this->resetForm();
        $this->toast('Paquete guardado.');
    }

    public function toggleStatus(int $id): void
    {
        $b = Bundle::findOrFail($id);
        $b->update(['status' => $b->isActive() ? Bundle::DRAFT : Bundle::ACTIVE]);
        $this->toast($b->isActive() ? 'Paquete publicado.' : 'Paquete en borrador (ya no se vende).');
    }

    public function delete(int $id): void
    {
        $b = Bundle::findOrFail($id);
        $b->courses()->detach();
        $b->delete();
        $this->toast('Paquete eliminado.');
    }

    #[Layout('layouts.admin-app')]
    public function render(BundleService $service)
    {
        $eligible = $service->eligibleCourses();
        $term = mb_strtolower(trim($this->search));
        $selected = collect($this->courseIds)->map(fn ($i) => (int) $i);
        $listSelected = round((float) $eligible->whereIn('id', $selected->all())->sum(fn ($c) => (float) $c->pricing->final_price), 2);

        return view('courses::livewire.admin.bundles', [
            'bundles' => Bundle::with('courses.pricing')->orderBy('sort')->orderByDesc('id')->get(),
            'service' => $service,
            'eligible' => $term === '' ? $eligible : $eligible->filter(fn ($c) => str_contains(mb_strtolower(html_entity_decode($c->title)), $term))->values(),
            'selected' => $selected,
            'listSelected' => $listSelected,
        ]);
    }
}
