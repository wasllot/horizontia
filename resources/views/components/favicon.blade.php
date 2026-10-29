@php
    // El icono subido en Ajustes manda, salvo en las páginas de evento (marca propia de Horizontia) o si no hay ninguno.
    $uploaded = empty(setting('_general.favicon')) || request()->routeIs('event.*') ? null : url(mediaUrl(setting('_general.favicon')[0]['path']));
@endphp
@if ($uploaded)
    <link rel="icon" href="{{ $uploaded }}" type="image/x-icon">
@else
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon/favicon-192.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
@endif
