<section class="ht-block ht-cta-wrap">
    <div class="container">
        <div class="ht-cta ht-cta--{{ pagesetting('style') ?: 'gold' }}">
            <div>
                <h2>{!! pagesetting('heading') !!}</h2>
                @if (!empty(pagesetting('text')))<p>{!! pagesetting('text') !!}</p>@endif
            </div>
            @if (!empty(pagesetting('button_text')) && !empty(pagesetting('button_url')))
                <a class="am-btn" href="{{ str_starts_with(pagesetting('button_url'), 'http') ? pagesetting('button_url') : url(pagesetting('button_url')) }}">{{ pagesetting('button_text') }}</a>
            @endif
        </div>
    </div>
</section>
