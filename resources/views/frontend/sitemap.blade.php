<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as [$loc, $priority, $updated])
    <url>
        <loc>{{ $loc }}</loc>
@if ($updated)
        <lastmod>{{ $updated->toAtomString() }}</lastmod>
@endif
        <priority>{{ $priority }}</priority>
    </url>
@endforeach
</urlset>
