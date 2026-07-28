<?php
// Script ultra simple para borrar la caché de vistas sin cargar Laravel entero
$viewsPath = __DIR__ . '/../storage/framework/views';
if (is_dir($viewsPath)) {
    $files = glob($viewsPath . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== '.gitignore') {
            unlink($file);
            $count++;
        }
    }
    echo "<h2 style='color:green;'>¡Listo! Se borraron $count archivos de la caché de vistas.</h2>";
} else {
    echo "<h2 style='color:red;'>No se encontró la carpeta de caché.</h2>";
}
echo "<p>Ya puedes recargar tu panel de administrador (Ctrl + F5).</p>";
