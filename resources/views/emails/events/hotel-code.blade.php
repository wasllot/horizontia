@php
    $start = $event->local($event->start_at);
    $end = $event->local($event->end_at);
    $dates = $start ? ucfirst($start->locale('es')->translatedFormat('l j \d\e F')) . ($end ? ' al ' . $end->locale('es')->translatedFormat('l j \d\e F \d\e Y') : '') : '';
@endphp
<!DOCTYPE html>
<html lang="es-MX"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Código de reservación · {{ $event->name }}</title></head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Roboto,Segoe UI,Arial,sans-serif;color:#14213d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;"><tr><td align="center" style="padding:24px 12px;">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #d9deea;border-top:6px solid #fed304;border-radius:20px;overflow:hidden;">
    <tr><td style="padding:28px 28px 6px;">
      <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#5b6478;">{{ $event->name }}</p>
      <h1 style="margin:8px 0 10px;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.25;color:#14213d;">Nueva compra con derecho a precio especial en habitación</h1>
      <p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#5b6478;">Quien compró recibió este mismo código y se pondrá en contacto con ustedes para reservar. Con él pueden confirmar que su compra del evento está pagada y aplicarle la tarifa especial (sujeta a disponibilidad).</p>
    </td></tr>
    <tr><td style="padding:0 28px 18px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fffae3;border:2px solid #fed304;border-radius:16px;"><tr><td align="center" style="padding:18px;">
        <div style="font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#5b6478;">Código de reservación</div>
        <div style="font-family:Consolas,Menlo,monospace;font-size:28px;font-weight:700;letter-spacing:2px;margin-top:4px;">{{ $order->roomCode() }}</div>
      </td></tr></table>
    </td></tr>
    <tr><td style="padding:0 28px 8px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#14213d;border-top:1px solid #d9deea;">
        <tr><td style="padding:12px 0 4px;color:#5b6478;width:34%;">Titular</td><td style="padding:12px 0 4px;font-weight:700;">{{ $order->name }}</td></tr>
        <tr><td style="padding:4px 0;color:#5b6478;">Correo</td><td style="padding:4px 0;"><a href="mailto:{{ $order->email }}" style="color:#0d5bd6;">{{ $order->email }}</a></td></tr>
        <tr><td style="padding:4px 0;color:#5b6478;">Teléfono</td><td style="padding:4px 0;">{{ $order->phone }}</td></tr>
        <tr><td style="padding:4px 0;color:#5b6478;">Personas</td><td style="padding:4px 0;">{{ $order->quantity }} ({{ $order->packagesLabel() }})</td></tr>
        <tr><td style="padding:4px 0;color:#5b6478;vertical-align:top;">Asistentes</td><td style="padding:4px 0;">{{ $tickets->pluck('attendee_name')->implode(', ') }}</td></tr>
        @if ($dates)<tr><td style="padding:4px 0;color:#5b6478;">Fechas del evento</td><td style="padding:4px 0;">{{ $dates }}</td></tr>@endif
        <tr><td style="padding:4px 0 12px;color:#5b6478;">Pago confirmado</td><td style="padding:4px 0 12px;">{{ $order->paid_at?->timezone(\App\Models\Event::timezone())->format('d/m/Y H:i') }} h · compra #{{ $order->id }}</td></tr>
      </table>
    </td></tr>
    <tr><td style="padding:6px 28px 28px;font-size:13px;line-height:1.6;color:#5b6478;">
      Este aviso se envía automáticamente cuando se aprueba el pago. Al cerrar las ventas recibirán además la lista completa.
      @if ($event->contact_email) ¿Dudas? Escriban a <a href="mailto:{{ $event->contact_email }}" style="color:#0d5bd6;">{{ $event->contact_email }}</a>.@endif<br>
      Organiza Horizontia.
    </td></tr>
  </table>
</td></tr></table>
</body></html>
