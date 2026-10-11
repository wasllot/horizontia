@php($width = pagesetting('width') ?: 'narrow')
<section class="ht-block ht-rich ht-rich--{{ $width }}">
    <div class="container">
        @if (!empty(pagesetting('heading')) || !empty(pagesetting('subheading')))
            <header class="ht-block__head">
                @if (!empty(pagesetting('heading')))<h2>{!! pagesetting('heading') !!}</h2>@endif
                @if (!empty(pagesetting('subheading')))<p>{!! pagesetting('subheading') !!}</p>@endif
            </header>
        @endif
        @if (!empty(pagesetting('content')))
            <div class="ht-prose">{!! pagesetting('content') !!}</div>
        @endif
    </div>
</section>
