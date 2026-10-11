<?php

namespace Modules\Courses\Services;

use App\Mail\LiveStreamScheduledEmail;
use Illuminate\Support\Facades\Mail;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseLiveStream;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;

/**
 * Live sessions of a course, either stand-alone or owned by a "live" lesson. One place for the rules
 * (who may touch it, announcements, reminders, join window) so the wizard, the sessions screen and the player agree.
 */
class LiveSessionService
{
    /** Minutes before the start from which students may join. */
    public const JOIN_WINDOW_MINUTES = 15;

    public const PROVIDERS = ['meet' => 'Google Meet', 'zoom' => 'Zoom', 'teams' => 'Microsoft Teams', 'other' => 'Otro'];

    /** Guess the provider from a meeting URL. */
    public static function providerFor(?string $url): ?string
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));

        return match (true) {
            $host === '' => null,
            str_contains($host, 'meet.google') => 'meet',
            str_contains($host, 'zoom.') => 'zoom',
            str_contains($host, 'teams.') => 'teams',
            default => 'other',
        };
    }

    public function create(Course $course, array $data, ?Curriculum $lesson = null, bool $announce = true): CourseLiveStream
    {
        $stream = CourseLiveStream::create([
            'course_id' => $course->id,
            'curriculum_id' => $lesson?->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'meeting_link' => $data['meeting_link'] ?? null,
            'provider' => self::providerFor($data['meeting_link'] ?? null),
            'date_time' => $data['date_time'],
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'notify_hours_before' => $data['notify_hours_before'] ?? 24,
            'status' => CourseLiveStream::STATUS_SCHEDULED,
        ]);

        if ($announce) {
            $this->announce($stream);
        }

        return $stream;
    }

    public function update(CourseLiveStream $stream, array $data): CourseLiveStream
    {
        $stream->update(array_filter([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'meeting_link' => $data['meeting_link'] ?? null,
            'provider' => array_key_exists('meeting_link', $data) ? self::providerFor($data['meeting_link']) : null,
            'date_time' => $data['date_time'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'notify_hours_before' => $data['notify_hours_before'] ?? null,
            'status' => $data['status'] ?? null,
            'recording_url' => $data['recording_url'] ?? null,
        ], fn ($v) => $v !== null));

        return $stream->refresh();
    }

    /** Students of the course as [email, name] pairs. */
    public function recipients(Course $course): array
    {
        $out = [];
        foreach (Enrollment::with('student.user')->where('course_id', $course->id)->get() as $enrollment) {
            $user = $enrollment->student?->user;
            if ($user?->email) {
                $out[$user->email] = $enrollment->student->full_name ?: $user->email;
            }
        }

        return $out;
    }

    public function announce(CourseLiveStream $stream): int
    {
        $stream->loadMissing('course');
        $sent = 0;
        foreach ($this->recipients($stream->course) as $email => $name) {
            Mail::to($email)->queue(new LiveStreamScheduledEmail($stream, $name));
            $sent++;
        }

        return $sent;
    }

    /** Where the session is in time: upcoming | soon (join window open) | live | ended | cancelled. */
    public static function phase(CourseLiveStream $stream, ?\DateTimeInterface $now = null): string
    {
        $now = \Carbon\Carbon::instance($now ?? now());
        if ($stream->status == CourseLiveStream::STATUS_CANCELLED) {
            return 'cancelled';
        }
        $start = $stream->date_time;
        $end = $start->copy()->addMinutes($stream->duration_minutes ?: 60);

        return match (true) {
            $stream->status == CourseLiveStream::STATUS_COMPLETED || $now->gte($end) => 'ended',
            $now->gte($start) => 'live',
            $now->gte($start->copy()->subMinutes(self::JOIN_WINDOW_MINUTES)) => 'soon',
            default => 'upcoming',
        };
    }

    /** iCalendar (.ics) body so a student can add the session to their calendar. */
    public static function ics(CourseLiveStream $stream): string
    {
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $esc = fn ($t) => addcslashes(str_replace(["\r", "\n"], ['', '\\n'], (string) $t), ',;\\');
        $end = $stream->date_time->copy()->addMinutes($stream->duration_minutes ?: 60);

        return implode("\r\n", array_filter([
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Horizontia//Live//ES', 'CALSCALE:GREGORIAN', 'BEGIN:VEVENT',
            'UID:live-' . $stream->id . '@' . parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:' . $fmt(now()), 'DTSTART:' . $fmt($stream->date_time), 'DTEND:' . $fmt($end),
            'SUMMARY:' . $esc($stream->title),
            $stream->description ? 'DESCRIPTION:' . $esc(strip_tags($stream->description)) : null,
            $stream->meeting_link ? 'URL:' . $stream->meeting_link : null,
            'END:VEVENT', 'END:VCALENDAR',
        ])) . "\r\n";
    }
}
