<x-mail::message>
# {{ $byInstructor ? 'Tu instructor respondió' : 'Respondieron tu pregunta' }}

Sobre «{{ $root->title }}»:

> {{ \Illuminate\Support\Str::limit($reply->body, 400) }}

<x-mail::button :url="$url">
Ver la conversación
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
