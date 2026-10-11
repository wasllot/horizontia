<?php

namespace App\Services;

/**
 * Placeholders usable inside builder pages ([[company]], [[refund_days]]…), resolved when the page is rendered
 * so legal texts follow config/academy.php instead of being frozen at seed time.
 */
class PageTokens
{
    public static function values(): array
    {
        return [
            '[[company]]' => (string) config('academy.legal_name'),
            '[[email]]' => (string) (config('academy.contact_email') ?: setting('_front_page_settings.footer_email')),
            '[[address]]' => (string) (config('academy.address') ?: setting('_front_page_settings.footer_address')),
            '[[refund_days]]' => (string) config('academy.refund_days'),
            '[[jurisdiction]]' => (string) config('academy.jurisdiction'),
            '[[updated]]' => config('academy.legal_updated_at')
                ? \Carbon\Carbon::parse(config('academy.legal_updated_at'))->locale('es')->translatedFormat('j \d\e F \d\e Y')
                : '',
        ];
    }

    /**
     * Tokens that only make sense inside the pages of an in-person course; elsewhere they print nothing
     * (a visitor never sees a raw `[[curso_…]]`).
     */
    public static function courseValues(?\Modules\Courses\Models\Course $course = null): array
    {
        $empty = ['[[curso_proxima_fecha]]' => '', '[[curso_ciudades]]' => '', '[[curso_sedes]]' => '', '[[curso_lugares]]' => ''];
        if (!$course || !$course->isOnsite()) {
            return $empty;
        }
        $dates = $course->sessions()->upcoming()->withCount(['enrollments as taken' => fn ($q) => $q->where('status', \Modules\Courses\Models\Enrollment::STATUSES['active'])])->get();
        if ($dates->isEmpty()) {
            return $empty;
        }
        $next = $dates->first(fn ($d) => $d->isBookable()) ?? $dates->first();
        $left = $next->seatsLeft();

        return [
            '[[curso_proxima_fecha]]' => ucfirst($next->whenLabel()),
            '[[curso_ciudades]]' => $dates->pluck('city')->unique()->implode(', '),
            '[[curso_sedes]]' => $dates->pluck('venue_name')->unique()->implode(', '),
            '[[curso_lugares]]' => !$next->isBookable() ? 'Agotado' : ($left === null ? 'Cupo disponible' : $left . ($left === 1 ? ' lugar' : ' lugares')),
        ];
    }

    public static function apply(string $html, ?\Modules\Courses\Models\Course $course = null): string
    {
        // The HTML parser percent-encodes brackets inside href attributes (mailto:%5B%5Bemail%5D%5D).
        $html = preg_replace('/%5B%5B([a-z_]+)%5D%5D/i', '[[$1]]', $html);

        if (!str_contains($html, '[[')) {
            return $html;
        }

        $v = self::values() + self::courseValues($course);

        // Optional fragments: no e-mail -> link to the contact form; no address -> drop the clause.
        if ($v['[[email]]'] === '') {
            $html = preg_replace('~<a href="mailto:\[\[email\]\]">\[\[email\]\]</a>~', 'nuestro <a href="' . e(route('contacto')) . '">formulario de contacto</a>', $html);
        }
        if ($v['[[address]]'] === '') {
            $html = str_replace(', con domicilio en [[address]],', '', $html);
        }

        return strtr($html, array_map('e', $v));
    }
}
