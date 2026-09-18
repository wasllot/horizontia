@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/courses/css/main.css') }}">
<style>
/* ── Pricing form ─────────────────────────────────────────────────── */

/* Free / Paid toggle cards */
.cr-pricing-mode {
    display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 28px;
}
.cr-mode-card {
    border: 2px solid #e4e9f0; border-radius: 14px; padding: 18px 20px;
    cursor: pointer; transition: border-color .2s, background .2s;
    display: flex; align-items: center; gap: 14px; background: #fff;
    user-select: none;
}
.cr-mode-card:hover { border-color: #aab3c5; }
.cr-mode-card.is-active { border-color: #14213d; background: #f4f6fb; }
.cr-mode-card .cr-mode-icon {
    width: 44px; height: 44px; border-radius: 11px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: #f0f3fa; transition: background .2s;
}
.cr-mode-card.is-active .cr-mode-icon { background: #14213d; }
.cr-mode-card.is-active .cr-mode-icon svg path,
.cr-mode-card.is-active .cr-mode-icon svg circle,
.cr-mode-card.is-active .cr-mode-icon svg polyline { stroke: #fed304; }
.cr-mode-card .cr-mode-text h4 { margin: 0 0 3px; font-size: 14px; font-weight: 700; color: #14213d; }
.cr-mode-card .cr-mode-text p  { margin: 0; font-size: 12px; color: #8a95b0; line-height: 1.4; }
.cr-mode-card .cr-mode-check {
    margin-left: auto; width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
    border: 2px solid #d5dce8; display: flex; align-items: center; justify-content: center;
    transition: border-color .2s, background .2s;
}
.cr-mode-card.is-active .cr-mode-check {
    background: #14213d; border-color: #14213d;
}
.cr-mode-card.is-active .cr-mode-check::after {
    content: ''; width: 6px; height: 6px; border-radius: 50%; background: #fed304;
}

/* Price input block */
.cr-price-block {
    background: linear-gradient(135deg, #14213d 0%, #1e2f55 100%);
    border-radius: 16px; padding: 24px 28px; margin-bottom: 24px; position: relative;
}
.cr-price-block-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
    color: #aab3c5; margin-bottom: 12px;
}
.cr-price-input-row {
    display: flex; align-items: center; gap: 0;
}
.cr-currency-badge {
    background: rgba(254,211,4,.15); border: 1px solid rgba(254,211,4,.35);
    border-radius: 10px 0 0 10px; padding: 12px 16px;
    font-size: 18px; font-weight: 800; color: #fed304; flex-shrink: 0;
}
.cr-price-input-row input[type="number"] {
    flex: 1; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-left: none;
    border-radius: 0 10px 10px 0; padding: 12px 16px;
    font-size: 22px; font-weight: 800; color: #fff; outline: none;
    transition: border-color .2s;
}
.cr-price-input-row input[type="number"]:focus { border-color: #fed304; }
.cr-price-input-row input[type="number"]::placeholder { color: rgba(255,255,255,.3); }
.cr-price-hint { font-size: 12px; color: rgba(255,255,255,.45); margin-top: 8px; }

/* Discount toggle row */
.cr-discount-toggle-row {
    display: flex; align-items: center; justify-content: space-between;
    background: #f8f9fc; border: 1px solid #e4e9f0;
    border-radius: 12px; padding: 16px 20px; margin-bottom: 20px;
}
.cr-discount-toggle-row .cr-dt-text h4 { margin: 0 0 2px; font-size: 14px; font-weight: 700; color: #14213d; }
.cr-discount-toggle-row .cr-dt-text p  { margin: 0; font-size: 12px; color: #8a95b0; }

/* Discount cards grid */
.cr-discount-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px; margin-bottom: 16px;
}
.cr-discount-card {
    border: 2px solid #e4e9f0; border-radius: 14px; padding: 16px 14px;
    cursor: pointer; background: #fff; transition: border-color .2s, background .2s;
    text-align: center; position: relative;
}
.cr-discount-card:hover { border-color: #aab3c5; }
.cr-discount-card.is-active { border-color: #fed304; background: #fffef5; }
.cr-discount-card .cr-dc-pct {
    font-size: 20px; font-weight: 800; color: #14213d; line-height: 1;
    margin-bottom: 6px;
}
.cr-discount-card.is-active .cr-dc-pct { color: #d4a800; }
.cr-discount-card .cr-dc-saves {
    font-size: 11px; color: #8a95b0; margin-bottom: 4px;
}
.cr-discount-card .cr-dc-final {
    font-size: 13px; font-weight: 700; color: #065f46;
}
.cr-discount-card .cr-dc-check {
    position: absolute; top: 8px; right: 8px;
    width: 18px; height: 18px; border-radius: 50%;
    border: 2px solid #d5dce8;
    display: flex; align-items: center; justify-content: center;
}
.cr-discount-card.is-active .cr-dc-check { background: #fed304; border-color: #fed304; }
.cr-discount-card.is-active .cr-dc-check::after {
    content: '✓'; font-size: 10px; color: #14213d; font-weight: 900;
}

/* Custom discount card */
.cr-discount-card.cr-custom-card .cr-custom-input-wrap {
    display: flex; align-items: center; justify-content: center; gap: 4px; margin-top: 6px;
}
.cr-discount-card.cr-custom-card input[type="text"] {
    width: 52px; text-align: center; font-size: 14px; font-weight: 700;
    border: 1px solid #d5dce8; border-radius: 6px; padding: 4px 6px;
    color: #14213d; outline: none; background: #f8f9fc;
}
.cr-discount-card.cr-custom-card input[type="text"]:focus { border-color: #fed304; }
.cr-discount-card.cr-custom-card span { font-size: 14px; font-weight: 700; color: #8a95b0; }

/* Final price summary */
.cr-final-price-banner {
    background: linear-gradient(135deg, #065f46, #047857);
    border-radius: 12px; padding: 16px 20px;
    display: flex; align-items: center; justify-content: space-between;
}
.cr-final-price-banner .cr-fp-label { font-size: 13px; font-weight: 600; color: rgba(255,255,255,.8); }
.cr-final-price-banner .cr-fp-amount { font-size: 24px; font-weight: 900; color: #fff; }
.cr-final-price-banner .cr-fp-savings { font-size: 12px; color: rgba(255,255,255,.6); }

/* Free course banner */
.cr-free-banner {
    background: linear-gradient(135deg, #14213d, #1e2f55);
    border-radius: 14px; padding: 28px 24px; text-align: center;
}
.cr-free-banner h3 { color: #fff; font-size: 18px; font-weight: 800; margin: 0 0 8px; }
.cr-free-banner p  { color: #aab3c5; font-size: 13px; margin: 0; }
.cr-free-banner .cr-free-badge {
    display: inline-block; background: #fed304; color: #14213d;
    font-size: 22px; font-weight: 900; padding: 8px 28px;
    border-radius: 10px; margin-bottom: 12px;
}
</style>
@endpush

<div class="cr-course-box" wire:init="loadData" wire:key="@this">
    <div class="cr-content-box">
        <h2>{{ __('courses::courses.add_pricing') }}</h2>
        <p>Define el precio del curso. Puedes ofrecerlo gratis o con un precio y descuento opcionales.</p>
    </div>

    <form class="am-themeform" onsubmit="return false;">
        <fieldset>
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

            @if ($isFree)
                {{-- Free course state --}}
                <div class="cr-free-banner">
                    <div class="cr-free-badge">GRATIS</div>
                    <h3>Este curso es gratuito</h3>
                    <p>Los estudiantes podrán inscribirse sin costo. Cambia a "Curso de pago" para establecer un precio.</p>
                </div>

            @else
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

        </fieldset>

        <div class="am-themeform_footer">
            <a href="{{ route('courses.tutor.edit-course', ['tab' => 'media', 'id' => $courseId]) }}" class="am-white-btn">
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
