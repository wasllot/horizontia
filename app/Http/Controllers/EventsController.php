<?php

namespace App\Http\Controllers;

use App\Services\Pages\EventsCatalog;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Courses\Models\CourseSession;

/**
 * Public "Eventos" section: the in-person (and hybrid) courses that still have dates ahead, grouped by month.
 * Buying works exactly like any other course; the card only shows when and where it happens.
 */
class EventsController extends Controller
{
    public function index(Request $request, SeoService $seo, EventsCatalog $catalog)
    {
        $tz = (string) config('academy.timezone', 'America/Mexico_City');
        $city = trim((string) $request->query('ciudad', ''));
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('mes', '')) ? (string) $request->query('mes') : '';
        $term = trim(mb_substr((string) $request->query('q', ''), 0, 80));

        $courses = $catalog->courses($term);

        // Filter options come from every upcoming date, before the visitor's own filters are applied.
        $allSessions = $courses->flatMap->sessions;
        $cities = $allSessions->pluck('city')->unique()->sort()->values();
        $months = $allSessions->map(fn (CourseSession $s) => $s->startsLocal()->format('Y-m'))->unique()->sort()->values()
            ->mapWithKeys(fn ($m) => [$m => $catalog->monthLabel($m)]);

        $events = $catalog->events($courses, $city, $month);

        $byMonth = $events->groupBy(fn ($e) => $e->primary->startsLocal()->format('Y-m'))->map(fn ($group, $key) => (object) [
            'label' => $catalog->monthLabel($key),
            'events' => $group,
        ]);

        $seo->reset();
        $seo->setTitle('Eventos y cursos presenciales | ' . (setting('_general.site_name') ?: 'Horizontia'))
            ->setDescription('Talleres y cursos presenciales con fechas y sede. Elige tu fecha, paga en línea con Mercado Pago y recibe todos los datos por correo.')
            ->setCanonical(route('events'));

        return view('frontend.events', $seo->getViewData() + [
            'byMonth' => $byMonth, 'total' => $events->count(), 'cities' => $cities, 'months' => $months,
            'filters' => ['ciudad' => $city, 'mes' => $month, 'q' => $term], 'tz' => $tz,
            'eventsSchema' => $this->schema($events, $catalog),
        ]);
    }

    /** One schema.org Event per upcoming date, so search engines can show them as events. */
    private function schema(Collection $events, EventsCatalog $catalog): array
    {
        $items = [];
        foreach ($events as $e) {
            $items = array_merge($items, $catalog->eventSchema($e->course, $e->dates));
        }

        return ['@context' => 'https://schema.org', '@graph' => $items];
    }
}
