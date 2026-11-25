@php
    $money = fn ($v) => '$' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $start = $event->local($event->start_at);
    $key = $order->confirmationKey() ?: $order->roomCode();   // toda compra pagada tiene clave; el respaldo es el código general del evento
    $dates = $event->datesLabel();
    $closing = $event->closingDatesLabel();
    $whatsapp = $event->hotelWhatsappUrl();
    $first = \Illuminate\Support\Str::of($order->name)->explode(' ')->first();
    $h2 = 'margin:26px 0 8px;font-family:Georgia,\'Times New Roman\',serif;font-size:17px;line-height:1.3;letter-spacing:.5px;color:#14213d;';
@endphp
<!DOCTYPE html>
<html lang="es-MX"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Confirmación de registro · {{ $event->name }}</title></head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Roboto,Segoe UI,Arial,sans-serif;color:#14213d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;"><tr><td align="center" style="padding:24px 12px;">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #d9deea;border-radius:20px;overflow:hidden;">
    <tr><td style="padding:0;line-height:0;font-size:0;"><img src="{{ $message->embed(public_path('images/eventos/astrofest-correo-banner.jpg')) }}" width="600" alt="Astrofest · Lluvia de estrellas Gemínidas con la astrónoma Luz Calderón" style="display:block;width:100%;max-width:600px;height:auto;border:0;"></td></tr>

    <tr><td align="center" style="padding:26px 28px 4px;">
      <h1 style="margin:0 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.25;color:#14213d;">Confirmación de registro</h1>
      <p style="margin:0 0 6px;font-size:18px;font-weight:700;color:#14213d;">Listo, {{ $first }}</p>
      <p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#14213d;">Tu lugar en {{ $event->name }} está apartado.</p>
      @if ($key)
      <p style="margin:0;font-size:14px;color:#5b6478;">Clave de confirmación:</p>
      <p style="margin:2px 0 14px;font-family:Consolas,Menlo,monospace;font-size:26px;font-weight:700;letter-spacing:1.5px;color:#0d5bd6;white-space:nowrap;">{{ $key }}</p>
      @endif
      <p style="margin:0 0 4px;font-size:15px;line-height:1.5;color:#14213d;">Paquete: <strong>{{ $order->spaceLabel() }}</strong></p>
      <p style="margin:0 0 4px;font-size:15px;line-height:1.5;color:#14213d;">Personas registradas: <strong>{{ $order->quantity }}</strong></p>
      @if ($dates)
      <p style="margin:0 0 4px;font-size:15px;line-height:1.5;color:#14213d;">Fechas: <strong>{{ $dates }}</strong></p>
      @endif
      @if ($event->venue_name || $start)
      <p style="margin:6px 0 0;font-size:13px;line-height:1.5;color:#5b6478;">{{ $event->venue_name }}{{ $event->venue_city ? ', ' . $event->venue_city : '' }}@if ($start) · recepción y registro desde las {{ $start->format('H:i') }} h @endif</p>
      @endif
    </td></tr>

    <tr><td style="padding:18px 28px 12px;">
      <p style="margin:0;font-size:14px;line-height:1.6;color:#5b6478;text-align:center;">Guarda este correo: al llegar al registro muestra el código QR (o la clave) de cada asistente y recibe tu pulsera.</p>
    </td></tr>
    @foreach ($tickets as $t)
    <tr><td style="padding:0 28px 14px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fffae3;border:2px solid #fed304;border-radius:16px;"><tr>
        <td width="124" align="center" style="padding:12px;"><img src="{{ $message->embedData(\App\Support\QrCode::png($t->code, 8), $t->code . '.png', 'image/png') }}" width="100" height="100" alt="QR {{ $t->code }}" style="display:block;width:100px;height:100px;border-radius:8px;background:#ffffff;"></td>
        <td style="padding:12px 12px 12px 0;color:#14213d;">
          <div style="font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#5b6478;white-space:nowrap;">Entrada {{ $loop->iteration }} de {{ $tickets->count() }}</div>
          <div style="font-size:18px;font-weight:700;margin:2px 0 8px;">{{ $t->attendee_name }}</div>
          <div style="font-size:12px;color:#5b6478;">Clave de acceso</div>
          <div style="font-family:Consolas,Menlo,monospace;font-size:18px;font-weight:700;letter-spacing:1px;white-space:nowrap;">{{ $t->code }}</div>
        </td></tr></table>
    </td></tr>
    @endforeach

    <tr><td style="padding:6px 28px 4px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#5b6478;border-top:1px solid #d9deea;">
        @foreach ($order->lines() as $l)
        <tr><td style="padding:{{ $loop->first ? '12px' : '2px' }} 0 4px;">{{ $l['qty'] }} × {{ $l['name'] }} ({{ $money($l['unit_price']) }})</td><td align="right" style="padding:{{ $loop->first ? '12px' : '2px' }} 0 4px;">{{ $money($l['subtotal']) }}</td></tr>
        @endforeach
        @if ((float) $order->discount > 0)<tr><td style="padding:2px 0;">{{ $order->discount_label ?: 'Descuento' }}</td><td align="right">−{{ $money($order->discount) }}</td></tr>@endif
        <tr><td style="padding:2px 0;">Subtotal sin IVA</td><td align="right">{{ $money($order->subtotalBeforeIva()) }}</td></tr>
        <tr><td style="padding:2px 0;">IVA ({{ rtrim(rtrim(number_format($event->iva_rate, 2), '0'), '.') }} %)</td><td align="right">{{ $money($order->iva) }}</td></tr>
        <tr><td style="padding:8px 0 4px;font-weight:700;color:#14213d;font-size:16px;">Total pagado (IVA incluido)</td><td align="right" style="padding:8px 0 4px;font-weight:700;color:#14213d;font-size:16px;">{{ $money($order->total) }} MXN</td></tr>
      </table>
      <p style="margin:6px 0 0;font-size:12px;color:#5b6478;">Compra #{{ $order->id }}@if ($order->payment_id) · Pago Mercado Pago {{ $order->payment_id }}@endif</p>
    </td></tr>

    <tr><td align="center" style="padding:6px 28px 0;">
      <h2 style="{{ $h2 }}">PARA QUE LO DISFRUTES AL MÁXIMO</h2>
      <p style="margin:0 0 10px;font-size:15px;line-height:1.6;color:#14213d;">A continuación, te damos algunas recomendaciones:</p>
      <table role="presentation" align="center" cellpadding="0" cellspacing="0"><tr><td align="left" style="font-size:15px;line-height:1.7;color:#14213d;">
        <ol style="margin:0;padding-left:22px;">
          <li>Trae ropa abrigadora.</li>
          <li>Tu casa de campaña y sleeping bag.</li>
          <li>Lámpara de luz roja.</li>
          <li>Silla o tapete.</li>
          <li>Si tienes telescopio, tráelo; si no, en el evento podrás observar con los nuestros.</li>
          <li>Binoculares.</li>
        </ol>
      </td></tr></table>
    </td></tr>

    @if ($event->show('rooms'))
    <tr><td align="center" style="padding:6px 28px 0;">
      <h2 style="{{ $h2 }}">¿PREFIERES HABITACIÓN?</h2>
      <p style="margin:0;font-size:15px;line-height:1.7;color:#14213d;">
        Si tienes interés en hacer cambio para una habitación en el hotel, comunícate
        @if ($whatsapp)
          por WhatsApp al siguiente número:<br><a href="{{ $whatsapp }}" style="color:#0d5bd6;font-size:20px;font-weight:700;text-decoration:underline;">{{ $event->hotel_whatsapp }}</a><br>
        @elseif ($event->hotel_phone)
          por teléfono al siguiente número:<br><a href="tel:{{ preg_replace('/[^\d+]/', '', $event->hotel_phone) }}" style="color:#0d5bd6;font-size:20px;font-weight:700;text-decoration:underline;">{{ $event->hotel_phone }}</a><br>
        @else
          directamente con el hotel
        @endif
        y comparte tu clave de confirmación @if ($key)(<strong>{{ $key }}</strong>) @endif para que te identifiquen y te ofrezcan un precio especial.
        @if ($event->sharedHotelCode())<br>Código del evento para el hotel: <strong>{{ $event->sharedHotelCode() }}</strong>.@endif
        @if ($event->hotel_booking_url)<br>También puedes hacerlo en <a href="{{ $event->hotel_booking_url }}" style="color:#0d5bd6;">el sitio del hotel</a>.@endif
      </p>
    </td></tr>
    @endif

    <tr><td align="center" style="padding:6px 28px 0;">
      <h2 style="{{ $h2 }}">¡GRACIAS POR MIRAR HACIA ARRIBA!</h2>
      @if ($closing)
      <p style="margin:0 0 6px;font-size:16px;line-height:1.5;color:#14213d;">¡Nos vemos {{ $closing }}!</p>
      @endif
      <img src="{{ $message->embed(public_path('images/eventos/astrofest-correo-mascota.png')) }}" width="150" alt="Galileo, la mascota de Astrofest, celebrando con su telescopio" style="display:block;margin:6px auto 0;width:150px;max-width:60%;height:auto;border:0;">
    </td></tr>

    <tr><td style="padding:18px 28px 6px;font-size:14px;line-height:1.6;color:#5b6478;">
      <strong style="color:#14213d;">Antes de ir:</strong> lleva tu comprobante e identificación. El estacionamiento ($60 por día) se paga directamente en el hotel.<br>
      Consulta las <a href="{{ $event->url('politicas') }}" style="color:#0d5bd6;">políticas del evento</a> y la <a href="{{ $event->url() }}" style="color:#0d5bd6;">página del evento</a>.
    </td></tr>
    <tr><td style="padding:10px 28px 28px;font-size:13px;color:#5b6478;">
      @if ($event->contact_email || $event->contact_whatsapp)¿Dudas? Escríbenos @if ($event->contact_email)a <a href="mailto:{{ $event->contact_email }}" style="color:#0d5bd6;">{{ $event->contact_email }}</a>@endif{{ $event->contact_email && $event->contact_whatsapp ? ' o ' : '' }}@if ($event->contact_whatsapp)por WhatsApp al {{ $event->contact_whatsapp }}@endif.<br>@endif
      Organiza Horizontia.
    </td></tr>
  </table>
</td></tr></table>
</body></html>
