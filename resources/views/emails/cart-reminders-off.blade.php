@extends('layouts.frontend-app', ['pageTitle' => 'Recordatorios desactivados', 'pageDescription' => 'Ya no recibirás recordatorios de carrito.'])


@section('content')
<section class="ht-pend">
    <div class="ht-pend__card">
        <svg class="ht-pend__icon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#1a9a5b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.5"/></svg>
        <h1>Listo, no te enviaremos más recordatorios de carrito</h1>
        <p>Seguirás recibiendo los correos de tus compras y de tus cursos. Puedes volver a comprar cuando quieras.</p>
        <p class="ht-pend__links"><a href="{{ route('courses.search-courses') }}">Ver cursos</a></p>
    </div>
</section>
@endsection
