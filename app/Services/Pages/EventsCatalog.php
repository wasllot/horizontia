<?php

namespace App\Services\Pages;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;

/**
 * Upcoming in-person / hybrid courses as "event" view-models. One query shared by the /eventos page and the
 * page-builder blocks, so both always agree on what is listed and in which order.
 */
class EventsCatalog
{
    /** Every active in-person/hybrid course that still has an upcoming, not cancelled date. */
    public function courses(string $term = ''): Collection
    {
        return Course::query()
            ->where('status', Course::STATUSES['active'])
            ->whereIn('delivery', ['in_person', 'hybrid'])
            ->with([
                'thumbnail', 'pricing', 'category:id,name', 'instructor.profile:id,user_id,first_name,last_name',
                'sessions' => fn ($q) => $q->upcoming()->withCount(['enrollments as taken' => fn ($e) => $e->where('status', Enrollment::STATUSES['active'])]),
            ])
            ->whereHas('sessions', fn ($q) => $q->upcoming())
            ->when($term !== '', fn ($q) => $q->where('title', 'like', '%' . $term . '%'))
            ->get();
    }

    /**
     * @return Collection<int, object{course:Course, dates:Collection, primary:CourseSession, others:int, soldOut:bool, cities:Collection}>
     */
    public function events(Collection $courses, string $city = '', string $month = ''): Collection
    {
        return $courses->map(function (Course $course) use ($city, $month) {
            $dates = $course->sessions
                ->when($city !== '', fn (Collection $c) => $c->where('city', $city))
                ->when($month !== '', fn (Collection $c) => $c->filter(fn ($s) => $s->startsLocal()->format('Y-m') === $month))
                ->sortBy('starts_at')->values();
            if ($dates->isEmpty()) {
                return null;
            }
            // The date shown on the card: the first one that can still be bought, or the first one if all are closed/full.
            $primary = $dates->first(fn (CourseSession $s) => $s->isBookable()) ?? $dates->first();

            return (object) [
                'course' => $course, 'dates' => $dates, 'primary' => $primary,
                'others' => $dates->count() - 1,
                'soldOut' => !$dates->contains(fn (CourseSession $s) => $s->isBookable()),
                'cities' => $dates->pluck('city')->unique()->values(),
            ];
        })->filter()->sortBy(fn ($e) => $e->primary->starts_at)->values();
    }

    /** The next `$limit` events (optionally in one city), for blocks embedded in any page. */
    public function next(int $limit = 3, string $city = ''): Collection
    {
        return $this->events($this->courses(), $city)->take(max(1, min(12, $limit)))->values();
    }

    /** schema.org Event items, one per date, for search engines (event rich results). */
    public function eventSchema(Course $course, iterable $sessions): array
    {
        $items = [];
        foreach ($sessions as $s) {
            $url = route('courses.course-detail', ['slug' => $course->slug]) . '?fecha=' . $s->id;
            $items[] = array_filter([
                '@type' => 'Event',
                'name' => html_entity_decode($course->title),
                'startDate' => $s->starts_at->toIso8601String(),
                'endDate' => $s->ends_at?->toIso8601String(),
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'location' => ['@type' => 'Place', 'name' => $s->venue_name, 'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s->address, 'addressLocality' => $s->city, 'addressRegion' => $s->state]],
                'url' => $url,
                'offers' => ['@type' => 'Offer', 'price' => (string) ($course->pricing?->final_price ?? 0), 'priceCurrency' => (string) (setting('_general.currency') ?: 'MXN'),
                    'availability' => $s->isBookable() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut', 'url' => $url],
            ]);
        }

        return $items;
    }

    public function monthLabel(string $yearMonth): string
    {
        return Carbon::createFromFormat('Y-m-d', $yearMonth . '-01')->locale('es')->translatedFormat('F Y');
    }
}
