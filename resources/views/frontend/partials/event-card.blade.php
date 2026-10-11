{{-- Tarjeta de un evento (página /eventos y bloques del editor). Espera $e (ver App\\Services\\Pages\\EventsCatalog). --}}
    @php
        $course = $e->course;
        $s = $e->primary;
        $start = $s->startsLocal();
        $end = $s->endsLocal();
        $left = $s->seatsLeft();
        $price = (float) ($course->pricing?->final_price ?? 0);
        $thumb = !empty($course->thumbnail?->path) && mediaExists($course->thumbnail->path) ? mediaThumb($course->thumbnail->path, 640) : null;
        $url = route('courses.course-detail', ['slug' => $course->slug]) . '?fecha=' . $s->id;
    @endphp
    <article class="ev-card {{ $e->soldOut ? 'is-soldout' : '' }}">
        <a class="ev-card__link" href="{{ $url }}" aria-label="{{ html_entity_decode($course->title) }} — {{ $start->locale('es')->translatedFormat('j \d\e F') }}"></a>
        <div class="ev-card__media">
            @if ($thumb)<img src="{{ $thumb }}" alt="" width="640" height="360" loading="lazy" decoding="async">@else<span class="ev-card__ph" aria-hidden="true"></span>@endif
            <time class="ev-date" datetime="{{ $s->starts_at->toIso8601String() }}">
                <strong>{{ $start->format('j') }}</strong>
                <span>{{ mb_strtoupper($start->locale('es')->translatedFormat('M')) }}</span>
            </time>
            <span class="ev-mode">{{ $course->delivery === 'hybrid' ? 'Presencial + en línea' : 'Presencial' }}</span>
        </div>
        <div class="ev-card__body">
            @if ($course->category?->name)<p class="ev-card__cat">{{ $course->category->name }}</p>@endif
            <h3>{{ html_entity_decode($course->title) }}</h3>
            <ul class="ev-meta">
                <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg><span>{{ $s->venue_name }} · {{ $s->city }}</span></li>
                <li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span>{{ $start->locale('es')->translatedFormat('l') }} · {{ $start->format('H:i') }}@if($end && $end->isSameDay($start))–{{ $end->format('H:i') }}@endif</span></li>
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20"/><circle cx="10" cy="8" r="3.5"/><path d="M20 20v-1.4a3.5 3.5 0 0 0-2.5-3.35M15.5 4.7a3.5 3.5 0 0 1 0 6.6"/></svg>
                    @if ($e->soldOut)<span class="ev-seats ev-seats--out">Agotado</span>
                    @elseif ($left === null)<span>Cupo disponible</span>
                    @elseif ($left <= 5)<span class="ev-seats ev-seats--low">Quedan {{ $left }} {{ $left === 1 ? 'lugar' : 'lugares' }}</span>
                    @else<span>{{ $left }} lugares disponibles</span>@endif
                </li>
            </ul>
            @if ($e->others > 0)<p class="ev-more">+ {{ $e->others }} {{ $e->others === 1 ? 'fecha más' : 'fechas más' }}@if($e->cities->count() > 1) · {{ $e->cities->implode(', ') }}@endif</p>@endif
        </div>
        <footer class="ev-card__foot">
            <strong class="ev-price">{{ $price > 0 ? formatAmount($price) : 'Gratis' }}</strong>
            <span class="ev-cta">{{ $e->soldOut ? 'Ver detalles' : ($e->others > 0 ? 'Elegir fecha' : 'Ver y comprar') }} <span aria-hidden="true">→</span></span>
        </footer>
    </article>
