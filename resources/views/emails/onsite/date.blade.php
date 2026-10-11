<x-mail::message>
# Hola, {{ $studentName }}

@switch($kind)
@case('reminder')
Te recordamos que **mañana** es tu curso presencial **{{ html_entity_decode($course->title) }}**.
@break
@case('changed')
Hubo un cambio en la fecha o sede de tu curso presencial **{{ html_entity_decode($course->title) }}**. Estos son los datos actualizados:
@break
@case('cancelled')
Lamentamos avisarte que la fecha que elegiste de **{{ html_entity_decode($course->title) }}** fue cancelada. Escríbenos y te ayudamos a elegir otra fecha o a tramitar tu reembolso.
@break
@default
¡Tu lugar está confirmado en **{{ html_entity_decode($course->title) }}**! Guarda estos datos:
@endswitch

@if($note)
> {{ $note }}

@endif
<x-mail::panel>
**Cuándo:** {{ $session->whenLabel() }}@if($session->schedule_note) · {{ $session->schedule_note }}@endif

**Dónde:** {{ $session->fullAddress() }}

@if($session->venue_notes)
**Indicaciones:** {{ $session->venue_notes }}

@endif
</x-mail::panel>

@if($kind !== 'cancelled')
<x-mail::button :url="$session->mapUrl()">
Cómo llegar
</x-mail::button>

Adjuntamos un archivo de calendario (.ics): ábrelo para añadir la fecha a tu agenda. También puedes ver tu fecha en [Mis cursos]({{ $myCoursesUrl }}).
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
