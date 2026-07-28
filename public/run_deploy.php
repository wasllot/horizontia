<?php

/**
 * Script de despliegue para correr comandos Artisan desde el navegador.
 * Sube este archivo a tu carpeta 'public' (ej: public/run_deploy.php)
 * y ábrelo desde tu dominio: tusitio.com/run_deploy.php
 * 
 * ATENCIÓN: Por seguridad, ELIMINA este archivo después de usarlo.
 */

// Aumentar el tiempo límite de ejecución para backups grandes
set_time_limit(300);

// Cargar las dependencias y la aplicación de Laravel
$vendorPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../core-horizontia/vendor/autoload.php',
    __DIR__ . '/core-horizontia/vendor/autoload.php',
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "<h1>Ejecutando Proceso de Migración Seguro...</h1>";

echo "<h2>1. Creando Backup de Base de Datos...</h2>";
try {
    $dbConnection = config('database.default');
    $dbHost = config("database.connections.{$dbConnection}.host");
    $dbPort = config("database.connections.{$dbConnection}.port");
    $dbName = config("database.connections.{$dbConnection}.database");
    $dbUser = config("database.connections.{$dbConnection}.username");
    $dbPass = config("database.connections.{$dbConnection}.password");

    $backupDir = storage_path('app/backups');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Ymd_His');
    $backupFile = "{$backupDir}/db_backup_{$timestamp}.sql";

    if ($dbConnection === 'mysql') {
        $command = sprintf(
            'mysqldump --user="%s" --password="%s" --host="%s" --port="%s" "%s" > "%s" 2>&1',
            $dbUser,
            $dbPass,
            $dbHost,
            $dbPort,
            $dbName,
            $backupFile
        );

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            echo "<p style='color: green;'>✅ Backup creado exitosamente: <b>" . basename($backupFile) . "</b></p>";
        } else {
            throw new \Exception("Error al crear el backup. " . implode("\n", $output));
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Tipo de base de datos ($dbConnection) no soportado automáticamente por este script web. Se procederá con cautela.</p>";
    }
} catch (\Exception $e) {
    echo "<p style='color: red;'>❌ ERROR FATAL AL CREAR BACKUP: " . $e->getMessage() . "</p>";
    echo "<p><b>ABORTO:</b> Por seguridad, las migraciones no se ejecutarán hasta que el backup funcione o lo realices manualmente.</p>";
    die();
}

echo "<h2>2. Ejecutando Comandos Artisan...</h2>";
try {
    echo "<b>Ejecutando optimize:clear...</b><br>";
    Artisan::call('optimize:clear');
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<b>Ejecutando migrate --force...</b><br>";
    Artisan::call('migrate', ['--force' => true]);
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<b>Ejecutando view:clear...</b><br>";
    Artisan::call('view:clear');
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<b>Ejecutando cache:clear...</b><br>";
    Artisan::call('cache:clear');
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<b>Ejecutando route:clear...</b><br>";
    Artisan::call('route:clear');
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<b>Ejecutando config:clear...</b><br>";
    Artisan::call('config:clear');
    echo nl2br(Artisan::output()) . "<br><br>";

    echo "<h2 style='color: green;'>¡Todos los comandos finalizados con éxito!</h2>";
    echo "<p style='color: red;'><strong>IMPORTANTE: Por razones de seguridad, elimina 'run_deploy.php' de tu servidor en Plesk ahora mismo.</strong></p>";

} catch (\Exception $e) {
    echo "<li><span class='error'>Error ejecutando comandos: " . $e->getMessage() . "</span></li>";
}
