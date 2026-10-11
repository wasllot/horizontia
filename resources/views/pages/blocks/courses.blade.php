@if ($courses->isNotEmpty())
<section class="ht-block ht-courses">
    <div class="container">
        @if (!empty($heading))<header class="ht-block__head"><h2>{{ $heading }}</h2></header>@endif
        <div class="ht-courses__grid">
            @foreach ($courses as $course)
                <a class="ht-course-card" href="{{ route('courses.course-detail', $course->slug) }}">
                    @if (!empty($course->thumbnail?->path))<img loading="lazy" decoding="async" width="640" height="360" src="{{ mediaThumb($course->thumbnail->path, 640) }}" alt="">@endif
                    <div class="ht-course-card__body">
                        <h3>{{ html_entity_decode($course->title) }}</h3>
                        <span class="ht-course-card__price">{{ isPaidSystem() && (float) ($course->pricing?->final_price ?? 0) > 0 ? formatAmount($course->pricing->final_price) : 'Gratis' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
