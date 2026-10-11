<x-mail::message>
# Hola, {{ $name }}

@foreach ($courses as $c)
@if ($c['started'])
Llevas **{{ $c['progress'] }}%** de **{{ html_entity_decode($c['title']) }}**@if ($c['progress'] < 99 && \App\Services\CourseCertificateService::enabled()). Te falta {{ 100 - $c['progress'] }}% para tu certificado @endif.
@else
Todavía no empiezas **{{ html_entity_decode($c['title']) }}**. Son unos minutos al día y lo tienes.
@endif
@if ($c['lesson'])
Sigue con: _{{ html_entity_decode($c['lesson']) }}_.
@endif

<x-mail::button :url="$c['url']">
{{ $c['started'] ? 'Continuar mi curso' : 'Empezar mi curso' }}
</x-mail::button>

@endforeach
Tu avance se guarda solo: entras y continúas en la lección donde lo dejaste.

Gracias,<br>
{{ config('app.name') }}

<x-mail::subcopy>
Te escribimos porque tienes cursos en curso. [No quiero recibir estos recordatorios]({{ $unsubscribeUrl }}).
</x-mail::subcopy>
</x-mail::message>
