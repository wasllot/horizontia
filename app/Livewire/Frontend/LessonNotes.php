<?php

namespace App\Livewire\Frontend;

use App\Models\LessonNote;
use App\Services\LessonProgressService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Services\CourseAccessService;

/** «Mis notas» de una lección: notas y marcadores personales, con el minuto del video si lo hay. Solo ve las suyas. */
class LessonNotes extends Component
{
    #[Locked]
    public int $courseId;

    #[Locked]
    public int $curriculumId;

    public string $body = '';

    public function mount(int $courseId, int $curriculumId): void
    {
        $this->courseId = $courseId;
        $this->curriculumId = $curriculumId;
    }

    /** La lección debe ser de ese curso y el alumno debe tener acceso. */
    private function lessonOrAbort(): Curriculum
    {
        abort_unless(Auth::user()?->hasRole('student'), 403);
        $lesson = Curriculum::with('section')->find($this->curriculumId);
        abort_unless($lesson && (int) $lesson->section?->course_id === $this->courseId && app(CourseAccessService::class)->canViewCurriculum(Auth::user(), $lesson), 403);

        return $lesson;
    }

    private function clean(?int $seconds): ?int
    {
        return $seconds === null ? null : max(0, min($seconds, 86400));
    }

    public function add(?int $seconds = null): void
    {
        $this->lessonOrAbort();
        $this->validate(['body' => 'required|string|max:2000'], ['body.required' => 'Escribe tu nota.', 'body.max' => 'La nota es muy larga (máximo 2000 caracteres).']);

        LessonNote::create([
            'user_id' => Auth::id(), 'course_id' => $this->courseId, 'curriculum_id' => $this->curriculumId, 'kind' => LessonNote::NOTE,
            'body' => trim(strip_tags($this->body)), 'seconds' => $this->clean($seconds),
        ]);
        $this->reset('body');
    }

    public function bookmark(int $seconds): void
    {
        $this->lessonOrAbort();
        $seconds = $this->clean($seconds);
        if (!LessonNote::where('user_id', Auth::id())->where('curriculum_id', $this->curriculumId)->where('kind', LessonNote::BOOKMARK)->where('seconds', $seconds)->exists()) {
            LessonNote::create(['user_id' => Auth::id(), 'course_id' => $this->courseId, 'curriculum_id' => $this->curriculumId, 'kind' => LessonNote::BOOKMARK, 'seconds' => $seconds]);
        }
    }

    public function remove(int $id): void
    {
        $this->lessonOrAbort();
        LessonNote::where('user_id', Auth::id())->where('curriculum_id', $this->curriculumId)->whereKey($id)->delete();
    }

    public function render()
    {
        $this->lessonOrAbort();

        return view('livewire.frontend.lesson-notes', [
            'notes' => LessonNote::where('user_id', Auth::id())->where('curriculum_id', $this->curriculumId)->orderByRaw('seconds is null, seconds')->orderBy('id')->get(),
        ]);
    }
}
