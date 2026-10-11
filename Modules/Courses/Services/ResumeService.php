<?php

namespace Modules\Courses\Services;

use App\Models\User;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Watchtime;

/** Where a student left off: the first lesson (in course order) they have not completed yet. */
class ResumeService
{
    /**
     * @return array{lesson: Curriculum, done: int, total: int}|null null when the course has no lessons or everything is done
     */
    public function resume(User $student, Course $course): ?array
    {
        $lessons = $course->sections()->with(['curriculums' => fn ($q) => $q->withContent()->orderBy('sort_order')])->get()
            ->flatMap(fn ($s) => $s->curriculums)->values();
        if ($lessons->isEmpty()) {
            return null;
        }

        $watched = Watchtime::where('user_id', $student->id)->whereIn('curriculum_id', $lessons->pluck('id'))->pluck('duration', 'curriculum_id');
        $isDone = fn (Curriculum $l) => (int) $l->content_length > 0 && (int) ($watched[$l->id] ?? 0) >= (int) $l->content_length;
        $done = $lessons->filter($isDone)->count();
        $next = $lessons->first(fn ($l) => !$isDone($l));

        return $next ? ['lesson' => $next, 'done' => $done, 'total' => $lessons->count()] : null;
    }
}
