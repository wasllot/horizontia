@extends('layouts.frontend-app', ['pageTitle' => 'Recordatorios desactivados', 'pageDescription' => 'Ya no recibirás recordatorios de avance.'])

@section('content')
<section class="ht-pend">
    <div class="ht-pend__card">
        <svg class="ht-pend__icon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#1a9a5b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.5"/></svg>
        <h1>Listo, no te enviaremos más recordatorios de avance</h1>
        <p>Tus cursos siguen disponibles y tu avance se conserva. Seguirás recibiendo los correos de tus compras.</p>
        <p class="ht-pend__links"><a href="{{ route('courses.course-list') }}">Ir a mis cursos</a></p>
    </div>
</section>
@endsection
