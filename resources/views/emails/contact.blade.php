<x-mail::message>
# Nuevo Mensaje de Contacto

Has recibido un nuevo mensaje desde el formulario de contacto de la plataforma.

**Nombre:** {{ $data['name'] }}  
**Correo:** {{ $data['email'] }}  
**Asunto:** {{ $data['subject'] }}  

**Mensaje:**  
{{ $data['message'] }}

<x-mail::button :url="'mailto:'.$data['email']">
Responder al usuario
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
