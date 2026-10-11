@php($items = collect(pagesetting('items'))->filter(fn ($i) => !empty($i['question'])))
@if ($items->isNotEmpty())
<section class="ht-block ht-faq">
    <div class="container">
        @if (!empty(pagesetting('heading')))<header class="ht-block__head"><h2>{!! pagesetting('heading') !!}</h2></header>@endif
        <div class="accordion ht-accordion" id="ht-faq-{{ md5(getSectionId()) }}">
            @foreach ($items->values() as $i => $item)
                @php($id = 'ht-faq-' . md5(getSectionId()) . '-' . $i)
                <div class="accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button {{ $i ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $id }}" aria-expanded="{{ $i ? 'false' : 'true' }}" aria-controls="{{ $id }}">{{ $item['question'] }}</button></h3>
                    <div id="{{ $id }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" data-bs-parent="#ht-faq-{{ md5(getSectionId()) }}"><div class="accordion-body">{!! strip_tags($item['answer'] ?? '', '<a><strong><em><br><ul><ol><li><code>') !!}</div></div>
                </div>
            @endforeach
        </div>
    </div>
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items->values()->map(fn ($i) => ['@type' => 'Question', 'name' => $i['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags(\App\Services\PageTokens::apply($i['answer'] ?? '')))]])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</section>
@endif
