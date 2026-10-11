@php /** @var \Illuminate\Support\Collection $recs */ @endphp
@if ($recs->isNotEmpty())
    <section class="rec" data-testid="recommendations" aria-labelledby="rec-title">
        <link rel="stylesheet" href="{{ asset('css/recs.css') }}?v={{ @filemtime(public_path('css/recs.css')) }}">
        <h2 id="rec-title">{{ $title }}</h2>
        <div class="rec__grid">
            @foreach ($recs as $c)
                <a class="rec__card" href="{{ route('courses.course-detail', ['slug' => $c->slug]) }}" data-testid="rec-card">
                    <img src="{{ !empty($c->thumbnail?->path) ? mediaThumb($c->thumbnail->path, 320) : asset('modules/courses/images/course.png') }}" alt="" loading="lazy">
                    <div class="rec__body">
                        <small>{{ $c->reason }}</small>
                        <strong>{{ html_entity_decode($c->title) }}</strong>
                        @if (isPaidSystem() && !empty($c->pricing?->final_price) && (float) $c->pricing->final_price > 0)<span>{!! formatAmount($c->pricing->final_price, true) !!}</span>@else<span>Gratis</span>@endif
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
