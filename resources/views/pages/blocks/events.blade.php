@if ($events->isNotEmpty())
<section class="ht-block ev ev-block">
    <div class="container">
        @if (!empty($heading))<header class="ht-block__head"><h2>{{ $heading }}</h2></header>@endif
        <div class="ev-grid">
            @foreach ($events as $e)
                @include('frontend.partials.event-card', ['e' => $e])
            @endforeach
        </div>
        <p class="ev-block__more"><a class="ev-btn" href="{{ route('events') }}">Ver todos los eventos</a></p>
    </div>
</section>
@endif
