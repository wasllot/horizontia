<?php

namespace Modules\Courses\Services\Lessons;

use Modules\Courses\Models\Curriculum;

/** A test. Stored under the historical type `assignment` (older courses already use it). */
class QuizHandler implements LessonHandler
{
    public function __construct(private ?QuizService $quizzes = null)
    {
        $this->quizzes ??= new QuizService();
    }

    public function type(): string { return 'assignment'; }

    public function rules(array $ctx = []): array
    {
        return ['quiz' => ['required', 'array']];
    }

    public function messages(): array
    {
        return ['quiz.required' => 'Agrega al menos una pregunta.'];
    }

    public function apply(Curriculum $lesson, array $data): array
    {
        $quiz = $this->quizzes->normalize($data['quiz']);   // throws a ValidationException with per-question messages

        return [
            'article_content' => json_encode($quiz, JSON_UNESCAPED_UNICODE),
            'media_path' => null,
            'content_length' => count($quiz['questions']) * 60,   // about a minute per question
        ];
    }

    public function isComplete(Curriculum $lesson): bool
    {
        return count($this->quizzes->load($lesson->article_content)['questions']) > 0;
    }
}
