<?php

namespace Modules\Courses\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;

/**
 * Recomendaciones: lo que otros alumnos de tus cursos también tomaron + cursos de la misma categoría.
 * Solo cursos publicados que la persona todavía no tiene. Cada recomendación explica por qué se sugiere.
 */
class CourseRecommendationService
{
    /** @return Collection<int, Course> cada curso trae ->reason */
    public function forStudent(User $student, int $limit = 6): Collection
    {
        $owned = Enrollment::where('student_id', $student->id)->pluck('course_id')->all();

        return $this->recommend($owned, $owned, $limit, 'Porque estudias');
    }

    /** Cursos relacionados con uno (para su página). @return Collection<int, Course> */
    public function related(Course $course, ?User $viewer = null, int $limit = 4): Collection
    {
        $exclude = array_merge([$course->id], $viewer ? Enrollment::where('student_id', $viewer->id)->pluck('course_id')->all() : []);

        return $this->recommend([$course->id], $exclude, $limit, 'Otros alumnos de');
    }

    /**
     * @param array<int, int> $basis courses the suggestion is built from
     * @param array<int, int> $exclude never suggested
     */
    private function recommend(array $basis, array $exclude, int $limit, string $reasonPrefix): Collection
    {
        $active = Course::STATUSES['active'];
        $scores = [];
        $why = [];

        if ($basis !== []) {
            $table = (new Enrollment())->getTable();
            // Alumnos que tomaron alguno de los cursos base → qué otros cursos tomaron (cuántos alumnos en común).
            $rows = DB::table("$table as a")->join("$table as b", 'a.student_id', '=', 'b.student_id')
                ->whereIn('a.course_id', $basis)->whereNotIn('b.course_id', $exclude)
                ->groupBy('b.course_id', 'a.course_id')->selectRaw('b.course_id as cid, a.course_id as base, COUNT(DISTINCT a.student_id) as shared')->get();
            foreach ($rows as $r) {
                $scores[$r->cid] = ($scores[$r->cid] ?? 0) + $r->shared * 3;
                if (!isset($why[$r->cid]) || $r->shared > $why[$r->cid]['shared']) {
                    $why[$r->cid] = ['base' => $r->base, 'shared' => $r->shared];
                }
            }
            // Misma categoría que lo que ya tiene.
            $categories = Course::whereIn('id', $basis)->pluck('category_id')->filter()->countBy();
            foreach (Course::where('status', $active)->whereIn('category_id', $categories->keys())->whereNotIn('id', $exclude)->get(['id', 'category_id']) as $c) {
                $scores[$c->id] = ($scores[$c->id] ?? 0) + 2 * ($categories[$c->category_id] ?? 1);
                $why[$c->id] ??= ['base' => Course::whereIn('id', $basis)->where('category_id', $c->category_id)->value('id'), 'shared' => 0];
            }
        }

        // Relleno por popularidad para no dejar vacío el bloque en una academia nueva.
        $popular = Course::where('status', $active)->whereNotIn('id', $exclude)->withCount('enrollments')->orderByDesc('enrollments_count')->orderByDesc('id')->limit($limit)->pluck('id');
        foreach ($popular as $i => $id) {
            $scores[$id] = ($scores[$id] ?? 0) + 0.5 - $i * 0.01;
        }

        arsort($scores);
        $ids = array_slice(array_keys($scores), 0, $limit * 2);
        $courses = Course::where('status', $active)->whereIn('id', $ids)->with(['pricing', 'thumbnail', 'category'])->get()->keyBy('id');
        $titles = Course::whereIn('id', collect($why)->pluck('base')->filter()->unique())->pluck('title', 'id');

        return collect($ids)->map(fn ($id) => $courses[$id] ?? null)->filter()->take($limit)->map(function ($c) use ($why, $titles, $reasonPrefix) {
            $base = $why[$c->id]['base'] ?? null;
            $c->reason = $base && isset($titles[$base]) ? $reasonPrefix . ' «' . html_entity_decode($titles[$base]) . '»' . ($reasonPrefix === 'Otros alumnos de' ? ' también tomaron este' : '') : 'Popular entre los alumnos';

            return $c;
        })->values();
    }
}
