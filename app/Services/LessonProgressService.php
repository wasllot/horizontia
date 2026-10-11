<?php

namespace App\Services;

use App\Models\LessonPosition;
use App\Models\User;
use Modules\Courses\Models\Curriculum;

/** Retomar dentro de una lección: guarda y devuelve el segundo del video donde se quedó cada alumno. */
class LessonProgressService
{
    /** Antes de este segundo no vale la pena retomar; a menos de este margen del final se considera terminada. */
    public const MIN_RESUME = 8;
    public const END_MARGIN = 15;

    public function save(User $user, Curriculum $lesson, int $seconds): void
    {
        $length = (int) $lesson->content_length;
        $seconds = max(0, $length > 0 ? min($seconds, $length) : $seconds);
        // Si terminó la lección, la próxima vez empieza desde el principio.
        if ($length > 0 && $seconds >= $length - self::END_MARGIN) {
            $seconds = 0;
        }
        LessonPosition::updateOrCreate(['user_id' => $user->id, 'curriculum_id' => $lesson->id], ['seconds' => $seconds]);
    }

    /** Segundo desde el que retomar (0 = desde el inicio). */
    public function resumeAt(?User $user, int $curriculumId): int
    {
        if (!$user) {
            return 0;
        }
        $seconds = (int) LessonPosition::where('user_id', $user->id)->where('curriculum_id', $curriculumId)->value('seconds');

        return $seconds >= self::MIN_RESUME ? $seconds : 0;
    }

    public static function clock(int $seconds): string
    {
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
