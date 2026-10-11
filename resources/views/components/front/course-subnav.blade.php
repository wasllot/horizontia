@props(['course', 'links'])
@php
    $current = (string) request()->route('page');
    $published = collect($links)->filter(fn ($l) => $l->page?->status === 'published');
    $onsite = $course->isOnsite();
    $next = $onsite ? $course->sessions()->upcoming()->get()->first(fn ($d) => $d->isBookable()) : null;
@endphp
<nav class="ht-subnav" aria-label="Información de {{ html_entity_decode($course->title) }}">
    <div class="container">
        <a class="ht-subnav__back" href="{{ route('courses.course-detail', ['slug' => $course->slug]) }}">← Volver al curso</a>
        <strong class="ht-subnav__title">{{ html_entity_decode($course->title) }}</strong>
        <ul class="ht-subnav__list">
            <li><a href="{{ route('courses.course-detail', ['slug' => $course->slug]) }}">Resumen</a></li>
            @foreach ($published as $link)
                <li><a href="{{ route('courses.info-page', [$course->slug, $link->page->slug]) }}" @if($link->page->slug === $current) class="is-current" aria-current="page" @endif>{{ $link->label }}</a></li>
            @endforeach
        </ul>
        @if ($onsite)
            @if ($next)
                <span class="ht-subnav__next" data-testid="subnav-next">{{ ucfirst($next->startsLocal()->locale('es')->translatedFormat('D j M')) }} · {{ $next->city }}</span>
                <a class="ht-subnav__buy am-btn" href="{{ route('courses.course-detail', ['slug' => $course->slug]) }}?fecha={{ $next->id }}#dates">Elegir fecha y comprar</a>
            @else
                <span class="ht-subnav__next">Sin fechas disponibles</span>
            @endif
        @else
            <a class="ht-subnav__buy am-btn" href="{{ route('courses.course-detail', ['slug' => $course->slug]) }}">Ver curso y comprar</a>
        @endif
    </div>
</nav>
