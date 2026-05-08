@props(['page'=> null])
@php
    $headerVariations = setting('_front_page_settings.header_variation_for_pages');
    $headerVariation  = '';
    if (!empty($headerVariations) && is_array($headerVariations)) {
        foreach ($headerVariations as $key => $variation) {
           if(is_array($variation) && isset($variation['page_id']) && is_object($page) && $variation['page_id'] == $page->id) {
                $headerVariation = $variation['header_variation'];
                break;
           }
        }
    }
@endphp

<header class="am-header_two {{ $headerVariation }}">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="am-header_two_wrap">
                    <strong class="am-logo">
                        <a href="{{ route('home') }}">
                            <x-application-logo />
                        </a>
                    </strong>
                    <nav class="am-navigation navbar-expand-xl">
                        <div class="am-navbar-toggler">
                            <div class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#tenavbar" aria-expanded="false" aria-label="Toggle navigation" role="button"></div>
                            <input type="checkbox" id="checkbox">
                            <label for="checkbox" class="toggler-menu">
                                <span class="menu-bars" id="menu-bar1"></span>
                                <span class="menu-bars" id="menu-bar2"></span>
                                <span class="menu-bars" id="menu-bar3"></span>
                            </label>
                        </div>
                        <div class="collapse navbar-collapse" id="tenavbar">
                            <style>
                                #tenavbar .navbar-nav li,
                                #tenavbar .navbar-nav li::before,
                                #tenavbar .navbar-nav li::after { content: none !important; display:none; list-style:none !important; }
                                #tenavbar .navbar-nav li { display: block !important; margin:0; padding:0; }
                                /* Fix potential stray arrows or bullets in header */
                                .am-header_two_wrap .am-navigation::before, .am-header_two_wrap .am-navigation::after { content: none !important; }
                            </style>
                            <ul class="navbar-nav" style="display:flex; gap:20px; list-style-type:none; padding:0; margin:0; align-items:center;">
                                <li class="nav-item" style="list-style:none; padding:0; margin:0; background:none;">
                                    <a class="nav-link" href="{{ route('home') }}" style="color:#1a1a2e; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:5px;">
                                        <i class="am-icon-home" style="color:#667eea;"></i> Inicio
                                    </a>
                                </li>
                                <li class="nav-item" style="list-style:none; padding:0; margin:0; background:none;">
                                    <a class="nav-link" href="{{ route('courses.search-courses') }}" style="color:#1a1a2e; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:5px;">
                                        <i class="am-icon-book-1" style="color:#ff9a9e;"></i> Cursos
                                    </a>
                                </li>
                                <li class="nav-item" style="list-style:none; padding:0; margin:0; background:none;">
                                    <a class="nav-link" href="{{ route('find-tutors') }}" style="color:#1a1a2e; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:5px;">
                                        <i class="am-icon-user-group" style="color:#f6d365;"></i> Tutores
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                    @auth
                        <x-frontend.user-menu />
                    @endauth
                    @guest
                        <div class="am-loginbtns">
                            <a href="{{ route('login') }}" class="am-btn">{{ __('general.login') }}</a>
                            <a href="{{ route('register') }}" class="am-white-btn">{{ __('general.get_started') }}</a>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>
</header>
