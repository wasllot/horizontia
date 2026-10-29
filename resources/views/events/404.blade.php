@php
    $event = $event ?? new \App\Models\Event(['name' => 'Astrofest®', 'slug' => 'astrofest-geminidas-2026']);
    $state = 'soon';
    $noindex = true;
@endphp
@extends('events.layout', ['metaTitle' => 'Página no encontrada · Astrofest®', 'metaDescription' => 'Esta página se perdió en el espacio.', 'canonical' => $event->url()])

@section('body')
<div class="ev-page"><div class="ev-narrow" style="text-align:center;display:grid;gap:14px;justify-items:center">
    @if ($event->asset('galileo'))<img class="ev-char ev-char--card" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" width="190" height="190">@endif
    <h1>Esta página se perdió en el espacio</h1>
    <p class="ev-muted">Error 404. La dirección que buscas no existe o ya no está disponible.</p>
    <a class="ev-btn" href="{{ $event->url() }}">Volver al evento</a>
</div></div>
@endsection
