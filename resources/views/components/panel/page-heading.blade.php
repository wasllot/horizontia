@props(['html' => ''])
@php
    // Cada página del panel debe tener un título de nivel 1 para lectores de pantalla. Si el contenido ya trae su <h1>, no se duplica;
    // si no, se añade uno oculto con el nombre de la página (la última miga de pan).
    $has = (bool) preg_match('/<h1[\s>]/i', (string) $html);
    $title = null;
    if (!$has) {
        try {
            $title = \Diglactic\Breadcrumbs\Breadcrumbs::generate()->last()?->title;
        } catch (\Throwable $e) {
            $title = null;
        }
        $title = $title ?: (setting('_general.site_name') ?: config('app.name'));
    }
@endphp
@unless ($has)<h1 class="hz-sr">{{ $title }}</h1>@endunless
