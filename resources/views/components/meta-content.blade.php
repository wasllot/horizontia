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
    $routeName = request()->routeIs('find-tutors') || request()->path() == 'search-courses' || request()->path() == 'blogs' ? request()->route()->getName() : null;
    $customSeoData = $routeName ? collect($seoSettings['seo_settings'] ?? [])->firstWhere('page_id', $routeName) : null;

    $seoTitle = $page?->title ?? ($seoData['seo_title'] ?? null) ?? ($customSeoData['seo_title'] ?? null) ?? $pageTitle ?? null;
    $seoDescription = $pageDescription ?? ($seoData['seo_description'] ?? null) ?? ($customSeoData['seo_description'] ?? null);
    $seoKeywords = $pageKeywords ?? ($seoData['seo_keywords'] ?? null) ?? ($customSeoData['seo_keywords'] ?? null);

    if (empty($seoTitle)) {
        $seoTitle = $siteTitle;
    }
    
    if (!Str::contains($seoTitle, $siteTitle)) {
        $fullTitle = $siteTitle . ' | ' . $seoTitle;
    } else {
        $fullTitle = $seoTitle;
    }

    $metaImageUrl = $metaImage 
        ? (Str::startsWith($metaImage, 'http') ? $metaImage : asset($metaImage))
        : null;
    $canonical = $canonicalUrl ?? request()->fullUrl();
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
<meta name="theme-color" content="#4F46E5">
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

@if($type === 'website' && $canonical === $siteUrl . '/')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "{{ $siteTitle }}",
    "url": "{{ $siteUrl }}",
    "description": "{{ $seoDescription ? Str::limit(strip_tags($seoDescription), 160) : 'Plataforma de tutorías y cursos online' }}",
    "potentialAction": {
        "@type": "SearchAction",
        "target": {
            "@type": "EntryPoint",
            "urlTemplate": "{{ $siteUrl }}/find-tutors?search={search_term_string}"
        },
        "query-input": "required name=search_term_string"
    }
}
</script>
@endif
