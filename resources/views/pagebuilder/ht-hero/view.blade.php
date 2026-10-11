<section class="ht-block ht-hero ht-hero--{{ pagesetting('style') ?: 'navy' }}">
    <div class="container">
        @if (!empty(pagesetting('pre_heading')))<span class="ht-hero__eyebrow">{{ pagesetting('pre_heading') }}</span>@endif
        <h1>{!! pagesetting('heading') !!}</h1>
        @if (!empty(pagesetting('text')))<p>{!! pagesetting('text') !!}</p>@endif
        @if (!empty(pagesetting('button_text')) && !empty(pagesetting('button_url')))
            <a class="am-btn" href="{{ str_starts_with(pagesetting('button_url'), 'http') ? pagesetting('button_url') : url(pagesetting('button_url')) }}">{{ pagesetting('button_text') }}</a>
        @endif
    </div>
</section>
