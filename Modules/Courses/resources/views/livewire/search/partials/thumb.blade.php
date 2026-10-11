@if (!empty($course->thumbnail?->path))
    <img src="{{ mediaThumb($course->thumbnail->path, 640) }}" width="640" height="360" alt="" decoding="async" @if (!empty($eager)) fetchpriority="high" @else loading="lazy" @endif>
@else
    <span class="ht-thumb-ph" aria-hidden="true">{{ mb_strtoupper(mb_substr(html_entity_decode($course->title), 0, 1)) }}</span>
@endif
