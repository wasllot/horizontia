<div class="am-accountwrap" wire:init="loadData">
    @slot('title')
        {{ __('general.dashboard') }}
    @endslot
    @if($isLoading)
        @include('skeletons.manage-account')
    @else
    <div class="am-section-load" wire:loading wire:target="refresh">
        @include('skeletons.manage-account')
    </div>
    <div>
        <div wire:loading.remove wire:target="refresh">
            @include('livewire.pages.tutor.manage-account.wallet-detail')
            @include('livewire.pages.tutor.manage-account.earning-graph')
            <div style="background:#fff; border-radius:20px; box-shadow:0 2px 20px rgba(0,0,0,.07); border:1px solid #f0f0f0; padding:28px; margin-top:24px; margin-bottom:24px;">
                
                {{-- Header --}}
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:28px;">
                    <div style="background:linear-gradient(135deg,#667eea,#764ba2); border-radius:12px; width:42px; height:42px; display:flex; align-items:center; justify-content:center;">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div>
                        <h2 style="font-size:1.1rem; font-weight:700; color:#1a1a2e; margin:0 0 2px;">{{ __('tutor.setup_payouts_methods') }}</h2>
                        <p style="font-size:.8rem; color:#aaa; margin:0;">Selecciona tu método preferido para recibir pagos</p>
                    </div>
                </div>

                <div x-data="{current_method:@entangle('form.current_method')}" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px;">
                    @php
                    $payout_method = [
                    'paypal' => [
                    'id' => 'PayPal',
                    'title' => __('tutor.payPal_balance'),
                    'image' => 'images/paypal.svg',
                    'price' => $withdrawalsType['paypal']['total_amount'] ?? 0,
                    'status' => isset($payoutStatus['paypal'])??[],
                    'remove_action' => isset($payoutStatus['paypal']) ? 'deletepopup' : 'setuppayoneerpopup',
                    'btnTitle' => isset($payoutStatus['paypal']) ? __('tutor.remove_account') : __('tutor.add_account'),
                    'accent' => '#003087',
                    'bg' => 'linear-gradient(135deg,#e8f0fe,#f0f4ff)',
                    ],
                    'payoneer' => [
                    'id' => 'payoneer',
                    'title' => __('tutor.payoneer_balance'),
                    'image' => 'images/payoneer.svg',
                    'price' => $withdrawalsType['payoneer']['total_amount'] ?? 0,
                    'status' => isset($payoutStatus['payoneer'])??[],
                    'remove_action' => isset($payoutStatus['payoneer']) ? 'deletepopup' : 'setuppayoneerpopup',
                    'btnTitle' => isset($payoutStatus['payoneer']) ? __('tutor.remove_account') : __('tutor.add_account'),
                    'accent' => '#ff4800',
                    'bg' => 'linear-gradient(135deg,#fff1ec,#fff8f5)',
                    ],
                    'bank' => [
                    'id' => 'bank',
                    'title' => __('tutor.bank_transfer'),
                    'image' => 'images/bank.svg',
                    'price' => $withdrawalsType['bank']['total_amount'] ?? 0,
                    'status' => isset($payoutStatus['bank'])??[],
                    'remove_action' => isset($payoutStatus['bank']) ? 'deletepopup' : 'setupaccountpopup',
                    'btnTitle' => isset($payoutStatus['bank']) ? __('tutor.remove_account') : __('tutor.add_account'),
                    'accent' => '#1a7f50',
                    'bg' => 'linear-gradient(135deg,#edfaf4,#f0fdf7)',
                    ],
                    ];
                    @endphp

                    @foreach ($payout_method as $method => $item)
                    <div wire:key="{{ $method }}-{{ time() }}"
                        style="background:{{ $item['bg'] }}; border-radius:16px; padding:22px; border:1.5px solid rgba(0,0,0,.06); transition:transform .2s, box-shadow .2s; display:flex; flex-direction:column; gap:12px;"
                        onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,.10)'"
                        onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='none'">

                        {{-- Logo --}}
                        <div style="display:flex; align-items:center; justify-content:space-between;">
                            <figure style="margin:0; width:56px; height:36px; display:flex; align-items:center;">
                                <img src="{{ asset($item['image']) }}" alt="{{ $item['id'] }}" style="max-width:100%; max-height:36px; object-fit:contain;">
                            </figure>
                            @if($item['status'])
                                <span style="background:#e6f9f0; color:#1a7f50; font-size:.7rem; font-weight:700; padding:3px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:.05em;">Activo</span>
                            @else
                                <span style="background:#f0f0f0; color:#999; font-size:.7rem; font-weight:700; padding:3px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:.05em;">Sin config.</span>
                            @endif
                        </div>

                        {{-- Amount --}}
                        <div>
                            @if($item['price'])
                                <div style="font-size:1.4rem; font-weight:800; color:#1a1a2e;">{!! formatAmount($item['price'], true) !!}</div>
                            @endif
                            <div style="font-size:.85rem; font-weight:600; color:#555;">{{ $item['title'] }}</div>
                            @if(!$item['status'])
                                <div style="font-size:.78rem; color:#bbb; margin-top:2px;">{{ __('tutor.no_account_added_yet') }}</div>
                            @endif
                        </div>

                        {{-- Radio (si activo) --}}
                        @if($item['status'])
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input wire:click="updateStatus('{{ $method }}')"
                                {{ $payoutStatus[$method]['status'] == 'active' ? 'checked' : '' }}
                                type="radio" id="default_{{ $method }}" name="method"
                                style="accent-color:{{ $item['accent'] }}; width:16px; height:16px;">
                            <label for="default_{{ $method }}" style="font-size:.8rem; color:#555; cursor:pointer; margin:0;">{{ __('tutor.make_default_method') }}</label>
                        </div>
                        @endif

                        {{-- Action button --}}
                        <div style="margin-top:auto; padding-top:4px;">
                            @if($item['status'])
                                <a href="javascript:void(0);"
                                    @click="current_method = @js($method); $wire.dispatch('toggleModel', { id: '{{ $item['remove_action'] }}', action: 'show' });"
                                    style="display:block; text-align:center; padding:9px 0; border-radius:10px; font-size:.82rem; font-weight:600; color:#e53e3e; background:rgba(229,62,62,.08); text-decoration:none; transition:background .2s;"
                                    onmouseenter="this.style.background='rgba(229,62,62,.15)'"
                                    onmouseleave="this.style.background='rgba(229,62,62,.08)'">
                                    {{ $item['btnTitle'] }}
                                </a>
                            @else
                                <a href="javascript:void(0);" wire:click="openPayout('{{ $method }}', '{{ $item['remove_action'] }}')"
                                    style="display:block; text-align:center; padding:9px 0; border-radius:10px; font-size:.82rem; font-weight:600; color:#fff; background:{{ $item['accent'] }}; text-decoration:none; transition:opacity .2s;"
                                    onmouseenter="this.style.opacity='.85'"
                                    onmouseleave="this.style.opacity='1'">
                                    {{ $item['btnTitle'] }}
                                </a>
                            @endif
                        </div>

                    </div>
                    @endforeach
                </div>

                <p style="font-size:.78rem; color:#bbb; margin-top:20px; border-top:1px solid #f0f0f0; padding-top:16px;">
                    {{ __('tutor.detail') }} <a href="{{ url('terms-condition') }}" style="color:#667eea; text-decoration:none;">{{ __('tutor.transfer_policy') }}</a>
                </p>
            </div>

        </div>
        <!-- setup account popup modal -->
        <div wire:ignore.self class="modal fade am-setupaccountpopup" id="setupaccountpopup" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="am-modal-header">
                        <h2>{{ __('tutor.setup_bank_account') }}</h2>
                        <span data-bs-dismiss="modal" class="am-closepopup">
                            <i class="am-icon-multiply-01"></i>
                        </span>
                    </div>
                    <div class="am-modal-body">
                        <form class="am-themeform">
                            <fieldset>
                                <div @class(['form-group', 'am-invalid'=> $errors->has('form.title')])>
                                    <x-input-label for="accounttitle" class="am-important"
                                        :value="__('tutor.bank_account_title')" />
                                    <x-text-input wire:model="form.title" id="accounttitle" name="accounttitle"
                                        placeholder="{{ __('tutor.enter_bank_account_title') }}" type="text" />
                                    <x-input-error field_name="form.title" />
                                </div>
                                <div @class(['form-group', 'am-invalid'=> $errors->has('form.accountNumber')])>
                                    <x-input-label for="account" class="am-important"
                                        :value="__('tutor.bank_account_number')" />
                                    <x-text-input wire:model="form.accountNumber" id="account" name="account"
                                        placeholder="{{ __('tutor.enter_bank_account_number') }}" type="text" />
                                    <x-input-error field_name="form.accountNumber" />
                                </div>
                                <div @class(['form-group', 'am-invalid'=> $errors->has('form.bankName')])>
                                    <x-input-label for="bankname" :value="__('tutor.bank_name')" class="am-important" />
                                    <x-text-input wire:model="form.bankName" id="bankname" name="bankname"
                                        placeholder="{{ __('tutor.enter_bank_name') }}" type="text" />
                                    <x-input-error field_name="form.bankName" />
                                </div>
                                <div @class(['form-group', 'am-invalid'=> $errors->has('form.bankRoutingNumber')])>
                                    <x-input-label for="routingnum" :value="__('tutor.bank_routing_number')" />
                                    <x-text-input wire:model="form.bankRoutingNumber" id="routingnum" name="routingnum"
                                        placeholder="{{ __('tutor.enter_bank_routing_number') }}" type="text" />
                                    <x-input-error field_name="form.bankRoutingNumber" />
                                </div>
                                <div @class(['form-group', 'am-invalid'=> $errors->has('form.bankIban')])>
                                    <x-input-label for="bankiban" :value="__('tutor.bank_iban')"/>
                                    <x-text-input wire:model="form.bankIban" id="bankiban" name="bankiban"
                                        placeholder="{{ __('tutor.enter_bank_iban') }}" type="text" />
                                    <x-input-error field_name="form.bankIban" />
                                </div>
                                <div @class(['form-group', 'am-invalid'=> $errors->has('form.bankBtc')])>
                                    <x-input-label for="bankbic" :value="__('tutor.bank_bic_swift')" />
                                    <x-text-input wire:model="form.bankBtc" id="bankbic" name="bankbic"
                                        placeholder="{{ __('tutor.enter_bank_bic_swift') }}" type="text" />
                                    <x-input-error field_name="form.bankBtc" />
                                </div>
                                <div class="form-group am-form-btns">
                                    <button wire:target="updatePayout" wire:loading.class="am-btn_disable"
                                        wire:click="updatePayout" type="button" class="am-btn">{{
                                        __('tutor.save_update') }}</button>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- setup payoneer popup modal -->
        <div wire:ignore.self class="modal fade am-setuppayoneerpopup" id="setuppayoneerpopup"
            data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="am-modal-header">
                        <h2>{{ __('tutor.setup_account',['payout_method' => ucfirst($form?->current_method)]) }}</h2>
                        <span data-bs-dismiss="modal" class="am-closepopup">
                            <i class="am-icon-multiply-01"></i>
                        </span>
                    </div>
                    <div class="am-modal-body">
                        <form class="am-themeform">
                            <fieldset>
                                <div @class(['form-group', 'am-invalid'=> $errors->has('form.email')])>
                                    <x-input-label for="Email" class="am-important" :value="__('tutor.email_label')" />
                                    <x-text-input id="Email" wire:model="form.email" name="Email"
                                        placeholder="{{ __('tutor.enter_email') }}" type="text" />
                                    <x-input-error field_name="form.email" />
                                </div>
                                <div class="form-group am-form-btns">
                                    <button wire:target="updatePayout" wire:loading.class="am-btn_disable"
                                        wire:click="updatePayout" type="button" class="am-btn">{{
                                        __('tutor.save_update') }}</button>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete modal -->
        <div wire:ignore.self class="modal fade am-deletepopup" id="deletepopup" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="am-modal-body">
                        <span data-bs-dismiss="modal" class="am-closepopup">
                            <i class="am-icon-multiply-01"></i>
                        </span>
                        <div class="am-deletepopup_icon">
                            <span><i class="am-icon-trash-02"></i></span>
                        </div>
                        <div class="am-deletepopup_title">
                            <h3>{{ __('tutor.confirm_title') }}</h3>
                            <p>{{ __('tutor.confirm_message') }}</p>
                        </div>
                        <div class="am-deletepopup_btns">
                            <a href="javascript:void(0);"class="am-btn am-btnsmall" data-bs-dismiss="modal">{{ __('tutor.no_button') }}</a>
                            <a href="javascript:void(0);" wire:target="removePayout" wire:loading.class="am-btn_disable" wire:click="removePayout"
                                class="am-btn am-btn-del">{{ __('tutor.yes_button') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@push('styles')
@vite([
'public/css/flatpicker.css',
'public/css/flatpicker-month-year-plugin.css'
])
@endpush

@push('scripts')
<script defer src="{{ asset('js/flatpicker.js') }}"></script>
<script defer src="{{ asset('js/flatpicker-month-year-plugin.js') }}"></script>
<script defer src="{{ asset('js/chart.js')}}"></script>
<script type="text/javascript" data-navigate-once>
        var earningsChart;
        var component = '';
        document.addEventListener('livewire:navigated', function() {
                component = @this;
        },{ once: true });

        document.addEventListener('initChartJs', (event)=>{
            setTimeout(() => {
                initCalendarJs(event.detail.currentDate);
                renderChart(event.detail.data.earnings, event.detail.data.days);
            }, 500);
        })

        function initCalendarJs(defaultDate) {
            $("#calendar-month-year").flatpickr({
                defaultDate: defaultDate,
                disableMobile: true,
                plugins: [
                    new monthSelectPlugin({
                        shorthand: true, //defaults to false
                        dateFormat: "F, Y", //defaults to "F Y"
                    })
                ],
                onChange: function(selectedDates, dateStr, instance) {
                    @this.set('selectedDate', dateStr);
                }
            });
        }
        function renderChart(earnigns, labels) {
            let days = Object.values(labels).map(day => day.toString());
            var ctx = document.getElementById('am-themechart').getContext('2d');
            if (earningsChart) {
                earningsChart.destroy();
            }
            var gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(117, 79, 254, 0.30)');
            gradient.addColorStop(1, 'rgba(255, 255, 255, 0.00)');

            earningsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: days,
                    datasets: [{
                        label: 'Earning',
                        data: earnigns,
                        backgroundColor: gradient,
                        borderColor: '#754FFE',
                        tension : 0.5,
                        borderWidth: 1,
                        fill: true,
                        pointBackgroundColor: '#754FFE',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#754FFE'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x:{
                            grid:{
                                drawTicks:false,
                                // display:false,
                            },

                        },
                        y: {
                            beginAtZero: true,
                            grid:{
                                drawTicks:false,
                            },
                            border:{
                                display:false,
                                dash:[12,12]
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `$${context.formattedValue} Earning`;
                                }
                            }
                        }
                    }
                }
            });
        }
</script>
@endpush
