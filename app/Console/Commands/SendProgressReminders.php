<?php

namespace App\Console\Commands;

use App\Mail\ProgressReminderEmail;
use App\Models\ProgressReminder;
use App\Models\User;
use App\Services\CourseCertificateService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Watchtime;
use Modules\Courses\Services\ResumeService;

class SendProgressReminders extends Command
{
    protected $signature = 'app:send-progress-reminders {--dry-run : List who would be e-mailed without sending} {--force-hours : Send outside the 9:00–20:00 window}';

    protected $description = 'Reminds students to resume online courses they have stopped (after 5, then 7 and 14 more days of silence; max 3 in a row)';

    /** Días de silencio antes de cada aviso consecutivo (el contador vuelve a 0 cuando la persona regresa). */
    private const GAPS_DAYS = [5, 7, 14];
    private const MAX_COURSES_PER_EMAIL = 3;

    public function handle(CourseCertificateService $certificates, ResumeService $resume): int
    {
        if (!config('academy.progress_reminders')) {
            $this->info('Recordatorios de avance desactivados (ACADEMY_PROGRESS_REMINDERS=false).');

            return self::SUCCESS;
        }
        $now = now(config('academy.timezone'));
        if (!$this->option('force-hours') && ($now->hour < 9 || $now->hour >= 20)) {
            $this->info('Fuera del horario de envío (9:00–20:00).');

            return self::SUCCESS;
        }

        $sent = 0;
        $byStudent = Enrollment::query()->where('status', Enrollment::STATUSES['active'])->whereHas('course', fn ($q) => $q->where('delivery', 'online'))->with('course')->get()->groupBy('student_id');

        foreach ($byStudent as $studentId => $enrollments) {
            $user = User::with('profile')->find($studentId);
            if (!$user || !$user->email || $user->progress_reminders_off_at || !$user->hasRole('student')) {
                continue;
            }

            $due = [];
            foreach ($enrollments as $enrollment) {
                $course = $enrollment->course;
                if (!$course || $course->getRawOriginal('status') != Course::STATUSES['active'] || (int) $course->content_length <= 0) {
                    continue;
                }
                $progress = $certificates->progress($user, $course);
                if ($progress >= 99) {
                    continue;
                }
                $item = $this->dueItem($user, $course, $enrollment, $progress, $resume, $now);
                if ($item) {
                    $due[] = $item;
                }
            }
            if ($due === []) {
                continue;
            }
            usort($due, fn ($a, $b) => $b['progress'] <=> $a['progress']);   // lo más avanzado primero: es lo más fácil de terminar
            $due = array_slice($due, 0, self::MAX_COURSES_PER_EMAIL);

            $this->line("{$user->email}: " . implode(', ', array_map(fn ($d) => html_entity_decode($d['title']) . " ({$d['progress']}%)", $due)));
            if ($this->option('dry-run')) {
                continue;
            }

            Mail::to($user->email)->queue(new ProgressReminderEmail(
                $user, $user->profile?->first_name ?: 'estudiante', array_map(fn ($d) => collect($d)->except(['course_id', 'count'])->all(), $due),
                URL::signedRoute('progress-reminders.off', ['user' => $user->id])
            ));
            foreach ($due as $d) {
                ProgressReminder::updateOrCreate(['user_id' => $user->id, 'course_id' => $d['course_id']], ['count' => $d['count'] + 1, 'last_sent_at' => now()]);
            }
            $sent++;
        }

        $this->info("Recordatorios enviados: {$sent}");

        return self::SUCCESS;
    }

    /** @return array{course_id:int, count:int, title:string, progress:int, lesson:?string, url:string, started:bool}|null */
    private function dueItem(User $user, Course $course, Enrollment $enrollment, int $progress, ResumeService $resume, Carbon $now): ?array
    {
        $lastWatch = Watchtime::where('user_id', $user->id)->where('course_id', $course->id)->max('updated_at');
        $lastActivity = Carbon::parse($lastWatch ?: $enrollment->created_at);
        $reminder = ProgressReminder::where('user_id', $user->id)->where('course_id', $course->id)->first();

        $count = $reminder?->count ?? 0;
        $since = $lastActivity;
        if ($reminder?->last_sent_at) {
            if ($lastActivity->gt($reminder->last_sent_at)) {
                $count = 0;                       // volvió a estudiar después del último aviso: se empieza de nuevo
            } else {
                $since = $reminder->last_sent_at; // sigue en silencio: se cuenta desde el último aviso
            }
        }
        if ($count >= count(self::GAPS_DAYS) || $since->gt($now->copy()->subDays(self::GAPS_DAYS[$count]))) {
            return null;
        }

        $next = $resume->resume($user, $course);

        return [
            'course_id' => $course->id, 'count' => $count, 'title' => (string) $course->title, 'progress' => $progress, 'started' => $progress > 0,
            'lesson' => $next ? (string) $next['lesson']->title : null,
            'url' => route('courses.course-taking', ['slug' => $course->slug] + ($next ? ['leccion' => $next['lesson']->id] : [])),
        ];
    }
}
