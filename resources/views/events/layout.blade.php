@php
    $seo = [
        'title' => $metaTitle ?? $event->text('seo.title'),
        'description' => $metaDescription ?? $event->text('seo.description'),
        'canonical' => $canonical ?? $event->url(),
        'image' => $event->asset('og'),
    ];
    $onLanding = $onLanding ?? false;
    $cartCount = $cartCount ?? 0;
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
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,opsz,wght@0,8..60,300;0,8..60,400;0,8..60,600;1,8..60,300;1,8..60,400&display=swap">
    <script>document.documentElement.classList.add('ev-js');</script>
    <link rel="stylesheet" href="{{ asset('css/event.css') }}?v={{ @filemtime(public_path('css/event.css')) }}">
    <script>
    window.dataLayer = window.dataLayer || [];
    function evPush(name, extra) { window.dataLayer.push(Object.assign({ event: name, event_slug: @json($event->slug) }, extra || {})); }
    </script>
    @stack('head')
@endpush

@section('content')
<div class="evp">
    @include('events.icons')
    <div class="ev-progress" id="evProgress" aria-hidden="true"></div>
    <header class="evp-sub {{ $onLanding ? 'evp-sub--float' : 'evp-sub--solid' }}" id="evHead">
        <div class="evp-wrap">
            <a class="evp-sub__name" href="{{ $event->url() }}" aria-label="Horizontia, inicio del evento {{ $event->name }}">
                <img src="{{ asset('images/eventos/horizontia-logo.webp') }}" alt="Horizontia" width="1223" height="785">
            </a>
            @if ($dates)<span class="evp-sub__date">{{ $dates }} · {{ $event->venue_city ?: 'Estado de México' }}</span>@endif
            <div class="evp-sub__r">
                <button type="button" class="evp-icon evp-icon--label" id="evRed" aria-pressed="false" aria-label="Modo luz roja" title="Modo luz roja: pinta la página en rojo para no deslumbrarte de noche">
                    <svg class="ev-i" aria-hidden="true"><use href="#i-bulb"/></svg><span>Luz roja</span>
                </button>
                @if ($state === 'open' || $cartCount > 0)
                    <a class="evp-icon" href="{{ $event->url('carrito') }}" aria-label="Carrito, {{ $cartCount }} {{ $cartCount === 1 ? 'entrada' : 'entradas' }}">
                        <svg class="ev-i" aria-hidden="true"><use href="#i-cart"/></svg>
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

    <footer class="evf" aria-label="Organizadores y contacto">
        <div class="evp-wrap">
            <div class="evf__grid">
                <div class="evf__org">
                    <p class="evf__label">Organiza</p>
                    <img class="evf__logo" src="{{ asset('images/nuevo/Logo-Horizontia-footer.png') }}" alt="Horizontia" width="165" height="52" loading="lazy">
                    @if ($event->asset('fama') || $event->asset('club'))
                        <p class="evf__label evf__label--sp">Con el respaldo de</p>
                        <div class="evf__chips">
                            @if ($event->asset('fama'))<span class="evf__chip"><img src="{{ $event->asset('fama') }}" alt="FAMA, Físicos Astrónomos Mexicanos y Asociados" loading="lazy"></span>@endif
                            @if ($event->asset('club'))<span class="evf__chip"><img src="{{ $event->asset('club') }}" alt="Club Campestre Teotihuacán" loading="lazy"></span>@endif
                        </div>
                    @endif
                </div>
                @if ($event->contact_email || $event->contact_whatsapp)
                    <div class="evf__contact">
                        <p class="evf__label">Contacto del evento</p>
                        @if ($event->contact_email)<a href="mailto:{{ $event->contact_email }}"><svg class="ev-i" aria-hidden="true"><use href="#i-mail"/></svg>{{ $event->contact_email }}</a>@endif
                        @if ($event->contact_whatsapp)<a href="https://wa.me/{{ preg_replace('/\D+/', '', $event->contact_whatsapp) }}" rel="noopener" target="_blank"><svg class="ev-i" aria-hidden="true"><use href="#i-whatsapp"/></svg>{{ $event->contact_whatsapp }}</a>@endif
                    </div>
                @endif
                <nav class="evf__legal" aria-label="Información legal">
                    <p class="evf__label">Información</p>
                    <a href="{{ $event->url('politicas') }}">Políticas del evento</a>
                    <a href="{{ route('legal.privacy') }}" rel="noopener">Aviso de privacidad</a>
                </nav>
            </div>
            @php
                $credits = collect(json_decode((string) @file_get_contents(public_path('images/eventos/stock/credits.json')), true) ?: [])->filter(fn ($c) => !empty($usedImages) && in_array($c['key'], $usedImages, true));
            @endphp
            <div class="evf__base">
                <span>© {{ now()->year }} Horizontia · {{ str_replace('®', '', $event->name) }}</span>
                @if ($credits->isNotEmpty())
                    <details class="evf__credits"><summary>Fotografías</summary>
                        <p>Imágenes ilustrativas de Wikimedia Commons con licencia libre.</p>
                        <ul>@foreach ($credits as $c)<li><a href="{{ $c['url'] }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::of($c['title'])->beforeLast('.')->limit(50) }}</a> · {{ $c['author'] ?: 'Autor/a no indicado/a' }} · {{ $c['license'] }}</li>@endforeach</ul>
                    </details>
                @endif
            </div>
        </div>
    </footer>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var b = document.body, red = document.getElementById('evRed');
    function setRed(on) { b.classList.toggle('ev-red', on); red.setAttribute('aria-pressed', on ? 'true' : 'false'); try { localStorage.setItem('ev_red', on ? '1' : '0'); } catch (e) {} }
    try { if (localStorage.getItem('ev_red') === '1') setRed(true); } catch (e) {}
    red.addEventListener('click', function () { setRed(!b.classList.contains('ev-red')); });
    var head = document.getElementById('evHead'), prog = document.getElementById('evProgress'), tick = false;
    var onScroll = function () { var y = window.scrollY || 0, h = document.documentElement; if (head) head.classList.toggle('is-stuck', y > 40); if (prog) prog.style.transform = 'scaleX(' + Math.min(1, y / Math.max(1, h.scrollHeight - h.clientHeight)) + ')'; tick = false; };
    window.addEventListener('scroll', function () { if (!tick) { tick = true; requestAnimationFrame(onScroll); } }, { passive: true }); onScroll();
    document.addEventListener('click', function (e) { var t = e.target.closest('[data-ev-track]'); if (t) evPush(t.getAttribute('data-ev-track')); });
})();
@if (session('event_pixel')) evPush(@json(session('event_pixel')), { value: {{ (float) ($event->price ?? 0) }}, currency: 'MXN' }); @endif
</script>
@endpush
