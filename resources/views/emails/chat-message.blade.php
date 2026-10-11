<x-mail::message>
# Tienes un mensaje nuevo

**{{ $name }}** te escribió:

> {{ \Illuminate\Support\Str::limit($message->body ?: '📎 Archivo adjunto', 300) }}

<x-mail::button :url="$url">
Responder
</x-mail::button>

Para no llenar tu correo, te avisamos una vez por hora como máximo mientras la conversación siga activa.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
