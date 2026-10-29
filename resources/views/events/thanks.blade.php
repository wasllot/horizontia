@extends('events.layout', ['metaTitle' => 'Confirmación de compra · ' . $event->name, 'canonical' => $event->url('gracias')])

@section('body')
@php $money = fn ($v) => '$' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0); @endphp
<div class="ev-page"><div class="ev-narrow">
    @if ($order->isPaid())
        @if ($event->asset('galileo'))<img class="ev-char ev-char--card" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" width="170" height="170">@endif
        <h1>¡Listo, {{ \Illuminate\Support\Str::of($order->name)->explode(' ')->first() }}! Tu lugar está apartado</h1>
        <p>Te enviamos la confirmación a <strong>{{ $order->email }}</strong> con tu clave de acceso. Al llegar, muéstrala en el registro (desde el celular o impresa) y recibe tu pulsera y tu kit.</p>
        @foreach ($order->tickets as $t)
            <div class="ev-ticket-card">
                <img src="{{ $event->url('boleto/' . $t->code . '.png') }}" alt="Código QR de {{ $t->attendee_name }}" width="116" height="116">
                <div><h2 style="margin:0 0 4px;font-size:1.15rem">{{ $t->attendee_name }}</h2><p style="margin:0">Clave de acceso</p><code>{{ $t->code }}</code></div>
            </div>
        @endforeach
        <p class="ev-hint">Total pagado: {{ $money($order->total) }} MXN · IVA incluido ({{ $money($order->iva) }}). Compra #{{ $order->id }}.</p>
        @if ($event->start_at)<p>Nos vemos el <strong>{{ $event->local($event->start_at)->locale('es')->translatedFormat('l j \d\e F') }}</strong> desde las {{ $event->local($event->start_at)->format('H:i') }} h{{ $event->venue_name ? ' en ' . $event->venue_name : '' }}.</p>@endif
    @elseif ($order->status === 'pending')
        <h1>Tu pago está pendiente</h1>
        <p>Estamos esperando la confirmación de Mercado Pago. En cuanto se acredite te enviamos tus entradas a <strong>{{ $order->email }}</strong>; no necesitas hacer nada más en esta página.</p>
        @if ($order->expires_at)<p class="ev-hint">Tu lugar queda guardado hasta el {{ $event->local($order->expires_at)->locale('es')->translatedFormat('j \d\e F, H:i') }} h.</p>@endif
        <p><a class="ev-btn ev-btn--ghost" href="{{ request()->fullUrl() }}">Revisar de nuevo</a></p>
    @else
        <h1>No se completó el pago</h1>
        <p>No se realizó ningún cargo o el pago fue rechazado. Tu carrito sigue guardado: puedes intentarlo de nuevo con otro medio de pago.</p>
        <p><a class="ev-btn" href="{{ $event->url('pago') }}">Intentar de nuevo</a></p>
    @endif
    <p style="margin-top:24px"><a class="ev-btn ev-btn--ghost" href="{{ $event->url() }}">Volver al evento</a></p>
</div></div>
@endsection

@if (!empty($pixel))
@push('scripts')
<script>
evPush('purchase', { transaction_id: @json('EV-' . $order->id), value: {{ (float) $order->total }}, currency: 'MXN', quantity: {{ (int) $order->quantity }} });
</script>
@endpush
@endif
