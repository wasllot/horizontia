@extends('layouts.frontend-app', ['pageTitle' => $path->title, 'pageDescription' => $path->subtitle ?: 'Ruta de aprendizaje de ' . $path->courses->count() . ' cursos.'])

@section('content')
<link rel="stylesheet" href="{{ asset('css/paths.css') }}?v={{ @filemtime(public_path('css/paths.css')) }}">
<section class="lp-page">
    <div class="lp-wrap">
        <header class="lp-head">
            <p class="lp-kicker">Ruta de aprendizaje</p>
            <h1>{{ $path->title }}</h1>
            @if ($path->subtitle)<p class="lp-sub">{{ $path->subtitle }}</p>@endif
            @if ($path->description)<p class="lp-desc">{{ $path->description }}</p>@endif

            @if ($student)
                <div class="lp-progress" data-testid="path-progress">
                    <div class="lp-progress__bar"><span style="width: {{ $status['percent'] }}%"></span></div>
                    <small>{{ $status['done'] }} de {{ $status['total'] }} cursos terminados · {{ $status['percent'] }}%</small>
                </div>
                @if ($certificate)
                    <a class="lp-btn" href="{{ $certificate->url() }}" target="_blank" rel="noopener" data-testid="path-cert">🎓 Ver el certificado de la ruta</a>
                @elseif ($status['next'])
                    @php $nextStep = collect($status['steps'])->firstWhere('current', true); @endphp
                    <a class="lp-btn" href="{{ $nextStep['owned'] ? route('courses.course-taking', ['slug' => $status['next']->slug]) : route('courses.course-detail', ['slug' => $status['next']->slug]) }}" data-testid="path-next">
                        {{ $nextStep['owned'] ? ($nextStep['progress'] > 0 ? 'Continuar: ' : 'Empezar: ') : 'Siguiente paso: ' }}{{ html_entity_decode($status['next']->title) }}
                    </a>
                @endif
            @else
                <p class="lp-note">{{ auth()->check() ? '' : 'Inicia sesión para ver tu avance en la ruta.' }}</p>
            @endif
        </header>

        <ol class="lp-steps" data-testid="path-steps">
            @foreach ($status['steps'] as $i => $step)
                @php $c = $step['course']; @endphp
                <li class="lp-step {{ $step['done'] ? 'is-done' : '' }} {{ $step['current'] && $student ? 'is-current' : '' }}" data-testid="path-step">
                    <span class="lp-step__n" aria-hidden="true">{{ $step['done'] ? '✓' : $i + 1 }}</span>
                    <img src="{{ !empty($c->thumbnail?->path) ? mediaThumb($c->thumbnail->path, 240) : asset('modules/courses/images/course.png') }}" alt="" loading="lazy">
                    <div class="lp-step__body">
                        <h2><a href="{{ route('courses.course-detail', ['slug' => $c->slug]) }}">{{ html_entity_decode($c->title) }}</a></h2>
                        <p>
                            @if ($c->content_length)<span>{{ getCourseDuration($c->content_length) }}</span>@endif
                            @if ($student)
                                @if ($step['done'])<span class="lp-tag lp-tag--ok">Terminado</span>
                                @elseif ($step['owned'])<span class="lp-tag">En curso · {{ $step['progress'] }}%</span>
                                @else<span class="lp-tag lp-tag--off">Aún no lo tienes</span>@endif
                            @endif
                            @if (!$student || !$step['owned'])<strong>@if ((float) ($c->pricing?->final_price ?? 0) > 0){!! formatAmount($c->pricing->final_price, true) !!}@else Gratis @endif</strong>@endif
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
@endsection
