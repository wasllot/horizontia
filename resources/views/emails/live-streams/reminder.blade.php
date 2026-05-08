<x-mail::message>
# Hola, {{ $studentName }}

Este es un recordatorio de que la sesión "En Vivo" del curso **{{ $liveStream->course->title }}** comenzará pronto.

**Detalles de la sesión:**
- **Tema:** {{ $liveStream->title }}
- **Fecha y hora:** {{ $liveStream->date_time->format('d/m/Y h:i A') }}
@if($liveStream->duration_minutes)
- **Duración aproximada:** {{ $liveStream->duration_minutes }} minutos
@endif

@if($liveStream->meeting_link)
<x-mail::button :url="$liveStream->meeting_link">
Unirse a la llamada
</x-mail::button>
@endif

¡Te esperamos!

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
