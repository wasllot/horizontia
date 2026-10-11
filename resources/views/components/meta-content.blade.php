@props([
    'pageTitle' => null, 
    'page' => null, 
    'pageDescription' => null, 
    'pageKeywords' => null, 
    'metaImage' => null,
    'canonicalUrl' => null,
    'robots' => 'index, follow',
    'schema' => null,
    'type' => 'website'
])

@php
    $siteTitle = setting('_general.site_name');
    if (empty($siteTitle)) {
        $siteTitle = 'Horizontia';
    }
    $siteUrl = config('app.url');
    $seoSettings = setting('_front_page_settings');
    $pageId = $page->id ?? null;
    $seoData = $pageId ? collect($seoSettings['seo_settings'] ?? [])->firstWhere('page_id', $pageId) : null;
    $routeName = request()->routeIs('find-tutors', 'courses.search-courses', 'blogs') ? request()->route()->getName() : null;
    $customSeoData = $routeName ? collect($seoSettings['seo_settings'] ?? [])->firstWhere('page_id', $routeName) : null;

    $seoTitle = $page?->title ?? ($seoData['seo_title'] ?? null) ?? ($customSeoData['seo_title'] ?? null) ?? $pageTitle ?? null;
    $seoDescription = $pageDescription ?? ($seoData['seo_description'] ?? null) ?? ($customSeoData['seo_description'] ?? null);
    $seoKeywords = $pageKeywords ?? ($seoData['seo_keywords'] ?? null) ?? ($customSeoData['seo_keywords'] ?? null);

    // Admin > SEO overrides (exact path > route name > wildcard) and global defaults win over what the page computed.
    $seoOverride = app(\App\Services\SeoOverrideService::class)->current();
    $seoGlobal   = setting('_seo') ?: [];
    if ($seoOverride) {
        $seoTitle       = $seoOverride['title']       ?: $seoTitle;
        $seoDescription = $seoOverride['description'] ?: $seoDescription;
        $seoKeywords    = $seoOverride['keywords']    ?: $seoKeywords;
        $robots         = $seoOverride['robots']      ?: $robots;
        $canonicalUrl   = $seoOverride['canonical']   ?: $canonicalUrl;
        $metaImage      = $seoOverride['og_image']    ?: $metaImage;
        $schema         = !empty($seoOverride['schema']) && is_array(json_decode($seoOverride['schema'], true)) ? json_decode($seoOverride['schema'], true) : $schema;
    }
    $seoDescription = $seoDescription ?: ($seoGlobal['default_description'] ?? null);
    $metaImage      = $metaImage ?: ($seoGlobal['default_image'] ?? asset('images/nuevo/Slider-horizontia.jpg'));

    // Private areas (dashboards, lessons, checkout, login) must never be indexed.
    if (request()->is('admin/*', 'estudiante/*', 'tutor/*', 'aula/*', 'mis-cursos', 'finalizar-compra*', 'gracias/*', 'iniciar-sesion', 'registro', 'olvide-mi-contrasena', 'restablecer-contrasena/*', 'pagar/*', 'messenger')) {
        $robots = 'noindex, nofollow';
    }

    if (empty($seoTitle)) {
        $seoTitle = $siteTitle;
    }
    
    // A title typed by the admin in Admin > SEO is used verbatim: no "Site | " prefix.
    if (!empty($seoOverride['title'])) {
        $fullTitle = $seoOverride['title'];
    } elseif (!Str::contains($seoTitle, $siteTitle)) {
        $fullTitle = $siteTitle . ' | ' . $seoTitle;
    } else {
        $fullTitle = $seoTitle;
    }

    $metaImageUrl = $metaImage 
        ? (Str::startsWith($metaImage, 'http') ? $metaImage : asset($metaImage))
        : null;
    $canonical = $canonicalUrl ?: request()->url();
@endphp

@if(!empty($og_tags))
    @foreach($og_tags as $key => $value)
        @if(str_starts_with($key, 'twitter:'))
            <meta name="{{ $key }}" content="{{ $value }}">
        @else
            <meta property="{{ $key }}" content="{{ $value }}">
        @endif
    @endforeach
@endif

<title>{{ $fullTitle }}</title>

<meta name="description" content="{{ $seoDescription ? Str::limit(strip_tags($seoDescription), 160) : '' }}">
<meta name="robots" content="{{ $robots }}">
<meta name="author" content="{{ $siteTitle }}">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#14213d">
<link rel="canonical" href="{{ $canonical }}">

@if($seoKeywords)
<meta name="keywords" content="{{ $seoKeywords }}">
@endif

<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $seoDescription ? Str::limit(strip_tags($seoDescription), 160) : '' }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:site_name" content="{{ $siteTitle }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
@if($metaImageUrl)
<meta property="og:image" content="{{ $metaImageUrl }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $seoTitle ?? $siteTitle }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $seoDescription ? Str::limit(strip_tags($seoDescription), 160) : '' }}">
@if($metaImageUrl)
<meta name="twitter:image" content="{{ $metaImageUrl }}">
<meta name="twitter:image:alt" content="{{ $seoTitle ?? $siteTitle }}">
@endif
<meta name="twitter:site" content="@horizontia">
<meta name="twitter:creator" content="@horizontia">

@if($schema)
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif

@if($type === 'website' && request()->is('/'))
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "{{ $siteTitle }}",
    "url": "{{ $siteUrl }}",
    "description": "{{ $seoDescription ? Str::limit(strip_tags($seoDescription), 160) : 'Academia en línea con cursos prácticos' }}",
    "potentialAction": {
        "@type": "SearchAction",
        "target": {
            "@type": "EntryPoint",
            "urlTemplate": "{{ route('courses.search-courses') }}?keyword={search_term_string}"
        },
        "query-input": "required name=search_term_string"
    }
}
</script>
@endif
