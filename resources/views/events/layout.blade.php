@php
    $seo = [
        'title' => $metaTitle ?? $event->text('seo.title'),
        'description' => $metaDescription ?? $event->text('seo.description'),
        'canonical' => $canonical ?? $event->url(),
        'image' => $event->asset('og'),
    ];
    $onLanding = $onLanding ?? false;
    $logo = $event->asset('logo') ?: asset('images/eventos/astrofest-wordmark.svg');
@endphp
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <x-gtm-head />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ !empty($noindex) ? 'noindex, nofollow' : 'index, follow' }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    <meta name="theme-color" content="#070B1F">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_MX">
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
    <link rel="icon" href="{{ asset('images/eventos/astrofest-wordmark.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/event.css') }}?v={{ @filemtime(public_path('css/event.css')) }}">
    @stack('head')
</head>
<body class="ev">
    <x-gtm-body />
    <a class="ev-skip" href="#contenido">Saltar al contenido</a>

    <header class="ev-head">
        <div class="ev-wrap">
            <a class="ev-logo" href="{{ $event->url() }}" aria-label="Astrofest, inicio del evento"><img src="{{ $logo }}" alt="Astrofest® {{ $event->name }}" width="200" height="60"></a>
            <div class="ev-head__r">
                <button type="button" class="ev-red-toggle" id="evRed" aria-pressed="false" aria-label="Modo luz roja" title="Modo luz roja">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-4 10.5c.8.8 1 1.5 1 2.5h6c0-1 .2-1.7 1-2.5A6 6 0 0 0 12 3z"/></svg>
                </button>
                @if ($state === 'open' || ($cartCount ?? 0) > 0)
                    <a class="ev-cart" href="{{ $event->url('carrito') }}" aria-label="Carrito, {{ $cartCount ?? 0 }} {{ ($cartCount ?? 0) === 1 ? 'entrada' : 'entradas' }}">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.4 12.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.5L21.5 8H6"/></svg>
                        @if (($cartCount ?? 0) > 0)<b>{{ $cartCount }}</b>@endif
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

    <main id="contenido">
        @yield('content')
    </main>

    <footer class="ev-foot">
        <div class="ev-wrap">
            <div class="ev-foot__orgs">
                <span>Organiza</span>
                <img class="horizontia" src="{{ asset('images/nuevo/Logo-Horizontia-footer.png') }}" alt="Horizontia" width="165" loading="lazy">
                @if ($event->asset('fama'))<img src="{{ $event->asset('fama') }}" alt="FAMA, Físicos Astrónomos Mexicanos y Asociados" loading="lazy">@endif
                @if ($event->asset('club'))<img src="{{ $event->asset('club') }}" alt="Club Campestre Teotihuacán" loading="lazy">@endif
            </div>
            @if ($event->contact_email || $event->contact_whatsapp)
                <p style="margin:0">Contacto del evento:
                    @if ($event->contact_email)<a href="mailto:{{ $event->contact_email }}">{{ $event->contact_email }}</a>@endif
                    @if ($event->contact_email && $event->contact_whatsapp) · @endif
                    @if ($event->contact_whatsapp)<a href="https://wa.me/{{ preg_replace('/\D+/', '', $event->contact_whatsapp) }}" rel="noopener" target="_blank">WhatsApp {{ $event->contact_whatsapp }}</a>@endif
                </p>
            @endif
            <nav class="ev-foot__links" aria-label="Información legal">
                <a href="{{ $event->url('politicas') }}">Políticas del evento</a>
                <a href="{{ route('legal.privacy') }}" rel="noopener">Aviso de privacidad</a>
            </nav>
        </div>
    </footer>

    <div class="ev-cookie" id="evCookie" role="dialog" aria-label="Aviso de cookies" hidden>
        <p>Usamos cookies necesarias para que el sitio funcione (carrito y pagos){{ gtmContainerId() ? ' y, solo si las aceptas, de analítica' : '' }}. <a href="{{ route('legal.cookies') }}">Más información</a></p>
        @if (gtmContainerId())
            <button type="button" class="ev-btn ev-btn--ghost ev-btn--sm" data-cookie="necessary">Solo necesarias</button>
            <button type="button" class="ev-btn ev-btn--sm" data-cookie="all">Aceptar todas</button>
        @else
            <button type="button" class="ev-btn ev-btn--sm" data-cookie="necessary">Entendido</button>
        @endif
    </div>

    <script>
    window.dataLayer = window.dataLayer || [];
    function evPush(name, extra) { window.dataLayer.push(Object.assign({ event: name, event_slug: @json($event->slug) }, extra || {})); }
    (function () {
        var b = document.body;
        // Modo luz roja
        var red = document.getElementById('evRed');
        function setRed(on) { b.classList.toggle('ev-red', on); red.setAttribute('aria-pressed', on ? 'true' : 'false'); try { localStorage.setItem('ev_red', on ? '1' : '0'); } catch (e) {} }
        try { if (localStorage.getItem('ev_red') === '1') setRed(true); } catch (e) {}
        red.addEventListener('click', function () { setRed(!b.classList.contains('ev-red')); });
        // Aviso de cookies (misma clave que el resto del sitio, para que la analítica respete la elección)
        var ck = document.getElementById('evCookie'), seen = false;
        try { seen = !!localStorage.getItem('ht_cookie_ok'); } catch (e) {}
        if (!seen) { ck.hidden = false; }
        ck.addEventListener('click', function (e) { var v = e.target.getAttribute('data-cookie'); if (!v) return; try { localStorage.setItem('ht_cookie_ok', v); } catch (x) {} ck.hidden = true; if (v === 'all') window.dispatchEvent(new CustomEvent('ht:cookies-accepted')); });
        // Seguimiento de clics marcados
        document.addEventListener('click', function (e) { var t = e.target.closest('[data-ev-track]'); if (t) evPush(t.getAttribute('data-ev-track')); });
    })();
    @if (session('event_pixel')) evPush(@json(session('event_pixel')), { value: {{ (float) ($event->price ?? 0) }}, currency: 'MXN' }); @endif
    @stack('scripts')
    </script>
</body>
</html>
