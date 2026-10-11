<x-mail::message>
# ¡Gracias por tu compra, {{ $studentName }}!

Ya tienes acceso. Esto es lo que compraste (pedido #{{ $order->id }}):

@foreach ($courses as $c)
- **{{ html_entity_decode($c['title']) }}**@if (!empty($c['date'])) — {{ $c['date'] }}@endif

@endforeach
@if ((float) $order->amount > 0)
**Total pagado:** {!! strip_tags(formatAmount($order->amount, true)) !!}

@endif
<x-mail::button :url="$myCoursesUrl">
Ir a mis cursos
</x-mail::button>

Si algo no se ve bien o necesitas ayuda, responde a este correo. Recuerda que tienes {{ config('academy.refund_days') }} días de garantía de reembolso.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
