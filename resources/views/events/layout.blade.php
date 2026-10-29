@php
    $seo = [
        'title' => $metaTitle ?? $event->text('seo.title'),
        'description' => $metaDescription ?? $event->text('seo.description'),
        'canonical' => $canonical ?? $event->url(),
        'image' => $event->asset('og'),
    ];
    $onLanding = $onLanding ?? false;
    $cartCount = $cartCount ?? 0;
    $logo = $event->asset('logo');
    $startLocal = $event->local($event->start_at);
    $dates = $startLocal ? $startLocal->format('j') . ($event->end_at ? ' y ' . $event->local($event->end_at)->format('j') : '') . ' de ' . $startLocal->locale('es')->translatedFormat('F') : '';
@endphp
{{-- Independiente: sin el menú ni el pie del sitio ('bare'), pero con su tipografía, colores y botones. --}}
@extends('layouts.frontend-app', ['bare' => true, 'ownMeta' => true])

@push('styles')
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ !empty($noindex) ? 'noindex, nofollow' : 'index, follow' }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    <meta name="theme-color" content="#14213d">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_MX">
    <meta property="og:site_name" content="Horizontia">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    @if ($seo['image'])
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta name="twitter:image" content="{{ $seo['image'] }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/event.css') }}?v={{ @filemtime(public_path('css/event.css')) }}">
    <script>
    window.dataLayer = window.dataLayer || [];
    function evPush(name, extra) { window.dataLayer.push(Object.assign({ event: name, event_slug: @json($event->slug) }, extra || {})); }
    </script>
    @stack('head')
@endpush

@section('content')
<div class="evp">
    <header class="evp-sub">
        <div class="evp-wrap">
            <a class="evp-sub__name" href="{{ $event->url() }}" aria-label="Astrofest, inicio del evento">
                <img src="{{ $logo }}" alt="Astrofest® {{ $event->name }}" width="200" height="52">
            </a>
            @if ($dates)<span class="evp-sub__date">{{ $dates }} · {{ $event->venue_city ?: 'Estado de México' }}</span>@endif
            <div class="evp-sub__r">
                <button type="button" class="evp-icon" id="evRed" aria-pressed="false" aria-label="Modo luz roja" title="Modo luz roja">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-4 10.5c.8.8 1 1.5 1 2.5h6c0-1 .2-1.7 1-2.5A6 6 0 0 0 12 3z"/></svg>
                </button>
                @if ($state === 'open' || $cartCount > 0)
                    <a class="evp-icon" href="{{ $event->url('carrito') }}" aria-label="Carrito, {{ $cartCount }} {{ $cartCount === 1 ? 'entrada' : 'entradas' }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.4 12.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.5L21.5 8H6"/></svg>
                        @if ($cartCount > 0)<b>{{ $cartCount }}</b>@endif
                    </a>
                @endif
                @if ($onLanding)
                    @if ($state === 'open')<a class="ev-btn ev-btn--sm" href="#entrada" data-ev-track="cta_header">{{ $event->text('hero.cta') }}</a>@endif
                @else
                    <a class="ev-btn ev-btn--ghost ev-btn--sm" href="{{ $event->url() }}">Volver al evento</a>
                @endif
            </div>
        </div>
    </header>

    @yield('body')

    <aside class="evp-orgs" aria-label="Organizadores y contacto">
        <div class="evp-wrap">
            <div class="evp-orgs__row">
                <span>Organiza</span>
                <img src="{{ asset('images/nuevo/logo-horizontia.png') }}" alt="Horizontia" height="34" loading="lazy" style="height:34px;width:auto">
                @if ($event->asset('fama'))<img src="{{ $event->asset('fama') }}" alt="FAMA, Físicos Astrónomos Mexicanos y Asociados" loading="lazy">@endif
                @if ($event->asset('club'))<img src="{{ $event->asset('club') }}" alt="Club Campestre Teotihuacán" loading="lazy">@endif
            </div>
            @if ($event->contact_email || $event->contact_whatsapp)
                <p>Contacto del evento:
                    @if ($event->contact_email)<a href="mailto:{{ $event->contact_email }}">{{ $event->contact_email }}</a>@endif
                    @if ($event->contact_email && $event->contact_whatsapp) · @endif
                    @if ($event->contact_whatsapp)<a href="https://wa.me/{{ preg_replace('/\D+/', '', $event->contact_whatsapp) }}" rel="noopener" target="_blank">WhatsApp {{ $event->contact_whatsapp }}</a>@endif
                </p>
            @endif
            <nav class="evp-orgs__links" aria-label="Información legal">
                <a href="{{ $event->url('politicas') }}">Políticas del evento</a>
                <a href="{{ route('legal.privacy') }}" rel="noopener">Aviso de privacidad</a>
            </nav>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var b = document.body, red = document.getElementById('evRed');
    function setRed(on) { b.classList.toggle('ev-red', on); red.setAttribute('aria-pressed', on ? 'true' : 'false'); try { localStorage.setItem('ev_red', on ? '1' : '0'); } catch (e) {} }
    try { if (localStorage.getItem('ev_red') === '1') setRed(true); } catch (e) {}
    red.addEventListener('click', function () { setRed(!b.classList.contains('ev-red')); });
    document.addEventListener('click', function (e) { var t = e.target.closest('[data-ev-track]'); if (t) evPush(t.getAttribute('data-ev-track')); });
})();
@if (session('event_pixel')) evPush(@json(session('event_pixel')), { value: {{ (float) ($event->price ?? 0) }}, currency: 'MXN' }); @endif
</script>
@endpush
