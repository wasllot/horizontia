<?php

/**
 * Script puntual para diagnosticar y arreglar el botón "Agregar Curso" que no aparece
 * en el panel de administrador a pesar de que el archivo fuente del módulo ya está actualizado.
 *
 * Ataca las dos causas más probables en un solo paso:
 *  1. Una copia "override" vieja publicada en resources/views/modules/courses (tiene
 *     prioridad sobre la vista real del módulo Courses, así que si existe y está desactualizada,
 *     Laravel siempre la va a mostrar sin importar qué tan actualizado esté el módulo).
 *  2. OPcache sirviendo bytecode PHP viejo sin revalidar contra el archivo en disco.
 *
 * Sube este archivo a la carpeta pública del dominio (junto a run_deploy.php) y ábrelo
 * desde el navegador: tudominio.com/fix_course_view.php
 *
 * ATENCIÓN: por seguridad, ELIMINA este archivo del servidor después de usarlo.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$vendorPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../core-horizontia/vendor/autoload.php',
    __DIR__ . '/core-horizontia/vendor/autoload.php',
];

$appRoot = null;
foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require $path;
        $candidateRoot = dirname($path, 2);
        $appPath = $candidateRoot . '/bootstrap/app.php';
        if (file_exists($appPath)) {
            $app = require_once $appPath;
            $appRoot = $candidateRoot;
            break;
        }
    }
}

if (!$appRoot) {
    die("<h1>Error crítico: No se encuentra 'vendor/autoload.php'</h1>
         <p>Rutas intentadas:</p>
         <ul><li>" . implode('</li><li>', $vendorPaths) . "</li></ul>");
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Artisan;

echo "<h1>Diagnóstico: botón 'Agregar Curso'</h1>";

echo "<h2>1. Buscando vista override en resources/views/modules/courses...</h2>";

$overrideDir = $appRoot . '/resources/views/modules/courses';

if (is_dir($overrideDir)) {
    echo "<p style='color:red;'>⚠️ Se encontró una carpeta override: <code>" . htmlspecialchars($overrideDir) . "</code></p>";

    $overrideFile = $overrideDir . '/livewire/admin/course-listing.blade.php';
    if (file_exists($overrideFile)) {
        $hasButton = str_contains(file_get_contents($overrideFile), 'admin-create-course-btn');
        echo "<p>El archivo override " . ($hasButton ? 'SÍ' : '<strong>NO</strong>') . " contiene el botón (esto explicaría por qué no aparece).</p>";
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($overrideDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($overrideDir);

    echo "<p style='color:green;'>✅ Carpeta override eliminada. Laravel ahora usará la vista real del módulo Courses.</p>";
} else {
    echo "<p style='color:green;'>No existe carpeta override. La vista del módulo se resuelve directamente (esta no era la causa).</p>";
}

echo "<h2>2. Reiniciando OPcache...</h2>";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p style='color:green;'>✅ OPcache reiniciado.</p>";
} else {
    echo "<p>OPcache no está activo o no disponible en este servidor (no era la causa).</p>";
}

echo "<h2>3. Limpiando cachés de Laravel...</h2>";
Artisan::call('optimize:clear');
echo nl2br(Artisan::output());

echo "<h2 style='color:green;'>Listo. Recarga el panel de administrador con Ctrl+F5 y revisa si aparece el botón.</h2>";
echo "<p style='color:red;'><strong>IMPORTANTE: elimina 'fix_course_view.php' de tu servidor ahora mismo.</strong></p>";
