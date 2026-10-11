@php $site = setting('_general.site_name') ?: config('app.name'); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Verificación de certificados · {{ $site }}</title>
    <meta name="description" content="Comprueba la autenticidad de un certificado emitido por {{ $site }} con su código de verificación.">
    <x-favicon />
    <x-pwa />
    <style>
        :root { --navy: #14213d; --navy-2: #1d2f55; --gold: #fed304; --gold-d: #8a6d00; --paper: #fffdf6; --ink: #1f2937; --muted: #5b6478; --line: #dfe3ec; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f5f8; color: var(--ink); font-family: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif; line-height: 1.6; }
        .serif { font-family: Georgia, "Times New Roman", "Source Serif 4", serif; }
        a { color: var(--navy); }

        .top { background: var(--navy); color: #fff; }
        .top__in { max-width: 1040px; margin: 0 auto; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .top img { height: 40px; width: auto; filter: brightness(0) invert(1); }
        .top a { color: #cfd6e6; text-decoration: none; font-size: .9rem; }
        .top a:hover { color: #fff; text-decoration: underline; }

        .hero { background: linear-gradient(180deg, var(--navy) 0, var(--navy-2) 100%); color: #fff; padding: 48px 24px 120px; text-align: center; }
        .hero__in { max-width: 720px; margin: 0 auto; }
        .seal { width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 50%; display: grid; place-items: center; border: 2px solid var(--gold); color: var(--gold); }
        .kicker { margin: 0 0 8px; letter-spacing: .22em; text-transform: uppercase; font-size: .78rem; color: #c9d2e6; }
        h1 { margin: 0 0 12px; font-size: clamp(1.7rem, 4.5vw, 2.5rem); line-height: 1.2; font-weight: 700; }
        .lead { margin: 0 auto; max-width: 58ch; color: #d6dcec; }

        .card { max-width: 640px; margin: -84px auto 0; padding: 28px; background: #fff; border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 18px 48px rgba(20, 33, 61, .16); }
        .card label { display: block; margin: 0 0 8px; font-weight: 700; color: var(--navy); }
        .field { display: flex; gap: 10px; }
        .field input { flex: 1; min-width: 0; padding: 14px 16px; border: 1.5px solid #c9cfdc; border-radius: 10px; font: 600 1.1rem ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; text-transform: uppercase; letter-spacing: .06em; color: var(--navy); background: #fbfbfd; }
        .field input::placeholder { color: #a3abbd; font-weight: 400; }
        .field input:focus { outline: 3px solid rgba(254, 211, 4, .55); border-color: var(--navy); background: #fff; }
        .field button { padding: 0 26px; border: 0; border-radius: 10px; background: var(--navy); color: #fff; font: 700 1rem inherit; cursor: pointer; }
        .field button:hover { background: var(--navy-2); }
        .hint { margin: 10px 0 0; color: var(--muted); font-size: .88rem; }
        @media (max-width: 520px) { .field { flex-direction: column; } .field button { padding: 14px; } .card { margin-left: 16px; margin-right: 16px; padding: 22px; } }

        main { padding: 0 24px 56px; }
        .sec { max-width: 1040px; margin: 48px auto 0; }
        .sec h2 { margin: 0 0 6px; font-size: 1.5rem; color: var(--navy); }
        .sec > p { margin: 0 0 22px; color: var(--muted); max-width: 70ch; }
        .grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .step { padding: 20px; background: #fff; border: 1px solid var(--line); border-radius: 12px; }
        .step b { display: grid; place-items: center; width: 32px; height: 32px; margin-bottom: 10px; border-radius: 50%; background: var(--navy); color: var(--gold); font-weight: 700; }
        .step h3 { margin: 0 0 4px; font-size: 1.02rem; color: var(--navy); }
        .step p { margin: 0; color: var(--muted); font-size: .94rem; }
        .checks { margin: 0; padding: 0; list-style: none; display: grid; gap: 10px; }
        .checks li { padding-left: 30px; position: relative; }
        .checks li::before { content: "✓"; position: absolute; left: 0; top: 0; width: 22px; height: 22px; border-radius: 50%; background: #e6f4ea; color: #1b6b3a; font-weight: 700; display: grid; place-items: center; font-size: .8rem; }
        .faq details { padding: 14px 18px; background: #fff; border: 1px solid var(--line); border-radius: 10px; margin-bottom: 10px; }
        .faq summary { cursor: pointer; font-weight: 700; color: var(--navy); }
        .faq p { margin: 8px 0 0; color: var(--muted); }

        footer { background: var(--navy); color: #aeb8d0; padding: 28px 24px; font-size: .88rem; }
        footer .in { max-width: 1040px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 8px 24px; justify-content: space-between; }
        footer a { color: #d6dcec; }
    </style>
</head>
<body>
<header class="top">
    <div class="top__in">
        <a href="{{ url('/') }}" aria-label="{{ $site }}"><img src="{{ asset('images/nuevo/logo-horizontia.png') }}" alt="{{ $site }}"></a>
        <a href="{{ url('/') }}">← Volver al sitio</a>
    </div>
</header>

<section class="hero">
    <div class="hero__in">
        <div class="seal" aria-hidden="true">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="m9 9 2 2 4-4"/><path d="M8.5 14.2 7 22l5-3 5 3-1.5-7.8"/></svg>
        </div>
        <p class="kicker">{{ $site }} · Registro de certificados</p>
        <h1 class="serif">Verificar un certificado</h1>
        <p class="lead">Cada certificado que emitimos tiene un código único. Escríbelo aquí para confirmar que es auténtico y consultar a quién se otorgó, por qué curso y cuándo.</p>
    </div>
</section>

<main>
    <form class="card" method="get" action="{{ route('certificates.verify') }}" role="search">
        <label for="codigo">Código de verificación</label>
        <div class="field">
            <input id="codigo" name="codigo" required maxlength="24" placeholder="HZ-XXXX-XXXX-XXXX" autocomplete="off" autocapitalize="characters" spellcheck="false" autofocus>
            <button type="submit">Verificar</button>
        </div>
        <p class="hint">Lo encuentras al pie del certificado, junto al código QR. También puedes escanear el QR directamente.</p>
    </form>

    <section class="sec" aria-labelledby="como">
        <h2 id="como" class="serif">Cómo verificar</h2>
        <p>Tres pasos y en segundos sabrás si el documento que tienes es genuino.</p>
        <div class="grid">
            <div class="step"><b>1</b><h3>Localiza el código</h3><p>Está impreso al pie del certificado y empieza con <strong>HZ-</strong>, seguido de tres bloques de cuatro caracteres.</p></div>
            <div class="step"><b>2</b><h3>Escríbelo arriba</h3><p>No importa si usas mayúsculas o minúsculas. Si escaneas el QR, este paso se hace solo.</p></div>
            <div class="step"><b>3</b><h3>Compara los datos</h3><p>Confirma que el nombre, el curso y la fecha del registro coinciden con el documento que recibiste.</p></div>
        </div>
    </section>

    <section class="sec" aria-labelledby="que">
        <h2 id="que" class="serif">Qué muestra el registro</h2>
        <p>La verificación es pública y solo expone lo necesario para validar el documento.</p>
        <ul class="checks">
            <li>Nombre de la persona a quien se otorgó el certificado.</li>
            <li>Curso o ruta de aprendizaje completada y su duración.</li>
            <li>Fecha de emisión e instructor responsable.</li>
            <li>Estado vigente, o el aviso de que fue <strong>revocado</strong> (con el motivo) si ya no es válido.</li>
        </ul>
    </section>

    <section class="sec faq" aria-labelledby="faq">
        <h2 id="faq" class="serif">Preguntas frecuentes</h2>
        <details><summary>No encuentro mi código o no funciona</summary><p>Revisa que no haya espacios de más y que los caracteres coincidan. Si el problema continúa, escríbenos y lo revisamos.</p></details>
        <details><summary>¿Qué significa que un certificado esté revocado?</summary><p>Que la academia lo dejó sin efecto (por ejemplo, si se emitió por error). La página lo indica con el motivo y deja de ser un comprobante válido.</p></details>
        <details><summary>¿Quién puede consultar un certificado?</summary><p>Cualquier persona con el código, sin necesidad de iniciar sesión. Por eso mostramos únicamente los datos del documento, nunca información de contacto.</p></details>
    </section>
</main>

<footer>
    <div class="in">
        <span>© {{ date('Y') }} {{ $site }}. Todos los derechos reservados.</span>
        <span><a href="{{ url('/') }}">Inicio</a> · <a href="{{ route('courses.search-courses') }}">Cursos</a>@if (Route::has('contacto')) · <a href="{{ route('contacto') }}">Contacto</a>@endif</span>
    </div>
</footer>
</body>
</html>
