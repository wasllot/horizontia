@extends('layouts.frontend-app', ['pageTitle' => 'Paquetes de cursos', 'pageDescription' => 'Varios cursos juntos con un precio menor que comprarlos por separado.'])

@section('content')
<link rel="stylesheet" href="{{ asset('css/bundles.css') }}?v={{ @filemtime(public_path('css/bundles.css')) }}">
<section class="bn-page">
    <div class="bn-wrap">
        <header class="bn-head">
            <h1>Paquetes de cursos</h1>
            <p class="bn-sub">Varios cursos juntos, con un precio menor que comprarlos por separado.</p>
        </header>
        @if ($bundles->isEmpty())
            <p>Por ahora no hay paquetes disponibles. <a href="{{ route('courses.search-courses') }}">Explora los cursos</a>.</p>
        @else
            <div class="bn-grid">
                @foreach ($bundles as $b)
                    <a class="bn-card" href="{{ route('courses.bundles.show', ['slug' => $b->slug]) }}" data-testid="bundle-card">
                        <div class="bn-card__covers">
                            @foreach ($b->courses->take(3) as $c)
                                <img src="{{ !empty($c->thumbnail?->path) ? mediaThumb($c->thumbnail->path, 240) : asset('modules/courses/images/course.png') }}" alt="" loading="lazy">
                            @endforeach
                        </div>
                        <h2>{{ $b->title }}</h2>
                        <p>{{ $b->subtitle ?: $b->courses->count() . ' cursos incluidos' }}</p>
                        <div class="bn-card__foot">
                            <strong>{!! formatAmount($b->price, true) !!}</strong>
                            @if ($b->list > $b->price)<s>{!! formatAmount($b->list, true) !!}</s><span class="bn-save">-{{ $b->percent }}%</span>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
