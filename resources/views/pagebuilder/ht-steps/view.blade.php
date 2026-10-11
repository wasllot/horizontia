@php($items = collect(pagesetting('items'))->filter(fn ($i) => !empty($i['title']))->values())
@if ($items->isNotEmpty())
<section class="ht-block ht-steps">
    <div class="container">
        @if (!empty(pagesetting('heading')))<header class="ht-block__head"><h2>{!! pagesetting('heading') !!}</h2></header>@endif
        <ol class="ht-steps__list">
            @foreach ($items as $i => $item)
                <li><span class="ht-steps__n">{{ $i + 1 }}</span><div><h3>{{ $item['title'] }}</h3>@if (!empty($item['text']))<p>{{ $item['text'] }}</p>@endif</div></li>
            @endforeach
        </ol>
    </div>
</section>
@endif
