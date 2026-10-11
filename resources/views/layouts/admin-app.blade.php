<!doctype html>
<html class="no-js" lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if( !empty(setting('_general.enable_rtl')) || !empty(session()->get('rtl')) ) dir="rtl" @endif>
@php
    $info       = Auth::user();
    $siteTitle  = setting('_general.site_name') ?: env('APP_NAME');
@endphp
    <head>
    <x-gtm-head :panel="true" />
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title> {{ __('general.adminpanel_title') }} | {{$siteTitle}}</title>
        <meta name="robots" content="noindex, nofollow">
        <x-favicon />
        <x-pwa />
        <x-typography />
        <x-pwa-scripts />
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @vite([
            'public/css/bootstrap.min.css',
            'public/admin/css/themify-icons.css',
            'public/admin/css/fontawesome/all.min.css',
            'public/css/select2.min.css',
            'public/css/mCustomScrollbar.min.css',
            'public/admin/css/feather-icons.css',
        ])
        <link rel="stylesheet" type="text/css" href="{{ asset('css/vendor/theme-admin.css') }}?v={{ @filemtime(public_path('css/vendor/theme-admin.css')) }}">
        @stack('styles')
        <x-panel-styles />
        <script data-navigate-once>try{if(localStorage.getItem('hz.sidebar.collapsed')==='1'&&innerWidth>1199)document.documentElement.classList.add('hz-collapsed')}catch(e){}</script>
        @if( !empty(setting('_general.enable_rtl')) || !empty(session()->get('rtl')) )
            <link rel="stylesheet" type="text/css" href="{{ asset('admin/css/rtl.css') }}"
            @if (\Nwidart\Modules\Facades\Module::has('forumwise') && \Nwidart\Modules\Facades\Module::isEnabled('forumwise'))
                <link rel="stylesheet" type="text/css" href="{{ asset('modules/forumwise/css/rtl.css') }}">
            @endif
        @endif
        @livewireStyles
    </head>
    <body class="hz hz--admin @if( !empty(setting('_general.enable_rtl')) || !empty(session()->get('rtl')) ) am-rtl @endif">
    <x-gtm-body :panel="true" />
        <a class="hz-skip" href="#hz-main">{{ __('Saltar al contenido') }}</a>
        <div class="hz-shell">
            <livewire:admin.sidebar />
            <div class="hz-scrim" data-hz-scrim></div>
            <div class="hz-body">
                <header class="hz-topbar">
                    <button type="button" class="hz-iconbtn hz-topbar__menu" data-hz-menu aria-controls="hz-sidebar" aria-expanded="false" aria-label="{{ __('Abrir menú') }}">
                        <x-panel.icon name="menu" />
                    </button>
                    <div class="hz-topbar__crumbs">{{ Breadcrumbs::render() }}</div>
                    <div class="hz-topbar__actions">
                        <a class="hz-viewsite" href="{{ url('/') }}" target="_blank" rel="noopener">
                            <x-panel.icon name="external" /> <span>{{ __('general.view_site') }}</span>
                        </a>
                        @if(!empty($info))
                            <div class="hz-pop">
                                <button type="button" class="hz-avatarbtn" data-hz-pop-toggle aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Menú de cuenta') }}" data-hz-avatar>
                                    <span class="hz-avatar">@include('components.panel.avatar-img', ['user' => $info, 'px' => 34])</span>
                                    <x-panel.icon name="chevron-down" size="14" style="margin-right:6px;color:var(--hz-muted)" />
                                </button>
                                <div class="hz-pop__panel" role="menu">
                                    <div class="hz-menu__who">
                                        <span class="hz-avatar" data-hz-avatar>@include('components.panel.avatar-img', ['user' => $info, 'px' => 36])</span>
                                        <div><strong>{{ $info->profile?->full_name ?: $info->email }}</strong><span>{{ $info->email }}</span></div>
                                    </div>
                                    <a class="hz-menu__item" role="menuitem" href="{{ route('admin.profile') }}"><x-panel.icon name="user" /> {{ __('sidebar.profile') }}</a>
                                    <a class="hz-menu__item" role="menuitem" href="{{ url('/') }}" target="_blank" rel="noopener"><x-panel.icon name="external" /> {{ __('general.view_site') }}</a>
                                    <a class="hz-menu__item" role="menuitem" href="{{ route('admin.clear-cache') }}"><x-panel.icon name="brush" /> {{ __('sidebar.clear-cache') }}</a>
                                    <hr class="hz-menu__sep">
                                    <a class="hz-menu__item hz-menu__item--danger" role="menuitem" href="{{ route('logout') }}"><x-panel.icon name="logout" /> {{ __('sidebar.logout') }}</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </header>
                <main class="hz-content" id="hz-main" tabindex="-1" @if(\App\Support\PanelLive::interval()) data-hz-live="{{ \App\Support\PanelLive::interval() }}" @endif>
                    @yield('content')
                    @if(!empty($slot))
                        {{ $slot }}
                    @endif
                </main>
            </div>
            <x-popups />
        </div>
        <script data-navigate-once>
            document.addEventListener('DOMContentLoaded', (event) => {
                jQuery(document).on("click", '.update-section-settings', function(event){                
                    let _this = jQuery(this);
                    let smtpSettingsUrl = "{{ !isDemoSite() && Route::has('admin.update-smtp-settings') ? route('admin.update-smtp-settings') : null }}";
                    if(smtpSettingsUrl && $('#smtp_setting').hasClass('active')) {
                        setTimeout(function() {
                            $.ajaxSetup({
                                headers: {
                                'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                                }
                            });
                            $.ajax({
                                url: smtpSettingsUrl,
                                method: 'post',
                                success: function(data){
                                    _this.find('.spinner-border').addClass('d-none');
                                    showAlert({
                                        message     : data.message,
                                        type        : data.success ? 'success' : 'error',
                                        title       : data.success ? @json(__('admin/general.success')) : @json(__('admin/general.error')),
                                        autoclose   : 4000,
                                    });
                                }
                            });
                        }, 1000);  
                    }

                    let s3SettignsUrl   = `{{ !isDemoSite() && 
                                                \Nwidart\Modules\Facades\Module::has('s3konnect') &&
                                                \Nwidart\Modules\Facades\Module::isEnabled('s3konnect') &&
                                                Route::has('s3konnect.settings') ? route('s3konnect.settings') : null
                                            }}`;
                    if(jQuery('#_storage').hasClass('active') && s3SettignsUrl) {
                        setTimeout(function() {
                                $.ajaxSetup({
                                    headers: {
                                        'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                                    }
                                });
                                $.ajax({
                                    url: s3SettignsUrl,
                                    method: 'post',
                                    success: function(data){
                                        _this.find('.spinner-border').addClass('d-none');
                                        showAlert({
                                            message     : data.message,
                                            type        : data.success ? 'success' : 'error',
                                            title       : data.success ? @json(__('s3konnect::s3konnect.success')) : @json(__('s3konnect::s3konnect.error')),
                                            autoclose   : 4000,
                                            redirectUrl : data.success ? location.href : false,
                                        });
                                    }
                                });
                            }, 1000);
                    }

                    if(jQuery('#social_login-tab').hasClass('active')) {
                        setTimeout(function() {
                                $.ajaxSetup({
                                    headers: {
                                        'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                                    }
                                });
                                $.ajax({
                                    url: `{{ route('admin.update-social-login-settings') }}`,
                                    method: 'post',
                                    success: function(data){
                                        _this.find('.spinner-border').addClass('d-none');
                                        showAlert({
                                            title       : data.success ? @json(__('admin/general.success_title')) : @json(__('admin/general.error_title')),
                                            message     : data.message,
                                            type        : data.success ? 'success' : 'error',
                                            autoclose   : 4000,
                                            redirectUrl : data.success ? location.href : false,
                                        });
                                    }
                                });
                            }, 1000);
                    }

                    if(jQuery(this).attr('data-form') != '_theme-form') {
                        return false;
                    }
                    
                    upadateSassStyle(_this);
                   
                });
                jQuery(document).on("click", '.btn.btn-danger', function(event){
                    if ($('#_theme').hasClass('active')){
                        setTimeout(function() {
                            upadateSassStyle(jQuery('.reset-section-settings'), redirect = location.href,);
                        }, 300);
                    }
                });
            });
            function upadateSassStyle(_this, redirect = false){
                setTimeout(function() {
                    $.ajaxSetup({
                        headers: {
                        'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url: "{{ url('admin/update-sass-style') }}",
                        method: 'post',
                        success: function(data){
                            _this.find('.spinner-border').addClass('d-none');
                            showAlert({
                                message     : data.message,
                                type        : data.success ? 'success' : 'error',
                                title       : data.success ? @json(__('admin/general.success')) : @json(__('admin/general.error')),
                                autoclose   : 4000,
                                redirectUrl : redirect,
                            });
                        }
                    });
                }, 300); 
            }
        </script>
        @livewireScripts
        <script src="{{ asset('js/jquery.min.js') }}" data-navigate-once></script>
        <script src="{{ asset('js/panel-compat.js') }}?v={{ @filemtime(public_path('js/panel-compat.js')) }}" data-navigate-once></script>
        <script defer src="{{ asset('js/bootstrap.min.js') }}" data-navigate-once></script>
        <script defer src="{{ asset('js/select2.min.js') }}" data-navigate-once></script>
        <script defer src="{{ asset('js/mCustomScrollbar.min.js') }}" data-navigate-once></script>
        <script defer src="{{ asset('js/main.js')}}" data-navigate-once></script>
        <script defer src="{{ asset('js/admin-app.js')}}" data-navigate-once></script>
        <script defer data-navigate-once src="{{ asset('js/panel.js') }}?v={{ @filemtime(public_path('js/panel.js')) }}"></script>
        @if(session('success'))
            <script>
                setTimeout(function() {
                    showAlert({
                        message     : "{{ session('success') }}",
                        type        : 'success', 
                        title       : @json(__('admin/general.success')),
                        autoclose   : 4000,
                    });
                }, 100);
            </script>
        @endif
            @stack('scripts')
        <x-whatsapp-button :panel="true" />
</body>
    </html>



