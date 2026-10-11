<?php
require __DIR__ . '/_guard.php';   // sin DEPLOY_TOKEN (24+ caracteres) en el .env responde 404: cerrado por defecto

/**
 * Panel de tareas de mantenimiento para hostings SIN SSH (Plesk, cPanel…).
 *   https://tudominio.com/tareas.php?key=TU_DEPLOY_TOKEN                 → lista de tareas
 *   https://tudominio.com/tareas.php?key=TU_DEPLOY_TOKEN&accion=estado   → ejecuta una
 * Solo ejecuta las acciones de la lista blanca de abajo (nunca comandos libres).
 * BÓRRALO del servidor cuando termines (Administración → Estado del sistema te lo recuerda).
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

$candidates = [__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../core-horizontia/vendor/autoload.php'];
$app = null;
foreach ($candidates as $autoload) {
    if (is_file($autoload)) {
        require $autoload;
        $app = require_once dirname($autoload, 2) . '/bootstrap/app.php';
        break;
    }
}
if (!$app) {
    http_response_code(500);
    exit('No se encontró vendor/autoload.php: sube el paquete completo (incluye la carpeta vendor).');
}
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$actions = [
    'estado'   => ['Revisión previa (seguridad, correo, tareas…)', 'academy:preflight', ['--no-dns' => true]],
    'migrar'   => ['1. Crear/actualizar las tablas de la base de datos', 'migrate', ['--force' => true]],
    'instalar' => ['2. Instalación limpia: roles, catálogos, plantillas, páginas en español y el administrador (solo si aún no hay usuarios)', 'db:seed', ['--class' => 'Database\\Seeders\\ProductionSeeder', '--force' => true]],
    'enlace'   => ['Crear el enlace public/storage', 'storage:link', []],
    'limpiar'  => ['Limpiar cachés (vistas, configuración, rutas)', 'optimize:clear', []],
    'paginas'  => ['Crear las páginas legales y informativas en español', 'academy:pages', []],
    'respaldo' => ['Respaldo de la base de datos ahora', 'app:backup-database', []],
    'cron'     => ['Ejecutar el planificador una vez (como el cron)', 'schedule:run', []],
    'cola'     => ['Procesar la cola de correos durante unos segundos', 'queue:work', ['--stop-when-empty' => true, '--max-time' => 20, '--tries' => 3]],
];

$key = htmlspecialchars((string) ($_GET['key'] ?? ''), ENT_QUOTES);
$run = (string) ($_GET['accion'] ?? '');
echo '<!doctype html><meta charset="utf-8"><title>Tareas</title><body style="font:15px/1.5 system-ui;max-width:760px;margin:30px auto;padding:0 16px">';
echo '<h1>Tareas de mantenimiento</h1><p>PHP ' . PHP_VERSION . ' · ' . htmlspecialchars(base_path(), ENT_QUOTES) . ' · entorno <b>' . app()->environment() . '</b></p>';

if ($run !== '') {
    if (!isset($actions[$run])) {
        http_response_code(400);
        exit('<p>Acción desconocida.</p>');
    }
    [$label, $command, $args] = $actions[$run];
    if ($run === 'instalar' && Illuminate\Support\Facades\DB::table('users')->count() > 0) {
        exit('<h2>' . htmlspecialchars($label) . '</h2><p style="color:#a00">Ya hay usuarios en esta base de datos: la instalación limpia no se ejecuta para no tocar nada. Si es una base nueva, revisa que apunte a la correcta.</p>');
    }
    echo '<h2>' . htmlspecialchars($label) . '</h2><pre style="background:#111;color:#eee;padding:14px;border-radius:8px;white-space:pre-wrap">';
    try {
        // Salida propia: Artisan::output() devuelve la de la ÚLTIMA llamada anidada (p. ej. optimize:clear dentro de un seeder) y perdería mensajes como la contraseña generada.
        $buffer = new Symfony\Component\Console\Output\BufferedOutput();
        $code = Illuminate\Support\Facades\Artisan::call($command, $args, $buffer);
        echo htmlspecialchars(trim($buffer->fetch()) ?: '(sin salida)');
        echo "\nCódigo de salida: {$code} " . ($code === 0 ? '✔' : '✖');
    } catch (Throwable $e) {
        echo 'ERROR: ' . htmlspecialchars($e->getMessage());
    }
    echo '</pre>';
}

echo '<h2>Acciones</h2><ul>';
foreach ($actions as $slug => [$label]) {
    echo "<li><a href=\"?key={$key}&accion={$slug}\">" . htmlspecialchars($label) . '</a></li>';
}
echo '</ul><p style="color:#a00"><b>Borra este archivo (tareas.php) y run_deploy.php del servidor cuando termines.</b></p>';
