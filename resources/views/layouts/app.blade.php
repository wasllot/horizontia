<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if( !empty(setting('_general.enable_rtl')) || !empty(session()->get('rtl')) ) dir="rtl" @endif>
    <head>
    <x-gtm-head :panel="true" />
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $siteTitle = setting('_general.site_name') ?? 'Horizontia';
            $siteUrl = config('app.url');
            $pageTitle = request()->is('messenger') ? 'Mensajes' : ($title ?? currentPageTitle() ?? '');
            $fullTitle = $pageTitle ? $siteTitle . ' | ' . $pageTitle : $siteTitle;
            $pageDescription = $pageDescription ?? null;
            $metaImage = $metaImage ?? null;
        @endphp
        <title>{{ $fullTitle }}</title>
        <meta name="description" content="{{ $pageDescription ? Str::limit(strip_tags($pageDescription), 160) : '' }}">
        <meta name="robots" content="noindex, nofollow">
        <link rel="canonical" href="{{ request()->fullUrl() }}">
        @if($pageDescription)
        <meta property="og:description" content="{{ Str::limit(strip_tags($pageDescription), 160) }}">
        @endif
        <meta property="og:title" content="{{ $fullTitle }}">
        <meta property="og:url" content="{{ request()->fullUrl() }}">
        <meta property="og:site_name" content="{{ $siteTitle }}">
        <meta property="og:type" content="website">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $fullTitle }}">
        <x-favicon />
        <x-pwa />
        <x-typography />
        <x-pwa-scripts />
        @vite([
            'public/css/bootstrap.min.css',
            'public/css/fonts.css',
            'public/css/icomoon/style.css',
            'public/css/select2.min.css',
        ])
        <link rel="stylesheet" type="text/css" href="{{ asset('css/vendor/theme-user.css') }}?v={{ @filemtime(public_path('css/vendor/theme-user.css')) }}">
        @if( !empty(setting('_general.enable_rtl')) || !empty(session()->get('rtl')) )
            <link rel="stylesheet" type="text/css" href="{{ asset('css/rtl.css') }}">
        @endif
        @stack('styles')
        <x-panel-styles />
        <meta name="hz-pulse" content="{{ route('panel.pulse') }}">
        <script data-navigate-once>try{if(localStorage.getItem('hz.sidebar.collapsed')==='1'&&innerWidth>1199)document.documentElement.classList.add('hz-collapsed')}catch(e){}</script>
        @livewire('livewire-ui-spotlight')
        @livewireStyles()
    </head>
    <body class="hz hz--user font-sans antialiased @if( !empty(setting('_general.enable_rtl')) || !empty(session()->get('rtl')) ) am-rtl @endif"
        x-data="{ isDragging: false }"
        x-on:dragover.prevent="isDragging = true"
        x-on:drop="isDragging = false">
    <x-gtm-body :panel="true" />
        <a class="hz-skip" href="#hz-main">{{ __('Saltar al contenido') }}</a>
        <div class="hz-shell">
            <livewire:pages.common.navigation />
            <div class="hz-scrim" data-hz-scrim></div>
            <div class="hz-body">
                <livewire:header.header />
                <main class="hz-content" id="hz-main" tabindex="-1" @if(\App\Support\PanelLive::interval()) data-hz-live="{{ \App\Support\PanelLive::interval() }}" @endif>
                    <div class="am-dashboard_box">
                        <div class="am-dashboard_box_wrap">
                            @yield('content')
                            {{ $slot ?? '' }}
                            @if (setting('_api.active_conference') == 'google_meet' && empty(isCalendarConnected(Auth::user())))
                                <div class="am-connect_google_calendar">
                                    <div class="am-connect_google_calendar_title">
                                        <figure>
                                            <img src="{{ asset('images/calendar.png') }}" alt="Image">
                                        </figure>
                                        <h4>{{ __('passwords.connect_google_calendar') }}</h4>
                                        <i class="am-icon-multiply-02" @click="jQuery('.am-connect_google_calendar').remove()"></i>
                                    </div>
                                    <p> {{ __('calendar.'.auth()->user()->role.'_calendar_alert_msg') }}</p>
                                    <a href="{{ route(auth()->user()->role.'.profile.account-settings') }}" class="am-btn">{{ __('general.connect') }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </main>
            </div>
            @if(session('impersonated_name'))
                <div class="am-impersonation-bar">
                    <span>{{ __('general.impersonating') }} <strong>{{ session('impersonated_name') }}</strong></span>
                    <a href="{{ route('exit-impersonate') }}" class="am-btn">{{ __('general.exit') }}</a>
                </div>
            @endif
        </div>
        <x-popups />
        @livewireScripts()
        <script src="{{ asset('js/jquery.min.js') }}" data-navigate-once></script>
        <script src="{{ asset('js/panel-compat.js') }}?v={{ @filemtime(public_path('js/panel-compat.js')) }}" data-navigate-once></script>
        <script defer src="{{ asset('js/bootstrap.min.js') }}" data-navigate-once></script>
        <script defer src="{{ asset('js/select2.min.js') }}" data-navigate-once></script>
        <script defer src="{{ asset('js/main.js') }}" data-navigate-once></script>
        <script defer data-navigate-once src="{{ asset('js/panel.js') }}?v={{ @filemtime(public_path('js/panel.js')) }}"></script>
        @stack('scripts')
        @if(showAIWriter())
            <x-open_ai />
        @endif
        <script data-navigate-once>
            document.addEventListener("DOMContentLoaded", () => {
                Livewire.on('remove-cart', (event) => {
                    const currentRoute = '{{ request()->route()->getName() }}';

                    const { index, cartable_id, cartable_type } = event.params;
                    if (currentRoute != 'tutor-detail') {
                        fetch('/remove-cart', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ index, cartable_id, cartable_type })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const event = new CustomEvent('cart-updated', {
                                detail: {
                                    cart_data: data.cart_data,
                                    total: data.total,
                                    subTotal: data.subTotal,
                                    discount: data.discount,
                                    toggle_cart: data.toggle_cart
                                }
                            });
                            window.dispatchEvent(event);
                        } else {
                            console.error('Failed to update cart:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                    }
                });
            });
        </script>
        <x-whatsapp-button :panel="true" />
</body>
</html>
