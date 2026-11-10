@php $persons = $orders->sum('quantity'); @endphp
<!DOCTYPE html>
<html lang="es-MX"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Lista final · {{ $event->name }}</title></head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Roboto,Segoe UI,Arial,sans-serif;color:#14213d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;"><tr><td align="center" style="padding:24px 12px;">
  <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border:1px solid #d9deea;border-top:6px solid #fed304;border-radius:20px;overflow:hidden;">
    <tr><td style="padding:28px 28px 8px;">
      <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#5b6478;">{{ $event->name }}</p>
      <h1 style="margin:8px 0 10px;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.25;color:#14213d;">Lista final de códigos de reservación</h1>
      <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#5b6478;">Las ventas cerraron: {{ $orders->count() }} {{ $orders->count() === 1 ? 'compra pagada' : 'compras pagadas' }} ({{ $persons }} {{ $persons === 1 ? 'persona' : 'personas' }}). Cada comprador tiene su código y se pondrá en contacto con ustedes para reservar habitación con el precio especial (sujeto a disponibilidad). Adjuntamos la misma lista en CSV para Excel.</p>
    </td></tr>
    <tr><td style="padding:0 28px 24px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
        <tr style="background:#14213d;color:#ffffff;text-align:left;"><th style="padding:9px 10px;">Código</th><th style="padding:9px 10px;">Titular</th><th style="padding:9px 10px;">Contacto</th><th style="padding:9px 10px;text-align:center;">Pers.</th></tr>
        @foreach ($orders as $o)
        <tr style="background:{{ $loop->even ? '#f6f7fb' : '#ffffff' }};border-bottom:1px solid #e6eaf2;">
          <td style="padding:9px 10px;font-family:Consolas,Menlo,monospace;font-weight:700;white-space:nowrap;">{{ $o->roomCode() }}</td>
          <td style="padding:9px 10px;">{{ $o->name }}</td>
          <td style="padding:9px 10px;">{{ $o->email }}<br><span style="color:#5b6478;">{{ $o->phone }}</span></td>
          <td style="padding:9px 10px;text-align:center;">{{ $o->quantity }}</td>
        </tr>
        @endforeach
      </table>
    </td></tr>
    <tr><td style="padding:0 28px 28px;font-size:13px;color:#5b6478;">
      @if ($event->contact_email)¿Dudas? Escriban a <a href="mailto:{{ $event->contact_email }}" style="color:#0d5bd6;">{{ $event->contact_email }}</a>.<br>@endif
      Organiza Horizontia.
    </td></tr>
  </table>
</td></tr></table>
</body></html>
