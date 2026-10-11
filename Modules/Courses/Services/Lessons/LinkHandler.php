<?php

namespace Modules\Courses\Services\Lessons;

use Modules\Courses\Models\Curriculum;

/** Shared behaviour of the lessons that embed an external URL (YouTube, Vimeo, Genially). */
abstract class LinkHandler implements LessonHandler
{
    abstract protected function field(): string;

    /** Turn the pasted URL into the canonical value stored in media_path, or null when it is not valid for this provider. */
    abstract public function normalize(string $url): ?string;

    abstract protected function invalidMessage(): string;

    public function rules(array $ctx = []): array
    {
        return [
            $this->field() => ['required', 'string', 'max:500', function ($attr, $value, $fail) {
                if ($this->normalize((string) $value) === null) {
                    $fail($this->invalidMessage());
                }
            }],
            'duration' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }

    public function messages(): array
    {
        return [$this->field() . '.required' => $this->invalidMessage()];
    }

    public function apply(Curriculum $lesson, array $data): array
    {
        $attrs = ['media_path' => $this->normalize((string) $data[$this->field()]), 'article_content' => null];
        if (isset($data['duration'])) {
            $attrs['content_length'] = (int) $data['duration'];
        }

        return $attrs;
    }

    public function isComplete(Curriculum $lesson): bool
    {
        return !empty($lesson->media_path);
    }
}
