<x-mail::message>
# Nueva pregunta en tu curso

**{{ \App\Services\CommunityService::displayName($post->author) }}** preguntó en «{{ html_entity_decode($course->title) }}»:

> **{{ $post->title }}**
> {{ \Illuminate\Support\Str::limit($post->body, 280) }}

<x-mail::button :url="$url">
Responder en la comunidad
</x-mail::button>

Para no saturar tu correo, te avisamos de una pregunta por hora como máximo. Las demás las ves en la comunidad del curso.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
