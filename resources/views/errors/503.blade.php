<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{  setting('_general.site_name') }}</title>
        <x-favicon />
        <x-typography />
        <link rel="stylesheet" href="{{ asset('css/maintenance.css') }}">
    </head>
<body>

    @if (true)
    <div class="ln-container">
        <div class="ln-wrapper">
            <div class="ln-content">
                <strong>
                    @if (!empty(setting('_maintenance.maintenance_logo')[0]['path']))
                        <img src="{{ url(mediaUrl(setting('_maintenance.maintenance_logo')[0]['path'])) }}" alt="{{ setting('_general.site_name') ?: config('app.name') }}">
                    @else
                        <img src="{{ asset('images/nuevo/logo-horizontia.png') }}" alt="{{ setting('_general.site_name') ?: config('app.name') }}" style="max-height: 90px; width: auto;">
                    @endif
                </strong>
                <h3>{{ setting('_maintenance.maintenance_title') ?: __('general.we_are_under_maintenance') }}</h3>
                @if (!empty(setting('_maintenance.maintenance_description')))
                    <p>{{ setting('_maintenance.maintenance_description') }}</p>
                @endif
                @if (!empty(setting('_maintenance.maintenance_email')))
                    <span>{{ __('general.please_contact_us_at') }}: <a href="mailto:{{ setting('_maintenance.maintenance_email') }}">{{ setting('_maintenance.maintenance_email') }}</a></span>
                @endif
            </div>
        </div>
    </div>
    @else
        <h1>{{ __('general.we_are_under_maintenance') }}</h1>
    @endif
    
</body>
</html>