<?php

namespace App\Services;

use App\Mail\CommunityQuestionEmail;
use App\Mail\CommunityReplyEmail;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Courses\Models\Course;
use Modules\Courses\Services\CourseAccessService;

/** Reglas de la comunidad de cada curso: quién entra, quién modera, límites de uso y avisos por correo. */
class CommunityService
{
    public const PER_MINUTE = 4;
    public const PER_DAY = 40;

    public static function enabled(): bool
    {
        return (bool) config('academy.community', true);
    }

    public function canJoin(?User $user, Course $course): bool
    {
        return self::enabled() && app(CourseAccessService::class)->canAccessCourse($user, $course);
    }

    /** Instructor del curso o administrador. */
    public function canModerate(?User $user, Course $course): bool
    {
        return $user && (in_array($user->role, ['admin', 'sub_admin'], true) || (int) $course->instructor_id === (int) $user->id);
    }

    /** «Ana R.» — nombre y la inicial del apellido, para no exponer nombres completos entre alumnos. */
    public static function displayName(?User $user): string
    {
        $p = $user?->profile;
        $first = trim((string) ($p?->first_name ?: ''));
        $last = trim((string) ($p?->last_name ?: ''));
        $name = trim($first . ($last !== '' ? ' ' . mb_substr($last, 0, 1) . '.' : ''));

        return $name !== '' ? $name : 'Alumno';
    }

    /** @return string|null motivo del bloqueo, o null si puede publicar */
    public function throttle(User $user): ?string
    {
        $dayKey = 'community_daily:' . $user->id . ':' . now()->format('Ymd');
        if (RateLimiter::tooManyAttempts('community:' . $user->id, self::PER_MINUTE)) {
            return 'Vas muy rápido. Espera un minuto antes de publicar de nuevo.';
        }
        if ((int) Cache::get($dayKey, 0) >= self::PER_DAY) {
            return 'Llegaste al límite de mensajes de hoy. Vuelve mañana.';
        }
        RateLimiter::hit('community:' . $user->id, 60);
        Cache::put($dayKey, (int) Cache::get($dayKey, 0) + 1, now()->endOfDay());

        return null;
    }

    public static function clean(string $text, int $max): string
    {
        return mb_substr(trim(preg_replace("/\n{3,}/", "\n\n", str_replace("\r", '', strip_tags($text)))), 0, $max);
    }

    public function ask(User $user, Course $course, string $title, string $body, ?int $curriculumId = null): CommunityPost
    {
        $post = CommunityPost::create(['course_id' => $course->id, 'curriculum_id' => $curriculumId, 'user_id' => $user->id, 'title' => self::clean($title, 140), 'body' => self::clean($body, 3000), 'last_activity_at' => now()]);

        // Al instructor se le avisa, pero como máximo un correo por hora y curso (no se satura si hay muchas preguntas).
        if (!$this->canModerate($user, $course) && $course->instructor?->email && Cache::add('community_notify:' . $course->id, 1, now()->addHour())) {
            Mail::to($course->instructor->email)->queue(new CommunityQuestionEmail($post->load('author'), $course));
        }

        return $post;
    }

    public function reply(User $user, Course $course, CommunityPost $root, string $body): CommunityPost
    {
        $reply = DB::transaction(function () use ($user, $course, $root, $body) {
            $reply = CommunityPost::create(['course_id' => $course->id, 'curriculum_id' => $root->curriculum_id, 'user_id' => $user->id, 'parent_id' => $root->id, 'body' => self::clean($body, 3000), 'last_activity_at' => now()]);
            $root->update(['last_activity_at' => now()]);

            return $reply;
        });

        $author = $root->author;
        if ($author && $author->id !== $user->id && $author->email) {
            Mail::to($author->email)->queue(new CommunityReplyEmail($root, $reply->load('author'), $course, $this->canModerate($user, $course)));
        }

        return $reply;
    }

    public function toggleLike(User $user, CommunityPost $post): bool
    {
        return DB::transaction(function () use ($user, $post) {
            $exists = DB::table('community_likes')->where('post_id', $post->id)->where('user_id', $user->id)->exists();
            if ($exists) {
                DB::table('community_likes')->where('post_id', $post->id)->where('user_id', $user->id)->delete();
            } else {
                DB::table('community_likes')->insert(['post_id' => $post->id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            $post->update(['likes_count' => DB::table('community_likes')->where('post_id', $post->id)->count()]);

            return !$exists;
        });
    }

    public function report(User $user, CommunityPost $post): void
    {
        DB::transaction(function () use ($user, $post) {
            if (!DB::table('community_reports')->where('post_id', $post->id)->where('user_id', $user->id)->exists()) {
                DB::table('community_reports')->insert(['post_id' => $post->id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            $post->update(['reports_count' => DB::table('community_reports')->where('post_id', $post->id)->count()]);
        });
    }
}
