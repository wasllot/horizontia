<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    {{-- Same assets as layouts/frontend-app: the header and footer are styled by horizontia-ui.css and need select2/splide too. --}}
    @vite([
    'public/css/bootstrap.min.css',
    'public/css/fonts.css',
    'public/css/icomoon/style.css',
    'public/css/select2.min.css',
    'public/css/splide.min.css',
    ])
    <link rel="stylesheet" type="text/css" href="{{ asset('css/main.css') }}">
    <x-favicon />
    <x-typography />
    @stack('styles')
    <x-ui-styles />
</head>

<body class="am-bodywrap">
    <a class="ht-skip" href="#main-content">Saltar al contenido</a>
    <x-front.header :page="null" />
    <main class="am-main am-404" id="main-content" tabindex="-1">
        <div class="tk-errorpage">
            <div class="tk-errorpage_content">
                <h1>@yield('code')</h1>
                <div class="tk-errorpage_title">
                    <h2>@yield('heading')</h2>
                    <p>@yield('message')</p>
                    <a href="{{ url('/') }}" class="am-btn">{{ __('general.go_to_home') }}</a>
                </div>
            </div>
        </div>
    </main>
    <x-popups />
    <x-front.footer :page="null" />
    @livewireScripts()
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script defer src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script defer src="{{ asset('js/select2.min.js') }}"></script>
    <script defer src="{{ asset('js/splide.min.js') }}"></script>
    <script defer src="{{ asset('js/main.js') }}"></script>
    @stack('scripts')
</body>

</html>