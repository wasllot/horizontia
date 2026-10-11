<?php

namespace Modules\Courses\Services\Lessons;

class GeniallyHandler extends LinkHandler
{
    public function type(): string { return 'genially_link'; }
    protected function field(): string { return 'genially_link'; }
    protected function invalidMessage(): string { return 'Ingresa un enlace público de Genially (https://view.genially.com/…).'; }

    /** Only Genially hosts may be embedded: an arbitrary URL in an iframe would let an instructor frame any site. */
    public function normalize(string $url): ?string
    {
        $url = trim($url);
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if (($parts['scheme'] ?? '') !== 'https' || $host === '') {
            return null;
        }
        $ok = $host === 'genial.ly' || str_ends_with($host, '.genial.ly') || $host === 'genially.com' || str_ends_with($host, '.genially.com');

        return $ok ? $url : null;
    }
}
