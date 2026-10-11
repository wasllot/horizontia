<?php

namespace App\Livewire\Frontend;

use App\Services\GeminiTutorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;

/**
 * Chat widget shown inside the lesson player. Renders NOTHING unless the tutor is configured and the
 * user has access to the course (see GeminiTutorService::canUse).
 */
class AiTutor extends Component
{
    #[Locked]
    public int $courseId;

    #[Locked]
    public ?int $curriculumId = null;

    // Locked: this array is sent back to Gemini as history, so the browser must not be able to forge "model" turns.
    #[Locked]
    public array $messages = [];

    public string $message = '';
    public bool $open = false;
    public ?string $error = null;

    public function mount(int $courseId, ?int $curriculumId = null): void
    {
        $this->courseId = $courseId;
        $this->curriculumId = $curriculumId;
        $this->messages = session($this->sessionKey(), []);
    }

    private function sessionKey(): string
    {
        return 'ai_tutor.' . Auth::id() . '.' . $this->courseId;
    }

    private function course(): ?Course
    {
        return Course::find($this->courseId);
    }

    public function send(GeminiTutorService $tutor): void
    {
        $this->error = null;
        $course = $this->course();
        $this->validate(['message' => 'required|string|max:' . GeminiTutorService::MAX_MESSAGE], [
            'message.required' => __('courses::courses.tutor_empty'),
            'message.max' => __('courses::courses.tutor_too_long'),
        ]);

        if (!$course || !$tutor->canUse(Auth::user(), $course)) {
            return;
        }

        // The lesson must belong to this course; anything else is ignored.
        $lesson = $this->curriculumId
            ? Curriculum::whereKey($this->curriculumId)->whereHas('section', fn ($q) => $q->where('course_id', $course->id))->first()
            : null;

        $question = $this->message;
        $result = $tutor->ask(Auth::user(), $course, $lesson, $question, $this->messages);
        $this->message = '';

        if (!$result['ok']) {
            $this->error = __('courses::courses.tutor_error_' . $result['error']);
            return;
        }

        $this->messages[] = ['role' => 'user', 'text' => $question];
        $this->messages[] = ['role' => 'model', 'text' => $result['text']];
        $this->messages = array_slice($this->messages, -20);
        session([$this->sessionKey() => $this->messages]);
    }

    public function clear(): void
    {
        $this->messages = [];
        $this->error = null;
        session()->forget($this->sessionKey());
    }

    public function render()
    {
        $course = $this->course();

        if (!$course || !app(GeminiTutorService::class)->canUse(Auth::user(), $course)) {
            return '<div></div>';
        }

        return view('livewire.frontend.ai-tutor');
    }
}
