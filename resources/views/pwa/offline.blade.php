<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Sin conexión · {{ setting('_general.site_name') ?: config('app.name') }}</title>
    <meta name="theme-color" content="#14213d">
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #fffdf6; color: #14213d; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; text-align: center; padding: 24px; }
        main { max-width: 420px; }
        img { height: 64px; width: auto; margin-bottom: 18px; }
        h1 { margin: 0 0 8px; font-size: 1.6rem; }
        p { margin: 0 0 20px; color: #5b6478; line-height: 1.5; }
        button { padding: 12px 22px; border: 0; border-radius: 10px; background: #fed304; color: #14213d; font: 700 1rem inherit; cursor: pointer; }
    </style>
</head>
<body>
<main>
    <img src="{{ asset('images/nuevo/logo-horizontia.png') }}" alt="{{ setting('_general.site_name') ?: config('app.name') }}">
    <h1>Estás sin conexión</h1>
    <p>No pudimos conectarnos a internet. Tu avance está a salvo: cuando vuelva la conexión, continúa donde te quedaste.</p>
    <button type="button" onclick="location.reload()">Reintentar</button>
</main>
</body>
</html>
