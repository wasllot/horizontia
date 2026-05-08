@props([
    'title' => null,
    'description' => null,
    'keywords' => null,
    'image' => null,
    'url' => null,
    'type' => 'website',
    'robots' => 'index, follow',
    'schema' => null,
    'author' => null,
    'noIndex' => false
])

@php
    $siteTitle = setting('_general.site_name') ?? 'Horizontia';
    $siteUrl = config('app.url');
    $locale = str_replace('_', '-', app()->getLocale());
    
    $pageTitle = $title;
    if ($pageTitle && !Str::contains($pageTitle, $siteTitle)) {
        $fullTitle = $pageTitle . ' | ' . $siteTitle;
    } elseif ($pageTitle) {
        $fullTitle = $pageTitle;
    } else {
        $fullTitle = $siteTitle;
    }

    $metaDescription = $description ? Str::limit(strip_tags($description), 160) : null;
    $metaImage = $image 
        ? (Str::startsWith($image, 'http') ? $image : asset($image))
        : null;
    $canonical = $url ?? request()->fullUrl();
    $robotsContent = $noIndex ? 'noindex, nofollow' : $robots;
@endphp

<title>{{ $fullTitle }}</title>

<meta name="description" content="{{ $metaDescription }}">
<meta name="robots" content="{{ $robotsContent }}">
@if($author)
<meta name="author" content="{{ $author }}">
@endif
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#4F46E5">
<link rel="canonical" href="{{ $canonical }}">

@if($keywords)
<meta name="keywords" content="{{ $keywords }}">
@endif

<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:site_name" content="{{ $siteTitle }}">
<meta property="og:locale" content="{{ $locale }}">
@if($metaImage)
<meta property="og:image" content="{{ $metaImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $pageTitle ?? $siteTitle }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
@if($metaImage)
<meta name="twitter:image" content="{{ $metaImage }}">
<meta name="twitter:image:alt" content="{{ $pageTitle ?? $siteTitle }}">
@endif

@if($schema)
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif
