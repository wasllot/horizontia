<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Route;
use App\Services\OrderService;
new class extends Component
{
    public $menuItems = [];
    public $activeRoute = [];
    public $totalCommission = 0;

    public function mount()
    {
        $this->totalCommission = (new OrderService())->getTotalCommission();
        $this->activeRoute = Route::currentRouteName();
        $this->menuItems = [
            [
                'title' =>  __('sidebar.insights'),
                'icon'  => 'icon-layers',
                'routes' => [
                    'admin.insights' => __('sidebar.insights'),
                ],
            ],
            [
                'title' =>  'Leads & Contactos',
                'icon'  => 'icon-inbox',
                'routes' => [
                    'admin.leads' => 'Ver Mensajes'
                ],
            ],
            [
                'title' =>  'Usuarios y Perfiles',
                'icon'  => 'icon-users',
                'routes' => [
                    'admin.users' => __('admin/sidebar.users'),
                    'admin.identity-verification' => __('admin/general.identity_verification')
                ],
            ],
            [
                'title' =>  'Finanzas y Pagos',
                'icon'  => 'icon-dollar-sign',
                'routes' => [
                    'admin.bookings' => __('admin/sidebar.bookings'),
                    'admin.invoices' => __('admin/sidebar.invoices'),
                    'admin.withdraw-requests' => __('sidebar.withdraw_requests'),
                    'admin.payment-methods' => __('sidebar.payment_methods'),
                    'admin.commission-settings' => __('sidebar.commission_settings'),
                    'admin.disputes' => __('sidebar.disputes'),
                ],
            ],
            [
                'title' =>  'Sistema y Plataforma',
                'icon'  => 'icon-layout',
                'routes' => [
                    'admin.manage-menus' => __('sidebar.menu'),
                    'pagebuilder' => __('sidebar.sitepages'),
                    'admin.taxonomy.subjects' => __('sidebar.subjects'),
                    'admin.taxonomy.subject-groups' => __('sidebar.subject_groups'),
                    'admin.taxonomy.languages' => __('sidebar.languages'),
                ],
            ],
            [
                'title' =>  'Ajustes Generales',
                'icon'  => 'icon-settings',
                'routes' => [
                    'optionbuilder' => 'Ajustes Generales',
                    'admin.email-settings' => __('sidebar.email_settings'),
                    'admin.notification-settings' => __('sidebar.notification_settings'),
                ],
            ]
        ];
        if (\Nwidart\Modules\Facades\Module::has('subscriptions') && \Nwidart\Modules\Facades\Module::isEnabled('subscriptions')){
            $this->menuItems[] = [
                'title' =>  __('sidebar.manage_subscriptions'),
                'icon'  => 'icon-repeat',
                'routes' => [
                    'admin.subscriptions.index' => __('sidebar.subscriptions_list'),
                    'admin.subscriptions.purchased' => __('sidebar.purchased_subscriptions'),
                ],
            ];
        }
        $this->menuItems[] = [
                                    'title' =>  __('blogs.manage_blogs'),
                                    'icon'  => 'icon-bold',
                                    'routes' => [
                                        'admin.create-blog'             => __('blogs.create_blog'),
                                        'admin.blog-listing'            => __('blogs.blog_listing'),
                                        'admin.blog-categories'         => __('blogs.blog_categories'),
                                    ],
                            ];

        if (\Nwidart\Modules\Facades\Module::has('forumwise') && \Nwidart\Modules\Facades\Module::isEnabled('forumwise')) {
            $this->menuItems[] = [
                'title' =>  __('sidebar.forums'),
                'icon'  => 'icon-message-square',
                'routes' => [
                    'categories' => __('sidebar.categories'),
                    'forums' => __('sidebar.forums'),
                ],
            ];
        }
      
        if (\Nwidart\Modules\Facades\Module::has('courses') && \Nwidart\Modules\Facades\Module::isEnabled('courses') && function_exists('courseMenuOptions')) {         
            $this->menuItems = array_merge($this->menuItems, courseMenuOptions('admin'));
        }

        if (\Nwidart\Modules\Facades\Module::has('starup') && \Nwidart\Modules\Facades\Module::isEnabled('starup') && function_exists('badgeMenuOptions')) {         
            $this->menuItems = array_merge($this->menuItems, badgeMenuOptions());
        }
    }




    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: false);
    }
}; ?>
@php
    $info       = Auth::user();
@endphp
<div class="tb-sidebarwrapperholder">
    {{--
        NOTE: intentionally an inline <style> here, not @push('styles').
        This component is included directly from layouts/admin-app.blade.php
        (in the <body>), AFTER that layout's own @stack('styles') in the
        <head> has already rendered -- a @push from here registers too late
        to reach it. A plain <style> tag works identically wherever it sits
        in the document, so this renders correctly regardless of order.
    --}}
    <style>
        /* Brand the admin sidebar to match the rest of the redesigned platform
           (navy + gold) instead of the theme's plain white. */
        .tb-sidebarwrapper { background: #14213d !important; }
        /* <x-application-logo /> always resolves to the dark wordmark
           regardless of variation (see components/application-logo.blade.php --
           it hardcodes a single logo URL, ignoring the prop entirely), so it's
           unreadable on this dark sidebar. Force it white, same technique
           already used in resources/views/livewire/pages/auth/login.blade.php. */
        .tb-sidebartop img { filter: brightness(0) invert(1); }
        .tb-siderbar-nav .tb-menuitm { color: #aab3c5 !important; }
        .tb-siderbar-nav .tb-menuitm i { color: #aab3c5 !important; }
        .tb-siderbar-nav .tb-menuitm:hover { background: rgba(254,211,4,0.1) !important; color: #fff !important; }
        .tb-siderbar-nav .tb-menuitm:hover i { color: #fed304 !important; }
        .tb-siderbar-nav li.active > .tb-menuitm,
        .tb-siderbar-nav li.active > .tb-menuitm i { color: #14213d !important; }
        .sidebar-sub-menu li a { color: #aab3c5 !important; }
        .sidebar-sub-menu li.active a,
        .sidebar-sub-menu li a:hover { color: #fed304 !important; }
        #tb-btnmenutoggle a,
        .tb-icongray { color: #aab3c5 !important; }
    </style>
    <aside id="tb-sidebarwrapper" class="tb-sidebarwrapper">
        <div id="tb-btnmenutoggle" class="tb-btnmenutoggle">
            <a href="javascript:void(0);"><i class="ti-pin2"></i></a>
        </div>
        <div class="tb-sidebartop">
            <strong class="am-logo">
                <x-application-logo />
            </strong>
            <a class="tb-icongray" href="javascript:void(0)"><i class="icon-layout"></i></a>
        </div>
        <nav id="tb-navdashboard" class="tb-navdashboard">
            <ul class="tb-siderbar-nav ">
                @foreach ($menuItems as $item)
                    <li @class([ 'menu-has-children' => count($item['routes']) > 1, 'active' => array_key_exists($activeRoute, $item['routes']), 'tb-openmenu' => array_key_exists($activeRoute, $item['routes']) && count($item['routes']) > 1 ])>
                        <a href="{{ count($item['routes']) > 1 ? 'javascript:void(0);' : route( array_key_first($item['routes'])) }}" class="tb-menuitm">
                            <i class="{{ $item['icon'] }}"></i><span class="tb-navdashboard__title">{{ $item['title']}}</span>
                        </a>
                        @if(count($item['routes']) > 1)
                            <ul class="sidebar-sub-menu" style="display:{{array_key_exists($activeRoute, $item['routes']) ? 'block': ''}}">
                                @foreach ( $item['routes'] as $route => $label)
                                    <li class="{{ request()->routeIs($route) ? 'active' : '' }}">
                                        <a href="{{route($route)}}">
                                            <span class="tb-navdashboard__title">{{ $label }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div class="admin-sidebar-footer">
                <!-- <div class="am-wallet">
                    <div class="am-wallet_title">
                        <span class="am-wallet_title_icon">
                            <i class="icon-dollar-sign"></i>
                        </span>
                        <div class="am-wallet_balance">
                            <strong>{!! formatAmount($totalCommission, true) !!}<span>{{ __('general.total_commission') }}</span></strong>
                        </div>
                    </div>
                </div> -->
                <div class="am-signout">
                    <a href="javascript:void(0)" wire:click="logout" class="tb-haslogout tb-menuitm">
                        <i class="ti-power-off"></i><span class="tb-navdashboard__title"> {{ __('sidebar.logout') }}</span>
                    </a>
                </div>
            </div>
        </nav>
    </aside>
</div>
@push('scripts')
<script>
    document.addEventListener('livewire:initialized', function() {
        document.addEventListener('update_image', (event) => {
            $('#adminImage img').attr('src', event.detail.image);
        });
     })
</script>
@endpush
