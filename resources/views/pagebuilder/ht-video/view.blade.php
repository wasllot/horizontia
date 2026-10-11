@php
    $url = trim((string) pagesetting('video_url'));
    $embed = null;
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        $embed = 'https://www.youtube-nocookie.com/embed/' . $m[1];
    } elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
        $embed = 'https://player.vimeo.com/video/' . $m[1];
    }
@endphp
@if ($embed)
<section class="ht-block ht-video">
    <div class="container">
        @if (!empty(pagesetting('heading')))<header class="ht-block__head"><h2>{!! pagesetting('heading') !!}</h2></header>@endif
        <div class="ht-video__frame"><iframe src="{{ $embed }}" title="{{ pagesetting('heading') ?: 'Video' }}" loading="lazy" allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>
        @if (!empty(pagesetting('caption')))<p class="ht-video__caption">{{ pagesetting('caption') }}</p>@endif
    </div>
</section>
@endif
