<?php

namespace App\Livewire\Pages\Admin\CoursePages;

use App\Models\CoursePage;
use Larabuild\Pagebuilder\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Courses\Models\Course;

/**
 * Admin > Páginas de curso: attach page-builder pages to a course as informative sub-pages
 * (/curso/{slug}/informacion/{pagina}) so a course is not limited to its sales page.
 */
class CoursePages extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;

    public ?int $courseId = null;
    public ?int $pageId = null;
    public string $label = '';
    public string $search = '';
    public bool $onlyPublished = true;   // the list also holds drafts and old demo courses; hide them unless asked

    public function selectCourse(int $id): void
    {
        $this->courseId = Course::whereKey($id)->exists() ? $id : null;
        $this->reset('pageId', 'label');
        $this->resetErrorBag();
    }

    public function updatedPageId($value): void
    {
        // Suggest the page name as the tab label.
        if ($value && $this->label === '') {
            $this->label = (string) Page::whereKey($value)->value('name');
        }
    }

    public function attach(): void
    {
        $this->validate([
            'courseId' => 'required|exists:' . (new Course())->getTable() . ',id',
            'pageId' => 'required|exists:' . config('pagebuilder.db_prefix') . 'pages,id',
            'label' => 'required|string|max:120',
        ], [], ['courseId' => 'curso', 'pageId' => 'página', 'label' => 'etiqueta']);

        $exists = CoursePage::where('course_id', $this->courseId)->where('page_id', $this->pageId)->exists();
        if ($exists) {
            $this->addError('pageId', 'Esa página ya está asociada al curso.');

            return;
        }

        CoursePage::create([
            'course_id' => $this->courseId,
            'page_id' => $this->pageId,
            'label' => trim($this->label),
            'sort' => (int) CoursePage::where('course_id', $this->courseId)->max('sort') + 1,
        ]);

        $this->reset('pageId', 'label');
        $this->notify('Página asociada al curso.');
    }

    public function detach(int $id): void
    {
        CoursePage::where('course_id', $this->courseId)->whereKey($id)->delete();
        $this->notify('Página retirada del curso.');
    }

    public function move(int $id, string $direction): void
    {
        $rows = CoursePage::where('course_id', $this->courseId)->orderBy('sort')->orderBy('id')->get()->values();
        $i = $rows->search(fn ($r) => $r->id === $id);
        $j = $direction === 'up' ? $i - 1 : $i + 1;
        if ($i === false || !isset($rows[$j])) {
            return;
        }
        $rows = $rows->all();
        [$rows[$i], $rows[$j]] = [$rows[$j], $rows[$i]];
        foreach ($rows as $n => $row) {
            $row->update(['sort' => $n]);
        }
    }

    private function notify(string $message): void
    {
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: $message);
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $course = $this->courseId ? Course::find($this->courseId) : null;

        $term = trim($this->search);
        $courses = Course::query()
            ->with('instructor.profile:id,user_id,first_name,last_name')
            ->withCount('infoPages')
            ->when($this->onlyPublished, fn ($q) => $q->where('status', Course::STATUSES['active']))
            ->when($term !== '', fn ($q) => $q->where('title', 'like', '%' . $term . '%'))
            ->orderByDesc('info_pages_count')->orderBy('title')
            ->limit(60)->get(['id', 'title', 'status', 'instructor_id', 'slug']);
        // The selected course stays visible even when a filter would hide it.
        if ($this->courseId && !$courses->contains('id', $this->courseId) && ($sel = Course::withCount('infoPages')->with('instructor.profile:id,user_id,first_name,last_name')->find($this->courseId, ['id', 'title', 'status', 'instructor_id', 'slug']))) {
            $courses->prepend($sel);
        }

        return view('livewire.pages.admin.course-pages.course-pages', [
            'courses' => $courses,
            'course' => $course,
            'links' => $course ? CoursePage::with('page:id,name,slug,status')->where('course_id', $course->id)->orderBy('sort')->orderBy('id')->get() : collect(),
            'pages' => Page::whereNotIn('slug', array_column(array_values(\App\Http\Controllers\PageController::PAGES), 0))->where('slug', '!=', '/')->orderBy('name')->get(['id', 'name', 'slug', 'status']),
        ]);
    }
}
