<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $cert ? 'Certificado · ' . $cert->student_name : 'Certificado no encontrado' }} · {{ setting('_general.site_name') ?: config('app.name') }}</title>
    <x-favicon />
    <style>
        :root { --navy: #14213d; --gold: #fed304; --paper: #fffdf6; --muted: #5b6478; --ok: #1b6b3a; --bad: #b42318; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f5; color: var(--navy); font-family: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif; }
        .bar { max-width: 1100px; margin: 18px auto 0; padding: 0 16px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
        .status { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; font-weight: 600; font-size: .92rem; background: #eaf7ef; color: var(--ok); }
        .status.is-bad { background: #fdecea; color: var(--bad); }
        .btn { display: inline-block; padding: 10px 18px; border: 0; border-radius: 10px; background: var(--gold); color: var(--navy); font: 700 .95rem inherit; cursor: pointer; text-decoration: none; }
        .btn.ghost { background: #fff; border: 1px solid #d5d9e4; }
        .sheet { position: relative; width: min(1100px, calc(100% - 32px)); aspect-ratio: 297 / 210; margin: 18px auto 32px; padding: 5%; background: var(--paper); border: 14px solid var(--navy); outline: 3px solid var(--gold); outline-offset: -26px; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; box-shadow: 0 10px 40px rgba(20, 33, 61, .18); }
        .sheet img.logo { height: clamp(40px, 7vw, 78px); width: auto; margin-bottom: 1.2%; }
        .kicker { letter-spacing: .28em; text-transform: uppercase; font-size: clamp(.62rem, 1.1vw, .95rem); color: var(--muted); }
        h1 { margin: 1.5% 0 .5%; font-size: clamp(1.4rem, 4vw, 3.1rem); font-weight: 800; letter-spacing: -.01em; }
        .who { margin: 1.6% 0 .6%; font-size: clamp(1.2rem, 3.6vw, 2.8rem); font-weight: 700; border-bottom: 3px solid var(--gold); padding: 0 4% .6%; }
        .what { font-size: clamp(.8rem, 1.6vw, 1.25rem); color: var(--muted); max-width: 78%; line-height: 1.5; margin: 0; }
        .what strong { color: var(--navy); }
        .meta { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; width: 100%; margin-top: 3%; font-size: clamp(.6rem, 1.05vw, .85rem); color: var(--muted); text-align: left; }
        .meta b { display: block; color: var(--navy); font-size: 1.05em; }
        .qr { width: clamp(56px, 9vw, 96px); height: clamp(56px, 9vw, 96px); }
        .stamp { position: absolute; inset: 0; display: grid; place-items: center; font-size: clamp(2rem, 9vw, 6rem); font-weight: 900; color: rgba(180, 35, 24, .22); transform: rotate(-12deg); pointer-events: none; }
        .notfound { max-width: 520px; margin: 12vh auto; padding: 28px; background: #fff; border-radius: 16px; text-align: center; }
        @media print {
            @page { size: A4 landscape; margin: 0; }
            body { background: #fff; }
            .bar { display: none; }
            .sheet { width: 100%; margin: 0; box-shadow: none; aspect-ratio: auto; height: 100vh; }
        }
    </style>
</head>
<body>
@if (!$cert)
    <div class="notfound">
        <h1 style="font-size:1.5rem">No encontramos ese certificado</h1>
        <p style="color:var(--muted)">El código <strong>{{ e(\App\Services\CourseCertificateService::normalizeCode($code)) }}</strong> no corresponde a ningún certificado emitido por {{ setting('_general.site_name') ?: config('app.name') }}. Revisa que esté bien escrito.</p>
        <a class="btn" href="{{ route('certificates.verify') }}">Probar otro código</a>
    </div>
@else
    <div class="bar">
        <span class="status {{ $cert->isValid() ? '' : 'is-bad' }}" data-testid="cert-status">
            {{ $cert->isValid() ? '✓ Certificado auténtico · emitido el ' . dateEs($cert->issued_at) : '✕ Certificado revocado' . ($cert->revoked_reason ? ': ' . $cert->revoked_reason : '') }}
        </span>
        <span>
            <button class="btn" type="button" onclick="window.print()">Imprimir o guardar en PDF</button>
            <a class="btn ghost" href="{{ route('certificates.verify') }}">Verificar otro</a>
        </span>
    </div>

    <article class="sheet" data-testid="certificate" aria-label="Certificado de {{ $cert->student_name }}">
        <img class="logo" src="{{ asset('images/nuevo/logo-horizontia.png') }}" alt="{{ setting('_general.site_name') ?: config('app.name') }}">
        <div class="kicker">{{ setting('_general.site_name') ?: config('app.name') }} certifica que</div>
        <div class="who">{{ $cert->student_name }}</div>
        <p class="what">completó satisfactoriamente {{ $cert->learning_path_id ? 'la ruta de aprendizaje' : 'el curso' }} <strong>{{ $cert->course_title }}</strong>@if ($cert->content_seconds > 0), con una duración de <strong>{{ getCourseDuration($cert->content_seconds) }}</strong> de contenido.@else.@endif</p>
        <div class="meta">
            <div>Fecha de emisión<b>{{ dateEs($cert->issued_at) }}</b></div>
            @if ($cert->instructor_name)<div>Instructor<b>{{ $cert->instructor_name }}</b></div>@endif
            <div>Código de verificación<b>{{ $cert->code }}</b>{{ preg_replace('#^https?://#', '', route('certificates.verify')) }}</div>
            <div id="qr" class="qr" aria-hidden="true"></div>
        </div>
        @unless ($cert->isValid())<div class="stamp" aria-hidden="true">REVOCADO</div>@endunless
    </article>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js" async onload="try{var q=qrcode(0,'M');q.addData({{ \Illuminate\Support\Js::from($cert->url()) }});q.make();document.getElementById('qr').innerHTML=q.createSvgTag({cellSize:3,margin:0,scalable:true});}catch(e){}"></script>
@endif
</body>
</html>
