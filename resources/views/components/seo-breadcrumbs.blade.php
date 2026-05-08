@props([
    'items' => [],
    'schema' => false
])

@php
    $siteTitle = setting('_general.site_name') ?? 'Horizontia';
    $siteUrl = config('app.url');
@endphp

@if(count($items) > 0)
<nav aria-label="Breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
        @foreach($items as $index => $item)
            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}" 
                itemprop="itemListElement" 
                itemscope 
                itemtype="https://schema.org/ListItem">
                @if(!$loop->last && !empty($item['url']))
                    <a href="{{ url($item['url']) }}" itemprop="item">
                        <span itemprop="name">{{ $item['label'] }}</span>
                    </a>
                @else
                    <span itemprop="name">{{ $item['label'] }}</span>
                @endif
                <meta itemprop="position" content="{{ $index + 1 }}">
            </li>
        @endforeach
    </ol>
</nav>
@endif

@if($schema && count($items) > 0)
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        @foreach($items as $index => $item)
        {
            "@type": "ListItem",
            "position": {{ $index + 1 }},
            "name": "{{ $item['label'] }}",
            @if(!$loop->last && !empty($item['url']))
            "item": "{{ url($item['url']) }}"
            @endif
        }{{ !$loop->last ? ',' : '' }}
        @endforeach
    ]
}
</script>
@endif
