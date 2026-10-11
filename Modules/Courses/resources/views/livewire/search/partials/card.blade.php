@php
    $pricing = $course->pricing;
    $isPaid  = isPaidSystem() && !empty($pricing?->final_price) && (float) $pricing->final_price > 0;
    $hasDiscount = $isPaid && (float) $pricing->price > (float) $pricing->final_price;
    $rating = (float) $course->ratings_avg_rating;
    $url = route('courses.course-detail', $course->slug);
    $title = html_entity_decode($course->title);
@endphp
<article class="ht-ccard" wire:key="course-{{ $course->id }}">
    <a class="ht-ccard__media" href="{{ $url }}" tabindex="-1" aria-hidden="true">
        @include('courses::livewire.search.partials.thumb', ['course' => $course, 'eager' => $eager ?? false])
        @if ($course->delivery === 'hybrid')<span class="ht-ccard__mode">Presencial + en línea</span>@endif
        @if ($hasDiscount)<span class="ht-ccard__off">-{{ round((1 - $pricing->final_price / $pricing->price) * 100) }}%</span>@endif
    </a>
    <div class="ht-ccard__body">
        @if ($course->category)
            <button type="button" class="ht-ccard__cat" wire:click="toggleCategory({{ $course->category->id }})">{{ $course->category->name }}</button>
        @endif
        <h3><a href="{{ $url }}">{{ $title }}</a></h3>
        @if ($course->instructor?->profile?->short_name)
            <p class="ht-ccard__inst">{{ $course->instructor->profile->short_name }}</p>
        @endif
        <ul class="ht-ccard__meta">
            @if ($course->ratings_count > 0)
                <li class="ht-ccard__rate"><span aria-hidden="true">★</span> <strong>{{ number_format($rating, 1) }}</strong> <small>({{ $course->ratings_count }})</small><span class="sr-only visually-hidden"> de 5 estrellas</span></li>
            @else
                <li class="ht-ccard__new">Nuevo</li>
            @endif
            <li>{{ $course->level === 'all' ? 'Todos los niveles' : __('courses::courses.' . $course->level) }}</li>
            @if (!empty($course->content_length))<li>{{ getCourseDuration($course->content_length) }}</li>@endif
            @if ($course->curriculums_count)<li>{{ $course->curriculums_count }} {{ $course->curriculums_count == 1 ? 'lección' : 'lecciones' }}</li>@endif
            @if (!empty($multiLang) && $course->language?->name)<li>{{ languageLabel($course->language->name) }}</li>@endif
        </ul>
        <div class="ht-ccard__foot">
            <div class="ht-ccard__price">
                @if ($isPaid)
                    @if ($hasDiscount)<s>{{ formatAmount($pricing->price) }}</s>@endif
                    <strong>{!! formatAmount($pricing->final_price, true) !!}</strong>
                @else
                    <strong class="is-free">Gratis</strong>
                @endif
            </div>
            <a href="{{ $url }}" class="ht-ccard__cta" aria-label="Ver el curso {{ $title }}">Ver curso <span aria-hidden="true">→</span></a>
        </div>
    </div>
</article>
