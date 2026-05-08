<?php

/**
 * Script de despliegue para correr comandos Artisan desde el navegador.
 * Sube este archivo a tu carpeta 'public' (ej: public/run_deploy.php)
 * y ábrelo desde tu dominio: tusitio.com/run_deploy.php
 * 
 * ATENCIÓN: Por seguridad, ELIMINA este archivo después de usarlo.
 */

// Cargar las dependencias y la aplicación de Laravel
// Determinar dinámicamente si el archivo está en 'public' o en la raíz
$vendorPaths = [
    __DIR__ . '/../core-horizontia/vendor/autoload.php',
    __DIR__ . '/core-horizontia/vendor/autoload.php',
    __DIR__ . '/../../core-horizontia/vendor/autoload.php',
];

$autoloadFound = false;
foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require $path;
        $appPath = dirname($path, 2) . '/bootstrap/app.php';
        if (file_exists($appPath)) {
            $app = require_once $appPath;
            $autoloadFound = true;
            break;
        }
    }
}

if (!$autoloadFound) {
    die("<h1>Error crítico: No se encuentra 'vendor/autoload.php'</h1>
         <p>Rutas intentadas:</p>
         <ul>
            <li>" . implode("</li><li>", $vendorPaths) . "</li>
         </ul>
         <p>Por favor, asegúrate de haber ejecutado <code>composer install</code> en tu servidor o de que las carpetas del core de Laravel ('vendor', 'bootstrap') existen.</p>");
}

// Iniciar el Kernel Http para tener acceso a los facades como Artisan
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\Artisan;

echo "<h1>Ejecutando Comandos Artisan...</h1>";

try {
    echo "<b>1. Ejecutando optimize:clear...</b><br>";
    Artisan::call('optimize:clear');
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<b>2. Ejecutando migrate --force...</b><br>";
    Artisan::call('migrate', ['--force' => true]);
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<b>3. Ejecutando view:clear...</b><br>";
    Artisan::call('view:clear');
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<b>4. Ejecutando cache:clear...</b><br>";
    Artisan::call('cache:clear');
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<b>5. Ejecutando route:clear...</b><br>";
    Artisan::call('route:clear');
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<b>6. Ejecutando config:clear...</b><br>";
    Artisan::call('config:clear');
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<h2 style='color: green;'>¡Todos los comandos finalizados con éxito!</h2>";
    echo "<p style='color: red;'><strong>IMPORTANTE: Por razones de seguridad, elimina 'run_deploy.php' de tu servidor ahora.</strong></p>";

} catch (\Exception $e) {
    echo "<li><span class='error'>Error al limpiar cachés: " . $e->getMessage() . "</span></li>";
}

echo "<h2>Diagnóstico:</h2>";
$viewPath = __DIR__.'/../core-horizontia/Modules/Courses/resources/views/livewire/tutor/live-streams/schedule-live-stream.blade.php';
if (file_exists($viewPath)) {
    echo "<li>El archivo de la vista existe en el servidor: " . realpath($viewPath) . "</li>";
} else {
    echo "<li><span class='error'>¡ADVERTENCIA! El directorio /core-horizontia/Modules/Courses/resources/views/livewire/tutor/live-streams/ NO EXISTE o la vista falta en el servidor!</span></li>";
}

echo "</ul>";
echo "<div class='success'>Proceso completado. Puede cerrar esta página y volver a la app.</div>";
