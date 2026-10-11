@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/courses/css/main.css') }}">
@endpush

<div class="cr-course-box" wire:init="loadData" wire:key="@this">
    <div class="cr-content-box">
        <h2>{{ __('courses::courses.add_pricing') }}</h2>
        <p>Define el precio del curso. Puedes ofrecerlo gratis o con un precio y descuento opcionales.</p>
    </div>

    <form class="am-themeform" onsubmit="return false;">
        <fieldset>
            <div class="cr-pricing-cols">
                <div class="cr-col">
            {{-- ── Free / Paid selector ──────────────────────────── --}}
            <div class="cr-pricing-mode">
                {{-- Paid option --}}
                <div class="cr-mode-card {{ !$isFree ? 'is-active' : '' }}" wire:click="toggleIsFree" style="{{ $isFree ? '' : 'pointer-events:none;' }}">
                    <div class="cr-mode-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="#5a6480" stroke-width="2"/>
                            <path d="M12 7v10M9 9.5C9 8.12 10.34 7 12 7s3 1.12 3 2.5-1.34 2.5-3 2.5-3 1.12-3 2.5S10.34 17 12 17s3-1.12 3-2.5" stroke="#5a6480" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="cr-mode-text">
                        <h4>Curso de pago</h4>
                        <p>Establece un precio y recibe ingresos por cada inscripción</p>
                    </div>
                    <div class="cr-mode-check"></div>
                </div>
                {{-- Free option --}}
                <div class="cr-mode-card {{ $isFree ? 'is-active' : '' }}" wire:click="toggleIsFree" style="{{ !$isFree ? '' : 'pointer-events:none;' }}">
                    <div class="cr-mode-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z" stroke="#5a6480" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="cr-mode-text">
                        <h4>Curso gratuito</h4>
                        <p>Llega a más estudiantes sin barreras de entrada</p>
                    </div>
                    <div class="cr-mode-check"></div>
                </div>
            </div>

                    @unless ($isFree)
                {{-- ── Price input ───────────────────────────────── --}}
                <div class="cr-price-block">
                    <div class="cr-price-block-label">Precio base del curso</div>
                    <div class="cr-price-input-row">
                        <span class="cr-currency-badge">{{ getCurrencySymbol() }}</span>
                        <input type="number" wire:model.live.debounce.500ms="price" id="price"
                               placeholder="0.00" step="0.01" min="0">
                    </div>
                    <p class="cr-price-hint">Ingresa el precio sin descuento. Puedes agregar un descuento en el paso siguiente.</p>
                </div>
                <x-input-error field_name='price' />

                    @endunless
                </div>
                <div class="cr-col">
                    @if ($isFree)
                <div class="cr-free-banner">
                    <div class="cr-free-badge">GRATIS</div>
                    <h3>Este curso es gratuito</h3>
                    <p>Los estudiantes podrán inscribirse sin costo. Cambia a "Curso de pago" para establecer un precio.</p>
                </div>

                    @else
                {{-- ── Discount toggle ───────────────────────────── --}}
                <div class="cr-discount-toggle-row">
                    <div class="cr-dt-text">
                        <h4>{{ __('courses::courses.allow_discount') }}</h4>
                        <p>Ofrece un descuento para atraer más estudiantes</p>
                    </div>
                    <input type="checkbox" wire:click='toggleDiscountAllowed'
                           id="allow-discount" class="cr-toggle"
                           wire:model="discountAllowed">
                </div>

                @if ($discountAllowed)
                    {{-- ── Discount cards ────────────────────────── --}}
                    <div class="cr-discount-grid">
                        @foreach ($discounts as $pct)
                            @php
                                $saves = number_format(($pct / 100) * (float) $price, 2);
                                $final = number_format((1 - $pct / 100) * (float) $price, 2);
                            @endphp
                            <div class="cr-discount-card {{ $discount == $pct ? 'is-active' : '' }}"
                                 wire:click="updateDiscount({{ $pct }})">
                                <div class="cr-dc-check"></div>
                                <div class="cr-dc-pct">{{ $pct }}%</div>
                                <div class="cr-dc-saves">Ahorras {{ getCurrencySymbol() }}{{ $saves }}</div>
                                <div class="cr-dc-final">{{ getCurrencySymbol() }}{{ $final }}</div>
                            </div>
                        @endforeach

                        {{-- Custom % --}}
                        @php
                            $customSaves = $customDiscount ? number_format(($customDiscount / 100) * (float) $price, 2) : '0.00';
                            $customFinal = $customDiscount ? number_format((1 - $customDiscount / 100) * (float) $price, 2) : number_format((float) $price, 2);
                        @endphp
                        <div class="cr-discount-card cr-custom-card {{ $discount == $customDiscount && $customDiscount ? 'is-active' : '' }}"
                             wire:click="updateCustomDiscount">
                            <div class="cr-dc-check"></div>
                            <div class="cr-dc-pct" style="font-size:13px;margin-bottom:4px;">Personalizado</div>
                            <div class="cr-custom-input-wrap" @click.stop>
                                <input type="text" wire:model.live.debounce.500ms='customDiscount'
                                       wire:click="updateCustomDiscount"
                                       placeholder="33">
                                <span>%</span>
                            </div>
                            @if ($customDiscount)
                                <div class="cr-dc-saves" style="margin-top:6px;">Ahorras {{ getCurrencySymbol() }}{{ $customSaves }}</div>
                                <div class="cr-dc-final">{{ getCurrencySymbol() }}{{ $customFinal }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Final price summary --}}
                    @if (!empty($final_price))
                        <div class="cr-final-price-banner">
                            <div>
                                <div class="cr-fp-label">Precio final de compra</div>
                                <div class="cr-fp-savings">
                                    @if ($discount)
                                        Descuento del {{ $discount }}% aplicado · precio original {{ getCurrencySymbol() }}{{ number_format((float) $price, 2) }}
                                    @endif
                                </div>
                            </div>
                            <div class="cr-fp-amount">{{ formatAmount($final_price) }}</div>
                        </div>
                    @endif
                @endif
                    @endif
                </div>
            </div>

        </fieldset>

        <div class="am-themeform_footer">
            <a href="{{ courseEditUrl(\Modules\Courses\Models\Course::find($courseId)?->isOnsite() ? 'sessions' : 'media', $courseId) }}" class="am-white-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M10.5 4.5L6 9L10.5 13.5" stroke="#585858" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ __('courses::courses.back') }}
            </a>
            <button wire:click="savePricing" class="am-btn" wire:loading.remove wire:target="savePricing">{{ __('courses::courses.save_continue') }}</button>
            <button class="am-btn am-btn_disable" wire:loading.flex wire:target="savePricing">{{ __('courses::courses.save_continue') }}</button>
        </div>
    </form>
</div>
