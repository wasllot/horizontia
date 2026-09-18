<?php

/**
 * Script puntual para detectar cursos sin imagen de portada y notificar a los tutores afectados.
 *
 * Sube este archivo a la carpeta pública del dominio y ábrelo desde el navegador:
 *   tudominio.com/check_thumbnails.php?token=horizontia2026          → solo muestra el reporte
 *   tudominio.com/check_thumbnails.php?token=horizontia2026&notify=1 → reporte + envía email a cada tutor afectado
 *
 * ATENCIÓN: por seguridad, ELIMINA este archivo del servidor después de usarlo.
 */

// ── Protección por token ───────────────────────────────────────────────────
// Llama al script con ?token=horizontia2026 o cambia el valor aquí.
$TOKEN_VALIDO = 'horizontia2026';
if (($_GET['token'] ?? '') !== $TOKEN_VALIDO) {
    http_response_code(403);
    die('<h2 style="color:red;">Acceso denegado.</h2><p>Añade <code>?token=' . $TOKEN_VALIDO . '</code> a la URL.</p>');
}
// ──────────────────────────────────────────────────────────────────────────

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

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Models\Course;

$notify = isset($_GET['notify']) && $_GET['notify'] === '1';
$disk   = getStorageDisk();

echo "<h1>Diagnóstico: imágenes de portada de cursos</h1>";

echo "<h2>1. Escaneando cursos...</h2>";

$courses  = Course::with(['thumbnail', 'instructor'])->get();
$affected = $courses->filter(function ($course) use ($disk) {
    if (!$course->thumbnail || !$course->thumbnail->path) return true;
    return !Storage::disk($disk)->exists($course->thumbnail->path);
});

echo "<p>Total de cursos: <strong>{$courses->count()}</strong></p>";

if ($affected->isEmpty()) {
    echo "<p style='color:green;'>✅ Todos los cursos tienen imagen de portada válida. No se requiere acción.</p>";
} else {
    echo "<p style='color:red;'>⚠️ Se encontraron <strong>{$affected->count()}</strong> curso(s) sin imagen de portada válida:</p>";

    echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;'>";
    echo "<tr style='background:#14213d;color:#fed304;'><th>ID</th><th>Título</th><th>Tutor</th><th>Email</th><th>Problema</th></tr>";

    foreach ($affected as $course) {
        $problema = empty($course->thumbnail?->path) ? 'Sin registro en BD' : 'Archivo faltante en disco';
        echo "<tr>";
        echo "<td>{$course->id}</td>";
        echo "<td>" . htmlspecialchars($course->title) . "</td>";
        echo "<td>" . htmlspecialchars($course->instructor?->name ?? '—') . "</td>";
        echo "<td>" . htmlspecialchars($course->instructor?->email ?? '—') . "</td>";
        echo "<td style='color:red;'>{$problema}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>2. Notificación a tutores</h2>";

if (!$notify) {
    echo "<p>Para enviar emails a los tutores afectados, abre:</p>";
    $token = htmlspecialchars($TOKEN_VALIDO);
    echo "<p><code>" . htmlspecialchars("https://{$_SERVER['HTTP_HOST']}/check_thumbnails.php?token={$TOKEN_VALIDO}&notify=1") . "</code></p>";
} elseif ($affected->isEmpty()) {
    echo "<p style='color:green;'>No hay tutores que notificar.</p>";
} else {
    foreach ($affected->groupBy('instructor_id') as $group) {
        $instructor = $group->first()->instructor;
        if (!$instructor) {
            echo "<p style='color:orange;'>⚠️ Curso ID {$group->first()->id} sin instructor asignado — omitido.</p>";
            continue;
        }

        Mail::raw(
            "Hola {$instructor->name},\n\n"
            . "Notamos que los siguientes cursos no tienen imagen de portada:\n\n"
            . $group->map(fn($c) => "  • {$c->title}")->implode("\n")
            . "\n\nPor favor sube una imagen entrando a:\n"
            . "  Mis Cursos → (editar el curso) → Media → Subir imagen de portada\n\n"
            . "Si tienes dudas responde este correo.\n\n"
            . "Gracias,\nEquipo Horizontia",
            fn($m) => $m
                ->to($instructor->email, $instructor->name)
                ->subject('Acción requerida: imagen de portada faltante en tus cursos')
        );

        echo "<p style='color:green;'>✅ Email enviado a <strong>{$instructor->email}</strong> ({$group->count()} curso(s)).</p>";
    }
}

echo "<br><p style='color:red;'><strong>IMPORTANTE: elimina 'check_thumbnails.php' de tu servidor ahora mismo.</strong></p>";
