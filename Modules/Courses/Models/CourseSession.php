<?php

namespace Modules\Courses\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One date of an in-person (or hybrid) course. Times are stored in UTC and always shown in the session's own timezone,
 * because attendees care about the clock at the venue, not at the server.
 */
class CourseSession extends Model
{
    use HasFactory;

    public const SCHEDULED = 'scheduled';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'course_id', 'starts_at', 'ends_at', 'timezone', 'schedule_note', 'venue_name', 'address', 'city', 'state',
        'maps_url', 'lat', 'lng', 'venue_notes', 'capacity', 'sales_close_at', 'status', 'sort',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sales_close_at' => 'datetime',
        'capacity' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = (config('courses.db_prefix') ?? 'courses_') . 'course_sessions';
        parent::__construct($attributes);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'session_id');
    }

    /** Dates that can still be shown to the public: not cancelled and not over. */
    public function scopeUpcoming($query)
    {
        return $query->where('status', self::SCHEDULED)->where(fn ($q) => $q->where('ends_at', '>=', now())->orWhere(fn ($w) => $w->whereNull('ends_at')->where('starts_at', '>=', now())));
    }

    /** Seats already sold (active enrollments for this date). */
    public function seatsTaken(): int
    {
        // `taken` is filled by withCount() when a list of dates is loaded; otherwise ask the database.
        return $this->getAttribute('taken') !== null ? (int) $this->getAttribute('taken') : $this->enrollments()->where('status', Enrollment::STATUSES['active'])->count();
    }

    /** null = unlimited. */
    public function seatsLeft(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->seatsTaken());
    }

    public function isSoldOut(): bool
    {
        return $this->capacity !== null && $this->seatsLeft() === 0;
    }

    public function salesCloseAt(): Carbon
    {
        return ($this->sales_close_at ?? $this->starts_at)->copy();
    }

    public function salesClosed(): bool
    {
        return now()->greaterThanOrEqualTo($this->salesCloseAt());
    }

    public function isCancelled(): bool
    {
        return $this->status === self::CANCELLED;
    }

    /** Why a student cannot buy this date right now (null = they can). */
    public function unavailableReason(): ?string
    {
        return match (true) {
            $this->isCancelled() => 'cancelled',
            $this->salesClosed() => 'closed',
            $this->isSoldOut() => 'sold_out',
            default => null,
        };
    }

    public function isBookable(): bool
    {
        return $this->unavailableReason() === null;
    }

    public function startsLocal(): Carbon
    {
        return $this->starts_at->copy()->setTimezone($this->timezone ?: config('academy.timezone'));
    }

    public function endsLocal(): ?Carbon
    {
        return $this->ends_at?->copy()->setTimezone($this->timezone ?: config('academy.timezone'));
    }

    /** "sábado 12 de noviembre de 2026, 09:00–13:00" in the session's timezone. */
    public function whenLabel(): string
    {
        $start = $this->startsLocal()->locale('es');
        $end = $this->endsLocal()?->locale('es');
        $label = $start->translatedFormat('l j \d\e F \d\e Y') . ', ' . $start->format('H:i');
        if ($end) {
            $label .= $end->isSameDay($start) ? '–' . $end->format('H:i') : ' → ' . $end->translatedFormat('j \d\e F') . ', ' . $end->format('H:i');
        }

        return $label;
    }

    public function venueLabel(): string
    {
        return trim($this->venue_name . ' · ' . $this->city . ($this->state ? ', ' . $this->state : ''));
    }

    /** Venue, street and city, for e-mails and the student's panel. */
    public function fullAddress(): string
    {
        return implode(', ', array_filter([$this->venue_name, $this->address, $this->city . ($this->state ? ', ' . $this->state : '')]));
    }

    /** Link to open the venue on a map: the one the organiser gave, or a search for the address. */
    public function mapUrl(): string
    {
        return $this->maps_url ?: 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($this->venue_name . ', ' . $this->address . ', ' . $this->city);
    }

    /** iCalendar (.ics) body so the student can add the date to their calendar (the time carries its own zone: UTC). */
    public function ics(?Course $course = null): string
    {
        $course ??= $this->course;
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $esc = fn ($t) => addcslashes(str_replace(["\r", "\n"], ['', '\\n'], (string) $t), ',;\\');
        $end = $this->ends_at ?? $this->starts_at->copy()->addHours(2);
        $notes = trim(implode("\n", array_filter([$this->schedule_note, $this->venue_notes, 'Cómo llegar: ' . $this->mapUrl()])));

        return implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Horizontia//Eventos//ES', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT', 'UID:sesion-' . $this->id . '@' . (parse_url(config('app.url'), PHP_URL_HOST) ?: 'horizontia'),
            'DTSTAMP:' . $fmt(now()), 'DTSTART:' . $fmt($this->starts_at), 'DTEND:' . $fmt($end),
            'SUMMARY:' . $esc(html_entity_decode((string) $course?->title)),
            'LOCATION:' . $esc($this->venue_name . ', ' . $this->address . ', ' . $this->city),
            'DESCRIPTION:' . $esc($notes), 'URL:' . ($course ? route('courses.course-detail', ['slug' => $course->slug]) : ''),
            'BEGIN:VALARM', 'TRIGGER:-PT24H', 'ACTION:DISPLAY', 'DESCRIPTION:Mañana: ' . $esc(html_entity_decode((string) $course?->title)), 'END:VALARM',
            'END:VEVENT', 'END:VCALENDAR',
        ]) . "\r\n";
    }
}
