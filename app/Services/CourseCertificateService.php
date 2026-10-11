<?php

namespace App\Services;

use App\Models\CourseCertificate;
use App\Models\User;
use Illuminate\Support\Str;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Watchtime;

/** Emite certificados verificables al terminar un curso en línea. Idempotente: una persona, un certificado por curso. */
class CourseCertificateService
{
    public static function enabled(): bool
    {
        return (bool) config('academy.certificates', true);
    }

    /** Avance (0-100) según el tiempo visto contra la duración del curso. */
    public function progress(User $student, Course $course): int
    {
        if ((int) $course->content_length <= 0) {
            return 0;
        }
        $watched = (int) Watchtime::where('user_id', $student->id)->where('course_id', $course->id)->sum('duration');

        $pct = (int) floor($watched / $course->content_length * 100);

        return $pct >= 99 ? 100 : $pct;   // igual que el aula: 99 % cuenta como curso terminado
    }

    public function find(User $student, Course $course): ?CourseCertificate
    {
        return CourseCertificate::where('user_id', $student->id)->where('course_id', $course->id)->first();
    }

    /** Emite el certificado si el curso está completo. Devuelve el certificado (nuevo o el que ya tenía) o null. */
    public function issueIfComplete(User $student, Course $course): ?CourseCertificate
    {
        if (!self::enabled() || $course->delivery === 'in_person') {
            return null;
        }
        if ($existing = $this->find($student, $course)) {
            return $existing;
        }
        if ($this->progress($student, $course) < 100) {
            return null;
        }

        $cert = $this->issue($student, $course);
        app(LearningPathService::class)->onCourseCompleted($student, $course);

        return $cert;
    }

    public function issue(User $student, Course $course): CourseCertificate
    {
        return CourseCertificate::firstOrCreate(['user_id' => $student->id, 'course_id' => $course->id], [
            'code' => $this->newCode(),
            'student_name' => trim((string) ($student->profile?->full_name ?: $student->email)),
            'course_title' => trim(html_entity_decode(strip_tags((string) $course->title))),
            'instructor_name' => $course->instructor?->profile?->full_name,
            'content_seconds' => (int) $course->content_length,
            'issued_at' => now(),
        ]);
    }

    /** Código corto, legible y difícil de adivinar: HZ-XXXX-XXXX-XXXX. */
    public function newCode(): string
    {
        do {
            $code = 'HZ-' . implode('-', str_split(strtoupper(Str::random(12)), 4));
            $code = strtr($code, ['0' => '7', 'O' => 'K', 'I' => 'M', '1' => '9', 'L' => 'N']);   // sin caracteres que se confunden al leerlos
        } while (CourseCertificate::where('code', $code)->exists());

        return $code;
    }

    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', trim((string) $code)));
    }

    /** Emite los certificados que ya se ganaron antes de existir esta función. @return int cuántos se emitieron */
    public function backfill(): int
    {
        $issued = 0;
        Watchtime::query()->select('user_id', 'course_id')->distinct()->orderBy('user_id')->chunk(200, function ($rows) use (&$issued) {
            foreach ($rows as $row) {
                $student = User::find($row->user_id);
                $course = Course::find($row->course_id);
                if ($student && $course && !$this->find($student, $course) && $this->issueIfComplete($student, $course)) {
                    $issued++;
                }
            }
        });

        return $issued;
    }
}
