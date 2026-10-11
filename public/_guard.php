<?php
/**
 * Guard compartido por los scripts de mantenimiento de public/.
 * Falla cerrado: sin DEPLOY_TOKEN definido en .env nadie entra.
 * Uso: https://tusitio.com/script.php?key=TU_TOKEN  (o cabecera X-Deploy-Token)
 */
(function () {
    $envFiles = [__DIR__ . '/../.env', __DIR__ . '/../core-horizontia/.env'];
    $token = '';
    foreach ($envFiles as $file) {
        if (!is_readable($file)) {
            continue;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(ltrim($line), 'DEPLOY_TOKEN=')) {
                $token = trim(substr(ltrim($line), strlen('DEPLOY_TOKEN=')), " \t\"'");
                break 2;
            }
        }
    }

    $given = $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? ($_GET['key'] ?? '');

    if (strlen($token) < 24 || !is_string($given) || !hash_equals($token, $given)) {
        http_response_code(404);
        exit;
    }
})();
