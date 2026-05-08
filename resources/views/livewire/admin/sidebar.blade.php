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
                'title' =>  __('sidebar.site_management'),
                'icon'  => 'icon-layout',
                'links' => [
                    'manage-menu' => [
                        'link'  => '',
                        'title' => __('sidebar.menu')
                    ]
                ],
                'routes' => [
                    'admin.manage-menus' => __('sidebar.menu'),
                    'optionbuilder' => __('sidebar.settings'),
                    'pagebuilder' => __('sidebar.sitepages'),
                    'admin.email-settings' => __('sidebar.email_settings'),
                    'admin.notification-settings' => __('sidebar.notification_settings'),
                ],
            ],
            [
                'title' =>  __('sidebar.taxonomies'),
                'icon'  => 'icon-database',
                'routes' => [
                    'admin.taxonomy.languages' => __('sidebar.languages'),
                    'admin.taxonomy.subjects' => __('sidebar.subjects'),
                    'admin.taxonomy.subject-groups' => __('sidebar.subject_groups'),
                ],
            ],
            [
                'title' =>  __('sidebar.translation_settings'),
                'icon'  => 'icon-globe',
                'routes' => [
                    'ltu.translation.index' => __('sidebar.languages')
                ],
            ],
            [
                'title' =>  __('sidebar.manage_packages'),
                'icon'  => 'icon-folder-plus',
                'routes' => [
                    'admin.packages.index' => __('sidebar.add_new_package'),
                    'admin.packages.installed' => __('sidebar.installed_packages')
                ],
            ],
            [
                'title' =>  __('sidebar.upgrade'),
                'icon'  => 'icon-upload-cloud',
                'routes' => [
                    'admin.upgrade' => __('sidebar.upgrade')
                ],
            ],
            [
                'title' =>  __('admin/sidebar.users'),
                'icon'  => 'icon-users',
                'routes' => [
                    'admin.users' => __('admin.users')
                ],
            ],
            [
                'title' =>  __('admin/general.identity_verification'),
                'icon'  => 'icon-user-check',
                'routes' => [
                    'admin.identity-verification' => __('identity-verification')
                ],
            ],
            [
                'title' =>  __('admin/sidebar.invoices'),
                'icon'  => 'icon-dollar-sign',
                'routes' => [
                    'admin.invoices' => __('invoices')
                ],
            ],
            [
                'title' =>  __('admin/sidebar.bookings'),
                'icon'  => 'icon-file-text',
                'routes' => [
                    'admin.bookings' => __('bookings')
                ],
            ],
            [
                'title' =>  __('sidebar.transaction_payment'),
                'icon'  => 'icon-credit-card',
                'routes' => [
                    'admin.withdraw-requests' => __('sidebar.withdraw_requests'),
                    'admin.commission-settings' => __('sidebar.commission_settings'),
                    'admin.payment-methods' => __('sidebar.payment_methods'),
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
                <li class="{{ request()->routeIs('admin.disputes') || request()->routeIs('admin.manage-dispute') ? 'active' : '' }}">
                    <a href="{{route('admin.disputes')}}" class="tb-menuitm">
                        <i class="icon-alert-triangle"></i><span class="tb-navdashboard__title">{{ __('sidebar.disputes')}}</span>
                    </a>
                </li>

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
