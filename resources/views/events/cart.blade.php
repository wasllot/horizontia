@extends('events.layout', ['metaTitle' => 'Tu carrito · ' . $event->name, 'canonical' => $event->url('carrito')])

@section('body')
@php $money = fn ($v) => '$' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0); @endphp
<div class="ev-page"><div class="ev-wrap">
    <h1>Tu carrito</h1>
    @if (session('event_error'))<p class="ev-alert" role="alert">{{ session('event_error') }}</p>@endif

    @if (!$quote)
        <div class="ev-card" style="text-align:center;display:grid;gap:14px;justify-items:center">
            @if ($event->asset('galileo'))<img class="ev-char ev-char--card" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" width="140" height="140">@endif
            <p>Tu carrito está vacío.</p>
            <a class="ev-btn" href="{{ $event->url() }}#entrada">Volver al evento</a>
        </div>
    @else
        <div class="ev-layout">
            <div class="ev-card">
                <h2 style="font-size:1.3rem;margin:0 0 6px">Entrada {{ $event->name }}</h2>
                <p class="ev-muted" style="margin:0 0 16px">{{ $money($quote['unit']) }} MXN por persona · IVA incluido · incluye camping para 1 persona</p>
                <form method="post" action="{{ $event->url('carrito/actualizar') }}" style="display:flex;flex-wrap:wrap;gap:14px;align-items:center">
                    @csrf
                    <div class="ev-qty">
                        <button type="submit" name="quantity" value="{{ max(1, $quantity - 1) }}" aria-label="Quitar una entrada" {{ $quantity <= 1 ? 'disabled' : '' }}>−</button>
                        <input type="number" name="quantity" value="{{ $quantity }}" min="1" max="{{ $maxQuantity }}" aria-label="Cantidad de entradas" readonly>
                        <button type="submit" name="quantity" value="{{ min($maxQuantity, $quantity + 1) }}" aria-label="Agregar una entrada" {{ $quantity >= $maxQuantity ? 'disabled' : '' }}>+</button>
                    </div>
                    <button type="submit" name="action" value="remove" class="ev-btn ev-btn--ghost ev-btn--sm">Quitar</button>
                </form>
            </div>
            <aside class="ev-sum" aria-label="Resumen">
                <dl>
                    <dt>{{ $quantity }} × {{ $money($quote['unit']) }}</dt><dd>{{ $money($quote['subtotal']) }}</dd>
                    @foreach ($quote['discounts'] as $d)<dt>{{ $d['label'] }} −{{ rtrim(rtrim(number_format($d['percent'], 2), '0'), '.') }} %</dt><dd>−{{ $money($d['amount']) }}</dd>@endforeach
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
