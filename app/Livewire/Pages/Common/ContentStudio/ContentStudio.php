<?php

namespace App\Livewire\Pages\Common\ContentStudio;

use App\Services\ContentStudioService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Section;
use Modules\Courses\Services\Lessons\LessonSaver;

/**
 * Estudio de contenido (instructores y administradores): prepara resúmenes, preguntas, descripciones y esquemas con IA.
 * Un instructor solo trabaja con sus propios cursos; el administrador, con todos. Nada se publica sin revisión.
 */
class ContentStudio extends Component
{
    public string $tool = 'summary';
    public string $text = '';
    public ?int $courseId = null;
    public ?int $lessonId = null;
    public int $count = 6;

    #[Locked]
    public ?string $output = null;

    /** @var array<int, array<string, mixed>> preguntas generadas, editables antes de crear el test */
    public array $questions = [];

    public ?string $error = null;
    public ?int $sectionId = null;
    public string $quizTitle = '';
    public ?string $createdUrl = null;

    public function mount(): void
    {
        $this->authorizeRole();
    }

    private function isAdmin(): bool
    {
        return (bool) Auth::user()?->hasAnyRole(['admin', 'sub_admin']);
    }

    private function authorizeRole(): void
    {
        abort_unless($this->isAdmin() || Auth::user()?->hasRole('tutor'), 403);
    }

    /** Cursos con los que puede trabajar esta persona. */
    private function courses()
    {
        $q = Course::query()->where('delivery', '!=', 'in_person')->orderBy('title');
        if (!$this->isAdmin()) {
            $q->where('instructor_id', Auth::id());
        }

        return $q->get(['id', 'title']);
    }

    private function ownCourseOrAbort(int $id): Course
    {
        $course = Course::find($id);
        abort_unless($course && ($this->isAdmin() || (int) $course->instructor_id === (int) Auth::id()), 403);

        return $course;
    }

    public function updatedCourseId(): void
    {
        $this->lessonId = null;
        $this->sectionId = null;
        if ($this->courseId) {
            $this->ownCourseOrAbort($this->courseId);
        }
    }

    /** Al elegir una lección, su contenido de texto pasa a la caja de texto. */
    public function updatedLessonId(): void
    {
        if (!$this->lessonId || !$this->courseId) {
            return;
        }
        $this->ownCourseOrAbort($this->courseId);
        $lesson = Curriculum::whereHas('section', fn ($q) => $q->where('course_id', $this->courseId))->find($this->lessonId);
        if (!$lesson) {
            return;
        }
        $body = $lesson->type === 'article' ? strip_tags((string) $lesson->article_content) : '';   // los tests nunca: guardan las respuestas
        $this->text = trim(preg_replace('/\s+/', ' ', html_entity_decode($lesson->title . '. ' . ($lesson->description && $lesson->description !== $lesson->title ? $lesson->description . '. ' : '') . $body)));
    }

    public function generate(ContentStudioService $studio): void
    {
        $this->authorizeRole();
        $this->reset('output', 'questions', 'error', 'createdUrl');
        if (!isset(ContentStudioService::TOOLS[$this->tool])) {
            $this->tool = 'summary';
        }

        $result = $studio->run(Auth::user(), $this->tool, $this->text, $this->count);
        if (!$result['ok']) {
            $this->error = ContentStudioService::errorMessage($result['error'] ?? 'failed');

            return;
        }
        if (isset($result['questions'])) {
            $this->questions = $result['questions'];
            $this->quizTitle = $this->quizTitle ?: 'Test de repaso';
        } else {
            $this->output = $result['text'];
        }
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    /** Crea una lección de test (borrador, sin vista previa) con las preguntas ya revisadas. */
    public function createQuiz(): void
    {
        $this->authorizeRole();
        $this->validate(['quizTitle' => 'required|string|max:160', 'sectionId' => 'required|integer'], ['quizTitle.required' => 'Ponle un nombre al test.', 'sectionId.required' => 'Elige en qué sección va el test.']);
        if ($this->questions === []) {
            $this->addError('questions', 'No hay preguntas para crear el test.');

            return;
        }
        $section = Section::find($this->sectionId);
        abort_unless($section, 404);
        $this->ownCourseOrAbort((int) $section->course_id);

        $lesson = Curriculum::create(['section_id' => $section->id, 'title' => trim(strip_tags($this->quizTitle)), 'description' => 'Test generado con el Estudio de contenido.', 'type' => 'assignment',
            'content_length' => 60, 'sort_order' => (int) Curriculum::where('section_id', $section->id)->max('sort_order') + 1, 'is_preview' => false]);
        app(LessonSaver::class)->save($lesson, 'assignment', ['quiz' => ['pass_score' => 70, 'questions' => collect($this->questions)->map(fn ($q) => ['type' => 'single'] + $q)->all()]]);

        $this->createdUrl = auth()->user()->hasRole('tutor') ? route('courses.tutor.edit-course', ['tab' => 'content', 'id' => $section->course_id]) : route('courses.admin.edit-course', ['tab' => 'content', 'id' => $section->course_id]);
        $this->reset('questions');
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Test creado en tu curso. Revísalo y publícalo cuando quieras.');
    }

    public function render()
    {
        $this->authorizeRole();
        $studio = app(ContentStudioService::class);
        $courses = $this->courses();
        $course = $this->courseId ? $courses->firstWhere('id', $this->courseId) : null;

        return view('livewire.pages.common.content-studio.content-studio', [
            'enabled' => $studio->isEnabled(),
            'tools' => ContentStudioService::TOOLS,
            'courses' => $courses,
            'lessons' => $course ? Curriculum::whereHas('section', fn ($q) => $q->where('course_id', $course->id))->where('type', '!=', 'assignment')->orderBy('section_id')->orderBy('sort_order')->get(['id', 'title', 'type']) : collect(),
            'sections' => $course ? Section::where('course_id', $course->id)->orderBy('sort_order')->get(['id', 'title']) : collect(),
            'max' => ContentStudioService::MAX_SOURCE,
        ])->layout($this->isAdmin() ? 'layouts.admin-app' : 'layouts.app', ['title' => 'Estudio de contenido']);
    }
}
