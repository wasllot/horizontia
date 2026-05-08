<x-mail::message>
# Hola, {{ $studentName }}

Se ha programado una nueva sesión "En Vivo" para el curso **{{ $liveStream->course->title }}**.

**Detalles de la sesión:**
- **Tema:** {{ $liveStream->title }}
- **Fecha y hora:** {{ $liveStream->date_time->format('d/m/Y h:i A') }}
@if($liveStream->duration_minutes)
- **Duración:** {{ $liveStream->duration_minutes }} minutos
@endif

@if($liveStream->description)
{{ $liveStream->description }}
@endif

@if($liveStream->meeting_link)
<x-mail::button :url="$liveStream->meeting_link">
Unirse a la llamada
</x-mail::button>
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
