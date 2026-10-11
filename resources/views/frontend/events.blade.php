@extends('layouts.frontend-app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/events.css') }}?v={{ @filemtime(public_path('css/events.css')) }}">
    @if (!empty($eventsSchema['@graph']))
        <script type="application/ld+json">{!! json_encode($eventsSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endif
@endpush

@section('content')
@php
    $filtered = $filters['ciudad'] !== '' || $filters['mes'] !== '' || $filters['q'] !== '';
    $wa = whatsappUrl();
@endphp
<div class="ev">
    {{-- Cabecera: qué es esta sección y cómo se compra, en tres pasos --}}
    <header class="ev-hero">
        <div class="container">
            <p class="ev-hero__kicker">Eventos presenciales</p>
            <h1>Aprende en persona, con fechas y sede</h1>
            <p class="ev-hero__lead">Talleres y cursos que se imparten en un lugar concreto. Elige tu fecha, paga en línea y recibe todos los datos por correo.</p>
            <ol class="ev-steps">
                <li><span>1</span><div><strong>Elige un evento</strong><small>Mira dónde y cuándo es</small></div></li>
                <li><span>2</span><div><strong>Escoge tu fecha</strong><small>Si hay varias, tú decides</small></div></li>
                <li><span>3</span><div><strong>Paga en línea</strong><small>Y recibe tu confirmación</small></div></li>
            </ol>
        </div>
    </header>

    <div class="container ev-body">
        @if ($total > 0 || $filtered)
            <form class="ev-filters" method="get" action="{{ route('events') }}" role="search" aria-label="Filtrar eventos">
                <label class="ev-field ev-field--search">
                    <span>Buscar</span>
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Nombre del evento" maxlength="80">
                </label>
                <label class="ev-field">
                    <span>Ciudad</span>
                    <select name="ciudad" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach ($cities as $c)
                            <option value="{{ $c }}" @selected($filters['ciudad'] === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="ev-field">
                    <span>Mes</span>
                    <select name="mes" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach ($months as $value => $label)
                            <option value="{{ $value }}" @selected($filters['mes'] === $value)>{{ ucfirst($label) }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="ev-btn">Buscar</button>
                @if ($filtered)<a class="ev-clear" href="{{ route('events') }}">Quitar filtros</a>@endif
            </form>
        @endif

        @forelse ($byMonth as $group)
            <section class="ev-month" aria-labelledby="mes-{{ $loop->index }}">
                <h2 id="mes-{{ $loop->index }}">{{ ucfirst($group->label) }} <small>{{ $group->events->count() }} {{ $group->events->count() === 1 ? 'evento' : 'eventos' }}</small></h2>
                <div class="ev-grid">
                    @foreach ($group->events as $e)
                        @include('frontend.partials.event-card', ['e' => $e])
                    @endforeach
                </div>
            </section>
        @empty
            <div class="ev-empty">
                <span class="ev-empty__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="15" rx="3"/><path d="M8 3.5v3M16 3.5v3M3.5 10h17"/></svg></span>
                @if ($filtered)
                    <h2>No encontramos eventos con esos filtros</h2>
                    <p>Prueba con otra ciudad o mes, o quita los filtros para ver todo lo programado.</p>
                    <a class="ev-btn" href="{{ route('events') }}">Ver todos los eventos</a>
                @else
                    <h2>Todavía no hay eventos programados</h2>
                    <p>Estamos preparando las próximas fechas. Mientras tanto, puedes empezar con nuestros cursos en línea.</p>
                    <div class="ev-empty__actions">
                        <a class="ev-btn" href="{{ route('courses.search-courses') }}">Ver cursos en línea</a>
                        @if ($wa)<a class="ev-btn ev-btn--ghost" href="{{ $wa }}" target="_blank" rel="noopener noreferrer">Avísame por WhatsApp</a>@endif
                    </div>
                @endif
            </div>
        @endforelse

        @if ($total > 0)
            <p class="ev-note">Las horas son de la sede (hora de {{ trim(str_replace('_', ' ', \Illuminate\Support\Str::afterLast($tz, '/'))) }}). El acceso queda confirmado en cuanto se aprueba el pago.</p>
        @endif
    </div>
</div>
@endsection
