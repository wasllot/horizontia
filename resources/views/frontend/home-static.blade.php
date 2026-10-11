@extends('layouts.frontend-app')

@section('content')
    <div class="page-home-page ht-home">
        <!-- Section start -->
        @php
            $requestUrl = \App\Services\AccessRequestService::enabled() ? route('access.request') : route('login');
            $homeStats = \Illuminate\Support\Facades\Cache::remember('home_stats', 600, fn () => [
                'courses' => \Modules\Courses\Models\Course::where('status', \Modules\Courses\Models\Course::STATUSES['active'])->count(),
                'instructors' => \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'tutor'))->count(),
            ]);
        @endphp
        <!-- Hero Section -->
        <section class="am-hero-section ht-hero" style="background-image: url('{{ webpAsset('images/nuevo/Slider-horizontia.jpg') }}');">
            <style>
                .ht-hero { position: relative; display: flex; align-items: center; min-height: 600px; background-size: cover; background-position: center; background-repeat: no-repeat; overflow: hidden; }
                .ht-hero::before { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(0, 0, 0, .38) 0%, rgba(0, 0, 0, .12) 48%, rgba(0, 0, 0, 0) 70%); pointer-events: none; }
                .ht-hero .container { position: relative; z-index: 1; }
                .ht-hero__content { color: #fff; text-align: left; padding: 56px 0 64px; }
                .ht-hero__kicker { margin: 0 0 10px; font-size: 1.5rem; font-weight: 300; color: #fed304; }
                .ht-hero h1 { margin: 0 0 20px; color: #fff; font-size: clamp(2.3rem, 5.2vw, 3.5rem); font-weight: 800; line-height: 1.2; }
                .ht-hero h1 em { font-style: normal; color: #fed304; position: relative; white-space: nowrap; }
                .ht-hero h1 em::after { content: ""; position: absolute; left: 0; right: 0; bottom: 2px; height: 5px; border-radius: 4px; background: #fed304; opacity: .55; transform: scaleX(0); transform-origin: left; animation: ht-underline .9s .9s cubic-bezier(.2, .8, .2, 1) forwards; }
                .ht-hero__lead { margin: 0 0 30px; max-width: 34em; font-size: 1.1rem; line-height: 1.6; color: #fff; }
                .ht-hero__btns { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 14px; margin-bottom: 18px; }
                .ht-hero__btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 30px; border-radius: 50px; font-weight: 700; text-decoration: none; transition: transform .2s, box-shadow .2s, background .2s; }
                .ht-hero__btn svg { transition: transform .25s; }
                .ht-hero__btn:hover svg { transform: translateX(4px); }
                .ht-hero__btn--main { background: #fed304; color: #000 !important; }
                .ht-hero__btn--main:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(254, 211, 4, .35); background: #ffdd2e; }
                .ht-hero__btn--ghost { background: rgba(255, 255, 255, .06); color: #fff !important; border: 2px solid rgba(255, 255, 255, .7); padding: 10px 26px; }
                .ht-hero__btn--ghost:hover { background: rgba(255, 255, 255, .16); transform: translateY(-2px); }
                .ht-hero__how { color: #fff; font-weight: 600; text-decoration: underline; text-underline-offset: 4px; }
                .ht-hero__how:hover { color: #fed304; }
                .ht-hero__proof { display: flex; flex-wrap: wrap; gap: 8px 26px; margin: 26px 0 0; padding: 18px 0 0; border-top: 1px solid rgba(255, 255, 255, .22); max-width: 34em; list-style: none; }
                .ht-hero__proof li { list-style: none !important; color: #d9deea; font-size: .95rem; } .ht-hero__proof li::marker { content: none; }
                .ht-hero__proof b { color: #fff; font-size: 1.25rem; margin-right: 4px; font-family: Georgia, serif; }
                .ht-hero__cue { position: absolute; left: 50%; bottom: 18px; z-index: 2; transform: translateX(-50%); width: 40px; height: 40px; display: grid; place-items: center; border-radius: 50%; border: 2px solid rgba(255, 255, 255, .55); color: #fff; text-decoration: none; animation: ht-bounce 2.2s ease-in-out infinite; transition: background .2s, border-color .2s; }
                .ht-hero__cue:hover { background: #fed304; border-color: #fed304; color: #000; }
                /* Entrada escalonada del texto */
                .ht-hero__content > * { opacity: 0; transform: translateY(22px); animation: ht-hero-in .8s cubic-bezier(.2, .8, .2, 1) forwards; }
                .ht-hero__content > :nth-child(1) { animation-delay: .05s; } .ht-hero__content > :nth-child(2) { animation-delay: .18s; } .ht-hero__content > :nth-child(3) { animation-delay: .32s; }
                .ht-hero__content > :nth-child(4) { animation-delay: .46s; } .ht-hero__content > :nth-child(5) { animation-delay: .6s; }
                .ht-hero { animation: ht-bg-in 1.4s ease-out both; }
                @keyframes ht-hero-in { to { opacity: 1; transform: none; } }
                @keyframes ht-underline { to { transform: scaleX(1); } }
                @keyframes ht-bounce { 0%, 100% { transform: translate(-50%, 0); } 50% { transform: translate(-50%, 7px); } }
                @keyframes ht-bg-in { from { background-position: 54% center; } to { background-position: center; } }
                @media (prefers-reduced-motion: reduce) { .ht-hero, .ht-hero__content > *, .ht-hero h1 em::after, .ht-hero__cue { animation: none !important; opacity: 1 !important; transform: none !important; } .ht-hero h1 em::after { transform: scaleX(1) !important; } .ht-hero__cue { transform: translateX(-50%) !important; } }
                @media (max-width: 767px) { .ht-hero { min-height: 560px; } .ht-hero::before { background: linear-gradient(180deg, rgba(0, 0, 0, .55), rgba(0, 0, 0, .25)); } .ht-hero__lead { max-width: none; } .ht-hero__cue { display: none; } }
            </style>
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-10">
                        <div class="am-hero-content ht-hero__content">
                            <h3 class="ht-hero__kicker">Con Horizontia, empodera tu futuro</h3>
                            <h1>Aprende hoy para un futuro más <em>brillante</em></h1>
                            @if (isPrivateAcademy())
                                <p class="ht-hero__lead">Cursos en línea con instructores expertos: aprende a tu ritmo, resuelve evaluaciones, obtén tu certificado y recibe ayuda de un tutor con IA.</p>
                                <div class="ht-hero__btns">
                                    <a href="{{ route('courses.search-courses') }}" class="am-btn ht-hero__btn ht-hero__btn--main" style="background-color: #fed304; color: #000;">VER CURSOS <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                                    @if (\App\Services\AccessRequestService::enabled())
                                        <a href="{{ $requestUrl }}" class="ht-hero__btn ht-hero__btn--ghost">Solicitar acceso</a>
                                    @endif
                                    <a href="{{ route('how-it-works') }}" class="ht-hero__how">Cómo funciona</a>
                                </div>
                                <ul class="ht-hero__proof" aria-label="La academia en cifras">
                                    <li><b>{{ $homeStats['courses'] }}</b>cursos</li>
                                    <li><b>{{ $homeStats['instructors'] }}</b>instructores</li>
                                    <li><b>100&nbsp;%</b>a tu ritmo</li>
                                </ul>
                            @else
                                <p class="ht-hero__lead">Alcanza tus metas con tutorías personalizadas de los mejores expertos. Conéctate con tutores dedicados para alcanzar el éxito.</p>
                                <div class="ht-hero__btns"><a href="#" class="am-btn ht-hero__btn ht-hero__btn--main" style="background-color: #fed304; color: #000;">CONTÁCTANOS</a></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <a class="ht-hero__cue" href="#pasos" aria-label="Ir a la siguiente sección"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></a>
        </section>

        <!-- Banner Strip -->
        <div class="am-banner-strip" style="background-color: #e0e0e0; padding: 10px 0; text-align: center;">
            <p
                style="margin: 0; font-weight: 600; color: #333; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">
                Conciencia humana con talento</p>
        </div>

        @if (isPrivateAcademy())
        <!-- Trust strip -->
        <style>
            .ht-trust { background: #fff; border-bottom: 1px solid #eef0f4; }
            .ht-trust__list { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px 36px; margin: 0; padding: 16px 0; list-style: none; }
            .ht-trust__list li { display: flex; align-items: center; gap: 8px; color: #14213d; font-size: .95rem; font-weight: 600; }
            .ht-trust__list svg { flex: 0 0 18px; color: #17b26a; }
        </style>
        <div class="ht-trust">
            <div class="container">
                <ul class="ht-trust__list">
                    @foreach (['Certificado al terminar', 'Garantía de reembolso de ' . config('academy.refund_days', 7) . ' días', 'Pagos seguros con Mercado Pago', 'Aprende a tu ritmo'] as $point)
                        <li><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>{{ $point }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @php
            $steps = [
                ['n' => '1', 'title' => 'Solicita tu acceso', 'text' => 'Cuéntanos si quieres aprender o enseñar y, al aprobar tu solicitud, te creamos tu cuenta.', 'cta' => 'Solicitar', 'href' => $requestUrl, 'img' => 'storage/optionbuilder/uploads/912805-12-2025_1226pmfoto-seguidas-1.jpg'],
                ['n' => '2', 'title' => 'Explora los cursos', 'text' => 'Revisa el catálogo y elige el curso que mejor se ajuste a lo que necesitas.', 'cta' => 'Ver cursos', 'href' => route('courses.search-courses'), 'img' => 'storage/optionbuilder/uploads/793305-12-2025_1240pmfoto-seguidas-2.jpg'],
                ['n' => '3', 'title' => 'Inscríbete y paga', 'text' => 'Con tu cuenta activa, inscríbete y paga con tarjeta, OXXO o transferencia: el acceso es inmediato.', 'cta' => 'Iniciar sesión', 'href' => route('login'), 'img' => 'storage/optionbuilder/uploads/835805-12-2025_1243pmfoto-seguidas-3.jpg'],
            ];
        @endphp

        <!-- Pasos -->
        <section class="am-steps-section ht-steps2" id="pasos" aria-labelledby="ht-steps-title">
            <style>
                .ht-steps2 { padding: 90px 0 80px; background: #f9f9f9; overflow: hidden; }
                .ht-steps2__head { text-align: center; margin: 0 auto 54px; max-width: 720px; padding: 0 16px; }
                .ht-steps2__kicker { display: inline-block; margin-bottom: 10px; font-size: .8rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #8a6d00; }
                .ht-steps2__head h2 { margin: 0 0 12px; font-size: clamp(1.9rem, 3.8vw, 2.7rem); line-height: 1.15; color: #14213d; }
                .ht-steps2__head p { margin: 0; color: #5b6478; font-size: 1.08rem; }
                .ht-steps2__grid { position: relative; list-style: none; margin: 0 auto; padding: 0 20px; max-width: 1240px; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 24px; }
                .ht-steps2__grid::before { content: ""; position: absolute; left: 12%; right: 12%; top: 112px; border-top: 2px dashed #e3c768; opacity: .7; z-index: 0; }
                .ht-steps2__grid > li { list-style: none !important; } .ht-steps2__grid > li::marker { content: none; }
                .ht-step { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; text-align: center; height: 100%; padding: 34px 22px 28px; background: #fff; border-radius: 22px; box-shadow: 0 10px 30px rgba(20, 33, 61, .06); color: inherit; text-decoration: none; transition: transform .35s cubic-bezier(.2, .8, .2, 1), box-shadow .35s; }
                .ht-step:hover, .ht-step:focus-visible { transform: translateY(-8px); box-shadow: 0 22px 48px rgba(20, 33, 61, .14); }
                .ht-step:focus-visible { outline: 3px solid #F4C430; outline-offset: 3px; }
                .ht-step__num { position: absolute; top: -16px; left: 50%; transform: translateX(-50%); width: 34px; height: 34px; border-radius: 50%; background: #14213d; color: #F4C430; font-weight: 800; display: grid; place-items: center; box-shadow: 0 0 0 5px #f9f9f9; }
                .ht-step__img { width: 88px; height: 88px; margin-bottom: 20px; border-radius: 50%; overflow: hidden; box-shadow: 0 6px 18px rgba(0, 0, 0, .12); }
                .ht-step__img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .5s; }
                .ht-step:hover .ht-step__img img { transform: scale(1.12); }
                .ht-step h3 { margin: 0 0 10px; font-size: 1.2rem; color: #14213d; }
                .ht-step p { margin: 0 0 20px; color: #5b6478; font-size: .98rem; line-height: 1.55; flex: 1; }
                .ht-step__cta { display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: .88rem; letter-spacing: .05em; text-transform: uppercase; color: #14213d; border-bottom: 2px solid #F2D07F; padding-bottom: 2px; }
                .ht-step__cta svg { transition: transform .25s; }
                .ht-step:hover .ht-step__cta svg { transform: translateX(5px); }
                .ht-step--gold { background: linear-gradient(145deg, #F4C430 0%, #f7d35c 100%); box-shadow: 0 16px 36px rgba(244, 196, 48, .35); justify-content: center; }
                .ht-step--gold:hover { transform: translateY(-8px); box-shadow: 0 24px 50px rgba(244, 196, 48, .5); }
                .ht-step--gold .ht-step__num { background: #000; color: #F4C430; }
                .ht-step--gold h3, .ht-step--gold p { color: #000; }
                .ht-step__icon { width: 88px; height: 88px; margin-bottom: 20px; border-radius: 50%; background: rgba(255, 255, 255, .35); display: grid; place-items: center; }
                .ht-step__btn { display: inline-block; padding: 12px 26px; border-radius: 999px; background: #000; color: #fff !important; font-weight: 700; text-decoration: none; transition: transform .2s, box-shadow .2s; }
                .ht-step__btn:hover { transform: scale(1.05); box-shadow: 0 8px 20px rgba(0, 0, 0, .3); }
                .ht-step__sub { margin-top: 12px; font-size: .88rem; font-weight: 600; color: #000; text-decoration: underline; }
                @media (max-width: 1100px) { .ht-steps2__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 40px; } .ht-steps2__grid::before { display: none; } }
                @media (max-width: 560px) { .ht-steps2__grid { grid-template-columns: 1fr; } }
            </style>
            <div class="ht-steps2__head ht-reveal">
                <span class="ht-steps2__kicker">Guía paso a paso</span>
                <h2 id="ht-steps-title">Desbloquea tu potencial con sencillos pasos</h2>
                <p>Del primer clic a tu primera lección, en cuatro pasos claros.</p>
            </div>
            <ol class="ht-steps2__grid">
                @foreach ($steps as $i => $st)
                    <li class="ht-reveal" style="--d: {{ $i * 0.12 }}s">
                        <a class="ht-step" href="{{ $st['href'] }}">
                            <span class="ht-step__num" aria-hidden="true">{{ $st['n'] }}</span>
                            <span class="ht-step__img"><img src="{{ webpAsset($st['img']) }}" {!! imgAttrs($st['img']) !!} alt="" loading="lazy"></span>
                            <h3>{{ $st['title'] }}</h3>
                            <p>{{ $st['text'] }}</p>
                            <span class="ht-step__cta">{{ $st['cta'] }} <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                        </a>
                    </li>
                @endforeach
                <li class="ht-reveal" style="--d: .36s">
                    <div class="ht-step ht-step--gold">
                        <span class="ht-step__num" aria-hidden="true">4</span>
                        <span class="ht-step__icon" aria-hidden="true"><svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="m3 13 9 5 9-5"/><path d="m3 17.5 9 5 9-5" opacity=".5"/></svg></span>
                        <h3>Comienza tu viaje</h3>
                        <p>{{ isPrivateAcademy() ? 'Solicita tu acceso, elige tu curso y empieza a aprender a tu ritmo.' : '¡Encuentra tu curso y reserva tu primera sesión hoy mismo!' }}</p>
                        <a class="ht-step__btn" href="{{ $requestUrl }}">Solicitar acceso</a>
                        <a class="ht-step__sub" href="{{ route('login') }}">Ya tengo cuenta</a>
                    </div>
                </li>
            </ol>
        </section>

        <!-- Qué ofrece Horizontia -->
        <section class="am-about-section ht-about" style="background-image: url('{{ webpAsset('images/nuevo/fondo-horizontia-amarillo.jpg') }}');">
            <style>
                .ht-about { padding: 100px 0; background-size: cover; background-position: center; overflow: hidden; }
                .ht-about__wrap { max-width: 1240px; margin: 0 auto; padding: 0 20px; display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); gap: clamp(24px, 4vw, 56px); align-items: center; }
                .ht-about__card { padding: clamp(28px, 4vw, 48px); border-radius: 26px; background: rgba(255, 255, 255, .94); box-shadow: 0 24px 60px rgba(20, 33, 61, .18); backdrop-filter: blur(4px); }
                .ht-about__card h2 { margin: 0 0 14px; font-size: clamp(1.9rem, 3.6vw, 2.6rem); color: #14213d; }
                .ht-about__lead { margin: 0 0 26px; font-size: 1.1rem; line-height: 1.65; color: #374151; }
                .ht-about__list { list-style: none; margin: 0 0 28px; padding: 0; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 22px; }
                .ht-about__list li { display: flex; gap: 14px; align-items: flex-start; }
                .ht-about__ico { flex: none; width: 44px; height: 44px; border-radius: 14px; background: #F4C430; display: grid; place-items: center; transition: transform .3s; }
                .ht-about__list li:hover .ht-about__ico { transform: rotate(-8deg) scale(1.1); }
                .ht-about__list strong { display: block; color: #14213d; margin-bottom: 2px; }
                .ht-about__list div > span { display: block; color: #5b6478; font-size: .93rem; line-height: 1.4 !important; }
                .ht-about__cta { display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; border-radius: 999px; background: #000; color: #fff !important; font-weight: 700; text-decoration: none; transition: transform .2s, box-shadow .2s; }
                .ht-about__cta:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(0, 0, 0, .3); }
                .ht-about__cta svg { transition: transform .25s; } .ht-about__cta:hover svg { transform: translateX(4px); }
                .ht-about__media { position: relative; }
                .ht-about__media figure { margin: 0; border-radius: 26px; overflow: hidden; box-shadow: 0 26px 60px rgba(0, 0, 0, .25); aspect-ratio: 1 / 1; }
                .ht-about__media img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .8s ease; }
                .ht-about__media:hover img { transform: scale(1.04); }
                .ht-about__chip { position: absolute; display: flex; align-items: center; gap: 10px; padding: 10px 16px 10px 12px; border-radius: 16px; background: #fff; box-shadow: 0 14px 30px rgba(20, 33, 61, .22); font-weight: 700; color: #14213d; font-size: .92rem; animation: ht-float 5s ease-in-out infinite; }
                .ht-about__chip i { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: #14213d; color: #F4C430; font-style: normal; }
                .ht-about__chip--a { left: -22px; bottom: 56px; } .ht-about__chip--b { right: -14px; top: 40px; animation-delay: -2.2s; }
                .ht-about__stats { display: flex; flex-wrap: wrap; gap: 28px; margin: 0 0 28px; padding: 18px 0 0; border-top: 1px solid #e5e7eb; }
                .ht-about__stats b { display: block; font-size: 1.9rem; line-height: 1; color: #14213d; font-family: Georgia, serif; }
                .ht-about__stats span { color: #5b6478; font-size: .9rem; }
                @keyframes ht-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
                @media (max-width: 960px) { .ht-about__wrap { grid-template-columns: 1fr; } .ht-about__chip--a { left: 12px; } .ht-about__chip--b { right: 12px; } }
                @media (max-width: 560px) { .ht-about__list { grid-template-columns: 1fr; } }
            </style>
            <div class="ht-about__wrap">
                <div class="ht-about__card ht-reveal">
                    <h2>Qué ofrece Horizontia</h2>
                    <p class="ht-about__lead">{{ isPrivateAcademy() ? 'Nuestra misión es ayudarte a alcanzar tus objetivos académicos y profesionales con cursos prácticos creados por instructores especialistas.' : 'Nuestra misión es conectarte con tutores expertos para que alcances tus objetivos.' }} Una plataforma intuitiva donde encuentras:</p>
                    <ul class="ht-about__list">
                        @php
                            $ico = fn ($d) => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#14213d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
                            $features = isPrivateAcademy() ? [
                                ['Cursos a tu ritmo', 'Videos, lecturas y ejercicios disponibles cuando tú quieras.', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
                                ['Evaluaciones y certificado', 'Mide tu avance y obtén un certificado verificable al terminar.', '<circle cx="12" cy="9" r="6"/><path d="M8.5 14.2 7 22l5-3 5 3-1.5-7.8"/>'],
                                ['Tutor con IA', 'Resuelve dudas de cada lección con pistas y explicaciones.', '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/>'],
                                ['Pagos seguros', 'Paga con Mercado Pago: tarjeta, efectivo o transferencia.', '<rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0 1 10 0v3"/>'],
                            ] : [
                                ['Tutorías 1 a 1', 'Sesiones personalizadas adaptadas a tu ritmo.', '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
                                ['Gran variedad de temas', 'Desde idiomas hasta desarrollo profesional.', '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>'],
                                ['Horarios flexibles', 'Agenda sesiones cuando más te convenga.', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
                                ['Plataforma segura', 'Pagos seguros y verificados.', '<rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0 1 10 0v3"/>'],
                            ];
                        @endphp
                        @foreach ($features as $i => [$t, $d, $svg])
                            <li class="ht-reveal" style="--d: {{ 0.1 + $i * 0.1 }}s"><span class="ht-about__ico">{!! $ico($svg) !!}</span><div><strong>{{ $t }}</strong><span>{{ $d }}</span></div></li>
                        @endforeach
                    </ul>
                    <div class="ht-about__stats">
                        <div><b data-count="{{ max(1, (int) $homeStats['courses']) }}">{{ $homeStats['courses'] }}</b><span>cursos disponibles</span></div>
                        <div><b data-count="{{ max(1, (int) $homeStats['instructors']) }}">{{ $homeStats['instructors'] }}</b><span>instructores</span></div>
                        <div><b>100&nbsp;%</b><span>a tu ritmo</span></div>
                    </div>
                    <a class="ht-about__cta" href="{{ $requestUrl }}">{{ \App\Services\AccessRequestService::enabled() ? 'Solicitar acceso' : 'Únete ahora' }} <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>
                <div class="ht-about__media ht-reveal" style="--d: .15s">
                    <figure><img src="{{ webpAsset('images/nuevo/que-ofrece-horizontia.jpg') }}" {!! imgAttrs('images/nuevo/que-ofrece-horizontia.jpg') !!} alt="Estudiantes conversando y sonriendo" loading="lazy"></figure>
                    <div class="ht-about__chip ht-about__chip--a"><i>★</i> Certificado al terminar</div>
                    <div class="ht-about__chip ht-about__chip--b"><i>✓</i> Pago 100&nbsp;% seguro</div>
                </div>
            </div>
        </section>

        <!-- Soporte y expertos: dos filas alternadas con imágenes del mismo tamaño -->
        <section class="am-support-section ht-split">
            <style>
                .ht-split__row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: clamp(28px, 5vw, 72px); align-items: center; }
                .ht-split__row + .ht-split__row { margin-top: clamp(56px, 8vw, 100px); }
                .ht-split__row--flip .ht-split__media { order: 2; }
                .ht-split__media { position: relative; margin: 0; border-radius: 24px; overflow: hidden; box-shadow: 0 22px 50px rgba(20, 33, 61, .14); aspect-ratio: 4 / 3; background: #eef0f6; }
                .ht-split__media img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .6s ease; }
                .ht-split__row:hover .ht-split__media img { transform: scale(1.03); }
                .ht-split__media::after { content: ""; position: absolute; left: 18px; bottom: 18px; width: 64px; height: 6px; border-radius: 6px; background: #F4C430; }
                .ht-split__kicker { display: inline-block; margin-bottom: 10px; font-size: .8rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #8a6d00; }
                .ht-split h2 { margin: 0 0 16px; font-size: clamp(1.8rem, 3.4vw, 2.6rem); line-height: 1.15; color: #14213d; }
                .ht-split__lead { margin: 0 0 22px; font-size: 1.12rem; line-height: 1.65; color: #4b5563; max-width: 54ch; }
                .ht-split__list { list-style: none; margin: 0 0 26px; padding: 0; display: grid; gap: 12px; }
                .ht-split__list li { list-style: none !important; position: relative; padding-left: 36px; color: #1f2937; font-weight: 500; line-height: 1.45; }
                .ht-split__list li::before { content: "✓"; position: absolute; left: 0; top: -1px; width: 24px; height: 24px; border-radius: 50%; background: #F4C430; color: #14213d; font-weight: 800; font-size: .85rem; display: grid; place-items: center; }
                .ht-split__list li::marker, .ht-split__list li::after { content: none !important; }
                .ht-split__cta { display: inline-block; color: #14213d; font-weight: 700; text-decoration: none; border-bottom: 2px solid #F2D07F; padding-bottom: 2px; }
                .ht-split__cta:hover { border-bottom-color: #14213d; }
                @media (max-width: 860px) { .ht-split__row { grid-template-columns: 1fr; } .ht-split__row--flip .ht-split__media { order: 0; } }
            </style>
            <div class="container">
                <div class="ht-split__row ht-reveal">
                    <figure class="ht-split__media">
                        <img src="{{ webpAsset('storage/optionbuilder/uploads/430205-12-2025_0744pmcomputer.jpg') }}" {!! imgAttrs('storage/optionbuilder/uploads/430205-12-2025_0744pmcomputer.jpg') !!} alt="Equipo de soporte atendiendo a un estudiante" loading="lazy">
                    </figure>
                    <div>
                        <span class="ht-split__kicker">Acompañamiento</span>
                        <h2>Soporte integral en cada paso</h2>
                        <p class="ht-split__lead">Nuestro equipo está dedicado a que tu experiencia sea fluida y exitosa: desde un problema técnico hasta una duda sobre el contenido de tu curso.</p>
                        <ul class="ht-split__list">
                            <li>Mensajes directos con tu instructor y con soporte, sin salir de la plataforma</li>
                            <li>Tutor con IA dentro de cada lección, disponible a cualquier hora</li>
                            <li>Comunidad por curso para resolver dudas con otros estudiantes</li>
                        </ul>
                        <a class="ht-split__cta" href="{{ route('contacto') }}">Escríbenos →</a>
                    </div>
                </div>

                <div class="ht-split__row ht-split__row--flip ht-reveal">
                    <figure class="ht-split__media">
                        <img src="{{ webpAsset('images/nuevo/instructores-horizontia.jpg') }}" {!! imgAttrs('images/nuevo/instructores-horizontia.jpg') !!} alt="Una instructora presenta una lección a su equipo en una sala luminosa" loading="lazy">
                    </figure>
                    <div>
                        <span class="ht-split__kicker">Instructores</span>
                        <h2>Un equipo de expertos te guiará</h2>
                        <p class="ht-split__lead">Contamos con instructores y mentores altamente calificados, seleccionados con rigor para ofrecerte la mejor educación. Aprende de profesionales con años de experiencia en su campo.</p>
                        <ul class="ht-split__list">
                            <li>Cada instructor es aprobado por nuestro equipo antes de publicar</li>
                            <li>Cursos en línea, sesiones en vivo y talleres presenciales</li>
                            <li>Certificado verificable al terminar cada curso</li>
                        </ul>
                        <a class="ht-split__cta" href="{{ \App\Services\AccessRequestService::enabled() ? route('access.request', ['como' => 'instructor']) : route('contacto') }}">Quiero enseñar en Horizontia →</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cursos Horizontia: carrusel -->
        @php
            $homeCourses = \Modules\Courses\Models\Course::with([
                'thumbnail',
                'instructor.profile:id,user_id,first_name,last_name',
                'category:id,name',
                'pricing',
            ])->where('status', \Modules\Courses\Models\Course::STATUSES['active'])
              ->where('delivery', '!=', 'in_person')   // los presenciales se listan en Eventos
              ->withAvg('ratings', 'rating')
              ->withCount('enrollments')
              ->latest()->take(12)->get();
        @endphp
        <section class="ht-cc" aria-labelledby="ht-cc-title" style="background-image: url('{{ asset('images/design/new-sunburst-bg.jpg') }}');">
            <style>
                .ht-cc { background-color: #1a1a1a; background-size: cover; background-position: center; padding: 84px 0 90px; color: #fff; }
                .ht-cc__in { max-width: 1320px; margin: 0 auto; padding: 0 24px; }
                .ht-cc__head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 18px 30px; margin-bottom: 34px; }
                .ht-cc__kicker { display: block; margin-bottom: 8px; font-size: .8rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #F4C430; }
                .ht-cc__head h2 { margin: 0 0 8px; color: #fff; font-size: clamp(2rem, 4vw, 3rem); line-height: 1.1; text-shadow: 0 2px 8px rgba(0, 0, 0, .4); }
                .ht-cc__head p { margin: 0; max-width: 52ch; color: #d7dcea; font-size: 1.05rem; }
                .ht-cc__all { display: inline-flex; align-items: center; gap: 8px; padding: 12px 26px; border-radius: 999px; background: #F4C430; color: #14213d !important; font-weight: 700; text-decoration: none; white-space: nowrap; transition: transform .2s, box-shadow .2s; }
                .ht-cc__all:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(244, 196, 48, .35); }
                .ht-cc__all svg { transition: transform .25s; } .ht-cc__all:hover svg { transform: translateX(4px); }

                .ht-cc__stage { position: relative; }
                .ht-cc__track { display: flex; gap: 22px; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; padding: 6px 4px 22px; scrollbar-width: none; -ms-overflow-style: none; }
                .ht-cc__track::-webkit-scrollbar { display: none; }
                .ht-cc__item { flex: 0 0 calc((100% - 66px) / 4); min-width: 250px; scroll-snap-align: start; }
                @media (max-width: 1280px) { .ht-cc__item { flex-basis: calc((100% - 44px) / 3); } }
                @media (max-width: 900px) { .ht-cc__item { flex-basis: calc((100% - 22px) / 2); } }
                @media (max-width: 560px) { .ht-cc__item { flex-basis: 82%; } }

                .ht-cc__card { display: flex; flex-direction: column; height: 100%; border-radius: 20px; overflow: hidden; background: #fff; color: #14213d; text-decoration: none; box-shadow: 0 10px 30px rgba(0, 0, 0, .28); transition: transform .35s cubic-bezier(.2, .8, .2, 1), box-shadow .35s; }
                .ht-cc__card:hover, .ht-cc__card:focus-visible { transform: translateY(-8px); box-shadow: 0 22px 46px rgba(0, 0, 0, .4); }
                .ht-cc__card:focus-visible { outline: 3px solid #F4C430; outline-offset: 3px; }
                .ht-cc__media { position: relative; aspect-ratio: 16 / 9; overflow: hidden; background: #e8edf5; }
                .ht-cc__media img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .6s ease; }
                .ht-cc__card:hover .ht-cc__media img { transform: scale(1.07); }
                .ht-cc__chip { position: absolute; top: 12px; left: 12px; max-width: calc(100% - 24px); padding: 5px 12px; border-radius: 999px; background: rgba(255, 255, 255, .95); color: #14213d; font-size: .72rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; box-shadow: 0 2px 8px rgba(0, 0, 0, .15); }
                .ht-cc__chip--live { top: auto; bottom: 12px; left: 12px; background: #e0361f; color: #fff; }
                .ht-cc__body { display: flex; flex-direction: column; flex: 1; padding: 18px 18px 16px; }
                .ht-cc__body h3 { margin: 0 0 8px; font-size: 1.08rem; line-height: 1.3; color: #14213d; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.6em; }
                .ht-cc__who { display: flex; align-items: center; gap: 8px; margin: 0 0 10px; font-size: .86rem; color: #6b7280; }
                .ht-cc__avatar { flex: none; width: 24px; height: 24px; border-radius: 50%; background: #14213d; color: #F4C430; display: grid; place-items: center; font-size: .72rem; font-weight: 800; }
                .ht-cc__meta { display: flex; flex-wrap: wrap; gap: 6px 12px; align-items: center; min-height: 22px; margin: 0 0 14px; font-size: .84rem; color: #6b7280; }
                .ht-cc__stars { display: inline-flex; align-items: center; gap: 4px; font-weight: 700; color: #14213d; }
                .ht-cc__foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: auto; padding-top: 12px; border-top: 1px solid #eef0f5; }
                .ht-cc__price { font-size: 1.25rem; font-weight: 800; color: #14213d; font-family: Georgia, serif; }
                .ht-cc__price--free { color: #11935a; }
                .ht-cc__price s { display: block; font-size: .78rem; font-weight: 400; color: #9ca3af; font-family: inherit; }
                .ht-cc__go { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; background: #fff3c4; color: #14213d; font-size: .82rem; font-weight: 700; transition: background .25s, gap .25s; }
                .ht-cc__card:hover .ht-cc__go { background: #F4C430; gap: 10px; }

                .ht-cc__arrow { position: absolute; top: 40%; z-index: 5; width: 48px; height: 48px; border: 0; border-radius: 50%; background: #fff; color: #14213d; cursor: pointer; display: grid; place-items: center; box-shadow: 0 8px 24px rgba(0, 0, 0, .35); transition: background .2s, transform .2s, opacity .2s; }
                .ht-cc__arrow:hover { background: #F4C430; transform: scale(1.08); }
                .ht-cc__arrow--prev { left: -16px; } .ht-cc__arrow--next { right: -16px; }
                @media (max-width: 1400px) { .ht-cc__arrow--prev { left: 4px; } .ht-cc__arrow--next { right: 4px; } }
                @media (max-width: 560px) { .ht-cc__arrow { display: none; } }
                .ht-cc__dots { display: flex; justify-content: center; gap: 8px; margin-top: 6px; }
                .ht-cc__dots button { width: 9px; height: 9px; padding: 0; border: 0; border-radius: 999px; background: rgba(255, 255, 255, .35); cursor: pointer; transition: width .3s, background .3s; }
                .ht-cc__dots button.is-on { width: 28px; background: #F4C430; }
            </style>
            <div class="ht-cc__in">
                <div class="ht-cc__head ht-reveal">
                    <div>
                        <span class="ht-cc__kicker">Catálogo</span>
                        <h2 id="ht-cc-title">Cursos Horizontia</h2>
                        <p>Cursos prácticos de instructores especialistas: en línea, en vivo o con certificado al terminar.</p>
                    </div>
                    <a class="ht-cc__all" href="{{ route('courses.search-courses') }}">Ver todos los cursos <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>

                @if ($homeCourses->isNotEmpty())
                    <div class="ht-cc__stage ht-reveal" style="--d: .1s"
                         x-data="{ track: null, canPrev: false, canNext: true, pages: 1, page: 0,
                            init() { this.track = this.$refs.track; this.track.addEventListener('scroll', () => this.sync(), { passive: true }); window.addEventListener('resize', () => this.sync()); this.sync(); },
                            sync() { const t = this.track; this.canPrev = t.scrollLeft > 8; this.canNext = t.scrollLeft < t.scrollWidth - t.clientWidth - 8; this.pages = Math.max(1, Math.ceil(t.scrollWidth / t.clientWidth)); this.page = Math.min(this.pages - 1, Math.round(t.scrollLeft / Math.max(1, t.scrollWidth - t.clientWidth) * (this.pages - 1))); },
                            go(d) { this.track.scrollBy({ left: d * this.track.clientWidth * 0.85 }); },
                            to(i) { this.track.scrollTo({ left: i * (this.track.scrollWidth - this.track.clientWidth) / Math.max(1, this.pages - 1) }); } }"
                         @keydown.arrow-left.prevent="go(-1)" @keydown.arrow-right.prevent="go(1)">
                        <button type="button" class="ht-cc__arrow ht-cc__arrow--prev" x-show="canPrev" x-cloak @click="go(-1)" aria-label="Cursos anteriores"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>

                        <div class="ht-cc__track" x-ref="track" role="list" tabindex="0" aria-label="Cursos disponibles">
                            @foreach ($homeCourses as $course)
                                @php
                                    $avg = round($course->ratings_avg_rating ?? 0, 1);
                                    $price = (float) ($course->pricing?->final_price ?? $course->pricing?->price ?? 0);
                                    $list = (float) ($course->pricing?->price ?? 0);
                                    $instructor = $course->instructor?->profile?->full_name ?: 'Instructor';
                                    $isLive = str_contains(mb_strtolower((string) $course->title), 'en vivo');
                                @endphp
                                <div class="ht-cc__item" role="listitem">
                                    <a class="ht-cc__card" href="{{ route('courses.course-detail', $course->slug) }}">
                                        <div class="ht-cc__media">
                                            @if (!empty($course->thumbnail?->path) && mediaExists($course->thumbnail->path))
                                                <img loading="lazy" decoding="async" width="640" height="360" src="{{ mediaThumb($course->thumbnail->path, 640) }}" alt="">
                                            @else
                                                <div style="position:absolute;inset:0;display:grid;place-items:center;background:linear-gradient(135deg,#14213d,#1a3a6b);"><svg width="48" height="48" viewBox="0 0 24 24" fill="#fff" style="opacity:.35" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-2 14.5v-9l6 4.5-6 4.5z"/></svg></div>
                                            @endif
                                            @if (!empty($course->category?->name))<span class="ht-cc__chip">{{ $course->category->name }}</span>@endif
                                            @if ($isLive)<span class="ht-cc__chip ht-cc__chip--live">● En vivo</span>@endif
                                        </div>
                                        <div class="ht-cc__body">
                                            <h3>{{ html_entity_decode($course->title) }}</h3>
                                            <p class="ht-cc__who"><span class="ht-cc__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($instructor, 0, 1)) }}</span>{{ $instructor }}</p>
                                            <div class="ht-cc__meta">
                                                @if ($avg > 0)<span class="ht-cc__stars"><svg width="14" height="14" viewBox="0 0 24 24" fill="#F4A617" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>{{ $avg }}</span>@endif
                                                @if ($course->content_length)<span>{{ getCourseDuration($course->content_length) }}</span>@endif
                                                @if ($course->enrollments_count > 0)<span>{{ number_format($course->enrollments_count) }} {{ $course->enrollments_count == 1 ? 'alumno' : 'alumnos' }}</span>@endif
                                            </div>
                                            <div class="ht-cc__foot">
                                                <span class="ht-cc__price {{ $price > 0 ? '' : 'ht-cc__price--free' }}">
                                                    @if ($price > 0)@if ($list > $price)<s>{{ formatAmount($list) }}</s>@endif{{ formatAmount($price) }}@else Gratis @endif
                                                </span>
                                                <span class="ht-cc__go">Ver curso <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="ht-cc__arrow ht-cc__arrow--next" x-show="canNext" x-cloak @click="go(1)" aria-label="Más cursos"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button>

                        <div class="ht-cc__dots" x-show="pages > 1" x-cloak aria-hidden="true">
                            <template x-for="i in pages" :key="i"><button type="button" :class="{ 'is-on': page === i - 1 }" @click="to(i - 1)" tabindex="-1"></button></template>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        @php $homeEvents = app(\App\Services\Pages\EventsCatalog::class)->next(3); @endphp
        @if ($homeEvents->isNotEmpty())
            @push('styles')<link rel="stylesheet" href="{{ asset('css/events.css') }}?v={{ @filemtime(public_path('css/events.css')) }}">@endpush
            <!-- Próximos eventos presenciales -->
            <section class="ev ev-home" aria-labelledby="home-events-title">
                <div class="container">
                    <header class="ev-home__head">
                        <div>
                            <p class="ev-hero__kicker ht-kicker">Presencial</p>
                            <h2 id="home-events-title">Próximos eventos</h2>
                        </div>
                        <a class="ev-btn" href="{{ route('events') }}">Ver todos los eventos</a>
                    </header>
                    <div class="ev-grid">
                        @foreach ($homeEvents as $e)
                            @include('frontend.partials.event-card', ['e' => $e])
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if (isPrivateAcademy())
        @php
            $faqs = [
                ['¿Cómo entro a la academia?', 'Las cuentas no se crean solas: pide tu acceso en «Solicitar acceso» (como estudiante o como instructor). Cuando lo aprobemos recibirás un correo con un enlace para elegir tu contraseña.'],
                ['¿Cómo me inscribo en un curso?', 'Con tu cuenta activa, elige el curso y págalo. El acceso se activa en cuanto se aprueba el pago; si el curso es gratuito, la inscripción es inmediata.'],
                ['¿Puedo enseñar en Horizontia?', 'Sí. Pide acceso como instructor contándonos tu experiencia y lo que te gustaría enseñar; nuestro equipo revisa cada solicitud.'],
                ['¿Cómo puedo pagar?', 'Con Mercado Pago: tarjeta, efectivo o transferencia. El pago es seguro y no guardamos los datos de tu tarjeta.'],
                ['¿Hay garantía de reembolso?', 'Sí, tienes ' . config('academy.refund_days', 7) . ' días de garantía de reembolso. Consulta la política de reembolsos para los detalles.'],
                ['¿Recibiré un certificado?', 'Sí. Al terminar el curso y aprobar sus evaluaciones puedes obtener tu constancia.'],
                ['¿Qué es el tutor con IA?', 'Un asistente dentro de cada lección que resuelve tus dudas con pistas y explicaciones, para que avances a tu ritmo.'],
            ];
        @endphp
        <style>
            .ht-faq { padding: 70px 0; background: #f9f9f9; }
            .ht-faq h2 { margin: 0 0 28px; text-align: center; color: #333; font-size: 2.1rem; font-weight: 700; }
            .ht-faq__list { max-width: 820px; margin: 0 auto; display: grid; gap: 10px; }
            .ht-faq details { background: #fff; border-radius: 14px; box-shadow: 0 4px 16px rgba(20, 33, 61, .06); }
            .ht-faq summary { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 22px; color: #14213d; font-weight: 650; cursor: pointer; list-style: none; }
            .ht-faq summary::-webkit-details-marker { display: none; }
            .ht-faq summary::after { content: "+"; flex: 0 0 auto; color: #14213d; font-size: 1.4rem; line-height: 1; }
            .ht-faq details[open] summary::after { content: "–"; }
            .ht-faq summary:focus-visible { outline: 3px solid #14213d; outline-offset: 2px; border-radius: 14px; }
            .ht-faq details p { margin: 0; padding: 0 22px 20px; color: #555; line-height: 1.6; }
        </style>
        <!-- FAQ -->
        <section class="ht-faq" aria-labelledby="ht-faq-title">
            <div class="container">
                <div class="ht-faq__head ht-reveal">
                    <span class="ht-faq__kicker">Resolvemos tus dudas</span>
                    <h2 id="ht-faq-title">Preguntas frecuentes</h2>
                </div>
                <div class="ht-faq__list">
                    @foreach ($faqs as [$question, $answer])
                        <details>
                            <summary>{{ $question }}</summary>
                            <p>{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @push('styles')
            {{-- First paint: the hero is a CSS background, so tell the browser about it early. --}}
            <link rel="preload" as="image" href="{{ webpAsset('images/nuevo/Slider-horizontia.jpg') }}" fetchpriority="high">
            @php
                $orgSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'EducationalOrganization',
                    'name' => setting('_general.site_name') ?: 'Horizontia',
                    'url' => url('/'),
                    'logo' => asset('images/nuevo/logo-horizontia.png'),
                    'description' => 'Academia en línea con cursos prácticos de instructores especialistas.',
                ];
                $courseList = [
                    '@context' => 'https://schema.org',
                    '@type' => 'ItemList',
                    'itemListElement' => $homeCourses->values()->map(fn ($c, $i) => [
                        '@type' => 'ListItem', 'position' => $i + 1, 'url' => route('courses.course-detail', $c->slug), 'name' => $c->title,
                    ])->all(),
                ];
                $faqSchema = isset($faqs) ? [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => collect($faqs)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all(),
                ] : null;
            @endphp
            <script type="application/ld+json">{!! json_encode($orgSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
            @if($homeCourses->isNotEmpty())
                <script type="application/ld+json">{!! json_encode($courseList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
            @endif
            @if($faqSchema)
                <script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
            @endif
        @endpush

        <!-- Filosofía: imagen panorámica contenida (nítida) con texto -->
        <section class="ht-calm">
            <style>
                .ht-calm__head { max-width: 760px; margin: 0 auto 34px; text-align: center; }
                .ht-calm__kicker { display: inline-block; margin-bottom: 10px; font-size: .8rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #8a6d00; }
                .ht-calm__head h2 { margin: 0 0 14px; font-size: clamp(1.8rem, 3.6vw, 2.7rem); line-height: 1.15; color: #14213d; }
                .ht-calm__head p { margin: 0; font-size: 1.12rem; line-height: 1.65; color: #4b5563; }
                .ht-calm__photo { position: relative; margin: 0; border-radius: 28px; overflow: hidden; aspect-ratio: 21 / 8; box-shadow: 0 26px 60px rgba(20, 33, 61, .18); background: #1a1a1a; }
                .ht-calm__photo img { width: 100%; height: 100%; object-fit: cover; object-position: center 35%; display: block; }
                .ht-calm__pills { position: absolute; left: 0; right: 0; bottom: 0; display: flex; flex-wrap: wrap; gap: 10px 14px; justify-content: center; padding: 60px 20px 22px; background: linear-gradient(180deg, rgba(20,33,61,0) 0%, rgba(20,33,61,.72) 100%); }
                .ht-calm__pills span { padding: 8px 16px; border-radius: 999px; background: rgba(255,255,255,.92); color: #14213d; font-weight: 700; font-size: .9rem; }
                @media (max-width: 760px) { .ht-calm__photo { aspect-ratio: 4 / 3; border-radius: 20px; } }

                .ht-close { position: relative; isolation: isolate; display: grid; place-items: center; min-height: 460px; margin-top: 70px; padding: 70px 20px; text-align: center; color: #fff; background: #14213d url('{{ webpAsset('images/nuevo/respiracion-consciente-horizontia.jpg') }}') center 30% / cover no-repeat; }
                .ht-close::before { content: ""; position: absolute; inset: 0; z-index: -1; background: linear-gradient(180deg, rgba(20,33,61,.55) 0%, rgba(20,33,61,.82) 100%); }
                .ht-close__kicker { display: block; margin-bottom: 12px; font-size: .85rem; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; color: #F4C430; }
                .ht-close h2 { margin: 0 0 12px; color: #fff; font-size: clamp(2.4rem, 6vw, 4rem); text-shadow: 0 4px 18px rgba(0,0,0,.45); }
                .ht-close p { margin: 0 auto 30px; max-width: 52ch; font-size: 1.2rem; line-height: 1.55; color: #eef1f8; }
                .ht-close__btns { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
                .ht-close__btn { display: inline-block; padding: 14px 30px; border-radius: 999px; background: #F4C430; color: #14213d !important; font-weight: 700; text-decoration: none; }
                .ht-close__btn:hover { background: #ffd54a; }
                .ht-close__btn--ghost { background: transparent; color: #fff !important; border: 2px solid rgba(255,255,255,.75); }
                .ht-close__btn--ghost:hover { background: rgba(255,255,255,.12); }
            </style>
            <div class="container">
                <div class="ht-calm__head ht-reveal">
                    <span class="ht-calm__kicker">Nuestra filosofía</span>
                    <h2>Aprender también es pausar, respirar y enfocarte</h2>
                    <p>Creemos que el talento florece cuando la mente está en calma. Por eso combinamos contenido práctico con un ritmo humano: sin prisas, con acompañamiento y a tu medida.</p>
                </div>
                <figure class="ht-calm__photo ht-reveal" style="--d: .1s">
                    <img src="{{ webpAsset('images/nuevo/wellness.jpg') }}" {!! imgAttrs('images/nuevo/wellness.jpg') !!} alt="Un equipo de profesionales comparte un momento de calma en la oficina" loading="lazy">
                    <figcaption class="ht-calm__pills"><span>Aprende a tu ritmo</span><span>Acompañamiento real</span><span>Conocimiento aplicable</span></figcaption>
                </figure>
            </div>
        </section>

        <!-- Cierre de marca -->
        <section class="ht-close" aria-label="Horizontia">
            <div>
                <span class="ht-close__kicker">Conciencia humana con talento</span>
                <h2>Horizontia</h2>
                <p>Cursos prácticos de instructores especialistas, con certificado al terminar. Empieza hoy a construir tu siguiente paso.</p>
                <div class="ht-close__btns">
                    <a class="ht-close__btn" href="{{ route('courses.search-courses') }}">Explorar cursos</a>
                    @if (\App\Services\AccessRequestService::enabled())
                        <a class="ht-close__btn ht-close__btn--ghost" href="{{ route('access.request') }}">Solicitar acceso</a>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
<style id="ht-rhythm">
    /* Ritmo único de la portada: mismo ancho de contenido, mismos márgenes laterales, mismo espacio vertical y mismos encabezados. */
    .ht-home { --ht-w: 1240px; --ht-gut: clamp(16px, 3vw, 24px); --ht-pad: clamp(56px, 7vw, 96px); --ht-h2: clamp(1.9rem, 3.8vw, 2.7rem); }

    .ht-home .container,
    .ht-home .ht-steps2__grid, .ht-home .ht-about__wrap, .ht-home .ht-cc__in, .ht-home .ev .container {
        width: 100%; max-width: var(--ht-w); margin-left: auto; margin-right: auto; padding-left: var(--ht-gut); padding-right: var(--ht-gut); box-sizing: border-box;
    }
    .ht-home .ht-steps2, .ht-home .ht-about, .ht-home .ht-split, .ht-home .ht-cc,
    .ht-home .ev-home, .ht-home .ht-faq, .ht-home .ht-calm, .ht-home .ht-close { padding-top: var(--ht-pad); padding-bottom: var(--ht-pad); }
    .ht-home .ht-close { margin-top: 0; }
    .ht-home .ht-calm { padding-bottom: 0; }          /* la foto panorámica se une al cierre de marca */
    .ht-home .ht-calm + .ht-close { margin-top: var(--ht-pad); }

    /* Encabezados de sección: misma etiqueta, mismo título, mismo respiro. */
    .ht-home .ht-kicker, .ht-home .ht-steps2__kicker, .ht-home .ht-calm__kicker, .ht-home .ht-split__kicker, .ht-home .ht-cc__kicker, .ht-home .ht-faq__kicker, .ht-home .ev-home__head .ev-hero__kicker {
        display: block; margin: 0 0 10px; font-size: .8rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #8a6d00; font-family: inherit;
    }
    .ht-home .ht-cc__kicker { color: #F4C430; }
    .ht-home .ht-steps2__head h2, .ht-home .ht-calm__head h2, .ht-home .ht-split h2, .ht-home .ht-about__card h2, .ht-home .ht-cc__head h2, .ht-home .ht-faq h2, .ht-home .ev-home__head h2 {
        margin: 0 0 14px; font-size: var(--ht-h2); font-weight: 700; line-height: 1.15; letter-spacing: -.01em;
    }
    .ht-home .ht-faq h2, .ht-home .ev-home__head h2 { color: #14213d; }
    .ht-home .ht-steps2__head, .ht-home .ht-calm__head, .ht-home .ht-faq__head { margin: 0 auto 44px; max-width: 720px; text-align: center; }
    .ht-home .ev-home__head, .ht-home .ht-cc__head { margin-bottom: 36px; }
    .ht-home .ht-steps2__head p, .ht-home .ht-calm__head p, .ht-home .ht-cc__head p, .ht-home .ht-split__lead { font-size: 1.1rem; line-height: 1.65; }
    .ht-home .ht-faq__list { max-width: 820px; }

    /* Botones de «ver todo» de cabecera: mismo tamaño y color. */
    .ht-home .ht-cc__all, .ht-home .ev-home__head .ev-btn { padding: 12px 26px; border-radius: 999px; background: #F4C430; color: #14213d !important; font-weight: 700; font-size: 1rem; }

    /* Rejillas con el mismo hueco. */
    .ht-home .ht-split__row { gap: clamp(28px, 5vw, 64px); }
    .ht-home .ht-split__row + .ht-split__row { margin-top: clamp(48px, 7vw, 88px); }
    .ht-home .ev-grid { gap: 22px; }
</style>
@endpush

