{{-- PWA: manifiesto + color del navegador + iconos de iOS. El service worker y el botón «Instalar» los registra public/js/pwa.js. --}}
@if (config('academy.pwa', true))
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="#14213d">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit(setting('_general.site_name') ?: config('app.name'), 12, '') }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}">
@endif
