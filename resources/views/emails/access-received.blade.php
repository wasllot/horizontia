<x-mail::message>
# Hola, {{ explode(' ', trim($request->name))[0] }}

Recibimos tu solicitud para entrar a **{{ config('app.name') }}** como **{{ $request->type === 'tutor' ? 'instructor' : 'estudiante' }}**.

Nuestro equipo la revisa y te respondemos por este correo, normalmente en uno o dos días hábiles. Cuando la aprobemos recibirás un enlace para crear tu contraseña y entrar.

Si no fuiste tú, ignora este mensaje: no se crea ninguna cuenta sin aprobación.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
