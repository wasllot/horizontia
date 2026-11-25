@php
    $key = $order->confirmationKey() ?: $order->roomCode();
    $dates = $event->datesLabel();
@endphp
<!DOCTYPE html>
<html lang="es-MX"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Confirmación de registro de participante · {{ $event->name }}</title></head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Roboto,Segoe UI,Arial,sans-serif;color:#14213d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;"><tr><td align="center" style="padding:24px 12px;">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #d9deea;border-radius:20px;overflow:hidden;">
    <tr><td style="padding:0;line-height:0;font-size:0;"><img src="{{ $message->embed(public_path('images/eventos/astrofest-correo-banner.jpg')) }}" width="600" alt="Astrofest · Lluvia de estrellas Gemínidas con la astrónoma Luz Calderón" style="display:block;width:100%;max-width:600px;height:auto;border:0;"></td></tr>

    <tr><td align="center" style="padding:26px 28px 8px;">
      <h1 style="margin:0 0 18px;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.25;color:#14213d;">Confirmación de registro de participante</h1>
      <p style="margin:0 0 8px;font-size:17px;font-weight:700;color:#14213d;">{{ $order->name }}</p>
      <p style="margin:0 0 8px;font-size:16px;font-weight:700;"><a href="mailto:{{ $order->email }}" style="color:#14213d;text-decoration:none;">{{ $order->email }}</a></p>
      <p style="margin:0 0 18px;font-size:16px;font-weight:700;color:#14213d;">{{ $order->phone }}</p>
      <p style="margin:0;font-size:14px;color:#5b6478;">Clave de confirmación:</p>
      <p style="margin:2px 0 14px;font-family:Consolas,Menlo,monospace;font-size:26px;font-weight:700;letter-spacing:1.5px;color:#0d5bd6;white-space:nowrap;">{{ $key }}</p>
      <p style="margin:0 0 4px;font-size:15px;line-height:1.5;color:#14213d;">Paquete: <strong>{{ $order->spaceLabel() }}</strong></p>
      <p style="margin:0 0 4px;font-size:15px;line-height:1.5;color:#14213d;">Personas registradas: <strong>{{ $order->quantity }}</strong></p>
      @if ($dates)
      <p style="margin:0 0 4px;font-size:15px;line-height:1.5;color:#14213d;">Fechas: <strong>{{ $dates }}</strong></p>
      @endif
      @if ($tickets->count() > 1 || $tickets->first()?->attendee_name !== $order->name)
      <p style="margin:8px 0 0;font-size:13px;line-height:1.5;color:#5b6478;">Asistentes: {{ $tickets->pluck('attendee_name')->implode(', ') }}</p>
      @endif
    </td></tr>

    <tr><td align="center" style="padding:20px 28px 0;">
      <img src="{{ $message->embedData(\App\Support\QrCode::png($key, 8), $key . '.png', 'image/png') }}" width="130" height="130" alt="QR {{ $key }}" style="display:block;width:130px;height:130px;border-radius:8px;background:#ffffff;">
      <p style="margin:18px 0 0;color:#9aa3b8;letter-spacing:2px;">______________</p>
    </td></tr>

    <tr><td align="center" style="padding:14px 28px 0;">
      <img src="{{ $message->embed(public_path('images/eventos/astrofest-correo-mascota.png')) }}" width="130" alt="Galileo, la mascota de Astrofest, celebrando con su telescopio" style="display:block;margin:0 auto;width:130px;max-width:50%;height:auto;border:0;">
    </td></tr>

    <tr><td style="padding:18px 28px 28px;font-size:13px;line-height:1.6;color:#5b6478;text-align:center;">
      Quien compró recibió esta misma clave y se pondrá en contacto con ustedes para reservar; con ella pueden confirmar que su compra del evento está pagada y aplicarle la tarifa especial (sujeta a disponibilidad).<br>
      Pago confirmado el {{ $order->paid_at?->timezone(\App\Models\Event::timezone())->format('d/m/Y H:i') }} h · compra #{{ $order->id }}. Este aviso se envía automáticamente cuando se aprueba el pago; al cerrar las ventas recibirán además la lista completa.
      @if ($event->contact_email)<br>¿Dudas? Escriban a <a href="mailto:{{ $event->contact_email }}" style="color:#0d5bd6;">{{ $event->contact_email }}</a>.@endif<br>
      Organiza Horizontia.
    </td></tr>
  </table>
</td></tr></table>
</body></html>
