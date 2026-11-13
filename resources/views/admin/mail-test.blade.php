<!DOCTYPE html>
<html lang="es-MX"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title>Prueba de correo · {{ config('app.name') }}</title>
<style>
body{margin:0;background:#f4f6fb;font-family:Roboto,system-ui,Segoe UI,Arial,sans-serif;color:#14213d;padding:24px}
.card{max-width:720px;margin:0 auto;background:#fff;border-radius:18px;padding:28px;box-shadow:0 10px 40px rgba(20,33,61,.08)}
h1{margin:0 0 6px;font-size:1.6rem} p.lead{margin:0 0 22px;color:#5b6478}
table{width:100%;border-collapse:collapse;font-size:.92rem;margin-bottom:22px} td{padding:9px 0;border-bottom:1px solid #eef1f7;vertical-align:top} td:first-child{color:#5b6478;width:38%}
.btns{display:flex;gap:12px;flex-wrap:wrap} button{border:0;border-radius:12px;padding:13px 20px;font:700 .95rem inherit;cursor:pointer;background:#fed304;color:#14213d} button.alt{background:#14213d;color:#fff}
.res{margin:0 0 20px;padding:0;list-style:none} .res li{padding:10px 14px;border-radius:12px;margin-bottom:8px;font-size:.92rem}
.ok{background:#e8f8ef;color:#126c3b} .bad{background:#fdecea;color:#a12622}
.hint{margin-top:8px;padding:8px 12px;background:#fff6dc;color:#5c4400;border-radius:10px} pre{white-space:pre-wrap;word-break:break-word;background:#1b2140;color:#e9ecfa;padding:12px;border-radius:10px;font-size:.8rem;margin:8px 0 0} small{color:#5b6478;display:block;margin-top:14px}
</style></head>
<body><div class="card">
    <h1>Prueba de correo</h1>
    <p class="lead">Envía un mensaje de prueba a los destinatarios de abajo para confirmar que el servidor de correo y la cola funcionan.</p>

    @if (session('mail_test_error'))<p class="res"><span class="bad" style="display:block;padding:10px 14px;border-radius:12px" role="alert">{{ session('mail_test_error') }}</span></p>@endif

    @if ($results)
        <h2 style="font-size:1.05rem;margin:0 0 10px">Resultado ({{ $mode === 'cola' ? 'por la cola' : ($mode === 'entradas' ? 'correo de entradas de ejemplo, directo por SMTP' : 'directo por SMTP') }})</h2>
        <ul class="res" data-testid="mail-test-results">
            @foreach ($results as $r)
                <li class="{{ $r['ok'] ? 'ok' : 'bad' }}">
                    {{ $r['ok'] ? '✅' : '❌' }} <strong>{{ $r['to'] }}</strong> — {{ $r['ok'] ? $r['msg'] : 'No se pudo enviar (' . $r['msg'] . ')' }}
                    @if (!$r['ok'])
                        @php $err = $r['error']; @endphp
                        <div style="margin-top:8px;word-break:break-word">{{ $err['message'] }}</div>
                        @if ($err['hint'])<div class="hint"><strong>Causa probable:</strong> {{ $err['hint'] }}</div>@endif
                        <details style="margin-top:8px"><summary style="cursor:pointer">Detalle técnico</summary>
                            <pre>Excepción: {{ $err['class'] }}
Mensaje: {{ $err['message'] }}@foreach ($err['chain'] as $c)

Causa anterior: {{ $c }}@endforeach
@if ($err['debug'])

Diálogo con el servidor SMTP:
{{ $err['debug'] }}@endif</pre>
                        </details>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <table>@foreach ($info as $k => $v)<tr><td>{{ $k }}</td><td>{{ $v }}</td></tr>@endforeach</table>

    <form method="post" action="{{ route('admin.mail-test.send') }}">
        @csrf
        <label for="mt-emails" style="display:block;font-weight:600;margin-bottom:6px">Enviar a (hasta 5 correos, separados por coma)</label>
        <input id="mt-emails" name="emails" type="text" value="{{ old('emails', $emails) }}" autocomplete="off" placeholder="correo@ejemplo.com, otro@ejemplo.com" style="width:100%;box-sizing:border-box;padding:12px 14px;border:1px solid #cfd6e4;border-radius:12px;font:inherit;margin-bottom:14px">
        <div class="btns">
        <button type="submit" name="mode" value="directo">Enviar directo (SMTP)</button>
        <button type="submit" name="mode" value="cola" class="alt">Enviar por la cola</button>
        <button type="submit" name="mode" value="entradas" class="alt" data-testid="mail-test-tickets">Probar correo de entradas (logo y QR)</button>
        </div>
    </form>

    @if (!empty($failed))
        <h2 style="font-size:1.05rem;margin:26px 0 10px">Últimos trabajos fallidos de la cola</h2>
        <ul class="res" data-testid="mail-test-failed">
            @foreach ($failed as $f)<li class="bad"><strong>{{ $f['when'] }}</strong> · cola «{{ $f['queue'] }}»<div style="word-break:break-word">{{ $f['error'] }}</div></li>@endforeach
        </ul>
        <small style="margin-top:0">Si un correo «por la cola» no llega, aquí suele aparecer el motivo.</small>
    @endif
    <small>«Directo» prueba solo el servidor SMTP. «Por la cola» prueba lo que usan los correos de compras de eventos: debe llegar en menos de un minuto; si no llega, falla el worker o el cron. Revisa también la carpeta de spam. «Correo de entradas» manda la plantilla real que recibe quien compra (logo y QR, con datos de ejemplo del evento más reciente): si «Directo» llega pero este no, el problema está en esa plantilla y no en el servidor.</small>
</div></body></html>
