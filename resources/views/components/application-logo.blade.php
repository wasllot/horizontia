@props(['variation' => 'default'])
@php
    // Logos are bundled locally (the old code hot-linked horizontia.com on every page view).
    // 'white' = for dark backgrounds (sidebars, footer); anything else = the dark wordmark for light backgrounds.
    $logoPath = in_array($variation, ['white', 'light'], true) ? 'images/nuevo/Logo-Horizontia-footer.png' : 'images/nuevo/logo-horizontia.png';
    $logoImg = logoAsset($logoPath);
    $logoSize = @getimagesize(public_path($logoPath)) ?: null;   // original ratio, so the box is reserved before the image loads
@endphp
<a href="{{ url('/') }}">
    <img src="{{ $logoImg }}" alt="{{ setting('_general.site_name') ?? config('app.name', __('general.app_name')) }}" @if($logoSize) width="{{ $logoSize[0] }}" height="{{ $logoSize[1] }}" @endif decoding="async" {{ $attributes }}>
</a>
