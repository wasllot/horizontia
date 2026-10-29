@extends('events.layout', ['onLanding' => true])

@php
    $money = fn ($v) => '$' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $img = fn (string $k) => asset('images/eventos/stock/' . $k . '.webp');
    $usedImages = ['lago-moke', 'lago-reflejo', 'admirando-galaxia', 'la-silla', 'atardecer', 'amanecer', 'hero-geminids', 'telescopios', 'moon', 'saturn', 'orion-nebula', 'pleiades', 'albireo', 'sun', 'teotihuacan-sol', 'teotihuacan-calzada', 'campfire'];
    $start = $event->local($event->start_at);
    $end = $event->local($event->end_at);
    $cd = $event->local($event->countdown_from);
    $minorsAge = (int) (data_get($event->content, 'minors_age') ?: 6);
    $hotelUrl = $event->hotel_booking_url;
    $earlyActive = collect($quote['discounts'])->first(fn ($d) => str_contains(mb_strtolower($d['label']), 'preventa'));
    $pricing = ['unit' => (float) $event->price, 'rules' => collect((array) $event->discounts)->filter(fn ($r) => !empty($r['enabled']) && (float) ($r['percent'] ?? 0) > 0)->map(fn ($r, $k) => ['key' => $k, 'percent' => (float) $r['percent'], 'min' => $k === 'group' ? (int) ($r['min_qty'] ?? 4) : 1])->values()->all(), 'iva' => (float) $event->iva_rate];
    $weekend = $start && $end ? 'Sáb ' . $start->format('j') . ' – dom ' . $end->format('j') . ' ' . $start->locale('es')->translatedFormat('M Y') : '';
    $icon = fn (string $n) => '<svg class="ev-i" aria-hidden="true"><use href="#i-' . $n . '"/></svg>';
    $takenNow = $event->capacity ? max(0, (int) $event->capacity - (int) $available) : null;

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
    $heroImg = $event->asset('hero_desktop') ?: $img('lago-moke');
@endphp

@push('head')
<link rel="preload" as="image" href="{{ $heroImg }}" fetchpriority="high">
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'Event',
    'name' => $event->name,
    'description' => $event->text('seo.description'),
    'startDate' => $start?->toIso8601String(), 'endDate' => $end?->toIso8601String(),
    'eventStatus' => 'https://schema.org/EventScheduled',
    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
    'location' => array_filter(['@type' => 'Place', 'name' => $event->venue_name, 'address' => array_filter(['@type' => 'PostalAddress', 'streetAddress' => $event->venue_address, 'addressLocality' => $event->venue_city, 'addressCountry' => 'MX'])]),
    'image' => $event->asset('og') ? [$event->asset('og')] : [$heroImg],
    'offers' => $event->hasPrice() ? ['@type' => 'Offer', 'priceCurrency' => 'MXN', 'price' => (string) (float) $event->price, 'url' => $event->url(), 'availability' => $state === 'sold_out' ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock'] : null,
    'performer' => ['@type' => 'Person', 'name' => 'Luz Calderón'],
    'organizer' => ['@type' => 'Organization', 'name' => 'Horizontia', 'url' => url('/')],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('body')

{{-- ACTO I · Portada: la Vía Láctea sobre el lago, fundida con el encabezado y con la sección siguiente --}}
<section class="ev-hero" aria-labelledby="ev-h1" id="evHero">
    <div class="ev-hero__bg" data-par="0.18" style="--img:url('{{ $heroImg }}')" role="presentation"></div>
    <div class="ev-stars" aria-hidden="true"></div>
    <span class="ev-shoot" aria-hidden="true"></span><span class="ev-shoot" aria-hidden="true"></span><span class="ev-shoot" aria-hidden="true"></span>
    <div class="ev-hero__in">
        <p class="ev-hero__kicker" style="--d:.1s">{{ $event->text('hero.supra') }}</p>
        <h1 id="ev-h1" class="ev-hero__brand" style="--d:.25s"><img src="{{ $event->asset('logo') }}" alt="{{ $event->text('seo.h1') }}" width="900" height="234" fetchpriority="high"></h1>
        <h2 class="ev-hero__q" style="--d:.5s">{{ $event->text('hero.title') }}</h2>
        @if ($start)<p class="ev-hero__when" style="--d:.7s">{{ $start->locale('es')->translatedFormat('l j \d\e F \d\e Y') }}<span aria-hidden="true">✦</span>{{ $event->venue_name }}</p>@endif
        <p class="ev-hero__sub" style="--d:.85s">{{ $event->text('hero.host') }}. {{ $event->text('hero.subtitle') }}</p>
        <div class="ev-cta" style="--d:1s">
            @if ($state === 'open')<a class="ev-btn ev-btn--glow" href="#entrada" data-ev-track="cta_hero">{{ $event->text('hero.cta') }}</a>@endif
            <a class="ev-btn ev-btn--ghost" href="#que-vamos-a-ver">{{ $event->text('hero.secondary') }}</a>
        </div>
        <p class="ev-hero__price" style="--d:1.15s">
            @if ($event->hasPrice())<span>{{ $money($event->price) }} MXN · IVA incluido</span>@endif
            @if ($state === 'open' && $available !== null)<span class="is-hot">¡Cupo limitado!</span>@endif
            @if ($earlyActive)<span class="is-hot">{{ $earlyActive['label'] }} −{{ rtrim(rtrim(number_format($earlyActive['percent'], 2), '0'), '.') }} %</span>@endif
        </p>
        @if ($cd && $start && !$event->isFinished() && now()->gte($cd))
            <p class="ev-count" id="evCount" data-start="{{ $start->toIso8601String() }}" aria-live="off"><span id="evCountText">&nbsp;</span></p>
        @endif
    </div>
    <div class="ev-hero__chars">
        @if ($event->asset('galileo'))<img class="is-l" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" width="265" height="350">@endif
        @if ($event->asset('telescopio'))<img class="is-r" src="{{ $event->asset('telescopio') }}" alt="Telescopio, mascota de Astrofest, un telescopio con brazos y zapatos" width="250" height="303">@endif
    </div>
    <a class="ev-scroll" href="#datos" aria-label="Ir al contenido"><span></span></a>
    <button type="button" class="ev-wish" id="evWish" aria-live="polite"><span>✦</span> Pide un deseo</button>
</section>

{{-- Datos clave: una tarjeta de vidrio que cabalga entre la portada y lo que sigue --}}
<section class="ev-facts" id="datos" aria-label="Datos clave">
    <div class="ev-wrap"><ul data-reveal>
        @if ($weekend)<li>{!! $icon('calendar') !!}<span>Fecha</span><b>{{ $weekend }}</b></li>@endif
        @if ($start)<li>{!! $icon('clock') !!}<span>Check-in</span><b>{{ $start->format('H:i') }} h</b></li>@endif
        @if ($event->venue_name)<li>{!! $icon('pin') !!}<span>Lugar</span><b>{{ $event->venue_name }}</b></li>@endif
        <li>{!! $icon('car') !!}<span>Distancia</span><b>~1 h de la CDMX</b></li>
        @if ($event->hasPrice())<li class="price">{!! $icon('ticket') !!}<span>Por persona · camping incluido</span><b>{{ $money($event->price) }} MXN</b></li>@endif
    </ul></div>
</section>

{{-- ACTO II · Imagina --}}
<section class="ev-sec ev-night ev-story" id="que-es" aria-labelledby="h-que-es">
    @include('events.constellation', ['kind' => 'gem'])
    <div class="ev-wrap">
        <div class="ev-split">
            <div data-reveal>
                <p class="ev-eyebrow">Una noche distinta</p>
                <h2 id="h-que-es">¿Qué es <em>Astrofest®</em>?</h2>
                <p class="ev-big">Cae la tarde sobre Teotihuacán, el cielo se pinta de naranja detrás de los cerros y, una a una, se encienden las primeras estrellas.</p>
                <p>Apagamos las luces, prendemos linternas rojas y, durante toda la noche, miramos hacia arriba juntos. No es una excursión ni un campamento cualquiera: es una noche diseñada momento a momento para que te enamores del cielo, con astrónomos, telescopios, fogata, música y la cacería de estrellas fugaces de las Gemínidas.</p>
                <p><strong>No necesitas saber nada de astronomía.</strong> Si tienes un telescopio guardado en el clóset, tráelo: te ayudamos a usarlo y serás el héroe de la noche.</p>
            </div>
            <figure class="ev-arch" data-reveal style="--d:.15s">
                <img src="{{ $img('telescopios') }}" alt="Personas observando el cielo nocturno junto a un telescopio" width="1200" height="806" loading="lazy" data-par="-0.05">
                <figcaption>Observación guiada con telescopios · imagen ilustrativa</figcaption>
                <img class="ev-arch__moon" src="{{ $img('saturn') }}" alt="" aria-hidden="true" width="140" height="140" loading="lazy">
            </figure>
        </div>
        <ul class="ev-pillars" data-reveal>
            <li>{!! $icon('star') !!}<b>Ciencia de verdad</b><span>Astrónomos y divulgadores te explican el cielo con palabras sencillas. Sin humo, sin promesas exageradas.</span></li>
            <li>{!! $icon('map') !!}<b>Teotihuacán y el cielo</b><span>Una de las ciudades más grandes de la antigüedad se trazó mirando al Sol y a las estrellas.</span></li>
            <li>{!! $icon('eye-off') !!}<b>La noche de la luz roja</b><span>Apagamos reflectores y pantallas para que tus ojos vean muchas más estrellas.</span></li>
            <li>{!! $icon('users') !!}<b>Para toda la familia</b><span>Rally con premios, diploma para todos y actividades para chicos y grandes. Menores de {{ $minorsAge }} años no pagan.</span></li>
        </ul>
        <ul class="ev-incl" aria-label="Lo que incluye la experiencia" data-reveal>
            @foreach ([['telescope', 'Telescopios'], ['mic', 'Conferencias'], ['trophy', 'Rally astronómico'], ['music', 'Música temática'], ['flame', 'Fogatas y CampingZone'], ['dome', 'Planetario móvil'], ['headphones', 'DJ en vivo'], ['gift', 'AstroRifa']] as [$i, $t])
                <li><span class="ev-incl__ico">{!! $icon($i) !!}</span>{{ $t }}</li>
            @endforeach
        </ul>
        <p class="ev-mark" data-reveal><b>Una marca para coleccionar.</b> Astrofest® Gemínidas 2026 es la primera edición de una nueva temporada de noches de estrellas. Cada lluvia de meteoros tendrá su propia pulsera, parche, playera y mapa del cielo. Guarda tu pulsera: será tu pase de coleccionista.</p>
    </div>
</section>

{{-- ACTO III · Lo que verás --}}
<section class="ev-sec ev-night ev-see" id="que-vamos-a-ver" aria-labelledby="h-ver">
    <div class="ev-bg" style="background-image:url('{{ $img('la-silla') }}')" data-par="0.08" role="presentation"></div>
    <div class="ev-wrap">
        <div data-reveal>
            <p class="ev-eyebrow">Lo que verás</p>
            <h2 id="h-ver">¿Qué vamos a <em>ver</em> en el cielo?</h2>
            <p class="ev-big"><strong>Una noche para mirar hacia arriba.</strong> Cada diciembre la Tierra cruza el polvo que dejó el asteroide Faetón, y el cielo se llena de estrellas fugaces. ¡Y este año, sin Luna que estorbe!</p>
        </div>

        <ul class="ev-stats" aria-label="Por qué el 12 de diciembre es una gran noche">
            <li data-reveal><strong><span data-count="9">9</span> dic</strong><span><b>Luna nueva.</b> El día 12 solo un creciente delgado que se oculta temprano: cielo oscuro el resto de la noche.</span></li>
            <li data-reveal style="--d:.12s"><strong><span data-count="13">13</span>–<span data-count="14">14</span> dic</strong><span><b>Máximo de las Gemínidas.</b> La noche del 12 es la víspera y ya tiene mucha actividad… y cae en sábado.</span></li>
            <li data-reveal style="--d:.24s"><strong><span data-count="2">2</span>:00 h</strong><span><b>El radiante en su punto más alto.</b> La gran observación será de medianoche a las 2:00.</span></li>
        </ul>

        <details class="ev-acc" data-reveal>
            <summary>Ciencia en un minuto: ¿qué son las Gemínidas?</summary>
            <p>Cada diciembre la Tierra cruza una nube de polvo que dejó un asteroide llamado 3200 Faetón. Esos granitos, muchos del tamaño de un grano de arena, chocan contra la atmósfera a unos 35 kilómetros por segundo y se calientan tanto que brillan: eso es una estrella fugaz. Se llaman Gemínidas porque, si sigues sus trazos hacia atrás, todas parecen salir de un punto en la constelación de Géminis, cerca de Cástor y Pólux: el <em>radiante</em>.</p>
        </details>
        <p class="ev-honest" data-reveal>{!! $icon('shield') !!}<span><strong>Un compromiso honesto:</strong> no prometemos un número exacto de estrellas fugaces por hora; el cielo tiene sus propios planes. Teotihuacán no es un cielo de alta montaña porque la CDMX está cerca, pero las Gemínidas son brillantes y se ven muy bien desde aquí.</span></p>

        <div class="ev-rail-head" data-reveal>
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

        <div class="ev-feature" data-reveal>
            <figure class="ev-photo"><img src="{{ $img('teotihuacan-sol') }}" alt="Pirámide del Sol en Teotihuacán bajo un cielo despejado" width="1400" height="917" loading="lazy"><figcaption>Pirámide del Sol, Teotihuacán · imagen ilustrativa</figcaption></figure>
            <div>
                <p class="ev-eyebrow">Mesoamérica</p>
                <h3>Teotihuacán y el cielo</h3>
                <p>La Ciudad de los Dioses no se construyó al azar. Sus calzadas y pirámides guardan alineaciones con el Sol y las estrellas que los arqueoastrónomos siguen estudiando. En Astrofest® escucharás cómo observaban el cielo los pueblos de Mesoamérica… mientras ves caer estrellas fugaces en el mismo cielo que ellos miraban.</p>
            </div>
        </div>

        @if ($state === 'open')
            <div class="ev-band" data-reveal>
                <p><b>{{ $start ? ucfirst($start->locale('es')->translatedFormat('l j \d\e F')) : 'Sábado 12 de diciembre' }}</b> · a 1 hora de la CDMX · {{ $money($event->price) }} MXN con camping incluido</p>
                <a class="ev-btn ev-btn--glow" href="#entrada" data-ev-track="cta_band">{{ $event->text('hero.cta') }}</a>
            </div>
        @endif
    </div>
</section>

{{-- ACTO IV · La noche, hora por hora (el cielo cambia: atardecer → noche → amanecer) --}}
<section class="ev-sec ev-night ev-prog" id="programa" aria-labelledby="h-programa">
    <div class="ev-bg ev-bg--top" style="background-image:url('{{ $img('atardecer') }}')" role="presentation"></div>
    <div class="ev-bg ev-bg--bottom" style="background-image:url('{{ $img('amanecer') }}')" role="presentation"></div>
    @include('events.constellation', ['kind' => 'orion'])
    <div class="ev-wrap">
        <div data-reveal>
            <p class="ev-eyebrow">Hora por hora</p>
            <h2 id="h-programa">Programa de la <em>noche</em></h2>
            <p class="ev-muted">Programa sujeto a cambios por clima. Todo ocurre dentro del hotel.</p>
        </div>
        <div class="ev-tabs" id="evTabs">
            <div class="ev-tabs__list" role="tablist" aria-label="Días del programa">
                <button type="button" role="tab" id="tab-sab" aria-controls="pan-sab" aria-selected="true">Sábado {{ $start?->format('j') ?? '12' }}</button>
                <button type="button" role="tab" id="tab-dom" aria-controls="pan-dom" aria-selected="false" tabindex="-1">Domingo {{ $end?->format('j') ?? '13' }}</button>
            </div>
            @foreach ([['sab', 'Sábado ' . ($start?->format('j') ?? '12') . ' de diciembre', $sat], ['dom', 'Domingo ' . ($end?->format('j') ?? '13') . ' de diciembre', $sun]] as [$id, $title, $rows])
                <div class="ev-tabs__panel" role="tabpanel" id="pan-{{ $id }}" aria-labelledby="tab-{{ $id }}">
                    <h3 class="ev-tabs__h">{{ $title }}</h3>
                    <ol class="ev-time" data-line>
                        @foreach ($rows as $r)
                            <li class="{{ !empty($r[4]) ? 'is-hl' : '' }}" data-reveal><span class="ev-time__ico">{!! $icon($r[3]) !!}</span><time>{{ $r[0] }}</time><b>{{ $r[1] }}</b><span>{{ $r[2] }}</span></li>
                        @endforeach
                    </ol>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Kit: lo que te llevas (ancla el valor antes del precio) --}}
<section class="ev-sec ev-night ev-kitsec" id="kit" aria-labelledby="h-kit">
    <div class="ev-wrap">
        <div data-reveal>
            <p class="ev-eyebrow">Tu recuerdo</p>
            <h2 id="h-kit">Kit de bienvenida, rally y <em>diploma</em></h2>
        </div>
        <div class="ev-kit-grid">
            <article class="ev-kit" data-reveal>
                <svg viewBox="0 0 320 130" role="img" aria-label="Ilustración de la pulsera coleccionable"><rect x="10" y="38" width="300" height="54" rx="27" fill="#14213d"/><rect x="10" y="38" width="300" height="54" rx="27" fill="none" stroke="#fed304" stroke-width="3"/><text x="160" y="72" text-anchor="middle" font-family="Georgia,serif" font-size="19" font-weight="700" fill="#fed304" textLength="214" lengthAdjust="spacingAndGlyphs">ASTROFEST · 2026</text><circle cx="40" cy="65" r="9" fill="#fed304"/><circle cx="280" cy="65" r="9" fill="#fed304"/></svg>
                <h3>Pulsera coleccionable</h3><p>De acceso: cada edición tendrá la suya. Guárdala, será tu pase de coleccionista.</p>
            </article>
            <article class="ev-kit" data-reveal style="--d:.12s">
                <svg viewBox="0 0 320 130" role="img" aria-label="Ilustración del Pasaporte Astrofest"><rect x="100" y="8" width="120" height="114" rx="10" fill="#14213d" stroke="#fed304" stroke-width="2"/><circle cx="160" cy="48" r="18" fill="none" stroke="#fed304" stroke-width="3"/><path d="M160 36l4 9 10 1-7 7 2 10-9-5-9 5 2-10-7-7 10-1z" fill="#fed304"/><text x="160" y="92" text-anchor="middle" font-family="Georgia,serif" font-size="11" font-weight="700" fill="#fff" letter-spacing="1.5">PASAPORTE</text><text x="160" y="106" text-anchor="middle" font-family="Georgia,serif" font-size="10" fill="#fed304" letter-spacing="2">ASTROFEST</text></svg>
                <h3>Pasaporte Astrofest®</h3><p>Anota tus estrellas fugaces, consigue sellos durante la noche y sigue el mapa del cielo de diciembre.</p>
            </article>
            <article class="ev-kit" data-reveal style="--d:.24s">
                <svg viewBox="0 0 320 130" role="img" aria-label="Ilustración del diploma de astrónomo"><rect x="50" y="10" width="220" height="110" rx="6" fill="#fffae3" stroke="#fed304" stroke-width="4"/><rect x="60" y="20" width="200" height="90" rx="3" fill="none" stroke="#14213d" stroke-width="1.2"/><text x="160" y="48" text-anchor="middle" font-family="Georgia,serif" font-size="16" font-weight="700" fill="#14213d" textLength="176" lengthAdjust="spacingAndGlyphs">Diploma de astrónomo</text><path d="M110 66h100" stroke="#14213d" stroke-width="1"/><text x="160" y="62" text-anchor="middle" font-family="Georgia,serif" font-size="12" font-style="italic" fill="#5b6478">con tu nombre</text><circle cx="160" cy="90" r="11" fill="#fed304"/><path d="M160 83l2.5 5 5.5.7-4 3.8 1 5.5-5-2.7-5 2.7 1-5.5-4-3.8 5.5-.7z" fill="#14213d"/></svg>
                <h3>Diploma de astrónomo</h3><p>Todos los participantes se llevan un diploma con su nombre, firmado por los organizadores. Se guarda por años.</p>
            </article>
        </div>
        <p class="ev-mark" data-reveal><b>El rally de las estrellas.</b> Desde la bienvenida te contamos cómo participar; durante las conferencias y la observación soltamos pistas sobre el cielo y el domingo, en la clausura, ¡premiamos a los ganadores! Tu kit también trae stickers de Galileo y Telescopio, y celofán rojo para convertir cualquier lámpara en linterna de astrónomo.</p>
    </div>
</section>

{{-- ACTO V · Tu lugar: el momento cumbre --}}
<section class="ev-sec ev-night ev-tick" id="entrada" aria-labelledby="h-entrada">
    <div class="ev-bg" style="background-image:url('{{ $img('admirando-galaxia') }}')" data-par="0.08" role="presentation"></div>
    <div class="ev-wrap">
        <div class="ev-tick__grid">
            <div class="ev-tick__copy">
                <div data-reveal>
                    <p class="ev-eyebrow">Tu lugar bajo las estrellas</p>
                    <h2 id="h-entrada">Tu entrada a <em>Astrofest®</em></h2>
                    <p class="ev-big">Una sola entrada. Toda la experiencia.</p>
                </div>
                <ul class="ev-value" data-reveal>
                    <li>{!! $icon('mic') !!}<span>Conferencias, taller de cielo y observación guiada con telescopios</span></li>
                    <li>{!! $icon('flame') !!}<span>Fogata, música temática, DJ en vivo, Planetario Móvil y rally con premios</span></li>
                    <li>{!! $icon('gift') !!}<span>Pulsera coleccionable, Pasaporte, mapa del cielo, stickers y diploma</span></li>
                    <li>{!! $icon('tent') !!}<span>Camping para 1 persona con tu casa de campaña: baños, vestidores y regaderas</span></li>
                </ul>
                <details class="ev-acc ev-acc--sm" data-reveal>
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

                <div class="ev-calm" id="plan-nube" data-reveal>
                    <h3>Compra tranquilo: Astrofest depende del cielo</h3>
                    <p class="ev-muted" style="margin:0 0 12px">…y el cielo no depende de nosotros. Por eso hay Plan Nube.</p>
                    <ul>
                        <li>{!! $icon('cloud') !!}<span><b>¿Nublado?</b> La fiesta sigue con charlas, planetario o proyección y fogata, y te llevas un crédito para la próxima edición.</span></li>
                        <li>{!! $icon('cloud-bolt') !!}<span><b>¿Clima peligroso?</b> Reprogramamos o cancelamos por tu seguridad. Tú eliges: nueva fecha o crédito.</span></li>
                        <li>{!! $icon('snow') !!}<span><b>¿Frío?</b> Es parte de la aventura y no es motivo de cancelación. ¡Ven abrigado!</span></li>
                    </ul>
                    <a href="{{ $event->url('politicas') }}">Consulta la política completa</a>
                </div>
            </div>

            <div class="ev-ticket" data-reveal style="--d:.15s">
                @if (session('event_error'))<p class="ev-alert" role="alert">{{ session('event_error') }}</p>@endif
                <p class="ev-ticket__label">Entrada general · Astrofest® Gemínidas 2026</p>
                @if ($event->hasPrice())
                    <div class="ev-ticket__price">{{ $money($event->price) }} <small>MXN por persona</small></div>
                    <p class="ev-tags"><span>{!! $icon('check') !!} IVA incluido</span><span>{!! $icon('check') !!} Camping incluido</span></p>
                @endif
                @foreach ($quote['discounts'] as $d)<p style="margin:0"><span class="ev-pill ev-pill--tag">{{ $d['label'] }} −{{ rtrim(rtrim(number_format($d['percent'], 2), '0'), '.') }} %</span></p>@endforeach

                @if ($state === 'open' && $event->capacity)
                    <div class="ev-meter" role="group" aria-label="Lugares apartados">
                        <div class="ev-meter__bar"><i style="--w:{{ min(100, round($takenNow * 100 / max(1, $event->capacity))) }}%"></i></div>
                        <p><strong>¡Cupo limitado!</strong> @if ($available !== null && $available <= 30) Quedan {{ $available }} {{ $available === 1 ? 'lugar' : 'lugares' }}. @else Los lugares se apartan en orden de pago. @endif</p>
                    </div>
                @endif
                @if ($state === 'open' && $sold >= 10)<p class="ev-proof">{!! $icon('users') !!} Ya hay <strong>{{ $sold }}</strong> personas con su lugar apartado.</p>@endif

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
                        </div>
                        <p class="ev-total">Total <strong id="evTotal">{{ $money($quote['total']) }}</strong> <small>MXN</small> <span class="ev-hint" id="evSaving"></span></p>
                        <button type="submit" class="ev-btn ev-btn--glow ev-btn--block" data-ev-track="add_to_cart_click">Agregar al carrito</button>
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
                @if ($event->show('rooms') && $hotelUrl)
                    <div class="ev-rooms">
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

{{-- El lugar --}}
<section class="ev-sec ev-night ev-place" id="lugar" aria-labelledby="h-lugar">
    <div class="ev-bg" style="background-image:url('{{ $img('lago-reflejo') }}')" data-par="0.06" role="presentation"></div>
    <div class="ev-wrap">
        <div class="ev-split ev-split--rev">
            <div class="ev-glass" data-reveal>
                <p class="ev-eyebrow">Cómo llegar</p>
                <h2 id="h-lugar">El <em>lugar</em></h2>
                <p><strong>{{ $event->venue_name }}</strong>{{ $event->venue_city ? ' · ' . $event->venue_city : '' }}. Un hotel de campo con lago, jardines amplios, capilla, auditorio y restaurante, a pocos minutos de las pirámides.</p>
                @if ($event->venue_address)<p class="ev-address">{!! $icon('pin') !!}<span>{{ $event->venue_address }}</span></p>@endif
                <div class="ev-route">
                    <div>{!! $icon('car') !!}<span><b>En auto</b>Aprox. 1 h desde la CDMX. Estacionamiento en el hotel ($60 por día).</span></div>
                    <div>{!! $icon('alert') !!}<span><b>Transporte</b>El paquete no incluye transporte.</span></div>
                </div>
                @if (trim((string) data_get($event->content, 'text.transport')))<p><strong>En transporte público:</strong> {{ data_get($event->content, 'text.transport') }}</p>@endif
                @if ($event->venue_map_url && $event->show('map'))<p><a class="ev-btn ev-btn--ghost" href="{{ $event->venue_map_url }}" target="_blank" rel="noopener" data-ev-track="click_map">{!! $icon('map') !!} Abrir en Google Maps</a></p>@endif
            </div>
            <figure class="ev-photo ev-photo--tilt" data-reveal style="--d:.15s">
                <img src="{{ $img('teotihuacan-calzada') }}" alt="Zona arqueológica de Teotihuacán a pocos minutos del hotel" width="1400" height="949" loading="lazy">
                <figcaption>Teotihuacán, a pocos minutos · imagen ilustrativa</figcaption>
            </figure>
        </div>
        <ul class="ev-amen" data-reveal>
            <li>{!! $icon('tent') !!}<b>Zona de camping</b><span>Jardín amplio frente a la capilla con baños, vestidores y regaderas exclusivos.</span></li>
            <li>{!! $icon('moon') !!}<b>Habitaciones</b><span>Se reservan directamente con el hotel (hacienda, campestre y estándar, todas con baño privado y toallas).</span></li>
            <li>{!! $icon('mic') !!}<b>Auditorio</b><span>Para 300 personas: las conferencias son bajo techo y lejos del frío.</span></li>
            <li>{!! $icon('cash') !!}<b>Restaurante Don José</b><span>Desayuno buffet de $350 (8:00 a 14:00 h) y comida a la carta. El restobar abre por la noche.</span></li>
            <li>{!! $icon('car') !!}<b>Estacionamiento</b><span>$60 por día; se paga directamente en el hotel. Tu coche queda resguardado.</span></li>
            <li>{!! $icon('flame') !!}<b>Fogatas</b><span>Área controlada y separada de casas de campaña y telescopios.</span></li>
        </ul>
        <figure class="ev-photo ev-photo--wide" data-reveal><img src="{{ $img('campfire') }}" alt="Fogata encendida de noche" width="1000" height="666" loading="lazy"><figcaption>Fogata en área controlada, lejos de los telescopios · imagen ilustrativa</figcaption></figure>
    </div>
</section>

{{-- ACTO VI · Prepárate (el día: lo práctico) --}}
<section class="ev-sec ev-day" id="que-traer" aria-labelledby="h-traer">
    <div class="ev-wrap">
        <div class="ev-head-row" data-reveal>
            <div><p class="ev-eyebrow">Prepárate</p><h2 id="h-traer">Qué traer y <em>reglas de oro</em></h2></div>
            <button type="button" class="ev-btn ev-btn--ghost ev-btn--sm" id="evPrint">{!! $icon('printer') !!} Imprimir lista</button>
        </div>
        <div class="ev-twin" data-reveal>
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
            <div data-reveal>
                <h3>Tu mochila de cazador de estrellas <small class="ev-check-count" id="evCheckCount" aria-live="polite"></small></h3>
                <ul class="ev-list ev-list--box ev-checklist" id="evChecklist">
                    @foreach (['Ropa de invierno en capas, gorro, guantes, bufanda y cobija.', 'Linterna con luz roja (o el celofán rojo de tu kit sobre una lámpara).', 'Para camping: casa de campaña, sleeping bag y aislante o colchón inflable.', 'Silla plegable o tapete para recostarte a ver el cielo.', 'Termo para bebidas calientes.', 'Binoculares o tu propio telescopio, si tienes.', 'Batería portátil para tu celular.', 'Gorra y bloqueador para la observación solar del domingo.', 'Tu comprobante o clave de compra e identificación.'] as $n => $item)
                        <li><label><input type="checkbox" data-k="{{ $n }}"><span>{{ $item }}</span></label></li>
                    @endforeach
                </ul>
            </div>
            <div data-reveal style="--d:.12s">
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

{{-- Equipo --}}
<section class="ev-sec ev-day ev-day--alt" id="equipo" aria-labelledby="h-equipo">
    <div class="ev-wrap">
        <div data-reveal>
            <p class="ev-eyebrow">Tus guías</p>
            <h2 id="h-equipo">Quiénes te <em>acompañan</em></h2>
        </div>
        <div class="ev-team">
            <article class="ev-card ev-team__lead" data-reveal>
                @if ($event->asset('luz'))<img class="ev-team__photo" src="{{ $event->asset('luz') }}" alt="Luz Calderón, astrónoma y anfitriona" loading="lazy" width="220" height="220">
                @else<div class="ev-team__avatar" aria-hidden="true">{!! $icon('star') !!}</div>@endif
                <div><p class="ev-eyebrow">Anfitriona</p><h3>Luz Calderón · Astrónoma</h3>
                <p>Divulgadora de la ciencia con muchos años de trayectoria y una comunidad enorme que la sigue. Ha organizado noches de estrellas con cientos de asistentes y es la voz que te guiará toda la noche: da la bienvenida, imparte conferencias y te enseña a leer el cielo.</p></div>
            </article>
            <article class="ev-card" data-reveal style="--d:.12s">
                <div class="ev-duo">
                    @if ($event->asset('galileo'))<img src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" loading="lazy" width="265" height="350">@endif
                    @if ($event->asset('telescopio'))<img src="{{ $event->asset('telescopio') }}" alt="Telescopio, mascota de Astrofest, un telescopio con brazos y zapatos" loading="lazy" width="250" height="303">@endif
                </div>
                <h3>Galileo y Telescopio</h3>
                <p>Tus guías del cielo: Galileo nunca deja de hacer preguntas y Telescopio siempre señala algo nuevo. Los encontrarás en el pasaporte, el diploma y los stickers.</p>
            </article>
            <article class="ev-card" data-reveal style="--d:.24s">
                <div class="ico">{!! $icon('telescope') !!}</div>
                <h3>Equipo de telescopios</h3>
                <p>Jóvenes astrónomos al pie del cañón toda la noche, operando los telescopios y resolviendo tus dudas.</p>
            </article>
        </div>
    </div>
</section>

{{-- Preguntas frecuentes --}}
<section class="ev-sec ev-day" id="preguntas" aria-labelledby="h-faq">
    <div class="ev-wrap">
        <div data-reveal>
            <p class="ev-eyebrow">Resolvemos tus dudas</p>
            <h2 id="h-faq">Preguntas <em>frecuentes</em></h2>
        </div>
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
                <div class="ev-faq" data-reveal style="--d:{{ $ci * .12 }}s">
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

{{-- ACTO FINAL · Amanecer: la última llamada --}}
<section class="ev-sec ev-night ev-final" aria-labelledby="h-cierre">
    <div class="ev-bg" style="background-image:url('{{ $img('amanecer') }}')" data-par="0.06" role="presentation"></div>
    <div class="ev-narrow" data-reveal>
        @if ($event->asset('galileo'))<img class="ev-final__char" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" loading="lazy" width="150" height="198">@endif
        <p class="ev-eyebrow">Mira hacia arriba</p>
        <h2 id="h-cierre">{{ $event->text('closing.title') }}</h2>
        <p class="ev-big">{{ $event->text('closing.text') }}</p>
        @if ($event->hasPrice())<p class="ev-final__price">{{ $money($event->price) }} MXN <small>por persona · IVA incluido · camping incluido</small></p>@endif
        @if ($state === 'open' && $available !== null && $available <= 30)<p class="ev-pill">Quedan {{ $available }} {{ $available === 1 ? 'lugar' : 'lugares' }}</p>@endif
        <div class="ev-cta" style="justify-content:center">
            @if ($state === 'open')<a class="ev-btn ev-btn--glow" href="#entrada" data-ev-track="cta_closing">{{ $event->text('hero.cta') }}</a>@endif
            @if ($event->contact_whatsapp)<a class="ev-btn ev-btn--ghost" href="https://wa.me/{{ preg_replace('/\D+/', '', $event->contact_whatsapp) }}" target="_blank" rel="noopener" data-ev-track="click_whatsapp">{!! $icon('whatsapp') !!} Escríbenos por WhatsApp</a>@endif
        </div>
    </div>
</section>

@if ($state === 'open' && $event->hasPrice())
    <div class="ev-bar" id="evBar" hidden>
        <div><small>Por persona</small><strong>{{ $money($event->price) }} MXN</strong></div>
        <a class="ev-btn ev-btn--sm ev-btn--glow" href="#entrada" data-ev-track="cta_sticky">{{ $event->text('hero.cta') }}</a>
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Contador de noches (zona horaria del evento)
    var c = document.getElementById('evCount');
    if (c) {
        var tz = 'America/Mexico_City', start = new Date(c.getAttribute('data-start'));
        var ymd = function (d) { var p = new Intl.DateTimeFormat('en-CA', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit' }).format(d).split('-'); return Date.UTC(p[0], p[1] - 1, p[2]); };
        var tick = function () { var n = Math.round((ymd(start) - ymd(new Date())) / 864e5), t = document.getElementById('evCountText');
            t.innerHTML = n > 0 ? 'Faltan <strong>' + n + '</strong> ' + (n === 1 ? 'noche' : 'noches') + ' para mirar hacia arriba' : (n === 0 ? 'Hoy miramos hacia arriba' : ''); };
        tick(); setInterval(tick, 60000);
    }

    // Selector de cantidad: el total se anima al cambiar
    var f = document.getElementById('evBuy');
    if (f) {
        var p = JSON.parse(f.getAttribute('data-pricing')), q = document.getElementById('evQty'), out = document.getElementById('evTotal'), sv = document.getElementById('evSaving'), shown = null;
        var fmt = function (n) { return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: n % 1 ? 2 : 0, maximumFractionDigits: 2 }); };
        var paint = function (to) { if (reduce || shown === null) { out.textContent = fmt(to); shown = to; return; } var from = shown, t0 = performance.now(); shown = to;
            var step = function (t) { var k = Math.min(1, (t - t0) / 380), e = 1 - Math.pow(1 - k, 3); out.textContent = fmt(from + (to - from) * e); if (k < 1) requestAnimationFrame(step); else out.textContent = fmt(to); }; requestAnimationFrame(step); };
        var calc = function () {
            var max = parseInt(q.max, 10) || 1, n = Math.min(max, Math.max(1, parseInt(q.value, 10) || 1)); q.value = n;
            var sub = p.unit * n, pct = 0; p.rules.forEach(function (r) { if (n >= r.min) pct += r.percent; }); pct = Math.min(100, pct);
            var tot = Math.round(sub * (100 - pct)) / 100; paint(tot); sv.textContent = pct ? '(ahorras ' + fmt(Math.round(sub * pct) / 100) + ')' : '';
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
        var show = function (i, focus) { btns.forEach(function (b, j) { b.setAttribute('aria-selected', j === i ? 'true' : 'false'); b.tabIndex = j === i ? 0 : -1; panels[j].hidden = j !== i; if (j === i) { [].slice.call(panels[j].querySelectorAll('[data-reveal]')).forEach(function (el) { el.classList.add('is-in'); }); } }); if (focus) btns[i].focus(); };
        btns.forEach(function (b, i) { b.addEventListener('click', function () { show(i); }); b.addEventListener('keydown', function (e) { if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') { e.preventDefault(); show((i + (e.key === 'ArrowRight' ? 1 : btns.length - 1)) % btns.length, true); } }); });
        show(0);
    }

    // Carrusel de objetos
    var rail = document.getElementById('evRail');
    if (rail) { [].slice.call(document.querySelectorAll('[data-rail]')).forEach(function (b) { b.addEventListener('click', function () { rail.scrollBy({ left: parseInt(b.getAttribute('data-rail'), 10) * Math.min(rail.clientWidth * .8, 420), behavior: reduce ? 'auto' : 'smooth' }); }); }); }

    // Aparición suave de lo que entra a la vista + constelaciones que se dibujan + cifras que cuentan
    var root = document.documentElement;
    var count = function (el) { var to = parseInt(el.getAttribute('data-count'), 10); if (reduce || isNaN(to) || el.__done) return; el.__done = true; var t0 = null; var step = function (t) { if (t0 === null) t0 = t; var k = Math.min(1, (t - t0) / 1200); el.textContent = Math.round(to * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(step); }; el.textContent = '0'; requestAnimationFrame(step); };
    var items = [].slice.call(document.querySelectorAll('[data-reveal], .ev-const'));
    if ('IntersectionObserver' in window && !reduce) {
        var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (!e.isIntersecting) return; e.target.classList.add('is-in'); [].slice.call(e.target.querySelectorAll('[data-count]')).forEach(count); io.unobserve(e.target); }); }, { rootMargin: '0px 0px -10% 0px', threshold: .12 });
        items.forEach(function (el) { io.observe(el); });
    } else { items.forEach(function (el) { el.classList.add('is-in'); }); }

    // Línea del programa que se va dibujando con tu avance (meta-gradiente: «ya casi llegas»)
    var lines = [].slice.call(document.querySelectorAll('[data-line]'));
    // Parallax muy suave (solo transform; apagado con «reducir movimiento»)
    var par = [].slice.call(document.querySelectorAll('[data-par]')), ticking = false;
    var frame = function () {
        var vh = innerHeight;
        par.forEach(function (el) { var host = el.closest('section') || el, r = host.getBoundingClientRect(); if (r.bottom < -100 || r.top > vh + 100) return; var k = parseFloat(el.getAttribute('data-par')) || 0; el.style.setProperty('--py', ((r.top + r.height / 2 - vh / 2) * k * -1).toFixed(1) + 'px'); });
        lines.forEach(function (ol) { var r = ol.getBoundingClientRect(); ol.style.setProperty('--p', Math.max(0, Math.min(1, (vh * .62 - r.top) / Math.max(1, r.height))).toFixed(3)); });
        ticking = false;
    };
    if (!reduce) { addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(frame); } }, { passive: true }); addEventListener('resize', frame); }
    frame();

    // Barra fija de compra en celular: solo cuando ni la portada ni la entrada están a la vista
    var bar = document.getElementById('evBar');
    if (bar && 'IntersectionObserver' in window) {
        var seen = { hero: true, entrada: false }, upd = function () { bar.hidden = seen.hero || seen.entrada; };
        var io2 = new IntersectionObserver(function (es) { es.forEach(function (e) { seen[e.target.getAttribute('data-k')] = e.isIntersecting; }); upd(); });
        [['.ev-hero', 'hero'], ['#entrada', 'entrada']].forEach(function (a) { var el = document.querySelector(a[0]); if (el) { el.setAttribute('data-k', a[1]); io2.observe(el); } });
    }

    // «Pide un deseo»: una estrella fugaz cruza la portada
    var wish = document.getElementById('evWish'), hero = document.getElementById('evHero');
    if (wish && hero) {
        var launch = function (x, y) { if (reduce) return; var s = document.createElement('i'); s.className = 'ev-meteor'; s.style.left = x + 'px'; s.style.top = y + 'px'; hero.appendChild(s); setTimeout(function () { s.remove(); }, 1500); };
        wish.addEventListener('click', function () { var r = hero.getBoundingClientRect(); launch(r.width * (.5 + Math.random() * .35), r.height * (.12 + Math.random() * .25)); wish.innerHTML = '<span>✦</span> ¡Deseo pedido!'; setTimeout(function () { wish.innerHTML = '<span>✦</span> Pide un deseo'; }, 2400); evPush('wish'); });
    }

    // Lista de «qué traer» con casillas que se recuerdan en tu dispositivo
    var cl = document.getElementById('evChecklist'), cc = document.getElementById('evCheckCount');
    if (cl) {
        var key = 'ev-lista-' + @json($event->slug), saved = {}; try { saved = JSON.parse(localStorage.getItem(key) || '{}'); } catch (e) {}
        var boxes = [].slice.call(cl.querySelectorAll('input')), upc = function () { var n = boxes.filter(function (b) { return b.checked; }).length; cc.textContent = n ? n + ' de ' + boxes.length + ' listo' + (n === 1 ? '' : 's') : ''; };
        boxes.forEach(function (b) { b.checked = !!saved[b.getAttribute('data-k')]; b.addEventListener('change', function () { saved[b.getAttribute('data-k')] = b.checked; try { localStorage.setItem(key, JSON.stringify(saved)); } catch (e) {} upc(); }); }); upc();
    }

    // Imprimir solo la lista de «qué traer»
    var pr = document.getElementById('evPrint');
    if (pr) { pr.addEventListener('click', function () { document.body.classList.add('ev-printing'); window.print(); setTimeout(function () { document.body.classList.remove('ev-printing'); }, 400); }); }
})();
</script>
@endpush
