@php
    $courses = \Modules\Courses\Models\Course::query()
        ->with(['thumbnail', 'pricing', 'instructor.profile:id,user_id,first_name,last_name'])
        ->where('status', \Modules\Courses\Models\Course::STATUSES['active'])
        ->when(pagesetting('category'), fn ($q, $c) => $q->where('category_id', $c))
        ->latest()->limit((int) (pagesetting('count') ?: 3))->get();
@endphp
@if ($courses->isNotEmpty())
<section class="ht-block ht-courses">
    <div class="container">
        @if (!empty(pagesetting('heading')))<header class="ht-block__head"><h2>{!! pagesetting('heading') !!}</h2></header>@endif
        <div class="ht-courses__grid">
            @foreach ($courses as $course)
                <a class="ht-course-card" href="{{ route('courses.course-detail', $course->slug) }}">
                    <img loading="lazy" decoding="async" width="640" height="360" src="{{ mediaThumb($course->thumbnail?->path, 640) }}" alt="">
                    <div class="ht-course-card__body">
                        <h3>{{ $course->title }}</h3>
                        <span class="ht-course-card__price">{{ isPaidSystem() && !empty($course->pricing?->final_price) ? formatAmount($course->pricing->final_price) : 'Gratis' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
        @if (!empty(pagesetting('button_text')))<p class="ht-courses__more"><a class="am-btn" href="{{ route('courses.search-courses') }}">{{ pagesetting('button_text') }}</a></p>@endif
    </div>
</section>
@endif
