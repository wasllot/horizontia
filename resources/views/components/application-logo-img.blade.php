@props(['variation' => 'default'])
{{-- Solo la imagen del logo (sin enlace), para usarla dentro de un <a> propio. --}}
@php
    $logoImg = in_array($variation, ['white', 'light'], true) ? asset('images/nuevo/Logo-Horizontia-footer.png') : asset('images/nuevo/logo-horizontia.png');
@endphp
<img src="{{ $logoImg }}" alt="{{ setting('_general.site_name') ?? config('app.name') }}" {{ $attributes }}>
