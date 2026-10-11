<x-mail::message>
# Hola, {{ $name }}

@if ($stage > 1)
Tu carrito sigue esperándote. Si todavía quieres este curso, termina tu compra cuando quieras:
@else
Dejaste esto en tu carrito. Lo guardamos para ti:
@endif

@foreach ($items as $i)
- **[{{ html_entity_decode($i['title']) }}]({{ $i['url'] }})**@if (!empty($i['date'])) — {{ $i['date'] }}@endif — {!! strip_tags(formatAmount($i['price'], true)) !!}

@endforeach

<x-mail::button :url="$checkoutUrl">
Terminar mi compra
</x-mail::button>

Pagas con tarjeta, OXXO o transferencia SPEI y tienes {{ config('academy.refund_days') }} días de garantía de reembolso. Si tienes dudas, responde a este correo.

Gracias,<br>
{{ config('app.name') }}

<x-mail::subcopy>
Te escribimos porque tienes productos en tu carrito. [No quiero recibir estos recordatorios]({{ $unsubscribeUrl }}).
</x-mail::subcopy>
</x-mail::message>
