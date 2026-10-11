<x-mail::message>
# Nueva solicitud de acceso

**{{ $request->name }}** ({{ $request->email }}) pide entrar como **{{ $request->typeLabel() }}**.

@if ($request->message)
> {{ \Illuminate\Support\Str::limit($request->message, 400) }}
@endif
@if ($request->link)

Enlace: {{ $request->link }}
@endif

<x-mail::button :url="$url">
Revisar solicitud
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
