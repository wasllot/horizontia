@props(['title', 'subtitle' => '', 'image' => null, 'overlay' => 55, 'align' => 'left', 'crumbs' => false, 'crumb' => null])
@php $overlay = max(0, min(85, (int) $overlay)); @endphp
<link rel="stylesheet" href="{{ asset('css/page-banner.css') }}?v={{ @filemtime(public_path('css/page-banner.css')) }}">
<header {{ $attributes->class(['hz-banner', 'hz-banner--image' => (bool) $image, 'hz-banner--center' => $align === 'center']) }} data-testid="page-banner" @if ($image) style="--hz-banner-img: url('{{ e($image) }}'); --hz-banner-overlay: {{ $overlay / 100 }};" @endif>
    <div class="hz-banner__in">
        <h1>{{ $title }}</h1>
        @if (trim((string) $subtitle) !== '')<p>{{ $subtitle }}</p>@endif
        @if ($crumbs)
            <nav class="hz-banner__crumbs" aria-label="Ruta de navegación"><a href="{{ route('home') }}">Inicio</a> <span aria-hidden="true">/</span> <strong>{{ $crumb ?: $title }}</strong></nav>
        @endif
    </div>
</header>
