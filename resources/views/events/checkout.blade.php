@extends('events.layout', ['metaTitle' => 'Tus datos y pago · ' . $event->name, 'canonical' => $event->url('pago')])

@section('body')
@php
    $money = fn ($v) => '$' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $f = fn ($k, $d = '') => old($k, $d);
    $invoiceAsked = (bool) old('wants_invoice');
@endphp
<div class="ev-page"><div class="ev-wrap">
    @include('events.steps', ['step' => 2])
    <h1>Tus datos y pago</h1>
    <details class="ev-more ev-sumtop"><summary>Resumen: {{ $quantity }} {{ $quantity === 1 ? 'persona' : 'personas' }} · {{ $money($quote['total']) }} MXN</summary>
        <p style="margin:0">{{ collect($quote['lines'])->map(fn ($l) => $l['qty'] . ' × ' . $l['name'])->implode(' + ') }} · IVA incluido ({{ $money($quote['iva']) }}). <a href="{{ $event->url('carrito') }}">Cambiar paquetes</a></p>
    </details>
    @if (session('event_error'))<p class="ev-alert" role="alert">{{ session('event_error') }}</p>@endif
    @if ($errors->any())<p class="ev-alert" role="alert">Revisa los campos marcados para continuar.</p>@endif

    <div class="ev-layout">
        <form class="ev-form" method="post" action="{{ $event->url('pago') }}" novalidate id="evCheckout">
            @csrf
            <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Sitio web<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            <fieldset class="ev-card" style="display:grid;gap:16px;border:1px solid var(--ev-line)">
                <legend class="ev-hint" style="padding:0 8px">Quién compra</legend>
                <div class="ev-field"><label for="name">Nombre completo</label><input id="name" name="name" type="text" autocomplete="name" value="{{ $f('name') }}" required>@error('name')<p class="ev-err">{{ $message }}</p>@enderror</div>
                <div class="ev-field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" autocomplete="email" value="{{ $f('email') }}" required><small>Aquí te enviamos tu confirmación con la clave de acceso.</small>@error('email')<p class="ev-err">{{ $message }}</p>@enderror</div>
                <div class="ev-field"><label for="phone">Teléfono o WhatsApp</label><input id="phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" value="{{ $f('phone') }}" required>@error('phone')<p class="ev-err">{{ $message }}</p>@enderror</div>
            </fieldset>

            <fieldset class="ev-card" style="display:grid;gap:16px;border:1px solid var(--ev-line)">
                <legend class="ev-hint" style="padding:0 8px">Quiénes asisten</legend>
                <p class="ev-hint" style="margin:0">La pulsera y el diploma llevan el nombre de cada asistente.</p>
                @for ($i = 0; $i < $quantity; $i++)
                    <div class="ev-field"><label for="att{{ $i }}">Asistente {{ $i + 1 }}</label><input id="att{{ $i }}" name="attendees[{{ $i }}]" type="text" value="{{ $f('attendees.' . $i) }}" required data-first="{{ $i === 0 ? '1' : '0' }}">@error('attendees.' . $i)<p class="ev-err">{{ $message }}</p>@enderror</div>
                @endfor
                @error('attendees')<p class="ev-err">{{ $message }}</p>@enderror
                <details class="ev-more" @if ($errors->hasAny(['minors', 'has_telescope', 'heard_from']) || old('minors') || old('heard_from')) open @endif>
                    <summary>Datos opcionales (menores, telescopio, cómo te enteraste)</summary>
                    <div class="ev-form">
                <div class="ev-field"><label for="minors">¿Cuántos menores de {{ (int) (data_get($event->content, 'minors_age') ?: 6) }} años los acompañan? <small>(no pagan)</small></label><input id="minors" name="minors" type="number" min="0" max="20" value="{{ $f('minors', 0) }}"></div>
                    <div class="ev-field"><label for="has_telescope">¿Traes telescopio?</label>
                    <select id="has_telescope" name="has_telescope"><option value="">Prefiero no decir</option><option value="si" @selected($f('has_telescope') === 'si')>Sí</option><option value="no" @selected($f('has_telescope') === 'no')>No</option></select></div>
                    <div class="ev-field"><label for="heard_from">¿Cómo te enteraste de Astrofest?</label><input id="heard_from" name="heard_from" type="text" maxlength="120" value="{{ $f('heard_from') }}"></div>
                    </div>
                </details>
            </fieldset>

            <fieldset class="ev-card" style="display:grid;gap:14px;border:1px solid var(--ev-line)">
                <legend class="ev-hint" style="padding:0 8px">Factura (opcional)</legend>
                <label class="ev-check"><input type="checkbox" name="wants_invoice" value="1" id="wantsInvoice" @checked($invoiceAsked)> <span>Necesito factura</span></label>
                <div id="invoiceBox" style="display:{{ $invoiceAsked ? 'grid' : 'none' }};gap:14px">
                    <div class="ev-field"><label for="inv_rfc">RFC</label><input id="inv_rfc" name="invoice[rfc]" type="text" maxlength="13" value="{{ $f('invoice.rfc') }}" style="text-transform:uppercase">@error('invoice.rfc')<p class="ev-err">{{ $message }}</p>@enderror</div>
                    <div class="ev-field"><label for="inv_razon">Nombre o razón social</label><input id="inv_razon" name="invoice[razon_social]" type="text" value="{{ $f('invoice.razon_social') }}">@error('invoice.razon_social')<p class="ev-err">{{ $message }}</p>@enderror</div>
                    <div class="ev-field"><label for="inv_cp">Código postal fiscal</label><input id="inv_cp" name="invoice[cp]" type="text" maxlength="10" value="{{ $f('invoice.cp') }}">@error('invoice.cp')<p class="ev-err">{{ $message }}</p>@enderror</div>
                    <div class="ev-field"><label for="inv_reg">Régimen fiscal</label><input id="inv_reg" name="invoice[regimen]" type="text" value="{{ $f('invoice.regimen') }}"></div>
                    <div class="ev-field"><label for="inv_uso">Uso del CFDI</label><input id="inv_uso" name="invoice[uso_cfdi]" type="text" value="{{ $f('invoice.uso_cfdi') }}"></div>
                    <div class="ev-field"><label for="inv_mail">Correo para la factura</label><input id="inv_mail" name="invoice[correo]" type="email" value="{{ $f('invoice.correo') }}"></div>
                </div>
            </fieldset>

            <div class="ev-field">
                <label class="ev-check"><input type="checkbox" name="accept_policies" value="1" required @checked(old('accept_policies'))> <span>He leído y acepto las <a href="{{ $event->url('politicas') }}" target="_blank" rel="noopener">políticas del evento</a> (clima, cancelaciones y reglamento).</span></label>
                @error('accept_policies')<p class="ev-err">{{ $message }}</p>@enderror
                @if ($event->show('rooms') && $event->hotelEmails())
                    <p class="ev-hint" style="margin:10px 0 0">Si reservas habitación, compartimos con el hotel tu nombre, correo, teléfono y código de reservación solo para validar tu precio especial.</p>
                @endif
                <label class="ev-check" style="margin-top:10px"><input type="checkbox" name="newsletter" value="1" @checked(old('newsletter'))> <span>Quiero recibir noticias de cursos y eventos (opcional).</span></label>
            </div>

            <button type="submit" class="ev-btn" id="evPay" data-ev-track="pay_click">Pagar {{ $money($quote['total']) }} MXN con Mercado Pago</button>
            <p class="ev-hint" style="margin:0">Pagas de forma segura en Mercado Pago, con tarjeta{{ '' }} y otros medios disponibles. Al terminar volverás a esta página.</p>
        </form>

        <aside class="ev-sum" aria-label="Resumen de tu compra">
            <dl>
                @foreach ($quote['lines'] as $l)<dt>{{ $l['qty'] }} × {{ $l['name'] }} ({{ $money($l['unit']) }})</dt><dd>{{ $money($l['subtotal']) }}</dd>@endforeach
                @foreach ($quote['discounts'] as $d)<dt>{{ $d['label'] }}</dt><dd>−{{ $money($d['amount']) }}</dd>@endforeach
                <dt class="ev-hint">Subtotal sin IVA</dt><dd class="ev-hint">{{ $money($quote['base']) }}</dd>
                <dt class="ev-hint">IVA ({{ rtrim(rtrim(number_format($event->iva_rate, 2), '0'), '.') }} %)</dt><dd class="ev-hint">{{ $money($quote['iva']) }}</dd>
                <dt class="total">Total</dt><dd class="total">{{ $money($quote['total']) }} MXN</dd>
            </dl>
            <p style="margin:16px 0 0"><a href="{{ $event->url('carrito') }}">Cambiar paquetes</a></p>
        </aside>
    </div>
</div></div>
@endsection

@push('scripts')
<script>
(function () {
    var cb = document.getElementById('wantsInvoice'), box = document.getElementById('invoiceBox');
    cb.addEventListener('change', function () { box.style.display = cb.checked ? 'grid' : 'none'; });
    var name = document.getElementById('name'), first = document.querySelector('[data-first="1"]');
    if (first) { name.addEventListener('blur', function () { if (!first.value) first.value = name.value; }); }
    document.getElementById('evCheckout').addEventListener('submit', function () { var b = document.getElementById('evPay'); b.classList.add('is-off'); b.setAttribute('aria-disabled', 'true'); b.textContent = 'Un momento…'; });
    evPush('begin_checkout', { value: {{ (float) $quote['total'] }}, currency: 'MXN' });
})();
</script>
@endpush
