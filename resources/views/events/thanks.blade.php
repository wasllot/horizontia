@extends('events.layout', ['metaTitle' => 'Confirmación de compra · ' . $event->name, 'canonical' => $event->url('gracias')])

@section('body')
@php $money = fn ($v) => '$' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0); @endphp
<div class="ev-page"><div class="ev-narrow">
    @include('events.steps', ['step' => $order->isPaid() ? 5 : 3])
    @if ($order->isPaid())
        @if ($event->asset('galileo'))<img class="ev-char ev-char--card" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" width="170" height="170">@endif
        <h1>¡Listo, {{ \Illuminate\Support\Str::of($order->name)->explode(' ')->first() }}! Tu lugar está apartado</h1>
        <p>Te enviamos la confirmación a <strong>{{ $order->email }}</strong> con tu clave de acceso. Al llegar, muéstrala en el registro (desde el celular o impresa) y recibe tu pulsera.</p>
        @foreach ($order->tickets as $t)
            <div class="ev-ticket-card">
                <img src="{{ $event->url('boleto/' . $t->code . '.png') }}" alt="Código QR de {{ $t->attendee_name }}" width="116" height="116">
                <div><h2 style="margin:0 0 4px;font-size:1.15rem">{{ $t->attendee_name }}</h2><p style="margin:0">Clave de acceso</p><code>{{ $t->code }}</code></div>
            </div>
        @endforeach
        @if ($order->confirmationKey())
            <div class="ev-card" style="margin:6px 0 16px">
                <h2 style="margin:0 0 6px;font-size:1.15rem">Tu clave de confirmación</h2>
                <p style="margin:0 0 6px"><code style="font-size:1.25rem;letter-spacing:.08em;color:var(--gold)">{{ $order->confirmationKey() }}</code></p>
                <p class="ev-hint" style="margin:0">{{ $order->spaceLabel() }} · {{ $order->quantity }} {{ $order->quantity === 1 ? 'persona registrada' : 'personas registradas' }}</p>
            </div>
            @if ($event->show('rooms'))
                <div class="ev-card" style="margin:6px 0 16px">
                    <h2 style="margin:0 0 6px;font-size:1.15rem">¿Prefieres habitación?</h2>
                    <p style="margin:0">Si tienes interés en hacer cambio para una habitación en el hotel, comunícate
                        @if ($event->hotelWhatsappUrl()) por WhatsApp al <a href="{{ $event->hotelWhatsappUrl() }}" target="_blank" rel="noopener"><strong>{{ $event->hotel_whatsapp }}</strong></a>
                        @elseif ($event->hotel_phone) por teléfono al <strong>{{ $event->hotel_phone }}</strong>
                        @else directamente con el hotel @endif
                        y comparte tu clave de confirmación para que te identifiquen y te ofrezcan un precio especial (sujeto a disponibilidad).</p>
                </div>
            @endif
        @endif
        <p class="ev-hint">{{ $order->packagesLabel() }} · Total pagado: {{ $money($order->total) }} MXN · IVA incluido ({{ $money($order->iva) }}). Compra #{{ $order->id }}.</p>
        <div class="ev-actions">
            <a class="ev-btn ev-btn--sm" href="{{ $event->url('evento.ics') }}">Agregar al calendario</a>
            <a class="ev-btn ev-btn--ghost ev-btn--sm" href="https://wa.me/?text={{ rawurlencode('¡Voy a ' . $event->name . '! Aparta tu lugar: ' . $event->url()) }}" target="_blank" rel="noopener" data-ev-track="share_whatsapp">Invitar por WhatsApp</a>
            @if ($event->venue_map_url)<a class="ev-btn ev-btn--ghost ev-btn--sm" href="{{ $event->venue_map_url }}" target="_blank" rel="noopener">Cómo llegar</a>@endif
            <button type="button" class="ev-btn ev-btn--ghost ev-btn--sm" onclick="window.print()">Imprimir boletos</button>
        </div>
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
