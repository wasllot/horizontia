<x-mail::message>
# Hola, {{ explode(' ', trim($request->name))[0] }}

Gracias por tu interés en **{{ config('app.name') }}**. Por ahora no podemos aprobar tu solicitud de acceso.

@if ($request->review_note)
> {{ $request->review_note }}

@endif
Si crees que fue un error o quieres darnos más información, escríbenos desde la página de contacto y lo revisamos.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
