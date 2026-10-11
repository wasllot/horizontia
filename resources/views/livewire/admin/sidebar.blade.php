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

        // Cada ítem pertenece a un grupo; el menú se pinta agrupado y con el orden de $groups.
        $items = [
            ['group' => 'general', 'title' => __('sidebar.insights'), 'icon' => 'icon-layers', 'routes' => ['admin.insights' => __('sidebar.insights')]],
            ['group' => 'general', 'title' => 'Reportes', 'icon' => 'icon-bar-chart-2', 'routes' => ['admin.reports' => 'Reportes']],
            ['group' => 'academy', 'title' => __('sidebar.users'), 'icon' => 'icon-users', 'routes' => [
                'admin.users' => __('admin/sidebar.users'),
                'admin.access-requests' => 'Solicitudes de acceso',
                'admin.identity-verification' => __('admin/general.identity_verification'),
            ]],
            ['group' => 'academy', 'title' => 'Rutas de aprendizaje', 'icon' => 'icon-git-branch', 'routes' => ['admin.learning-paths' => 'Rutas de aprendizaje']],
            ['group' => 'academy', 'title' => 'Estudio de contenido', 'icon' => 'icon-cpu', 'routes' => ['admin.content-studio' => 'Estudio de contenido']],
            ['group' => 'academy', 'title' => 'Comunidad', 'icon' => 'icon-message-circle', 'routes' => ['admin.community' => 'Comunidad']],
            ['group' => 'academy', 'title' => 'Certificados', 'icon' => 'icon-award', 'routes' => ['admin.certificates' => 'Certificados']],
            ['group' => 'academy', 'title' => __('sidebar.contacts'), 'icon' => 'icon-inbox', 'routes' => ['admin.leads' => __('sidebar.contacts')]],
            ['group' => 'content', 'title' => __('sidebar.blog'), 'icon' => 'icon-edit-3', 'routes' => [
                'admin.blog-listing' => __('blogs.blog_listing'),
                'admin.create-blog' => __('blogs.create_blog'),
                'admin.blog-categories' => __('blogs.blog_categories'),
            ]],
            ['group' => 'content', 'title' => __('sidebar.sitepages'), 'icon' => 'icon-file-text', 'routes' => [
                'admin.pages' => __('sidebar.site_pages'),
                'admin.course-pages' => __('sidebar.course_pages'),
                'admin.page-banners' => 'Banners de páginas',
            ]],
            ['group' => 'content', 'title' => __('sidebar.menu'), 'icon' => 'icon-menu', 'routes' => ['admin.manage-menus' => __('sidebar.menu')]],
            ['group' => 'content', 'title' => 'SEO', 'icon' => 'icon-search', 'routes' => ['admin.seo' => 'SEO']],
            ['group' => 'business', 'title' => __('sidebar.finance'), 'icon' => 'icon-credit-card', 'routes' => [
                'admin.invoices' => __('admin/sidebar.invoices'),
                'admin.bookings' => __('admin/sidebar.bookings'),
                'admin.withdraw-requests' => __('sidebar.withdraw_requests'),
                'admin.payment-methods' => __('sidebar.payment_methods'),
                'admin.coupons' => 'Cupones',
                'admin.commission-settings' => __('sidebar.commission_settings'),
                'admin.disputes' => __('sidebar.disputes'),
            ]],
            ['group' => 'system', 'title' => __('sidebar.nav_settings'), 'icon' => 'icon-settings', 'routes' => [
                'optionbuilder' => __('sidebar.general_settings'),
                'admin.email-settings' => __('sidebar.email_settings'),
                'admin.notification-settings' => __('sidebar.notification_settings'),
                'admin.config-transfer' => 'Exportar / importar',
            ]],
            ['group' => 'system', 'title' => 'Estado del sistema', 'icon' => 'icon-activity', 'routes' => ['admin.system-status' => 'Estado del sistema']],
            ['group' => 'system', 'title' => __('sidebar.languages'), 'icon' => 'icon-globe', 'routes' => [
                'admin.taxonomy.languages' => __('sidebar.languages'),
                'admin.taxonomy.subjects' => __('sidebar.subjects'),
                'admin.taxonomy.subject-groups' => __('sidebar.subject_groups'),
            ]],
        ];

        if (\Nwidart\Modules\Facades\Module::has('subscriptions') && \Nwidart\Modules\Facades\Module::isEnabled('subscriptions')) {
            $items[] = ['group' => 'business', 'title' => __('sidebar.manage_subscriptions'), 'icon' => 'icon-repeat', 'routes' => [
                'admin.subscriptions.index' => __('sidebar.subscriptions_list'),
                'admin.subscriptions.purchased' => __('sidebar.purchased_subscriptions'),
            ]];
        }

        if (\Nwidart\Modules\Facades\Module::has('forumwise') && \Nwidart\Modules\Facades\Module::isEnabled('forumwise')) {
            $items[] = ['group' => 'academy', 'title' => __('sidebar.forums'), 'icon' => 'icon-message-square', 'routes' => [
                'categories' => __('sidebar.categories'),
                'forums' => __('sidebar.forums'),
            ]];
        }

        if (\Nwidart\Modules\Facades\Module::has('courses') && \Nwidart\Modules\Facades\Module::isEnabled('courses') && function_exists('courseMenuOptions')) {
            foreach (courseMenuOptions('admin') as $courseItem) {
                $items[] = ['group' => 'academy', 'first' => true] + $courseItem;
            }
        }

        if (\Nwidart\Modules\Facades\Module::has('starup') && \Nwidart\Modules\Facades\Module::isEnabled('starup') && function_exists('badgeMenuOptions')) {
            foreach (badgeMenuOptions() as $badgeItem) {
                $items[] = ['group' => 'academy'] + $badgeItem;
            }
        }

        if (isPrivateAcademy()) {
            $marketplaceRoutes = array_flip(['admin.bookings', 'admin.withdraw-requests', 'admin.commission-settings', 'admin.disputes', 'admin.taxonomy.subjects', 'admin.taxonomy.subject-groups']);
            foreach ($items as $index => $item) {
                $items[$index]['routes'] = array_diff_key($item['routes'] ?? [], $marketplaceRoutes);
            }
        }

        // Orden estable: por grupo; los cursos abren el grupo "Academia".
        $order = array_flip(['general', 'academy', 'content', 'business', 'system']);
        $indexed = [];
        foreach ($items as $position => $item) {
            $indexed[] = [$order[$item['group']] ?? 99, empty($item['first']) ? 1 : 0, $position, $item];
        }
        usort($indexed, fn ($a, $b) => [$a[0], $a[1], $a[2]] <=> [$b[0], $b[1], $b[2]]);
        $this->menuItems = array_map(fn ($row) => $row[3], $indexed);
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login'), navigate: false);
    }
}; ?>
<aside class="hz-sidebar" id="hz-sidebar" aria-label="{{ __('Navegación principal') }}">
    <div class="hz-sidebar__brand">
        <a href="{{ route('admin.insights') }}" class="hz-brand" aria-label="{{ setting('_general.site_name') ?: config('app.name') }}">
            <x-application-logo-img variation="white" />
        </a>
        <button type="button" class="hz-iconbtn hz-sidebar__collapse" data-hz-collapse aria-pressed="false" aria-label="Contraer menú lateral">
            <x-panel.icon name="collapse" />
        </button>
    </div>

    <nav class="hz-nav" aria-label="{{ __('Menú de administración') }}">
        <ul class="hz-nav__list">
            @php $lastGroup = null; @endphp
            @foreach ($menuItems as $index => $item)
                @php
                    $count  = count($item['routes']);
                    $isOpen = array_key_exists($activeRoute, $item['routes']);
                    $first  = array_key_first($item['routes']);
                @endphp
                @continue($count === 0)
                @if (($item['group'] ?? null) !== $lastGroup)
                    @php $lastGroup = $item['group'] ?? null; @endphp
                    @if ($lastGroup !== 'general')
                        <li class="hz-nav__heading" role="presentation">{{ __('sidebar.group_' . $lastGroup) }}</li>
                    @endif
                @endif
                <li @class(['hz-nav__item', 'is-active' => $isOpen, 'is-open' => $isOpen && $count > 1])>
                    @if ($count > 1)
                        <button type="button" class="hz-nav__link" data-hz-toggle data-tip="{{ $item['title'] }}" aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="hz-sub-{{ $index }}">
                            <span class="hz-nav__icon"><i class="{{ $item['icon'] }}" aria-hidden="true"></i></span>
                            <span class="hz-nav__label">{{ $item['title'] }}</span>
                            <x-panel.icon name="chevron-down" class="hz-nav__chev" />
                        </button>
                        <div class="hz-nav__subwrap" id="hz-sub-{{ $index }}">
                            <ul class="hz-nav__sub">
                                @foreach ($item['routes'] as $route => $label)
                                    <li @class(['hz-nav__item', 'is-active' => request()->routeIs($route)])>
                                        <a class="hz-nav__link" href="{{ route($route) }}" @if (request()->routeIs($route)) aria-current="page" @endif>
                                            <span class="hz-nav__label">{{ $label }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <a class="hz-nav__link" href="{{ route($first) }}" data-tip="{{ $item['title'] }}" @if ($isOpen) aria-current="page" @endif>
                            <span class="hz-nav__icon"><i class="{{ $item['icon'] }}" aria-hidden="true"></i></span>
                            <span class="hz-nav__label">{{ $item['title'] }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="hz-sidebar__foot">
        <button type="button" class="hz-nav__link hz-nav__link--logout" wire:click="logout" data-tip="{{ __('sidebar.logout') }}">
            <span class="hz-nav__icon"><x-panel.icon name="logout" /></span>
            <span class="hz-nav__label">{{ __('sidebar.logout') }}</span>
        </button>
    </div>
</aside>
@push('scripts')
<script>
    document.addEventListener('livewire:initialized', function () {
        document.addEventListener('update_image', function (event) {
            document.querySelectorAll('[data-hz-avatar] img').forEach(function (img) { img.src = event.detail.image; });
        });
    });
</script>
@endpush
