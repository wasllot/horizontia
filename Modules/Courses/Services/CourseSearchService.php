<?php

namespace Modules\Courses\Services;

use App\Models\SearchLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Courses\Models\Course;

/**
 * Búsqueda de cursos por relevancia: sin acentos ni mayúsculas, con plurales y errores de tecleo (distancia de edición),
 * pesa más el título que el subtítulo, la categoría y la descripción. Si no hay resultados sugiere cómo se escribe.
 */
class CourseSearchService
{
    private const STOPWORDS = ['de', 'del', 'la', 'las', 'el', 'los', 'un', 'una', 'unos', 'unas', 'y', 'o', 'en', 'para', 'por', 'con', 'sin', 'al', 'a', 'que', 'como', 'mi', 'mis', 'su', 'sus', 'curso', 'cursos', 'quiero', 'aprender'];
    private const CORPUS_TTL = 600;

    /** Palabras sin acentos, en minúsculas y con el plural simple quitado. @return array<int, string> */
    public static function tokens(string $text): array
    {
        $ascii = Str::lower(Str::ascii($text));
        $words = preg_split('/[^a-z0-9]+/', $ascii, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(array_map(fn ($w) => self::stem($w), array_filter($words, fn ($w) => !in_array($w, self::STOPWORDS, true) && strlen($w) >= 2)))));
    }

    private static function stem(string $w): string
    {
        if (strlen($w) > 4 && str_ends_with($w, 'es')) {
            return substr($w, 0, -2);
        }

        return strlen($w) > 3 && str_ends_with($w, 's') ? substr($w, 0, -1) : $w;
    }

    /** @return array<int, array{id:int, title:array<int,string>, subtitle:array<int,string>, category:array<int,string>, body:array<int,string>}> */
    public function corpus(): array
    {
        return Cache::remember('course_search_corpus', self::CORPUS_TTL, function () {
            return Course::query()->whereNull('deleted_at')->with('category:id,name')->get(['id', 'title', 'subtitle', 'description', 'category_id'])->map(fn ($c) => [
                'id' => (int) $c->id,
                'title' => self::tokens(html_entity_decode((string) $c->title)),
                'subtitle' => self::tokens(html_entity_decode((string) $c->subtitle)),
                'category' => self::tokens((string) $c->category?->name),
                'body' => self::tokens(Str::limit(trim(strip_tags(html_entity_decode((string) $c->description))), 600, '')),
            ])->all();
        });
    }

    public static function flush(): void
    {
        Cache::forget('course_search_corpus');
    }

    /** Cuánto se parece una palabra buscada a una lista de palabras: 0 si nada. */
    private function match(string $t, array $words, int $exact, int $prefix, int $part, int $fuzzy): int
    {
        $best = 0;
        foreach ($words as $w) {
            if ($w === $t) {
                return $exact;
            }
            if (strlen($t) >= 3 && str_starts_with($w, $t)) {
                $best = max($best, $prefix);
            } elseif (strlen($t) >= 4 && str_contains($w, $t)) {
                $best = max($best, $part);
            } elseif ($fuzzy > 0 && strlen($t) >= 4 && abs(strlen($w) - strlen($t)) <= 2 && levenshtein($t, $w) <= (strlen($t) > 6 ? 2 : 1)) {
                $best = max($best, $fuzzy);
            }
        }

        return $best;
    }

    /** IDs de curso de lo más a lo menos relevante (vacío si nada coincide). @return array<int, int> */
    public function rank(string $query, int $limit = 500): array
    {
        $tokens = self::tokens($query);
        if ($tokens === []) {
            return [];
        }
        $phrase = implode(' ', $tokens);

        $scores = [];
        foreach ($this->corpus() as $c) {
            $score = 0;
            $hits = 0;
            foreach ($tokens as $t) {
                $s = max(
                    $this->match($t, $c['title'], 10, 8, 6, 5),
                    $this->match($t, $c['subtitle'], 4, 3, 2, 0),
                    $this->match($t, $c['category'], 5, 4, 3, 2),
                    $this->match($t, $c['body'], 1, 1, 0, 0),
                );
                if ($s > 0) {
                    $hits++;
                    $score += $s;
                }
            }
            if ($hits === 0) {
                continue;
            }
            if ($hits === count($tokens)) {
                $score += 10 + (count($tokens) > 1 && str_contains(implode(' ', $c['title']), $phrase) ? 15 : 0);
            }
            $scores[$c['id']] = $score;
        }
        arsort($scores);

        return array_slice(array_keys($scores), 0, $limit);
    }

    /** «¿Quisiste decir…?»: la palabra del catálogo más parecida a lo escrito, cuando la búsqueda no encuentra nada. */
    public function suggest(string $query): ?string
    {
        $tokens = self::tokens($query);
        if ($tokens === []) {
            return null;
        }
        $vocab = [];
        foreach ($this->corpus() as $c) {
            foreach (array_merge($c['title'], $c['category']) as $w) {
                if (strlen($w) >= 4) {
                    $vocab[$w] = ($vocab[$w] ?? 0) + 1;
                }
            }
        }

        $fixed = [];
        $changed = false;
        foreach ($tokens as $t) {
            if (isset($vocab[$t]) || strlen($t) < 4) {
                $fixed[] = $t;
                continue;
            }
            $best = null;
            $bestD = 99;
            foreach ($vocab as $w => $n) {
                $d = levenshtein($t, $w);
                if ($d < $bestD || ($d === $bestD && $n > ($vocab[$best] ?? 0))) {
                    [$best, $bestD] = [$w, $d];
                }
            }
            if ($best !== null && $bestD <= (strlen($t) > 6 ? 3 : 2)) {
                $fixed[] = $best;
                $changed = true;
            } else {
                $fixed[] = $t;
            }
        }
        $suggestion = implode(' ', $fixed);

        return $changed && $this->rank($suggestion, 1) !== [] ? $suggestion : null;
    }

    /** Guarda la búsqueda para el reporte de «lo que buscan y no encuentran». Teclear una frase cuenta una sola vez. */
    public function log(string $query, int $results, ?int $userId): void
    {
        $query = Str::limit(trim(Str::lower(preg_replace('/\s+/', ' ', strip_tags($query)))), 80, '');
        if (mb_strlen($query) < 3) {
            return;
        }
        $key = 'search_log_last:' . ($userId ?: (request()->hasSession() ? request()->session()->getId() : 'anon'));
        $last = Cache::get($key);
        if ($last && now()->timestamp - $last['at'] <= 20 && (str_starts_with($query, $last['q']) || str_starts_with($last['q'], $query))) {
            SearchLog::whereKey($last['id'])->update(['query' => $query, 'results' => $results]);   // sigue escribiendo: se actualiza la misma fila
            Cache::put($key, ['id' => $last['id'], 'q' => $query, 'at' => now()->timestamp], 60);

            return;
        }
        $row = SearchLog::create(['query' => $query, 'results' => $results, 'user_id' => $userId]);
        Cache::put($key, ['id' => $row->id, 'q' => $query, 'at' => now()->timestamp], 60);
    }
}
