<?php

namespace Modules\Courses\Services\Lessons;

use Modules\Courses\Models\Curriculum;

class ArticleHandler implements LessonHandler
{
    public const WORDS_PER_MINUTE = 238;

    public function type(): string { return 'article'; }

    public function rules(array $ctx = []): array
    {
        return ['article' => ['required', 'string', 'max:500000', function ($attr, $value, $fail) {
            $clean = cleanLessonHtml((string) $value);
            if (trim(strip_tags((string) $clean)) === '' && !preg_match('/<(img|iframe)\b/i', (string) $clean)) {
                $fail('Escribe el contenido del artículo.');
            }
        }]];
    }

    public function messages(): array
    {
        return ['article.required' => 'Escribe el contenido del artículo.'];
    }

    public function apply(Curriculum $lesson, array $data): array
    {
        $html = cleanLessonHtml((string) $data['article']);
        $words = str_word_count(strip_tags($html));

        return [
            'article_content' => $html,
            'media_path' => null,
            'content_length' => (int) max(1, ceil($words / self::WORDS_PER_MINUTE)) * 60,
        ];
    }

    public function isComplete(Curriculum $lesson): bool
    {
        return trim(strip_tags((string) $lesson->article_content)) !== '' || preg_match('/<(img|iframe)\b/i', (string) $lesson->article_content) === 1;
    }
}
