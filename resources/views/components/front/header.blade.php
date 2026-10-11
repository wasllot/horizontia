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
                            @php $navItems = app(\App\Services\MenuService::class)->header(); @endphp
                            <ul class="navbar-nav ht-nav">
                                @foreach ($navItems as $item)
                                    @php
                                        $path = '/' . ltrim((string) parse_url($item['url'], PHP_URL_PATH), '/');
                                        $active = $path === '/' ? request()->path() === '/' : (request()->is(ltrim($path, '/')) || request()->is(ltrim($path, '/') . '/*'));
                                    @endphp
                                    @if (!empty($item['children']))
                                        <li class="nav-item ht-nav__item ht-nav__has-children" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @keydown.escape="open = false">
                                            <a class="nav-link {{ $active ? 'is-active' : '' }}" href="{{ $item['url'] }}" target="{{ $item['target'] }}" aria-haspopup="true" :aria-expanded="open">{{ $item['label'] }} <span class="ht-nav__caret" aria-hidden="true"></span></a>
                                            <ul class="ht-nav__dropdown" x-show="open" x-transition.opacity.duration.150ms x-cloak>
                                                @foreach ($item['children'] as $child)
                                                    <li><a href="{{ $child['url'] }}" target="{{ $child['target'] }}">{{ $child['label'] }}</a></li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    @else
                                        <li class="nav-item ht-nav__item"><a class="nav-link {{ $active ? 'is-active' : '' }}" href="{{ $item['url'] }}" target="{{ $item['target'] }}" @if ($active) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </nav>
                    @auth
                        <x-frontend.user-menu />
                    @endauth
                    @guest
                        <div class="am-loginbtns">
                            @if (\App\Services\AccessRequestService::enabled())
                                <a href="{{ route('access.request') }}" class="am-request-link" style="margin-right: 18px; color: #14213d; font-weight: 700; text-decoration: none; white-space: nowrap;">Solicitar acceso</a>
                            @endif
                            <a href="{{ route('login') }}" class="am-btn">{{ __('general.login') }}</a>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>
</header>
