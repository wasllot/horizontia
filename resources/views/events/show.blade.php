@extends('events.layout', ['onLanding' => true])

@php
    $money = fn ($v) => '$' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $img = fn (string $k) => asset('images/eventos/stock/' . $k . '.webp');
    $usedImages = ['hero-geminids', 'telescopios', 'moon', 'saturn', 'orion-nebula', 'pleiades', 'albireo', 'sun', 'teotihuacan-sol', 'teotihuacan-calzada', 'campfire', 'star-party'];
    $start = $event->local($event->start_at);
    $end = $event->local($event->end_at);
    $cd = $event->local($event->countdown_from);
    $minorsAge = (int) (data_get($event->content, 'minors_age') ?: 6);
    $hotelUrl = $event->hotel_booking_url;
    $earlyActive = collect($quote['discounts'])->first(fn ($d) => str_contains(mb_strtolower($d['label']), 'preventa'));
    $pricing = ['unit' => (float) $event->price, 'rules' => collect((array) $event->discounts)->filter(fn ($r) => !empty($r['enabled']) && (float) ($r['percent'] ?? 0) > 0)->map(fn ($r, $k) => ['key' => $k, 'percent' => (float) $r['percent'], 'min' => $k === 'group' ? (int) ($r['min_qty'] ?? 4) : 1])->values()->all(), 'iva' => (float) $event->iva_rate];
    $dateLong = $start ? $start->locale('es')->translatedFormat('l j \d\e F \d\e Y') : '';
    $weekend = $start && $end ? 'Sáb ' . $start->format('j') . ' – dom ' . $end->format('j') . ' ' . $start->locale('es')->translatedFormat('M Y') : '';
    $icon = fn (string $n) => '<svg class="ev-i" aria-hidden="true"><use href="#i-' . $n . '"/></svg>';

    $sat = [
        ['15:00', 'Check-in en el hotel', 'Instala tu casa de campaña o entra a tu habitación.', 'tent'],
        ['16:00', 'Registro del evento', 'Recibe tu pulsera y tu kit con tu clave de acceso.', 'ticket'],
        ['17:00', 'Bienvenida: ¿qué veremos esta noche?', 'Luz Calderón explica la noche y arranca el rally.', 'mic'],
        ['17:45', 'Atardecer', 'Momento fotográfico mientras el Sol se despide.', 'sun'],
        ['18:15', 'Conferencia: Teotihuacán y el cielo', 'En el auditorio: arqueoastronomía en Mesoamérica.', 'mic'],
        ['19:00', 'Iniciamos: taller de cielo', 'Constelaciones y primera mirada a la Luna y a Saturno.', 'telescope'],
        ['20:00', 'Cena', 'En el restaurante o la zona de snacks (no incluida).', 'cash'],
        ['20:30', 'Música temática', 'Ambiente musical inspirado en el espacio.', 'music'],
        ['21:00', 'Fogata', 'Bombones y plática, lejos de los telescopios.', 'flame'],
        ['22:00', 'Primera observación + segunda conferencia', 'Planetas, cúmulos y la Nebulosa de Orión.', 'telescope'],
        ['23:00', 'Caza de Gemínidas', 'Luces apagadas, sin música ni celulares.', 'star'],
        ['00:30–02:00', 'La gran observación', 'El radiante de Géminis en lo más alto: el mejor momento de la noche.', 'star', true],
        ['02:00', 'Observación libre', 'Para quienes sigan despiertos.', 'moon'],
    ];
    $sun = [
        ['07:00', 'Café al amanecer', 'Ver salir el Sol después de una noche de estrellas.', 'sun'],
        ['08:00', 'Desayuno', 'Buffet en el restaurante del hotel (no incluido).', 'cash'],
        ['10:00', 'Observación solar y premiación', 'Manchas solares con filtros especializados y seguros.', 'sun', true],
        ['12:00', 'Concluye el evento', 'Premios del rally, diplomas y foto de grupo. Check-out.', 'trophy'],
        ['Después', 'Visita a la zona arqueológica', 'Por tu cuenta, con los horarios y reglas de la zona.', 'map'],
    ];
    $objects = [
        ['moon', 'La Luna creciente', 'Al atardecer sus cráteres y montañas se ven con relieve. Parece que la estás tocando.'],
        ['saturn', 'Saturno', 'El planeta de los anillos. Verlo por primera vez en un telescopio no se olvida.'],
        ['orion-nebula', 'La Nebulosa de Orión', 'Una cuna de estrellas a unos 1,300 años luz.'],
        ['pleiades', 'Cúmulos de estrellas', 'Familias de estrellas nacidas juntas, como las Pléyades: diamantes azules.'],
        ['albireo', 'Estrellas dobles', 'Puntos de luz que, con telescopio, se separan en dos soles de colores distintos.'],
        [null, 'Constelaciones de invierno', 'Con un puntero de luz verde (solo del astrónomo) encontrarás Orión, Géminis, Cástor, Pólux y Proción.'],
        ['sun', 'El Sol (domingo)', 'Con filtros seguros veremos manchas solares. ¡Nunca mires al Sol sin filtro!'],
    ];
    $alts = ['moon' => 'Luna creciente delgada sobre un cielo negro', 'saturn' => 'Saturno con sus anillos y auroras en el polo', 'orion-nebula' => 'Nebulosa de Orión fotografiada por el telescopio Hubble', 'pleiades' => 'Cúmulo estelar de las Pléyades, estrellas azules', 'albireo' => 'Albireo, estrella doble de un punto azul y otro anaranjado', 'sun' => 'Disco del Sol con manchas solares'];
@endphp

@push('head')
<link rel="preload" as="image" href="{{ $img('hero-geminids') }}" fetchpriority="high">
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'Event',
    'name' => $event->name,
    'description' => $event->text('seo.description'),
    'startDate' => $start?->toIso8601String(), 'endDate' => $end?->toIso8601String(),
    'eventStatus' => 'https://schema.org/EventScheduled',
    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
    'location' => array_filter(['@type' => 'Place', 'name' => $event->venue_name, 'address' => array_filter(['@type' => 'PostalAddress', 'streetAddress' => $event->venue_address, 'addressLocality' => $event->venue_city, 'addressCountry' => 'MX'])]),
    'image' => $event->asset('og') ? [$event->asset('og')] : [$img('hero-geminids')],
    'offers' => $event->hasPrice() ? ['@type' => 'Offer', 'priceCurrency' => 'MXN', 'price' => (string) (float) $event->price, 'url' => $event->url(), 'availability' => $state === 'sold_out' ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock'] : null,
    'performer' => ['@type' => 'Person', 'name' => 'Luz Calderón'],
    'organizer' => ['@type' => 'Organization', 'name' => 'Horizontia', 'url' => url('/')],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('body')

{{-- 1 · Portada --}}
<section class="ev-hero" aria-labelledby="ev-h1">
    <div class="ev-hero__img" style="background-image:url('{{ $event->asset('hero_desktop') ?: $img('hero-geminids') }}')" role="presentation"></div>
    <div class="ev-stars" aria-hidden="true"></div>
    <span class="ev-shoot" aria-hidden="true"></span><span class="ev-shoot" aria-hidden="true"></span><span class="ev-shoot" aria-hidden="true"></span>
    <div class="ev-wrap">
        <h1 class="ev-supra" id="ev-h1">{{ $event->text('seo.h1') }}</h1>
        <h2 class="ev-hero__title">{{ $event->text('hero.title') }}</h2>
        <p class="ev-lead">{{ $event->text('hero.subtitle') }}</p>
        <p class="ev-chips">
            @if ($start)<span class="ev-pill">{{ ucfirst($start->locale('es')->translatedFormat('l j \d\e F')) }} · desde las {{ $start->format('H:i') }} h</span>@endif
            @if ($event->hasPrice())<span class="ev-chip">{{ $money($event->price) }} MXN · IVA incluido</span>@endif
            @if ($state === 'open' && $available !== null)<span class="ev-chip ev-chip--hot">¡Cupo limitado!</span>@endif
            @if ($earlyActive)<span class="ev-pill ev-pill--tag">{{ $earlyActive['label'] }} −{{ rtrim(rtrim(number_format($earlyActive['percent'], 2), '0'), '.') }} %</span>@endif
        </p>
        <div class="ev-cta">
            @if ($state === 'open')<a class="ev-btn" href="#entrada" data-ev-track="cta_hero">{{ $event->text('hero.cta') }}</a>@endif
            <a class="ev-btn ev-btn--ghost" href="#que-vamos-a-ver">{{ $event->text('hero.secondary') }}</a>
        </div>
        <p class="ev-meta">{{ $event->text('hero.supra') }} · {{ $event->text('hero.host') }} · {{ $event->venue_name }}</p>
        @if ($cd && $start && !$event->isFinished() && now()->gte($cd))
            <p class="ev-count" id="evCount" data-start="{{ $start->toIso8601String() }}" aria-live="off"><span id="evCountText">&nbsp;</span></p>
        @endif
    </div>
    @if ($event->asset('galileo') || $event->asset('telescopio'))
        <div class="ev-hero__chars">
            @if ($event->asset('galileo'))<img src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" width="265" height="350">@endif
            @if ($event->asset('telescopio'))<img src="{{ $event->asset('telescopio') }}" alt="Telescopio, mascota de Astrofest, un telescopio con brazos y zapatos" width="250" height="303">@endif
        </div>
    @endif
</section>

{{-- 2 · Datos clave --}}
<section class="ev-facts" aria-label="Datos clave">
    <div class="ev-wrap"><ul>
        @if ($weekend)<li>{!! $icon('calendar') !!}<span>Fecha</span><b>{{ $weekend }}</b></li>@endif
        @if ($start)<li>{!! $icon('clock') !!}<span>Check-in</span><b>{{ $start->format('H:i') }} h</b></li>@endif
        @if ($event->venue_name)<li>{!! $icon('pin') !!}<span>Lugar</span><b>{{ $event->venue_name }}</b></li>@endif
        <li>{!! $icon('car') !!}<span>Distancia</span><b>~1 h de la CDMX</b></li>
        @if ($event->hasPrice())<li class="price">{!! $icon('ticket') !!}<span>Por persona · camping incluido</span><b>{{ $money($event->price) }} MXN</b></li>@endif
    </ul></div>
</section>

{{-- 3 · Qué incluye (resumen visual) --}}
<section class="ev-incl" aria-label="Lo que incluye la experiencia">
    <div class="ev-wrap"><ul>
        @foreach ([['telescope', 'Telescopios'], ['mic', 'Conferencias'], ['trophy', 'Rally astronómico'], ['music', 'Música temática'], ['flame', 'Fogatas y CampingZone'], ['dome', 'Planetario móvil'], ['headphones', 'DJ en vivo'], ['gift', 'AstroRifa']] as [$i, $t])
            <li><span class="ev-incl__ico">{!! $icon($i) !!}</span>{{ $t }}</li>
        @endforeach
    </ul></div>
</section>

{{-- 4 · ¿Qué es Astrofest®? --}}
<section class="ev-sec" id="que-es" aria-labelledby="h-que-es">
    <div class="ev-wrap">
        <div class="ev-split">
            <div>
                <h2 id="h-que-es">¿Qué es Astrofest®?</h2>
                <p>Imagina esto: cae la tarde sobre Teotihuacán, el cielo se pinta de naranja detrás de los cerros y, una a una, se encienden las primeras estrellas. Apagamos las luces, prendemos linternas rojas y, durante toda la noche, miramos hacia arriba juntos.</p>
                <p>No es una excursión ni un campamento cualquiera: es una noche diseñada momento a momento para que te enamores del cielo, con astrónomos, telescopios, fogata, música y la cacería de estrellas fugaces de las Gemínidas.</p>
                <p><strong>No necesitas saber nada de astronomía.</strong> Si tienes un telescopio guardado en el clóset, tráelo: te ayudamos a usarlo y serás el héroe de la noche.</p>
            </div>
            <figure class="ev-photo">
                <img src="{{ $img('telescopios') }}" alt="Personas observando el cielo nocturno junto a un telescopio" width="1200" height="806" loading="lazy">
                <figcaption>Observación guiada con telescopios · imagen ilustrativa</figcaption>
            </figure>
        </div>
        <div class="ev-grid ev-grid--4" style="margin-top:30px">
            <article class="ev-card"><div class="ico">{!! $icon('star') !!}</div><h3>Ciencia de verdad</h3><p>Astrónomos y divulgadores te explican el cielo con palabras sencillas. Sin humo, sin promesas exageradas.</p></article>
            <article class="ev-card"><div class="ico">{!! $icon('map') !!}</div><h3>Teotihuacán y el cielo</h3><p>Una de las ciudades más grandes de la antigüedad se trazó mirando al Sol y a las estrellas.</p></article>
            <article class="ev-card"><div class="ico">{!! $icon('eye-off') !!}</div><h3>La noche de la luz roja</h3><p>Apagamos reflectores y pantallas para que tus ojos vean muchas más estrellas.</p></article>
            <article class="ev-card"><div class="ico">{!! $icon('users') !!}</div><h3>Para toda la familia</h3><p>Rally con premios, diploma para todos y actividades para chicos y grandes. Menores de {{ $minorsAge }} años no pagan.</p></article>
        </div>
        <div class="ev-paper" style="margin-top:22px"><h3>Una marca para coleccionar</h3><p>Astrofest® Gemínidas 2026 es la primera edición de una nueva temporada de noches de estrellas. Cada lluvia de meteoros tendrá su propia pulsera, parche, playera y mapa del cielo. Guarda tu pulsera: será tu pase de coleccionista.</p></div>
    </div>
</section>

{{-- 5 · ¿Qué vamos a ver? --}}
<section class="ev-sec ev-sec--mid" id="que-vamos-a-ver" aria-labelledby="h-ver">
    <div class="ev-wrap">
        <h2 id="h-ver">¿Qué vamos a ver en el cielo?</h2>
        <p class="ev-lead-dark"><strong>Una noche para mirar hacia arriba.</strong> Cada diciembre la Tierra cruza el polvo que dejó el asteroide Faetón, y el cielo se llena de estrellas fugaces. ¡Y este año, sin Luna que estorbe!</p>

        <ul class="ev-stats" aria-label="Por qué el 12 de diciembre es una gran noche">
            <li><strong>9 dic</strong><span><b>Luna nueva.</b> El día 12 solo un creciente delgado que se oculta temprano: cielo oscuro el resto de la noche.</span></li>
            <li><strong>13–14 dic</strong><span><b>Máximo de las Gemínidas.</b> La noche del 12 es la víspera y ya tiene mucha actividad… y cae en sábado.</span></li>
            <li><strong>2:00 h</strong><span><b>El radiante en su punto más alto.</b> La gran observación será de medianoche a las 2:00.</span></li>
        </ul>

        <details class="ev-acc">
            <summary>Ciencia en un minuto: ¿qué son las Gemínidas?</summary>
            <p>Cada diciembre la Tierra cruza una nube de polvo que dejó un asteroide llamado 3200 Faetón. Esos granitos, muchos del tamaño de un grano de arena, chocan contra la atmósfera a unos 35 kilómetros por segundo y se calientan tanto que brillan: eso es una estrella fugaz. Se llaman Gemínidas porque, si sigues sus trazos hacia atrás, todas parecen salir de un punto en la constelación de Géminis, cerca de Cástor y Pólux: el <em>radiante</em>.</p>
        </details>
        <p class="ev-honest">{!! $icon('shield') !!}<span><strong>Un compromiso honesto:</strong> no prometemos un número exacto de estrellas fugaces por hora; el cielo tiene sus propios planes. Teotihuacán no es un cielo de alta montaña porque la CDMX está cerca, pero las Gemínidas son brillantes y se ven muy bien desde aquí.</span></p>

        <div class="ev-rail-head">
            <h3>Lo que verás por los telescopios</h3>
            <div class="ev-rail-btns"><button type="button" class="evp-icon" data-rail="-1" aria-label="Anterior">{!! $icon('chev-l') !!}</button><button type="button" class="evp-icon" data-rail="1" aria-label="Siguiente">{!! $icon('chev-r') !!}</button></div>
        </div>
        <div class="ev-rail" id="evRail" tabindex="0" role="region" aria-label="Objetos que veremos: desliza para ver más">
            @foreach ($objects as [$k, $t, $d])
                <article class="ev-obj">
                    @if ($k)
                        <img src="{{ $img($k) }}" alt="{{ $alts[$k] }}" width="720" height="540" loading="lazy">
                    @else
                        <svg class="ev-obj__map" viewBox="0 0 720 540" role="img" aria-label="Mapa de las constelaciones de Orión y Géminis" preserveAspectRatio="xMidYMid slice">
                            <rect width="720" height="540" fill="#0b1230"/>
                            <g stroke="#fed304" stroke-opacity=".55" stroke-width="2" fill="none"><path d="M250 120l70 90-20 130M320 210l80 14 70-30M400 224l-10 130M470 194l60-86M300 340l-70 100M390 354l60 90"/><path d="M520 90l40 120M560 210l30 140" stroke="#7fd3ff"/></g>
                            <g fill="#fff"><circle cx="250" cy="120" r="7"/><circle cx="470" cy="194" r="6"/><circle cx="320" cy="210" r="5"/><circle cx="400" cy="224" r="6"/><circle cx="470" cy="226" r="5"/><circle cx="300" cy="340" r="6"/><circle cx="390" cy="354" r="9"/><circle cx="530" cy="108" r="5"/><circle cx="230" cy="440" r="5"/><circle cx="450" cy="444" r="5"/><circle cx="560" cy="210" r="7" fill="#7fd3ff"/><circle cx="520" cy="90" r="6" fill="#7fd3ff"/><circle cx="590" cy="350" r="4"/><circle cx="120" cy="300" r="3"/><circle cx="650" cy="470" r="3"/><circle cx="90" cy="90" r="3"/></g>
                            <g fill="#cfe6ff" font-family="Roboto,sans-serif" font-size="22"><text x="262" y="112">Betelgeuse</text><text x="406" y="388">Rigel</text><text x="456" y="96" fill="#7fd3ff">Cástor</text><text x="568" y="236" fill="#7fd3ff">Pólux</text></g>
                        </svg>
                    @endif
                    <div class="ev-obj__body"><h4>{{ $t }}</h4><p>{{ $d }}</p></div>
                </article>
            @endforeach
        </div>

        <div class="ev-feature">
            <figure class="ev-photo"><img src="{{ $img('teotihuacan-sol') }}" alt="Pirámide del Sol en Teotihuacán bajo un cielo despejado" width="1400" height="917" loading="lazy"><figcaption>Pirámide del Sol, Teotihuacán · imagen ilustrativa</figcaption></figure>
            <div>
                <h3>Teotihuacán y el cielo</h3>
                <p>La Ciudad de los Dioses no se construyó al azar. Sus calzadas y pirámides guardan alineaciones con el Sol y las estrellas que los arqueoastrónomos siguen estudiando. En Astrofest® escucharás cómo observaban el cielo los pueblos de Mesoamérica… mientras ves caer estrellas fugaces en el mismo cielo que ellos miraban.</p>
            </div>
        </div>
    </div>
</section>

{{-- 6 · Programa --}}
<section class="ev-sec" id="programa" aria-labelledby="h-programa">
    <div class="ev-wrap">
        <h2 id="h-programa">Programa de la noche</h2>
        <p class="ev-muted">Programa sujeto a cambios por clima. Todo ocurre dentro del hotel.</p>
        <div class="ev-tabs" id="evTabs">
            <div class="ev-tabs__list" role="tablist" aria-label="Días del programa">
                <button type="button" role="tab" id="tab-sab" aria-controls="pan-sab" aria-selected="true">Sábado {{ $start?->format('j') ?? '12' }}</button>
                <button type="button" role="tab" id="tab-dom" aria-controls="pan-dom" aria-selected="false" tabindex="-1">Domingo {{ $end?->format('j') ?? '13' }}</button>
            </div>
            @foreach ([['sab', 'Sábado ' . ($start?->format('j') ?? '12') . ' de diciembre', $sat], ['dom', 'Domingo ' . ($end?->format('j') ?? '13') . ' de diciembre', $sun]] as [$id, $title, $rows])
                <div class="ev-tabs__panel" role="tabpanel" id="pan-{{ $id }}" aria-labelledby="tab-{{ $id }}">
                    <h3 class="ev-tabs__h">{{ $title }}</h3>
                    <ol class="ev-time">
                        @foreach ($rows as $r)
                            <li class="{{ !empty($r[4]) ? 'is-hl' : '' }}"><span class="ev-time__ico">{!! $icon($r[3]) !!}</span><time>{{ $r[0] }}</time><b>{{ $r[1] }}</b><span>{{ $r[2] }}</span></li>
                        @endforeach
                    </ol>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- 7 · Entrada + Plan Nube --}}
<section class="ev-sec ev-sec--mid" id="entrada" aria-labelledby="h-entrada">
    <div class="ev-wrap">
        <h2 id="h-entrada">Tu entrada a Astrofest®</h2>
        @if (session('event_error'))<p class="ev-alert" role="alert">{{ session('event_error') }}</p>@endif
        <div class="ev-grid ev-grid--2" style="align-items:start">
            <div class="ev-ticket">
                @if ($event->asset('telescopio'))<img class="ev-ticket__char" src="{{ $event->asset('telescopio') }}" alt="" aria-hidden="true" loading="lazy" width="110" height="133">@endif
                @if ($event->hasPrice())
                    <div class="ev-ticket__price">{{ $money($event->price) }} MXN<small>por persona</small></div>
                    <p class="ev-tags"><span>{!! $icon('check') !!} IVA incluido</span><span>{!! $icon('check') !!} Camping incluido</span>@if ($state === 'open' && $available !== null)<span class="is-hot">¡Cupo limitado!</span>@endif</p>
                @endif
                @foreach ($quote['discounts'] as $d)<p style="margin:0"><span class="ev-pill ev-pill--tag">{{ $d['label'] }} −{{ rtrim(rtrim(number_format($d['percent'], 2), '0'), '.') }} %</span></p>@endforeach

                @if ($state === 'open')
                    <form method="post" action="{{ $event->url('carrito') }}" id="evBuy" data-pricing='@json($pricing)'>
                        @csrf
                        <div class="ev-field">
                            <label for="evQty">¿Cuántas entradas?</label>
                            <div class="ev-qty">
                                <button type="button" data-step="-1" aria-label="Quitar una entrada">−</button>
                                <input id="evQty" name="quantity" type="number" min="1" max="{{ $maxQuantity }}" value="1" inputmode="numeric">
                                <button type="button" data-step="1" aria-label="Agregar una entrada">+</button>
                            </div>
                            @if ($available !== null && $available <= 10)<p class="ev-hint" style="margin-top:8px">Quedan {{ $available }} {{ $available === 1 ? 'lugar' : 'lugares' }}.</p>@endif
                        </div>
                        <p class="ev-total" style="margin:14px 0">Total: <strong id="evTotal">{{ $money($quote['total']) }}</strong> MXN <span class="ev-hint" id="evSaving"></span></p>
                        <button type="submit" class="ev-btn ev-btn--block" data-ev-track="add_to_cart_click">Agregar al carrito</button>
                        <p class="ev-secure">{!! $icon('shield') !!}<span>Pago seguro con Mercado Pago. Tarjeta y otros medios disponibles. <a href="{{ $event->url('politicas') }}">Políticas y cancelaciones</a></span></p>
                    </form>
                @elseif ($state === 'sold_out')
                    <button type="button" class="ev-btn is-off" disabled aria-disabled="true">{{ $event->text('sold_out') }}</button>
                    <p class="ev-hint">Las entradas se agotaron. Gracias por tu interés.</p>
                @elseif ($state === 'ended')
                    <p class="ev-soon">Las ventas ya cerraron.</p>
                @elseif ($state === 'not_yet' && $event->sales_open_at)
                    <p class="ev-soon">Las entradas salen a la venta el {{ $event->local($event->sales_open_at)->locale('es')->translatedFormat('j \d\e F \a \l\a\s H:i') }} h.</p>
                @elseif ($state === 'finished')
                    <p class="ev-soon"><strong>Edición finalizada.</strong> Gracias por mirar hacia arriba con nosotros. Pronto anunciaremos la próxima lluvia de estrellas.</p>
                @else
                    <p class="ev-soon">Las entradas estarán disponibles muy pronto.</p>
                @endif

                <ul class="ev-sumlist">
                    <li>{!! $icon('mic') !!} Conferencias, taller de cielo y observación guiada</li>
                    <li>{!! $icon('flame') !!} Fogata, música, DJ en vivo, planetario y rally</li>
                    <li>{!! $icon('gift') !!} Pulsera, pasaporte, mapa del cielo y diploma</li>
                    <li>{!! $icon('tent') !!} Camping para 1 persona, con tu casa de campaña</li>
                </ul>
                <details class="ev-acc ev-acc--sm">
                    <summary>Ver todo lo que incluye y lo que no</summary>
                    <ul class="ev-list">
                        <li>Conferencias magistrales en el auditorio y taller de cielo.</li>
                        <li>Observación con telescopios guiada por astrónomos, caza de Gemínidas y gran observación de madrugada.</li>
                        <li>Fogata, música temática, DJ en vivo, shows en Planetario Móvil, rally con pistas y premios y AstroRifa.</li>
                        <li>Observación del Sol el domingo por la mañana.</li>
                        <li>Pulsera coleccionable, Pasaporte Astrofest®, mapa del cielo de diciembre, stickers y celofán rojo para tu linterna.</li>
                        <li>Diploma de participación.</li>
                        <li>Camping para 1 persona (con tu casa de campaña), con baños, vestidores y regaderas: un lugar seguro para acampar.</li>
                    </ul>
                    <ul class="ev-list ev-list--no" style="margin-top:12px">
                        <li>Habitación (se reserva con el hotel), transporte ni alimentos (cena, snacks, desayuno buffet).</li>
                        <li>Estacionamiento: $60 por día, se paga directamente en el hotel.</li>
                    </ul>
                    <p class="ev-hint" style="margin-top:10px">Menores de {{ $minorsAge }} años no pagan. Cada persona de {{ $minorsAge }} años o más necesita su entrada.</p>
                </details>
            </div>

            <div class="ev-side">
                <div class="ev-calm" id="plan-nube">
                    <h3>Compra tranquilo: Astrofest depende del cielo</h3>
                    <p class="ev-muted" style="margin:0 0 12px">…y el cielo no depende de nosotros. Por eso hay Plan Nube.</p>
                    <ul>
                        <li>{!! $icon('cloud') !!}<span><b>¿Nublado?</b> La fiesta sigue con charlas, planetario o proyección y fogata, y te llevas un crédito para la próxima edición.</span></li>
                        <li>{!! $icon('cloud-bolt') !!}<span><b>¿Clima peligroso?</b> Reprogramamos o cancelamos por tu seguridad. Tú eliges: nueva fecha o crédito.</span></li>
                        <li>{!! $icon('snow') !!}<span><b>¿Frío?</b> Es parte de la aventura y no es motivo de cancelación. ¡Ven abrigado!</span></li>
                    </ul>
                    <a href="{{ $event->url('politicas') }}">Consulta la política completa</a>
                </div>
                @if ($event->show('rooms') && $hotelUrl)
                    <div class="ev-card ev-rooms">
                        <h3>¿Prefieres habitación?</h3>
                        <p>{{ $event->text('rooms.text') }}</p>
                        <div class="ev-cta" style="margin:0">
                            <a class="ev-btn ev-btn--ghost ev-btn--sm" href="{{ $hotelUrl }}" target="_blank" rel="noopener" data-ev-track="click_reserve_room">Reservar habitación en el hotel</a>
                            @if ($event->hotel_whatsapp)<a class="ev-btn ev-btn--ghost ev-btn--sm" href="https://wa.me/{{ preg_replace('/\D+/', '', $event->hotel_whatsapp) }}" target="_blank" rel="noopener" data-ev-track="click_reserve_room">WhatsApp del hotel</a>@endif
                            @if ($event->hotel_phone)<a class="ev-btn ev-btn--ghost ev-btn--sm" href="tel:{{ preg_replace('/[^\d+]/', '', $event->hotel_phone) }}" data-ev-track="click_reserve_room">Llamar: {{ $event->hotel_phone }}</a>@endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- 8 · El lugar --}}
<section class="ev-sec" id="lugar" aria-labelledby="h-lugar">
    <div class="ev-wrap">
        <div class="ev-split ev-split--rev">
            <div>
                <h2 id="h-lugar">El lugar y cómo llegar</h2>
                <p><strong>{{ $event->venue_name }}</strong>{{ $event->venue_city ? ' · ' . $event->venue_city : '' }}. Un hotel de campo con lago, jardines amplios, capilla, auditorio y restaurante, a pocos minutos de las pirámides.</p>
                @if ($event->venue_address)<p class="ev-address">{!! $icon('pin') !!}<span>{{ $event->venue_address }}</span></p>@endif
                <div class="ev-route">
                    <div>{!! $icon('car') !!}<span><b>En auto</b>Aprox. 1 h desde la CDMX. Estacionamiento en el hotel ($60 por día).</span></div>
                    <div>{!! $icon('alert') !!}<span><b>Transporte</b>El paquete no incluye transporte.</span></div>
                </div>
                @if (trim((string) data_get($event->content, 'text.transport')))<p><strong>En transporte público:</strong> {{ data_get($event->content, 'text.transport') }}</p>@endif
                @if ($event->venue_map_url && $event->show('map'))<p><a class="ev-btn ev-btn--ghost" href="{{ $event->venue_map_url }}" target="_blank" rel="noopener" data-ev-track="click_map">{!! $icon('map') !!} Abrir en Google Maps</a></p>@endif
            </div>
            <figure class="ev-photo">
                <img src="{{ $img('teotihuacan-calzada') }}" alt="Zona arqueológica de Teotihuacán a pocos minutos del hotel" width="1400" height="949" loading="lazy">
                <figcaption>Teotihuacán, a pocos minutos · imagen ilustrativa</figcaption>
            </figure>
        </div>
        <div class="ev-grid ev-grid--3" style="margin-top:28px">
            <article class="ev-card"><div class="ico">{!! $icon('tent') !!}</div><h3>Zona de camping</h3><p>Jardín amplio frente a la capilla con baños, vestidores y regaderas exclusivos.</p></article>
            <article class="ev-card"><div class="ico">{!! $icon('moon') !!}</div><h3>Habitaciones</h3><p>Se reservan directamente con el hotel (hacienda, campestre y estándar, todas con baño privado y toallas).</p></article>
            <article class="ev-card"><div class="ico">{!! $icon('mic') !!}</div><h3>Auditorio</h3><p>Para 300 personas: las conferencias son bajo techo y lejos del frío.</p></article>
            <article class="ev-card"><div class="ico">{!! $icon('cash') !!}</div><h3>Restaurante Don José</h3><p>Desayuno buffet de $350 (8:00 a 14:00 h) y comida a la carta. El restobar abre por la noche.</p></article>
            <article class="ev-card"><div class="ico">{!! $icon('car') !!}</div><h3>Estacionamiento</h3><p>$60 por día; se paga directamente en el hotel. Tu coche queda resguardado.</p></article>
            <article class="ev-card"><div class="ico">{!! $icon('flame') !!}</div><h3>Fogatas</h3><p>Área controlada y separada de casas de campaña y telescopios.</p></article>
        </div>
        <figure class="ev-photo ev-photo--wide" style="margin-top:22px"><img src="{{ $img('campfire') }}" alt="Fogata encendida de noche" width="1000" height="666" loading="lazy"><figcaption>Fogata en área controlada, lejos de los telescopios · imagen ilustrativa</figcaption></figure>
    </div>
</section>

{{-- 9 · Qué traer y reglas de oro --}}
<section class="ev-sec ev-sec--mid" id="que-traer" aria-labelledby="h-traer">
    <div class="ev-wrap">
        <div class="ev-head-row">
            <h2 id="h-traer">Qué traer y reglas de oro</h2>
            <button type="button" class="ev-btn ev-btn--ghost ev-btn--sm" id="evPrint">{!! $icon('printer') !!} Imprimir lista</button>
        </div>
        <div class="ev-twin">
            <div class="ev-callout">
                @if ($event->asset('galileo'))<img src="{{ $event->asset('galileo') }}" alt="" aria-hidden="true" loading="lazy" width="96" height="127">@endif
                <div><h3>¿Tienes telescopio? ¡Tráetelo!</h3><p>Si no, observa con los nuestros.</p></div>
            </div>
            <div class="ev-callout ev-callout--cold">
                <span class="ev-callout__ico">{!! $icon('thermo') !!}</span>
                <div><h3>Noche de invierno: 6–7 °C o menos</h3><p>Vístete como si fueras a la montaña: es mejor que te sobre un suéter a que te falte. Las conferencias son bajo techo y habrá bebidas calientes a la venta.</p></div>
            </div>
        </div>
        <div class="ev-grid ev-grid--2">
            <div>
                <h3>Tu mochila de cazador de estrellas</h3>
                <ul class="ev-list ev-list--box">
                    <li>Ropa de invierno en capas, gorro, guantes, bufanda y cobija.</li>
                    <li>Linterna con luz roja (o el celofán rojo de tu kit sobre una lámpara).</li>
                    <li>Para camping: casa de campaña, sleeping bag y aislante o colchón inflable.</li>
                    <li>Silla plegable o tapete para recostarte a ver el cielo.</li>
                    <li>Termo para bebidas calientes.</li>
                    <li>Binoculares o tu propio telescopio, si tienes.</li>
                    <li>Batería portátil para tu celular.</li>
                    <li>Gorra y bloqueador para la observación solar del domingo.</li>
                    <li>Tu comprobante o clave de compra e identificación.</li>
                </ul>
            </div>
            <div>
                <h3>Las reglas de oro de la noche</h3>
                <ol class="ev-rules">
                    <li><b>Solo luz roja.</b> Tus ojos tardan 20–30 minutos en acostumbrarse a la oscuridad y una sola luz blanca lo arruina.</li>
                    <li><b>Pantallas al mínimo.</b> Baja el brillo o usa modo nocturno. En la caza de Gemínidas, celulares guardados.</li>
                    <li><b>Cero láseres para el público.</b> El AIFA está cerca y apuntar a un avión es delito federal. Solo el astrónomo usa el puntero.</li>
                    <li><b>La fogata, en su lugar.</b> Solo en el área designada, lejos de casas de campaña y telescopios.</li>
                    <li><b>Los telescopios se miran, no se mueven.</b> Pide ayuda al operador para ajustar el enfoque.</li>
                </ol>
            </div>
        </div>
    </div>
</section>

{{-- 10 · Kit, rally y diploma --}}
<section class="ev-sec" id="kit" aria-labelledby="h-kit">
    <div class="ev-wrap">
        <h2 id="h-kit">Kit de bienvenida, rally y diploma</h2>
        <div class="ev-grid ev-grid--3">
            <article class="ev-card ev-kit">
                <svg viewBox="0 0 320 130" role="img" aria-label="Ilustración de la pulsera coleccionable"><rect x="10" y="38" width="300" height="54" rx="27" fill="#14213d"/><rect x="10" y="38" width="300" height="54" rx="27" fill="none" stroke="#fed304" stroke-width="3"/><text x="160" y="72" text-anchor="middle" font-family="Georgia,serif" font-size="19" font-weight="700" fill="#fed304" letter-spacing="3">ASTROFEST · 2026</text><circle cx="40" cy="65" r="9" fill="#fed304"/><circle cx="280" cy="65" r="9" fill="#fed304"/></svg>
                <h3>Pulsera coleccionable</h3><p>De acceso: cada edición tendrá la suya. Guárdala, será tu pase de coleccionista.</p>
            </article>
            <article class="ev-card ev-kit">
                <svg viewBox="0 0 320 130" role="img" aria-label="Ilustración del Pasaporte Astrofest"><rect x="100" y="8" width="120" height="114" rx="10" fill="#14213d"/><circle cx="160" cy="48" r="18" fill="none" stroke="#fed304" stroke-width="3"/><path d="M160 36l4 9 10 1-7 7 2 10-9-5-9 5 2-10-7-7 10-1z" fill="#fed304"/><text x="160" y="92" text-anchor="middle" font-family="Georgia,serif" font-size="11" font-weight="700" fill="#fff" letter-spacing="1.5">PASAPORTE</text><text x="160" y="106" text-anchor="middle" font-family="Georgia,serif" font-size="10" fill="#fed304" letter-spacing="2">ASTROFEST</text></svg>
                <h3>Pasaporte Astrofest®</h3><p>Anota tus estrellas fugaces, consigue sellos durante la noche y sigue el mapa del cielo de diciembre.</p>
            </article>
            <article class="ev-card ev-kit">
                <svg viewBox="0 0 320 130" role="img" aria-label="Ilustración del diploma de astrónomo"><rect x="50" y="10" width="220" height="110" rx="6" fill="#fffae3" stroke="#fed304" stroke-width="4"/><rect x="60" y="20" width="200" height="90" rx="3" fill="none" stroke="#14213d" stroke-width="1.2"/><text x="160" y="48" text-anchor="middle" font-family="Georgia,serif" font-size="16" font-weight="700" fill="#14213d">Diploma de astrónomo</text><path d="M110 66h100" stroke="#14213d" stroke-width="1"/><text x="160" y="62" text-anchor="middle" font-family="Georgia,serif" font-size="12" font-style="italic" fill="#5b6478">con tu nombre</text><circle cx="160" cy="90" r="11" fill="#fed304"/><path d="M160 83l2.5 5 5.5.7-4 3.8 1 5.5-5-2.7-5 2.7 1-5.5-4-3.8 5.5-.7z" fill="#14213d"/></svg>
                <h3>Diploma de astrónomo</h3><p>Todos los participantes se llevan un diploma con su nombre, firmado por los organizadores. Se guarda por años.</p>
            </article>
        </div>
        <p style="margin-top:18px"><strong>El rally de las estrellas.</strong> Desde la bienvenida te contamos cómo participar; durante las conferencias y la observación soltamos pistas sobre el cielo y el domingo, en la clausura, ¡premiamos a los ganadores! Tu kit también trae stickers de Galileo y Telescopio, y celofán rojo para convertir cualquier lámpara en linterna de astrónomo.</p>
    </div>
</section>

{{-- 11 · Quiénes te acompañan --}}
<section class="ev-sec ev-sec--mid" id="equipo" aria-labelledby="h-equipo">
    <div class="ev-wrap">
        <h2 id="h-equipo">Quiénes te acompañan</h2>
        <div class="ev-team">
            <article class="ev-card ev-team__lead">
                @if ($event->asset('luz'))<img class="ev-team__photo" src="{{ $event->asset('luz') }}" alt="Luz Calderón, astrónoma y anfitriona" loading="lazy" width="220" height="220">
                @else<div class="ev-team__avatar" aria-hidden="true">{!! $icon('star') !!}</div>@endif
                <div><p class="ev-eyebrow">Anfitriona</p><h3>Luz Calderón · Astrónoma</h3>
                <p>Divulgadora de la ciencia con muchos años de trayectoria y una comunidad enorme que la sigue. Ha organizado noches de estrellas con cientos de asistentes y es la voz que te guiará toda la noche: da la bienvenida, imparte conferencias y te enseña a leer el cielo.</p></div>
            </article>
            <article class="ev-card">
                <div class="ev-duo">
                    @if ($event->asset('galileo'))<img src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" loading="lazy" width="265" height="350">@endif
                    @if ($event->asset('telescopio'))<img src="{{ $event->asset('telescopio') }}" alt="Telescopio, mascota de Astrofest, un telescopio con brazos y zapatos" loading="lazy" width="250" height="303">@endif
                </div>
                <h3>Galileo y Telescopio</h3>
                <p>Tus guías del cielo: Galileo nunca deja de hacer preguntas y Telescopio siempre señala algo nuevo. Los encontrarás en el pasaporte, el diploma y los stickers.</p>
            </article>
            <article class="ev-card">
                <div class="ico">{!! $icon('telescope') !!}</div>
                <h3>Equipo de telescopios</h3>
                <p>Jóvenes astrónomos al pie del cañón toda la noche, operando los telescopios y resolviendo tus dudas.</p>
            </article>
        </div>
    </div>
</section>

{{-- 12 · Preguntas frecuentes --}}
<section class="ev-sec" id="preguntas" aria-labelledby="h-faq">
    <div class="ev-wrap">
        <h2 id="h-faq">Preguntas frecuentes</h2>
        @php
            $faq = [
                'Antes de ir' => [
                    ['¿Necesito saber de astronomía?', 'Para nada. Astrofest® está pensado para que cualquiera disfrute: te explicamos todo con palabras sencillas.'],
                    ['¿Hace mucho frío?', 'Sí: la temperatura puede bajar a 6–7 °C o menos. Trae ropa de invierno en capas, gorro, guantes y cobija.'],
                    ['¿Puedo llevar mi telescopio o binoculares?', '¡Claro! Es más, te lo recomendamos. Nuestro equipo te ayuda a usarlo.'],
                    ['¿A qué hora puedo llegar?', 'El check-in en el hotel es desde las ' . ($start?->format('H:i') ?? '15:00') . ' h y el registro del evento a las 16:00. Puedes llegar antes a comer al restaurante.'],
                    ['¿Los niños pagan?', "Los menores de {$minorsAge} años no pagan. A partir de los {$minorsAge} años pagan como cualquier asistente."],
                ],
                'Cielo y clima' => [
                    ['¿Cuántas estrellas fugaces voy a ver?', 'Nadie puede prometer un número exacto. El 12 de diciembre es la víspera del máximo de las Gemínidas, con Luna casi nueva: una de las mejores noches del año para verlas cerca de la CDMX.'],
                    ['¿Y si se nubla?', 'La fiesta sigue con nuestro Plan Nube (charlas, planetario o proyección y fogata) y te llevas un crédito para la próxima edición. Consulta la política completa en Políticas del evento.'],
                ],
                'Boletos y pagos' => [
                    ['¿Cómo pago y cómo entro?', 'Pagas en línea en esta página con Mercado Pago. Recibirás una confirmación con tu clave de acceso y un código QR; al llegar, muéstralo en el registro y recibe tu pulsera y tu kit.'],
                    ['¿La comida está incluida?', 'No. Puedes comer en el restaurante del hotel (desayuno buffet de $350), comprar en el restobar o llevar tus propios alimentos.'],
                ],
                'Cómo llegar' => [
                    ['¿Hay estacionamiento?', 'Sí, cuesta $60 por día y se paga directamente en el hotel.'],
                    ['¿Incluye transporte?', 'No. Puedes llegar en auto (≈1 h desde la CDMX).'],
                    ['¿Puedo visitar las pirámides?', 'Sí, por tu cuenta, el domingo después del evento, con los horarios y reglas de la zona arqueológica.'],
                ],
            ];
            if ($event->show('rooms') && $hotelUrl) {
                $faq['Boletos y pagos'][] = ['¿Puedo compartir habitación con mis amigos o familia?', 'Las habitaciones se reservan directamente con el hotel desde el botón «Reservar habitación en el hotel». Aquí compras tu entrada de Astrofest®. Cada persona necesita su entrada.'];
            }
            $cols = [array_slice($faq, 0, 2, true), array_slice($faq, 2, 2, true)];
        @endphp
        <div class="ev-faq-cols">
            @foreach ($cols as $ci => $group)
                <div class="ev-faq">
                    @foreach ($group as $name => $items)
                        <h3>{{ $name }}</h3>
                        @foreach ($items as $ii => [$q, $a])
                            <details @if ($ci === 0 && $loop->parent->first && $ii === 0) open @endif><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
                        @endforeach
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- 13 · Cierre --}}
<section class="ev-sec ev-close" aria-labelledby="h-cierre" style="background-image:linear-gradient(rgba(20,33,61,.86),rgba(20,33,61,.92)),url('{{ $img('hero-geminids') }}')">
    <div class="ev-narrow">
        @if ($event->asset('galileo'))<img class="ev-char" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" loading="lazy" width="150" height="198" style="width:120px">@endif
        <h2 id="h-cierre">{{ $event->text('closing.title') }}</h2>
        <p class="ev-muted">{{ $event->text('closing.text') }}</p>
        @if ($event->hasPrice())<p class="ev-close__price">{{ $money($event->price) }} MXN <small>por persona · IVA incluido · camping incluido</small></p>@endif
        @if ($state === 'open' && $available !== null && $available <= 30)<p class="ev-chip ev-chip--hot">Quedan {{ $available }} {{ $available === 1 ? 'lugar' : 'lugares' }}</p>@endif
        <div class="ev-cta" style="justify-content:center">
            @if ($state === 'open')<a class="ev-btn" href="#entrada" data-ev-track="cta_closing">{{ $event->text('hero.cta') }}</a>@endif
            @if ($event->contact_whatsapp)<a class="ev-btn ev-btn--ghost" href="https://wa.me/{{ preg_replace('/\D+/', '', $event->contact_whatsapp) }}" target="_blank" rel="noopener" data-ev-track="click_whatsapp">{!! $icon('whatsapp') !!} Escríbenos por WhatsApp</a>@endif
        </div>
    </div>
</section>

@if ($state === 'open' && $event->hasPrice())
    <div class="ev-bar" id="evBar" hidden>
        <div><small>Por persona</small><strong>{{ $money($event->price) }} MXN</strong></div>
        <a class="ev-btn ev-btn--sm" href="#entrada" data-ev-track="cta_sticky">{{ $event->text('hero.cta') }}</a>
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    // Contador: noches que faltan, con la zona horaria del evento.
    var c = document.getElementById('evCount');
    if (c) {
        var tz = 'America/Mexico_City', start = new Date(c.getAttribute('data-start'));
        var ymd = function (d) { var p = new Intl.DateTimeFormat('en-CA', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit' }).format(d).split('-'); return Date.UTC(p[0], p[1] - 1, p[2]); };
        var tick = function () { var n = Math.round((ymd(start) - ymd(new Date())) / 864e5), t = document.getElementById('evCountText');
            t.innerHTML = n > 0 ? 'Faltan <strong>' + n + '</strong> ' + (n === 1 ? 'noche' : 'noches') + ' para mirar hacia arriba' : (n === 0 ? 'Hoy miramos hacia arriba' : ''); };
        tick(); setInterval(tick, 60000);
    }

    // Selector de cantidad con total al instante
    var f = document.getElementById('evBuy');
    if (f) {
        var p = JSON.parse(f.getAttribute('data-pricing')), q = document.getElementById('evQty'), out = document.getElementById('evTotal'), sv = document.getElementById('evSaving');
        var fmt = function (n) { return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: n % 1 ? 2 : 0, maximumFractionDigits: 2 }); };
        var calc = function () {
            var max = parseInt(q.max, 10) || 1, n = Math.min(max, Math.max(1, parseInt(q.value, 10) || 1)); q.value = n;
            var sub = p.unit * n, pct = 0; p.rules.forEach(function (r) { if (n >= r.min) pct += r.percent; }); pct = Math.min(100, pct);
            var tot = Math.round(sub * (100 - pct)) / 100; out.textContent = fmt(tot); sv.textContent = pct ? '(ahorras ' + fmt(Math.round(sub * pct) / 100) + ')' : '';
        };
        f.addEventListener('click', function (e) { var s = e.target.getAttribute('data-step'); if (s) { q.value = (parseInt(q.value, 10) || 1) + parseInt(s, 10); calc(); } });
        q.addEventListener('input', calc); calc();
        evPush('view_item', { value: p.unit, currency: 'MXN' });
    }

    // Programa en pestañas (sin JavaScript se ven los dos días)
    var tabs = document.getElementById('evTabs');
    if (tabs) {
        tabs.classList.add('is-tabs');
        var btns = [].slice.call(tabs.querySelectorAll('[role=tab]')), panels = [].slice.call(tabs.querySelectorAll('[role=tabpanel]'));
        var show = function (i, focus) { btns.forEach(function (b, j) { b.setAttribute('aria-selected', j === i ? 'true' : 'false'); b.tabIndex = j === i ? 0 : -1; panels[j].hidden = j !== i; }); if (focus) btns[i].focus(); };
        btns.forEach(function (b, i) { b.addEventListener('click', function () { show(i); }); b.addEventListener('keydown', function (e) { if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') { e.preventDefault(); show((i + (e.key === 'ArrowRight' ? 1 : btns.length - 1)) % btns.length, true); } }); });
        show(0);
    }

    // Carrusel de objetos
    var rail = document.getElementById('evRail');
    if (rail) { [].slice.call(document.querySelectorAll('[data-rail]')).forEach(function (b) { b.addEventListener('click', function () { rail.scrollBy({ left: parseInt(b.getAttribute('data-rail'), 10) * Math.min(rail.clientWidth * .8, 420), behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }); }); }); }

    // Barra fija de compra en celular: solo cuando ni la portada ni la entrada están a la vista
    var bar = document.getElementById('evBar');
    if (bar && 'IntersectionObserver' in window) {
        var seen = { hero: true, entrada: false }, upd = function () { bar.hidden = seen.hero || seen.entrada; };
        var io = new IntersectionObserver(function (es) { es.forEach(function (e) { seen[e.target.getAttribute('data-k')] = e.isIntersecting; }); upd(); });
        [['.ev-hero', 'hero'], ['#entrada', 'entrada']].forEach(function (a) { var el = document.querySelector(a[0]); if (el) { el.setAttribute('data-k', a[1]); io.observe(el); } });
    }

    // Imprimir solo la lista de «qué traer»
    var pr = document.getElementById('evPrint');
    if (pr) { pr.addEventListener('click', function () { document.body.classList.add('ev-printing'); window.print(); setTimeout(function () { document.body.classList.remove('ev-printing'); }, 400); }); }
})();
</script>
@endpush
