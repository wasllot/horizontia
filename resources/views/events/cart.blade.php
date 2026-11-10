@extends('events.layout', ['metaTitle' => 'Tu carrito · ' . $event->name, 'canonical' => $event->url('carrito')])

@section('body')
@php $money = fn ($v) => '$' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0); @endphp
<div class="ev-page"><div class="ev-wrap">
    @include('events.steps', ['step' => 1])
    <h1>Tu carrito</h1>
    @if (session('event_error'))<p class="ev-alert" role="alert">{{ session('event_error') }}</p>@endif

    @if (!$quote)
        <div class="ev-card" style="text-align:center;display:grid;gap:14px;justify-items:center">
            @if ($event->asset('galileo'))<img class="ev-char ev-char--card" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" width="140" height="140">@endif
            <p>Tu carrito está vacío.</p>
            <a class="ev-btn" href="{{ $event->url() }}#entrada">Volver al evento</a>
        </div>
    @else
        @php $multi = count($packages) > 1; $room = fn ($pk) => $quantity + $pk['persons'] <= $maxQuantity; @endphp
        <div class="ev-layout">
            <div class="ev-card">
                <h2 style="font-size:1.3rem;margin:0 0 6px">{{ $multi ? 'Tus paquetes' : 'Entrada' }} · {{ $event->name }}</h2>
                <p class="ev-muted" style="margin:0 0 16px">{{ $quantity }} {{ $quantity === 1 ? 'persona' : 'personas' }} · IVA incluido · incluye espacio para tu casa de campaña</p>
                <form method="post" action="{{ $event->url('carrito/actualizar') }}">
                    @csrf
                    <ul class="ev-lines">
                        @foreach ($quote['lines'] as $l)
                            @php $pk = collect($packages)->firstWhere('key', $l['key']); @endphp
                            <li class="ev-line">
                                <div><b>{{ $l['name'] }}</b><small>{{ $money($l['unit']) }} MXN {{ $multi ? 'por paquete · ' . $l['persons'] * $l['qty'] . ' ' . ($l['persons'] * $l['qty'] === 1 ? 'persona' : 'personas') : 'por persona' }}</small></div>
                                <div class="ev-line__sum">{{ $money($l['subtotal']) }}</div>
                                <div class="ev-line__ctl">
                                    <div class="ev-qty ev-qty--sm">
                                        <button type="submit" name="line" value="{{ $l['key'] }}|{{ $l['qty'] - 1 }}" aria-label="Quitar un paquete de {{ $l['name'] }}" {{ $l['qty'] <= 1 ? 'disabled' : '' }}>−</button>
                                        <input type="number" value="{{ $l['qty'] }}" min="1" aria-label="Cantidad de {{ $l['name'] }}" readonly>
                                        <button type="submit" name="line" value="{{ $l['key'] }}|{{ $l['qty'] + 1 }}" aria-label="Agregar un paquete de {{ $l['name'] }}" {{ $pk && $room($pk) ? '' : 'disabled' }}>+</button>
                                    </div>
                                    <button type="submit" name="line" value="{{ $l['key'] }}|0" class="ev-btn ev-btn--ghost ev-btn--sm">Quitar</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @if ($multi)
                        <h3 style="font-size:1.05rem;margin:0 0 10px">¿Son más amigos? Agrega otro paquete</h3>
                        <div class="ev-more-pkgs">
                            @foreach ($packages as $pk)
                                <button type="submit" name="add" value="{{ $pk['key'] }}" class="ev-btn ev-btn--ghost ev-btn--sm" {{ $room($pk) ? '' : 'disabled' }}>+ {{ $pk['name'] }} · {{ $money($pk['price']) }}</button>
                            @endforeach
                        </div>
                    @endif
                    <p class="ev-hint" style="margin:14px 0 0">Hasta {{ $maxQuantity }} {{ $maxQuantity === 1 ? 'persona' : 'personas' }} por compra. Menores de {{ (int) (data_get($event->content, 'minors_age') ?: 6) }} años no pagan.</p>
                </form>
            </div>
            <aside class="ev-sum" aria-label="Resumen">
                <dl>
                    @foreach ($quote['lines'] as $l)<dt>{{ $l['qty'] }} × {{ $l['name'] }} ({{ $money($l['unit']) }})</dt><dd>{{ $money($l['subtotal']) }}</dd>@endforeach
                    @foreach ($quote['discounts'] as $d)<dt>{{ $d['label'] }} −{{ rtrim(rtrim(number_format($d['percent'], 2), '0'), '.') }} %</dt><dd>−{{ $money($d['amount']) }}</dd>@endforeach
                    <dt class="ev-hint">Personas</dt><dd class="ev-hint">{{ $quantity }}</dd>
                    <dt class="ev-hint">IVA incluido ({{ rtrim(rtrim(number_format($event->iva_rate, 2), '0'), '.') }} %)</dt><dd class="ev-hint">{{ $money($quote['iva']) }}</dd>
                    <dt class="total">Total</dt><dd class="total">{{ $money($quote['total']) }} MXN</dd>
                </dl>
                <p style="margin:18px 0 0;display:grid;gap:10px">
                    <a class="ev-btn" href="{{ $event->url('pago') }}" data-ev-track="begin_checkout_click">Continuar al pago</a>
                    <a class="ev-btn ev-btn--ghost" href="{{ $event->url() }}#entrada">Volver al evento</a>
                </p>
            </aside>
        </div>
    @endif
</div></div>
@endsection
