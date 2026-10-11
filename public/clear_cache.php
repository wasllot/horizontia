<?php
require __DIR__ . "/_guard.php";
/**
 * Limpia toda la caché de Laravel sin correr migraciones.
 * Sube a: public/clear_cache.php → visita tusitio.com/clear_cache.php
 * ELIMINA este archivo después de usarlo.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$vendorPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../core-horizontia/vendor/autoload.php',
];

$app = null;
foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require $path;
        $appPath = dirname($path, 2) . '/bootstrap/app.php';
        if (file_exists($appPath)) {
            $app = require_once $appPath;
            break;
        }
    }
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'>
<title>Clear Cache</title>
<style>body{font-family:monospace;padding:24px;background:#0d1117;color:#e6edf3;}
h1{color:#58a6ff;} .ok{color:#3fb950;} .err{color:#f85149;} .cmd{color:#ffa657;}
pre{background:#161b22;padding:12px;border-radius:6px;}</style></head><body>";

echo "<h1>🧹 Horizontia — Clear Cache</h1>";

if (!$app) {
    echo "<p class='err'>❌ No se pudo cargar el bootstrap de Laravel.</p></body></html>";
    exit;
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Artisan;

$commands = [
    'config:clear'  => 'Configuración',
    'cache:clear'   => 'Caché de aplicación',
    'view:clear'    => 'Vistas compiladas',
    'route:clear'   => 'Rutas',
    'event:clear'   => 'Eventos',
];

foreach ($commands as $cmd => $label) {
    try {
        echo "<p><span class='cmd'>php artisan $cmd</span> — $label... ";
        Artisan::call($cmd);
        $out = trim(Artisan::output());
        echo "<span class='ok'>✅ OK</span>";
        if ($out) echo "<pre>$out</pre>";
        echo "</p>";
    } catch (\Throwable $e) {
        echo "<span class='err'>❌ " . htmlspecialchars($e->getMessage()) . "</span></p>";
    }
}

// También limpia manualmente bootstrap/cache
$bootstrapCache = dirname(__DIR__) . '/bootstrap/cache';
$cleared = 0;
foreach (glob($bootstrapCache . '/*.php') as $f) {
    if (!in_array(basename($f), ['packages.php', 'services.php', 'modules.php'])) {
        @unlink($f);
        $cleared++;
    }
}
echo "<p class='ok'>✅ Bootstrap cache: $cleared archivos eliminados</p>";

echo "<hr style='border-color:#30363d;margin:24px 0'>
<p style='color:#8b949e;'>⚠️ <strong>Elimina este archivo del servidor.</strong></p>
</body></html>";
