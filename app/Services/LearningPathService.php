<?php

namespace App\Services;

use App\Models\CourseCertificate;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;

/** Avance de una persona en una ruta y emisión del certificado de la ruta completa. */
class LearningPathService
{
    public function __construct(private CourseCertificateService $certificates)
    {
    }

    /** Cursos que se pueden estudiar hoy: publicados y en línea. */
    public function eligibleCourses(): Collection
    {
        return Course::where('status', Course::STATUSES['active'])->where('delivery', 'online')->orderBy('title')->get(['id', 'title', 'slug', 'content_length']);
    }

    public function isPublic(LearningPath $path): bool
    {
        return $path->isActive() && $path->courses->filter(fn ($c) => $c->status === 'active')->count() >= 2;
    }

    public function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'ruta';
        $slug = $base;
        $i = 2;
        while (LearningPath::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /**
     * Estado de cada paso para una persona (o solo la lista de cursos si no ha entrado).
     *
     * @return array{steps: array<int, array{course: Course, owned: bool, progress: int, done: bool, current: bool}>, done: int, total: int, percent: int, next: ?Course, complete: bool}
     */
    public function status(LearningPath $path, ?User $student): array
    {
        $courses = $path->courses->filter(fn ($c) => $c->status === 'active')->values();
        $owned = $student ? Enrollment::where('student_id', $student->id)->whereIn('course_id', $courses->pluck('id'))->pluck('course_id')->all() : [];

        $steps = [];
        $next = null;
        foreach ($courses as $course) {
            $has = in_array($course->id, $owned, true);
            $progress = ($student && $has) ? $this->certificates->progress($student, $course) : 0;
            $done = $progress >= 100;
            $current = !$done && $next === null;
            if ($current) {
                $next = $course;
            }
            $steps[] = ['course' => $course, 'owned' => $has, 'progress' => $progress, 'done' => $done, 'current' => $current];
        }
        $doneCount = collect($steps)->where('done', true)->count();
        $total = count($steps);

        return ['steps' => $steps, 'done' => $doneCount, 'total' => $total, 'percent' => $total ? (int) floor($doneCount / $total * 100) : 0, 'next' => $next, 'complete' => $total > 0 && $doneCount === $total];
    }

    public function findCertificate(User $student, LearningPath $path): ?CourseCertificate
    {
        return CourseCertificate::where('user_id', $student->id)->where('learning_path_id', $path->id)->first();
    }

    /** Emite el certificado de la ruta cuando todos sus cursos están terminados. */
    public function issueIfComplete(User $student, LearningPath $path): ?CourseCertificate
    {
        if (!CourseCertificateService::enabled()) {
            return null;
        }
        if ($existing = $this->findCertificate($student, $path)) {
            return $existing;
        }
        $status = $this->status($path->loadMissing('courses'), $student);
        if (!$status['complete'] || $status['total'] < 2) {
            return null;
        }

        return CourseCertificate::firstOrCreate(['user_id' => $student->id, 'learning_path_id' => $path->id], [
            'code' => $this->certificates->newCode(),
            'student_name' => trim((string) ($student->profile?->full_name ?: $student->email)),
            'course_title' => trim(strip_tags($path->title)),
            'content_seconds' => (int) collect($status['steps'])->sum(fn ($s) => (int) $s['course']->content_length),
            'issued_at' => now(),
        ]);
    }

    /** Se llama al terminar un curso: revisa las rutas activas que lo incluyen. */
    public function onCourseCompleted(User $student, Course $course): void
    {
        LearningPath::active()->whereHas('courses', fn ($q) => $q->where($course->getQualifiedKeyName(), $course->id))->get()
            ->each(fn ($path) => $this->issueIfComplete($student, $path));
    }

    /** Rutas activas que incluyen un curso (para sugerirlas en la página del curso). */
    public function pathsFor(Course $course): Collection
    {
        return LearningPath::active()->whereHas('courses', fn ($q) => $q->where($course->getQualifiedKeyName(), $course->id))->with('courses')->get()->filter(fn ($p) => $this->isPublic($p))->values();
    }
}
