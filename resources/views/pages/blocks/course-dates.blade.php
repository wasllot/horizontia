@if ($dates->isNotEmpty())
<section class="ht-block ev ht-dates">
    <div class="container">
        <header class="ht-block__head"><h2>{{ !empty($heading) ? $heading : 'Fechas disponibles' }}</h2></header>
        <ul class="ht-dates__list">
            @foreach ($dates as $s)
                @php
                    $start = $s->startsLocal();
                    $left = $s->seatsLeft();
                    $reason = $s->unavailableReason();
                    $url = route('courses.course-detail', ['slug' => $course->slug]) . '?fecha=' . $s->id;
                @endphp
                <li class="ht-date {{ $reason ? 'is-off' : '' }}">
                    <time class="ht-date__badge" datetime="{{ $s->starts_at->toIso8601String() }}"><strong>{{ $start->format('j') }}</strong><span>{{ mb_strtoupper($start->locale('es')->translatedFormat('M')) }}</span></time>
                    <div class="ht-date__main">
                        <strong>{{ ucfirst($s->whenLabel()) }}</strong>
                        <span>{{ $s->venue_name }} · {{ $s->city }}</span>
                        <small>{{ $s->address }}</small>
                    </div>
                    <div class="ht-date__seats">
                        @if ($reason === 'sold_out')<span class="ev-seats ev-seats--out">Agotada</span>
                        @elseif ($reason === 'closed')<span class="ev-seats ev-seats--out">Ventas cerradas</span>
                        @elseif ($left === null)<span>Cupo disponible</span>
                        @elseif ($left <= 5)<span class="ev-seats ev-seats--low">Quedan {{ $left }} {{ $left === 1 ? 'lugar' : 'lugares' }}</span>
                        @else<span>{{ $left }} lugares</span>@endif
                    </div>
                    @if ($reason)
                        <span class="ev-btn ev-btn--ghost is-disabled" aria-disabled="true">No disponible</span>
                    @else
                        <a class="ev-btn" href="{{ $url }}">Elegir esta fecha</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
