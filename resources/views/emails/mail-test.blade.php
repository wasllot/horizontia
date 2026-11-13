<!DOCTYPE html>
<html lang="es-MX"><head><meta charset="utf-8"><title>Prueba de correo</title></head>
<body style="margin:0;padding:24px;background:#f6f7fb;font-family:Roboto,Segoe UI,Arial,sans-serif;color:#14213d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
  <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="max-width:520px;width:100%;background:#ffffff;border:1px solid #d9deea;border-top:6px solid #fed304;border-radius:16px;">
    <tr><td style="padding:26px;">
      <h1 style="margin:0 0 10px;font-family:Georgia,'Times New Roman',serif;font-size:22px;">Si lees esto, el correo funciona</h1>
      <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#5b6478;">Este mensaje salió de <strong>{{ $appUrl }}</strong> por el modo <strong>{{ $mode }}</strong>.</p>
      <p style="margin:0;font-size:13px;line-height:1.7;color:#5b6478;">Enviado: {{ $sentAt->format('d/m/Y H:i:s') }} (hora de México)<br>Remitente: {{ $from }}</p>
    </td></tr>
  </table>
</td></tr></table>
</body></html>
