@php
    $url = route('blog-details', $blog->slug);
    $cat = $blog->categories->first();
    $author = $blog->author?->profile?->short_name;
@endphp
<article class="ht-bcard {{ !empty($featured) ? 'ht-bcard--featured' : '' }}" wire:key="blog-{{ $blog->id }}">
    <a class="ht-bcard__media" href="{{ $url }}" tabindex="-1" aria-hidden="true">
        @if (!empty($blog->image) && mediaExists($blog->image))
            <img src="{{ mediaThumb($blog->image, !empty($featured) ? 960 : 640) }}" alt="" width="640" height="360" decoding="async" @if (!empty($eager)) fetchpriority="high" @else loading="lazy" @endif>
        @else
            <span class="ht-thumb-ph" aria-hidden="true">{{ mb_strtoupper(mb_substr($blog->title, 0, 1)) }}</span>
        @endif
    </a>
    <div class="ht-bcard__body">
        <div class="ht-bcard__meta">
            @if ($cat)<a href="{{ route('blogs', ['category' => $cat->slug]) }}" class="ht-bcard__cat">{{ $cat->name }}</a>@endif
            <span>{{ $blog->reading_minutes }} min de lectura</span>
        </div>
        <h3><a href="{{ $url }}">{{ $blog->title }}</a></h3>
        <p>{{ $blog->excerpt(!empty($featured) ? 45 : 24) }}</p>
        <div class="ht-bcard__foot">
            @if ($author)<span>{{ $author }}</span><span aria-hidden="true">·</span>@endif
            <time datetime="{{ $blog->updated_at->toDateString() }}">{{ $blog->updated_at->locale('es')->translatedFormat('j M Y') }}</time>
        </div>
    </div>
</article>
