<x-mail::message>
# Nueva solicitud de reembolso

**{{ $request->user?->profile?->full_name ?: $request->user?->email }}** pidió el reembolso del pedido **#{{ $request->order_id }}** ({!! strip_tags(formatAmount($request->order->amount)) !!}).

> {{ $request->reason }}

Avance al pedirlo: **{{ $request->progress }} %**.

<x-mail::button :url="$url">
Revisar la solicitud
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
