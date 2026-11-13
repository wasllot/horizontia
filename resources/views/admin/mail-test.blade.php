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
.ok{background:#e8f8ef;color:#126c3b} .bad{background:#fdecea;color:#a12622} small{color:#5b6478;display:block;margin-top:14px}
</style></head>
<body><div class="card">
    <h1>Prueba de correo</h1>
    <p class="lead">Envía un mensaje de prueba a los destinatarios de abajo para confirmar que el servidor de correo y la cola funcionan.</p>

    @if ($results)
        <h2 style="font-size:1.05rem;margin:0 0 10px">Resultado ({{ $mode === 'cola' ? 'por la cola' : 'directo por SMTP' }})</h2>
        <ul class="res" data-testid="mail-test-results">
            @foreach ($results as $r)
                <li class="{{ $r['ok'] ? 'ok' : 'bad' }}">{{ $r['ok'] ? '✅' : '❌' }} <strong>{{ $r['to'] }}</strong> — {{ $r['msg'] }}</li>
            @endforeach
        </ul>
    @endif

    <table>@foreach ($info as $k => $v)<tr><td>{{ $k }}</td><td>{{ $v }}</td></tr>@endforeach</table>

    <form method="post" action="{{ route('admin.mail-test.send') }}" class="btns">
        @csrf
        <button type="submit" name="mode" value="directo">Enviar directo (SMTP)</button>
        <button type="submit" name="mode" value="cola" class="alt">Enviar por la cola</button>
    </form>
    <small>«Directo» prueba solo el servidor SMTP. «Por la cola» prueba lo que usan los correos de compras de eventos: debe llegar en menos de un minuto; si no llega, falla el worker o el cron. Revisa también la carpeta de spam.</small>
</div></body></html>
