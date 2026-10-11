<?php

namespace Modules\Courses\Livewire\Pages\Tutor\CourseCreation\Components\ManageCourseContent;

use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Courses\Livewire\Traits\AdminAwareCourseRouting;
use Modules\Courses\Livewire\Traits\GuardsCourseAccess;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Section;
use Modules\Courses\Services\CourseService;
use Modules\Courses\Services\Lessons\GeniallyHandler;
use Modules\Courses\Services\Lessons\LessonSaver;
use Modules\Courses\Services\Lessons\LessonType;
use Modules\Courses\Services\Lessons\QuizService;
use Modules\Courses\Services\Lessons\VimeoHandler;
use Modules\Courses\Services\Lessons\VideoFileHandler;
use Modules\Courses\Services\Lessons\YoutubeHandler;

/**
 * Step "Contenido": a master-detail builder. The outline (sections and lessons, drag to reorder) is on the left and the
 * editor of the selected lesson on the right. Every action resolves records through CourseAccess (ids are client input)
 * and every lesson type is saved by its own handler (see Services/Lessons).
 */
class CourseBuilder extends Component
{
    use WithFileUploads;
    use AdminAwareCourseRouting;
    use GuardsCourseAccess;

    #[Locked]
    public $courseId;

    #[Locked]
    public ?int $lessonId = null;

    // Selected lesson
    public string $lessonTitle = '';
    public string $lessonDescription = '';
    public bool $isPreview = false;
    public string $group = 'video';
    public string $source = 'video';          // stored type inside the video group
    public array $content = [];
    public $videoUpload = null;
    public $scormUpload = null;
    public int $durationSeconds = 0;

    // Outline editing
    #[Locked]
    public ?int $renamingSection = null;
    public string $sectionTitle = '';
    public bool $addingSection = false;
    public string $newSectionTitle = '';
    #[Locked]
    public ?int $addingLessonIn = null;
    public string $newLessonTitle = '';

    public function mount($id = null): void
    {
        $this->courseId = $id ?? request()->route('id');
        $this->guardedCourse($this->courseId);
        $this->content = $this->emptyContent();

        // Open the first lesson so the editor is never an empty page for a course that already has content.
        $first = Curriculum::whereIn('section_id', Section::where('course_id', $this->courseId)->pluck('id'))->orderBy('section_id')->orderBy('sort_order')->first();
        if ($first) {
            $this->loadLesson($first);
        }
    }

    private function emptyContent(): array
    {
        return [
            'yt_link' => '', 'vm_link' => '', 'genially_link' => '', 'article' => '', 'duration_min' => null,
            'quiz' => ['pass_score' => 70, 'max_attempts' => 0, 'show_answers' => true, 'shuffle' => false, 'questions' => []],
            'live' => ['date_time' => '', 'duration_minutes' => 60, 'meeting_link' => '', 'notify_hours_before' => 24, 'description' => '', 'recording_url' => ''],
        ];
    }

    public function render()
    {
        $sections = Section::where('course_id', $this->courseId)->with(['curriculums' => fn ($q) => $q->with('liveStream')])->orderBy('sort_order')->orderBy('id')->get();
        $saver = new LessonSaver();

        $lessons = $sections->flatMap->curriculums;
        $stats = [
            'sections' => $sections->count(),
            'lessons' => $lessons->count(),
            'minutes' => (int) round($lessons->sum('content_length') / 60),
            'incomplete' => $lessons->filter(fn ($l) => !$saver->isComplete($l))->count(),
        ];
        $complete = $lessons->mapWithKeys(fn ($l) => [$l->id => $saver->isComplete($l)]);
        $lesson = $this->lessonId ? $lessons->firstWhere('id', $this->lessonId) : null;

        return view('courses::livewire.tutor.course-creation.components.manage-course-content.course-builder', [
            'sections' => $sections,
            'stats' => $stats,
            'complete' => $complete,
            'lesson' => $lesson,
            'embed' => $this->embedUrl(),
            'enrolled' => \Modules\Courses\Models\Enrollment::where('course_id', $this->courseId)->count(),
            'backUrl' => courseEditUrl(isPaidSystem() ? 'pricing' : 'media', $this->courseId),
        ]);
    }

    public function loadData(): void {}

    // ------------------------------------------------------------------ sections

    public function addSection(): void
    {
        $this->guardedCourse($this->courseId);
        $this->validate(['newSectionTitle' => 'required|string|max:150'], ['newSectionTitle.required' => 'Escribe el nombre de la sección.']);

        $order = (int) Section::where('course_id', $this->courseId)->max('sort_order') + 1;
        Section::create(['course_id' => $this->courseId, 'title' => trim(strip_tags($this->newSectionTitle)), 'description' => '', 'sort_order' => $order]);
        $this->reset(['newSectionTitle', 'addingSection']);
        $this->toast('Sección creada');
    }

    public function startRenameSection(int $id): void
    {
        $section = $this->guardedSection($id);
        $this->renamingSection = $section->id;
        $this->sectionTitle = $section->title;
    }

    public function saveSectionTitle(): void
    {
        $section = $this->guardedSection($this->renamingSection);
        $this->validate(['sectionTitle' => 'required|string|max:150'], ['sectionTitle.required' => 'El nombre no puede estar vacío.']);
        $section->update(['title' => trim(strip_tags($this->sectionTitle))]);
        $this->reset(['renamingSection', 'sectionTitle']);
    }

    public function cancelRename(): void
    {
        $this->reset(['renamingSection', 'sectionTitle']);
    }

    public function deleteSection(int $id): void
    {
        $section = $this->guardedSection($id);
        $saver = new LessonSaver();
        foreach ($section->curriculums as $lesson) {
            $saver->discard($lesson);
        }
        if ($this->lessonId && $section->curriculums->contains('id', $this->lessonId)) {
            $this->clearLesson();
        }
        $section->delete();
        $this->syncLength();
        $this->toast('Sección eliminada');
    }

    /** @param array<int> $ids section ids in the new order */
    public function reorderSections(array $ids): void
    {
        $this->guardedCourse($this->courseId);
        $valid = Section::where('course_id', $this->courseId)->pluck('id')->all();
        foreach (array_values(array_filter(array_map('intval', $ids), fn ($i) => in_array($i, $valid, true))) as $order => $id) {
            Section::where('id', $id)->update(['sort_order' => $order]);
        }
    }

    // ------------------------------------------------------------------ lessons

    public function startAddLesson(int $sectionId): void
    {
        $this->addingLessonIn = $this->guardedSection($sectionId)->id;
        $this->newLessonTitle = '';
    }

    public function cancelAddLesson(): void
    {
        $this->reset(['addingLessonIn', 'newLessonTitle']);
    }

    public function addLesson(): void
    {
        $section = $this->guardedSection($this->addingLessonIn);
        $this->validate(['newLessonTitle' => 'required|string|max:200'], ['newLessonTitle.required' => 'Escribe el título de la lección.']);

        $lesson = Curriculum::create([
            'section_id' => $section->id,
            'title' => trim(strip_tags($this->newLessonTitle)),
            'description' => '',
            'type' => 'video',
            'is_preview' => false,
            'sort_order' => (int) Curriculum::where('section_id', $section->id)->max('sort_order') + 1,
        ]);
        $this->reset(['addingLessonIn', 'newLessonTitle']);
        $this->loadLesson($lesson);
    }

    public function selectLesson(int $id): void
    {
        $this->loadLesson($this->guardedLesson($id));
    }

    private function loadLesson(Curriculum $lesson): void
    {
        $this->resetErrorBag();
        $this->reset(['videoUpload', 'scormUpload', 'durationSeconds']);
        $this->lessonId = $lesson->id;
        $this->lessonTitle = $lesson->title;
        $this->lessonDescription = (string) $lesson->description;
        $this->isPreview = (bool) $lesson->is_preview;
        $this->group = LessonType::groupOf($lesson->type);
        $this->source = $this->group === 'video' ? ($lesson->type ?: 'video') : 'video';

        $content = $this->emptyContent();
        match ($lesson->type) {
            'yt_link' => $content['yt_link'] = (string) $lesson->media_path,
            'vm_link' => $content['vm_link'] = (string) $lesson->media_path,
            'genially_link' => $content['genially_link'] = (string) $lesson->media_path,
            'article' => $content['article'] = (string) $lesson->article_content,
            'assignment' => $content['quiz'] = (new QuizService())->load($lesson->article_content),
            default => null,
        };
        if (in_array($lesson->type, ['yt_link', 'vm_link', 'genially_link'], true) && $lesson->content_length) {
            $content['duration_min'] = (int) round($lesson->content_length / 60);
        }
        if ($lesson->type === 'live' && ($stream = $lesson->liveStream)) {
            $content['live'] = [
                'date_time' => $stream->date_time?->format('Y-m-d\TH:i'),
                'duration_minutes' => $stream->duration_minutes ?: 60,
                'meeting_link' => (string) $stream->meeting_link,
                'notify_hours_before' => $stream->notify_hours_before ?? 24,
                'description' => (string) $stream->description,
                'recording_url' => (string) $stream->recording_url,
            ];
        }
        $this->content = $content;
        $this->dispatch('lesson-loaded', id: $lesson->id, article: $content['article'], quiz: $content['quiz']);
    }

    private function clearLesson(): void
    {
        $this->reset(['lessonId', 'lessonTitle', 'lessonDescription', 'isPreview', 'videoUpload', 'scormUpload', 'durationSeconds']);
        $this->group = 'video';
        $this->source = 'video';
        $this->content = $this->emptyContent();
    }

    public function setGroup(string $group): void
    {
        abort_unless(isset(LessonType::GROUPS[$group]), 422);
        $this->group = $group;
        if ($group === 'video' && !isset(LessonType::VIDEO_SOURCES[$this->source])) {
            $this->source = 'video';
        }
        $this->resetErrorBag();
        $this->dispatch('lesson-group', group: $group);
    }

    public function setSource(string $source): void
    {
        abort_unless(isset(LessonType::VIDEO_SOURCES[$source]), 422);
        $this->source = $source;
        $this->resetErrorBag();
    }

    private function storedType(): string
    {
        return $this->group === 'video' ? $this->source : LessonType::defaultType($this->group);
    }

    public function saveLesson(bool $andNext = false): void
    {
        $lesson = $this->guardedLesson($this->lessonId);
        $this->validate([
            'lessonTitle' => 'required|string|max:200',
            'lessonDescription' => 'nullable|string|max:5000',
        ], ['lessonTitle.required' => 'La lección necesita un título.']);

        $type = $this->storedType();
        $input = $this->content + ['files' => array_filter(['video' => $this->videoUpload, 'scorm' => $this->scormUpload])];
        $input['duration'] = $this->durationSeconds ?: (isset($this->content['duration_min']) && $this->content['duration_min'] !== '' ? (int) $this->content['duration_min'] * 60 : null);
        $input['article'] = $this->content['article'] ?? '';

        $lesson->update([
            'title' => trim(strip_tags($this->lessonTitle)),
            'description' => cleanLessonHtml($this->lessonDescription),
            'is_preview' => $this->isPreview,
        ]);

        try {
            (new LessonSaver())->save($lesson, $type, $input);
        } catch (ValidationException $e) {
            $this->toast('Revisa los campos marcados', 'error');
            throw $e;
        }

        $this->reset(['videoUpload', 'scormUpload', 'durationSeconds']);
        $this->syncLength();
        $this->loadLesson($lesson->refresh());
        $this->toast('Lección guardada');

        if ($andNext) {
            $this->openNext($lesson);
        }
    }

    private function openNext(Curriculum $current): void
    {
        $ordered = Curriculum::whereIn('section_id', Section::where('course_id', $this->courseId)->orderBy('sort_order')->orderBy('id')->pluck('id'))->get()
            ->sortBy(fn ($l) => [Section::find($l->section_id)->sort_order, $l->section_id, $l->sort_order])->values();
        $i = $ordered->search(fn ($l) => $l->id === $current->id);
        if ($i !== false && isset($ordered[$i + 1])) {
            $this->loadLesson($ordered[$i + 1]);
        }
    }

    public function updatedVideoUpload(): void
    {
        $this->resetErrorBag('content.files.video');
        if (!$this->videoUpload) {
            return;
        }
        $ext = strtolower($this->videoUpload->getClientOriginalExtension());
        $mb = $this->videoUpload->getSize() / 1048576;
        if (!in_array($ext, VideoFileHandler::allowedExtensions(), true) || $mb > VideoFileHandler::maxMegabytes()) {
            $this->addError('content.files.video', 'Usa un video ' . implode(', ', VideoFileHandler::allowedExtensions()) . ' de hasta ' . VideoFileHandler::maxMegabytes() . ' MB.');
            $this->videoUpload = null;
            $this->dispatch('cr-video-rejected');
        }
    }

    public function deleteLesson(int $id): void
    {
        $lesson = $this->guardedLesson($id);
        (new LessonSaver())->discard($lesson);
        if ($this->lessonId === $lesson->id) {
            $this->clearLesson();
        }
        $lesson->delete();
        $this->syncLength();
        $this->toast('Lección eliminada');
    }

    public function duplicateLesson(int $id): void
    {
        $src = $this->guardedLesson($id);
        $copy = $src->replicate(['media_path', 'article_content', 'created_at', 'updated_at']);
        $copy->title = mb_substr($src->title, 0, 185) . ' (copia)';
        $copy->sort_order = (int) Curriculum::where('section_id', $src->section_id)->max('sort_order') + 1;
        // Files and live sessions are not cloned; text, quizzes and external links are.
        if (in_array($src->type, ['article', 'assignment', 'yt_link', 'vm_link', 'genially_link'], true)) {
            $copy->media_path = $src->media_path;
            $copy->article_content = $src->article_content;
        } elseif (in_array($src->type, ['video', 'scorm', 'live'], true)) {
            $copy->type = 'video';
            $copy->content_length = 0;
        }
        $copy->save();
        $this->loadLesson($copy);
        $this->toast('Lección duplicada');
    }

    /**
     * @param array<int> $toIds   lesson ids of the target section, in order
     * @param array<int> $fromIds lesson ids left in the source section (when a lesson moved between sections)
     */
    public function reorderLessons(int $toSection, array $toIds, ?int $fromSection = null, array $fromIds = []): void
    {
        $this->guardedCourse($this->courseId);
        $sectionIds = Section::where('course_id', $this->courseId)->pluck('id')->all();
        abort_unless(in_array($toSection, $sectionIds, true), 403);
        $courseLessons = Curriculum::whereIn('section_id', $sectionIds)->pluck('section_id', 'id');

        foreach ([[$toSection, $toIds], [$fromSection, $fromIds]] as [$section, $ids]) {
            if (!$section || !in_array($section, $sectionIds, true)) {
                continue;
            }
            foreach (array_values(array_filter(array_map('intval', $ids), fn ($i) => $courseLessons->has($i))) as $order => $id) {
                Curriculum::where('id', $id)->update(['section_id' => $section, 'sort_order' => $order]);
            }
        }
    }

    // ------------------------------------------------------------------ helpers

    /** Embeddable URL of the pasted external video, for the live preview in the editor. */
    private function embedUrl(): ?string
    {
        if ($this->group !== 'video') {
            return null;
        }

        return match ($this->source) {
            'yt_link' => (new YoutubeHandler())->normalize((string) ($this->content['yt_link'] ?? '')),
            'vm_link' => ($v = VimeoHandler::parse((string) ($this->content['vm_link'] ?? ''))) ? 'https://player.vimeo.com/video/' . $v['id'] . ($v['hash'] ? '?h=' . $v['hash'] : '') : null,
            'genially_link' => (new GeniallyHandler())->normalize((string) ($this->content['genially_link'] ?? '')),
            default => null,
        };
    }

    private function syncLength(): void
    {
        $course = Course::with('sections.curriculums')->find($this->courseId);
        if ($course) {
            $course->content_length = (int) $course->sections->sum(fn ($s) => $s->curriculums->sum('content_length'));
            $course->save();
        }
    }

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('showAlertMessage', type: $type, title: '', message: $message);
    }

    /** "Guardar y continuar": the content step never blocks; the publish step lists what is still missing. */
    public function next()
    {
        $this->guardedCourse($this->courseId);
        $this->syncLength();

        return redirect(courseEditUrl('faqs', $this->courseId));
    }
}
