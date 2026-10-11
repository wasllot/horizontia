<?php
require __DIR__ . "/_guard.php";
/**
 * Diagnóstico y reparación de paquetes SCORM.
 * Sube a: public/scorm_diag.php → visita tusitio.com/scorm_diag.php
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
<title>SCORM Diagnóstico</title>
<style>body{font-family:monospace;padding:24px;background:#0d1117;color:#e6edf3;max-width:960px;}
h1,h2{color:#58a6ff;} .ok{color:#3fb950;} .err{color:#f85149;} .warn{color:#ffa657;}
table{width:100%;border-collapse:collapse;} td,th{padding:8px 12px;border:1px solid #30363d;text-align:left;}
th{background:#161b22;} tr:nth-child(even){background:#0d1117;} tr:nth-child(odd){background:#161b22;}
pre{background:#161b22;padding:12px;border-radius:6px;overflow-x:auto;white-space:pre-wrap;}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;}
.badge-ok{background:#238636;} .badge-err{background:#b62324;} .badge-warn{background:#9e6a03;}</style></head><body>";

echo "<h1>🔍 SCORM Diagnóstico — Horizontia</h1>";

if (!$app) {
    echo "<p class='err'>❌ No se pudo cargar Laravel.</p></body></html>";
    exit;
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Services\ScormService;

// ── 1. Buscar todos los currículos de tipo SCORM ──────────────────────────
echo "<h2>1. Currículos SCORM en base de datos</h2>";

try {
    $scorms = DB::table('curriculum')
        ->where('type', 'scorm')
        ->select('id', 'title', 'media_path', 'created_at')
        ->orderByDesc('created_at')
        ->get();

    if ($scorms->isEmpty()) {
        echo "<p class='warn'>⚠️ No hay currículos de tipo 'scorm' en la BD.</p>";
    } else {
        echo "<table><thead><tr><th>ID</th><th>Título</th><th>media_path (BD)</th><th>URL generada</th><th>Archivo existe</th></tr></thead><tbody>";
        foreach ($scorms as $s) {
            $absPath = $s->media_path ? public_path($s->media_path) : null;
            $exists  = $absPath && file_exists($absPath);
            $url     = $s->media_path ? url($s->media_path) : '—';

            $badge = $exists
                ? "<span class='badge badge-ok'>✅ Existe</span>"
                : "<span class='badge badge-err'>❌ NO existe</span>";

            echo "<tr>
                <td>{$s->id}</td>
                <td>" . htmlspecialchars($s->title ?? '') . "</td>
                <td><code>" . htmlspecialchars($s->media_path ?? '—') . "</code></td>
                <td><a href='$url' target='_blank' style='color:#58a6ff'>$url</a></td>
                <td>$badge</td>
            </tr>";
        }
        echo "</tbody></table>";
    }
} catch (\Throwable $e) {
    echo "<p class='err'>❌ Error consultando BD: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ── 2. Archivos temporales SCORM (ZIPs pendientes) ────────────────────────
echo "<h2>2. ZIPs temporales en disco (scorm_temp/)</h2>";

$diskName   = function_exists('getStorageDisk') ? getStorageDisk() : 'public';
$scormTemps = [];
try {
    $scormTemps = Storage::disk($diskName)->files('scorm_temp');
} catch (\Throwable $e) {}

if (empty($scormTemps)) {
    echo "<p style='color:#8b949e;'>No hay ZIPs en scorm_temp/.</p>";
} else {
    echo "<ul>";
    foreach ($scormTemps as $f) {
        $size = Storage::disk($diskName)->size($f);
        echo "<li><code>$f</code> (" . round($size / 1024, 1) . " KB)</li>";
    }
    echo "</ul>";
}

// ── 3. Carpetas ya extraídas en public/storage/scorm/ ─────────────────────
echo "<h2>3. Carpetas SCORM extraídas en public/storage/scorm/</h2>";
$scormBase = public_path('storage/scorm');
if (!is_dir($scormBase)) {
    echo "<p class='warn'>⚠️ La carpeta <code>public/storage/scorm/</code> no existe.</p>";
} else {
    $dirs = glob($scormBase . '/*', GLOB_ONLYDIR);
    if (empty($dirs)) {
        echo "<p class='err'>❌ Existe la carpeta pero está vacía — ningún SCORM fue extraído.</p>";
    } else {
        echo "<table><thead><tr><th>Carpeta (ID)</th><th>Archivos</th><th>Entry point</th><th>URL</th></tr></thead><tbody>";
        foreach ($dirs as $dir) {
            $id    = basename($dir);
            $files = iterator_count(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)));
            $entry = null;
            foreach (['index.html', 'story.html', 'story_html5.html', 'index_lms.html'] as $f) {
                if (file_exists($dir . '/' . $f)) { $entry = $f; break; }
            }
            if (!$entry) {
                // check manifest
                $mf = $dir . '/imsmanifest.xml';
                $entry = file_exists($mf) ? '(ver manifest)' : '❌ NO encontrado';
            }
            $url = url("storage/scorm/$id/$entry");
            echo "<tr><td>$id</td><td>$files</td><td><code>$entry</code></td>
                <td><a href='$url' target='_blank' style='color:#58a6ff'>$url</a></td></tr>";
        }
        echo "</tbody></table>";
    }
}

// ── 4. Re-extraer un SCORM si tiene ZIP pero no carpeta ───────────────────
$action = $_GET['reextract'] ?? null;
if ($action) {
    echo "<h2>4. Re-extrayendo curriculum ID: " . intval($action) . "</h2>";
    try {
        $row = DB::table('curriculum')->where('id', intval($action))->first();
        if (!$row) { echo "<p class='err'>❌ Curriculum no encontrado.</p>"; }
        else {
            // Buscar zip en scorm_temp
            $zips = Storage::disk($diskName)->files('scorm_temp');
            if (empty($zips)) {
                echo "<p class='err'>❌ No hay ZIPs en scorm_temp/ para re-extraer.
                      Debes volver a subir el paquete SCORM desde el panel.</p>";
            } else {
                $zip = $zips[0]; // tomar el más reciente si hay varios
                $svc = new ScormService();
                $entry = $svc->processScormZip($zip, $row->id);
                if ($entry) {
                    DB::table('curriculum')->where('id', $row->id)
                        ->update(['media_path' => $entry]);
                    echo "<p class='ok'>✅ Re-extraído. Nuevo media_path: <code>$entry</code></p>";
                    echo "<p>URL: <a href='" . url($entry) . "' target='_blank' style='color:#58a6ff'>" . url($entry) . "</a></p>";
                } else {
                    echo "<p class='err'>❌ La extracción falló. Revisa los logs de Laravel.</p>";
                }
            }
        }
    } catch (\Throwable $e) {
        echo "<p class='err'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<h2>4. Acciones</h2>
    <p>Para re-extraer un SCORM específico añade <code>?reextract=ID</code> a la URL
       (usando el ID del curriculum de la tabla de arriba).</p>
    <p class='warn'>⚠️ Requiere que el ZIP aún esté en <code>scorm_temp/</code>.
       Si ya no está, sube el paquete nuevamente desde el panel del tutor.</p>";
}

echo "<hr style='border-color:#30363d;margin:24px 0'>
<p style='color:#8b949e;'>⚠️ <strong>Elimina este archivo del servidor cuando termines.</strong></p>
</body></html>";
