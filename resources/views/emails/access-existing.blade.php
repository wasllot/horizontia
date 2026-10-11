<x-mail::message>
# Hola, {{ explode(' ', trim($name))[0] }}

Pediste acceso a **{{ config('app.name') }}**, pero este correo **ya tiene una cuenta**.

<x-mail::button :url="route('login')">
Iniciar sesión
</x-mail::button>

Si no recuerdas tu contraseña, usa «Olvidé mi contraseña» en esa misma pantalla.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
