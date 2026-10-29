@extends('events.layout', ['onLanding' => true])

@php
    $money = fn ($v) => '$' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $start = $event->local($event->start_at);
    $end = $event->local($event->end_at);
    $cd = $event->local($event->countdown_from);
    $minorsAge = (int) (data_get($event->content, 'minors_age') ?: 6);
    $hotelUrl = $event->hotel_booking_url;
    $earlyActive = collect($quote['discounts'])->first(fn ($d) => str_contains(mb_strtolower($d['label']), 'preventa'));
    $pricing = ['unit' => (float) $event->price, 'rules' => collect((array) $event->discounts)->filter(fn ($r) => !empty($r['enabled']) && (float) ($r['percent'] ?? 0) > 0)->map(fn ($r, $k) => ['key' => $k, 'percent' => (float) $r['percent'], 'min' => $k === 'group' ? (int) ($r['min_qty'] ?? 4) : 1])->values()->all(), 'iva' => (float) $event->iva_rate];
    $dateLong = $start ? $start->locale('es')->translatedFormat('l j \d\e F \d\e Y') : '';
    $weekend = $start && $end ? 'Sáb ' . $start->format('j') . ' – dom ' . $end->format('j') . ' ' . $start->locale('es')->translatedFormat('M Y') : '';
@endphp

@push('head')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'Event',
    'name' => $event->name,
    'description' => $event->text('seo.description'),
    'startDate' => $start?->toIso8601String(), 'endDate' => $end?->toIso8601String(),
    'eventStatus' => 'https://schema.org/EventScheduled',
    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
    'location' => array_filter(['@type' => 'Place', 'name' => $event->venue_name, 'address' => array_filter(['@type' => 'PostalAddress', 'streetAddress' => $event->venue_address, 'addressLocality' => $event->venue_city, 'addressCountry' => 'MX'])]),
    'image' => $event->asset('og') ? [$event->asset('og')] : null,
    'offers' => $event->hasPrice() ? ['@type' => 'Offer', 'priceCurrency' => 'MXN', 'price' => (string) (float) $event->price, 'url' => $event->url(), 'availability' => $state === 'sold_out' ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock'] : null,
    'performer' => ['@type' => 'Person', 'name' => 'Luz Calderón'],
    'organizer' => ['@type' => 'Organization', 'name' => 'Horizontia', 'url' => url('/')],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')

{{-- 1 · Portada --}}
<section class="ev-hero" aria-labelledby="ev-h1">
    @if ($event->asset('hero_desktop'))<div class="ev-hero__img" style="background-image:url('{{ $event->asset('hero_desktop') }}')" role="presentation"></div>@endif
    <div class="ev-stars" aria-hidden="true"></div>
    <span class="ev-shoot" aria-hidden="true"></span><span class="ev-shoot" aria-hidden="true"></span><span class="ev-shoot" aria-hidden="true"></span>
    <div class="ev-wrap">
        <p class="ev-supra">{{ $event->text('hero.supra') }}</p>
        <h1 class="ev-h1" id="ev-h1">{{ $event->text('seo.h1') }}</h1>
        <h2 class="ev-hero__title">{{ $event->text('hero.title') }}</h2>
        <p class="ev-lead">{{ $event->text('hero.subtitle') }}</p>
        @if ($start)
            <p class="ev-dateline"><span class="ev-pill">{{ ucfirst($dateLong) }}</span> <span>Desde las {{ $start->format('H:i') }} h · {{ $event->venue_name }}{{ $event->venue_city ? ', ' . $event->venue_city : '' }}</span></p>
        @endif
        @if ($earlyActive)<p><span class="ev-pill ev-pill--tag">{{ $earlyActive['label'] }} −{{ rtrim(rtrim(number_format($earlyActive['percent'], 2), '0'), '.') }} %</span></p>@endif
        <div class="ev-cta">
            @if ($state === 'open')<a class="ev-btn" href="#entrada" data-ev-track="cta_hero">{{ $event->text('hero.cta') }}</a>@endif
            <a class="ev-btn ev-btn--ghost" href="#que-vamos-a-ver">{{ $event->text('hero.secondary') }}</a>
        </div>
        @if ($cd && $start && !$event->isFinished() && now()->gte($cd))
            <p class="ev-count" id="evCount" data-start="{{ $start->toIso8601String() }}" aria-live="off"><span id="evCountText">&nbsp;</span></p>
        @endif
    </div>
    @if ($event->asset('galileo'))<img class="ev-hero__char" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" width="330" height="330">@endif
</section>

{{-- 2 · Franja de datos --}}
<section class="ev-facts" aria-label="Datos clave">
    <div class="ev-wrap"><ul>
        @if ($weekend)<li><span>Fecha</span><b>{{ $weekend }}</b></li>@endif
        @if ($start)<li><span>Check-in</span><b>{{ $start->format('H:i') }} h</b></li>@endif
        @if ($event->venue_name)<li><span>Lugar</span><b>{{ $event->venue_name }}</b></li>@endif
        <li><span>Distancia</span><b>~1 h de la CDMX</b></li>
        @if ($event->hasPrice())<li class="price"><span>Por persona, IVA incluido · incluye camping</span><b>{{ $money($event->price) }} MXN</b></li>@endif
    </ul></div>
</section>

{{-- 3 · ¿Qué es Astrofest®? --}}
<section class="ev-sec" id="que-es" aria-labelledby="h-que-es">
    <div class="ev-wrap">
        <h2 id="h-que-es">¿Qué es Astrofest®?</h2>
        <p>Imagina esto: cae la tarde sobre Teotihuacán, el cielo se pinta de naranja detrás de los cerros y, una a una, se encienden las primeras estrellas. Apagamos las luces, prendemos linternas rojas y, durante toda la noche, miramos hacia arriba juntos.</p>
        <p>Astrofest® no es una excursión ni un campamento cualquiera: es una noche diseñada momento a momento para que te enamores del cielo. Habrá conferencias con astrónomos, telescopios para asomarte a la Luna y a Saturno, una fogata para calentar las manos (y los bombones), música y, sobre todo, la cacería de estrellas fugaces de la lluvia de meteoros Gemínidas.</p>
        <p>No necesitas saber nada de astronomía. Si nunca has visto por un telescopio, esta es tu noche. Y si tienes uno guardado en el clóset esperando su gran momento, tráelo: te ayudamos a usarlo y, de paso, serás el héroe de la noche cuando todos quieran asomarse por él.</p>
        <div class="ev-grid ev-grid--2" style="margin-top:26px">
            <article class="ev-card"><div class="ico" aria-hidden="true">✦</div><h3>Ciencia de verdad</h3><p>Astrónomos y divulgadores te explican el cielo con palabras sencillas. Sin humo, sin promesas exageradas.</p></article>
            <article class="ev-card"><div class="ico" aria-hidden="true">▲</div><h3>Teotihuacán y el cielo</h3><p>Descubre cómo una de las ciudades más grandes de la antigüedad se trazó mirando al Sol y a las estrellas.</p></article>
            <article class="ev-card"><div class="ico" aria-hidden="true">●</div><h3>La noche de la luz roja</h3><p>Apagamos reflectores y pantallas para que tus ojos se acostumbren a la oscuridad y veas muchas más estrellas.</p></article>
            <article class="ev-card"><div class="ico" aria-hidden="true">☺</div><h3>Para toda la familia</h3><p>Rally con pistas y premios, diploma para todos y actividades para chicos y grandes. Menores de {{ $minorsAge }} años no pagan.</p></article>
        </div>
        <div class="ev-paper" style="margin-top:22px"><h3>Una marca para coleccionar</h3><p>Astrofest® Gemínidas 2026 es la primera edición de una nueva temporada de noches de estrellas. Cada lluvia de meteoros tendrá su propia pulsera, parche, playera y mapa del cielo. Guarda tu pulsera: será tu pase de coleccionista para las próximas ediciones.</p></div>
    </div>
</section>

{{-- 4 · ¿Qué vamos a ver? --}}
<section class="ev-sec ev-sec--mid" id="que-vamos-a-ver" aria-labelledby="h-ver">
    <div class="ev-wrap">
        <h2 id="h-ver">¿Qué vamos a ver en el cielo?</h2>
        <h3>Las Gemínidas: polvo de un asteroide que se enciende</h3>
        <p>Cada diciembre, la Tierra cruza una nube de polvo que dejó a su paso un asteroide llamado 3200 Faetón. Esos granitos, muchos del tamaño de un grano de arena, chocan contra la atmósfera a unos 35 kilómetros por segundo y se calientan tanto que brillan: eso es una estrella fugaz. Se llaman Gemínidas porque, si sigues sus trazos hacia atrás, todas parecen salir de un mismo punto en la constelación de Géminis, muy cerca de sus dos estrellas más brillantes, Cástor y Pólux. A ese punto se le llama radiante. Son una de las lluvias más bonitas del año porque sus meteoros suelen ser brillantes y pasar con calma por el cielo.</p>
        <h3 style="margin-top:26px">¿Por qué el 12 de diciembre de 2026 es una gran noche?</h3>
        <div class="ev-grid ev-grid--3">
            <article class="ev-card"><h3>Es la víspera del máximo</h3><p>El pico de las Gemínidas se espera la noche del 13 al 14 de diciembre, pero la noche del 12 al 13 ya tiene mucha actividad… y cae en sábado.</p></article>
            <article class="ev-card"><h3>La Luna no estorba</h3><p>Hay Luna nueva el 9 de diciembre. El día 12 apenas veremos un creciente delgadito que se oculta temprano y nos deja el cielo oscuro el resto de la noche.</p></article>
            <article class="ev-card"><h3>El mejor momento es de madrugada</h3><p>El radiante sube y alcanza su mayor altura hacia las 2:00 h, así que la gran observación será de medianoche a las 2:00.</p></article>
        </div>
        <p style="margin-top:18px"><strong>Un compromiso honesto:</strong> no te prometemos un número exacto de estrellas fugaces por hora; el cielo tiene sus propios planes. Teotihuacán no es un cielo de alta montaña porque la CDMX está cerca, pero las Gemínidas son brillantes y se ven muy bien desde aquí. Lo que sí te prometemos es una de las mejores ventanas del año para verlas cerca de la ciudad.</p>

        <h3 style="margin-top:30px">Lo que verás por los telescopios</h3>
        <div class="ev-grid ev-grid--3">
            @foreach ([
                ['☾', 'La Luna creciente', 'Al atardecer, sus cráteres y montañas se ven con relieve justo en la línea donde empieza la sombra. Parece que la estás tocando.'],
                ['♄', 'Saturno', 'El planeta de los anillos. Verlo por primera vez en un telescopio es de esas cosas que no se olvidan.'],
                ['✺', 'La Nebulosa de Orión', 'Una nube gigante de gas donde están naciendo estrellas, a unos 1,300 años luz. Una auténtica cuna estelar.'],
                ['✧', 'Cúmulos de estrellas', 'Familias de estrellas que nacieron juntas, como las Pléyades: un puñado de diamantes azules en el cielo de invierno.'],
                ['⁂', 'Estrellas dobles', 'Puntos de luz que, con el telescopio, se separan en dos soles, a veces de colores distintos.'],
                ['✦', 'Constelaciones de invierno', 'Con un puntero de luz verde (manejado solo por el astrónomo) aprenderás a encontrar Orión, Géminis, Cástor, Pólux y Proción a simple vista.'],
                ['☉', 'El Sol (domingo por la mañana)', 'Con filtros especiales y seguros, podremos ver manchas solares. ¡Nunca mires al Sol sin un filtro adecuado!'],
            ] as [$i, $t, $d])
                <article class="ev-card"><div class="ico" aria-hidden="true">{{ $i }}</div><h3>{{ $t }}</h3><p>{{ $d }}</p></article>
            @endforeach
        </div>
        <div class="ev-paper" style="margin-top:26px;display:grid;gap:6px">
            <h3>Teotihuacán y el cielo</h3>
            <p>La Ciudad de los Dioses no se construyó al azar. Sus calzadas y pirámides guardan alineaciones con el Sol y las estrellas que los arqueoastrónomos siguen estudiando hoy. En Astrofest® escucharás cómo observaban el cielo los pueblos de Mesoamérica y por qué Teotihuacán es uno de los mejores lugares del país para hablar de astronomía antigua… mientras ves caer estrellas fugaces en el mismo cielo que ellos miraban.</p>
        </div>
    </div>
</section>

{{-- 5 · Programa --}}
<section class="ev-sec" id="programa" aria-labelledby="h-programa">
    <div class="ev-wrap">
        <h2 id="h-programa">Programa de la noche</h2>
        <p class="ev-muted">Programa sujeto a cambios por clima. Todo ocurre dentro del hotel.</p>
        <div class="ev-days">
            <div>
                <h3>Sábado {{ $start?->format('j') ?? '12' }} de diciembre</h3>
                <ol class="ev-time">
                    @foreach ([
                        ['15:00', 'Check-in y registro', 'Recibe tu pulsera y tu kit. Instala tu casa de campaña o entra a tu habitación. Puedes llegar antes a comer al restaurante del hotel.'],
                        ['17:00', 'Bienvenida: ¿qué veremos esta noche?', 'Luz Calderón nos explica qué son las Gemínidas, las reglas de la noche y arranca el rally.'],
                        ['17:45', 'Atardecer', 'Momento fotográfico mientras el Sol se despide.'],
                        ['18:15', 'Conferencia: Teotihuacán y el cielo', 'En el auditorio. Arqueoastronomía y la observación del cielo en Mesoamérica.'],
                        ['19:00', 'Taller de cielo', 'Constelaciones, cómo orientarse y cómo usar un telescopio. Primera mirada a la Luna creciente y a Saturno.'],
                        ['20:00', 'Cena', 'Tiempo para cenar en el restaurante o en la zona de snacks (no incluida).'],
                        ['20:30', 'Música temática', 'Ambiente musical inspirado en el espacio.'],
                        ['21:00', 'Fogata', 'Bombones y plática alrededor del fuego, lejos de la zona de telescopios.'],
                        ['22:00', 'Primera observación + segunda conferencia', 'Planetas, cúmulos, la Nebulosa de Orión y estrellas dobles. Si hace frío, ¡refúgiate en la charla!'],
                        ['23:00', 'Caza de Gemínidas', 'Luces apagadas, sin música ni celulares. Cuenta tus estrellas fugaces en tu Pasaporte Astrofest®.'],
                        ['00:30–02:00', 'La gran observación', 'El radiante de Géminis en lo más alto: el mejor momento de la noche.'],
                        ['02:00', 'Observación libre', 'Para quienes sigan despiertos. Los demás… ¡a soñar con estrellas!'],
                    ] as [$h, $t, $d])
                        <li><time>{{ $h }}</time><b>{{ $t }}</b><span>{{ $d }}</span></li>
                    @endforeach
                </ol>
            </div>
            <div>
                <h3>Domingo {{ $end?->format('j') ?? '13' }} de diciembre</h3>
                <ol class="ev-time">
                    @foreach ([
                        ['07:00', 'Café al amanecer', 'Ver salir el Sol después de una noche de estrellas.'],
                        ['08:00', 'Desayuno', 'Buffet en el restaurante del hotel (no incluido).'],
                        ['10:00', 'Observación del Sol', 'Manchas solares con filtros especiales.'],
                        ['12:00', 'Clausura', 'Premios del rally, entrega de diplomas y foto de grupo. Check-out.'],
                        ['Después', 'Visita a la zona arqueológica', 'Por tu cuenta, con los horarios y reglas de la zona.'],
                    ] as [$h, $t, $d])
                        <li><time>{{ $h }}</time><b>{{ $t }}</b><span>{{ $d }}</span></li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</section>

{{-- 6 · Entrada y alojamiento --}}
<section class="ev-sec ev-sec--mid" id="entrada" aria-labelledby="h-entrada">
    <div class="ev-wrap">
        <h2 id="h-entrada">Tu entrada a Astrofest®</h2>
        @if (session('event_error'))<p class="ev-alert" role="alert">{{ session('event_error') }}</p>@endif
        <div class="ev-grid ev-grid--2" style="align-items:start">
            <div class="ev-ticket">
                @if ($event->hasPrice())
                    <div class="ev-ticket__price">{{ $money($event->price) }} MXN<small>por persona · IVA incluido · incluye camping para 1 persona</small></div>
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
                        <button type="submit" class="ev-btn" data-ev-track="add_to_cart_click">Agregar al carrito</button>
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
            </div>
            <div>
                <h3>La entrada incluye</h3>
                <ul class="ev-list">
                    <li>Conferencias magistrales en el auditorio y taller de cielo.</li>
                    <li>Observación con telescopios guiada por astrónomos, caza de Gemínidas y gran observación de madrugada.</li>
                    <li>Fogata, música temática, DJ en vivo, shows en Planetario Móvil y rally con pistas y premios.</li>
                    <li>Observación del Sol el domingo por la mañana.</li>
                    <li>Pulsera coleccionable, Pasaporte Astrofest®, mapa del cielo de diciembre, stickers y celofán rojo para tu linterna.</li>
                    <li>Diploma de participación.</li>
                    <li>Camping para 1 persona, con baños, vestidores y regaderas de la zona.</li>
                </ul>
                <h3 style="margin-top:20px">No incluye</h3>
                <ul class="ev-list ev-list--no">
                    <li>Habitación (se reserva con el hotel), transporte ni alimentos (cena, snacks, desayuno buffet).</li>
                    <li>Estacionamiento: $60 por día, se paga directamente en el hotel.</li>
                </ul>
                <p class="ev-hint" style="margin-top:12px">Menores de {{ $minorsAge }} años no pagan. Cada persona de {{ $minorsAge }} años o más necesita su entrada.</p>
            </div>
        </div>
        @if ($event->show('rooms') && $hotelUrl)
            <div class="ev-card" style="margin-top:24px;display:grid;gap:12px">
                <h3>¿Prefieres dormir en habitación?</h3>
                <p>{{ $event->text('rooms.text') }}</p>
                <div class="ev-cta" style="margin:0">
                    <a class="ev-btn ev-btn--ghost" href="{{ $hotelUrl }}" target="_blank" rel="noopener" data-ev-track="click_reserve_room">Reservar habitación en el hotel</a>
                    @if ($event->hotel_whatsapp)<a class="ev-btn ev-btn--ghost ev-btn--sm" href="https://wa.me/{{ preg_replace('/\D+/', '', $event->hotel_whatsapp) }}" target="_blank" rel="noopener" data-ev-track="click_reserve_room">WhatsApp del hotel</a>@endif
                    @if ($event->hotel_phone)<a class="ev-btn ev-btn--ghost ev-btn--sm" href="tel:{{ preg_replace('/[^\d+]/', '', $event->hotel_phone) }}" data-ev-track="click_reserve_room">Llamar al hotel</a>@endif
                </div>
            </div>
        @endif
    </div>
</section>

{{-- 7 · El lugar --}}
<section class="ev-sec" id="lugar" aria-labelledby="h-lugar">
    <div class="ev-wrap">
        <h2 id="h-lugar">El lugar y cómo llegar</h2>
        <p><strong>{{ $event->venue_name }}</strong>{{ $event->venue_city ? ' · ' . $event->venue_city : '' }} · a una hora de la CDMX.</p>
        <p>Un hotel de campo con lago, jardines amplios, capilla, auditorio y restaurante, a pocos minutos de las pirámides. Tiene todo para pasar una noche de estrellas con comodidad: baños y regaderas para la zona de camping, habitaciones con baño privado y espacios abiertos para instalar telescopios.</p>
        @if ($event->venue_address)<p class="ev-muted">{{ $event->venue_address }}</p>@endif
        <div class="ev-grid ev-grid--2" style="margin-top:20px">
            <article class="ev-card"><h3>Zona de camping</h3><p>Jardín amplio frente a la capilla con baños, vestidores y regaderas exclusivos.</p></article>
            <article class="ev-card"><h3>Habitaciones</h3><p>Se reservan directamente con el hotel (tres categorías: hacienda, campestre y estándar, todas con baño privado y toallas).</p></article>
            <article class="ev-card"><h3>Auditorio</h3><p>Para 300 personas; aquí serán las conferencias, bajo techo y lejos del frío.</p></article>
            <article class="ev-card"><h3>Restaurante Don José</h3><p>Desayuno buffet de $350 (8:00 a 14:00 h). Comida a la carta durante el día. El restobar abre por la noche, después de que cierra el restaurante.</p></article>
            <article class="ev-card"><h3>Estacionamiento</h3><p>$60 por día; se paga directamente en el hotel. Tu coche queda resguardado.</p></article>
            <article class="ev-card"><h3>Fogatas</h3><p>Área controlada y separada de casas de campaña y telescopios.</p></article>
        </div>
        <h3 style="margin-top:26px">Cómo llegar</h3>
        <p><strong>En auto:</strong> aproximadamente una hora desde la CDMX. Hay estacionamiento en el hotel ($60 por día). <strong>Transporte:</strong> el paquete no incluye transporte.</p>
        @if (trim((string) data_get($event->content, 'text.transport')))<p><strong>En transporte público:</strong> {{ data_get($event->content, 'text.transport') }}</p>@endif
        @if ($event->venue_map_url && $event->show('map'))<p><a class="ev-btn ev-btn--ghost" href="{{ $event->venue_map_url }}" target="_blank" rel="noopener" data-ev-track="click_map">Abrir en Google Maps</a></p>@endif
    </div>
</section>

{{-- 8 · Qué traer y reglas --}}
<section class="ev-sec ev-sec--mid" id="que-traer" aria-labelledby="h-traer">
    <div class="ev-wrap">
        <h2 id="h-traer">Qué traer y reglas de oro</h2>
        <div class="ev-grid ev-grid--2">
            <div>
                <h3>Tu mochila de cazador de estrellas</h3>
                <ul class="ev-list">
                    <li>Ropa de invierno en capas: en diciembre la temperatura puede bajar a 6–7 °C o menos. Gorro, guantes, bufanda y cobija.</li>
                    <li>Linterna con luz roja (o pon el celofán rojo de tu kit sobre una lámpara normal).</li>
                    <li>Para camping: casa de campaña, sleeping bag y aislante o colchón inflable.</li>
                    <li>Silla plegable o tapete para recostarte a ver el cielo (¡la mejor forma de ver meteoros!).</li>
                    <li>Termo para bebidas calientes.</li>
                    <li>Binoculares o tu propio telescopio si tienes. ¡Te ayudamos a usarlo!</li>
                    <li>Batería portátil para tu celular.</li>
                    <li>Gorra y bloqueador para la observación solar del domingo.</li>
                    <li>Tu comprobante o clave de compra e identificación.</li>
                </ul>
            </div>
            <div>
                <h3>Las reglas de oro de la noche</h3>
                <ul class="ev-list">
                    <li><strong>Solo luz roja.</strong> Tus ojos tardan unos 20–30 minutos en acostumbrarse a la oscuridad, y una sola luz blanca lo arruina.</li>
                    <li><strong>Pantallas al mínimo.</strong> Baja el brillo del celular o usa modo nocturno. En la caza de Gemínidas, celulares guardados.</li>
                    <li><strong>Cero láseres para el público.</strong> El aeropuerto AIFA está cerca y apuntar a un avión es un delito federal. Solo el astrónomo usa el puntero.</li>
                    <li><strong>La fogata, en su lugar.</strong> Solo en el área designada, lejos de casas de campaña y telescopios.</li>
                    <li><strong>Los telescopios se miran, no se mueven.</strong> Pide ayuda al operador para ajustar el enfoque.</li>
                </ul>
                <p class="ev-hint" style="margin-top:14px"><strong>Aviso de «noche de invierno».</strong> Las noches de diciembre en Teotihuacán son frías y secas. Vístete como si fueras a la montaña: es mejor que te sobre un suéter a que te falte. Las conferencias son bajo techo y habrá bebidas calientes a la venta.</p>
            </div>
        </div>
    </div>
</section>

{{-- 9 · Kit, rally y diploma --}}
<section class="ev-sec" id="kit" aria-labelledby="h-kit">
    <div class="ev-wrap">
        <h2 id="h-kit">Kit de bienvenida, rally y diploma</h2>
        <div class="ev-grid ev-grid--3">
            <article class="ev-card"><h3>Tu kit de bienvenida</h3><ul class="ev-list"><li>Pulsera de acceso coleccionable: cada edición tendrá la suya.</li><li>Pasaporte Astrofest®: anota tus estrellas fugaces y consigue sellos durante la noche.</li><li>Mapa del cielo de diciembre.</li><li>Stickers de Galileo y Telescopio.</li><li>Celofán rojo para convertir cualquier lámpara en linterna de astrónomo.</li></ul></article>
            <article class="ev-card"><h3>El rally de las estrellas</h3><p>Desde la bienvenida te contamos cómo participar. Durante las conferencias y la observación iremos soltando pistas sobre el cielo, y el domingo, en la clausura, ¡premiamos a los ganadores!</p></article>
            <article class="ev-card"><h3>Tu diploma de astrónomo</h3><p>Todos los participantes se llevan un diploma de Astrofest® con su nombre, firmado por los organizadores. Hay quienes lo guardan por años como el recuerdo de la noche en que se enamoraron del cielo.</p></article>
        </div>
    </div>
</section>

{{-- 10 · Quiénes te acompañan --}}
<section class="ev-sec ev-sec--mid" id="equipo" aria-labelledby="h-equipo">
    <div class="ev-wrap">
        <h2 id="h-equipo">Quiénes te acompañan</h2>
        <div class="ev-grid ev-grid--3">
            <article class="ev-card">
                @if ($event->asset('luz'))<img class="ev-char" src="{{ $event->asset('luz') }}" alt="Luz Calderón, astrónoma y anfitriona" loading="lazy" width="190" height="190" style="border-radius:50%">@endif
                <h3>Luz Calderón · Astrónoma y anfitriona</h3>
                <p>Divulgadora de la ciencia con muchos años de trayectoria y una comunidad enorme que la sigue. Ha organizado noches de estrellas con cientos de asistentes y es la voz que te guiará toda la noche: da la bienvenida, imparte conferencias y te enseña a leer el cielo.</p>
            </article>
            <article class="ev-card">
                @if ($event->asset('galileo'))<img class="ev-char ev-char--card" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" loading="lazy" width="190" height="190">@endif
                <h3>Galileo y Telescopio · Tus guías del cielo</h3>
                <p>Galileo es un sabio curioso que nunca deja de hacer preguntas, y Telescopio, su compañero inquieto, siempre está señalando algo nuevo en el cielo. Los encontrarás en el pasaporte, en el diploma y en los stickers de tu kit.</p>
            </article>
            <article class="ev-card">
                <h3>Equipo de telescopios</h3>
                <p>Jóvenes astrónomos que estarán al pie del cañón toda la noche operando los telescopios y resolviendo tus dudas.</p>
            </article>
        </div>
    </div>
</section>

{{-- 11 · Preguntas frecuentes y cierre --}}
<section class="ev-sec" id="preguntas" aria-labelledby="h-faq">
    <div class="ev-narrow ev-faq">
        <h2 id="h-faq">Preguntas frecuentes</h2>
        @foreach ([
            ['¿Necesito saber de astronomía?', 'Para nada. Astrofest® está pensado para que cualquiera disfrute: te explicamos todo con palabras sencillas.'],
            ['¿Cuántas estrellas fugaces voy a ver?', 'Nadie puede prometer un número exacto. El 12 de diciembre es la víspera del máximo de las Gemínidas, con Luna casi nueva: una de las mejores noches del año para verlas cerca de la CDMX.'],
            ['¿Y si se nubla?', 'La fiesta sigue con nuestro Plan Nube (charlas, planetario o proyección y fogata) y te llevas un crédito para la próxima edición. Consulta la política completa en Políticas del evento.'],
            ['¿Hace mucho frío?', 'Sí: la temperatura puede bajar a 6–7 °C o menos. Trae ropa de invierno en capas, gorro, guantes y cobija.'],
            ['¿Los niños pagan?', "Los menores de {$minorsAge} años no pagan. A partir de los {$minorsAge} años pagan como cualquier asistente."],
            ['¿La comida está incluida?', 'No. Puedes comer en el restaurante del hotel (desayuno buffet de $350), comprar en el restobar o llevar tus propios alimentos.'],
            ['¿Puedo llevar mi telescopio o binoculares?', '¡Claro! Es más, te lo recomendamos. Nuestro equipo te ayuda a usarlo.'],
            ['¿Cómo pago y cómo entro?', 'Pagas en línea en esta página con Mercado Pago. Recibirás una confirmación con tu clave de acceso; al llegar, muéstrala en el registro y recibe tu pulsera y tu kit.'],
            ['¿A qué hora puedo llegar?', 'El check-in es desde las ' . ($start?->format('H:i') ?? '15:00') . ' h. Puedes llegar antes a comer al restaurante.'],
            ['¿Hay estacionamiento?', 'Sí, cuesta $60 por día y se paga directamente en el hotel.'],
            ['¿Incluye transporte?', 'No. Puedes llegar en auto (≈1 h desde la CDMX).'],
            ['¿Puedo visitar las pirámides?', 'Sí, por tu cuenta, el domingo después del evento, con los horarios y reglas de la zona arqueológica.'],
        ] as [$q, $a])
            <details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
        @endforeach
        @if ($event->show('rooms') && $hotelUrl)
            <details><summary>¿Puedo compartir habitación con mis amigos o familia?</summary><p>Las habitaciones se reservan directamente con el hotel desde el botón «Reservar habitación en el hotel». Aquí compras tu entrada de Astrofest® y el hotel te descuenta la parte del camping. Cada persona necesita su entrada.</p></details>
        @endif
    </div>
</section>

<section class="ev-sec ev-close" aria-labelledby="h-cierre">
    <div class="ev-narrow">
        @if ($event->asset('galileo'))<img class="ev-char ev-char--card" src="{{ $event->asset('galileo') }}" alt="Galileo, mascota de Astrofest, un sabio de barba gris con catalejo" loading="lazy" width="160" height="160" style="width:140px">@endif
        <h2 id="h-cierre">{{ $event->text('closing.title') }}</h2>
        <p class="ev-muted">{{ $event->text('closing.text') }}</p>
        @if ($state === 'open')<a class="ev-btn" href="#entrada" data-ev-track="cta_closing">{{ $event->text('hero.cta') }}</a>@endif
    </div>
</section>
@endsection

@push('scripts')
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
})();
@endpush
