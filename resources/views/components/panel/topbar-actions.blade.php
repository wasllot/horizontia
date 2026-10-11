@php
    $user = auth()->user();
    $isShopper = $user?->hasAnyRole(['student']) || ($user?->hasRole('tutor') && \Nwidart\Modules\Facades\Module::has('subscriptions') && \Nwidart\Modules\Facades\Module::isEnabled('subscriptions'));
    $couponsOn = \Nwidart\Modules\Facades\Module::has('kupondeal') && \Nwidart\Modules\Facades\Module::isEnabled('kupondeal');
    $unread = $user?->unreadNotifications()->count() ?? 0;
    $msgs = $user?->hasAnyRole(['tutor', 'student']) && \App\Services\ChatService::enabled() ? app(\App\Services\ChatService::class)->unreadCount($user) : 0;
@endphp
<x-panel.language />

@if ($isShopper)
    <div class="hz-pop"
         x-data="{
            cartData: @js(App\Facades\Cart::content()),
            total: @js(formatAmount(App\Facades\Cart::total(), true)),
            subTotal: @js(formatAmount(App\Facades\Cart::subtotal(), true)),
            discount: @js(formatAmount(App\Facades\Cart::discount(), true)),
            removeItem(index, cartable_id, cartable_type) {
                this.cartData.splice(index, 1);
                Livewire.dispatch('remove-cart', { params: { index, cartable_id, cartable_type } });
                setTimeout(() => Livewire.dispatch('remove-course-cart', { params: { index, cartable_id, cartable_type } }), 1000);
            }
         }"
         x-on:cart-updated.window="
            cartData = $event.detail.cart_data; total = $event.detail.total; subTotal = $event.detail.subTotal; discount = $event.detail.discount;
            if ($event.detail.toggle_cart == 'open') { $el.classList.add('is-open'); }
         ">
        <button type="button" class="hz-iconbtn" data-hz-pop-toggle aria-haspopup="true" aria-expanded="false" aria-label="{{ __('Carrito de compras') }}">
            <x-panel.icon name="cart" />
            <template x-if="cartData.length > 0"><span class="hz-badge-dot" x-text="cartData.length"></span></template>
        </button>
        <div class="hz-pop__panel hz-cart">
            <template x-if="cartData.length === 0">
                <div class="hz-cart__empty">
                    <strong>{{ __('general.cart_empty') }}</strong>
                    <p>{{ __('general.cart_empty_desc') }}</p>
                    @if (Route::has('courses.search-courses'))<a class="hz-btn hz-btn--secondary hz-btn--sm" href="{{ route('courses.search-courses') }}">{{ __('courses::courses.find_courses') }}</a>@endif
                </div>
            </template>
            <template x-if="cartData.length > 0">
                <div>
                    <h2 class="hz-cart__title">{{ __('tutor.order_summary') }}</h2>
                    <ul class="hz-cart__list">
                        <template x-for="(item, index) in cartData" :key="item.cartable_type + item.cartable_id">
                            <li>
                                <div class="hz-cart__info">
                                    <strong x-text="item.name || (item.options && item.options.subject)"></strong>
                                    <span x-text="item.options && (item.options.sub_category || item.options.session_time || item.options.period)"></span>
                                </div>
                                <span class="hz-cart__price" x-html="item.options && item.options.price"></span>
                                <button type="button" class="hz-iconbtn" @click.prevent="removeItem(index, item.cartable_id, item.cartable_type)" aria-label="{{ __('general.remove') }}"><x-panel.icon name="x" size="14" /></button>
                            </li>
                        </template>
                    </ul>
                    <dl class="hz-cart__totals">
                        <div><dt>{{ __('general.subtotal') }}</dt><dd x-html="subTotal"></dd></div>
                        @if ($couponsOn)<template x-if="!/\b0\.00\b/.test(discount)"><div><dt>{{ __('general.discount') }}</dt><dd x-html="discount"></dd></div></template>@endif
                        <div class="is-total"><dt>{{ __('general.grand_total') }}</dt><dd x-html="total"></dd></div>
                    </dl>
                    <a class="hz-btn hz-btn--primary hz-btn--block" href="{{ route('checkout') }}" wire:navigate.remove>{{ __('general.proceed_order') }}</a>
                </div>
            </template>
        </div>
    </div>
@endif

@hasanyrole('tutor|student')
    <div class="hz-pop">
        <button type="button" class="hz-iconbtn" data-hz-pop-toggle aria-haspopup="true" aria-expanded="false" aria-label="{{ __('general.notifications') }}">
            <x-panel.icon name="bell" />
            <span class="hz-badge-dot" data-hz-badge="notifications" aria-live="polite" @if (!$unread) hidden @endif>{{ $unread }}</span>
        </button>
        <div class="hz-pop__panel hz-notify"><livewire:pages.common.notifications /></div>
    </div>
    @if (Route::has('laraguppy.messenger'))
        <a href="{{ route('laraguppy.messenger') }}" class="hz-iconbtn" aria-label="{{ __('sidebar.messages') }}">
            <x-panel.icon name="chat" />
            <span class="hz-badge-dot" data-hz-badge="messages" @if (!$msgs) hidden @endif>{{ $msgs }}</span>
        </a>
    @endif
@endhasanyrole

<div class="hz-pop">
    <button type="button" class="hz-avatarbtn" data-hz-pop-toggle aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Menú de cuenta') }}">
        <span class="hz-avatar">@include('components.panel.avatar-img', ['user' => $user, 'px' => 34])</span>
        <x-panel.icon name="chevron-down" size="14" style="margin-right:6px;color:var(--hz-muted)" />
    </button>
    <div class="hz-pop__panel" role="menu">
        <div class="hz-menu__who">
            <span class="hz-avatar">@include('components.panel.avatar-img', ['user' => $user, 'px' => 36])</span>
            <div><strong>{{ $user?->profile?->full_name ?: $user?->email }}</strong><span>{{ $user?->email }}</span></div>
        </div>
        @hasanyrole('tutor|student')
            <a class="hz-menu__item" role="menuitem" href="{{ route($user->role . '.profile.personal-details') }}"><x-panel.icon name="user" /> {{ __('sidebar.profile_settings') }}</a>
        @endhasanyrole
        <a class="hz-menu__item" role="menuitem" href="{{ url('/') }}" target="_blank" rel="noopener"><x-panel.icon name="external" /> {{ __('general.view_site') }}</a>
        <hr class="hz-menu__sep">
        <a class="hz-menu__item hz-menu__item--danger" role="menuitem" href="{{ route('logout') }}"><x-panel.icon name="logout" /> {{ __('general.sign_out') }}</a>
    </div>
</div>
