<x-mail::message>
# ¡Bienvenido{{ $request->type === 'tutor' ? ', instructor' : '' }}, {{ explode(' ', trim($request->name))[0] }}!

Aprobamos tu acceso a **{{ config('app.name') }}** como **{{ $request->type === 'tutor' ? 'instructor' : 'estudiante' }}**. Solo falta que elijas tu contraseña:

<x-mail::button :url="$activationUrl">
Crear mi contraseña
</x-mail::button>

El enlace funciona una sola vez y vence en 7 días. Si caduca, entra a «Iniciar sesión» y usa «Olvidé mi contraseña» con este mismo correo.

@if ($request->type === 'tutor')
Después de entrar podrás completar tu perfil y crear tu primer curso.
@else
Después de entrar podrás explorar el catálogo, inscribirte en un curso y estudiar a tu ritmo.
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
