<x-mail::message>
# ¡Felicidades, {{ $cert->student_name }}!

Terminaste **{{ $cert->course_title }}** y tu certificado ya está listo. Tiene un código único (**{{ $cert->code }}**) que cualquiera puede verificar en línea.

<x-mail::button :url="$url">
Ver mi certificado
</x-mail::button>

Puedes imprimirlo o guardarlo en PDF desde esa página, y agregarlo a tu perfil profesional:

<x-mail::button :url="$linkedin" color="success">
Agregar a LinkedIn
</x-mail::button>

Gracias por aprender con nosotros,<br>
{{ config('app.name') }}
</x-mail::message>
