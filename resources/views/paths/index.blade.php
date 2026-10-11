@extends('layouts.frontend-app', ['pageTitle' => 'Rutas de aprendizaje', 'pageDescription' => 'Cursos en el orden recomendado para llegar de cero a dominar un tema, con certificado final.'])

@section('content')
<link rel="stylesheet" href="{{ asset('css/bundles.css') }}?v={{ @filemtime(public_path('css/bundles.css')) }}">
<link rel="stylesheet" href="{{ asset('css/paths.css') }}?v={{ @filemtime(public_path('css/paths.css')) }}">
<section class="bn-page">
    <div class="bn-wrap">
        <header class="bn-head">
            <h1>Rutas de aprendizaje</h1>
            <p class="bn-sub">Una guía paso a paso: los cursos en el orden recomendado y un certificado al terminar toda la ruta.</p>
        </header>
        @if ($paths->isEmpty())
            <p>Por ahora no hay rutas publicadas. <a href="{{ route('courses.search-courses') }}">Explora los cursos</a>.</p>
        @else
            <div class="bn-grid">
                @foreach ($paths as $p)
                    <a class="bn-card" href="{{ route('paths.show', ['slug' => $p->slug]) }}" data-testid="path-card">
                        <div class="bn-card__covers">
                            @foreach ($p->courses->take(3) as $c)
                                <img src="{{ !empty($c->thumbnail?->path) ? mediaThumb($c->thumbnail->path, 240) : asset('modules/courses/images/course.png') }}" alt="" loading="lazy">
                            @endforeach
                        </div>
                        <h2>{{ $p->title }}</h2>
                        <p>{{ $p->subtitle ?: $p->courses->count() . ' cursos en orden' }}</p>
                        <div class="bn-card__foot"><strong>{{ $p->courses->count() }} cursos</strong><span class="bn-save">Con certificado</span></div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
