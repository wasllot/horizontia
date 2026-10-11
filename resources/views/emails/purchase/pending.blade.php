<x-mail::message>
# Hola, {{ $studentName }}

@if ($info['kind'] === 'review')
Recibimos tu pago con **{{ $info['label'] }}** y el banco lo está revisando. Te avisaremos en cuanto se apruebe; normalmente tarda unos minutos.
@else
Tu pedido #{{ $order->id }} está **esperando tu pago con {{ $info['label'] }}**. En cuanto se acredite activamos tu acceso y te enviamos otro correo.
@if (!empty($info['voucher_url']))

<x-mail::button :url="$info['voucher_url']">
Ver instrucciones y ficha de pago
</x-mail::button>
@endif
@if (!empty($info['expires_at']))

**Vigencia:** paga antes del {{ $info['expires_at'] }}; después la ficha caduca y tendrás que hacer un nuevo pedido.
@endif
@endif

Puedes consultar el estado de tu pedido aquí: [{{ $statusUrl }}]({{ $statusUrl }})

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
