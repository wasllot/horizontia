@php($course = view()->shared('htCourse'))
@if ($course)
<section class="ht-block ht-course-info">
    <div class="container">
        <div class="ht-course-info__card">
            <div>
                <h2>{{ pagesetting('heading') ?: $course->title }}</h2>
                @if ($course->subtitle)<p>{{ $course->subtitle }}</p>@endif
                <ul class="ht-course-info__facts">
                    <li><strong>Nivel</strong> {{ ucfirst($course->level) }}</li>
                    <li><strong>Lecciones</strong> {{ $course->curriculums()->count() }}</li>
                    <li><strong>Duración</strong> {{ getCourseDuration($course->content_length) }}</li>
                </ul>
            </div>
            @if (pagesetting('show_price') !== '0')
                <div class="ht-course-info__buy">
                    <strong>{{ isPaidSystem() && !empty($course->pricing?->final_price) ? formatAmount($course->pricing->final_price) : 'Gratis' }}</strong>
                    <a class="am-btn" href="{{ route('courses.course-detail', $course->slug) }}">Ver curso y comprar</a>
                </div>
            @endif
        </div>
    </div>
</section>
@endif
