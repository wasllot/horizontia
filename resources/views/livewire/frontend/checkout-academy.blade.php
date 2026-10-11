@php
    $couponsOn = \Nwidart\Modules\Facades\Module::has('kupondeal') && \Nwidart\Modules\Facades\Module::isEnabled('kupondeal');
    $enabledMethods = collect($available_payment_methods)->filter(fn ($m) => ($m['status'] ?? 'off') === 'on');
    $refundDays = config('academy.refund_days');
@endphp
<div class="ht-co" x-data="{ summary: false }">
    <header class="ht-co__bar">
        <strong class="ht-co__logo"><x-application-logo /></strong>
        <a href="{{ route('courses.search-courses') }}" class="ht-co__back">← Seguir explorando</a>
        <span class="ht-co__secure"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg> Pago seguro</span>
    </header>

    @if ($content->isEmpty())
        <div class="ht-co__empty">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/></svg>
            <h1>Tu carrito está vacío</h1>
            <p>Elige un curso y vuelve para pagar en un solo paso.</p>
            <a class="am-btn" href="{{ route('courses.search-courses') }}">Ver cursos</a>
        </div>
    @else
        <div class="ht-co__wrap">
            <section class="ht-co__main">
                <h1>Finalizar compra</h1>
                <p class="ht-co__lead">Solo necesitamos tus datos básicos: el acceso al curso se activa en cuanto se aprueba el pago.</p>

                @if (!empty($dateProblem))
                    <p class="ht-co__warn" role="alert" data-testid="date-problem"><strong>{{ html_entity_decode($dateProblem[2] ?? '') }}:</strong> {{ $dateProblem[1] }}</p>
                @endif
                <form id="ht-co-form" wire:submit="updateInfo" novalidate>
                    <fieldset class="ht-co__card">
                        <legend><span>1</span> Tus datos</legend>
                        <div class="ht-co__grid">
                            <div class="ht-field @error('form.firstName') has-error @enderror">
                                <label for="co-first">Nombre</label>
                                <input id="co-first" type="text" wire:model.blur="form.firstName" autocomplete="given-name" required>
                                @error('form.firstName') <small role="alert">{{ $message }}</small> @enderror
                            </div>
                            <div class="ht-field @error('form.lastName') has-error @enderror">
                                <label for="co-last">Apellido</label>
                                <input id="co-last" type="text" wire:model.blur="form.lastName" autocomplete="family-name" required>
                                @error('form.lastName') <small role="alert">{{ $message }}</small> @enderror
                            </div>
                            <div class="ht-field ht-field--full @error('form.email') has-error @enderror">
                                <label for="co-email">Correo electrónico</label>
                                <input id="co-email" type="email" inputmode="email" wire:model.blur="form.email" autocomplete="email" required>
                                <span class="ht-field__hint">Aquí te enviamos tu recibo y la confirmación.</span>
                                @error('form.email') <small role="alert">{{ $message }}</small> @enderror
                            </div>
                            @if (\App\Services\WhatsAppService::enabled())
                                <div class="ht-field ht-field--full @error('form.phone') has-error @enderror" data-testid="co-whatsapp">
                                    <label class="ht-check"><input type="checkbox" wire:model.live="form.whatsappOptin"> <span>Quiero recibir avisos por WhatsApp (confirmación de compra y recordatorios de mis sesiones)</span></label>
                                    @if ($form->whatsappOptin)
                                        <label for="co-phone">Tu número de WhatsApp</label>
                                        <input id="co-phone" type="tel" inputmode="tel" wire:model.blur="form.phone" autocomplete="tel" placeholder="55 1234 5678" maxlength="30">
                                        <span class="ht-field__hint">Con lada; si son 10 dígitos asumimos México (+52). Puedes dejar de recibirlos cuando quieras.</span>
                                    @endif
                                    @error('form.phone') <small role="alert">{{ $message }}</small> @enderror
                                </div>
                            @endif
                        </div>
                    </fieldset>

                    <fieldset class="ht-co__card">
                        <legend><span>2</span> Método de pago</legend>
                        @forelse ($enabledMethods as $method => $m)
                            <label class="ht-pay {{ $form->paymentMethod === $method ? 'is-on' : '' }}" wire:key="pm-{{ $method }}">
                                <input type="radio" name="payment" value="{{ $method }}" wire:model.live="form.paymentMethod" @checked($form->paymentMethod === $method)>
                                <span class="ht-pay__body">
                                    <strong>{{ __('settings.' . $method . '_title') }}</strong>
                                    @if ($method === 'mercadopago')<small>Tarjeta de crédito o débito, efectivo o transferencia</small>@endif
                                </span>
                                <img src="{{ asset('images/payment_methods/' . $method . '.png') }}" alt="" loading="lazy" height="22">
                            </label>
                        @empty
                            <p class="ht-co__warn">Por ahora no hay métodos de pago disponibles. Inténtalo más tarde o <a href="{{ route('contacto') }}">contáctanos</a>.</p>
                        @endforelse
                        @error('form.paymentMethod') <small class="ht-co__err" role="alert">{{ $message }}</small> @enderror
                    </fieldset>

                    <p class="ht-co__legal">Al pagar aceptas los <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Términos y condiciones</a> y el <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Aviso de privacidad</a>.</p>
                </form>
            </section>

            <aside class="ht-co__summary" aria-label="Resumen del pedido">
                <button type="button" class="ht-co__toggle" @click="summary = !summary" :aria-expanded="summary">
                    <span>Resumen del pedido <em>({{ $content->count() }})</em></span>
                    <strong>{!! formatAmount($totalAmount, true) !!}</strong>
                </button>
                <div class="ht-co__panel" :class="{ 'is-open': summary }">
                    <h2>Resumen del pedido</h2>
                    <ul class="ht-co__items">
                        @foreach ($content as $item)
                            <li wire:key="item-{{ $item['cartable_id'] }}-{{ md5($item['cartable_type']) }}">
                                @if (!empty($item['options']['image']))
                                    <img src="{{ mediaThumb($item['options']['image'], 160) }}" alt="" width="64" height="36" loading="lazy">
                                @else
                                    <span class="ht-thumb-ph" aria-hidden="true">{{ mb_strtoupper(mb_substr($item['name'], 0, 1)) }}</span>
                                @endif
                                <div>
                                    <strong>{{ html_entity_decode($item['name']) }}</strong>
                                    @if (!empty($item['options']['bundle_title']))<small class="ht-co__date ht-co__bundle" data-testid="co-bundle">Paquete: {{ $item['options']['bundle_title'] }}</small>@endif
                                    @if (!empty($item['options']['session_label']))<small class="ht-co__date" data-testid="co-date">{{ $item['options']['session_label'] }}</small>@endif
                                    <button type="button" class="ht-link" wire:click="removeCart({{ $item['cartable_id'] }}, @js($item['cartable_type']))" wire:loading.attr="disabled" wire:target="removeCart">Quitar</button>
                                </div>
                                <span class="ht-co__price">
                                    @if ($couponsOn && !empty($item['discounted_total']))<s>{!! formatAmount($item['price'], true) !!}</s> {!! $item['discounted_total'] !!}
                                    @else {!! formatAmount($item['price'], true) !!} @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    @php $appliedCoupon = \App\Facades\Cart::appliedCoupon(); @endphp
                    @if ($appliedCoupon)
                        <p class="ht-co__applied" data-testid="co-coupon-applied"><span>Código <strong>{{ $appliedCoupon['coupon']->code }}</strong> aplicado</span> <button type="button" class="ht-link" wire:click="removeCoupon" wire:loading.attr="disabled" wire:target="removeCoupon">Quitar</button></p>
                    @else
                        <form class="ht-co__coupon" wire:submit="applyCoupon" data-testid="co-coupon-form">
                            <input type="text" wire:model="coupon" placeholder="Código de descuento" aria-label="Código de descuento" maxlength="40" autocomplete="off">
                            <button type="submit" class="ht-btn-ghost" wire:loading.attr="disabled" wire:target="applyCoupon">Aplicar</button>
                        </form>
                    @endif
                    <small class="ht-co__err" role="alert" aria-live="polite">{{ $errors->first('coupon') }}</small>

                    <dl class="ht-co__totals">
                        <div><dt>Subtotal</dt><dd>{!! formatAmount($subTotal, true) !!}</dd></div>
                        @if ($discount > 0)<div class="is-discount"><dt>Descuento</dt><dd>-{!! formatAmount($discount, true) !!}</dd></div>@endif
                        <div class="is-total"><dt>Total</dt><dd>{!! formatAmount($totalAmount, true) !!} <small>{{ setting('_general.currency') }}</small></dd></div>
                    </dl>

                    <button type="submit" form="ht-co-form" class="ht-co__pay" wire:loading.attr="disabled" wire:target="updateInfo" @disabled($enabledMethods->isEmpty())>
                        <span wire:loading.remove wire:target="updateInfo">Pagar {!! formatAmount($payAmount, true) !!}</span>
                        <span wire:loading wire:target="updateInfo">Procesando…</span>
                    </button>

                    <ul class="ht-co__trust">
                        <li><span aria-hidden="true">✓</span> Pago cifrado{{ $form->paymentMethod ? ' con ' . __('settings.' . $form->paymentMethod . '_title') : '' }}</li>
                        <li><span aria-hidden="true">✓</span> Garantía de reembolso de {{ $refundDays }} días</li>
                        <li><span aria-hidden="true">✓</span> Acceso inmediato al aprobarse el pago</li>
                    </ul>
                </div>
            </aside>
        </div>

        <div class="ht-co__sticky" aria-hidden="false">
            <div><small>Total</small><strong>{!! formatAmount($payAmount, true) !!}</strong></div>
            <button type="submit" form="ht-co-form" class="ht-co__pay" wire:loading.attr="disabled" wire:target="updateInfo" @disabled($enabledMethods->isEmpty())>
                <span wire:loading.remove wire:target="updateInfo">Pagar ahora</span>
                <span wire:loading wire:target="updateInfo">Procesando…</span>
            </button>
        </div>
    @endif
</div>
@if (session()->get('error'))
    <script>document.addEventListener('DOMContentLoaded', () => Livewire.dispatch('showAlertMessage', { type: 'error', message: @js(session()->get('error')) }));</script>
@endif
