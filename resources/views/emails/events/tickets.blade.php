@php
    $money = fn ($v) => '$' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $start = $event->local($event->start_at);
    $logo = $event->asset('logo') ?: asset('images/eventos/astrofest-wordmark.svg');
@endphp
<!DOCTYPE html>
<html lang="es-MX"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Tus entradas · {{ $event->name }}</title></head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Roboto,Segoe UI,Arial,sans-serif;color:#14213d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;"><tr><td align="center" style="padding:24px 12px;">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #d9deea;border-top:6px solid #fed304;border-radius:20px;overflow:hidden;">
    <tr><td style="padding:28px 28px 8px;" align="center"><table role="presentation" cellpadding="0" cellspacing="0" style="background:#1c1c1c;border-radius:14px;"><tr><td style="padding:10px 22px;"><img src="{{ $message->embed(public_path('images/eventos/astrofest-logo.png')) }}" width="220" alt="Astrofest {{ $event->name }}" style="display:block;width:220px;max-width:100%;height:auto;"></td></tr></table></td></tr>
    <tr><td style="padding:12px 28px 0;">
      <h1 style="margin:16px 0 8px;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.25;color:#14213d;">¡Tu lugar está apartado, {{ \Illuminate\Support\Str::of($order->name)->explode(' ')->first() }}!</h1>
      <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#5b6478;">Gracias por tu compra. Guarda este correo: al llegar al registro muestra el código QR (o la clave) de cada asistente y recibe tu pulsera.</p>
      @if ($start)<p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#14213d;"><strong style="color:#14213d;background:#fed304;padding:2px 8px;border-radius:6px;">{{ ucfirst($start->locale('es')->translatedFormat('l j \d\e F \d\e Y')) }}</strong> · check-in desde las {{ $start->format('H:i') }} h<br>{{ $event->venue_name }}{{ $event->venue_city ? ', ' . $event->venue_city : '' }}</p>@endif
    </td></tr>
    @foreach ($tickets as $t)
    <tr><td style="padding:0 28px 14px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fffae3;border:2px solid #fed304;border-radius:16px;"><tr>
        <td width="150" align="center" style="padding:14px;"><img src="{{ $message->embedData(\App\Support\QrCode::png($t->code, 8), $t->code . '.png', 'image/png') }}" width="122" height="122" alt="QR {{ $t->code }}" style="display:block;border-radius:8px;background:#ffffff;"></td>
        <td style="padding:14px 14px 14px 0;color:#14213d;">
          <div style="font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#5b6478;">Entrada {{ $loop->iteration }} de {{ $tickets->count() }}</div>
          <div style="font-size:18px;font-weight:700;margin:2px 0 8px;">{{ $t->attendee_name }}</div>
          <div style="font-size:12px;color:#5b6478;">Clave de acceso</div>
          <div style="font-family:Consolas,Menlo,monospace;font-size:20px;font-weight:700;letter-spacing:1.5px;">{{ $t->code }}</div>
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
    @if ($event->show('rooms'))
    <tr><td style="padding:14px 28px 0;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fffae3;border:1px dashed #fed304;border-radius:14px;"><tr><td style="padding:16px 18px;font-size:14px;line-height:1.6;color:#14213d;">
        <strong style="font-family:Georgia,'Times New Roman',serif;font-size:16px;">¿Prefieres habitación?</strong><br>
        @if ($order->roomCode())
          Tu código de reservación es:<br>
          <span style="display:inline-block;margin:8px 0;padding:6px 14px;border-radius:8px;background:#14213d;color:#fed304;font-family:Consolas,Menlo,monospace;font-size:20px;font-weight:700;letter-spacing:1.5px;">{{ $order->roomCode() }}</span><br>
          Para reservar, contacta al hotel
          @if ($event->hotel_whatsapp || $event->hotel_phone)
            @if ($event->hotel_phone) al <strong>{{ $event->hotel_phone }}</strong>@endif @if ($event->hotel_phone && $event->hotel_whatsapp) o @endif @if ($event->hotel_whatsapp) por WhatsApp al <strong>{{ $event->hotel_whatsapp }}</strong>@endif
          @endif
          y da tu código: el hotel ya lo tiene registrado y te hace la reservación a un precio especial (sujeto a disponibilidad). @if ($event->hotel_booking_url)También puedes hacerlo en <a href="{{ $event->hotel_booking_url }}" style="color:#0d5bd6;">el sitio del hotel</a>.@endif
        @else
          En breve te compartiremos por correo tu código para reservar directamente con el hotel, a un precio especial.
        @endif
      </td></tr></table>
    </td></tr>
    @endif
    <tr><td style="padding:18px 28px 6px;font-size:14px;line-height:1.6;color:#5b6478;">
      <strong style="color:#14213d;">Antes de ir:</strong> lleva ropa de invierno en capas, linterna con luz roja, tu comprobante e identificación. El estacionamiento ($60 por día) se paga directamente en el hotel.<br>
      Consulta las <a href="{{ $event->url('politicas') }}" style="color:#0d5bd6;">políticas del evento</a> y la <a href="{{ $event->url() }}" style="color:#0d5bd6;">página del evento</a>.
    </td></tr>
    <tr><td style="padding:14px 28px 28px;font-size:13px;color:#5b6478;">
      @if ($event->contact_email || $event->contact_whatsapp)¿Dudas? Escríbenos @if ($event->contact_email)a <a href="mailto:{{ $event->contact_email }}" style="color:#0d5bd6;">{{ $event->contact_email }}</a>@endif @if ($event->contact_whatsapp)por WhatsApp al {{ $event->contact_whatsapp }}@endif.<br>@endif
      Organiza Horizontia.
    </td></tr>
  </table>
</td></tr></table>
</body></html>
