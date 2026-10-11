<?php

namespace Modules\Courses\Services\Lessons;

use Illuminate\Validation\ValidationException;

/**
 * Quiz content lives as JSON in the lesson. Three rules keep it safe and honest:
 *  - the author's JSON is normalised on save (shape, bounds, plain-text strings);
 *  - students never receive the correct answers, only the questions ({@see forStudent});
 *  - grading is always done here, on the server ({@see grade}).
 */
class QuizService
{
    public const TYPES = ['single', 'multiple', 'true_false'];
    public const MAX_QUESTIONS = 100;

    /** Normalise whatever the builder sent (or an older stored quiz) into the canonical shape; throws on invalid content. */
    public function normalize(array|string|null $raw): array
    {
        $data = is_string($raw) ? (json_decode($raw, true) ?: []) : ($raw ?: []);
        $errors = [];
        $questions = [];
        $seen = [];

        foreach (array_values($data['questions'] ?? []) as $i => $q) {
            $n = $i + 1;
            $type = $q['type'] ?? 'single';
            $type = $type === 'multiple_choice' ? 'single' : $type;
            if (!in_array($type, self::TYPES, true)) {
                $type = 'single';
            }

            $text = trim(strip_tags((string) ($q['text'] ?? $q['question'] ?? '')));   // `question` is the pre-builder format
            if ($text === '') {
                $errors["content.quiz.$i"] = "La pregunta $n no tiene enunciado.";
                continue;
            }

            $options = $type === 'true_false'
                ? ['Verdadero', 'Falso']
                : array_values(array_filter(array_map(fn ($o) => trim(strip_tags((string) $o)), (array) ($q['options'] ?? [])), fn ($o) => $o !== ''));
            if (count($options) < 2) {
                $errors["content.quiz.$i"] = "La pregunta $n necesita al menos dos opciones.";
                continue;
            }
            $options = array_slice($options, 0, 8);

            $correct = $q['correct'] ?? 0;
            if ($type === 'multiple') {
                $correct = array_values(array_unique(array_filter(array_map('intval', (array) $correct), fn ($c) => $c >= 0 && $c < count($options))));
                sort($correct);
                if ($correct === []) {
                    $errors["content.quiz.$i"] = "Marca al menos una respuesta correcta en la pregunta $n.";
                    continue;
                }
            } else {
                $correct = is_array($correct) ? (int) ($correct[0] ?? 0) : (int) $correct;
                if ($correct < 0 || $correct >= count($options)) {
                    $errors["content.quiz.$i"] = "La respuesta correcta de la pregunta $n no es válida.";
                    continue;
                }
            }

            $id = (int) ($q['id'] ?? 0);
            while ($id <= 0 || isset($seen[$id])) {
                $id = ($seen ? max(array_keys($seen)) : 0) + 1;
            }
            $seen[$id] = true;

            $questions[] = [
                'id' => $id,
                'type' => $type,
                'text' => mb_substr($text, 0, 500),
                'options' => array_map(fn ($o) => mb_substr($o, 0, 300), $options),
                'correct' => $correct,
                'explanation' => mb_substr(trim(strip_tags((string) ($q['explanation'] ?? ''))), 0, 500),
            ];
        }

        if ($questions === [] && $errors === []) {
            $errors['content.quiz'] = 'Agrega al menos una pregunta.';
        }
        if (count($questions) > self::MAX_QUESTIONS) {
            $errors['content.quiz'] = 'Un test admite hasta ' . self::MAX_QUESTIONS . ' preguntas.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'pass_score' => max(0, min(100, (int) ($data['pass_score'] ?? 70))),
            'max_attempts' => max(0, min(20, (int) ($data['max_attempts'] ?? 0))),   // 0 = unlimited
            'show_answers' => (bool) ($data['show_answers'] ?? true),
            'shuffle' => (bool) ($data['shuffle'] ?? false),
            'questions' => $questions,
        ];
    }

    /** Decode a stored quiz without throwing (empty quiz when the content is missing or broken). */
    public function load(?string $json): array
    {
        $data = json_decode((string) $json, true);
        if (!is_array($data)) {
            return ['pass_score' => 70, 'max_attempts' => 0, 'show_answers' => true, 'shuffle' => false, 'questions' => []];
        }
        try {
            return $this->normalize($data);
        } catch (ValidationException) {
            return ['pass_score' => (int) ($data['pass_score'] ?? 70), 'max_attempts' => 0, 'show_answers' => true, 'shuffle' => false, 'questions' => []];
        }
    }

    /** What a student may see: questions and options, never the answers or the explanations. */
    public function forStudent(array $quiz): array
    {
        return [
            'pass_score' => $quiz['pass_score'],
            'max_attempts' => $quiz['max_attempts'],
            'shuffle' => $quiz['shuffle'],
            'questions' => array_map(fn ($q) => [
                'id' => $q['id'],
                'type' => $q['type'],
                'multiple' => $q['type'] === 'multiple',
                'text' => $q['text'],
                'options' => $q['options'],
            ], $quiz['questions']),
        ];
    }

    /**
     * @param array<int|string, int|array<int>> $answers  keyed by question id (older submissions used the question position)
     * @return array{score:int, passed:bool, correct:int, total:int, results:array<int, array>}
     */
    public function grade(array $quiz, array $answers): array
    {
        $results = [];
        $correctCount = 0;
        $positional = array_is_list($answers);   // older submissions are a plain list by position; current ones are keyed by question id
        foreach ($quiz['questions'] as $position => $q) {
            $given = $positional ? ($answers[$position] ?? null) : ($answers[$q['id']] ?? $answers[(string) $q['id']] ?? null);
            $ok = $this->isCorrect($q, $given);
            $correctCount += $ok ? 1 : 0;
            $results[$q['id']] = [
                'correct' => $ok,
                'answer' => $given,
                'correct_answer' => $q['correct'],
                'explanation' => $q['explanation'] ?? '',
            ];
        }
        $total = count($quiz['questions']);
        $score = $total > 0 ? (int) round($correctCount / $total * 100) : 0;

        return ['score' => $score, 'passed' => $score >= $quiz['pass_score'], 'correct' => $correctCount, 'total' => $total, 'results' => $results];
    }

    private function isCorrect(array $q, mixed $given): bool
    {
        if ($q['type'] === 'multiple') {
            $given = array_values(array_unique(array_map('intval', (array) $given)));
            sort($given);

            return $given !== [] && $given === array_values($q['correct']);
        }

        return $given !== null && $given !== '' && !is_array($given) && (int) $given === (int) $q['correct'];
    }
}
