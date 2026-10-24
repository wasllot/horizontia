<x-mail::message>
@if ($request->status === \App\Models\RefundRequest::APPROVED)
# Aprobamos tu reembolso

Aprobamos el reembolso del pedido **#{{ $request->order_id }}**. Se devuelve al mismo medio de pago a través de Mercado Pago; según tu banco o medio de pago puede tardar entre 3 y 15 días hábiles en acreditarse. El acceso al curso se retiró.
@else
# Sobre tu solicitud de reembolso

Revisamos tu solicitud del pedido **#{{ $request->order_id }}** y por ahora no pudimos aprobarla.

> {{ $request->review_note }}

Si crees que es un error, responde a este correo o escríbenos desde el formulario de contacto. La garantía es de {{ $days }} días y complementa tus derechos como consumidor.
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
