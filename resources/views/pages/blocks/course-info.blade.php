<section class="ht-block ht-course-info">
    <div class="container">
        <div class="ht-course-info__card">
            <div>
                <h2>{{ html_entity_decode($course->title) }}</h2>
                @if ($course->subtitle)<p>{{ $course->subtitle }}</p>@endif
                <ul class="ht-course-info__facts">
                    <li><strong>Nivel</strong> {{ $course->level === 'all' ? 'Todos los niveles' : __('courses::courses.' . $course->level) }}</li>
                    @if (!$course->isOnsite() || $course->curriculums()->count() > 0)<li><strong>Lecciones</strong> {{ $course->curriculums()->count() }}</li>@endif
                    @if ($course->isOnsite())<li><strong>Modalidad</strong> {{ $course->deliveryLabel() }}</li>@endif
                    @if ($course->content_length)<li><strong>Duración</strong> {{ getCourseDuration($course->content_length) }}</li>@endif
                </ul>
                @php
                    $dates = $course->isOnsite() ? $course->sessions()->upcoming()->get() : collect();
                    $nextDate = $dates->first(fn ($d) => $d->isBookable());
                @endphp
                @if ($dates->isNotEmpty())
                    <ul class="ht-course-info__dates" aria-label="Próximas fechas">
                        @foreach ($dates->take(3) as $d)
                            <li><strong>{{ ucfirst($d->startsLocal()->locale('es')->translatedFormat('D j M')) }}</strong> {{ $d->city }}@unless ($d->isBookable()) <em>· {{ $d->unavailableReason() === 'sold_out' ? 'agotada' : 'ventas cerradas' }}</em>@endunless</li>
                        @endforeach
                        @if ($dates->count() > 3)<li>+ {{ $dates->count() - 3 }} más</li>@endif
                    </ul>
                @endif
            </div>
            <div class="ht-course-info__buy">
                <strong>{{ isPaidSystem() && (float) ($course->pricing?->final_price ?? 0) > 0 ? formatAmount($course->pricing->final_price) : 'Gratis' }}</strong>
                <a class="am-btn" href="{{ route('courses.course-detail', $course->slug) }}{{ $nextDate ? '?fecha=' . $nextDate->id . '#dates' : '' }}">{{ $course->isOnsite() ? 'Elegir fecha y comprar' : 'Ver curso y comprar' }}</a>
            </div>
        </div>
    </div>
</section>
