@props(['name', 'email' => null, 'image' => null, 'url' => null])
@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $hasImage = !empty($image) && mediaExists($image);
@endphp
<div class="hz-usercell">
    <span class="hz-usercell__avatar" aria-hidden="true">
        @if ($hasImage)
            <img src="{{ resizedImage($image, 72, 72) }}" alt="" loading="lazy" width="36" height="36">
        @else
            {{ $initials ?: '·' }}
        @endif
    </span>
    <span class="hz-usercell__text">
        <strong>{{ $name }}@if ($url)<a href="{{ $url }}" class="hz-usercell__link" title="{{ __('general.visit_profile') }}" aria-label="{{ __('general.visit_profile') }}"><i class="icon-external-link" aria-hidden="true"></i></a>@endif</strong>
        @if ($email)<small>{{ $email }}</small>@endif
    </span>
</div>
