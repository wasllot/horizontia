<?php

namespace App\Livewire\Pages\Admin\LearningPaths;

use App\Models\LearningPath;
use App\Services\LearningPathService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Admin > Rutas de aprendizaje: cursos en un orden recomendado, con avance y certificado final. */
class LearningPaths extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;

    public bool $formOpen = false;
    public ?int $editingId = null;
    public string $title = '';
    public string $subtitle = '';
    public string $description = '';
    public string $status = LearningPath::DRAFT;
    /** @var array<int, int|string> course ids in path order */
    public array $courseIds = [];
    public string $search = '';

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
        $p = LearningPath::with('courses')->findOrFail($id);
        $this->resetForm();
        $this->editingId = $p->id;
        $this->title = $p->title;
        $this->subtitle = (string) $p->subtitle;
        $this->description = (string) $p->description;
        $this->status = $p->status;
        $this->courseIds = $p->courses->pluck('id')->map(fn ($i) => (string) $i)->all();
        $this->formOpen = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset('formOpen', 'editingId', 'title', 'subtitle', 'description', 'courseIds', 'search');
        $this->status = LearningPath::DRAFT;
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

    public function move(int $id, string $direction): void
    {
        $ids = array_map('strval', $this->courseIds);
        $i = array_search((string) $id, $ids, true);
        $j = $direction === 'up' ? $i - 1 : $i + 1;
        if ($i === false || !isset($ids[$j])) {
            return;
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        $this->courseIds = $ids;
    }

    public function save(LearningPathService $service): void
    {
        $this->validate([
            'title' => 'required|string|max:160',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status' => 'required|in:draft,active',
        ], ['title.required' => 'Escribe el nombre de la ruta.']);

        $eligible = $service->eligibleCourses()->keyBy('id');
        $ids = collect($this->courseIds)->map(fn ($i) => (int) $i)->unique()->filter(fn ($i) => $eligible->has($i))->values();
        if ($ids->count() < 2) {
            $this->addError('courseIds', 'Elige al menos 2 cursos publicados y en línea.');

            return;
        }

        $data = ['title' => trim(strip_tags($this->title)), 'subtitle' => trim(strip_tags($this->subtitle)) ?: null, 'description' => trim(strip_tags($this->description)) ?: null, 'status' => $this->status];
        if ($this->editingId) {
            $path = LearningPath::findOrFail($this->editingId);
            $path->update($data);
        } else {
            $path = LearningPath::create($data + ['slug' => $service->uniqueSlug($data['title']), 'sort' => (int) LearningPath::max('sort') + 1]);
        }
        $path->courses()->sync($ids->mapWithKeys(fn ($id, $i) => [$id => ['sort' => $i + 1]])->all());

        $this->resetForm();
        $this->toast('Ruta guardada.');
    }

    public function toggleStatus(int $id): void
    {
        $p = LearningPath::findOrFail($id);
        $p->update(['status' => $p->isActive() ? LearningPath::DRAFT : LearningPath::ACTIVE]);
        $this->toast($p->isActive() ? 'Ruta publicada.' : 'Ruta en borrador (ya no se muestra).');
    }

    public function delete(int $id): void
    {
        $p = LearningPath::findOrFail($id);
        $p->courses()->detach();
        $p->delete();
        $this->toast('Ruta eliminada. Los cursos y los certificados ya emitidos no se tocan.');
    }

    #[Layout('layouts.admin-app')]
    public function render(LearningPathService $service)
    {
        $eligible = $service->eligibleCourses();
        $term = mb_strtolower(trim($this->search));
        $byId = $eligible->keyBy('id');

        return view('livewire.pages.admin.learning-paths.learning-paths', [
            'paths' => LearningPath::with('courses')->orderBy('sort')->orderByDesc('id')->get(),
            'service' => $service,
            'eligible' => $term === '' ? $eligible : $eligible->filter(fn ($c) => str_contains(mb_strtolower(html_entity_decode($c->title)), $term))->values(),
            'selected' => collect($this->courseIds)->map(fn ($i) => (int) $i)->filter(fn ($i) => $byId->has($i))->map(fn ($i) => $byId[$i]),
        ]);
    }
}
