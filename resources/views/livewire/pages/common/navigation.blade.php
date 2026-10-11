<div class="hz-sidebar-host">
    <aside class="hz-sidebar" id="hz-sidebar" aria-label="{{ __('Navegación principal') }}">
        <div class="hz-sidebar__brand">
            <a href="{{ url('/') }}" class="hz-brand" aria-label="{{ setting('_general.site_name') ?: config('app.name') }}">
                <x-application-logo-img variation="white" />
            </a>
            <button type="button" class="hz-iconbtn hz-sidebar__collapse" data-hz-collapse aria-pressed="false" aria-label="Contraer menú lateral">
                <x-panel.icon name="collapse" />
            </button>
        </div>

        <nav class="hz-nav" aria-label="{{ __('Menú principal') }}">
            <ul class="hz-nav__list">
                @php $lastGroup = null; @endphp
                @foreach ($menuItems as $item)
                    @continue(!in_array($role, $item['accessibility']))
                    @if (($item['group'] ?? null) !== $lastGroup)
                        @php $lastGroup = $item['group'] ?? null; @endphp
                        <li class="hz-nav__heading" role="presentation">{{ $lastGroup === 'learning' ? ($role === 'tutor' ? __('sidebar.group_teaching') : __('sidebar.group_learning')) : __('sidebar.group_account') }}</li>
                    @endif
                    @php($active = in_array($activeRoute, $item['onActiveRoute']))
                    <li @class(['hz-nav__item', 'is-active' => $active, 'is-open' => $active && !empty($item['children'])])>
                        <a class="hz-nav__link" href="{{ !empty($item['route']) ? route($item['route']) : '#' }}" data-tip="{{ $item['title'] }}"
                           @if (empty($item['disableNavigate'])) wire:navigate.remove @endif @if ($active) aria-current="page" @endif>
                            <span class="hz-nav__icon">{!! $item['icon'] !!}</span>
                            <span class="hz-nav__label">{{ $item['title'] }}</span>
                        </a>
                        @if (!empty($item['children']))
                            <div class="hz-nav__subwrap">
                                <ul class="hz-nav__sub">
                                    @foreach ($item['children'] as $child)
                                        <li @class(['hz-nav__item', 'is-active' => Route::currentRouteName() == $child['route']])>
                                            <a class="hz-nav__link" href="{{ route($child['route']) }}" @if (empty($child['disableNavigate'])) wire:navigate.remove @endif>
                                                <span class="hz-nav__label">{{ $child['title'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="hz-sidebar__foot">
            @unless (isPrivateAcademy())
                <div class="hz-wallet">
                    <strong>{!! formatAmount($balance, true) !!}</strong>
                    <span>{{ __('general.wallet_balance') }}</span>
                    <button type="button" class="hz-btn hz-btn--primary hz-btn--sm" wire:click="openModel">{{ __('general.withdraw_now') }}</button>
                </div>
            @endunless
            <button type="button" class="hz-nav__link hz-nav__link--logout" wire:click="logout" data-tip="{{ __('general.sign_out') }}">
                <span class="hz-nav__icon"><x-panel.icon name="logout" /></span>
                <span class="hz-nav__label">{{ __('general.sign_out') }}</span>
            </button>
        </div>
    </aside>
    <div wire:ignore.self class="modal fade am-setuppayoneerpopup" id="amount" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="am-modal-header">
                    <h2>{{ __('tutor.setup_payoneer_account',['payout_method' => ucfirst($userPayoutMethod?->payout_method)]) }}</h2>
                    <span data-bs-dismiss="modal" class="am-closepopup">
                        <i class="am-icon-multiply-01"></i>
                    </span>
                </div>
                <div class="am-modal-body">
                    <figure class="am-setup_img">
                        <img src="{{ asset('images/account-info-bg.png') }}" alt="img description">
                        <figcaption class="am-setup_img_content">
                            <span>{{ ucfirst($userPayoutMethod?->payout_method) }}</span>
                            <figure class="am-setup_img_icon">
                                @if ($userPayoutMethod?->payout_method == 'paypal')
                                    <img src="{{ asset('images/paypal.svg') }}" alt="img description">
                                @elseif ($userPayoutMethod?->payout_method == 'payoneer')
                                    <img src="{{ asset('images/payoneer.svg') }}" alt="img description">
                                @endif
                            </figure>
                        </figcaption>
                    </figure>
                    <form class="am-themeform">
                        <fieldset>
                            <div @class(['form-group', 'am-invalid' => $errors->has('amount')])>
                                <x-input-label for="amount" class="am-important" :value="__('tutor.withdraw_amount')" />
                                <div class="am-maxamount">
                                    <x-text-input id="amount" wire:model="amount" name="amount" placeholder="{{ __('tutor.withdraw_amount') }}" type="text" />
                                    <x-input-label for="maxamount" :value="__('tutor.max_limit')" />
                                    <span>{{ number_format($balance, 2) }}</span>
                                </div>
                                <x-input-error field_name="amount" />
                            </div>
                            <div class="form-group am-form-btns">
                                <button wire:target="addWithdarwals" wire:loading.class="am-btn_disable" wire:click="addWithdarwals" type="button" class="am-btn">{{ __('tutor.save_update') }}</button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
