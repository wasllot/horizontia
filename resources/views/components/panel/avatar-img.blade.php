@php
    /** @var \App\Models\User $user */
    $px   = $px ?? 36;
    $name = $user->profile?->short_name ?: $user->email;
    $img  = $user->profile?->image;
@endphp
@if (!empty($img) && mediaExists($img))
    <img src="{{ resizedImage($img, $px, $px) }}" alt="{{ $name }}" width="{{ $px }}" height="{{ $px }}">
@else
    <span aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span><span class="hz-sr">{{ $name }}</span>
@endif
